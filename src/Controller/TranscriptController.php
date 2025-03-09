<?php

namespace App\Controller;

use App\Repository\TranscriptRepository;
use App\Service\OpenAIService;
use App\Service\TextPreprocessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Entity\Episodes;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TranscriptController extends AbstractController
{
    private $openAIService;
    private $textPreprocessor;
    private $logger;

    public function __construct(OpenAIService $openAIService, TextPreprocessor $textPreprocessor, LoggerInterface $logger)
    {
        $this->openAIService = $openAIService;
        $this->textPreprocessor = $textPreprocessor;
        $this->logger = $logger;
    }

    #[Route('/transcript', name: 'app_transcript')]
    public function uploadTranscript(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $transcriptFile = $request->files->get('transcript');
            if ($transcriptFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/files/';
                $transcriptFileName = $transcriptFile->getClientOriginalName();
                $transcriptFile->move($uploadDir, $transcriptFileName);

                $filePath = $uploadDir . $transcriptFileName;
                $cleanedFilePath = $uploadDir . pathinfo($transcriptFileName, PATHINFO_FILENAME) . '_cleaned.txt';

                // Exécuter le script Python pour nettoyer le fichier de transcription
                $process = new Process(['python', $this->getParameter('kernel.project_dir') . '/scripts/preprocess_transcript.py', $filePath]);
                $process->run();

                // Vérifier si le processus a réussi
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                // Lire le contenu nettoyé
                $cleanedContent = file_get_contents($cleanedFilePath);

                // Ajouter le contenu nettoyé à la mémoire de l'IA : C'est à dire pour cette version dans la base de donnée
                $this->openAIService->addToMemory($cleanedContent);

                return new Response('Transcription uploaded and processed successfully.');
            }

            return new Response('No transcript file uploaded', 400);
        }

        // return $this->render('upload_transcript.html.twig');
        return $this->render('transcript/index.html.twig', [
            'controller_name' => 'TranscriptController',
        ]);
    }

    #[Route('/generate-questions', name: 'generate_questions')]
    public function generateQuestions(OpenAIService $openAIService, EntityManagerInterface $entityManager, TextPreprocessor $textPreprocessor): Response
    {
        try {
            // Récupérer l'épisode complété le plus récent
            $episode = $entityManager->getRepository(Episodes::class)
                ->findOneBy(
                    ['status' => Episodes::STATUS_COMPLETED],
                    ['releaseDate' => 'DESC']
                );

            if (!$episode) {
                throw new \RuntimeException('Aucun épisode disponible');
            }

            $transcript = $episode->getTranscript();

            if (empty($transcript)) {
                throw new \RuntimeException('Le transcript est vide');
            }

            // Log pour debug
            $this->logger->info('Transcript utilisé:', ['transcript_length' => strlen($transcript)]);

            // Prétraiter le transcript (chunking + résumé) avec mise en cache
            $processedTranscript = $textPreprocessor->processLargeTranscript($transcript, $episode->getId());
            
            if (empty($processedTranscript) || strpos($processedTranscript, 'Erreur') === 0) {
                $this->logger->warning('Utilisation d\'extraits du transcript original suite à une erreur de traitement');
                $processedTranscript = [];
                
                // Diviser manuellement le transcript en 4 parties égales
                $totalLength = strlen($transcript);
                $partLength = (int)($totalLength / 4);
                
                // Extrait du début (premier quart)
                $startPart = substr($transcript, 0, $partLength);
                $this->logger->info('Traitement du chunk du début', ['length' => strlen($startPart)]);
                $processedTranscript[] = "Voici un extrait du début du podcast:\n\n" . substr($startPart, 0, 3000) . "...";
                
                // Extrait du deuxième quart
                $secondPart = substr($transcript, $partLength, $partLength);
                $this->logger->info('Traitement du chunk du deuxième quart', ['length' => strlen($secondPart)]);
                $processedTranscript[] = "Voici un extrait du deuxième quart du podcast:\n\n" . substr($secondPart, 0, 3000) . "...";
                
                // Extrait du troisième quart
                $thirdPart = substr($transcript, $partLength * 2, $partLength);
                $this->logger->info('Traitement du chunk du troisième quart', ['length' => strlen($thirdPart)]);
                $processedTranscript[] = "Voici un extrait du troisième quart du podcast:\n\n" . substr($thirdPart, 0, 3000) . "...";
                
                // Extrait de la fin (dernier quart)
                $endPart = substr($transcript, $partLength * 3);
                $this->logger->info('Traitement du chunk de la fin', ['length' => strlen($endPart)]);
                $processedTranscript[] = "Voici un extrait de la fin du podcast:\n\n" . substr($endPart, 0, 3000) . "...";
            }
            
            $this->logger->info('Transcript traité:', [
                'processed_length' => strlen(implode("\n", $processedTranscript)),
                'processed_preview' => substr(implode("\n", $processedTranscript), 0, 200) . '...',
                'sections_count' => count($processedTranscript)
            ]);

            // Générer les questions à partir de chaque extrait
            $questions = [];
            foreach ($processedTranscript as $index => $transcriptPart) {
                $partType = $index == 0 ? "début" : ($index == count($processedTranscript) - 1 ? "fin" : "milieu");
                $this->logger->info('Génération de questions pour la partie', ['part' => $partType, 'length' => strlen($transcriptPart)]);
                
                $response = $openAIService->generateQuestion($transcriptPart);
                
                // Ajouter les questions générées à la liste
                if (isset($response['choices'][0]['message']['content'])) {
                    $newQuestions = explode("\n", $response['choices'][0]['message']['content']);
                    $questions = array_merge($questions, $newQuestions);
                }
            }

            // Limiter le nombre de questions si nécessaire
            if (count($questions) > 20) {
                $questions = array_slice($questions, 0, 20);
            }
            
            // Si aucune question n'a été générée, créer des questions génériques
            if (empty($questions)) {
                $questions = [
                    "1. Quels sont les principaux sujets abordés dans ce podcast ?",
                    "2. Qui sont les intervenants principaux et quels sont leurs rôles ?",
                    "3. Quelles sont les informations clés présentées dans cette discussion ?",
                    "4. Quelles conclusions ou recommandations sont formulées ?",
                    "5. Comment ce contenu pourrait-il être appliqué dans un contexte pratique ?"
                ];
                $this->logger->warning('Utilisation de questions génériques suite à un échec de génération');
            }
            
            // Ajouter un log pour vérifier les données avant le rendu
            $this->logger->info('Données pour le template:', [
                'episode_title' => $episode->getTitle(),
                'questions_count' => count($questions),
            ]);
            
            // Retourner les questions générées
            return $this->render('openai/generate_questions.html.twig', [
                'episode' => $episode,
                'questions' => $questions,
                'transcript' => $processedTranscript, // Utiliser le transcript traité
                'original_transcript' => substr($transcript, 0, 500) . '...' // Aperçu du transcript original
            ]);
        } catch (\Exception $e) {
            // Log l'erreur et afficher un message d'erreur
            $this->logger->error('Erreur lors de la génération des questions: ' . $e->getMessage());
            return $this->render('openai/error.html.twig', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

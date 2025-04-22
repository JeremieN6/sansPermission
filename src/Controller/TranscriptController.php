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
use App\Entity\Quizzes;
use App\Entity\Questions;
use App\Entity\Answers;
use App\Repository\EpisodesRepository;
use App\Repository\QuestionsRepository;
use App\Repository\QuizzesRepository;

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
    public function generateQuestions(
        OpenAIService $openAIService, 
        EntityManagerInterface $entityManager, 
        TextPreprocessor $textPreprocessor,
        QuizzesRepository $quizzesRepository,
        QuestionsRepository $questionsRepository): Response
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
            
            // Diviser manuellement le transcript en 4 parties égales
            $totalLength = strlen($transcript);
            $partLength = (int)($totalLength / 4);
            $chunks = [
                substr($transcript, 0, $partLength),
                substr($transcript, $partLength, $partLength),
                substr($transcript, $partLength * 2, $partLength),
                substr($transcript, $partLength * 3)
            ];
            
            // Générer les questions à partir de chaque extrait
            $questions = [];
            foreach ($chunks as $index => $chunk) {
                $partType = $index == 0 ? "début" : ($index == count($chunks) - 1 ? "fin" : "milieu");
                $this->logger->info('Génération de questions pour la partie', [
                    'part' => $partType, 
                    'length' => strlen($chunk)
                ]);
                
                $response = $openAIService->generateQuestion($chunk);
                
                if (isset($response['choices'][0]['message']['content'])) {
                    $content = $response['choices'][0]['message']['content'];
                    $lines = array_values(array_filter(
                        explode("\n", $content),
                        function($line) {
                            return !empty(trim($line));
                        }
                    ));

                    // Parcourir les lignes pour extraire les questions et réponses
                    $currentQuestion = null;
                    $currentAnswers = [];
                    $questionSet = [];

                    foreach ($lines as $line) {
                        $line = trim($line);
                        
                        // Si c'est une nouvelle question (commence par un chiffre suivi d'un point)
                        if (preg_match('/^\d+\./', $line)) {
                            // Si on avait une question précédente complète, l'ajouter
                            if ($currentQuestion && count($currentAnswers) === 4) {
                                $questionSet[] = $currentQuestion;
                                $questionSet = array_merge($questionSet, $currentAnswers);
                            }
                            $currentQuestion = $line;
                            $currentAnswers = [];
                        }
                        // Si c'est une réponse (commence par (+) ou (-))
                        elseif (strpos($line, '(+)') === 0 || strpos($line, '(-)') === 0) {
                            $currentAnswers[] = $line;
                        }
                    }

                    // Ajouter la dernière question si elle est complète
                    if ($currentQuestion && count($currentAnswers) === 4) {
                        $questionSet[] = $currentQuestion;
                        $questionSet = array_merge($questionSet, $currentAnswers);
                    }

                    $questions = array_merge($questions, $questionSet);
                }
            }

            // Log pour debug
            $this->logger->info('Questions générées', [
                'total_questions' => count($questions) / 5, // Diviser par 5 car chaque question a 4 réponses
                'questions_sample' => array_slice($questions, 0, 10)
            ]);
            
            // S'assurer d'avoir exactement 20 questions (4 parties × 5 questions)
            $this->logger->info('Nombre total de questions avant filtrage', ['count' => count($questions)]);

            // Réorganiser les questions en groupes de 5 (question + 4 réponses)
            $questionGroups = array_chunk($questions, 5);
            if (count($questionGroups) > 20) {
                $questionGroups = array_slice($questionGroups, 0, 20); // Garder seulement 20 groupes
                $questions = array_merge(...$questionGroups);
            }

            // Vérification du nombre total de questions
            $this->logger->info('Nombre total de questions générées', [
                'count' => count($questions),
                'questions_preview' => array_slice($questions, 0, 5)
            ]);
            
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

            // Vérifier si un quiz existe déjà pour cet épisode
            $existingQuiz = $entityManager->getRepository(Quizzes::class)->findOneBy(['episodeId' => $episode]);
            if ($existingQuiz !== null) {
                $this->addFlash('error', 'Un quiz existe déjà pour cet épisode. La génération de nouvelles questions est refusée.');
                return $this->redirectToRoute('app_main');
            }

            // Vérifier si des questions existent déjà pour ce quiz
            $existingQuestions = $entityManager->getRepository(Questions::class)->findBy(['quizId' => $existingQuiz]);
            if (!empty($existingQuestions)) {
                $this->addFlash('error', 'Des questions existent déjà pour ce quiz. La génération de nouvelles questions est refusée.');
                return $this->redirectToRoute('app_main');
            } 
            
            // Créer un nouveau quiz
            $quiz = new Quizzes();
            $quiz->setEpisodeId($episode);
            $quiz->setTitle("Quiz: " . $episode->getTitle());
            $quiz->setCreatedAt(new \DateTimeImmutable());
            $quiz->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($quiz);
            
            // Parcourir les questions générées et les sauvegarder
            $currentQuestion = null;
            $questionText = '';
            $answers = [];
            
            foreach ($questions as $line) {
                if (preg_match('/^\d+\./', $line)) { // Si c'est une nouvelle question
                    // Sauvegarder la question précédente si elle existe
                    if ($currentQuestion !== null) {
                        $this->saveQuestion($currentQuestion, $answers, $quiz, $entityManager);
                    }
                    
                    // Initialiser une nouvelle question
                    $questionText = $line;
                    $answers = [];
                    $currentQuestion = new Questions();
                    $currentQuestion->setContent($questionText);
                    $currentQuestion->setQuizId($quiz);
                    $currentQuestion->setCreatedAt(new \DateTimeImmutable());
                    $currentQuestion->setUpdatedAt(new \DateTimeImmutable());
                } elseif (strpos($line, '(+)') === 0 || strpos($line, '(-)') === 0) {
                    // Ajouter la réponse
                    $isCorrect = strpos($line, '(+)') === 0;
                    $answerText = trim(mb_substr($line, 3)); // Supprimer le préfixe (+) ou (-) avec mb_substr pour une gestion plus précise des caractères spéciaux
                    
                    $answer = new Answers();
                    $answer->setContent($answerText);
                    $answer->setCorrect($isCorrect);
                    $answer->setCreatedAt(new \DateTimeImmutable());
                    $answer->setUpdatedAt(new \DateTimeImmutable());
                    
                    $answers[] = $answer;
                }
            }
            
            // Sauvegarder la dernière question
            if ($currentQuestion !== null) {
                $this->saveQuestion($currentQuestion, $answers, $quiz, $entityManager);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Les questions ont été générées et sauvegardées avec succès !');

            return $this->render('openai/generate_questions.html.twig', [
                'episode' => $episode,
                'questions' => $questions,
                'quiz' => $quiz
            ]);
            
        } catch (\Exception $e) {
            // Log l'erreur et afficher un message d'erreur
            $this->logger->error('Erreur lors de la génération des questions: ' . $e->getMessage());
            return $this->render('openai/error.html.twig', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function saveQuestion(Questions $question, array $answers, Quizzes $quiz, EntityManagerInterface $entityManager): void
    {
        $entityManager->persist($question);
        
        foreach ($answers as $answer) {
            $answer->setQuestionId($question);
            $entityManager->persist($answer);
        }
    }
}

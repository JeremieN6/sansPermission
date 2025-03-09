<?php

namespace App\Controller;

use App\Entity\Episodes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\OpenAIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class OpenAIController extends AbstractController
{
    private $params;
    private $logger;

    public function __construct(
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->params = $params;
        $this->logger = $logger;
    }

    #[Route('/open_ai', name: 'app_open_ai')]
    public function index(): Response
    {
        return $this->render('open_ai/index.html.twig', [
            'controller_name' => 'OpenAIController',
        ]);
    }

    // #[Route('/generate-question', name: 'generate_question')]
    // public function generateQuestion(OpenAIService $openaiService): Response
    // {
    //     //Récupérer la Clé d'authentification OpenAI à partir des variables d'environnement
    //     $openai_api_key = $this->params->get('OPENAI_API_KEY');

    //     // Clé d'authentification OpenAI
    //     // $apiKey = 'YOUR_API_KEY_HERE';

    //     // Exécute le service pour générer une question
    //     $videoContent = 'Contenu de la vidéo YouTube';
    //     $generatedQuestion = $openaiService->generateQuestion($videoContent);

    //     // Affiche la question générée dans la vue ou retourne une réponse JSON
    //     return $this->json($generatedQuestion);
    // }

    #[Route('/generate-questions', name: 'generate_questions')]
    public function generateQuestions(OpenAIService $openAIService, EntityManagerInterface $entityManager): Response
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
            $this->logger->info('Transcript utilisé:', ['transcript' => $transcript]);

             // Si le transcript est trop long, le résumer avant de générer les questions
            $maxTranscriptLength = 1200; // Exemple de longueur maximale pour l'API OpenAI
            if (strlen($transcript) > $maxTranscriptLength) {
                $summaryResponse = $openAIService->generateSummary($transcript);
                $transcript = $summaryResponse['choices'][0]['message']['content'];  // Utiliser le résumé
                $this->logger->info('Résumé du transcript généré');
            }

            // Générer les questions
            $response = $openAIService->generateQuestion($transcript);

            // Formater la réponse pour l'affichage
            $questions = [];
            if (isset($response['choices'][0]['message']['content'])) {
                $questions = explode("\n", $response['choices'][0]['message']['content']);
                $questions = array_filter($questions); // Enlever les lignes vides
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
                'transcript' => $transcript
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

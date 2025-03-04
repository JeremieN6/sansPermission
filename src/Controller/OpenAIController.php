<?php

namespace App\Controller;

use App\Entity\Episodes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\OpenAIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OpenAIController extends AbstractController
{
    private $params;
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    #[Route('/open_ai', name: 'app_open_ai')]
    public function index(): Response
    {
        return $this->render('open_ai/index.html.twig', [
            'controller_name' => 'OpenAIController',
        ]);
    }

    #[Route('/generate-question', name: 'generate_question')]
    public function generateQuestion(OpenAIService $openaiService): Response
    {
        //Récupérer la Clé d'authentification OpenAI à partir des variables d'environnement
        $openai_api_key = $this->params->get('OPENAI_API_KEY');

        // Clé d'authentification OpenAI
        // $apiKey = 'YOUR_API_KEY_HERE';

        // Exécute le service pour générer une question
        $videoContent = 'Contenu de la vidéo YouTube';
        $generatedQuestion = $openaiService->generateQuestion($videoContent);

        // Affiche la question générée dans la vue ou retourne une réponse JSON
        return $this->json($generatedQuestion);
    }

    #[Route('/generate-questions', name: 'generate_questions')]
    public function generateQuestions(OpenAIService $openAIService, EntityManagerInterface $entityManager): Response
    {
       // Récupérer un transcript spécifique depuis la base de données
       $episode = $entityManager->getRepository(Episodes::class)->find(2); // Remplacez 2 par l'ID de l'épisode souhaité
       $transcript = $episode->getTranscript();

       // Afficher le transcript pour vérification
       dump($transcript); // ou utilisez un logger pour enregistrer le transcript

       // Appel à la méthode pour générer des questions
       $generatedQuestions = $openAIService->generateQuestion($transcript);

       // Retourner les questions générées dans une réponse JSON ou les afficher dans une vue
       return $this->render('openai/generate_questions.html.twig', [
           'questions' => $generatedQuestions,
       ]);
    }
}

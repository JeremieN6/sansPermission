<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\OpenAIService;
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
}

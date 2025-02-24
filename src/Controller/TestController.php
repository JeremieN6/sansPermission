<?php
// filepath: /c:/other/Mes Projets Dev/Projets/ProjetPerso/Saas/sansPermission/src/Controller/TestController.php
namespace App\Controller;


use App\Service\YouTubeScriptService;
use App\Service\OpenAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


class TestController extends AbstractController
{
    private YouTubeScriptService $youTubeScriptService;
    private OpenAIService $openAIService;


    public function __construct(YouTubeScriptService $youTubeScriptService, OpenAIService $openAIService)
    {
        $this->youTubeScriptService = $youTubeScriptService;
        $this->openAIService = $openAIService;
    }


    #[Route('/fetch-scripts', name: 'fetch_scripts')]
    public function fetchScripts(): Response
    {
        // Cette route sera modifiée plus tard pour le fine-tuning
        return $this->render('test/fetch_scripts.html.twig', [
            'response' => 'Fine-tuning temporairement désactivé',
        ]);
    }
}

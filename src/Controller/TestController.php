<?php
// filepath: /c:/other/Mes Projets Dev/Projets/ProjetPerso/Saas/sansPermission/src/Controller/TestController.php
namespace App\Controller;


use App\Service\YouTubeScriptService;
use App\Service\OpenAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


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
        $scripts = $this->youTubeScriptService->fetchScripts();
        $response = $this->openAIService->fineTuneModel($scripts);
        return $this->render('test/fetch_scripts.html.twig', [
            'response' => $response,
        ]);
    }

    #[Route('/test-youtube', name: 'test_youtube')]
    public function testYoutube(): Response
    {
        try {
            $scripts = $this->youTubeScriptService->fetchScripts();
            return $this->json([
                'success' => true,
                'scripts' => $scripts
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

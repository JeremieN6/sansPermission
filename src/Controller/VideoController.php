<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\YouTubeScriptService;

class VideoController extends AbstractController
{
    private $YouTubeScriptService;

    // Injecter le service YouTubeScriptService via le constructeur
    public function __construct(YouTubeScriptService $YouTubeScriptService)
    {
        $this->YouTubeScriptService = $YouTubeScriptService;
        // Récupérer la variable d'environnement
        $this->channelId = getenv('YOUTUBE_CHANNEL_ID');
        $this->apiKey = getenv('YOUTUBE_API_KEY');  // Si tu as également une API key dans .env
    }

    public function getLatestVideos(): Response
    {
        $videos = $this->YouTubeScriptService->getLatestVideos(3);

        return $this->render('_components/episodes/latest_episodes.html.twig', [
            'videos' => $videos
        ]);
    }
}

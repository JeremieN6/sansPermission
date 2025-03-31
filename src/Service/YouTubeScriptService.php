<?php
// filepath: /c:/other/Mes Projets Dev/Projets/ProjetPerso/Saas/sansPermission/src/Service/YouTubeScriptService.php
namespace App\Service;

use App\Entity\Episodes;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpClient\HttpClient;
use GuzzleHttp\Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YouTubeScriptService
{
    private string $pythonPath;
    private string $scriptPath;
    private string $apiKey;
    private string $youtube_api_key;
    private $channelId;
    private HttpClientInterface $httpClient;  // Déclare la propriété
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        string $pythonPath,
        string $scriptPath,
        string $apiKey,
        HttpClientInterface $httpClient,  // Injection du client HTTP
        EntityManagerInterface $entityManager,
        ?LoggerInterface $logger = null
    ) {
        $this->pythonPath = $pythonPath;
        $this->scriptPath = $scriptPath;
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;  // Assigner l'instance du client HTTP
        $this->channelId = $_ENV['YOUTUBE_CHANNEL_ID']; // Récupérer la variable d'environnement ici
        $this->youtube_api_key = $_ENV['YOUTUBE_API_KEY'];  // Si tu as également une API key dans .env
        $this->entityManager = $entityManager;
        $this->logger = $logger ?? new NullLogger();
    }

    private function getVideoDetails(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }
    
        $ids = implode(',', $videoIds);
        $url = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id={$ids}&key={$this->youtube_api_key}";
    
        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();
    
        $durations = [];
        foreach ($data['items'] as $item) {
            $videoId = $item['id'];
            $duration = new \DateInterval($item['contentDetails']['duration']);
            $totalSeconds = ($duration->h * 3600) + ($duration->i * 60) + $duration->s;
            $durations[$videoId] = $totalSeconds;
        }
    
        return $durations;
    }
    
    private function convertDuration(string $duration): string
    {
        $interval = new \DateInterval($duration);
    
        // Format : Hh Mm Ss (avec gestion des heures si présentes)
        if ($interval->h > 0) {
            return sprintf("%dh %02dmin", $interval->h, $interval->i);
        }
    
        return sprintf("%02dmin", $interval->i);
    }

    public function getLatestVideos(int $maxResults = 3): array
    {
        $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId={$this->channelId}&maxResults={$maxResults}&order=date&type=video&videoDuration=long&key={$this->youtube_api_key}";
    
        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();
    
        $videos = [];
        $videoIds = [];
    
        foreach ($data['items'] as $item) {
            if (!isset($item['id']['videoId'])) {
                continue;
            }
            
            $videoId = $item['id']['videoId'];
            $videoIds[] = $videoId;
    
            $videos[$videoId] = [
                'title' => $item['snippet']['title'],
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'],
                'publishedAt' => new \DateTime($item['snippet']['publishedAt']),
                'url' => "https://www.youtube.com/watch?v={$videoId}"
            ];
        }
    
        // 🔹 2e requête pour récupérer les durées et filtrer les Shorts
        $durations = $this->getVideoDetails($videoIds);
    
        $filteredVideos = [];
        foreach ($videos as $id => $video) {
            if (isset($durations[$id]) && $durations[$id] >= 60) { // Exclut les Shorts
                $seconds = $durations[$id];
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $formattedDuration = ($hours > 0) ? sprintf("%dh %02dmin", $hours, $minutes) : sprintf("%02dmin", $minutes);
                
                $video['duration'] = $formattedDuration;
                $filteredVideos[] = $video;
            }
        }
    
        return array_slice($filteredVideos, 0, $maxResults); // Retourne le bon nombre de vidéos
    }
}

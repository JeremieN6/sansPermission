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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Process\Process;

class YouTubeScriptService
{
    private string $pythonPath;
    private string $scriptPath;
    private string $apiKey;
    private string $youtube_api_key;
    private string $youtube_api_key_2;
    private $channelId;
    private HttpClientInterface $httpClient;  // Déclare la propriété
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;

    public function __construct(
        string $pythonPath,
        string $scriptPath,
        string $apiKey,
        HttpClientInterface $httpClient,  // Injection du client HTTP
        EntityManagerInterface $entityManager,
        CacheInterface $cache,  // Injection du cache
        ?LoggerInterface $logger = null
    ) {
        $this->pythonPath = $pythonPath;
        $this->scriptPath = $scriptPath;
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;  // Assigner l'instance du client HTTP
        $this->channelId = $_ENV['YOUTUBE_CHANNEL_ID']; // Récupérer la variable d'environnement ici
        $this->youtube_api_key = $_ENV['YOUTUBE_API_KEY'];  // Si tu as également une API key dans .env
        $this->youtube_api_key_2 = $_ENV['YOUTUBE_API_KEY_2'];  // Si tu as également une API key dans .env
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->logger = $logger ?? new NullLogger();
    }

    private function getVideoDetails(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }
    
        $ids = implode(',', $videoIds);
        $url = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id={$ids}&key={$this->youtube_api_key_2}";
    
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

    public function getLatestVideos(int $maxResults = 4): array
    {
        return $this->cache->get('youtube_latest_videos', function (ItemInterface $item) use ($maxResults) {
            $item->expiresAfter(3600); // 🔥 Stocke en cache pour 1 heure
    
            $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId={$this->channelId}&maxResults={$maxResults}&order=date&type=video&videoDuration=long&key={$this->youtube_api_key_2}";
    
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
        });
    }

    private function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'k';
        }
        return (string) $number;
    }

    public function getChannelDetails(): array
    {
        $url = "https://www.googleapis.com/youtube/v3/channels?part=snippet,statistics&id={$this->channelId}&key={$this->youtube_api_key_2}";
    
        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();
    
        if (empty($data['items'])) {
            return [];
        }
    
        $channel = $data['items'][0];
    
        return [
            'title' => $channel['snippet']['title'], // Nom de la chaîne
            'description' => $channel['snippet']['description'], // Nom de la chaîne
            'logo' => $channel['snippet']['thumbnails']['default']['url'], // Logo de la chaîne
            'subscribers' => $this->formatNumber((int) $channel['statistics']['subscriberCount']),
            'totalViews' => $this->formatNumber((int) $channel['statistics']['viewCount']),
            'videoCount' => $this->formatNumber((int) $channel['statistics']['videoCount']),
        ];
    }

    private function extractVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:v=|\/)([0-9A-Za-z_-]{11}).*/',  // URLs standards et partagées
            '/(?:shorts\/)([0-9A-Za-z_-]{11})/',  // URLs de shorts
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    // J'ai créer cette méthode pour extraire l'ID de la vidéo à partir de l'URL
    // Cela permet de réutiliser la logique d'extraction dans d'autres méthodes et de garder la méthode exctratVideoId private
    // et de ne pas exposer la logique d'extraction à l'extérieur de cette classe. 
    public function getVideoIdFromUrl(string $url): ?string 
    {
        return $this->extractVideoId($url);
    }

    public function getVideoThumbnail(string $videoUrl): ?string
    {
        $videoId = $this->extractVideoId($videoUrl);
        if (!$videoId) {
            return null;
        }

        $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$videoId}&key={$this->youtube_api_key_2}";
        
        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
            
            if (empty($data['items'])) {
                return null;
            }
            
            return $data['items'][0]['snippet']['thumbnails']['medium']['url'];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la miniature : ' . $e->getMessage());
            return null;
        }
    }

    public function getVideoViews(string $videoId): int
    {
        try {
            $url = "https://www.googleapis.com/youtube/v3/videos?part=statistics&id={$videoId}&key={$this->youtube_api_key_2}";
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            if (empty($data['items'])) {
                return 0;
            }

            return (int) $data['items'][0]['statistics']['viewCount'];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des vues : ' . $e->getMessage());
            return 0;
        }
    }

    public function getVideoPublishDate(string $videoId): ?\DateTimeImmutable
    {
        try {
            $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$videoId}&key={$this->youtube_api_key}";
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            if (empty($data['items'])) {
                return null;
            }

            return new \DateTimeImmutable($data['items'][0]['snippet']['publishedAt']);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la date de publication : ' . $e->getMessage());
            return null;
        }
    }

    private function getTranscriptFromPython(string $videoUrl): ?string
    {
        try {
            $this->logger->info('Démarrage de la récupération du transcript pour : ' . $videoUrl);
            
            // Vérifier que le script existe
            if (!file_exists($this->scriptPath)) {
                throw new \Exception('Le script Python n\'existe pas : ' . $this->scriptPath);
            }

            // Construire la commande
            $process = new Process([
                $this->pythonPath,
                $this->scriptPath,
                $this->youtube_api_key_2,
                $videoUrl
            ]);
            
            $this->logger->info('Exécution de la commande : ' . $process->getCommandLine());
            
            // Augmenter le timeout si nécessaire
            $process->setTimeout(60);
            
            // Exécuter le script
            $process->run();

            // Vérifier si le script s'est bien exécuté
            if (!$process->isSuccessful()) {
                $this->logger->error('Erreur lors de l\'exécution du script Python: ' . $process->getErrorOutput());
                throw new \Exception('Erreur lors de l\'exécution du script Python: ' . $process->getErrorOutput());
            }

            // Récupérer le chemin du fichier JSON
            $jsonFile = dirname($this->scriptPath) . '/video_data.json';
            $this->logger->info('Recherche du fichier JSON : ' . $jsonFile);

            if (!file_exists($jsonFile)) {
                throw new \Exception('Le fichier video_data.json n\'a pas été créé');
            }

            // Lire et décoder le fichier JSON
            $jsonContent = file_get_contents($jsonFile);
            if ($jsonContent === false) {
                throw new \Exception('Impossible de lire le fichier video_data.json');
            }

            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }

            if (!isset($data['transcript'])) {
                throw new \Exception('Le transcript n\'est pas présent dans les données JSON');
            }

            $this->logger->info('Transcript récupéré avec succès');
            return $data['transcript'];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du transcript: ' . $e->getMessage());
            throw $e; // Propager l'erreur pour la gérer dans processYoutubeVideo
        }
    }

    public function processYoutubeVideo(string $url): array
    {
        $videoId = $this->extractVideoId($url);
        if (!$videoId) {
            throw new \Exception("URL YouTube invalide");
        }

        try {
            // Récupérer les informations de la vidéo via l'API YouTube
            $apiUrl = "https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails&id={$videoId}&key={$this->youtube_api_key_2}";
            $response = $this->httpClient->request('GET', $apiUrl);
            $data = $response->toArray();

            if (empty($data['items'])) {
                throw new \Exception("Vidéo non trouvée");
            }

            $videoInfo = $data['items'][0]['snippet'];
            $contentDetails = $data['items'][0]['contentDetails'];

            // Récupérer le transcript via le script Python
            $transcript = $this->getTranscriptFromPython($url);
            if (!$transcript) {
                throw new \Exception("Impossible de récupérer le transcript de la vidéo");
            }

            // Formater les données
            return [
                'title' => $videoInfo['title'],
                'description' => $videoInfo['description'],
                'publishedAt' => new \DateTime($videoInfo['publishedAt']),
                'duration' => $contentDetails['duration'],
                'thumbnail' => $videoInfo['thumbnails']['medium']['url'],
                'videoId' => $videoId,
                'url' => $url,
                'transcript' => $transcript
            ];
        } catch (\Exception $e) {
            throw new \Exception("Erreur lors de la récupération des informations de la vidéo : " . $e->getMessage());
        }
    }
}

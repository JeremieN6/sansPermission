<?php
// filepath: /c:/other/Mes Projets Dev/Projets/ProjetPerso/Saas/sansPermission/src/Service/YouTubeScriptService.php
namespace App\Service;

use App\Entity\Episodes;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class YouTubeScriptService
{
    private string $pythonPath;
    private string $scriptPath;
    private string $apiKey;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        string $pythonPath,
        string $scriptPath,
        string $apiKey,
        EntityManagerInterface $entityManager,
        ?LoggerInterface $logger = null
    ) {
        $this->pythonPath = $pythonPath;
        $this->scriptPath = $scriptPath;
        $this->apiKey = $apiKey;
        $this->entityManager = $entityManager;
        $this->logger = $logger ?? new NullLogger();
    }

    public function processYoutubeVideo(string $url): Episodes
    {
        try {
            // Vérifier si la vidéo existe déjà
            $existingEpisode = $this->entityManager->getRepository(Episodes::class)
                ->findOneBy(['videoUrl' => $url]);
            
            if ($existingEpisode) {
                if ($existingEpisode->getStatus() === Episodes::STATUS_COMPLETED) {
                    throw new \RuntimeException('Cette vidéo a déjà été traitée. Utilisez "Forcer la mise à jour" pour la retraiter.');
                }
                $episode = $existingEpisode;
            } else {
                $episode = new Episodes();
                $episode->setVideoUrl($url);
                $episode->setCreatedAt(new \DateTimeImmutable());
            }

            $episode->setStatus(Episodes::STATUS_PROCESSING);
            $this->entityManager->persist($episode);
            $this->entityManager->flush();

            try {
                // Exécuter le script Python
                $command = escapeshellcmd("{$this->pythonPath} {$this->scriptPath} {$this->apiKey} " . escapeshellarg($url));
                $output = [];
                $returnVar = null;
                exec($command . " 2>&1", $output, $returnVar);

                if ($returnVar !== 0) {
                    $episode->setStatus(Episodes::STATUS_ERROR);
                    throw new \RuntimeException('Erreur lors de l\'exécution du script: ' . implode("\n", $output));
                }

                // Lire le fichier JSON généré
                if (!file_exists('video_data.json')) {
                    $episode->setStatus(Episodes::STATUS_ERROR);
                    throw new \RuntimeException('Le fichier de données n\'a pas été créé');
                }

                $videoData = json_decode(file_get_contents('video_data.json'), true);
                if (!$videoData) {
                    $episode->setStatus(Episodes::STATUS_ERROR);
                    throw new \RuntimeException('Impossible de décoder les données de la vidéo');
                }

                // Mettre à jour l'épisode
                $episode->setTitle($videoData['title']);
                $episode->setDescription($videoData['description']);
                $episode->setTranscript($videoData['transcript']);
                $episode->setReleaseDate(new \DateTime($videoData['publishedAt']));
                $episode->setStatus(Episodes::STATUS_COMPLETED);
                $episode->setUpdatedAt(new \DateTimeImmutable());

                $this->entityManager->flush();

                return $episode;

            } catch (\Exception $e) {
                $episode->setStatus(Episodes::STATUS_ERROR);
                $this->entityManager->flush();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement de la vidéo: ' . $e->getMessage());
            throw $e;
        }
    }
}

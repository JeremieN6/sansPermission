<?php
// filepath: /c:/other/Mes Projets Dev/Projets/ProjetPerso/Saas/sansPermission/src/Service/YouTubeScriptService.php
namespace App\Service;

use Psr\Log\LoggerInterface;

class YouTubeScriptService
{
    private string $pythonPath;
    private string $scriptPath;
    private string $apiKey;
    private string $channelId;
    private LoggerInterface $logger;

    public function __construct(string $pythonPath, string $scriptPath, string $apiKey, string $channelId, LoggerInterface $logger)
    {
        $this->pythonPath = $pythonPath;
        $this->scriptPath = $scriptPath;
        $this->apiKey = $apiKey;
        $this->channelId = $channelId;
        $this->logger = $logger;
    }

    public function fetchScripts(): array
    {
        $command = escapeshellcmd("{$this->pythonPath} {$this->scriptPath} {$this->apiKey} {$this->channelId}");
        $this->logger->info('Executing command: ' . $command);
        $output = shell_exec($command);
        $this->logger->info('Command output: ' . $output);
        $scripts = json_decode(file_get_contents('scripts.json'), true);
        $this->logger->info('Scripts fetched: ' . json_encode($scripts));
        return $scripts;
    }
}

<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class TextPreprocessor
{
    private $logger;
    private $openAIService;
    private $chunkSize = 3000; // Taille approximative en tokens
    private $cache;

    public function __construct(LoggerInterface $logger, OpenAIService $openAIService)
    {
        $this->logger = $logger;
        $this->openAIService = $openAIService;
        $this->cache = new FilesystemAdapter('transcripts', 0, __DIR__ . '/../../var/cache');
    }

    /**
     * Divise un texte en chunks et génère un résumé pour chaque chunk
     */
    public function processLargeTranscript(string $transcript, int $episodeId): string
    {
        // Vérifier si le résumé est déjà en cache
        $cacheKey = 'transcript_summary_' . md5($transcript) . '_' . $episodeId;
        $cachedItem = $this->cache->getItem($cacheKey);
        
        if ($cachedItem->isHit()) {
            $this->logger->info('Résumé récupéré depuis le cache', [
                'episode_id' => $episodeId
            ]);
            return $cachedItem->get();
        }
        
        // Diviser le transcript en chunks
        $chunks = $this->splitIntoChunks($transcript);
        
        // Sélectionner des morceaux aléatoires
        $selectedChunks = [];
        $numberOfChunksToSelect = 4; // Par exemple, sélectionner 4 morceaux

        for ($i = 0; $i < $numberOfChunksToSelect; $i++) {
            $randomIndex = rand(0, count($chunks) - 1);
            $selectedChunks[] = $chunks[$randomIndex];
        }

        // Traiter les morceaux sélectionnés
        $summaries = [];
        foreach ($selectedChunks as $chunk) {
            $this->logger->info('Traitement du chunk', ['length' => strlen($chunk)]);
            $summary = $this->summarizeChunk($chunk);
            if (!empty($summary) && strpos($summary, 'Erreur') !== 0) {
                $summaries[] = $summary;
            }
        }

        // Combiner les résumés
        $combinedSummary = $this->summarizeFinal(implode("\n", $summaries));

        // Mettre en cache le résultat
        $cachedItem->set($combinedSummary);
        $cachedItem->expiresAfter(86400); // Cache valide pendant 24h

        return $combinedSummary;
    }
    
    /**
     * Nettoie le texte pour éviter les problèmes d'encodage
     */
    private function cleanText(string $text): string
    {
        // Convertir en UTF-8 propre
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Supprimer les caractères non-UTF8
        $text = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]|[\x00-\x7F][\x80-\xBF]+|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
                 '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
                 '', $text);
        
        // Remplacer les caractères problématiques
        $text = str_replace(["\r", "\n\n\n", "\t"], ["\n", "\n\n", " "], $text);
        
        return trim($text);
    }
    
    /**
     * Divise un texte en chunks de taille approximative
     */
    public function splitIntoChunks(string $transcript): array
    {
        // Implémentez la logique pour diviser le transcript en chunks
        $chunks = explode("\n\n", $transcript); // Exemple simple de découpage par paragraphes
        return $chunks;
    }
    
    /**
     * Génère un résumé pour un chunk de texte
     */
    private function summarizeChunk(string $chunk): string
    {
        try {
            $response = $this->openAIService->generateSummary($chunk);
            
            if (is_array($response) && !empty($response)) {
                // Si c'est un tableau de résumés (cas où generateSummary retourne plusieurs résumés)
                if (isset($response[0]) && is_array($response[0])) {
                    $summaryContent = '';
                    foreach ($response as $summaryResponse) {
                        if (isset($summaryResponse['choices'][0]['message']['content'])) {
                            $summaryContent .= $summaryResponse['choices'][0]['message']['content'] . "\n";
                        }
                    }
                    return trim($summaryContent);
                }
                // Cas où generateSummary retourne un seul résumé
                elseif (isset($response['choices'][0]['message']['content'])) {
                    return $response['choices'][0]['message']['content'];
                } elseif (isset($response['choices'][0]['text'])) {
                    return $response['choices'][0]['text'];
                }
            }
            
            return "Résumé non disponible pour ce segment.";
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du résumé', ['error' => $e->getMessage()]);
            return "Segment ignoré: " . substr($e->getMessage(), 0, 100);
        }
    }
    
    /**
     * Génère un résumé final à partir des résumés combinés
     */
    private function summarizeFinal(string $combinedSummaries): string
    {
        try {
            $response = $this->openAIService->generateFinalSummary($combinedSummaries);
            
            if (isset($response['choices'][0]['message']['content'])) {
                return $response['choices'][0]['message']['content'];
            } elseif (isset($response['choices'][0]['text'])) {
                return $response['choices'][0]['text'];
            }
            
            return "Résumé final non disponible.";
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du résumé final', ['error' => $e->getMessage()]);
            return "Erreur lors de la génération du résumé final. Voici les résumés partiels:\n\n" . substr($combinedSummaries, 0, 2000) . "...";
        }
    }
}

<?php

// src/Service/OpenAIService.php

namespace App\Service;
use GuzzleHttp\Client;

class OpenAIService {

    private $client;
    private $apiKey;

    public function __construct(string $apiKey)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

    }

    public function generateQuestion(string $videoContent): array
    {
        // Logique pour envoyer une requête à l'API OpenAI et récupérer la question générée
        // Utilise $this->client pour effectuer la requête HTTP avec Guzzle

        // Retourne la question générée sous forme de tableau
        return ['question' => 'Question générée par l\'IA'];
    }

}
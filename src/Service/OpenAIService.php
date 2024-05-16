<?php

// src/Service/OpenAIService.php

namespace App\Service;

use App\Entity\Transcript;
use GuzzleHttp\Client;
use Orhanerday\OpenAi\OpenAi;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;

class OpenAIService
{
    // private $client;
    // private static $apiKey;

    // public function __construct()
    // {
    //     $this->client = new Client([
    //         'base_uri' => 'https://api.openai.com/v1/',
    //         'headers' => [
    //             'Authorization' => 'Bearer ' . self::$apiKey,
    //             'Content-Type' => 'application/json',
    //         ],
    //         'verify' => false, // Désactive la vérification SSL
    //     ]);
    // }

    // public static function setApiKey(string $apiKey): void
    // {
    //     self::$apiKey = $apiKey;
    // }

    private $client;
    private $parameterBag;
    private $logger;
    private $entityManager;
    
    public function __construct(
        ParameterBagInterface $parameterBag,
        LoggerInterface $logger
    )
    {
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->parameterBag->get('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ],
            'verify' => false, // Désactive la vérification SSL (pour les tests seulement)
        ]);
    }

    public function addToMemory(string $text): void
    {
        // Cette méthode doit ajouter le texte à la mémoire de l'IA

        $transcript = new Transcript();
        $transcript->setContent($text);
        $this->entityManager->persist($transcript);
        $this->entityManager->flush();
    }

    public function generateQuestions(string $text): string
    {

        // $response = $this->client->post('completions', [
        //     'json' => [
        //         'model' => 'text-davinci-003',
        //         'prompt' => "Based on the following text, generate some quiz questions:\n$text",
        //         'max_tokens' => 150,
        //     ],
        // ]);

        // $data = json_decode($response->getBody(), true);
        // return $data['choices'][0]['text'];

        // $openai_api_key = $this->parameterBag->get('OPENAI_API_KEY');
        // $open_ai = new OpenAi($openai_api_key);

        // $complete = $open_ai->completion([
        //     'model' => 'text-davinci-003',
        //     'prompt' => "Based on the following text, generate some quiz questions:\n$text",
        //     'temperature' => 0,
        //     'max_tokens' => 3500,
        //     'frequency_penalty' => 0.5,
        //     'presence_penalty' => 0,
        // ]);

        // $json = json_decode($complete, true);

        // if (isset($json['choices'][0]['text'])) {
        //     $json = $json['choices'][0]['text'];

        //     return $json;
        // }

        // $json = 'Une erreur est survenue !';

        // return $json;


        // OK FONCTIONNEL

        // try {
        //     $openai_api_key = $this->parameterBag->get('OPENAI_API_KEY');
        //     $open_ai = new OpenAi($openai_api_key);

        //     $complete = $open_ai->completion([
        //         'model' => 'gpt-3.5-turbo-instruct',
        //         'prompt' => "Based on the following text, generate some quiz questions:\n$text",
        //         'temperature' => 0,
        //         'max_tokens' => 3500,
        //         'frequency_penalty' => 0.5,
        //         'presence_penalty' => 0,
        //     ]);

        //     $json = json_decode($complete, true);

        //     // Ajouter des logs pour vérifier le contenu de $complete et $json
        //     dd($complete);
        //     dd($json);

        //     if (isset($json['choices'][0]['text'])) {
        //         return $json['choices'][0]['text'];
        //     }

        //     return 'Une erreur est survenue ! (No text found in response)';

        // } catch (\Exception $e) {
        //     // Ajouter des logs pour capturer les exceptions
        //     dd($e->getMessage());
        //     return 'Une erreur est survenue ! (Exception: ' . $e->getMessage() . ')';
        // }


        try {
            $openai_api_key = $this->parameterBag->get('OPENAI_API_KEY');
            $open_ai = new OpenAi($openai_api_key);

            $this->logger->info('Sending request to OpenAI API', ['prompt' => $text]);

            $complete = $open_ai->completion([
                'model' => 'gpt-3.5-turbo-instruct',
                'prompt' => "Based on the following text, generate some quiz questions:\n$text",
                'temperature' => 0,
                'max_tokens' => 3500,
                'frequency_penalty' => 0.5,
                'presence_penalty' => 0,
            ]);

            $json = json_decode($complete, true);

            $this->logger->info('Received response from OpenAI API', ['response' => $json]);

            if (isset($json['choices'][0]['text'])) {
                return $json['choices'][0]['text'];
            }

            $this->logger->error('No text found in OpenAI API response', ['response' => $json]);

            return 'Une erreur est survenue ! (No text found in response)';

        } catch (\Exception $e) {
            $this->logger->error('Exception occurred while calling OpenAI API', ['exception' => $e->getMessage()]);
            return 'Une erreur est survenue ! (Exception: ' . $e->getMessage() . ')';
        }


    // Final Mais sans IA
    
    //     try {
    //         // Simuler une réponse de l'API OpenAI
    //         $simulatedResponse = [
    //             'choices' => [
    //                 [
    //                     'text' => "Question 1: What is the main topic discussed in the text?\nOption A: Topic A\nOption B: Topic B\nOption C: Topic C\nOption D: Topic D\n"
    //                 ]
    //             ]
    //         ];
    
    //         $this->logger->info('Simulated response from OpenAI API', ['response' => $simulatedResponse]);
    
    //         if (isset($simulatedResponse['choices'][0]['text'])) {
    //             return $simulatedResponse['choices'][0]['text'];
    //         }
    
    //         $this->logger->error('No text found in simulated API response', ['response' => $simulatedResponse]);
    
    //         return 'Une erreur est survenue ! (No text found in simulated response)';
    
    //     } catch (\Exception $e) {
    //         $this->logger->error('Exception occurred while simulating OpenAI API call', ['exception' => $e->getMessage()]);
    //         return 'Une erreur est survenue ! (Exception: ' . $e->getMessage() . ')';
    //     }
    }
}
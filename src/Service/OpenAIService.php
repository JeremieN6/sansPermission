<?php


// src/Service/OpenAIService.php


namespace App\Service;


use App\Entity\Transcript;
use GuzzleHttp\Client;
use Orhanerday\OpenAi\OpenAi;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\AIModel;
use App\Entity\Episodes;
use Symfony\Component\HttpClient\HttpClient;


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
    private string $apiKey;
   
    public function __construct(
        ParameterBagInterface $parameterBag,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        string $apiKey
    )
    {
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->apiKey = $apiKey;
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


    public function fineTuneModel(array $scripts): string
    {
        try {
            $openai_api_key = $this->parameterBag->get('OPENAI_API_KEY');
            $open_ai = new OpenAi($openai_api_key);

            // Log avant la création du fichier
            $this->logger->info('Début du fine-tuning', [
                'nombre_scripts' => count($scripts)
            ]);

            // Préparer les données d'entraînement dans le format JSONL requis
            $training_data = [];
            foreach ($scripts as $videoId => $script) {
                $training_data[] = [
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a quiz generator specialized in creating questions from video transcripts.'],
                        ['role' => 'user', 'content' => $script],
                        ['role' => 'assistant', 'content' => 'Generate 5 quiz questions with multiple choice answers based on this content.']
                    ]
                ];
            }

            $this->logger->info('Préparation des données d\'entraînement', ['training_data' => $training_data]);

            // Créer le fichier d'entraînement
            $training_file = $open_ai->uploadFile([
                'purpose' => 'fine-tune',
                'file' => json_encode($training_data)
            ]);

            $this->logger->info('Fichier d\'entraînement créé', [
                'file_id' => $training_file['id'] ?? 'non disponible'
            ]);

            // Vérifier le statut du fine-tuning
            $status = $open_ai->retrieveFineTune([
                'fine_tune_id' => $training_file['id']
            ]);

            $this->logger->info('Statut du fine-tuning', [
                'status' => $status
            ]);

            return json_encode($status);
        } catch (\Exception $e) {
            $this->logger->error('Erreur de fine-tuning', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function prepareTrainingData(array $episodes): array
    {
        $trainingData = [];
        foreach ($episodes as $episode) {
            // Découper le transcript en sections plus petites si nécessaire
            $transcript = $episode->getTranscript();
            $sections = $this->splitTranscript($transcript);
            
            foreach ($sections as $section) {
                $trainingData[] = [
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Vous êtes un expert en génération de questions basées sur des transcriptions vidéo. Générez des questions pertinentes et leurs réponses.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Générez des questions et réponses basées sur cette transcription : $section"
                        ],
                        [
                            'role' => 'assistant',
                            'content' => "Voici les questions et réponses basées sur la transcription :\n\nQ1: [Question générée]\nR1: [Réponse détaillée]\n\nQ2: [Question générée]\nR2: [Réponse détaillée]"
                        ]
                    ]
                ];
            }
        }
        return $trainingData;
    }

    private function splitTranscript(string $transcript, int $maxLength = 2000): array
    {
        // Découper le transcript en sections plus petites pour respecter les limites de tokens
        $sections = [];
        $words = explode(' ', $transcript);
        $currentSection = '';
        
        foreach ($words as $word) {
            if (strlen($currentSection . ' ' . $word) > $maxLength) {
                $sections[] = trim($currentSection);
                $currentSection = $word;
            } else {
                $currentSection .= ' ' . $word;
            }
        }
        
        if (!empty($currentSection)) {
            $sections[] = trim($currentSection);
        }
        
        return $sections;
    }

    public function startFineTuning(array $episodes): AIModel
    {
        // Créer un nouveau modèle
        $aiModel = new AIModel();
        $aiModel->setStatus(AIModel::STATUS_PENDING);
        
        // Ajouter les épisodes utilisés pour l'entraînement
        foreach ($episodes as $episode) {
            $aiModel->addTrainingEpisode($episode);
        }

        // Préparer les données d'entraînement
        $trainingData = $this->prepareTrainingData($episodes);

        try {
            // Créer le fichier d'entraînement
            $response = $this->createTrainingFile($trainingData);
            $fileId = $response['id'];

            // Démarrer le fine-tuning
            $response = $this->startFineTuningJob($fileId);
            $aiModel->setModelId($response['id']);
            $aiModel->setStatus(AIModel::STATUS_TRAINING);
            
            $this->entityManager->persist($aiModel);
            $this->entityManager->flush();

            return $aiModel;
        } catch (\Exception $e) {
            $aiModel->setStatus(AIModel::STATUS_ERROR);
            throw $e;
        }
    }

    private function createTrainingFile(array $trainingData): array
    {
        // Créer un fichier JSONL temporaire
        $tempFile = tempnam(sys_get_temp_dir(), 'training_');
        $jsonlFile = $tempFile . '.jsonl';
        rename($tempFile, $jsonlFile);

        // Écrire les données au format JSONL
        $handle = fopen($jsonlFile, 'w');
        foreach ($trainingData as $data) {
            fwrite($handle, json_encode($data) . "\n");
        }
        fclose($handle);

        try {
            // Préparer les données multipart
            $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
            
            $data = '';
            // Ajouter le champ purpose
            $data .= "--{$boundary}\r\n";
            $data .= "Content-Disposition: form-data; name=\"purpose\"\r\n\r\n";
            $data .= "fine-tune\r\n";
            
            // Ajouter le fichier
            $data .= "--{$boundary}\r\n";
            $data .= "Content-Disposition: form-data; name=\"file\"; filename=\"training_data.jsonl\"\r\n";
            $data .= "Content-Type: application/json\r\n\r\n";
            $data .= file_get_contents($jsonlFile) . "\r\n";
            $data .= "--{$boundary}--\r\n";

            // Envoyer à l'API OpenAI
            $client = HttpClient::create();
            $response = $client->request('POST', 'https://api.openai.com/v1/files', [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                ],
                'body' => $data
            ]);

            // Pour le débogage
            $this->logger->info('Réponse OpenAI:', [
                'status' => $response->getStatusCode(),
                'content' => $response->getContent(false)
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du fichier:', [
                'error' => $e->getMessage(),
                'file_content' => file_get_contents($jsonlFile)
            ]);
            throw $e;
        } finally {
            // Nettoyer
            if (file_exists($jsonlFile)) {
                unlink($jsonlFile);
            }
        }
    }

    private function startFineTuningJob(string $fileId): array
    {
        $client = HttpClient::create();
        $response = $client->request('POST', 'https://api.openai.com/v1/fine_tuning/jobs', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'training_file' => $fileId,
                'model' => 'gpt-3.5-turbo',
            ],
        ]);

        return $response->toArray();
    }

    public function generateQuestion(string $transcript, ?string $modelId = null): array
    {
        $client = HttpClient::create();
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $modelId ?? 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Vous êtes un expert en génération de questions basées sur des transcriptions vidéo.'],
                ['role' => 'user', 'content' => "Générez des questions pertinentes basées sur cette transcription : $transcript"],
            ],
            'temperature' => 0.7,
        ];

        $response = $client->request('POST', $endpoint, [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        return $response->toArray();
    }

    public function checkTrainingStatus(AIModel $model): array
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.openai.com/v1/fine_tuning/jobs/' . $model->getModelId(), [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
            ],
        ]);

        $status = $response->toArray();
        
        // Log pour debug
        $this->logger->debug('Réponse API OpenAI:', $status);
        
        // Mettre à jour le statut du modèle
        if ($status['status'] === 'succeeded') {
            $model->setStatus(AIModel::STATUS_COMPLETED);
            if (isset($status['training_metrics'])) {
                $model->setTrainingMetrics(json_encode($status['training_metrics']));
            }
        } elseif ($status['status'] === 'failed') {
            $model->setStatus(AIModel::STATUS_ERROR);
        }
        
        $this->entityManager->flush();
        
        return $status;
    }
}

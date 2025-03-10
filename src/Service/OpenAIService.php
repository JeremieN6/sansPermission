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
use App\Service\TextPreprocessor;


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

    private function logApiCall(string $modelId, string $transcript, array $response): void
    {
        $this->logger->info('Appel API OpenAI', [
            'model_id' => $modelId,
            'transcript_length' => strlen($transcript),
            'response' => $response
        ]);
    }


    public function generateQuestion(string $transcript): array
    {
        try {
            if (empty($transcript) || strpos($transcript, 'Erreur:') === 0) {
                throw new \RuntimeException('Transcript invalide ou contenant des erreurs');
            }
            
            $modelId = 'gpt-3.5-turbo-16k'; // Utiliser le modèle avec plus de contexte
            $endpoint = 'https://api.openai.com/v1/chat/completions';
            
            $data = [
                'model' => $modelId,
                'messages' => [
                    ['role' => 'system', 'content' => 'Vous êtes un expert en création de quiz. Générez EXACTEMENT 20 questions pertinentes avec leurs réponses.'],
                    ['role' => 'user', 'content' => "Analysez cette transcription et générez EXACTEMENT 20 questions en traversant tous les sujets du transcript.
                    Autrement dit en choisissant les sujets abordé tout le long de la vidéo, jusqu'à la fin.

                    Règles IMPORTANTES :
                    - Générez EXACTEMENT 20 questions numérotées de 1 à 20
                    - Chaque question DOIT avoir EXACTEMENT 4 réponses
                    - Une seule réponse doit être correcte par question
                    - Les questions doivent être variées et pertinentes
                    
                    Format STRICT à respecter :
                    1. Question
                    [✓] Bonne réponse
                    [✗] Mauvaise réponse 1
                    [✗] Mauvaise réponse 2
                    [✗] Mauvaise réponse 3

                    (ligne vide entre chaque question)

                    Transcription à analyser :
                    $transcript"],
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'presence_penalty' => 0.6,
                'frequency_penalty' => 0.3
            ];

            $response = HttpClient::create()->request('POST', $endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
                'timeout' => 180 // 3 minutes pour permettre la génération complète
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération des questions', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function generateQuestionWithFallbackModel(string $transcript): array
    {
        $client = HttpClient::create();
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $modelId = 'gpt-3.5-turbo';
        
        $data = [
            'model' => $modelId,
            'messages' => [
                ['role' => 'system', 'content' => 'Vous êtes un expert en génération de questions basées sur des transcriptions vidéo.'],
                ['role' => 'user', 'content' => "Générez des questions pertinentes basées sur cette transcription : $transcript"],
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ];
        
        $this->logger->info('Envoi de requête au modèle de secours', [
            'model_id' => $modelId,
            'transcript_preview' => substr($transcript, 0, 100) . '...',
        ]);
        
        $response = $client->request('POST', $endpoint, [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);
        
        $responseData = $response->toArray();
        $this->logApiCall($modelId, $transcript, $responseData);
        
        return $responseData;
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
    
    private function splitIntoChunks(string $text): array
    {
        // Réduire la taille des chunks pour éviter les erreurs 400
        $chunkSizeChars = 1200; // Taille plus petite et sécurisée
        
        // Diviser le texte en paragraphes
        $paragraphs = str_split($text, $chunkSizeChars);
        
        $chunks = [];
        $currentChunk = '';
        
        foreach ($paragraphs as $paragraph) {
            // Si l'ajout de ce paragraphe dépasse la taille du chunk
            if (strlen($currentChunk) + strlen($paragraph) > $chunkSizeChars && !empty($currentChunk)) {
                $chunks[] = $currentChunk;
                $currentChunk = $paragraph;
            } else {
                $currentChunk .= (empty($currentChunk) ? '' : "\n") . $paragraph;
            }
        }
        
        // Ajouter le dernier chunk s'il n'est pas vide
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }
        
        return $chunks;
    }
    /**
     * Génère un résumé pour un chunk de texte
     */
    public function generateSummary(string $chunk): array
    {
        $client = HttpClient::create();
    
        try {
            // Vérifier si le texte est valide UTF-8
            if (!mb_check_encoding($chunk, 'UTF-8')) {
                $chunk = mb_convert_encoding($chunk, 'UTF-8', 'auto');
            }
            
            $modelId = 'gpt-3.5-turbo';
            $endpoint = 'https://api.openai.com/v1/chat/completions';
            
            $data = [
                'model' => $modelId,
                'messages' => [
                    ['role' => 'system', 'content' => 'Vous êtes un expert en résumé de texte. Résumez le texte fourni en conservant les informations importantes.'],
                    ['role' => 'user', 'content' => "Résumez ce texte en conservant les points clés et les informations importantes : $chunk"],
                ],
                'temperature' => 0.5,
                'max_tokens' => 500
            ];
            
            $this->logger->info('Envoi de requête pour résumé', [
                'model_id' => $modelId,
                'chunk_length' => strlen($chunk)
            ]);
            
            $response = $client->request('POST', $endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
                'timeout' => 30, // Augmenter le timeout pour éviter les erreurs
            ]);
            
            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du résumé', [
                'error' => $e->getMessage(),
                'chunk_preview' => substr($chunk, 0, 100)
            ]);
            
            // En cas d'erreur, retourner un format compatible
            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => "Erreur: " . $e->getMessage()
                        ]
                    ]
                ]
            ];
        }
    }

    /**
     * Génère un résumé final à partir des résumés combinés
     */
    public function generateFinalSummary(string $combinedSummaries): array
    {
        $client = HttpClient::create();
        
        try {
            $modelId = 'gpt-3.5-turbo';
            $endpoint = 'https://api.openai.com/v1/chat/completions';
            
            $data = [
                'model' => $modelId,
                'messages' => [
                    ['role' => 'system', 'content' => 'Vous êtes un expert en synthèse de texte. Créez une synthèse cohérente à partir des résumés fournis.'],
                    ['role' => 'user', 'content' => "Créez une synthèse cohérente à partir de ces résumés : $combinedSummaries"],
                ],
                'temperature' => 0.5,
                'max_tokens' => 1000
            ];
            
            $this->logger->info('Envoi de requête pour résumé final', [
                'model_id' => $modelId,
                'combined_summaries_length' => strlen($combinedSummaries)
            ]);
            
            $response = $client->request('POST', $endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);
            
            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du résumé final', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

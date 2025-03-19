<?php

namespace App\Service;

use App\Entity\Quizzes;
use App\Entity\Questions;
use App\Entity\Answers;
use App\Entity\Episodes;
use App\Repository\QuestionsRepository;
use App\Repository\QuizzesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\OpenAIService;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class QuizGeneratorService extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private OpenAIService $openAIService;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, OpenAIService $openAIService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->openAIService = $openAIService;
        $this->logger = $logger;
        
    }

    public function generateQuiz(int $episodeId): array
    {
        // try {
        //     $episode = $this->entityManager->getRepository(Episodes::class)->find($episodeId);

        //     if (!$episode) {
        //         return new JsonResponse(['error' => 'Épisode introuvable'], Response::HTTP_NOT_FOUND);
        //     }

        //     // Vérifier si un quiz existe déjà pour cet épisode
        //     $existingQuiz = $this->entityManager->getRepository(Quizzes::class)->findOneBy(['episodeId' => $episode]);
        //     if ($existingQuiz) {
        //         return new JsonResponse(['error' => 'Un quiz existe déjà pour cet épisode'], Response::HTTP_NOT_FOUND);
        //     }

        //     $transcript = $episode->getTranscript();

        //     if (empty($transcript)) {
        //         throw new \RuntimeException('Le transcript est vide');
        //     }

        //     // Log pour debug
        //     $this->logger->info('Transcript utilisé:', ['transcript_length' => strlen($transcript)]);

        //     // Prétraiter le transcript (chunking + résumé) avec mise en cache
        //     $processedTranscript = $textPreprocessor->processLargeTranscript($transcript, $episode->getId());
            
        //     // Diviser manuellement le transcript en 4 parties égales
        //     $totalLength = strlen($transcript);
        //     $partLength = (int)($totalLength / 4);
        //     $chunks = [
        //         substr($transcript, 0, $partLength),
        //         substr($transcript, $partLength, $partLength),
        //         substr($transcript, $partLength * 2, $partLength),
        //         substr($transcript, $partLength * 3)
        //     ];
            
        //     // Générer les questions à partir de chaque extrait
        //     $questions = [];
        //     foreach ($chunks as $index => $chunk) {
        //         $partType = $index == 0 ? "début" : ($index == count($chunks) - 1 ? "fin" : "milieu");
        //         $this->logger->info('Génération de questions pour la partie', [
        //             'part' => $partType, 
        //             'length' => strlen($chunk)
        //         ]);
                
        //         $response = $openAIService->generateQuestion($chunk);
                
        //         if (isset($response['choices'][0]['message']['content'])) {
        //             $content = $response['choices'][0]['message']['content'];
        //             $lines = array_values(array_filter(
        //                 explode("\n", $content),
        //                 function($line) {
        //                     return !empty(trim($line));
        //                 }
        //             ));

        //             // Parcourir les lignes pour extraire les questions et réponses
        //             $currentQuestion = null;
        //             $currentAnswers = [];
        //             $questionSet = [];

        //             foreach ($lines as $line) {
        //                 $line = trim($line);
                        
        //                 // Si c'est une nouvelle question (commence par un chiffre suivi d'un point)
        //                 if (preg_match('/^\d+\./', $line)) {
        //                     // Si on avait une question précédente complète, l'ajouter
        //                     if ($currentQuestion && count($currentAnswers) === 4) {
        //                         $questionSet[] = $currentQuestion;
        //                         $questionSet = array_merge($questionSet, $currentAnswers);
        //                     }
        //                     $currentQuestion = $line;
        //                     $currentAnswers = [];
        //                 }
        //                 // Si c'est une réponse (commence par [✓] ou [✗])
        //                 elseif (strpos($line, '[✓]') === 0 || strpos($line, '[✗]') === 0) {
        //                     $currentAnswers[] = $line;
        //                 }
        //             }

        //             // Ajouter la dernière question si elle est complète
        //             if ($currentQuestion && count($currentAnswers) === 4) {
        //                 $questionSet[] = $currentQuestion;
        //                 $questionSet = array_merge($questionSet, $currentAnswers);
        //             }

        //             $questions = array_merge($questions, $questionSet);
        //         }
        //     }

        //     // Log pour debug
        //     $this->logger->info('Questions générées', [
        //         'total_questions' => count($questions) / 5, // Diviser par 5 car chaque question a 4 réponses
        //         'questions_sample' => array_slice($questions, 0, 10)
        //     ]);
            
        //     // S'assurer d'avoir exactement 20 questions (4 parties × 5 questions)
        //     $this->logger->info('Nombre total de questions avant filtrage', ['count' => count($questions)]);

        //     // Réorganiser les questions en groupes de 5 (question + 4 réponses)
        //     $questionGroups = array_chunk($questions, 5);
        //     if (count($questionGroups) > 20) {
        //         $questionGroups = array_slice($questionGroups, 0, 20); // Garder seulement 20 groupes
        //         $questions = array_merge(...$questionGroups);
        //     }

        //     // Vérification du nombre total de questions
        //     $this->logger->info('Nombre total de questions générées', [
        //         'count' => count($questions),
        //         'questions_preview' => array_slice($questions, 0, 5)
        //     ]);
            
        //     // Si aucune question n'a été générée, créer des questions génériques
        //     if (empty($questions)) {
        //         $questions = [
        //             "1. Quels sont les principaux sujets abordés dans ce podcast ?",
        //             "2. Qui sont les intervenants principaux et quels sont leurs rôles ?",
        //             "3. Quelles sont les informations clés présentées dans cette discussion ?",
        //             "4. Quelles conclusions ou recommandations sont formulées ?",
        //             "5. Comment ce contenu pourrait-il être appliqué dans un contexte pratique ?"
        //         ];
        //         $this->logger->warning('Utilisation de questions génériques suite à un échec de génération');
        //     }
            
        //     // Ajouter un log pour vérifier les données avant le rendu
        //     $this->logger->info('Données pour le template:', [
        //         'episode_title' => $episode->getTitle(),
        //         'questions_count' => count($questions),
        //     ]);

        //     // Vérifier si un quiz existe déjà pour cet épisode
        //     $existingQuiz = $entityManager->getRepository(Quizzes::class)->findOneBy(['episodeId' => $episode]);
        //     if ($existingQuiz !== null) {
        //         $this->addFlash('error', 'Un quiz existe déjà pour cet épisode. La génération de nouvelles questions est refusée.');
        //         return $this->redirectToRoute('app_main');
        //     }

        //     // Vérifier si des questions existent déjà pour ce quiz
        //     $existingQuestions = $entityManager->getRepository(Questions::class)->findBy(['quizId' => $existingQuiz]);
        //     if (!empty($existingQuestions)) {
        //         $this->addFlash('error', 'Des questions existent déjà pour ce quiz. La génération de nouvelles questions est refusée.');
        //         return $this->redirectToRoute('app_main');
        //     } 
            
        //     // Créer un nouveau quiz
        //     $quiz = new Quizzes();
        //     $quiz->setEpisodeId($episode);
        //     $quiz->setTitle("Quiz: " . $episode->getTitle());
        //     $quiz->setCreatedAt(new \DateTimeImmutable());
        //     $quiz->setUpdatedAt(new \DateTimeImmutable());
            
        //     $entityManager->persist($quiz);
            
        //     // Parcourir les questions générées et les sauvegarder
        //     $currentQuestion = null;
        //     $questionText = '';
        //     $answers = [];
            
        //     foreach ($questions as $line) {
        //         if (preg_match('/^\d+\./', $line)) { // Si c'est une nouvelle question
        //             // Sauvegarder la question précédente si elle existe
        //             if ($currentQuestion !== null) {
        //                 $this->saveQuestion($currentQuestion, $answers, $quiz, $entityManager);
        //             }
                    
        //             // Initialiser une nouvelle question
        //             $questionText = $line;
        //             $answers = [];
        //             $currentQuestion = new Questions();
        //             $currentQuestion->setContent($questionText);
        //             $currentQuestion->setQuizId($quiz);
        //             $currentQuestion->setCreatedAt(new \DateTimeImmutable());
        //             $currentQuestion->setUpdatedAt(new \DateTimeImmutable());
        //         } elseif (strpos($line, '[✓]') === 0 || strpos($line, '[✗]') === 0) {
        //             // Ajouter la réponse
        //             $isCorrect = strpos($line, '[✓]') === 0;
        //             $answerText = trim(mb_substr($line, 3)); // Supprimer le préfixe [✓] ou [✗] avec mb_substr pour une gestion plus précise des caractères spéciaux
                    
        //             $answer = new Answers();
        //             $answer->setContent($answerText);
        //             $answer->setCorrect($isCorrect);
        //             $answer->setCreatedAt(new \DateTimeImmutable());
        //             $answer->setUpdatedAt(new \DateTimeImmutable());
                    
        //             $answers[] = $answer;
        //         }
        //     }
            
        //     // Sauvegarder la dernière question
        //     if ($currentQuestion !== null) {
        //         $this->saveQuestion($currentQuestion, $answers, $quiz, $entityManager);
        //     }
            
        //     $entityManager->flush();
            
        //     $this->addFlash('success', 'Les questions ont été générées et sauvegardées avec succès !');

        //     return $this->render('openai/generate_questions.html.twig', [
        //         'episode' => $episode,
        //         'questions' => $questions,
        //         'quiz' => $quiz
        //     ]);
            
        // } catch (\Exception $e) {
        //     // Log l'erreur et afficher un message d'erreur
        //     $this->logger->error('Erreur lors de la génération des questions: ' . $e->getMessage());
        //     return $this->render('openai/error.html.twig', [
        //         'error' => $e->getMessage()
        //     ]);
        // }

        $episode = $this->entityManager->getRepository(Episodes::class)->find($episodeId);

        if (!$episode) {
            return ['error' => 'Épisode introuvable'];
        }

        // Vérifier si un quiz existe déjà pour cet épisode
        $existingQuiz = $this->entityManager->getRepository(Quizzes::class)->findOneBy(['episodeId' => $episode]);
        if ($existingQuiz) {
            return ['error' => 'Un quiz existe déjà pour cet épisode'];
        }

        $transcriptChunks = $episode->getTranscript();
        if (!$transcriptChunks) {
            return ['error' => 'Aucun transcript disponible pour cet épisode'];
        }

        // Génération des questions via OpenAI
        $generatedQuestions = [];

        if (is_string($transcriptChunks)) {
            // $transcriptChunks = explode("\u_\u", $transcriptChunks); // Divise par double saut de ligne
            // $transcriptChunks = explode("\n", $transcriptChunks); // Séparation par un seul saut de ligne
            $transcriptChunks = preg_split('/\s*__\s*/u', $transcriptChunks);

        }

        // Vérification finale
        if (!is_array($transcriptChunks)) {
            throw new \Exception("Erreur: \$transcriptChunks n'est pas un tableau !");
        }

        // dump($transcriptChunks); die;

        foreach ($transcriptChunks as $chunk) {
            $questions = $this->openAIService->generateQuestion($chunk);
        
            // Vérifier si $questions est une chaîne et la convertir en tableau
            if (is_string($questions)) {
                $questions = [$questions]; // Transforme en tableau
            }
        
            if (is_array($questions)) { 
                $generatedQuestions = array_merge($generatedQuestions, $questions);
            } else {
                dump($questions); // Debug si ce n'est pas un array
                die("Erreur : 'generateQuestions()' ne retourne pas un tableau !");
            }
        }

        $rawContent = $generatedQuestions['choices'][0]['message']['content'] ?? null;

        if (!$rawContent) {
            throw new \RuntimeException("Le contenu des questions est vide ou inexistant.");
        }
        
        // Vérifie ce qui est réellement récupéré
        dump($rawContent); die;
        
        // Convertir le texte en tableau de questions
        $questionsArray = explode("\n\n", trim($rawContent)); // Sépare chaque question par les doubles sauts de ligne
        
        dump($questionsArray); die; // Vérifie le tableau obtenu
        
        foreach ($questionsArray as $q) {
            dump($q); // Vérifie chaque question séparée
        
            // Ici, on pourrait améliorer le parsing si nécessaire
            $questionText = explode("\n", $q)[0]; // Prend uniquement l'énoncé de la question
        
            $question = new Questions();
            $question->setContent($questionText); // ✅ Enregistre la question correctement
        }

        // Création du quiz
        $quiz = new Quizzes();
        $quiz->setEpisodeId($episode);
        $quiz->setTitle("Quiz: " . $episode->getTitle());
        $quiz->setCreatedAt(new \DateTimeImmutable());
        $quiz->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($quiz);

        // Sauvegarde des questions et réponses
        foreach ($generatedQuestions as $q) {
            $question = new Questions();
            $question->setContent($q['question']);
            $question->setQuizId($quiz);
            $question->setCreatedAt(new \DateTimeImmutable());
            $question->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($question);

            foreach ($q['answers'] as $answerData) {
                $answer = new Answers();
                $answer->setContent($answerData['text']);
                $answer->setCorrect($answerData['correct']);
                $answer->setCreatedAt(new \DateTimeImmutable());
                $answer->setUpdatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($answer);
            }
        }

        $this->entityManager->flush();

        return ['success' => 'Le quiz a été généré avec succès'];
    }

    private function saveQuestion(Questions $question, array $answers, Quizzes $quiz, EntityManagerInterface $entityManager): void
    {
        $entityManager->persist($question);
        
        foreach ($answers as $answer) {
            $answer->setQuestionId($question);
            $entityManager->persist($answer);
        }
    }
}

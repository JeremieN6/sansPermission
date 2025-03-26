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
        $episode = $this->entityManager->getRepository(Episodes::class)->find($episodeId);
    
        if (!$episode) {
            return ['error' => 'Épisode introuvable'];
        }
    
        $existingQuiz = $this->entityManager->getRepository(Quizzes::class)->findOneBy(['episodeId' => $episode]);
        if ($existingQuiz) {
            return ['error' => 'Un quiz existe déjà pour cet épisode'];
        }
    
        $transcript = $episode->getTranscript();
        if (!$transcript) {
            return ['error' => 'Aucun transcript disponible pour cet épisode'];
        }
    
        // Séparer le transcript en chunks exploitables
        $transcriptChunks = preg_split('/\s*__\s*/u', $transcript);
        if (!is_array($transcriptChunks)) {
            throw new \Exception("Erreur: \$transcriptChunks n'est pas un tableau !");
        }
    
        // Créer le quiz avant la boucle
        $quiz = new Quizzes();
        $quiz->setEpisodeId($episode);
        $quiz->setTitle("Quiz: " . $episode->getTitle());
        $quiz->setCreatedAt(new \DateTimeImmutable());
        $quiz->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($quiz);

        $generatedQuestions = [];
        $currentQuestionSet = [];
        $totalQuestions = 0;  // Compteur pour limiter à 20 questions
    
        foreach ($transcriptChunks as $chunk) {
            if ($totalQuestions >= 20) {
                break;  // Arrêter si on a déjà 20 questions
            }
    
            if (strlen($chunk) < 50) {
                continue;
            }
    
            $questions = $this->openAIService->generateQuestion($chunk);
            
            if (!isset($questions['choices'][0]['message']['content'])) {
                $this->logger->error("Réponse OpenAI sans contenu valide", ['response' => $questions]);
                continue;
            }
    
            $content = $questions['choices'][0]['message']['content'];
            $questionsArray = explode("\n", trim($content)); // Sépare les lignes
    
            foreach ($questionsArray as $line) {
                $line = trim($line);
                if (empty($line)) continue;
    
                $currentQuestionSet[] = $line;
    
                // Quand nous avons 5 lignes (1 question + 4 réponses), on traite le groupe
                if (count($currentQuestionSet) === 5) {
                    $this->saveQuestionGroup($currentQuestionSet, $quiz);
                    $currentQuestionSet = []; // Réinitialiser pour le prochain groupe
                    $totalQuestions++;
    
                    if ($totalQuestions >= 20) {
                        break 2;  // Sortir des deux boucles une fois 20 questions atteintes
                    }
                }
            }
        }
    
        // Gérer le dernier groupe s'il est incomplet
        if (!empty($currentQuestionSet)) {
            $this->logger->warning("Groupe de questions incomplet ignoré", [
                'count' => count($currentQuestionSet)
            ]);
        }
    
        $this->entityManager->flush();
    
        return ['success' => 'Quiz généré avec succès', 'total_questions' => $totalQuestions];
    }
    

    private function saveQuestionGroup(array $questionSet, Quizzes $quiz)
    {
        // Vérifier que nous avons exactement 5 éléments
        if (count($questionSet) !== 5) {
            $this->logger->warning('Format de question invalide - nombre incorrect d\'éléments', [
                'count' => count($questionSet)
            ]);
            return;
        }
    
        // Trouver la question (la ligne qui ne commence pas par [✓] ou [✗])
        $questionText = null;
        $answers = [];
        
        foreach ($questionSet as $line) {
            if (!str_starts_with($line, '[✓]') && !str_starts_with($line, '[✗]')) {
                $questionText = preg_replace('/^\d+\.\s*/', '', $line);
            } else {
                $answers[] = $line;
            }
        }
    
        // Vérifier qu'on a bien une question et 4 réponses
        if ($questionText === null || count($answers) !== 4) {
            $this->logger->warning('Format de question invalide - structure incorrecte', [
                'has_question' => ($questionText !== null),
                'answers_count' => count($answers)
            ]);
            return;
        }
    
        // Créer la question
        $questionEntity = new Questions();
        $questionEntity->setContent(trim($questionText));
        $questionEntity->setQuizId($quiz);
        $questionEntity->setCreatedAt(new \DateTimeImmutable());
        $questionEntity->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($questionEntity);
    
        // Créer les réponses
        foreach ($answers as $answer) {
            $isCorrect = str_starts_with($answer, '[✓]');
            $answerText = trim(substr($answer, 3));
    
            $answerEntity = new Answers();
            $answerEntity->setContent($answerText);
            $answerEntity->setCorrect($isCorrect);
            $answerEntity->setQuestionId($questionEntity);
            $answerEntity->setCreatedAt(new \DateTimeImmutable());
            $answerEntity->setUpdatedAt(new \DateTimeImmutable());
            $answerEntity->setQuestionId($questionEntity);

            $this->entityManager->persist($answerEntity);
        }
    
        $this->entityManager->flush();
    }

}

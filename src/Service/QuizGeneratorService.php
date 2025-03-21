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
    
        $generatedQuestions = [];
        
        foreach ($transcriptChunks as $chunk) {
            if (strlen($chunk) < 50) {
                continue; // Ignore les petits morceaux non exploitables
            }
    
            $questions = $this->openAIService->generateQuestion($chunk);
    
            // Vérification du format de retour de l'API OpenAI
            if (!is_array($questions)) {
                $this->logger->error("Réponse inattendue de OpenAI: ", ['response' => $questions]);
                continue;
            }
    
            if (!isset($questions['choices'][0]['message']['content'])) {
                $this->logger->error("Réponse OpenAI sans contenu valide", ['response' => $questions]);
                continue;
            }
    
            $content = $questions['choices'][0]['message']['content'];
            $questionsArray = explode("\n", trim($content)); // Sépare les lignes
    
            foreach ($questionsArray as $question) {
                if (!empty(trim($question))) {
                    $generatedQuestions[] = trim($question);
                }
            }
        }
    
        if (empty($generatedQuestions)) {
            $this->logger->warning("Aucune question générée pour l'épisode ID: $episodeId");
            return ['error' => 'Échec de la génération de questions'];
        }
    
        // Création et sauvegarde du quiz
        $quiz = new Quizzes();
        $quiz->setEpisodeId($episode);
        $quiz->setTitle("Quiz: " . $episode->getTitle());
        $quiz->setCreatedAt(new \DateTimeImmutable());
        $quiz->setUpdatedAt(new \DateTimeImmutable());
    
        $this->entityManager->persist($quiz);
        
        foreach (array_chunk($generatedQuestions, 5) as $questionSet) {
            $this->saveQuestionGroup($questionSet, $quiz);
        }
    
        $this->entityManager->flush();
    
        return ['success' => 'Quiz généré avec succès', 'total_questions' => count($generatedQuestions)];
    }
    

    private function saveQuestionGroup(array $questionSet, Quizzes $quiz)
    {
        if (count($questionSet) < 5) {
            return;
        }

        $questionText = array_shift($questionSet);
        $questionEntity = new Questions();
        $questionEntity->setContent($questionText);
        $questionEntity->setQuizId($quiz);
        $questionEntity->setCreatedAt(new \DateTimeImmutable());
        $questionEntity->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($questionEntity);

        foreach ($questionSet as $answer) {
            $isCorrect = strpos($answer, '[✓]') === 0;
            $answerText = trim(mb_substr($answer, 3)); // Supprime [✓] ou [✗]

            $answerEntity = new Answers();
            $answerEntity->setContent($answerText);
            $answerEntity->setCorrect($isCorrect);
            $answerEntity->setCreatedAt(new \DateTimeImmutable());
            $answerEntity->setUpdatedAt(new \DateTimeImmutable());
            $answerEntity->setQuestionId($questionEntity);

            $this->entityManager->persist($answerEntity);
        }
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

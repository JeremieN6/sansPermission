<?php

namespace App\Controller;

use App\Entity\Answers;
use App\Entity\Quizzes;
use App\Repository\QuestionsRepository;
use App\Repository\QuizzesRepository;
use App\Repository\UserScoresRepository;
use App\Service\ScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\YouTubeScriptService;

class QuizController extends AbstractController
{
    private YouTubeScriptService $YouTubeScriptService;

    // Injecter YouTubeScriptService dans le constructeur
    public function __construct(YouTubeScriptService $YouTubeScriptService)
    {
        $this->YouTubeScriptService = $YouTubeScriptService;
    }

    #[Route('/quiz/{id}/submit', name: 'quiz_submit', methods: ['POST'])]
    public function submitQuiz(
        Request $request,
        Quizzes $quiz,
        EntityManagerInterface $entityManager,
        ScoreService $scoreService // Injection du service
    ): Response {
        $user = $this->getUser();
        $answers = $request->request->all();
        $correctAnswers = 0;

        foreach ($answers as $questionId => $answerId) {
            $answer = $entityManager->getRepository(Answers::class)->find($answerId);

            if ($answer && $answer->isCorrect()) {
                $correctAnswers++; // Incrémente le score si la réponse est correcte
            }
        }

        // 🔥 Ajoute l'enregistrement du score ici
        $scoreService->saveUserScore($user, $quiz, $correctAnswers);

        $this->addFlash('success', "Tu as obtenu $correctAnswers bonnes réponses !");

        return $this->redirectToRoute('quiz_results', ['id' => $quiz->getId()]);
    }

    #[Route('/quiz-home', name: 'quiz_home')]
    public function quizIndex (
        Request $request,
        EntityManagerInterface $entityManager,
        UserScoresRepository $userScoresRepository,
        QuizzesRepository $quizzesRepository
    ){
        // Récupérer tous les quiz
        // $allQuizzes = $quizzesRepository->findAll();

        // Récupérer les vidéos
        $videos = $this->YouTubeScriptService->getLatestVideos(4);

        return $this->render('quiz/index.html.twig', [
            'controller_name' => 'QuizController',
            'videos' => $videos,
            // 'allQuizzes' => $allQuizzes,
        ]);
    }

    #[Route('/quiz-list', name: 'quiz_list')]
    public function quizList(
        QuizzesRepository $quizzesRepository,
        YouTubeScriptService $youTubeScriptService
    ){
        // Récupérer tous les quiz
        $allQuizzes = $quizzesRepository->findAll();

        // Récupérer les miniatures pour chaque quiz
        foreach ($allQuizzes as $quiz) {
            $episode = $quiz->getEpisodeId();
            if ($episode && $episode->getVideoUrl()) {
                $thumbnail = $youTubeScriptService->getVideoThumbnail($episode->getVideoUrl());
                if ($thumbnail) {
                    $quiz->thumbnail = $thumbnail;
                }
            }
        }

        return $this->render('quiz/list.html.twig', [
            'controller_name' => 'QuizController',
            'allQuizzes' => $allQuizzes,
        ]);
    }

    #[Route('/quiz-game/{id}', name: 'quiz_game', methods: ['GET', 'POST'])]
    public function quizGame(
        int $id,
        Request $request,
        QuestionsRepository $questionsRepository,
        QuizzesRepository $quizzesRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $quiz = $quizzesRepository->find($id);
        if (!$quiz) {
            throw $this->createNotFoundException('Quiz non trouvé');
        }
    
        $questions = $questionsRepository->findBy(['quizId' => $quiz]);
        $currentQuestionIndex = $request->request->getInt('currentQuestionIndex', 0);
        $currentScore = $request->getSession()->get('currentScore', 0);
    
        if ($request->isMethod('POST')) {
            $answerId = $request->request->getInt('answer');
            $answer = $entityManager->getRepository(Answers::class)->find($answerId);
    
            if ($answer && $answer->isCorrect()) {
                $currentScore++;
            }
    
            $currentQuestionIndex++;
            $request->getSession()->set('currentScore', $currentScore);
        }
    
        return $this->render('quiz/quiz-game.html.twig', [
            'questions' => $questions,
            'currentScore' => $currentScore,
            'currentQuestionIndex' => $currentQuestionIndex,
            'quiz' => $quiz,
        ]);
    }

}
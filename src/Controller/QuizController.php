<?php

namespace App\Controller;

use App\Entity\Answers;
use App\Entity\Quizzes;
use App\Entity\QuizAtempt;
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

    #[Route('/quiz-home', name: 'quiz_home')]
    public function quizIndex()
    {
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
    ) {
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

        $session = $request->getSession();
        $questions = $questionsRepository->findBy(['quizId' => $quiz]);
        $currentQuestionIndex = $request->request->getInt('currentQuestionIndex', 0);

        // Réinitialiser le score et enregistrer le timestamp de début si c'est la première question
        if ($currentQuestionIndex === 0) {
            $session->set('currentScore', 0);
            $session->set('quiz_start_time', new \DateTimeImmutable());
        }

        $currentScore = $session->get('currentScore', 0);

        if ($request->isMethod('POST')) {
            $answerId = $request->request->getInt('answer');
            $answer = $entityManager->getRepository(Answers::class)->find($answerId);

            if ($answer && $answer->isCorrect()) {
                $currentScore++;
                $session->set('currentScore', $currentScore);
            }

            $currentQuestionIndex++;
        }

        // Si c'est la dernière question
        // if ($currentQuestionIndex >= count($questions)) {
        //     return $this->redirectToRoute('quiz_submit', ['id' => $quiz->getId()]);
        // }

        return $this->render('quiz/quiz-game.html.twig', [
            'questions' => $questions,
            'currentScore' => $currentScore,
            'currentQuestionIndex' => $currentQuestionIndex,
            'quiz' => $quiz,
        ]);
    }

    #[Route('/quiz/{id}/submit', name: 'quiz_submit', methods: ['GET', 'POST'])]
    public function submitQuiz(
        Request $request,
        Quizzes $quiz,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour soumettre un quiz.');
            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();

        // Ne traiter la soumission que si c'est une requête POST
        if ($request->isMethod('POST')) {
            // Récupérer le temps de début et calculer la durée
            $startTime = $session->get('quiz_start_time');
            if (!$startTime instanceof \DateTimeImmutable) {
                $startTime = new \DateTimeImmutable($startTime->format('Y-m-d H:i:s'));
            }
            $endTime = new \DateTimeImmutable();
            
            // Calculer la durée en secondes
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

            // Créer une nouvelle tentative
            $attempt = new QuizAtempt();
            $attempt->setUser($user);
            $attempt->setQuiz($quiz);
            $attempt->setScore($session->get('currentScore', 0));
            $attempt->setStartedAt($startTime);
            $attempt->setEndedAt($endTime);
            $attempt->setDuration($duration);

            $entityManager->persist($attempt);
            $entityManager->flush();

            // Nettoyer la session
            $session->remove('currentScore');
            $session->remove('quiz_start_time');

            $this->addFlash('success', "Quiz terminé ! Score final : {$attempt->getScore()}");
        }
        return $this->redirectToRoute('quiz_list');
    }

    #[Route('/quiz-user-profil', name: 'quiz_user_profil')]
    public function quizUserProfil(): Response
    {
        return $this->render('quiz/quiz_user_profil.html.twig');
    }
}

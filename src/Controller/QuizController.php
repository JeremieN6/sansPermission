<?php

namespace App\Controller;

use App\Entity\Answers;
use App\Entity\Quizzes;
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
    public function indexQuiz(
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
}
<?php

namespace App\Controller;

use App\Entity\Answers;
use App\Entity\Quizzes;
use App\Repository\UserScoresRepository;
use App\Service\ScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{

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
}

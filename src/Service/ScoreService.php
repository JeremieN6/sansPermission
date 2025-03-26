<?php

namespace App\Service;

use App\Entity\UserScores;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Quizzes;
use App\Entity\Users;

class ScoreService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function saveUserScore(Users $user, Quizzes $quiz, int $score): void
    {
        $userScore = new UserScores();
        $userScore->setUser($user);
        $userScore->setQuiz($quiz);
        $userScore->setScore($score);

        $this->entityManager->persist($userScore);
        $this->entityManager->flush();
    }
}

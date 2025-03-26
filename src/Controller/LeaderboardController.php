<?php

namespace App\Controller;

use App\Repository\UserScoresRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeaderboardController extends AbstractController
{
    #[Route('/leaderboard', name: 'leaderboard')]
    public function index(UserScoresRepository $userScoresRepository): Response
    {
        $leaderboard = $userScoresRepository->getLeaderboard();

        return $this->render('leaderboard/index.html.twig', [
            'leaderboard' => $leaderboard,
        ]);
    }
}
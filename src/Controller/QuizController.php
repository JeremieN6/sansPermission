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
use App\Form\UserSettingsType;
use App\Entity\UserScores;
use App\Entity\Episodes;
use App\Repository\QuizAtemptRepository;

class QuizController extends AbstractController
{
    private YouTubeScriptService $YouTubeScriptService;

    // Injecter YouTubeScriptService dans le constructeur
    public function __construct(YouTubeScriptService $YouTubeScriptService)
    {
        $this->YouTubeScriptService = $YouTubeScriptService;
    }

    #[Route('/quiz-home', name: 'quiz_home')]
    public function quizIndex(QuizzesRepository $quizRepository, QuizAtemptRepository $quiz_atempt_repository): Response
    {
        // Utilisateur connecté
        $currentUser = $this->getUser();

        // Récupérer les vidéos
        $videos = $this->YouTubeScriptService->getLatestVideos(4);

        // Récupérer le dernier quiz créé
        $dernierQuiz = $quizRepository->findOneBy([], ['createdAt' => 'DESC']); // ou 'id' => 'DESC' si pas de date

        //Récupérer la liste des joueurs par score
        $listPlayer = $quiz_atempt_repository->findBy([], ['score' => 'DESC'], 3); // Top 3 joueurs par score

        return $this->render('quiz/index.html.twig', [
            'controller_name' => 'QuizController',
            'videos' => $videos,
            'dernierQuiz' => $dernierQuiz,
            'listPlayer' => $listPlayer,
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/quiz-list', name: 'quiz_list')]
    public function quizList(
        YouTubeScriptService $youTubeScriptService,
        QuizzesRepository $quizzesRepository,
        QuizAtemptRepository $quiz_atempt_repository,
        Request $request
    ) {
        
        // Récupérer le filtre depuis la requête
        $filter = $request->query->get('filter', 'quiz_latest');
        
        if (!in_array($filter, ['mostFamous', 'quiz_latest', 'quiz_oldest', 'video_latest', 'video_oldest'])) {
            $filter = 'quiz_latest';
        }

        // Récupérer tous les quiz
        $allQuizzes = $quizzesRepository->findAll();

        // Récupérer le dernier quiz créé
        $dernierQuiz = $quizzesRepository->findOneBy([], ['createdAt' => 'DESC']); // ou 'id' => 'DESC' si pas de date        

        //Récupérer la liste des joueurs par score
        $listPlayer = $quiz_atempt_repository->findBy([], ['score' => 'DESC'], 3); // Top 3 joueurs par score        

        // Récupérer les miniatures pour chaque quiz
        foreach ($allQuizzes as $quiz) {
            $episode = $quiz->getEpisodeId();
            if ($episode && $episode->getVideoUrl()) {
                $videoId = $youTubeScriptService->getVideoIdFromUrl($episode->getVideoUrl());
                $thumbnail = $youTubeScriptService->getVideoThumbnail($episode->getVideoUrl());
                $quiz->views = $youTubeScriptService->getVideoViews($episode->getVideoUrl());
                $quiz->publishedAt = $youTubeScriptService->getVideoPublishDate($videoId);
                if ($thumbnail) {
                    $quiz->thumbnail = $thumbnail;
                }
            }
        }

        // Trier les quiz selon le filtre
        usort($allQuizzes, function($a, $b) use ($filter) {
            switch ($filter) {
                case 'mostFamous':
                    return ($b->views ?? 0) <=> ($a->views ?? 0);
                case 'quiz_latest':
                    return $b->getCreatedAt() <=> $a->getCreatedAt();
                case 'quiz_oldest':
                    return $a->getCreatedAt() <=> $b->getCreatedAt();
                case 'video_latest':
                    $dateB = $b->publishedAt ?? $b->getCreatedAt();
                    $dateA = $a->publishedAt ?? $a->getCreatedAt();
                    return $dateB <=> $dateA;
                case 'video_oldest':
                    $dateA = $a->publishedAt ?? $a->getCreatedAt();
                    $dateB = $b->publishedAt ?? $b->getCreatedAt();
                    return $dateA <=> $dateB;
                default:
                    return $b->getCreatedAt() <=> $a->getCreatedAt();
            }
        });

        return $this->render('quiz/list.html.twig', [
            'controller_name' => 'QuizController',
            'allQuizzes' => $allQuizzes,
            'dernierQuiz' => $dernierQuiz,
            'listPlayer' => $listPlayer,
            'currentFilter' => $filter,
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
    public function quizUserProfil(EntityManagerInterface $em): Response
    {
        $connectedUser = $this->getUser();
        if (!$connectedUser || !($connectedUser instanceof \App\Entity\Users)) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer toutes les tentatives de l'utilisateur connecté
        $quizAtempts = $em->getRepository(QuizAtempt::class)->findBy(['user' => $connectedUser]);

        $totalQuiz = count($quizAtempts);
        $totalScore = 0;
        $totalCorrect = 0;
        $totalAnswers = 0;

        foreach ($quizAtempts as $attempt) {
            $totalScore += $attempt->getScore();
            // Si tu veux compter les bonnes réponses, adapte ici selon ta logique métier
        }

        // Score moyen global (tous les utilisateurs)
        $allAtempts = $em->getRepository(QuizAtempt::class)->findAll();
        $globalScore = 0;
        foreach ($allAtempts as $attempt) {
            $globalScore += $attempt->getScore();
        }
        $globalAvg = count($allAtempts) > 0 ? round($globalScore / count($allAtempts), 2) : 0;

        $userAnswers = $connectedUser->getUserAnswers();
        foreach ($userAnswers as $answer) {
            $totalAnswers++;
            if ($answer->isCorrect()) $totalCorrect++;
        }
        $percentCorrect = $totalAnswers > 0 ? round($totalCorrect / $totalAnswers * 100) : 0;

        $quizRepo = $em->getRepository(Quizzes::class);
        $quizTotal = $quizRepo->count([]);
        $episodeTotal = 0;
        if (class_exists(Episodes::class)) {
            $episodeTotal = $em->getRepository(Episodes::class)->count([]);
        }

        return $this->render('quiz/quiz_user_profil.html.twig', [
            'prenom' => $connectedUser->getPrenom(),
            'pseudo' => $connectedUser->getPseudo(),
            'quiz_total' => $quizTotal,
            'episode_total' => $episodeTotal,
            'user_quiz_total' => $totalQuiz,
            'user_score_total' => $totalScore,
            'global_avg' => $globalAvg,
            'percent_correct' => $percentCorrect,
        ]);
    }

    #[Route('/quiz-setting-profil', name: 'quiz_setting_profil')]
    public function quizSettingProfil(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || !($user instanceof \App\Entity\Users)) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserSettingsType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('quiz_setting_profil');
        }

        return $this->render('quiz/quiz_setting_profil.html.twig', [
            'prenom' => $user->getPrenom(),
            'pseudo' => $user->getPseudo(),
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/abonnement', name: 'abonnement')]
    public function abonnement(): Response
    {
        return $this->render('abonnement/index.html.twig');
    }

    #[Route('/classement-general', name: 'classement_general')]
    public function classementGeneral(
        QuizAtemptRepository $quiz_atempt_repository, 
        Request $request
        ): Response
    {
        $sort = $request->query->get('sort', 'score'); // valeur par défaut : score

        $classement = $quiz_atempt_repository->findAll(); // on ne trie pas ici, on trie plus tard

        // Compter le nombre de quiz tentés par utilisateur
        $userQuizCounts = [];
        $userScores = [];

        foreach ($classement as $attempt) {
            $userId = $attempt->getUser()->getId();
            $pseudo = $attempt->getUser()->getPseudo();

            // Incrémenter nombre de quiz
            if (!isset($userQuizCounts[$userId])) {
                $userQuizCounts[$userId] = 0;
                $userScores[$userId] = [
                    'user' => $attempt->getUser(),
                    'score' => 0
                ];
            }

            $userQuizCounts[$userId]++;
            $userScores[$userId]['score'] += $attempt->getScore();
        }

        // Trier selon le paramètre GET
        if ($sort === 'quiz') {
            uasort($userScores, function ($a, $b) use ($userQuizCounts) {
                return $userQuizCounts[$b['user']->getId()] <=> $userQuizCounts[$a['user']->getId()];
            });
        } else {
            uasort($userScores, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });
        }
        
        return $this->render('classement/index.html.twig', [
            'classement' => $userScores,
            'userQuizCounts' => $userQuizCounts,
            'totalUsers' => count($userQuizCounts),
            'sort' => $sort
        ]);
    }

    #[Route('/recompenses', name: 'recompenses')]
    public function mentionsLegales(): Response
    {
        return $this->render('recompenses/index.html.twig');
    }
}

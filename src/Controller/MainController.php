<?php

namespace App\Controller;

use App\Entity\Episodes;
use App\Repository\PlanRepository;
use App\Service\OpenAIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(PlanRepository $planRepository, OpenAIService $openAIService, EntityManagerInterface $entityManager): Response
    {
        $plans = $planRepository->findAll();

        $episode = $entityManager->getRepository(Episodes::class)
        ->findOneBy(
            ['status' => Episodes::STATUS_COMPLETED],
            ['releaseDate' => 'DESC']
        );

        $transcript = $episode->getTranscript();
        // dd($transcript);
        // $transcriptSize = strlen($transcript);
        // dd($transcriptSize);

        $summaryResponse = $openAIService->generateSummary($transcript);

        // dd($summaryResponse);

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'plans' => $plans,
            'summary' => $summaryResponse,
        ]);
    }

    #[Route('/testenv', name: 'app_testenv')]
    public function checkEnv(): Response
    {
        $openAiApiKey = $this->getParameter('OPENAI_API_KEY');
        return new Response('<pre>' . print_r($openAiApiKey, true) . '</pre>');
    }
}

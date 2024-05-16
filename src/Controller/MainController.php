<?php

namespace App\Controller;

use App\Repository\PlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(PlanRepository $planRepository): Response
    {
        $plans = $planRepository->findAll();

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'plans' => $plans
        ]);
    }

    #[Route('/testenv', name: 'app_testenv')]
    public function checkEnv(): Response
    {
        $openAiApiKey = $this->getParameter('OPENAI_API_KEY');
        return new Response('<pre>' . print_r($openAiApiKey, true) . '</pre>');
    }
}

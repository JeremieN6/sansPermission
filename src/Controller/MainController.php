<?php

namespace App\Controller;

use App\Form\CollaborationType;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\YouTubeScriptService;

class MainController extends AbstractController
{
    private YouTubeScriptService $YouTubeScriptService;

    // Injecter YouTubeScriptService dans le constructeur
    public function __construct(YouTubeScriptService $YouTubeScriptService)
    {
        $this->YouTubeScriptService = $YouTubeScriptService;
    }

    #[Route('/', name: 'app_main')]
    public function index(PlanRepository $planRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        // $plans = $planRepository->findAll();

        //Youtube Channel URL
        $youtube_channel_url = "https://www.youtube.com/@sanspermissionpodcast";

        // Récupérer les vidéos
        $videos = $this->YouTubeScriptService->getLatestVideos(3);

        // Récupérer la description de la chaîne
        $channelInfo = $this->YouTubeScriptService->getChannelDetails();

        //Formulaire de collaboration
        $form = $this->createForm(CollaborationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $collaboration = $form->getData();
            $entityManager->persist($collaboration);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de collaboration a bien été envoyée !');

            return $this->redirectToRoute('app_main');
        }

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            // 'plans' => $plans,
            'videos' => $videos,
            'form' => $form->createView(),
            'channel' => $channelInfo,
            'youtube_channel_url' => $youtube_channel_url
            // 'summary' => $summaryResponse,
        ]);
    }

    #[Route('/testenv', name: 'app_testenv')]
    public function checkEnv(): Response
    {
        $openAiApiKey = $this->getParameter('OPENAI_API_KEY');
        return new Response('<pre>' . print_r($openAiApiKey, true) . '</pre>');
    }
}

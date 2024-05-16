<?php

namespace App\Controller;

use App\Repository\TranscriptRepository;
use App\Service\OpenAIService;
use App\Service\TextPreprocessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class TranscriptController extends AbstractController
{
    private $openAIService;
    private $textPreprocessor;

    public function __construct(OpenAIService $openAIService, TextPreprocessor $textPreprocessor)
    {
        $this->openAIService = $openAIService;
        $this->textPreprocessor = $textPreprocessor;
    }

    #[Route('/transcript', name: 'app_transcript')]
    public function uploadTranscript(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $transcriptFile = $request->files->get('transcript');
            if ($transcriptFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/files/';
                $transcriptFileName = $transcriptFile->getClientOriginalName();
                $transcriptFile->move($uploadDir, $transcriptFileName);

                $filePath = $uploadDir . $transcriptFileName;
                $cleanedFilePath = $uploadDir . pathinfo($transcriptFileName, PATHINFO_FILENAME) . '_cleaned.txt';

                // Exécuter le script Python pour nettoyer le fichier de transcription
                $process = new Process(['python', $this->getParameter('kernel.project_dir') . '/scripts/preprocess_transcript.py', $filePath]);
                $process->run();

                // Vérifier si le processus a réussi
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                // Lire le contenu nettoyé
                $cleanedContent = file_get_contents($cleanedFilePath);

                // Ajouter le contenu nettoyé à la mémoire de l'IA : C'est à dire pour cette version dans la base de donnée
                $this->openAIService->addToMemory($cleanedContent);

                return new Response('Transcription uploaded and processed successfully.');
            }

            return new Response('No transcript file uploaded', 400);
        }

        // return $this->render('upload_transcript.html.twig');
        return $this->render('transcript/index.html.twig', [
            'controller_name' => 'TranscriptController',
        ]);
    }

    #[Route('/generate-questions', name: 'generate_questions')]
    public function generateQuestions(
        TranscriptRepository $transcriptRepository
    ): Response
    {
        // VERSION FONCTIONNEL SANS MEMORISATION DES DONNEE EN BASE

        // // Récupérer le contenu de la mémoire de l'IA
        // // Pour simplifier, nous allons supposer que nous récupérons le contenu d'un fichier stocké
        // $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/files/transcript.txt';
        // $cleanedContent = $this->textPreprocessor->cleanAndSplitText($filePath);

        // // Générer des questions à partir du contenu
        // $questions = $this->openAIService->generateQuestions($cleanedContent);

        // return new Response('<pre>' . print_r($questions, true) . '</pre>');


        $transcripts = $transcriptRepository->findAll();
        $allContent = '';
        foreach ($transcripts as $transcript) {
            $allContent .= $transcript->getContent() . "\n";
        }

        // Générer des questions à partir de tout le contenu des transcripts
        $questions = $this->openAIService->generateQuestions($allContent);

        return new Response('<pre>' . print_r($questions, true) . '</pre>');
    }
}

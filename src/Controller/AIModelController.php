<?php

namespace App\Controller;

use App\Entity\AIModel;
use App\Entity\Episodes;
use App\Service\OpenAIService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/ai-model')]
class AIModelController extends AbstractCrudController
{
    private OpenAIService $openAIService;
    private EntityManagerInterface $entityManager;

    public function __construct(OpenAIService $openAIService, EntityManagerInterface $entityManager)
    {
        $this->openAIService = $openAIService;
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return AIModel::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('modelId', 'ID du modèle'),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => AIModel::STATUS_PENDING,
                    'En cours' => AIModel::STATUS_TRAINING,
                    'Complété' => AIModel::STATUS_COMPLETED,
                    'Erreur' => AIModel::STATUS_ERROR,
                ])
                ->renderAsBadges([
                    AIModel::STATUS_PENDING => 'dark',
                    AIModel::STATUS_TRAINING => 'warning',
                    AIModel::STATUS_COMPLETED => 'success',
                    AIModel::STATUS_ERROR => 'danger',
                ]),
            TextareaField::new('trainingMetrics', 'Métriques d\'entraînement'),
            AssociationField::new('trainingEpisodes', 'Épisodes d\'entraînement'),
            ArrayField::new('configuration', 'Configuration'),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm(),
        ];
    }

    #[Route('/start-training', name: 'start_training')]
    public function startTraining(): Response
    {
        // Récupérer tous les épisodes avec transcripts
        $episodes = $this->entityManager->getRepository(Episodes::class)
            ->findBy(['status' => Episodes::STATUS_COMPLETED]);

        try {
            $aiModel = $this->openAIService->startFineTuning($episodes);
            $this->addFlash('success', 'Fine-tuning démarré avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du démarrage du fine-tuning : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin');
    }
} 
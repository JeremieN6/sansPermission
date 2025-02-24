<?php

namespace App\Controller\Admin;

use App\Entity\AIModel;
use App\Entity\Episodes;
use App\Service\OpenAIService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

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

    public function configureActions(Actions $actions): Actions
    {
        $startTraining = Action::new('startTraining', 'Démarrer l\'entraînement')
            ->linkToRoute('start_training')
            ->addCssClass('btn btn-primary')
            ->setIcon('fa fa-play')
            ->createAsGlobalAction();

        $checkStatus = Action::new('checkStatus', 'Vérifier le statut')
            ->linkToCrudAction('checkStatus')
            ->displayIf(static function (AIModel $entity) {
                return $entity->getStatus() === AIModel::STATUS_TRAINING;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $startTraining)
            ->add(Crud::PAGE_DETAIL, $startTraining)
            ->add(Crud::PAGE_INDEX, $checkStatus)
            ->add(Crud::PAGE_DETAIL, $checkStatus);
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
            AssociationField::new('trainingEpisodes', 'Episodes utilisés')
                ->setFormTypeOption('by_reference', false)
                ->formatValue(function ($value, $entity) {
                    if (!$value) return '';
                    return implode(', ', array_map(function($episode) {
                        return $episode->getTitle();
                    }, $entity->getTrainingEpisodes()->toArray()));
                }),
            ArrayField::new('configuration', 'Configuration')
                ->hideOnIndex(),
            TextareaField::new('trainingMetrics', 'Métriques d\'entraînement')
                ->hideOnIndex()
                ->setFormTypeOption('disabled', true)
                ->formatValue(function ($value) {
                    if (!$value) return '';
                    $metrics = json_decode($value, true);
                    return json_encode($metrics, JSON_PRETTY_PRINT);
                }),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Modèle IA')
            ->setEntityLabelInPlural('Modèles IA')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des Modèles IA')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un Modèle IA')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le Modèle IA')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détails du Modèle IA');
    }

    #[Route('/admin/ai-model/start-training', name: 'start_training')]
    public function startTraining(): Response
    {
        $episodes = $this->entityManager->getRepository(Episodes::class)
            ->findBy(['status' => Episodes::STATUS_COMPLETED]);

        if (empty($episodes)) {
            $this->addFlash('warning', 'Aucun épisode disponible pour l\'entraînement. Veuillez d\'abord ajouter des épisodes avec des transcripts.');
            return $this->redirectToRoute('admin');
        }

        try {
            $aiModel = $this->openAIService->startFineTuning($episodes);
            $this->addFlash('success', 'Fine-tuning démarré avec succès ! Vous pouvez suivre son état dans la liste des modèles.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du démarrage du fine-tuning : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class
        ]);
    }

    public function checkStatus(AdminContext $context): Response
    {
        /** @var AIModel $model */
        $model = $context->getEntity()->getInstance();

        try {
            $status = $this->openAIService->checkTrainingStatus($model);
            $this->addFlash('success', 'Statut mis à jour avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la vérification du statut : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class
        ]);
    }
} 
<?php

namespace App\Controller\Admin;

use App\Entity\Episodes;
use App\Service\YouTubeScriptService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class EpisodesCrudController extends AbstractCrudController
{
    private YouTubeScriptService $youTubeScriptService;
    private AdminUrlGenerator $adminUrlGenerator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        YouTubeScriptService $youTubeScriptService,
        AdminUrlGenerator $adminUrlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->youTubeScriptService = $youTubeScriptService;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Episodes::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $fetchTranscript = Action::new('fetchTranscript', 'Récupérer le transcript')
            ->linkToCrudAction('fetchTranscript')
            ->addCssClass('btn btn-primary');

        $forceUpdate = Action::new('forceUpdate', 'Forcer la mise à jour')
            ->linkToCrudAction('forceUpdate')
            ->addCssClass('btn btn-warning');

        return $actions
            ->add(Crud::PAGE_EDIT, $fetchTranscript)
            ->add(Crud::PAGE_INDEX, $fetchTranscript)
            ->add(Crud::PAGE_DETAIL, $forceUpdate)
            ->add(Crud::PAGE_EDIT, $forceUpdate);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            TextEditorField::new('description', 'Description'),
            UrlField::new('videoUrl', 'URL YouTube'),
            TextEditorField::new('transcript', 'Transcript')->hideOnIndex(),
            DateTimeField::new('releaseDate', 'Date de sortie'),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'Non traité' => Episodes::STATUS_NOT_PROCESSED,
                    'En cours' => Episodes::STATUS_PROCESSING,
                    'Complété' => Episodes::STATUS_COMPLETED,
                    'Erreur' => Episodes::STATUS_ERROR,
                ])
                ->renderAsBadges([
                    Episodes::STATUS_NOT_PROCESSED => 'dark',
                    Episodes::STATUS_PROCESSING => 'warning',
                    Episodes::STATUS_COMPLETED => 'success',
                    Episodes::STATUS_ERROR => 'danger',
                ]),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }

    public function fetchTranscript(AdminContext $context): Response
    {
        $episode = $context->getEntity()->getInstance();
        $url = $episode->getVideoUrl();

        try {
            $updatedEpisode = $this->youTubeScriptService->processYoutubeVideo($url);
            $this->addFlash('success', 'Transcript récupéré avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($episode->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function forceUpdate(AdminContext $context): Response
    {
        $episode = $context->getEntity()->getInstance();
        $episode->setStatus(Episodes::STATUS_NOT_PROCESSED);
        $this->entityManager->flush();
        
        return $this->fetchTranscript($context);
    }
}

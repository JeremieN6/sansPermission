<?php

namespace App\Controller\Admin;

use App\Entity\Answers;
use App\Entity\Episodes;
use App\Entity\Invoice;
use App\Entity\Plan;
use App\Entity\Questions;
use App\Entity\Quizzes;
use App\Entity\Subscription;
use App\Entity\UserAnswerChoices;
use App\Entity\UserAnswers;
use App\Entity\Users;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SansPermission');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Quizz');
        yield MenuItem::linkToCrud('Quizz', 'fas fa-question', Quizzes::class);
        yield MenuItem::linkToCrud('Questions', 'fas fa-circle-question', Questions::class);
        yield MenuItem::linkToCrud('Bonnes Réponses', 'fas fa-check', Answers::class);
        yield MenuItem::linkToCrud('Réponse Utilisateur', 'fas fa-users', UserAnswers::class);
        yield MenuItem::linkToCrud('Réponses Multiples', 'fas fa-layer-group', UserAnswerChoices::class);
        yield MenuItem::linkToCrud('Episodes', 'fas fa-video', Episodes::class);


        yield MenuItem::section('Abonnements');
        yield MenuItem::linkToCrud('Plans', 'fas fa-paper-plane', Plan::class);
        yield MenuItem::linkToCrud('Souscriptions', 'fas fa-cart-plus', Subscription::class);
        yield MenuItem::linkToCrud('Factures', 'fas fa-file-invoice', Invoice::class);

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', Users::class);
    }
}

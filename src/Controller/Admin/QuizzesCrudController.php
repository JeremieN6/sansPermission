<?php

namespace App\Controller\Admin;

use App\Entity\Quizzes;
use App\Form\GenerateQuizType;
use App\Service\QuizGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizzesCrudController extends AbstractCrudController
{
    private QuizGeneratorService $quizGeneratorService;

    public function __construct(QuizGeneratorService $quizGeneratorService)
    {
        $this->quizGeneratorService = $quizGeneratorService;
    }

    public static function getEntityFqcn(): string
    {
        return Quizzes::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateQuiz = Action::new('generateQuiz', 'Générer un quiz', 'fa fa-magic')
            ->linkToRoute('admin_generate_quiz')
            ->createAsGlobalAction() // Ajout en tant qu'action globale
            ->addCssClass('btn btn-primary');

        return $actions->add(Crud::PAGE_INDEX, $generateQuiz);
    }


    #[Route('/admin/generate-quiz', name: 'admin_generate_quiz', methods: ['GET', 'POST'])]
    public function generateQuiz(Request $request): Response
    {
        $form = $this->createForm(GenerateQuizType::class);
        $form->handleRequest($request);
    
        if ($request->isMethod('GET')) {
            return $this->render('admin/generate_quiz_form.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            $episode = $form->get('episode')->getData();
            $result = $this->quizGeneratorService->generateQuiz($episode->getId());
    
            if (isset($result['error'])) {
                $this->addFlash('danger', $result['error']);
            } else {
                $this->addFlash('success', 'Quiz généré avec succès !');
            }
    
            return $this->redirectToRoute('admin');
        }
    
        return $this->render('admin/generate_quiz_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
            AssociationField::new('episodeId'),
            CollectionField::new('category'),
            DateField::new('createdAt'),
            DateField::new('updatedAt'),
        ];
    }
    
}

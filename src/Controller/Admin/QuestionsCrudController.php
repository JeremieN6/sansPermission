<?php

namespace App\Controller\Admin;

use App\Entity\Questions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class QuestionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Questions::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('content'),
            AssociationField::new('quizId'),
            TextEditorField::new('content'),
            DateField::new('createdAt'),
            DateField::new('updatedAt'),
        ];
    }
    
}

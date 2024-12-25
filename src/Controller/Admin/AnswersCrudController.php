<?php

namespace App\Controller\Admin;

use App\Entity\Answers;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class AnswersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Answers::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            IdField::new('questionId'),
            TextEditorField::new('content'),
            BooleanField::new('isCorrect'),
            DateField::new('createdAt'),
            AssociationField::new('userAnswers'),
            AssociationField::new('userAnswerChoices'),
        ];
    }
    
}

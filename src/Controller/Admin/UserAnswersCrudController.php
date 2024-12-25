<?php

namespace App\Controller\Admin;

use App\Entity\UserAnswers;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;

class UserAnswersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserAnswers::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('userId'),
            AssociationField::new('questionId'),
            AssociationField::new('answerId'),
            BooleanField::new('isCorrect'),
            DateField::new('createdAt'),
        ];
    }
    
}

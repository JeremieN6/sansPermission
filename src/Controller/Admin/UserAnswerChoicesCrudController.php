<?php

namespace App\Controller\Admin;

use App\Entity\UserAnswerChoices;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserAnswerChoicesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserAnswerChoices::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('userAnswerId'),
            AssociationField::new('answerId'),
            BooleanField::new('isCorrect'),
        ];
    }
    
}

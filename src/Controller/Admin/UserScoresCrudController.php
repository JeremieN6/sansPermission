<?php

namespace App\Controller\Admin;

use App\Entity\UserScores;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class UserScoresCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserScores::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            AssociationField::new('user', 'Utilisateur'),
            AssociationField::new('quiz', 'Quiz'),
            IntegerField::new('score'),
        ];
    }
    
}

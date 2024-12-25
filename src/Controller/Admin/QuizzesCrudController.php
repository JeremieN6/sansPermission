<?php

namespace App\Controller\Admin;

use App\Entity\Quizzes;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class QuizzesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Quizzes::class;
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

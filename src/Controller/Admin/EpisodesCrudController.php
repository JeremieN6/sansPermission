<?php

namespace App\Controller\Admin;

use App\Entity\Episodes;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EpisodesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Episodes::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
            DateField::new('releaseDate'),
            DateField::new('createdAt'),
            DateField::new('updatedAt'),
            AssociationField::new('quizzes'),
        ];
    }
    
}

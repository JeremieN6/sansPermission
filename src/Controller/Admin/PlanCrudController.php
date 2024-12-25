<?php

namespace App\Controller\Admin;

use App\Entity\Plan;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PlanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Plan::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            SlugField::new('slug')->setTargetFieldName('nom'),
            TextField::new('stripe_id'),
            MoneyField::new('price')->setCurrency('EUR'),
            TextEditorField::new('description'),
            DateField::new('created_at','Crée le'),
            TextField::new('payment_link')
        ];
    }
    
}

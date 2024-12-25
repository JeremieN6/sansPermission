<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('plan', 'Nom de l\'abonnement '),
            AssociationField::new('user', 'Utilisateur'),
            TextField::new('stripe_id'),
            DateField::new('current_period_start','Commence le'),
            DateField::new('current_period_end','Termine le'),
            BooleanField::new('is_active')
        ];
    }
    
}

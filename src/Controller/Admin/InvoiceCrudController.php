<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class InvoiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Invoice::class;
    }

    /**/
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('subscription', 'Nom de l\'abonnement '),
            TextField::new('stripe_id'),
            MoneyField::new('amount_paid')->setCurrency('EUR'),
            TextField::new('number', 'Numéro de Facture'),
            UrlField::new('hosted_invoice_url', 'Lien de la Facture'),
            DateField::new('created_at','Crée le')
        ];
    }
    
}

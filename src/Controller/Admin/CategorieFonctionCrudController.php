<?php

namespace App\Controller\Admin;

use App\Entity\CategorieFonction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategorieFonctionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategorieFonction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Categorie de fonction')
            ->setEntityLabelInPlural('Categories de fonction')
            ->setDefaultSort(['code' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('code', 'Code');
        yield NumberField::new('tauxMonetaire', 'Taux monetaire');
    }
}

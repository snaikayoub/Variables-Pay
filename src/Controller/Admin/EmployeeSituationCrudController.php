<?php

namespace App\Controller\Admin;

use App\Entity\EmployeeSituation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EmployeeSituationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmployeeSituation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('employee', 'Collaborateur');
        yield DateField::new('startDate', 'Debut');
        yield DateField::new('endDate', 'Fin');
        yield AssociationField::new('service', 'Service');
        yield AssociationField::new('category', 'Categorie professionnelle');
        yield TextField::new('typePaie', 'Type de paie');
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\GrpPerf;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

class GrpPerfCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GrpPerf::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Groupe Performance')
            ->setEntityLabelInPlural('Groupes Performances')
            ->setSearchFields(['nameGrp'])
            ->setDefaultSort(['nameGrp' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            TextField::new('nameGrp')
                ->setLabel('Nom du groupe')
                ->setRequired(true)
                ->setMaxLength(255)
                ->setHelp('Saisissez le nom du groupe de performance'),

            AssociationField::new('employees')
                ->setLabel('Employes')
                ->hideOnForm()
                ->hideOnIndex()
                ->onlyOnDetail()
                ->formatValue(function ($value, $entity) {
                    return $value ? count($value) . ' employe(s)' : '0 employes';
                }),

            AssociationField::new('categoryTMs')
                ->setLabel('Taux par categorie')
                ->hideOnForm()
                ->hideOnIndex()
                ->onlyOnDetail()
                ->formatValue(function ($value, $entity) {
                    return $value ? count($value) . ' taux' : '0 taux';
                })
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('nameGrp');
    }
}

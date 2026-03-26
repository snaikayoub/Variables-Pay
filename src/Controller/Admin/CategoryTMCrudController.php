<?php

namespace App\Controller\Admin;

use App\Entity\GrpPerf;
use App\Entity\Category;
use App\Entity\CategoryTM;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CategoryTMCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategoryTM::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Category TM')
            ->setEntityLabelInPlural('Category TMs')
            ->setSearchFields(['category.name', 'grpPerf.name', 'TM'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            AssociationField::new('category')
                ->setLabel('Category')
                ->setRequired(true)
                ->setCrudController(CategoryCrudController::class)
                ->autocomplete()
                ->formatValue(function ($value, $entity) {
                    return $value ? $value->getCategoryName() : '';
                }),

            AssociationField::new('grpPerf')
                ->setLabel('Groupes Performances')
                ->setRequired(true)
                ->setCrudController(GrpPerfCrudController::class)
                ->autocomplete()
                ->formatValue(function ($value, $entity) {
                    return $value ? $value->getNameGrp() : '';
                }),

            IntegerField::new('TM')
                ->setLabel('TM')
                ->setRequired(true)
                ->setHelp('Entrez la valeur de TM (entre 5 et 30)')
                ->setCustomOption(IntegerField::OPTION_NUMBER_FORMAT, '%d')
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('category')->setLabel('Category'))
            ->add(EntityFilter::new('grpPerf')->setLabel('Group Performance'))
            ->add('TM');
    }
}

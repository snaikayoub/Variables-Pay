<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Category')
            ->setEntityLabelInPlural('Categories')
            ->setSearchFields(['categoryName'])
            ->setDefaultSort(['categoryName' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            TextField::new('categoryName')
                ->setLabel('Category Name')
                ->setRequired(true)
                ->setMaxLength(255)
                ->setHelp('Enter the category name'),

            AssociationField::new('employeeSituations')
                ->setLabel('Employee Situations')
                ->hideOnForm()
                ->hideOnIndex()
                ->onlyOnDetail()
                ->formatValue(function ($value, $entity) {
                    return $value ? count($value) . ' employee situation(s)' : '0 employee situations';
                }),

            AssociationField::new('categoryTMs')
                ->setLabel('Category TMs')
                ->hideOnForm()
                ->hideOnIndex()
                ->onlyOnDetail()
                ->formatValue(function ($value, $entity) {
                    return $value ? count($value) . ' category TM(s)' : '0 category TMs';
                })
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('categoryName');
    }
}
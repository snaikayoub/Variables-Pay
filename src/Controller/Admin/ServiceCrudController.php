<?php
// src/Controller/Admin/ServiceCrudController.php
namespace App\Controller\Admin;

use App\Entity\Service;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{
    IdField,
    TextField,
    AssociationField,
    CollectionField,
    TextareaField
};
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ServiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Service::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Service')
            ->setEntityLabelInPlural('Services')
            ->setSearchFields(['id', 'nom'])
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield TextField::new('nom', 'Nom du service');

        yield AssociationField::new('division', 'Division')
            ->setCrudController(DivisionCrudController::class);

        // Gestionnaires (ManyToMany)
        // Display as string on index
        yield TextField::new('gestionnairesNames', 'Gestionnaires')
            ->onlyOnIndex()
            ->setSortable(false);

        // Show as collection on detail view
        yield CollectionField::new('gestionnaire', 'Gestionnaires')
            ->onlyOnDetail();

        // Edit form with AssociationField
        yield AssociationField::new('gestionnaire', 'Gestionnaires')
            ->onlyOnForms()
            ->setFormTypeOptions(['by_reference' => false]);

        // Validateur de service (ManyToOne)
        yield AssociationField::new('validateurService', 'Validateur Service')
            ->setCrudController(UserCrudController::class);
    }
}
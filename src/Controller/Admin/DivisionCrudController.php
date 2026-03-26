<?php
// src/Controller/Admin/DivisionCrudController.php
namespace App\Controller\Admin;

use App\Entity\Division;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{
    IdField,
    TextField,
    AssociationField
};
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class DivisionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Division::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Division')
            ->setEntityLabelInPlural('Divisions')
            ->setSearchFields(['id', 'nom'])
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        // Affiché dans la liste
        yield IdField::new('id')->onlyOnIndex();

        yield TextField::new('nom', 'Nom de la division');

        // Afficher la liste des services rattachés en détail
        yield AssociationField::new('services', 'Services')
            ->onlyOnDetail()
            ->formatValue(static function ($value, $entity) {
                // $entity est une instance de Division
                return implode(', ', array_map(
                    fn($svc) => $svc->getNom(),
                    $entity->getServices()->toArray()
                ));
            });

        // Sélection du validateur de division dans les formulaires
        yield AssociationField::new('validateurDivision', 'Validateur Division')
            ->onlyOnForms()
            ->setCrudController(UserCrudController::class);

        // Affichage du validateur dans la liste
        yield AssociationField::new('validateurDivision', 'Validateur Division')
            ->onlyOnIndex();
    }

    
}

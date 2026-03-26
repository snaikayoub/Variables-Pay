<?php

namespace App\Controller\Admin;

use App\Entity\VoyageDeplacement;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VoyageDeplacementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return VoyageDeplacement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Voyage / Deplacement')
            ->setEntityLabelInPlural('Voyages / Deplacements')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('employee', 'Collaborateur')->setRequired(true);
        yield AssociationField::new('periodePaie', 'Periode')->setRequired(true);

        yield TextField::new('typeVoyage', 'Type')->hideOnIndex();
        yield TextareaField::new('motif', 'Motif')->hideOnIndex();
        yield TextField::new('modeTransport', 'Transport');
        yield DateTimeField::new('dateHeureDepart', 'Depart');
        yield DateTimeField::new('dateHeureRetour', 'Retour');
        yield NumberField::new('distanceKm', 'Distance (Km)');

        yield TextField::new('villeDepartAller', 'Ville depart (aller)')->hideOnIndex();
        yield TextField::new('villeArriveeAller', 'Ville arrivee (aller)')->hideOnIndex();
        yield TextField::new('villeDepartRetour', 'Ville depart (retour)')->hideOnIndex();
        yield TextField::new('villeArriveeRetour', 'Ville arrivee (retour)')->hideOnIndex();

        yield ChoiceField::new('status', 'Statut')->setChoices([
            'Brouillon' => VoyageDeplacement::STATUS_DRAFT,
            'Soumis' => VoyageDeplacement::STATUS_SUBMITTED,
            'Valide service' => VoyageDeplacement::STATUS_SERVICE_VALIDATED,
            'Valide' => VoyageDeplacement::STATUS_VALIDATED,
            'Rejete' => VoyageDeplacement::STATUS_REJECTED,
        ])->renderAsBadges();

        yield TextareaField::new('commentaire', 'Commentaire')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Cree le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Modifie le')->hideOnForm();
    }
}

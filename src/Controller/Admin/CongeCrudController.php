<?php

namespace App\Controller\Admin;

use App\Entity\Conge;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CongeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Conge::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Conge')
            ->setEntityLabelInPlural('Conges')
            ->setDefaultSort(['dateCreation' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('employee', 'Collaborateur')->setRequired(true);
        yield TextField::new('typeConge', 'Type');

        yield DateField::new('dateDebut', 'Date debut');
        yield DateField::new('dateFin', 'Date fin');
        yield NumberField::new('nombreJours', 'Nombre jours');

        yield ChoiceField::new('statut', 'Statut')->setChoices([
            'En attente' => 'en_attente',
            'Approuve' => 'approuve',
            'Rejete' => 'rejete',
            'Annule' => 'annule',
        ]);

        yield BooleanField::new('demiJournee', 'Demi journee');
        yield ChoiceField::new('periodeDemiJournee', 'Periode demi journee')
            ->setChoices([
                'Matin' => 'matin',
                'Apres-midi' => 'apres-midi',
            ])
            ->renderAsBadges();

        yield TextareaField::new('motif', 'Motif')->hideOnIndex();
        yield TextareaField::new('commentaireGestionnaire', 'Commentaire gestionnaire')->hideOnIndex();

        yield AssociationField::new('traitePar', 'Traite par')->hideOnIndex();
        yield DateTimeField::new('dateCreation', 'Cree le')->hideOnForm();
        yield DateTimeField::new('dateTraitement', 'Traite le')->hideOnForm();
    }
}

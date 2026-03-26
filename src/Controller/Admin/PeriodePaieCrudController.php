<?php

namespace App\Controller\Admin;

use App\Entity\PeriodePaie;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore; // ← Corrigé ici
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class PeriodePaieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PeriodePaie::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Période de paie')
            ->setEntityLabelInPlural('Périodes de paie')
            ->setSearchFields(['typePaie', 'mois', 'annee', 'quinzaine', 'statut'])
            ->setDefaultSort(['annee' => 'DESC', 'mois' => 'DESC', 'quinzaine' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        // ID — seulement en index
        $id = IntegerField::new('id')->onlyOnIndex();

        // Type de paie (texte)
        $typePaie = TextField::new('typePaie', 'Type de paie')
            ->setHelp('Ex. : "mensuelle" ou "quinzaine"')
            ->setRequired(true);

        // Mois (1–12)
        $mois = IntegerField::new('mois', 'Mois')
            ->setHelp('Valeur entre 1 et 12')
            ->setRequired(true);

        // Année (ex. 2025)
        $annee = IntegerField::new('annee', 'Année')
            ->setHelp('Par exemple : 2025')
            ->setRequired(true);

        // Statut (inactive, ouverte, fermée)
        $statut = ChoiceField::new('statut', 'Statut')
            ->setChoices([
                'Inactive' => PeriodePaie::STATUT_INACTIF,
                'Ouverte'  => PeriodePaie::STATUT_OUVERT,
                'Fermée'   => PeriodePaie::STATUT_FERME,
            ])
            ->setRequired(true)
            ->renderExpanded(); // boutons radio

        // Scores — uniquement en lecture (index/detail). L'édition se fait dans le form builder.
        $scoreEquipeIndex    = NumberField::new('scoreEquipe', 'Score équipe')
            ->setNumDecimals(2)
            ->onlyOnIndex();
        $scoreCollectifIndex = NumberField::new('scoreCollectif', 'Score collectif')
            ->setNumDecimals(2)
            ->onlyOnIndex();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $typePaie,
                $mois,
                $annee,
                IntegerField::new('quinzaine', 'Quinzaine')->onlyOnIndex(),
                $statut,
                $scoreEquipeIndex,
                $scoreCollectifIndex,
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $typePaie,
                $mois,
                $annee,
                IntegerField::new('quinzaine', 'Quinzaine')->onlyOnDetail(),
                $statut,
                $scoreEquipeIndex,
                $scoreCollectifIndex,
            ];
        }

        // PAGE_NEW et PAGE_EDIT : on ne déclare pas ici quinzaine ni les scores,
        // car ils seront (re)créés dynamiquement dans createEditFormBuilder().
        return [
            $typePaie,
            $mois,
            $annee,
            // pas de : IntegerField::new('quinzaine', 'Quinzaine')
            $statut,
            // pas de : NumberField::new('scoreEquipe', …)
            // pas de : NumberField::new('scoreCollectif', …)
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('statut', 'Statut')->setChoices([
                'Inactive' => PeriodePaie::STATUT_INACTIF,
                'Ouverte' => PeriodePaie::STATUT_OUVERT,
                'Fermee' => PeriodePaie::STATUT_FERME,
                'Archivee' => PeriodePaie::STATUT_ARCHIVE,
            ]));
    }

    /**
     * NOTE IMPORTANTE : signature mise à jour pour correspondre exactement à la classe parent.
     *
     * @param EntityDto     $entityDto
     * @param KeyValueStore $formOptions
     * @param AdminContext  $context
     *
     * @return FormBuilderInterface
     *
     * @see EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController::createEditFormBuilder()
     */
    public function createEditFormBuilder(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormBuilderInterface {
        // On récupère le builder par défaut d'EasyAdmin
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        // 1) PRE_SET_DATA : au chargement du formulaire d'édition
        $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var PeriodePaie|null $periode */
            $periode = $event->getData();
            $form    = $event->getForm();

            // Désactiver 'quinzaine' si typePaie est "mensuelle"
            $disableQuinzaine = ($periode?->getTypePaie() === 'mensuelle');

            // Désactiver les scores si statut est "fermee"
            $disableScores = ($periode?->getStatut() === PeriodePaie::STATUT_FERME);

            // (Re)création de 'quinzaine'
            $form->add('quinzaine', IntegerType::class, [
                'label'    => 'Quinzaine',
                'required' => false,
                'disabled' => $disableQuinzaine,
                'attr'     => ['min' => 1, 'max' => 2],
                'help'     => '1 pour la 1ᵉ quinzaine, 2 pour la 2ᵉ (laisser vide si mensuelle).',
            ]);

            // (Re)création de 'scoreEquipe'
            $form->add('scoreEquipe', NumberType::class, [
                'label'    => 'Score équipe',
                'required' => false,
                'disabled' => $disableScores,
                'scale'    => 2,
                'help'     => 'Décimal (5,2), ex. : 12.34',
            ]);

            // (Re)création de 'scoreCollectif'
            $form->add('scoreCollectif', NumberType::class, [
                'label'    => 'Score collectif',
                'required' => false,
                'disabled' => $disableScores,
                'scale'    => 2,
                'help'     => 'Décimal (5,2), ex. : 45.67',
            ]);
        });

        // 2) PRE_SUBMIT : avant la validation, si l'utilisateur change
        //    'typePaie' ou 'statut' à la volée dans le formulaire
        $formBuilder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $submittedData = $event->getData();
            $form          = $event->getForm();

            // Si l'utilisateur choisit "mensuelle", on désactive quinzaine
            $disableQuinzaine = (($submittedData['typePaie'] ?? null) === 'mensuelle');
            $form->add('quinzaine', IntegerType::class, [
                'label'    => 'Quinzaine',
                'required' => false,
                'disabled' => $disableQuinzaine,
                'attr'     => ['min' => 1, 'max' => 2],
                'help'     => '1 pour la 1ᵉ quinzaine, 2 pour la 2ᵉ (laisser vide si mensuelle).',
            ]);

            // Si l'utilisateur choisit "fermee", on désactive les scores
            $disableScores = (($submittedData['statut'] ?? null) === PeriodePaie::STATUT_FERME);
            $form->add('scoreEquipe', NumberType::class, [
                'label'    => 'Score équipe',
                'required' => false,
                'disabled' => $disableScores,
                'scale'    => 2,
                'help'     => 'Décimal (5,2), ex. : 12.34',
            ]);
            $form->add('scoreCollectif', NumberType::class, [
                'label'    => 'Score collectif',
                'required' => false,
                'disabled' => $disableScores,
                'scale'    => 2,
                'help'     => 'Décimal (5,2), ex. : 45.67',
            ]);
        });

        return $formBuilder;
    }

    /**
     * Si vous souhaitez le même comportement en création (PAGE_NEW),
     * voici l'implémentation pour createNewFormBuilder().
     */
    public function createNewFormBuilder(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormBuilderInterface {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        // Ajout des champs dynamiques avec les mêmes listeners que pour l'édition
        $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            // Pour un nouveau formulaire, pas de désactivation par défaut
            $form->add('quinzaine', IntegerType::class, [
                'label'    => 'Quinzaine',
                'required' => false,
                'attr'     => ['min' => 1, 'max' => 2],
                'help'     => '1 pour la 1ᵉ quinzaine, 2 pour la 2ᵉ (laisser vide si mensuelle).',
            ]);

            $form->add('scoreEquipe', NumberType::class, [
                'label'    => 'Score équipe',
                'required' => false,
                'scale'    => 2,
                'help'     => 'Décimal (5,2), ex. : 12.34',
            ]);

            $form->add('scoreCollectif', NumberType::class, [
                'label'    => 'Score collectif',
                'required' => false,
                'scale'    => 2,
                'help'     => 'Décimal (5,2), ex. : 45.67',
            ]);
        });

        $formBuilder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $submittedData = $event->getData();
            $form          = $event->getForm();

            // Si l'utilisateur choisit "mensuelle", on désactive quinzaine
            $disableQuinzaine = (($submittedData['typePaie'] ?? null) === 'mensuelle');
            $form->add('quinzaine', IntegerType::class, [
                'label'    => 'Quinzaine',
                'required' => false,
                'disabled' => $disableQuinzaine,
                'attr'     => ['min' => 1, 'max' => 2],
                'help'     => '1 pour la 1ᵉ quinzaine, 2 pour la 2ᵉ (laisser vide si mensuelle).',
            ]);

            // Si l'utilisateur choisit "fermee", on désactive les scores
            $disableScores = (($submittedData['statut'] ?? null) === PeriodePaie::STATUT_FERME);
            $form->add('scoreEquipe', NumberType::class, [
                'label'    => 'Score équipe',
                'required' => false,
                'disabled' => $disableScores,
                'scale'    => 2,
                'help'     => 'Décimal (5,2), ex. : 12.34',
            ]);
            $form->add('scoreCollectif', NumberType::class, [
                'label'    => 'Score collectif',
                'required' => false,
                'disabled' => $disableScores,
                'scale'    => 2,
                'help'     => 'Décimal (5,2), ex. : 45.67',
            ]);
        });

        return $formBuilder;
    }
}

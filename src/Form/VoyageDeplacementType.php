<?php

namespace App\Form;

use App\Entity\Employee;
use App\Entity\PeriodePaie;
use App\Entity\VoyageDeplacement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class VoyageDeplacementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            /* =========================
             * Employé concerné
             * ========================= */
            ->add('employee', EntityType::class, [
                'class' => Employee::class,
                'label' => 'Employé',
                'choice_label' => 'fullName', // ⚠️ adapte au vrai champ
                'placeholder' => 'Choisir un employé',
                'choices' => $options['employees'],  // ← Use the option
            ])

            /* =========================
             * Période de paie (lecture seule)
             * ========================= */
            ->add('periodePaie', TextType::class, [
                'label' => 'Période de paie',
                'mapped' => false,
                'disabled' => true,
                'data' => $options['periode_paie_label'] ?? '',
                'attr' => [
                    'class' => 'form-control-plaintext bg-light',
                    'readonly' => true,
                ],
            ])


            /* =========================
             * Type de voyage
             * ========================= */
            ->add('typeVoyage', ChoiceType::class, [
                'label' => 'Type de mission',
                'required' => false,
                'choices' => [
                    'Mission' => 'mission',
                    'Déplacement interne' => 'interne',
                    'Formation' => 'formation',
                ],
                'placeholder' => 'Choisir le type',
            ])

            /* =========================
             * Motif
             * ========================= */
            ->add('motif', TextareaType::class, [
                'label' => 'Motif du déplacement',
                'required' => false,
                'attr' => ['rows' => 3],
            ])

            /* =========================
             * Mode de transport
             * ========================= */
            ->add('modeTransport', ChoiceType::class, [
                'label' => 'Mode de transport',
                'choices' => [
                    'Voiture' => 'voiture',
                    'Train' => 'train',
                    'Avion' => 'avion',
                    'Bus' => 'bus',
                    'Autre' => 'autre',
                ],
            ])

            /* =========================
             * ALLER
             * ========================= */
            ->add('dateHeureDepart', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
            ])

            ->add('villeDepartAller', TextType::class, [
                'label' => 'Ville de départ',
                'required' => false,
                'attr' => [
                    'class' => 'form-control autocomplete-ville',
                    'placeholder' => 'Ex: Paris, France',
                    'data-type' => 'depart-aller',
                ],
            ])

            ->add('villeArriveeAller', TextType::class, [
                'label' => 'Ville d\'arrivée',
                'required' => false,
                'attr' => [
                    'class' => 'form-control autocomplete-ville',
                    'placeholder' => 'Ex: Lyon, France',
                    'data-type' => 'arrivee-aller',
                ],
            ])

            /* =========================
             * RETOUR
             * ========================= */
            ->add('dateHeureRetour', DateTimeType::class, [
                'label' => 'Date et heure de retour',
                'widget' => 'single_text',
            ])

            ->add('villeDepartRetour', TextType::class, [
                'label' => 'Ville de départ (retour)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control autocomplete-ville',
                    'placeholder' => 'Ex: Lyon, France',
                    'data-type' => 'depart-retour',
                ],
            ])

            ->add('villeArriveeRetour', TextType::class, [
                'label' => 'Ville d\'arrivée (retour)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control autocomplete-ville',
                    'placeholder' => 'Ex: Paris, France',
                    'data-type' => 'arrivee-retour',
                ],
            ])

            /* =========================
             * Champs cachés pour stocker les coordonnées
             * ========================= */
            ->add('latDepartAller', HiddenType::class)
            ->add('lonDepartAller', HiddenType::class)
            ->add('latArriveeAller', HiddenType::class)
            ->add('lonArriveeAller', HiddenType::class)
            
            ->add('latDepartRetour', HiddenType::class)
            ->add('lonDepartRetour', HiddenType::class)
            ->add('latArriveeRetour', HiddenType::class)
            ->add('lonArriveeRetour', HiddenType::class)

            /* =========================
             * Distance totale (calculée automatiquement)
             * ========================= */
            ->add('distanceKm', HiddenType::class)
            /* =========================
             * Commentaire
             * ========================= */
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VoyageDeplacement::class,
            'employees' => null,  // ← Add this
            'periode_paie_label' => '', // ✅ IMPORTANT : Ajoutez cette ligne
        ]);
    }
}

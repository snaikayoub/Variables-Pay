<?php

// src/Form/PeriodePaieType.php

namespace App\Form;

use App\Entity\PeriodePaie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PeriodePaieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('scoreEquipe', NumberType::class, [
                'required' => false,
                'label' => 'Score Équipe'
            ])
            ->add('scoreCollectif', NumberType::class, [
                'required' => false,
                'label' => 'Score Collectif'
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Ouverte' => 'Ouverte',
                    'Fermée' => 'Fermée'
                ],
                'label' => 'Statut'
            ]);
    }
}

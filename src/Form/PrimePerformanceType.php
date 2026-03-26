<?php
// src/Form/PrimePerformanceType.php
namespace App\Form;

use App\Entity\PrimePerformance;
use App\Entity\Employee;
use App\Entity\PeriodePaie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrimePerformanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('employee', EntityType::class, [
                'class'       => Employee::class,
                'choice_label'=> 'matricule',
                'label'       => 'Collaborateur',
            ])
            ->add('periodePaie', EntityType::class, [
                'class'        => PeriodePaie::class,
                'choice_label' => fn(PeriodePaie $p)=> sprintf('%s %02d/%04d', 
                                                ucfirst($p->getTypePaie()), 
                                                $p->getMois(), 
                                                $p->getAnnee()
                                            ),
                'label'        => 'Période de paie',
            ])
            ->add('groupePerf', TextType::class, [
                'label' => 'Groupe de performance',
            ])
            ->add('tauxMonaitaire', MoneyType::class, [
                'label'    => 'Taux monétaire',
                'currency' => 'MAD',
                'scale'    => 2,
            ])
            ->add('joursPerf', NumberType::class, [
                'label' => 'Jours de performance',
                'scale' => 2,
            ])
            ->add('scoreEquipe', NumberType::class, [
                'label' => 'Score équipe (%)',
                'scale' => 2,
            ])
            ->add('scoreCollectif', NumberType::class, [
                'label' => 'Score collectif (%)',
                'scale' => 2,
            ])
            ->add('noteHierarchique', NumberType::class, [
                'label' => 'Note hiérarchique (%)',
                'scale' => 2,
            ])
        ;
        // montantPerf n'est pas saisi, il sera calculé en controller
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PrimePerformance::class,
        ]);
    }
}

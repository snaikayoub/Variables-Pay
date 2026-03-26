<?php
// src/Controller/Admin/PrimePerformanceCrudController.php
namespace App\Controller\Admin;

use App\Entity\PrimePerformance;
use App\Entity\PeriodePaie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Actions, Action, Crud};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PrimePerformanceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PrimePerformance::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('employee')
            ->setRequired(true);
        
        yield AssociationField::new('periodePaie')
            ->setRequired(true);
        
        yield NumberField::new('scoreEquipe', 'Score Équipe');
        yield NumberField::new('scoreCollectif', 'Score Collectif');
        yield NumberField::new('joursPerf', 'Jours Perf.');
        yield NumberField::new('noteHierarchique', 'Note Hiérarchique');

        yield NumberField::new('montantPerf', 'Montant')
            ->hideOnForm();
        
        if (Crud::PAGE_INDEX === $pageName || Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('status');
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        $submit = Action::new('submit','Soumettre')
            ->linkToCrudAction('submit')
            ->displayIf(fn(PrimePerformance $p) => $p->getStatus() === PrimePerformance::STATUS_DRAFT);

        $srvValidate = Action::new('service_validate','Valider service')
            ->linkToCrudAction('serviceValidate')
            ->displayIf(function(PrimePerformance $p) {
                // Get current employee situation to access the service
                $currentSituation = $this->getCurrentEmployeeSituation($p->getEmployee());
                if (!$currentSituation) {
                    return false;
                }
                
                return $p->getStatus() === PrimePerformance::STATUS_SUBMITTED
                    && $this->getUser() === $currentSituation->getService()->getValidateurService();
            });

        $divValidate = Action::new('division_validate','Valider division')
            ->linkToCrudAction('divisionValidate')
            ->displayIf(function(PrimePerformance $p) {
                // Get current employee situation to access the service
                $currentSituation = $this->getCurrentEmployeeSituation($p->getEmployee());
                if (!$currentSituation) {
                    return false;
                }
                
                return $p->getStatus() === PrimePerformance::STATUS_SERVICE_VALIDATED
                    && $this->getUser() === $currentSituation->getService()->getDivision()->getValidateurDivision();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $submit)
            ->add(Crud::PAGE_INDEX, $srvValidate)
            ->add(Crud::PAGE_INDEX, $divValidate)
            ->add(Crud::PAGE_DETAIL, $submit)
            ->add(Crud::PAGE_DETAIL, $srvValidate)
            ->add(Crud::PAGE_DETAIL, $divValidate);
    }

    /**
     * Get the current employee situation (the one without an end date or with the latest end date)
     */
    private function getCurrentEmployeeSituation($employee)
    {
        if (!$employee) {
            return null;
        }
        
        $situations = $employee->getEmployeeSituations();
        if ($situations->isEmpty()) {
            return null;
        }
        
        $currentSituation = null;
        $latestDate = null;
        
        foreach ($situations as $situation) {
            // Situation without end date is the current one
            if ($situation->getEndDate() === null) {
                return $situation;
            }
            
            // Otherwise, find the one with the latest end date
            if ($latestDate === null || $situation->getEndDate() > $latestDate) {
                $latestDate = $situation->getEndDate();
                $currentSituation = $situation;
            }
        }
        
        return $currentSituation;
    }

    /**
     * Calculate montantPerf before persisting
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var PrimePerformance $entityInstance */
        $entityInstance->calculerMontant();
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Calculate montantPerf before updating
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var PrimePerformance $entityInstance */
        $entityInstance->calculerMontant();
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function submit(AdminContext $context, EntityManagerInterface $em, WorkflowInterface $prime_performance): RedirectResponse
    {
        /** @var PrimePerformance $entity **/
        $entity = $context->getEntity()->getInstance();
        $prime_performance->apply($entity, 'submit');
        $em->flush();
        $this->addFlash('success','Soumis pour validation.');
        return $this->redirect($context->getReferrer());
    }

    public function serviceValidate(AdminContext $context, EntityManagerInterface $em, WorkflowInterface $prime_performance): RedirectResponse
    {
        $entity = $context->getEntity()->getInstance();
        $prime_performance->apply($entity, 'service_validate');
        $em->flush();
        $this->addFlash('success','Validé par le service.');
        return $this->redirect($context->getReferrer());
    }

    public function divisionValidate(AdminContext $context, EntityManagerInterface $em, WorkflowInterface $prime_performance): RedirectResponse
    {
        $entity = $context->getEntity()->getInstance();
        $prime_performance->apply($entity, 'division_validate');
        $em->flush();
        $this->addFlash('success','Validé par la division.');
        return $this->redirect($context->getReferrer());
    }
}
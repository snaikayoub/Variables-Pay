<?php

namespace App\Controller\Admin;

use App\Entity\Conge;
use App\Entity\Employee;
use App\Entity\EmployeeSituation;
use App\Entity\PrimeFonction;
use App\Entity\PrimePerformance;
use App\Entity\VoyageDeplacement;
use App\Filter\CurrentServiceFilter;
use App\Filter\CurrentTypePaieFilter;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\HttpFoundation\Response;

class EmployeeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Employee::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Collaborateurs')
            ->setPageTitle('new', 'Ajouter un collaborateur')
            ->setPageTitle('edit', fn (Employee $employee) => sprintf('Modifier %s', $employee->getNom(). ' ' . $employee->getPrenom()))
            ->setEntityLabelInPlural('Collaborateurs')
            ->setEntityLabelInSingular('Collaborateur');
    }
    public function configureActions(Actions $actions): Actions
    {
        $safeBatchDelete = Action::new('safeBatchDelete', 'Supprimer (sans historique)', 'fa fa-trash')
            ->linkToCrudAction('safeBatchDelete')
            ->createAsBatchAction()
            ->addCssClass('text-danger');

        $cascadeBatchDelete = Action::new('cascadeBatchDelete', 'Supprimer (cascade)', 'fa fa-skull-crossbones')
            ->linkToCrudAction('cascadeBatchDelete')
            ->createAsBatchAction()
            ->addCssClass('text-danger');

        return $actions
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->disable(Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, $safeBatchDelete)
            ->add(Crud::PAGE_INDEX, $cascadeBatchDelete);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Employee) {
            $this->cascadeDeleteEmployee($entityManager, $entityInstance);

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function safeBatchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('ea-batch-action-'.$batchActionDto->getName(), $batchActionDto->getCsrfToken())) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine')->getManagerForClass(Employee::class);
        $repo = $em->getRepository(Employee::class);

        $deleted = 0;
        $skipped = [];

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var Employee|null $employee */
            $employee = $repo->find($id);
            if (null === $employee) {
                continue;
            }

            $counts = [
                'situations' => $em->getRepository(EmployeeSituation::class)->count(['employee' => $employee]),
                'primePerformance' => $em->getRepository(PrimePerformance::class)->count(['employee' => $employee]),
                'primeFonction' => $em->getRepository(PrimeFonction::class)->count(['employee' => $employee]),
                'conges' => $em->getRepository(Conge::class)->count(['employee' => $employee]),
                'voyages' => $em->getRepository(VoyageDeplacement::class)->count(['employee' => $employee]),
            ];

            $hasDependencies = array_sum($counts) > 0;
            if ($hasDependencies) {
                $skipped[] = sprintf(
                    '%s (%s %s)',
                    $employee->getMatricule(),
                    $employee->getNom(),
                    $employee->getPrenom()
                );
                continue;
            }

            $em->remove($employee);
            $deleted++;
        }

        if ($deleted > 0) {
            $em->flush();
            $this->addFlash('success', sprintf('%d collaborateur(s) supprime(s).', $deleted));
        }

        if (!empty($skipped)) {
            $this->addFlash('warning', sprintf(
                '%d collaborateur(s) non supprime(s) car lie(s) a de l\'historique (primes, situations, conges, voyages): %s',
                count($skipped),
                implode(', ', $skipped)
            ));
        }

        return $this->redirect(
            $this->container->get(AdminUrlGeneratorInterface::class)
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->set(EA::PAGE, 1)
                ->generateUrl()
        );
    }

    public function cascadeBatchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('ea-batch-action-'.$batchActionDto->getName(), $batchActionDto->getCsrfToken())) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine')->getManagerForClass(Employee::class);
        $repo = $em->getRepository(Employee::class);

        $deleted = 0;
        $failed = [];

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var Employee|null $employee */
            $employee = $repo->find($id);
            if (null === $employee) {
                continue;
            }

            try {
                $this->cascadeDeleteEmployee($em, $employee);
                $deleted++;
            } catch (ForeignKeyConstraintViolationException $e) {
                $failed[] = sprintf('%s (%s %s)', $employee->getMatricule(), $employee->getNom(), $employee->getPrenom());
            }

            $em->clear();
        }

        if ($deleted > 0) {
            $this->addFlash('success', sprintf('%d collaborateur(s) supprime(s) en cascade.', $deleted));
        }

        if (!empty($failed)) {
            $this->addFlash('error', sprintf(
                '%d collaborateur(s) non supprime(s) (dependances non gerees): %s',
                count($failed),
                implode(', ', $failed)
            ));
        }

        return $this->redirect(
            $this->container->get(AdminUrlGeneratorInterface::class)
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->set(EA::PAGE, 1)
                ->generateUrl()
        );
    }

    private function cascadeDeleteEmployee(EntityManagerInterface $em, Employee $employee): void
    {
        // if the employee is referenced as the processor of other leave requests,
        // keep those requests and only nullify the reference
        $em->createQuery('UPDATE App\\Entity\\Conge c SET c.traitePar = NULL WHERE c.traitePar = :emp')
            ->setParameter('emp', $employee)
            ->execute();

        // remove all dependent records owned by this employee
        $em->createQuery('DELETE FROM App\\Entity\\PrimePerformance pp WHERE pp.employee = :emp')
            ->setParameter('emp', $employee)
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\PrimeFonction pf WHERE pf.employee = :emp')
            ->setParameter('emp', $employee)
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\VoyageDeplacement v WHERE v.employee = :emp')
            ->setParameter('emp', $employee)
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\Conge c WHERE c.employee = :emp')
            ->setParameter('emp', $employee)
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\EmployeeSituation es WHERE es.employee = :emp')
            ->setParameter('emp', $employee)
            ->execute();

        $em->remove($employee);
        $em->flush();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(CurrentServiceFilter::new('service', 'Service'))
            ->add(CurrentTypePaieFilter::new('typePaie', 'Type de paie'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('matricule', 'Matricule'),
            TextField::new('nom', 'Nom'),
            TextField::new('prenom', 'Prenom'),
            AssociationField::new('grpPerf', 'Groupe de performance'),
            AssociationField::new('categorieFonction', 'Categorie de fonction'),
            TextField::new('currentCategoryName', 'Categorie professionnelle')
                ->onlyOnIndex(),
            TextField::new('currentServiceName', 'Service')
                ->onlyOnIndex(),
        ];
    }
}

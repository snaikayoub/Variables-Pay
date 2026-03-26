<?php

namespace App\Controller\Admin;

use App\Entity\PrimeFonction;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Workflow\WorkflowInterface;

class PrimeFonctionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PrimeFonction::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('employee')->setRequired(true);
        yield AssociationField::new('periodePaie')->setRequired(true);

        // Taux monetaire calcule automatiquement depuis la categorie de fonction du collaborateur
        yield NumberField::new('tauxMonetaireFonction', 'Taux monetaire')
            ->hideOnForm();
        yield NumberField::new('nombreJours', 'Nombre de jours');
        yield NumberField::new('noteHierarchique', 'Note (0..1)');

        yield NumberField::new('montantFonction', 'Montant')->hideOnForm();

        if (Crud::PAGE_INDEX === $pageName || Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('status');
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var PrimeFonction $entityInstance */
        $entityInstance->calculerMontant();
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var PrimeFonction $entityInstance */
        $entityInstance->calculerMontant();
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureActions(Actions $actions): Actions
    {
        $submit = Action::new('submit', 'Soumettre')
            ->linkToCrudAction('submit')
            ->displayIf(fn(PrimeFonction $p) => $p->getStatus() === PrimeFonction::STATUS_DRAFT);

        $srvValidate = Action::new('service_validate', 'Valider service')
            ->linkToCrudAction('serviceValidate')
            ->displayIf(fn(PrimeFonction $p) => $p->getStatus() === PrimeFonction::STATUS_SUBMITTED);

        $divValidate = Action::new('division_validate', 'Valider division')
            ->linkToCrudAction('divisionValidate')
            ->displayIf(fn(PrimeFonction $p) => $p->getStatus() === PrimeFonction::STATUS_SERVICE_VALIDATED);

        return $actions
            ->add(Crud::PAGE_INDEX, $submit)
            ->add(Crud::PAGE_INDEX, $srvValidate)
            ->add(Crud::PAGE_INDEX, $divValidate)
            ->add(Crud::PAGE_DETAIL, $submit)
            ->add(Crud::PAGE_DETAIL, $srvValidate)
            ->add(Crud::PAGE_DETAIL, $divValidate);
    }

    public function submit(AdminContext $context, EntityManagerInterface $em, WorkflowInterface $prime_fonction): RedirectResponse
    {
        /** @var PrimeFonction $entity */
        $entity = $context->getEntity()->getInstance();
        $prime_fonction->apply($entity, 'submit');
        $em->flush();
        $this->addFlash('success', 'Soumis pour validation.');

        return $this->redirect($context->getReferrer());
    }

    public function serviceValidate(AdminContext $context, EntityManagerInterface $em, WorkflowInterface $prime_fonction): RedirectResponse
    {
        $entity = $context->getEntity()->getInstance();
        $prime_fonction->apply($entity, 'service_validate');
        $em->flush();
        $this->addFlash('success', 'Valide par le service.');

        return $this->redirect($context->getReferrer());
    }

    public function divisionValidate(AdminContext $context, EntityManagerInterface $em, WorkflowInterface $prime_fonction): RedirectResponse
    {
        $entity = $context->getEntity()->getInstance();
        $prime_fonction->apply($entity, 'division_validate');
        $em->flush();
        $this->addFlash('success', 'Valide par la division.');

        return $this->redirect($context->getReferrer());
    }
}

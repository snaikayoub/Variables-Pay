<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PeriodePaie;
use App\Entity\VoyageDeplacement;
use App\Form\VoyageDeplacementType;
use App\Service\GestionnaireService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PeriodePaieRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\VoyageDeplacementRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/gestionnaire/voyages')]
#[IsGranted('ROLE_GESTIONNAIRE_SERVICE')]
class VoyageDeplacementController extends AbstractController
{
    /**
     * 📋 Liste des voyages des collaborateurs gérés
     */
    #[Route('/', name: 'voyage_list')]
    public function list(
        Request $request,
        VoyageDeplacementRepository $repository,
        GestionnaireService $gestionnaireService
    ): Response {
        // Récupération de l'utilisateur connecté
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer les employés gérés par ce gestionnaire
        $employees = $gestionnaireService->getManagedEmployeesByUser($user);

        // Récupération des voyages liés aux employés gérés
        $voyages = [];
        if (!empty($employees)) {
            $criteria = ['employee' => $employees];

            $status = $request->query->get('status');
            if (is_string($status) && in_array($status, [
                VoyageDeplacement::STATUS_DRAFT,
                VoyageDeplacement::STATUS_SUBMITTED,
                VoyageDeplacement::STATUS_SERVICE_VALIDATED,
                VoyageDeplacement::STATUS_VALIDATED,
                VoyageDeplacement::STATUS_REJECTED,
            ], true)) {
                $criteria['status'] = $status;
            }

            $voyages = $repository->findBy($criteria, ['createdAt' => 'DESC']);
        }

        return $this->render('gestionnaire/voyages/list.html.twig', [
            'voyages' => $voyages,
            'statusFilter' => $request->query->get('status'),
        ]);
    }

    /**
     * ➕ Création d'un voyage (statut initial : draft)
     */
    #[Route('/new', name: 'voyage_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        PeriodePaieRepository $periodeRepo,
        VoyageDeplacementRepository $voyageRepo,
        GestionnaireService $gestionnaireService
    ): Response {
        $user = $this->getUser();

        // Sécurité
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // ✅ Récupérer le type de paie depuis l'URL
        $typePaie = $request->query->get('type');

        if (!in_array($typePaie, ['mensuelle', 'quinzaine'])) {
            throw new \InvalidArgumentException('Type de paie invalide. Utilisez "mensuelle" ou "quinzaine".');
        }


        // Récupération des collaborateurs gérés
        $employees = $gestionnaireService->getManagedEmployeesByUser($user, $typePaie);

        if (empty($employees)) {
            throw new \LogicException('Aucun collaborateur rattaché à ce gestionnaire.');
        }

        $voyage = new VoyageDeplacement();
        $voyage->setStatus(VoyageDeplacement::STATUS_DRAFT);
        $voyage->setCreatedAt(new \DateTimeImmutable());

        // ✅ Période de paie ouverte avec le type correspondant
        $periode = $periodeRepo->findOneBy([
            'statut' => PeriodePaie::STATUT_OUVERT,
            'typePaie' => $typePaie
        ]);
        if (!$periode) {
            throw new \LogicException('Aucune période de paie ouverte pour le type "' . $typePaie . '".');
        }

        // ✅ Assigner la période au voyage
        $voyage->setPeriodePaie($periode);

        // Formulaire avec choix des collaborateurs
        $form = $this->createForm(
            VoyageDeplacementType::class,
            $voyage,
            [
                'employees' => $employees, // 👈 clé importante
                'periode_paie_label' => $periode->__toString(), // ✅ Passer le label
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $departureAt = $voyage->getDateHeureDepart();
            $returnAt = $voyage->getDateHeureRetour();
            $employee = $voyage->getEmployee();

            if (null !== $departureAt && null !== $returnAt && $returnAt < $departureAt) {
                $form->get('dateHeureRetour')->addError(new FormError('La date/heure de retour doit etre apres le depart.'));
            } elseif ($employee && $departureAt && $voyageRepo->isDepartureOverlapping($employee, $departureAt)) {
                $form->get('dateHeureDepart')->addError(new FormError('Ce collaborateur a deja un deplacement sur ce creneau.'));
            }

            if ($form->isValid()) {
            $em->persist($voyage);
            $em->flush();

            $this->addFlash('success', 'Voyage enregistré en brouillon.');
            return $this->redirectToRoute('voyage_list');
            }
        }

        return $this->render('gestionnaire/voyages/new.html.twig', [
            'form' => $form->createView(),
            'typePaie' => $typePaie, // ✅ Pour affichage dans le template
        ]);
    }

    /**
     * ✏️ Édition d'un voyage (autorisé uniquement en draft ou rejected)
     */
    #[Route('/edit/{id}', name: 'voyage_edit')]
    public function edit(
        VoyageDeplacement $voyage,
        Request $request,
        EntityManagerInterface $em,
        GestionnaireService $gestionnaireService,
        WorkflowInterface $voyage_deplacement,
        VoyageDeplacementRepository $voyageRepo
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Sécurité métier
        if (!in_array($voyage->getStatus(), [
            VoyageDeplacement::STATUS_DRAFT,
            VoyageDeplacement::STATUS_REJECTED
        ])) {
            throw $this->createAccessDeniedException('Modification non autorisée.');
        }

        // Récupérer le type de paie depuis la période associée
        $periode = $voyage->getPeriodePaie();
        $typePaie = $periode?->getTypePaie();

        // Récupérer les collaborateurs gérés
        $employees = $gestionnaireService->getManagedEmployeesByUser($user, $typePaie);

        $managedEmployeeIds = array_map(static fn($e) => $e->getId(), $employees);
        $voyageEmployeeId = $voyage->getEmployee()?->getId();
        if (null === $voyageEmployeeId || !in_array($voyageEmployeeId, $managedEmployeeIds, true)) {
            throw $this->createAccessDeniedException('Voyage non autorise.');
        }

        $form = $this->createForm(VoyageDeplacementType::class, $voyage, [
            'employees' => $employees,
            'periode_paie_label' => $periode ? (string) $periode : '',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $voyage->setUpdatedAt(new \DateTimeImmutable());

            $departureAt = $voyage->getDateHeureDepart();
            $returnAt = $voyage->getDateHeureRetour();
            $employee = $voyage->getEmployee();

            if (null !== $departureAt && null !== $returnAt && $returnAt < $departureAt) {
                $form->get('dateHeureRetour')->addError(new FormError('La date/heure de retour doit etre apres le depart.'));
            } elseif ($employee && $departureAt && $voyageRepo->isDepartureOverlapping($employee, $departureAt, $voyage->getId())) {
                $form->get('dateHeureDepart')->addError(new FormError('Ce collaborateur a deja un deplacement sur ce creneau.'));
            }

            if (!$form->isValid()) {
                return $this->render('gestionnaire/voyages/edit.html.twig', [
                    'form' => $form->createView(),
                    'voyage' => $voyage,
                ]);
            }

            $em->flush();

            if ($request->request->has('save_and_submit')) {
                if ($voyage_deplacement->can($voyage, 'submit')) {
                    $voyage_deplacement->apply($voyage, 'submit');
                    $em->flush();

                    $this->addFlash('success', 'Voyage mis a jour et soumis pour validation.');

                    return $this->redirectToRoute('voyage_list');
                }

                $this->addFlash('warning', 'Voyage enregistre, mais soumission impossible pour ce statut.');
            }

            $this->addFlash('success', 'Voyage mis à jour.');

            return $this->redirectToRoute('voyage_list');
        }

        return $this->render('gestionnaire/voyages/edit.html.twig', [
            'form' => $form->createView(),
            'voyage' => $voyage,
        ]);
    }

    /**
     * 📤 Soumission du voyage (workflow : submit)
     */
    #[Route('/submit/{id}', name: 'voyage_submit', methods: ['POST'])]
    public function submit(
        VoyageDeplacement $voyage,
        Request $request,
        WorkflowInterface $voyage_deplacement,
        EntityManagerInterface $em,
        GestionnaireService $gestionnaireService
    ): Response {
        if (!$this->isCsrfTokenValid('voyage_submit_'.$voyage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('voyage_list');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $typePaie = $voyage->getPeriodePaie()?->getTypePaie();
        $managedEmployees = $gestionnaireService->getManagedEmployeesByUser($user, $typePaie);
        $managedEmployeeIds = array_map(static fn($e) => $e->getId(), $managedEmployees);
        $voyageEmployeeId = $voyage->getEmployee()?->getId();

        if (null === $voyageEmployeeId || !in_array($voyageEmployeeId, $managedEmployeeIds, true)) {
            throw $this->createAccessDeniedException('Soumission non autorisee.');
        }

        if ($voyage_deplacement->can($voyage, 'submit')) {
            $voyage_deplacement->apply($voyage, 'submit');
            $em->flush();

            $this->addFlash('success', 'Voyage soumis pour validation.');
        } else {
            $this->addFlash('error', 'Action non autorisée.');
        }

        return $this->redirectToRoute('voyage_list');
    }

    #[Route('/delete/{id}', name: 'voyage_delete', methods: ['POST'])]
    public function delete(
        VoyageDeplacement $voyage,
        Request $request,
        EntityManagerInterface $em,
        GestionnaireService $gestionnaireService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_voyage_'.$voyage->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('voyage_list');
        }

        if (!in_array($voyage->getStatus(), [VoyageDeplacement::STATUS_DRAFT, VoyageDeplacement::STATUS_REJECTED], true)) {
            $this->addFlash('error', 'Suppression non autorisee pour ce statut.');
            return $this->redirectToRoute('voyage_list');
        }

        $managedEmployees = $gestionnaireService->getManagedEmployeesByUser($user);
        $managedEmployeeIds = array_map(static fn($e) => $e->getId(), $managedEmployees);
        $voyageEmployeeId = $voyage->getEmployee()?->getId();

        if (null === $voyageEmployeeId || !in_array($voyageEmployeeId, $managedEmployeeIds, true)) {
            throw $this->createAccessDeniedException('Suppression non autorisee.');
        }

        $em->remove($voyage);
        $em->flush();

        $this->addFlash('success', 'Voyage supprime.');

        return $this->redirectToRoute('voyage_list');
    }
}

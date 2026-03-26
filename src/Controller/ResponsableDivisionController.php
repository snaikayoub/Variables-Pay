<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PeriodePaie;
use App\Entity\Service;
use App\Entity\PrimeFonction;
use App\Entity\PrimePerformance;
use App\Entity\VoyageDeplacement;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PeriodePaieRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\PrimeFonctionRepository;
use App\Repository\PrimePerformanceRepository;
use App\Repository\VoyageDeplacementRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/responsable/division', name: 'responsable_division_')]
class ResponsableDivisionController extends AbstractController
{
    #[Route('/validation/{module}', name: 'validation_overview', methods: ['GET'], requirements: ['module' => 'performance|fonction|voyages'])]
    public function validationOverview(
        string $module,
        Request $request,
        EntityManagerInterface $em,
        PeriodePaieRepository $periodeRepo,
        PrimePerformanceRepository $ppRepo,
        PrimeFonctionRepository $pfRepo,
        VoyageDeplacementRepository $voyageRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_DIVISION');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $type = (string) $request->query->get('type', 'mensuelle');
        if (!in_array($type, ['mensuelle', 'quinzaine'], true)) {
            throw $this->createNotFoundException();
        }

        // Services de la division du responsable
        $services = $em->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->join('s.division', 'd')
            ->where('d.validateurDivision = :user')
            ->setParameter('user', $user)
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();

        $serviceId = (int) $request->query->get('serviceId', 0);
        $service = null;
        if ($serviceId > 0) {
            $service = $em->getRepository(Service::class)->find($serviceId);
            if (!$service || $service->getDivision()?->getValidateurDivision() !== $user) {
                throw $this->createAccessDeniedException('Service non autorise');
            }
        }

        $periodeCourante = $periodeRepo->findOneBy(
            ['typePaie' => $type, 'statut' => PeriodePaie::STATUT_OUVERT],
            ['annee' => 'DESC', 'mois' => 'DESC', 'quinzaine' => 'DESC']
        );

        $today = new \DateTimeImmutable();
        $filterByService = static function (array $items, int $sid) use ($today): array {
            if ($sid <= 0) {
                return $items;
            }

            return array_values(array_filter($items, static function ($item) use ($sid, $today) {
                $employee = $item->getEmployee();
                if (null === $employee) {
                    return false;
                }

                $situations = $employee->getEmployeeSituations()->filter(
                    fn($es) =>
                        $es->getService()?->getId() === $sid
                        && $es->getStartDate() <= $today
                        && (null === $es->getEndDate() || $es->getEndDate() >= $today)
                );

                return !$situations->isEmpty();
            }));
        };

        $submitted = [];
        $ready = [];
        $validated = [];
        $rejected = [];

        if ('performance' === $module) {
            $submitted = $ppRepo->findByDivisionAndStatusAndType($user, PrimePerformance::STATUS_SUBMITTED, $type);
            $ready = $ppRepo->findByDivisionAndStatusAndType($user, PrimePerformance::STATUS_SERVICE_VALIDATED, $type);
            $validated = $ppRepo->findByDivisionAndStatusAndType($user, PrimePerformance::STATUS_DIVISION_VALIDATED, $type);

            if (null !== $service) {
                $submitted = $filterByService($submitted, $serviceId);
                $ready = $filterByService($ready, $serviceId);
                $validated = $filterByService($validated, $serviceId);
            }
        } elseif ('fonction' === $module) {
            $submitted = $pfRepo->findByDivisionAndStatusAndType($user, PrimeFonction::STATUS_SUBMITTED, $type);
            $ready = $pfRepo->findByDivisionAndStatusAndType($user, PrimeFonction::STATUS_SERVICE_VALIDATED, $type);
            $validated = $pfRepo->findByDivisionAndStatusAndType($user, PrimeFonction::STATUS_DIVISION_VALIDATED, $type);

            if (null !== $service) {
                $submitted = $filterByService($submitted, $serviceId);
                $ready = $filterByService($ready, $serviceId);
                $validated = $filterByService($validated, $serviceId);
            }
        } else {
            // voyages: uniquement visible apres validation service
            if (null !== $service) {
                $ready = $voyageRepo->findByDivisionValidatorServiceAndStatusAndType($user, $service, VoyageDeplacement::STATUS_SERVICE_VALIDATED, $type);
                $validated = $voyageRepo->findByDivisionValidatorServiceAndStatusAndType($user, $service, VoyageDeplacement::STATUS_VALIDATED, $type);
                $rejected = $voyageRepo->findByDivisionValidatorServiceAndStatusAndType($user, $service, VoyageDeplacement::STATUS_REJECTED, $type);
            } else {
                $ready = $voyageRepo->findByDivisionValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_SERVICE_VALIDATED, $type);
                $validated = $voyageRepo->findByDivisionValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_VALIDATED, $type);
                $rejected = $voyageRepo->findByDivisionValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_REJECTED, $type);
            }
        }

        return $this->render('responsable/division/validation.html.twig', [
            'module' => $module,
            'type' => $type,
            'services' => $services,
            'serviceId' => $serviceId,
            'service' => $service,
            'periodeCourante' => $periodeCourante,
            'submitted' => $submitted,
            'ready' => $ready,
            'validated' => $validated,
            'rejected' => $rejected,
        ]);
    }

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(PrimePerformanceRepository $repo, PrimeFonctionRepository $primeFonctionRepo, VoyageDeplacementRepository $voyageRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_DIVISION');
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Comptes pour le widget
        $countReadyMensuelle = $repo->countByTypeAndStatus($user, 'mensuelle', 'service_validated');
        $countReadyQuinzaine = $repo->countByTypeAndStatus($user, 'quinzaine', 'service_validated');
        $countValidMensuelle = $repo->countByTypeAndStatus($user, 'mensuelle', 'division_validated');
        $countValidQuinzaine = $repo->countByTypeAndStatus($user, 'quinzaine', 'division_validated');

        $countFonctionReadyMensuelle = $primeFonctionRepo->countByTypeAndStatus($user, 'mensuelle', PrimeFonction::STATUS_SERVICE_VALIDATED);
        $countFonctionReadyQuinzaine = $primeFonctionRepo->countByTypeAndStatus($user, 'quinzaine', PrimeFonction::STATUS_SERVICE_VALIDATED);
        $countFonctionValidMensuelle = $primeFonctionRepo->countByTypeAndStatus($user, 'mensuelle', PrimeFonction::STATUS_DIVISION_VALIDATED);
        $countFonctionValidQuinzaine = $primeFonctionRepo->countByTypeAndStatus($user, 'quinzaine', PrimeFonction::STATUS_DIVISION_VALIDATED);

        return $this->render('responsable/division/dashboard.html.twig', [
            'countReadyMensuelle' => $countReadyMensuelle,
            'countReadyQuinzaine' => $countReadyQuinzaine,
            'countValidMensuelle' => $countValidMensuelle,
            'countValidQuinzaine' => $countValidQuinzaine,
            'countVoyageReadyMensuelle' => $voyageRepo->countByDivisionValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_SERVICE_VALIDATED, 'mensuelle'),
            'countVoyageReadyQuinzaine' => $voyageRepo->countByDivisionValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_SERVICE_VALIDATED, 'quinzaine'),
            'countVoyageValidMensuelle' => $voyageRepo->countByDivisionValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_VALIDATED, 'mensuelle'),
            'countVoyageValidQuinzaine' => $voyageRepo->countByDivisionValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_VALIDATED, 'quinzaine'),
            'countFonctionReadyMensuelle' => $countFonctionReadyMensuelle,
            'countFonctionReadyQuinzaine' => $countFonctionReadyQuinzaine,
            'countFonctionValidMensuelle' => $countFonctionValidMensuelle,
            'countFonctionValidQuinzaine' => $countFonctionValidQuinzaine,
        ]);
    }

    // NOTE: older per-service GET screens were removed.

    #[Route('/validation/voyages/{type}/valider/{id}', name: 'voyages_valider', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerVoyage(
        string $type,
        VoyageDeplacement $voyage,
        Request $request,
        WorkflowInterface $voyage_deplacement,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_DIVISION');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('responsable_division_voyage_valider_'.$voyage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_dashboard');
        }

        $service = $voyage->getEmployee()?->getCurrentService();
        if (null === $service || $service->getDivision()?->getValidateurDivision() !== $user) {
            throw $this->createAccessDeniedException('Voyage non autorise');
        }

        if ($voyage_deplacement->can($voyage, 'division_validate')) {
            $voyage_deplacement->apply($voyage, 'division_validate');
            $em->flush();
            $this->addFlash('success', 'Deplacement valide par la division.');
        } else {
            $this->addFlash('error', 'Action non autorisee.');
        }

        return $this->redirectToRoute('responsable_division_validation_overview', [
            'module' => 'voyages',
            'type' => $type,
            'serviceId' => $request->request->getInt('serviceId', 0),
        ]);
    }

    #[Route('/validation/voyages/{type}/retour/{id}', name: 'voyages_retour', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourVoyage(
        string $type,
        VoyageDeplacement $voyage,
        Request $request,
        WorkflowInterface $voyage_deplacement,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_DIVISION');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('responsable_division_voyage_retour_'.$voyage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_dashboard');
        }

        $service = $voyage->getEmployee()?->getCurrentService();
        if (null === $service || $service->getDivision()?->getValidateurDivision() !== $user) {
            throw $this->createAccessDeniedException('Voyage non autorise');
        }

        if ($voyage_deplacement->can($voyage, 'retour_service')) {
            $voyage_deplacement->apply($voyage, 'retour_service');
            $em->flush();
            $this->addFlash('success', 'Deplacement retourne au service.');
        } else {
            $this->addFlash('error', 'Action non autorisee.');
        }

        return $this->redirectToRoute('responsable_division_validation_overview', [
            'module' => 'voyages',
            'type' => $type,
            'serviceId' => $request->request->getInt('serviceId', 0),
        ]);
    }

    #[Route('/validation/voyages/{type}/rejeter/{id}', name: 'voyages_rejeter', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function rejeterVoyage(
        string $type,
        VoyageDeplacement $voyage,
        Request $request,
        WorkflowInterface $voyage_deplacement,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_DIVISION');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('responsable_division_voyage_rejeter_'.$voyage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_dashboard');
        }

        $service = $voyage->getEmployee()?->getCurrentService();
        if (null === $service || $service->getDivision()?->getValidateurDivision() !== $user) {
            throw $this->createAccessDeniedException('Voyage non autorise');
        }

        if ($voyage_deplacement->can($voyage, 'reject')) {
            $voyage_deplacement->apply($voyage, 'reject');
            $em->flush();
            $this->addFlash('warning', 'Deplacement rejete.');
        } else {
            $this->addFlash('error', 'Action non autorisee.');
        }

        return $this->redirectToRoute('responsable_division_validation_overview', [
            'module' => 'voyages',
            'type' => $type,
            'serviceId' => $request->request->getInt('serviceId', 0),
        ]);
    }


    #[Route('/validation/performance/{type}/valider', name: 'performance_valider_batch', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerBatch(
        string $type,
        Request $request,
        PrimePerformanceRepository $repo,
        EntityManagerInterface $em,
        WorkflowInterface $prime_performance
    ): Response {
        if (!$this->isCsrfTokenValid('responsable_division_valider_batch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        // Récupère l’array de sélection correctement
        $ids = $request->request->all('selected') ?: [];

        if (empty($ids)) {
            $this->addFlash('warning', 'Aucune prime sélectionnée.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $primes = $repo->findByIdsForDivisionValidator($user, $ids);
        $count  = 0;
        foreach ($primes as $pp) {
            if ($prime_performance->can($pp, 'division_validate')) {
                $prime_performance->apply($pp, 'division_validate');
                $count++;
            }
        }
        $em->flush();

        $this->addFlash('success', "$count prime(s) validée(s) par la division.");
        return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
    }


    #[Route('/validation/performance/{type}/valider/{id}', name: 'performance_valider_ligne', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerLigne(
        string $type,
        PrimePerformance $pp,
        Request $request,
        WorkflowInterface $prime_performance,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('responsable_division_valider_ligne', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $service = $pp->getEmployee()?->getCurrentService();
        if (null === $service || $service->getDivision()?->getValidateurDivision() !== $user) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        // Vérifie si la prime peut être validée par la division
        if ($prime_performance->can($pp, 'division_validate')) {
            $prime_performance->apply($pp, 'division_validate');
            $em->flush();
            $this->addFlash('success', 'Prime validée par la division.');
        } else {
            $this->addFlash('error', 'Impossible de valider cette prime.');
        }

        return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
    }

    #[Route('/validation/performance/{type}/retour', name: 'performance_retour_batch', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourBatch(
        string $type,
        Request $request,
        PrimePerformanceRepository $repo,
        EntityManagerInterface $em,
        WorkflowInterface $prime_performance
    ): Response {
        if (!$this->isCsrfTokenValid('responsable_division_retour_batch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        // Récupère l’array de sélection correctement
        $ids = $request->request->all('selected') ?: [];

        if (empty($ids)) {
            $this->addFlash('warning', 'Aucune prime sélectionnée.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $primes = $repo->findByIdsForDivisionValidator($user, $ids);
        $count  = 0;
        foreach ($primes as $pp) {
            if ($prime_performance->can($pp, 'retour_service')) {
                $prime_performance->apply($pp, 'retour_service');
                $count++;
            }
        }
        $em->flush();

        $this->addFlash('success', "$count prime(s) retournée(s) par la division.");
        return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
    }

    #[Route('/validation/performance/{type}/retour/{id}', name: 'performance_retour_ligne', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourLigne(
        string $type,
        PrimePerformance $pp,
        Request $request,
        WorkflowInterface $prime_performance,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('responsable_division_retour_ligne', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $service = $pp->getEmployee()?->getCurrentService();
        if (null === $service || $service->getDivision()?->getValidateurDivision() !== $user) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        if ($prime_performance->can($pp, 'retour_service')) {
            $prime_performance->apply($pp, 'retour_service');
            $em->flush();
            $this->addFlash('success', 'Prime retournée au service.');
        } else {
            $this->addFlash('error', 'Impossible de retourner cette prime.');
        }

        return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'performance', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
    }

    // NOTE: older per-service fonction GET screen removed.

    #[Route('/validation/fonction/{type}/valider', name: 'fonction_valider_batch', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerFonctionBatch(
        string $type,
        Request $request,
        PrimeFonctionRepository $repo,
        EntityManagerInterface $em,
        WorkflowInterface $prime_fonction
    ): Response {
        if (!$this->isCsrfTokenValid('responsable_division_fonction_valider_batch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        $ids = $request->request->all('selected') ?: [];

        if (empty($ids)) {
            $this->addFlash('warning', 'Aucune prime sélectionnée.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $primes = $repo->findByIdsForDivisionValidator($user, $ids);
        $count = 0;

        foreach ($primes as $pf) {
            if ($prime_fonction->can($pf, 'division_validate')) {
                $prime_fonction->apply($pf, 'division_validate');
                $count++;
            }
        }

        $em->flush();
        $this->addFlash('success', $count . ' prime(s) validée(s) par la division.');

        return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
    }

    #[Route('/validation/fonction/{type}/valider/{id}', name: 'fonction_valider_ligne', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerFonctionLigne(
        string $type,
        PrimeFonction $pf,
        Request $request,
        WorkflowInterface $prime_fonction,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('responsable_division_fonction_valider_ligne', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $service = $pf->getEmployee()?->getCurrentService();
        if (null === $service || $service->getDivision()?->getValidateurDivision() !== $user) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        if ($prime_fonction->can($pf, 'division_validate')) {
            $prime_fonction->apply($pf, 'division_validate');
            $em->flush();
            $this->addFlash('success', 'Prime validée par la division.');
        } else {
            $this->addFlash('error', 'Impossible de valider cette prime.');
        }

        return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
    }

    #[Route('/validation/fonction/{type}/retour', name: 'fonction_retour_batch', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourFonctionBatch(
        string $type,
        Request $request,
        PrimeFonctionRepository $repo,
        EntityManagerInterface $em,
        WorkflowInterface $prime_fonction
    ): Response {
        if (!$this->isCsrfTokenValid('responsable_division_fonction_retour_batch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        $ids = $request->request->all('selected') ?: [];

        if (empty($ids)) {
            $this->addFlash('warning', 'Aucune prime sélectionnée.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $primes = $repo->findByIdsForDivisionValidator($user, $ids);
        $count = 0;

        foreach ($primes as $pf) {
            if ($prime_fonction->can($pf, 'retour_service')) {
                $prime_fonction->apply($pf, 'retour_service');
                $count++;
            }
        }

        $em->flush();
        $this->addFlash('success', $count . ' prime(s) retournée(s) au service.');

        return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
    }

    #[Route('/validation/fonction/{type}/retour/{id}', name: 'fonction_retour_ligne', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourFonctionLigne(
        string $type,
        PrimeFonction $pf,
        Request $request,
        WorkflowInterface $prime_fonction,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('responsable_division_fonction_retour_ligne', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $service = $pf->getEmployee()?->getCurrentService();
        if (null === $service || $service->getDivision()?->getValidateurDivision() !== $user) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        if ($prime_fonction->can($pf, 'retour_service')) {
            $prime_fonction->apply($pf, 'retour_service');
            $em->flush();
            $this->addFlash('success', 'Prime retournée au service.');
        } else {
            $this->addFlash('error', 'Impossible de retourner cette prime.');
        }

        return $this->redirectToRoute('responsable_division_validation_overview', ['module' => 'fonction', 'type' => $type, 'serviceId' => $request->request->getInt('serviceId', 0)]);
    }
}

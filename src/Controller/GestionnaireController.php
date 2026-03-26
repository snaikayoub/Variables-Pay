<?php

namespace App\Controller;

use App\Entity\CategoryTM;
use App\Entity\PeriodePaie;
use App\Entity\PrimeFonction;
use App\Entity\PrimePerformance;
use App\Entity\EmployeeSituation;
use App\Entity\VoyageDeplacement;
use App\Service\PeriodePaieService;
use App\Service\PrimePerformanceService;
use App\Repository\PrimePerformanceRepository;
use App\Repository\PrimeFonctionRepository;
use App\Repository\VoyageDeplacementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\GestionnaireService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;

#[Route('/gestionnaire')]
#[IsGranted('ROLE_GESTIONNAIRE_SERVICE')]
class GestionnaireController extends AbstractController
{
    private PeriodePaieService $periodePaieService;
    private GestionnaireService $GestionnaireService;
    private PrimePerformanceService $primePerformanceService;
    private PrimePerformanceRepository $primePerformanceRepository;

    public function __construct(
        PeriodePaieService $periodePaieService, 
        GestionnaireService $GestionnaireService, 
        PrimePerformanceService $primePerformanceService,
        PrimePerformanceRepository $primePerformanceRepository
    ) {
        $this->periodePaieService = $periodePaieService;
        $this->GestionnaireService = $GestionnaireService;
        $this->primePerformanceService = $primePerformanceService;
        $this->primePerformanceRepository = $primePerformanceRepository;
    }

    #[Route('/dashboard', name: 'gestionnaire_dashboard')]
    public function dashboard(EntityManagerInterface $em, VoyageDeplacementRepository $voyageRepo): Response
    {
        $user = $this->getUser();
        $services = $this->GestionnaireService->getManagedServicesByUser($user);
        $nombreCollaborateurs = $this->GestionnaireService->countCollaborateursByServices($services);

        $managedEmployees = $this->GestionnaireService->getManagedEmployeesByUser($user);
        $voyagesRejetesCount = 0;
        if (!empty($managedEmployees)) {
            $voyagesRejetesCount = $voyageRepo->count([
                'employee' => $managedEmployees,
                'status' => VoyageDeplacement::STATUS_REJECTED,
            ]);
        }

        $employeesMensuelle = $this->GestionnaireService->getManagedEmployeesByUser($user, 'mensuelle');
        $employeesQuinzaine = $this->GestionnaireService->getManagedEmployeesByUser($user, 'quinzaine');
        $periodeMensuelle = $this->periodePaieService->getPeriodeOuverte('mensuelle');
        $periodeQuinzaine = $this->periodePaieService->getPeriodeOuverte('quinzaine');

        $periodeFilters = array_values(array_filter([$periodeMensuelle, $periodeQuinzaine]));

        $countByEmployeeStatusAndPeriods = function (string $entityClass, string $status) use ($em, $managedEmployees, $periodeFilters): int {
            if (empty($managedEmployees)) {
                return 0;
            }

            $criteria = [
                'employee' => $managedEmployees,
                'status' => $status,
            ];

            if (!empty($periodeFilters)) {
                $criteria['periodePaie'] = $periodeFilters;
            }

            return $em->getRepository($entityClass)->count($criteria);
        };

        $primePerformanceCounts = [
            'saisie' => $countByEmployeeStatusAndPeriods(PrimePerformance::class, PrimePerformance::STATUS_DRAFT),
            'soumis' => $countByEmployeeStatusAndPeriods(PrimePerformance::class, PrimePerformance::STATUS_SUBMITTED),
            'rejete' => 0,
            'valide_service' => $countByEmployeeStatusAndPeriods(PrimePerformance::class, PrimePerformance::STATUS_SERVICE_VALIDATED),
            'valide_division' => $countByEmployeeStatusAndPeriods(PrimePerformance::class, PrimePerformance::STATUS_DIVISION_VALIDATED),
        ];

        $primeFonctionCounts = [
            'saisie' => $countByEmployeeStatusAndPeriods(PrimeFonction::class, PrimeFonction::STATUS_DRAFT),
            'soumis' => $countByEmployeeStatusAndPeriods(PrimeFonction::class, PrimeFonction::STATUS_SUBMITTED),
            'rejete' => 0,
            'valide_service' => $countByEmployeeStatusAndPeriods(PrimeFonction::class, PrimeFonction::STATUS_SERVICE_VALIDATED),
            'valide_division' => $countByEmployeeStatusAndPeriods(PrimeFonction::class, PrimeFonction::STATUS_DIVISION_VALIDATED),
        ];

        $deplacementsCounts = [
            'saisie' => $countByEmployeeStatusAndPeriods(VoyageDeplacement::class, VoyageDeplacement::STATUS_DRAFT),
            'soumis' => $countByEmployeeStatusAndPeriods(VoyageDeplacement::class, VoyageDeplacement::STATUS_SUBMITTED),
            'rejete' => $countByEmployeeStatusAndPeriods(VoyageDeplacement::class, VoyageDeplacement::STATUS_REJECTED),
            'valide_service' => $countByEmployeeStatusAndPeriods(VoyageDeplacement::class, VoyageDeplacement::STATUS_SERVICE_VALIDATED),
            'valide_division' => $countByEmployeeStatusAndPeriods(VoyageDeplacement::class, VoyageDeplacement::STATUS_VALIDATED),
        ];

        // Compter les primes saisies ce mois
        $primesSaisiesMois = $this->primePerformanceRepository->countPrimePerformanceSaisies($user);

        // Compter les primes en attente (draft)
        $primesEnAttente = $this->primePerformanceRepository->countPrimePerformanceEnAttente($user);

        // Compter les primes validées
        $primesValidees = $this->primePerformanceRepository->countPrimePerformanceValidees($user);

        return $this->render('gestionnaire/g_dashboard.html.twig', [
            'collaborateurs_count' => $nombreCollaborateurs,
            'collaborateurs_performance' => $nombreCollaborateurs, // Peut être affiné selon vos besoins
            'saisies_mois' => $primesSaisiesMois,
            'primes_en_attente' => $primesEnAttente,
            'primes_validees' => $primesValidees,
            'conges_en_attente' => 0, // À implémenter selon votre entité Congé
            'conges_approuves' => 0,  // À implémenter selon votre entité Congé
            'voyages_rejetes_count' => $voyagesRejetesCount,
            'collaborateurs_mensuelle_count' => is_countable($employeesMensuelle) ? count($employeesMensuelle) : 0,
            'collaborateurs_quinzaine_count' => is_countable($employeesQuinzaine) ? count($employeesQuinzaine) : 0,
            'periode_mensuelle_ouverte' => $periodeMensuelle,
            'periode_quinzaine_ouverte' => $periodeQuinzaine,

            'primePerformanceCounts' => $primePerformanceCounts,
            'primeFonctionCounts' => $primeFonctionCounts,
            'deplacementsCounts' => $deplacementsCounts,
        ]);
    }

    // src/Controller/GestionnaireController.php

    #[Route('/saisie/performance/{type}', name: 'gestionnaire_saisie_performance', methods: ['GET', 'POST'])]
    public function saisie(string $type, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // 1) Récupérer tous les services gérés par ce gestionnaire
        $services = $this->GestionnaireService->getManagedServicesByUser($user);

        // 2) Charger automatiquement la seule période "ouverte" de ce type
        $periodeouverte = $this->periodePaieService->getPeriodeOuverte($type);
        
        if (!$periodeouverte) {
            $this->addFlash('error', 'Aucune période ouverte pour ce type de paie.');
            return $this->redirectToRoute('gestionnaire_dashboard');
        }
        if (!$this->periodePaieService->isScoreConfigured($periodeouverte)) {
            $this->addFlash('error', 'Les scores pour cette période ne sont pas configurés.');
            return $this->redirectToRoute('gestionnaire_dashboard');
        }
        $scoreEquipe    = $periodeouverte->getScoreEquipe();
        $scoreCollectif = $periodeouverte->getScoreCollectif();

        // 3) Rassembler toutes les EmployeeSituations actives des services
        $allSituations = new ArrayCollection();
        foreach ($services as $srv) {
            foreach ($srv->getEmployeeSituations() as $es) {
                $allSituations->add($es);
            }
        }
        $today = new \DateTimeImmutable();
        $situations = $allSituations
            ->filter(
                fn(EmployeeSituation $es) =>
                $es->getTypePaie() === $type
                    && $es->getStartDate() <= $today
                    && (null === $es->getEndDate() || $es->getEndDate() >= $today)
            )
            ->toArray();

        // 4) Récupérer toutes les PrimePerformance existantes pour cette période
        /** @var PrimePerformance[] $allPP */
        $allPP = $em->getRepository(PrimePerformance::class)
            ->findBy(['periodePaie' => $periodeouverte]);

        // 5) Construire submittedMap : status ≠ draft
        $submittedMap = [];
        foreach ($allPP as $pp) {
            if ($pp->getStatus() !== PrimePerformance::STATUS_DRAFT) {
                $submittedMap[$pp->getEmployee()->getId()] = $pp;
            }
        }

        // 6) Filtrer submittedMap pour ne garder que les employés issus de nos situations
        $validEmployeeIds = array_map(
            fn(EmployeeSituation $es) => $es->getEmployee()->getId(),
            $situations
        );
        $submittedMap = array_filter(
            $submittedMap,
            fn(PrimePerformance $pp) => in_array($pp->getEmployee()->getId(), $validEmployeeIds, true)
        );

        // 7) pending = celles sans PP ou PP encore en draft
        $pending = array_filter(
            $situations,
            fn(EmployeeSituation $es) =>
            !isset($submittedMap[$es->getEmployee()->getId()])
        );

        // 8) Rendre la vue
        return $this->render('gestionnaire/g_prime_performance.html.twig', [
            'type'           => $type,
            'periode'        => $periodeouverte,
            'scoreEquipe'    => $scoreEquipe,
            'scoreCollectif' => $scoreCollectif,
            'pending'        => $pending,
            'submittedMap'   => $submittedMap,
        ]);
    }


    #[Route('/saisie/performance/{type}/submit/{esId}', name: 'gestionnaire_submit_line', methods: ['POST'])]
    public function submitLine(
        string $type,
        int $esId,
        Request $request,
        EntityManagerInterface $em,
        WorkflowInterface $prime_performance
    ): Response {
        if (!$this->isCsrfTokenValid('gestionnaire_submit_line_' . $esId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('gestionnaire_saisie_performance', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $periodeId = $request->request->getInt('periode');

        $es = $em->getRepository(EmployeeSituation::class)->find($esId);
        $periode = $em->getRepository(PeriodePaie::class)->find($periodeId);

        if (!$es || !$periode) {
            $this->addFlash('error', 'Donnée introuvable.');
        } else {
            $service = $es->getService();
            if (null === $service || !$service->getGestionnaire()->contains($user)) {
                throw $this->createAccessDeniedException('Situation non autorisee');
            }

            // Re-vérifier les scores dans la période
            if (
                null === $periode->getScoreEquipe() || '' === trim((string) $periode->getScoreEquipe())
                || null === $periode->getScoreCollectif() || '' === trim((string) $periode->getScoreCollectif())
            ) {
                $this->addFlash('error', 'Les scores pour cette période ne sont pas configurés.');
                return $this->redirectToRoute('gestionnaire_saisie_performance', ['type' => $type]);
            }

            $all = $request->request->all();
            $vals = $all['vals'] ?? [];

            if (empty($vals['joursPerf']) || empty($vals['noteHierarchique'])) {
                $this->addFlash('error', 'Veuillez renseigner tous les champs avant de soumettre.');
            } else {
                try {
                    $repo = $em->getRepository(PrimePerformance::class);
                    $pp = $repo->findOneBy([
                        'periodePaie' => $periode,
                        'employee'    => $es->getEmployee(),
                    ]);
                    if (!$pp) {
                        $pp = new PrimePerformance();
                        $pp->setEmployee($es->getEmployee())
                            ->setPeriodePaie($periode)
                            ->setStatus(PrimePerformance::STATUS_DRAFT);
                    }

                    // Récupérer le taux monétaire depuis CategoryTM
                    $grp = $es->getEmployee()->getGrpPerf();
                    $cat = $es->getCategory();
                    $model = $em->getRepository(CategoryTM::class)
                        ->findOneBy(['grpPerf' => $grp, 'category' => $cat]);
                    $tm = $model?->getTM() ?? 0.0;

                    $pp->setTauxMonetaire($tm)
                        ->setJoursPerf((float)$vals['joursPerf'])
                        ->setNoteHierarchique((float)$vals['noteHierarchique'])
                        ->calculerMontant();

                    $em->persist($pp);

                    // Changer le statut via le workflow (de draft à submitted)
                    if ($prime_performance->can($pp, 'submit')) {
                        $prime_performance->apply($pp, 'submit');
                    }
                    $em->flush();

                    $this->addFlash('success', sprintf(
                        'Prime de %s calculée : %s',
                        $es->getEmployee()->getMatricule(),
                        $pp->getMontantFormate()
                    ));
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', 'Erreur de calcul : ' . $e->getMessage());
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
                }
            }
        }

        return $this->redirectToRoute('gestionnaire_saisie_performance', [
            'type'    => $type,
        ]);
    }

    // src/Controller/GestionnaireController.php

    #[Route('/saisie/performance/{type}/revert/{ppId}', name: 'gestionnaire_revert_line', methods: ['POST'])]
    public function revertLine(
        string $type,
        int $ppId,
        Request $request,
        EntityManagerInterface $em,
        WorkflowInterface $prime_performance
    ): Response {
        if (!$this->isCsrfTokenValid('gestionnaire_revert_line_' . $ppId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('gestionnaire_saisie_performance', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Récupération de l'ID de période depuis le champ caché
        $periodeId = $request->request->getInt('periode');

        /** @var PrimePerformance|null $pp */
        $pp = $em->getRepository(PrimePerformance::class)->find($ppId);

        if (!$pp) {
            $this->addFlash('error', 'Prime introuvable.');
        } else {
            $service = $pp->getEmployee()?->getCurrentService();
            if (null === $service || !$service->getGestionnaire()->contains($user)) {
                throw $this->createAccessDeniedException('Prime non autorisee');
            }

            // Vérifier qu'on peut bien effectuer la transition de retour
            if ($prime_performance->can($pp, 'retour_gestionnaire')) {
                $prime_performance->apply($pp, 'retour_gestionnaire');
                $em->flush();
                $this->addFlash('success', 'Ligne remise en cours de modification.');
            } else {
                $this->addFlash('warning', 'Impossible de remettre cette ligne en modification.');
            }
        }

        // Redirection vers le même écran de saisie
        return $this->redirectToRoute('gestionnaire_saisie_performance', [
            'type'    => $type,
            'periode' => $periodeId,
        ]);
    }

    #[Route('/saisie/fonction/{type}', name: 'gestionnaire_saisie_fonction', methods: ['GET'])]
    public function saisieFonction(string $type, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $services = $this->GestionnaireService->getManagedServicesByUser($user);

        $periodeouverte = $this->periodePaieService->getPeriodeOuverte($type);
        if (!$periodeouverte) {
            $this->addFlash('error', 'Aucune période ouverte pour ce type de paie.');
            return $this->redirectToRoute('gestionnaire_dashboard');
        }

        $allSituations = new ArrayCollection();
        foreach ($services as $srv) {
            foreach ($srv->getEmployeeSituations() as $es) {
                $allSituations->add($es);
            }
        }

        $today = new \DateTimeImmutable();
        $situations = $allSituations
            ->filter(
                fn(EmployeeSituation $es) =>
                    $es->getTypePaie() === $type
                    && $es->getStartDate() <= $today
                    && (null === $es->getEndDate() || $es->getEndDate() >= $today)
            )
            ->toArray();

        /** @var PrimeFonction[] $allPF */
        $allPF = $em->getRepository(PrimeFonction::class)->findBy(['periodePaie' => $periodeouverte]);

        $submittedMap = [];
        foreach ($allPF as $pf) {
            if ($pf->getStatus() !== PrimeFonction::STATUS_DRAFT) {
                $submittedMap[$pf->getEmployee()->getId()] = $pf;
            }
        }

        $validEmployeeIds = array_map(
            fn(EmployeeSituation $es) => $es->getEmployee()->getId(),
            $situations
        );
        $submittedMap = array_filter(
            $submittedMap,
            fn(PrimeFonction $pf) => in_array($pf->getEmployee()->getId(), $validEmployeeIds, true)
        );

        $pending = array_filter(
            $situations,
            fn(EmployeeSituation $es) => !isset($submittedMap[$es->getEmployee()->getId()])
        );

        return $this->render('gestionnaire/g_prime_fonction.html.twig', [
            'type' => $type,
            'periode' => $periodeouverte,
            'pending' => $pending,
            'submittedMap' => $submittedMap,
        ]);
    }

    #[Route('/saisie/fonction/{type}/submit/{esId}', name: 'gestionnaire_submit_fonction_line', methods: ['POST'])]
    public function submitFonctionLine(
        string $type,
        int $esId,
        Request $request,
        EntityManagerInterface $em,
        WorkflowInterface $prime_fonction
    ): Response {
        if (!$this->isCsrfTokenValid('gestionnaire_submit_fonction_line_' . $esId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('gestionnaire_saisie_fonction', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $periodeId = $request->request->getInt('periode');

        $es = $em->getRepository(EmployeeSituation::class)->find($esId);
        $periode = $em->getRepository(PeriodePaie::class)->find($periodeId);

        if (!$es || !$periode) {
            $this->addFlash('error', 'Donnée introuvable.');
            return $this->redirectToRoute('gestionnaire_saisie_fonction', ['type' => $type]);
        }

        $service = $es->getService();
        if (null === $service || !$service->getGestionnaire()->contains($user)) {
            throw $this->createAccessDeniedException('Situation non autorisee');
        }

        $all = $request->request->all();
        $vals = $all['vals'] ?? [];

        if ('' === (string) ($vals['nombreJours'] ?? '') || '' === (string) ($vals['noteHierarchique'] ?? '')) {
            $this->addFlash('error', 'Veuillez renseigner tous les champs avant de soumettre.');
            return $this->redirectToRoute('gestionnaire_saisie_fonction', ['type' => $type]);
        }

        try {
            $repo = $em->getRepository(PrimeFonction::class);
            $pf = $repo->findOneBy([
                'periodePaie' => $periode,
                'employee' => $es->getEmployee(),
            ]);

            if (!$pf) {
                $pf = new PrimeFonction();
                $pf
                    ->setEmployee($es->getEmployee())
                    ->setPeriodePaie($periode)
                    ->setStatus(PrimeFonction::STATUS_DRAFT);
            }

            // Taux monetaire: derive de la categorie de fonction affectee au collaborateur
            $tm = $es->getEmployee()?->getCategorieFonction()?->getTauxMonetaire() ?? 0.0;

            $pf
                ->setTauxMonetaireFonction((float) $tm)
                ->setNombreJours((float) $vals['nombreJours'])
                ->setNoteHierarchique((float) $vals['noteHierarchique'])
                ->calculerMontant();

            $em->persist($pf);

            if ($prime_fonction->can($pf, 'submit')) {
                $prime_fonction->apply($pf, 'submit');
            }

            $em->flush();

            $this->addFlash('success', sprintf(
                'Prime de fonction de %s calculee : %s',
                $es->getEmployee()->getMatricule(),
                $pf->getMontantFormate()
            ));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Erreur de calcul : ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }

        return $this->redirectToRoute('gestionnaire_saisie_fonction', ['type' => $type]);
    }

    #[Route('/saisie/fonction/{type}/revert/{pfId}', name: 'gestionnaire_revert_fonction_line', methods: ['POST'])]
    public function revertFonctionLine(
        string $type,
        int $pfId,
        Request $request,
        EntityManagerInterface $em,
        WorkflowInterface $prime_fonction
    ): Response {
        if (!$this->isCsrfTokenValid('gestionnaire_revert_fonction_line_' . $pfId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('gestionnaire_saisie_fonction', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $periodeId = $request->request->getInt('periode');

        /** @var PrimeFonction|null $pf */
        $pf = $em->getRepository(PrimeFonction::class)->find($pfId);

        if (!$pf) {
            $this->addFlash('error', 'Prime introuvable.');
            return $this->redirectToRoute('gestionnaire_saisie_fonction', ['type' => $type]);
        }

        $service = $pf->getEmployee()?->getCurrentService();
        if (null === $service || !$service->getGestionnaire()->contains($user)) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        if ($prime_fonction->can($pf, 'retour_gestionnaire')) {
            $prime_fonction->apply($pf, 'retour_gestionnaire');
            $em->flush();
            $this->addFlash('success', 'Ligne remise en cours de modification.');
        } else {
            $this->addFlash('warning', 'Impossible de remettre cette ligne en modification.');
        }

        return $this->redirectToRoute('gestionnaire_saisie_fonction', [
            'type' => $type,
            'periode' => $periodeId,
        ]);
    }


    #[Route('/api/periode/{id}/scores', name: 'api_periode_scores', methods: ['GET'])]
    public function getScoresPeriode(int $id, EntityManagerInterface $em): JsonResponse
    {
        $periode = $em->getRepository(PeriodePaie::class)->find($id);
        if (!$periode) {
            return new JsonResponse(['error' => 'Période introuvable'], 404);
        }
        return new JsonResponse([
            'scoreEquipe'    => $periode->getScoreEquipe(),
            'scoreCollectif' => $periode->getScoreCollectif(),
            'configured'     => (
                null !== $periode->getScoreEquipe() && '' !== trim((string) $periode->getScoreEquipe())
                && null !== $periode->getScoreCollectif() && '' !== trim((string) $periode->getScoreCollectif())
            ),
            'periode' => [
                'id'       => $periode->getId(),
                'mois'     => $periode->getMois(),
                'annee'    => $periode->getAnnee(),
                'quinzaine' => $periode->getQuinzaine(),
                'label'    => sprintf(
                    '%02d/%d%s',
                    $periode->getMois(),
                    $periode->getAnnee(),
                    $periode->getQuinzaine() ? ' (Q' . $periode->getQuinzaine() . ')' : ''
                )
            ]
        ]);
    }
    #[Route('/historique/{type}', name: 'gestionnaire_historique')]
    public function historique(string $type, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Récupérer les services gérés par ce gestionnaire
        $services = $this->GestionnaireService->getManagedServicesByUser($user);

        // Récupérer l'historique des primes
        // Logique à implémenter selon vos besoins

        return $this->render('gestionnaire/historique.html.twig', [
            'type' => $type,
            'services' => $services,
        ]);
    }

    #[Route('/conges/validation', name: 'gestionnaire_conges_validation')]
    public function congesValidation(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Récupérer les demandes de congés en attente
        // Logique à implémenter selon vos entités

        return $this->render('gestionnaire/conges_validation.html.twig', [
            // Variables nécessaires
        ]);
    }

    #[Route('/conges/planning', name: 'gestionnaire_conges_planning')]
    public function congesPlanning(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Récupérer le planning des congés
        // Logique à implémenter selon vos besoins

        return $this->render('gestionnaire/conges_planning.html.twig', [
            // Variables nécessaires
        ]);
    }

    #[Route('/rapports', name: 'gestionnaire_rapports')]
    public function rapports(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Générer les rapports et statistiques
        // Logique à implémenter selon vos besoins

        return $this->render('gestionnaire/rapports.html.twig', [
            // Variables nécessaires
        ]);
    }

    #[Route('/aide', name: 'gestionnaire_aide')]
    public function aide(): Response
    {
        return $this->render('gestionnaire/aide.html.twig');
    }
}

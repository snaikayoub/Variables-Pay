<?php

// src/Controller/RhController.php

namespace App\Controller;

use App\Repository\DivisionRepository;
use App\Repository\PeriodePaieRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\PrimeFonctionRepository;
use App\Repository\PrimePerformanceRepository;
use App\Repository\VoyageDeplacementRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\ExportService;
use App\Entity\PeriodePaie;
use App\Entity\VoyageDeplacement;

#[Route('/rh')]
class RhController extends AbstractController
{
    #[Route('/', name: 'rh_dashboard')]
    public function dashboard(
        DivisionRepository $divisionRepo,
        PrimePerformanceRepository $primeRepo,
        PrimeFonctionRepository $primeFonctionRepo,
        VoyageDeplacementRepository $voyageRepo,
        PeriodePaieRepository $periodeRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $divisions = $divisionRepo->findAll();

        // Statistiques RH
        $globalStats = [
            'draft' => $primeRepo->countByStatus('draft'),
            'submitted' => $primeRepo->countByStatus('submitted'),
            'service_validated' => $primeRepo->countByStatus('service_validated'),
            'division_validated' => $primeRepo->countByStatus('division_validated'),
        ];

        $globalFonctionStats = [
            'draft' => $primeFonctionRepo->countByStatus('draft'),
            'submitted' => $primeFonctionRepo->countByStatus('submitted'),
            'service_validated' => $primeFonctionRepo->countByStatus('service_validated'),
            'division_validated' => $primeFonctionRepo->countByStatus('division_validated'),
        ];

        $globalVoyageStats = [
            'draft' => $voyageRepo->countByStatus(VoyageDeplacement::STATUS_DRAFT),
            'submitted' => $voyageRepo->countByStatus(VoyageDeplacement::STATUS_SUBMITTED),
            'service_validated' => $voyageRepo->countByStatus(VoyageDeplacement::STATUS_SERVICE_VALIDATED),
            'validated' => $voyageRepo->countByStatus(VoyageDeplacement::STATUS_VALIDATED),
            'rejected' => $voyageRepo->countByStatus(VoyageDeplacement::STATUS_REJECTED),
        ];

        $periodeMensuelleOuverte = $periodeRepo->findOneBy(
            ['typePaie' => 'mensuelle', 'statut' => PeriodePaie::STATUT_OUVERT],
            ['annee' => 'DESC', 'mois' => 'DESC', 'quinzaine' => 'DESC']
        );
        $periodeQuinzaineOuverte = $periodeRepo->findOneBy(
            ['typePaie' => 'quinzaine', 'statut' => PeriodePaie::STATUT_OUVERT],
            ['annee' => 'DESC', 'mois' => 'DESC', 'quinzaine' => 'DESC']
        );

        $servicesTotal = 0;
        foreach ($divisions as $d) {
            $servicesTotal += count($d->getServices());
        }

        return $this->render('rh/dashboard.html.twig', [
            'divisions' => $divisions,
            'stats' => $globalStats,
            'fonctionStats' => $globalFonctionStats,
            'voyageStats' => $globalVoyageStats,
            'servicesTotal' => $servicesTotal,
            'periodeMensuelleOuverte' => $periodeMensuelleOuverte,
            'periodeQuinzaineOuverte' => $periodeQuinzaineOuverte,
        ]);
    }

    #[Route('/etat-validations', name: 'rh_etat_validations')]
    public function etatValidations(
        Request $request,
        DivisionRepository $divisionRepo,
        PrimePerformanceRepository $ppRepo,
        PrimeFonctionRepository $pfRepo,
        VoyageDeplacementRepository $voyageRepo
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');
        $divisions = $divisionRepo->findAll();

        $type = (string) $request->query->get('type', 'all');
        $typeFilter = null;
        if (in_array($type, ['mensuelle', 'quinzaine'], true)) {
            $typeFilter = $type;
        } else {
            $type = 'all';
        }

        // serviceId => count
        $ppSubmitted = $ppRepo->countByServiceGrouped('submitted', $typeFilter);
        $ppServiceValidated = $ppRepo->countByServiceGrouped('service_validated', $typeFilter);
        $ppDivisionValidated = $ppRepo->countByServiceGrouped('division_validated', $typeFilter);

        $pfSubmitted = $pfRepo->countByServiceGrouped('submitted', $typeFilter);
        $pfServiceValidated = $pfRepo->countByServiceGrouped('service_validated', $typeFilter);
        $pfDivisionValidated = $pfRepo->countByServiceGrouped('division_validated', $typeFilter);

        $vSubmitted = $voyageRepo->countByServiceGrouped(VoyageDeplacement::STATUS_SUBMITTED, $typeFilter);
        $vServiceValidated = $voyageRepo->countByServiceGrouped(VoyageDeplacement::STATUS_SERVICE_VALIDATED, $typeFilter);
        $vValidated = $voyageRepo->countByServiceGrouped(VoyageDeplacement::STATUS_VALIDATED, $typeFilter);
        $vRejected = $voyageRepo->countByServiceGrouped(VoyageDeplacement::STATUS_REJECTED, $typeFilter);

        $etat = [];
        foreach ($divisions as $div) {
            $divData = [
                'division' => $div,
                'totals' => [
                    'performance' => ['submitted' => 0, 'service_validated' => 0, 'division_validated' => 0],
                    'fonction' => ['submitted' => 0, 'service_validated' => 0, 'division_validated' => 0],
                    'voyages' => ['submitted' => 0, 'service_validated' => 0, 'validated' => 0, 'rejected' => 0],
                ],
                'services' => [],
            ];

            foreach ($div->getServices() as $s) {
                $sid = $s->getId();
                $row = [
                    'service' => $s,
                    'performance' => [
                        'submitted' => $ppSubmitted[$sid] ?? 0,
                        'service_validated' => $ppServiceValidated[$sid] ?? 0,
                        'division_validated' => $ppDivisionValidated[$sid] ?? 0,
                    ],
                    'fonction' => [
                        'submitted' => $pfSubmitted[$sid] ?? 0,
                        'service_validated' => $pfServiceValidated[$sid] ?? 0,
                        'division_validated' => $pfDivisionValidated[$sid] ?? 0,
                    ],
                    'voyages' => [
                        'submitted' => $vSubmitted[$sid] ?? 0,
                        'service_validated' => $vServiceValidated[$sid] ?? 0,
                        'validated' => $vValidated[$sid] ?? 0,
                        'rejected' => $vRejected[$sid] ?? 0,
                    ],
                ];

                $divData['totals']['performance']['submitted'] += $row['performance']['submitted'];
                $divData['totals']['performance']['service_validated'] += $row['performance']['service_validated'];
                $divData['totals']['performance']['division_validated'] += $row['performance']['division_validated'];

                $divData['totals']['fonction']['submitted'] += $row['fonction']['submitted'];
                $divData['totals']['fonction']['service_validated'] += $row['fonction']['service_validated'];
                $divData['totals']['fonction']['division_validated'] += $row['fonction']['division_validated'];

                $divData['totals']['voyages']['submitted'] += $row['voyages']['submitted'];
                $divData['totals']['voyages']['service_validated'] += $row['voyages']['service_validated'];
                $divData['totals']['voyages']['validated'] += $row['voyages']['validated'];
                $divData['totals']['voyages']['rejected'] += $row['voyages']['rejected'];

                $divData['services'][] = $row;
            }

            $etat[] = $divData;
        }

        return $this->render('rh/etat_validations.html.twig', [
            'type' => $type,
            'etat' => $etat,
        ]);
    }

    #[Route('/etat-periode/{type}', name: 'rh_etat_periode')]
    public function etatParPeriode(string $type, PeriodePaieRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');
        $periodes = $repo->findBy(['typePaie' => $type], ['annee' => 'DESC', 'mois' => 'DESC']);
        return $this->render('rh/etat_periode.html.twig', [
            'type' => $type,
            'periodes' => $periodes,
        ]);
    }
    #[Route('/tableau-synthese', name: 'rh_tableau_synthese')]
    public function tableauSynthese(
        Request $request,
        PrimePerformanceRepository $primeRepo,
        PeriodePaieRepository $periodeRepo,
        DivisionRepository $divisionRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');
        $periodes = $periodeRepo->findBy([], ['annee' => 'DESC', 'mois' => 'DESC']);
        $divisions = $divisionRepo->findAll();

        $primes = $this->getFilteredPrimes($request, $primeRepo);

        return $this->render('rh/tableau_synthese.html.twig', [
            'primes'     => $primes,
            'periodes'   => $periodes,
            'divisions'  => $divisions,
            'periodeId'  => $request->query->get('periode'),
            'statut'     => $request->query->get('statut'),
            'divisionId' => $request->query->get('division'),
        ]);
    }

    #[Route('/tableau-synthese/export-excel', name: 'rh_tableau_synthese_export_excel')]
    public function exportSyntheseExcel(
        Request $request,
        PrimePerformanceRepository $primeRepo,
        ExportService $exportService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');
        $primes = $this->getFilteredPrimes($request, $primeRepo);

        $data = [];
        foreach ($primes as $pp) {
            $sit = $pp->getEmployee()->getEmployeeSituations()->first() ?: null;
            $data[] = [
                $pp->getEmployee()->getMatricule(),
                $pp->getEmployee()->getFullName(),
                (string)$pp->getPeriodePaie(),
                $sit ? ($sit->getService()?->getDivision()?->getNom() ?? '') : '',
                $sit ? ($sit->getService()?->getNom() ?? '') : '',
                $pp->getStatus(),
                $pp->getMontantFormate()
            ];
        }
        $headers = ['Matricule', 'Nom', 'Période', 'Division', 'Service', 'Statut', 'Montant'];
        $filename = 'synthese_rh_' . date('Ymd_His') . '.xlsx';
        
        return $exportService->exportXlsx($data, $headers, $filename);
    }

    #[Route('/tableau-synthese/export-pdf', name: 'rh_tableau_synthese_export_pdf')]
    public function exportSynthesePdf(
        Request $request,
        PrimePerformanceRepository $primeRepo,
        ExportService $exportService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');
        $primes = $this->getFilteredPrimes($request, $primeRepo);
        $html = $this->renderView('rh/partials/export_pdf.html.twig', ['primes' => $primes]);
        $filename = 'synthese_rh_' . date('Ymd_His') . '.pdf';
        
        return $exportService->exportPdf($html, $filename);
    }

    #[Route('/tableau-synthese-fonction', name: 'rh_tableau_synthese_fonction')]
    public function tableauSyntheseFonction(
        Request $request,
        PrimeFonctionRepository $primeRepo,
        PeriodePaieRepository $periodeRepo,
        DivisionRepository $divisionRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');
        $periodes = $periodeRepo->findBy([], ['annee' => 'DESC', 'mois' => 'DESC']);
        $divisions = $divisionRepo->findAll();

        $primes = $this->getFilteredPrimeFonctions($request, $primeRepo);

        return $this->render('rh/tableau_synthese_fonction.html.twig', [
            'primes' => $primes,
            'periodes' => $periodes,
            'divisions' => $divisions,
            'periodeId' => $request->query->get('periode'),
            'statut' => $request->query->get('statut'),
            'divisionId' => $request->query->get('division'),
        ]);
    }

    #[Route('/tableau-synthese-fonction/export-excel', name: 'rh_tableau_synthese_fonction_export_excel')]
    public function exportSyntheseFonctionExcel(
        Request $request,
        PrimeFonctionRepository $primeRepo,
        ExportService $exportService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');
        $primes = $this->getFilteredPrimeFonctions($request, $primeRepo);

        $data = [];
        foreach ($primes as $pf) {
            $sit = $pf->getEmployee()->getEmployeeSituations()->first() ?: null;
            $data[] = [
                $pf->getEmployee()->getMatricule(),
                $pf->getEmployee()->getFullName(),
                (string) $pf->getPeriodePaie(),
                $sit ? ($sit->getService()?->getDivision()?->getNom() ?? '') : '',
                $sit ? ($sit->getService()?->getNom() ?? '') : '',
                $pf->getStatus(),
                $pf->getTauxMonetaireFonction(),
                $pf->getNombreJours(),
                $pf->getNoteHierarchique(),
                $pf->getMontantFormate(),
            ];
        }

        $headers = ['Matricule', 'Nom', 'Période', 'Division', 'Service', 'Statut', 'Taux', 'Jours', 'Note', 'Montant'];
        $filename = 'synthese_rh_prime_fonction_' . date('Ymd_His') . '.xlsx';

        return $exportService->exportXlsx($data, $headers, $filename);
    }

    #[Route('/tableau-synthese-fonction/export-pdf', name: 'rh_tableau_synthese_fonction_export_pdf')]
    public function exportSyntheseFonctionPdf(
        Request $request,
        PrimeFonctionRepository $primeRepo,
        ExportService $exportService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');
        $primes = $this->getFilteredPrimeFonctions($request, $primeRepo);
        $html = $this->renderView('rh/partials/export_pdf_fonction.html.twig', ['primes' => $primes]);
        $filename = 'synthese_rh_prime_fonction_' . date('Ymd_His') . '.pdf';

        return $exportService->exportPdf($html, $filename);
    }

    #[Route('/tableau-synthese-voyages', name: 'rh_tableau_synthese_voyages')]
    public function tableauSyntheseVoyages(
        Request $request,
        VoyageDeplacementRepository $voyageRepo,
        PeriodePaieRepository $periodeRepo,
        DivisionRepository $divisionRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $periodes = $periodeRepo->findBy([], ['annee' => 'DESC', 'mois' => 'DESC']);
        $divisions = $divisionRepo->findAll();

        $voyages = $this->getFilteredVoyages($request, $voyageRepo);

        return $this->render('rh/tableau_synthese_voyages.html.twig', [
            'voyages' => $voyages,
            'periodes' => $periodes,
            'divisions' => $divisions,
            'periodeId' => $request->query->get('periode'),
            'statut' => $request->query->get('statut'),
            'divisionId' => $request->query->get('division'),
        ]);
    }

    #[Route('/tableau-synthese-voyages/export-excel', name: 'rh_tableau_synthese_voyages_export_excel')]
    public function exportSyntheseVoyagesExcel(
        Request $request,
        VoyageDeplacementRepository $voyageRepo,
        ExportService $exportService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $voyages = $this->getFilteredVoyages($request, $voyageRepo);

        $data = [];
        foreach ($voyages as $v) {
            $sit = $v->getEmployee()?->getEmployeeSituations()->first() ?: null;
            $data[] = [
                $v->getEmployee()?->getMatricule() ?? '',
                $v->getEmployee()?->getFullName() ?? '',
                (string) $v->getPeriodePaie(),
                $sit ? ($sit->getService()?->getDivision()?->getNom() ?? '') : '',
                $sit ? ($sit->getService()?->getNom() ?? '') : '',
                $v->getStatus(),
                $v->getModeTransport(),
                $v->getDateHeureDepart()?->format('d/m/Y H:i') ?? '',
                $v->getDateHeureRetour()?->format('d/m/Y H:i') ?? '',
                $v->getDistanceKm(),
            ];
        }

        $headers = ['Matricule', 'Nom', 'Periode', 'Division', 'Service', 'Statut', 'Transport', 'Depart', 'Retour', 'Distance (Km)'];
        $filename = 'synthese_rh_deplacements_' . date('Ymd_His') . '.xlsx';

        return $exportService->exportXlsx($data, $headers, $filename);
    }

    #[Route('/tableau-synthese-voyages/export-pdf', name: 'rh_tableau_synthese_voyages_export_pdf')]
    public function exportSyntheseVoyagesPdf(
        Request $request,
        VoyageDeplacementRepository $voyageRepo,
        ExportService $exportService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $voyages = $this->getFilteredVoyages($request, $voyageRepo);
        $html = $this->renderView('rh/partials/export_pdf_voyages.html.twig', ['voyages' => $voyages]);
        $filename = 'synthese_rh_deplacements_' . date('Ymd_His') . '.pdf';

        return $exportService->exportPdf($html, $filename);
    }

    private function getFilteredPrimes(Request $request, PrimePerformanceRepository $primeRepo): array
    {
        $periodeId = $request->query->get('periode');
        $statut = $request->query->get('statut');
        $divisionId = $request->query->get('division');

        $criteria = [];
        if ($periodeId) {
            $criteria['periodePaie'] = $periodeId;
        }
        if ($statut) {
            $criteria['status'] = $statut;
        }

        return $primeRepo->findWithDivision($criteria, $divisionId);
    }

    private function getFilteredPrimeFonctions(Request $request, PrimeFonctionRepository $primeRepo): array
    {
        $periodeId = $request->query->get('periode');
        $statut = $request->query->get('statut');
        $divisionId = $request->query->get('division');

        $criteria = [];
        if ($periodeId) {
            $criteria['periodePaie'] = $periodeId;
        }
        if ($statut) {
            $criteria['status'] = $statut;
        }

        return $primeRepo->findWithDivision($criteria, $divisionId);
    }

    private function getFilteredVoyages(Request $request, VoyageDeplacementRepository $voyageRepo): array
    {
        $periodeId = $request->query->get('periode');
        $statut = $request->query->get('statut');
        $divisionId = $request->query->get('division');

        $criteria = [];
        if ($periodeId) {
            $criteria['periodePaie'] = $periodeId;
        }
        if ($statut) {
            $criteria['status'] = $statut;
        }

        return $voyageRepo->findWithDivision($criteria, $divisionId);
    }
}

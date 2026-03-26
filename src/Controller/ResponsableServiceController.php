<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\VoyageDeplacement;
use App\Entity\PrimeFonction;
use App\Entity\PrimePerformance;
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

#[Route('/responsable/service', name: 'responsable_service_')]
class ResponsableServiceController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(PrimePerformanceRepository $repo, PrimeFonctionRepository $primeFonctionRepo, VoyageDeplacementRepository $voyageRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $countMensuelle = $repo->countSubmittedByType($user, 'mensuelle');
        $countQuinzaine = $repo->countSubmittedByType($user, 'quinzaine');

        $countFonctionMensuelle = $primeFonctionRepo->countSubmittedByType($user, 'mensuelle');
        $countFonctionQuinzaine = $primeFonctionRepo->countSubmittedByType($user, 'quinzaine');

        $countVoyageMensuelle = $voyageRepo->countByServiceValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_SUBMITTED, 'mensuelle');
        $countVoyageQuinzaine = $voyageRepo->countByServiceValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_SUBMITTED, 'quinzaine');

        return $this->render('responsable/service/dashboard.html.twig', [
            'countMensuelle' => $countMensuelle,
            'countQuinzaine' => $countQuinzaine,
            'countFonctionMensuelle' => $countFonctionMensuelle,
            'countFonctionQuinzaine' => $countFonctionQuinzaine,
            'countVoyageMensuelle' => $countVoyageMensuelle,
            'countVoyageQuinzaine' => $countVoyageQuinzaine,
        ]);
    }

    #[Route('/validation/fonction/{type}', name: 'validation_fonction', methods: ['GET'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function primeFonction(
        string $type,
        PrimeFonctionRepository $repo,
        PeriodePaieRepository $periodeRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        $periodeCourante = $periodeRepo->findOneBy([
            'typePaie' => $type,
            'statut' => 'Ouverte',
        ], ['annee' => 'DESC', 'mois' => 'DESC']);

        $user = $this->getUser();

        $submitted = $repo->findByServiceAndStatusAndType($user, PrimeFonction::STATUS_SUBMITTED, $type);
        $drafts = $repo->findByServiceAndStatusAndType($user, PrimeFonction::STATUS_DRAFT, $type);
        $validated = $repo->findByServiceAndStatusAndType($user, PrimeFonction::STATUS_SERVICE_VALIDATED, $type);

        return $this->render('responsable/service/prime_fonction.html.twig', [
            'type' => $type,
            'primes' => $submitted,
            'drafts' => $drafts,
            'validated' => $validated,
            'periodeCourante' => $periodeCourante,
            'user' => $user,
        ]);
    }

    #[Route('/validation/voyages/{type}', name: 'validation_voyages', methods: ['GET'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function voyages(
        string $type,
        VoyageDeplacementRepository $voyageRepo,
        PeriodePaieRepository $periodeRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array($type, ['mensuelle', 'quinzaine'], true)) {
            throw $this->createNotFoundException();
        }

        $periodeCourante = $periodeRepo->findOneBy([
            'typePaie' => $type,
            'statut' => 'Ouverte',
        ], ['annee' => 'DESC', 'mois' => 'DESC']);

        $submitted = $voyageRepo->findByServiceValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_SUBMITTED, $type);
        $validated = $voyageRepo->findByServiceValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_SERVICE_VALIDATED, $type);
        $rejected = $voyageRepo->findByServiceValidatorAndStatusAndType($user, VoyageDeplacement::STATUS_REJECTED, $type);

        return $this->render('responsable/service/voyages.html.twig', [
            'type' => $type,
            'periodeCourante' => $periodeCourante,
            'submitted' => $submitted,
            'validated' => $validated,
            'rejected' => $rejected,
        ]);
    }

    #[Route('/validation/voyages/{type}/valider/{id}', name: 'voyages_valider', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerVoyage(
        string $type,
        VoyageDeplacement $voyage,
        Request $request,
        WorkflowInterface $voyage_deplacement,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('responsable_voyage_valider_'.$voyage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_voyages', ['type' => $type]);
        }

        $service = $voyage->getEmployee()?->getCurrentService();
        if (null === $service || $service->getValidateurService() !== $user) {
            throw $this->createAccessDeniedException('Voyage non autorise');
        }

        if ($voyage_deplacement->can($voyage, 'service_validate')) {
            $voyage_deplacement->apply($voyage, 'service_validate');
            $em->flush();
            $this->addFlash('success', 'Deplacement valide par le service.');
        } else {
            $this->addFlash('error', 'Action non autorisee.');
        }

        return $this->redirectToRoute('responsable_service_validation_voyages', ['type' => $type]);
    }

    #[Route('/validation/voyages/{type}/rejeter/{id}', name: 'voyages_rejeter', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function rejeterVoyage(
        string $type,
        VoyageDeplacement $voyage,
        Request $request,
        WorkflowInterface $voyage_deplacement,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('responsable_voyage_rejeter_'.$voyage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_voyages', ['type' => $type]);
        }

        $service = $voyage->getEmployee()?->getCurrentService();
        if (null === $service || $service->getValidateurService() !== $user) {
            throw $this->createAccessDeniedException('Voyage non autorise');
        }

        if ($voyage_deplacement->can($voyage, 'reject')) {
            $voyage_deplacement->apply($voyage, 'reject');
            $em->flush();
            $this->addFlash('warning', 'Deplacement rejete.');
        } else {
            $this->addFlash('error', 'Action non autorisee.');
        }

        return $this->redirectToRoute('responsable_service_validation_voyages', ['type' => $type]);
    }

    #[Route('/validation/voyages/{type}/retour/{id}', name: 'voyages_retour', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourVoyage(
        string $type,
        VoyageDeplacement $voyage,
        Request $request,
        WorkflowInterface $voyage_deplacement,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('responsable_voyage_retour_'.$voyage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_voyages', ['type' => $type]);
        }

        $service = $voyage->getEmployee()?->getCurrentService();
        if (null === $service || $service->getValidateurService() !== $user) {
            throw $this->createAccessDeniedException('Voyage non autorise');
        }

        if ($voyage_deplacement->can($voyage, 'retour_gestionnaire')) {
            $voyage_deplacement->apply($voyage, 'retour_gestionnaire');
            $em->flush();
            $this->addFlash('success', 'Deplacement retourne au gestionnaire.');
        } else {
            $this->addFlash('error', 'Action non autorisee.');
        }

        return $this->redirectToRoute('responsable_service_validation_voyages', ['type' => $type]);
    }

    #[Route('/validation/performance/{type}', name: 'validation_performance', methods: ['GET'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function primePerformance(
        string $type,
        PrimePerformanceRepository $repo,
        PeriodePaieRepository $periodeRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        $periodeCourante = $periodeRepo->findOneBy([
            'typePaie' => $type,
            'statut' => 'Ouverte',
        ], ['annee' => 'DESC', 'mois' => 'DESC']);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $submitted = $repo->findByServiceAndStatusAndType($user, 'submitted', $type);
        $draft = $repo->findByServiceAndStatusAndType($user, 'draft', $type);
        $validated = $repo->findByServiceAndStatusAndType($user, 'service_validated', $type);

        return $this->render('responsable/service/prime_performance.html.twig', [
            'type' => $type,
            'primes' => $submitted,
            'drafts' => $draft,
            'validated' => $validated,
            'periodeCourante' => $periodeCourante,
            'user' => $user,
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
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        // Correction : utilisation de all() au lieu de get()
        if (!$this->isCsrfTokenValid('responsable_valider_batch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
        }

        $ids = $request->request->all('selected') ?: [];
        
        if (empty($ids)) {
            $this->addFlash('warning', 'Aucun élément sélectionné.');
            return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $primes = $repo->findByIdsForServiceValidator($user, $ids);
        $count = 0;

        foreach ($primes as $pp) {
            if ($prime_performance->can($pp, 'service_validate')) {
                $prime_performance->apply($pp, 'service_validate');
                $count++;
            }
        }

        $em->flush();
        $this->addFlash('success', $count . ' ligne(s) validée(s)');

        // Redirection simplifiée
        return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
    }

    #[Route('/validation/performance/{type}/valider/{id}', name: 'performance_valider_ligne', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerLigne(
        string $type,
        PrimePerformance $pp,
        Request $request,
        WorkflowInterface $prime_performance,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        if (!$this->isCsrfTokenValid('responsable_valider_ligne', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $service = $pp->getEmployee()?->getCurrentService();
        if (null === $service || $service->getValidateurService() !== $user) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        if ($prime_performance->can($pp, 'service_validate')) {
            $prime_performance->apply($pp, 'service_validate');
            $em->flush();
            $this->addFlash('success', 'Ligne validée.');
        } else {
            $this->addFlash('error', 'Action non autorisée.');
        }

        // Redirection simplifiée
        return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
    }

    #[Route('/validation/performance/{type}/retour', name: 'performance_retour_batch', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourBatch(
        string $type,
        Request $request,
        PrimePerformanceRepository $repo,
        EntityManagerInterface $em,
        WorkflowInterface $prime_performance
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        if (!$this->isCsrfTokenValid('responsable_retour_batch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
        }

        // Correction : utilisation de all() au lieu de get()
        $ids = $request->request->all('selected') ?: [];
        
        if (empty($ids)) {
            $this->addFlash('warning', 'Aucun élément sélectionné.');
            return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $primes = $repo->findByIdsForServiceValidator($user, $ids);
        $count = 0;

        foreach ($primes as $pp) {
            if ($prime_performance->can($pp, 'retour_gestionnaire')) {
                $prime_performance->apply($pp, 'retour_gestionnaire');
                $count++;
            }
        }

        $em->flush();
        $this->addFlash('success', $count . ' prime(s) retournée(s) au gestionnaire.');

        // Redirection simplifiée
        return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
    }

    #[Route('/validation/performance/{type}/retour/{id}', name: 'performance_retour_ligne', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourLigne(
        string $type,
        PrimePerformance $pp,
        Request $request,
        WorkflowInterface $prime_performance,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        if (!$this->isCsrfTokenValid('responsable_retour_ligne', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $service = $pp->getEmployee()?->getCurrentService();
        if (null === $service || $service->getValidateurService() !== $user) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        if ($prime_performance->can($pp, 'retour_gestionnaire')) {
            $prime_performance->apply($pp, 'retour_gestionnaire');
            $em->flush();
            $this->addFlash('success', 'Prime retournée au gestionnaire.');
        } else {
            $this->addFlash('error', 'Impossible de retourner cette prime.');
        }

        // Redirection simplifiée
        return $this->redirectToRoute('responsable_service_validation_performance', ['type' => $type]);
    }

    #[Route('/validation/fonction/{type}/valider', name: 'fonction_valider_batch', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerFonctionBatch(
        string $type,
        Request $request,
        PrimeFonctionRepository $repo,
        EntityManagerInterface $em,
        WorkflowInterface $prime_fonction
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        if (!$this->isCsrfTokenValid('responsable_fonction_valider_batch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
        }

        $ids = $request->request->all('selected') ?: [];

        if (empty($ids)) {
            $this->addFlash('warning', 'Aucun element selectionne.');
            return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $primes = $repo->findByIdsForServiceValidator($user, $ids);
        $count = 0;

        foreach ($primes as $pf) {
            if ($prime_fonction->can($pf, 'service_validate')) {
                $prime_fonction->apply($pf, 'service_validate');
                $count++;
            }
        }

        $em->flush();
        $this->addFlash('success', $count . ' ligne(s) validee(s)');

        return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
    }

    #[Route('/validation/fonction/{type}/valider/{id}', name: 'fonction_valider_ligne', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function validerFonctionLigne(
        string $type,
        PrimeFonction $pf,
        Request $request,
        WorkflowInterface $prime_fonction,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        if (!$this->isCsrfTokenValid('responsable_fonction_valider_ligne', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $service = $pf->getEmployee()?->getCurrentService();
        if (null === $service || $service->getValidateurService() !== $user) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        if ($prime_fonction->can($pf, 'service_validate')) {
            $prime_fonction->apply($pf, 'service_validate');
            $em->flush();
            $this->addFlash('success', 'Ligne validee.');
        } else {
            $this->addFlash('error', 'Action non autorisee.');
        }

        return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
    }

    #[Route('/validation/fonction/{type}/retour', name: 'fonction_retour_batch', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourFonctionBatch(
        string $type,
        Request $request,
        PrimeFonctionRepository $repo,
        EntityManagerInterface $em,
        WorkflowInterface $prime_fonction
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        if (!$this->isCsrfTokenValid('responsable_fonction_retour_batch', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
        }

        $ids = $request->request->all('selected') ?: [];

        if (empty($ids)) {
            $this->addFlash('warning', 'Aucun element selectionne.');
            return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $primes = $repo->findByIdsForServiceValidator($user, $ids);
        $count = 0;

        foreach ($primes as $pf) {
            if ($prime_fonction->can($pf, 'retour_gestionnaire')) {
                $prime_fonction->apply($pf, 'retour_gestionnaire');
                $count++;
            }
        }

        $em->flush();
        $this->addFlash('success', $count . ' prime(s) retournee(s) au gestionnaire.');

        return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
    }

    #[Route('/validation/fonction/{type}/retour/{id}', name: 'fonction_retour_ligne', methods: ['POST'], requirements: ['type' => 'mensuelle|quinzaine'])]
    public function retourFonctionLigne(
        string $type,
        PrimeFonction $pf,
        Request $request,
        WorkflowInterface $prime_fonction,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_SERVICE');

        if (!$this->isCsrfTokenValid('responsable_fonction_retour_ligne', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $service = $pf->getEmployee()?->getCurrentService();
        if (null === $service || $service->getValidateurService() !== $user) {
            throw $this->createAccessDeniedException('Prime non autorisee');
        }

        if ($prime_fonction->can($pf, 'retour_gestionnaire')) {
            $prime_fonction->apply($pf, 'retour_gestionnaire');
            $em->flush();
            $this->addFlash('success', 'Prime retournee au gestionnaire.');
        } else {
            $this->addFlash('error', 'Impossible de retourner cette prime.');
        }

        return $this->redirectToRoute('responsable_service_validation_fonction', ['type' => $type]);
    }
}

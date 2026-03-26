<?php

namespace App\Controller\Api;

use App\Entity\PrimePerformance;
use App\Entity\User;
use App\Repository\PrimePerformanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/api/responsable/service/prime-performance')]
#[IsGranted('ROLE_RESPONSABLE_SERVICE')]
final class ResponsableServicePrimePerformanceController extends AbstractController
{
    #[Route('', name: 'api_responsable_service_prime_performance_list', methods: ['GET'])]
    public function list(Request $request, PrimePerformanceRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $typePaie = $request->query->getString('typePaie');
        if (!in_array($typePaie, ['mensuelle', 'quinzaine'], true)) {
            return new JsonResponse(['message' => 'typePaie is required (mensuelle|quinzaine)'], Response::HTTP_BAD_REQUEST);
        }

        $status = $request->query->getString('status', PrimePerformance::STATUS_SUBMITTED);
        if (!in_array($status, [
            PrimePerformance::STATUS_DRAFT,
            PrimePerformance::STATUS_SUBMITTED,
            PrimePerformance::STATUS_SERVICE_VALIDATED,
            PrimePerformance::STATUS_DIVISION_VALIDATED,
        ], true)) {
            return new JsonResponse(['message' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
        }

        $items = $repo->findByServiceAndStatusAndType($user, $status, $typePaie);

        return $this->json([
            'items' => array_map([$this, 'serializePrimePerformance'], $items),
        ]);
    }

    #[Route('/batch/validate', name: 'api_responsable_service_prime_performance_batch_validate', methods: ['POST'])]
    public function batchValidate(
        Request $request,
        PrimePerformanceRepository $repo,
        WorkflowInterface $prime_performance,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $ids = BatchRequest::idsFromPayload($payload);
        if ([] === $ids) {
            return new JsonResponse(['message' => 'ids[] is required'], Response::HTTP_BAD_REQUEST);
        }

        $pps = $repo->findByIdsForServiceValidator($user, $ids);
        $ppsById = [];
        foreach ($pps as $pp) {
            $ppsById[$pp->getId()] = $pp;
        }

        $validated = [];
        $skipped = [];

        foreach ($ids as $id) {
            $pp = $ppsById[$id] ?? null;
            if (!$pp) {
                $skipped[] = ['id' => $id, 'reason' => 'Not found or not in your scope'];
                continue;
            }

            if (!$prime_performance->can($pp, 'service_validate')) {
                $skipped[] = ['id' => $id, 'reason' => 'Transition not allowed'];
                continue;
            }

            $prime_performance->apply($pp, 'service_validate');
            $validated[] = $id;
        }

        $em->flush();

        return $this->json([
            'processed' => count($ids),
            'validated' => $validated,
            'skipped' => $skipped,
        ]);
    }

    #[Route('/batch/retour', name: 'api_responsable_service_prime_performance_batch_retour', methods: ['POST'])]
    public function batchRetourGestionnaire(
        Request $request,
        PrimePerformanceRepository $repo,
        WorkflowInterface $prime_performance,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $ids = BatchRequest::idsFromPayload($payload);
        if ([] === $ids) {
            return new JsonResponse(['message' => 'ids[] is required'], Response::HTTP_BAD_REQUEST);
        }

        $pps = $repo->findByIdsForServiceValidator($user, $ids);
        $ppsById = [];
        foreach ($pps as $pp) {
            $ppsById[$pp->getId()] = $pp;
        }

        $returned = [];
        $skipped = [];

        foreach ($ids as $id) {
            $pp = $ppsById[$id] ?? null;
            if (!$pp) {
                $skipped[] = ['id' => $id, 'reason' => 'Not found or not in your scope'];
                continue;
            }

            if (!$prime_performance->can($pp, 'retour_gestionnaire')) {
                $skipped[] = ['id' => $id, 'reason' => 'Transition not allowed'];
                continue;
            }

            $prime_performance->apply($pp, 'retour_gestionnaire');
            $returned[] = $id;
        }

        $em->flush();

        return $this->json([
            'processed' => count($ids),
            'returned' => $returned,
            'skipped' => $skipped,
        ]);
    }

    private function serializePrimePerformance(PrimePerformance $pp): array
    {
        $employee = $pp->getEmployee();
        $periode = $pp->getPeriodePaie();

        return [
            'id' => $pp->getId(),
            'status' => $pp->getStatus(),
            'employee' => $employee ? [
                'id' => $employee->getId(),
                'matricule' => $employee->getMatricule(),
                'fullName' => $employee->getFullName(),
            ] : null,
            'periode' => $periode ? [
                'id' => $periode->getId(),
                'typePaie' => $periode->getTypePaie(),
                'mois' => $periode->getMois(),
                'annee' => $periode->getAnnee(),
                'quinzaine' => $periode->getQuinzaine(),
                'label' => (string) $periode,
            ] : null,
            'tauxMonetaire' => $pp->getTauxMonetaire(),
            'joursPerf' => $pp->getJoursPerf(),
            'noteHierarchique' => $pp->getNoteHierarchique(),
            'scoreEquipe' => $pp->getScoreEquipe(),
            'scoreCollectif' => $pp->getScoreCollectif(),
            'montantPerf' => $pp->getMontantPerf(),
            'calculatedAt' => $pp->getCalculatedAt()?->format(DATE_ATOM),
        ];
    }
}

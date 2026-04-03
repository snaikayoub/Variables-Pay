<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\VoyageDeplacement;
use App\Repository\VoyageDeplacementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/api/responsable/service/voyages')]
#[IsGranted('ROLE_RESPONSABLE_SERVICE')]
final class ResponsableServiceVoyageController extends AbstractController
{
    #[Route('', name: 'api_responsable_service_voyages_list', methods: ['GET'])]
    public function list(Request $request, VoyageDeplacementRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $typePaie = $request->query->getString('typePaie');
        if (!in_array($typePaie, ['mensuelle', 'quinzaine'], true)) {
            return new JsonResponse(['message' => 'typePaie is required (mensuelle|quinzaine)'], Response::HTTP_BAD_REQUEST);
        }

        $status = $request->query->getString('status', VoyageDeplacement::STATUS_SUBMITTED);
        if (!in_array($status, [
            VoyageDeplacement::STATUS_SUBMITTED,
            VoyageDeplacement::STATUS_SERVICE_VALIDATED,
            VoyageDeplacement::STATUS_VALIDATED,
            VoyageDeplacement::STATUS_REJECTED,
        ], true)) {
            return new JsonResponse(['message' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
        }

        $items = $repo->findByServiceValidatorAndStatusAndType($user, $status, $typePaie);

        return $this->json([
            'items' => array_map([$this, 'serializeVoyage'], $items),
        ]);
    }

    #[Route('/batch/validate', name: 'api_responsable_service_voyages_batch_validate', methods: ['POST'])]
    public function batchValidate(
        Request $request,
        VoyageDeplacementRepository $repo,
        WorkflowInterface $voyage_deplacement,
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

        $voyages = $repo->findByIdsForServiceValidator($user, $ids);
        $byId = [];
        foreach ($voyages as $v) {
            $byId[$v->getId()] = $v;
        }

        $validated = [];
        $skipped = [];

        foreach ($ids as $id) {
            $v = $byId[$id] ?? null;
            if (!$v) {
                $skipped[] = ['id' => $id, 'reason' => 'Not found or not in your scope'];
                continue;
            }

            if (!$voyage_deplacement->can($v, 'service_validate')) {
                $skipped[] = ['id' => $id, 'reason' => 'Transition not allowed'];
                continue;
            }

            $voyage_deplacement->apply($v, 'service_validate');
            $v->setUpdatedAt(new \DateTimeImmutable());
            $validated[] = $id;
        }

        $em->flush();

        return $this->json([
            'processed' => count($ids),
            'validated' => $validated,
            'skipped' => $skipped,
        ]);
    }

    #[Route('/batch/reject', name: 'api_responsable_service_voyages_batch_reject', methods: ['POST'])]
    public function batchReject(
        Request $request,
        VoyageDeplacementRepository $repo,
        WorkflowInterface $voyage_deplacement,
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

        $voyages = $repo->findByIdsForServiceValidator($user, $ids);
        $byId = [];
        foreach ($voyages as $v) {
            $byId[$v->getId()] = $v;
        }

        $rejected = [];
        $skipped = [];

        foreach ($ids as $id) {
            $v = $byId[$id] ?? null;
            if (!$v) {
                $skipped[] = ['id' => $id, 'reason' => 'Not found or not in your scope'];
                continue;
            }

            if (!$voyage_deplacement->can($v, 'reject')) {
                $skipped[] = ['id' => $id, 'reason' => 'Transition not allowed'];
                continue;
            }

            $voyage_deplacement->apply($v, 'reject');
            $v->setUpdatedAt(new \DateTimeImmutable());
            $rejected[] = $id;
        }

        $em->flush();

        return $this->json([
            'processed' => count($ids),
            'rejected' => $rejected,
            'skipped' => $skipped,
        ]);
    }

    #[Route('/batch/retour', name: 'api_responsable_service_voyages_batch_retour', methods: ['POST'])]
    public function batchRetourGestionnaire(
        Request $request,
        VoyageDeplacementRepository $repo,
        WorkflowInterface $voyage_deplacement,
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

        $voyages = $repo->findByIdsForServiceValidator($user, $ids);
        $byId = [];
        foreach ($voyages as $v) {
            $byId[$v->getId()] = $v;
        }

        $returned = [];
        $skipped = [];

        foreach ($ids as $id) {
            $v = $byId[$id] ?? null;
            if (!$v) {
                $skipped[] = ['id' => $id, 'reason' => 'Not found or not in your scope'];
                continue;
            }

            if (!$voyage_deplacement->can($v, 'retour_gestionnaire')) {
                $skipped[] = ['id' => $id, 'reason' => 'Transition not allowed'];
                continue;
            }

            $voyage_deplacement->apply($v, 'retour_gestionnaire');
            $v->setUpdatedAt(new \DateTimeImmutable());
            $returned[] = $id;
        }

        $em->flush();

        return $this->json([
            'processed' => count($ids),
            'returned' => $returned,
            'skipped' => $skipped,
        ]);
    }

    private function serializeVoyage(VoyageDeplacement $voyage): array
    {
        $employee = $voyage->getEmployee();
        $periode = $voyage->getPeriodePaie();

        return [
            'id' => $voyage->getId(),
            'status' => $voyage->getStatus(),
            'typePaie' => $periode?->getTypePaie(),
            'periode' => $periode ? (string) $periode : null,
            'employee' => $employee ? [
                'id' => $employee->getId(),
                'matricule' => $employee->getMatricule(),
                'fullName' => $employee->getFullName(),
            ] : null,
            'typeVoyage' => $voyage->getTypeVoyage(),
            'motif' => $voyage->getMotif(),
            'modeTransport' => $voyage->getModeTransport(),
            'dateHeureDepart' => $voyage->getDateHeureDepart()?->format(DATE_ATOM),
            'dateHeureRetour' => $voyage->getDateHeureRetour()?->format(DATE_ATOM),
            'distanceKm' => $voyage->getDistanceKm(),
            'prisEnCharge' => $voyage->isPrisEnCharge(),
            'villeDepartAller' => $voyage->getVilleDepartAller(),
            'villeArriveeAller' => $voyage->getVilleArriveeAller(),
            'villeDepartRetour' => $voyage->getVilleDepartRetour(),
            'villeArriveeRetour' => $voyage->getVilleArriveeRetour(),
            'createdAt' => $voyage->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $voyage->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}

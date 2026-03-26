<?php

namespace App\Controller\Api;

use App\Entity\PrimeFonction;
use App\Entity\User;
use App\Repository\PrimeFonctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/api/responsable/service/prime-fonction')]
#[IsGranted('ROLE_RESPONSABLE_SERVICE')]
final class ResponsableServicePrimeFonctionController extends AbstractController
{
    #[Route('', name: 'api_responsable_service_prime_fonction_list', methods: ['GET'])]
    public function list(Request $request, PrimeFonctionRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $typePaie = $request->query->getString('typePaie');
        if (!in_array($typePaie, ['mensuelle', 'quinzaine'], true)) {
            return new JsonResponse(['message' => 'typePaie is required (mensuelle|quinzaine)'], Response::HTTP_BAD_REQUEST);
        }

        $status = $request->query->getString('status', PrimeFonction::STATUS_SUBMITTED);
        if (!in_array($status, [
            PrimeFonction::STATUS_DRAFT,
            PrimeFonction::STATUS_SUBMITTED,
            PrimeFonction::STATUS_SERVICE_VALIDATED,
            PrimeFonction::STATUS_DIVISION_VALIDATED,
        ], true)) {
            return new JsonResponse(['message' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
        }

        $items = $repo->findByServiceAndStatusAndType($user, $status, $typePaie);

        return $this->json([
            'items' => array_map([$this, 'serializePrimeFonction'], $items),
        ]);
    }

    #[Route('/batch/validate', name: 'api_responsable_service_prime_fonction_batch_validate', methods: ['POST'])]
    public function batchValidate(
        Request $request,
        PrimeFonctionRepository $repo,
        WorkflowInterface $prime_fonction,
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

        $pfs = $repo->findByIdsForServiceValidator($user, $ids);
        $pfsById = [];
        foreach ($pfs as $pf) {
            $pfsById[$pf->getId()] = $pf;
        }

        $validated = [];
        $skipped = [];

        foreach ($ids as $id) {
            $pf = $pfsById[$id] ?? null;
            if (!$pf) {
                $skipped[] = ['id' => $id, 'reason' => 'Not found or not in your scope'];
                continue;
            }

            if (!$prime_fonction->can($pf, 'service_validate')) {
                $skipped[] = ['id' => $id, 'reason' => 'Transition not allowed'];
                continue;
            }

            $prime_fonction->apply($pf, 'service_validate');
            $validated[] = $id;
        }

        $em->flush();

        return $this->json([
            'processed' => count($ids),
            'validated' => $validated,
            'skipped' => $skipped,
        ]);
    }

    #[Route('/batch/retour', name: 'api_responsable_service_prime_fonction_batch_retour', methods: ['POST'])]
    public function batchRetourGestionnaire(
        Request $request,
        PrimeFonctionRepository $repo,
        WorkflowInterface $prime_fonction,
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

        $pfs = $repo->findByIdsForServiceValidator($user, $ids);
        $pfsById = [];
        foreach ($pfs as $pf) {
            $pfsById[$pf->getId()] = $pf;
        }

        $returned = [];
        $skipped = [];

        foreach ($ids as $id) {
            $pf = $pfsById[$id] ?? null;
            if (!$pf) {
                $skipped[] = ['id' => $id, 'reason' => 'Not found or not in your scope'];
                continue;
            }

            if (!$prime_fonction->can($pf, 'retour_gestionnaire')) {
                $skipped[] = ['id' => $id, 'reason' => 'Transition not allowed'];
                continue;
            }

            $prime_fonction->apply($pf, 'retour_gestionnaire');
            $returned[] = $id;
        }

        $em->flush();

        return $this->json([
            'processed' => count($ids),
            'returned' => $returned,
            'skipped' => $skipped,
        ]);
    }

    private function serializePrimeFonction(PrimeFonction $pf): array
    {
        $employee = $pf->getEmployee();
        $periode = $pf->getPeriodePaie();

        return [
            'id' => $pf->getId(),
            'status' => $pf->getStatus(),
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
            'tauxMonetaireFonction' => $pf->getTauxMonetaireFonction(),
            'nombreJours' => $pf->getNombreJours(),
            'noteHierarchique' => $pf->getNoteHierarchique(),
            'montantFonction' => $pf->getMontantFonction(),
            'calculatedAt' => $pf->getCalculatedAt()?->format(DATE_ATOM),
        ];
    }
}

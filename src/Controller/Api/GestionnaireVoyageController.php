<?php

namespace App\Controller\Api;

use App\Entity\PeriodePaie;
use App\Entity\User;
use App\Entity\VoyageDeplacement;
use App\Repository\PeriodePaieRepository;
use App\Repository\VoyageDeplacementRepository;
use App\Service\GestionnaireService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/api/gestionnaire/voyages')]
#[IsGranted('ROLE_GESTIONNAIRE_SERVICE')]
final class GestionnaireVoyageController extends AbstractController
{
    #[Route('', name: 'api_gestionnaire_voyages_list', methods: ['GET'])]
    public function list(Request $request, VoyageDeplacementRepository $voyageRepo, GestionnaireService $gestionnaireService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $status = $request->query->getString('status');
        $typePaie = $request->query->getString('typePaie');

        if ('' !== $status && !in_array($status, [
            VoyageDeplacement::STATUS_DRAFT,
            VoyageDeplacement::STATUS_SUBMITTED,
            VoyageDeplacement::STATUS_SERVICE_VALIDATED,
            VoyageDeplacement::STATUS_VALIDATED,
            VoyageDeplacement::STATUS_REJECTED,
        ], true)) {
            return new JsonResponse(['message' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
        }

        if ('' !== $typePaie && !in_array($typePaie, ['mensuelle', 'quinzaine'], true)) {
            return new JsonResponse(['message' => 'Invalid typePaie'], Response::HTTP_BAD_REQUEST);
        }

        $employees = $gestionnaireService->getManagedEmployeesByUser($user, '' !== $typePaie ? $typePaie : null);
        $voyages = $voyageRepo->findByEmployeesAndFilters($employees, '' !== $status ? $status : null, '' !== $typePaie ? $typePaie : null);

        $data = array_map([$this, 'serializeVoyage'], $voyages);

        return $this->json(['items' => $data]);
    }

    #[Route('', name: 'api_gestionnaire_voyages_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        PeriodePaieRepository $periodeRepo,
        VoyageDeplacementRepository $voyageRepo,
        GestionnaireService $gestionnaireService
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

        $typePaie = isset($payload['typePaie']) ? (string) $payload['typePaie'] : '';
        if (!in_array($typePaie, ['mensuelle', 'quinzaine'], true)) {
            return new JsonResponse(['message' => 'typePaie is required (mensuelle|quinzaine)'], Response::HTTP_BAD_REQUEST);
        }

        $employees = $gestionnaireService->getManagedEmployeesByUser($user, $typePaie);
        if ([] === $employees) {
            return new JsonResponse(['message' => 'No managed employees for this typePaie'], Response::HTTP_FORBIDDEN);
        }

        $employeeId = isset($payload['employeeId']) ? (int) $payload['employeeId'] : 0;
        $employee = null;
        foreach ($employees as $e) {
            if ($e->getId() === $employeeId) {
                $employee = $e;
                break;
            }
        }
        if (null === $employee) {
            return new JsonResponse(['message' => 'Invalid employeeId for this gestionnaire'], Response::HTTP_FORBIDDEN);
        }

        $periode = $periodeRepo->findOneBy([
            'statut' => PeriodePaie::STATUT_OUVERT,
            'typePaie' => $typePaie,
        ]);
        if (!$periode) {
            return new JsonResponse(['message' => 'No open periodePaie for this typePaie'], Response::HTTP_CONFLICT);
        }

        $depart = $this->parseDateTime($payload['dateHeureDepart'] ?? null);
        $retour = $this->parseDateTime($payload['dateHeureRetour'] ?? null);
        if (null === $depart || null === $retour) {
            return new JsonResponse(['message' => 'dateHeureDepart and dateHeureRetour are required (ISO-8601)'], Response::HTTP_BAD_REQUEST);
        }
        if ($retour < $depart) {
            return new JsonResponse(['message' => 'dateHeureRetour must be >= dateHeureDepart'], Response::HTTP_BAD_REQUEST);
        }

        if ($voyageRepo->isDepartureOverlapping($employee, $depart)) {
            return new JsonResponse(['message' => 'Departure overlaps with another voyage'], Response::HTTP_CONFLICT);
        }

        $modeTransport = isset($payload['modeTransport']) ? trim((string) $payload['modeTransport']) : '';
        if ('' === $modeTransport) {
            return new JsonResponse(['message' => 'modeTransport is required'], Response::HTTP_BAD_REQUEST);
        }

        $voyage = new VoyageDeplacement();
        $voyage->setEmployee($employee);
        $voyage->setPeriodePaie($periode);
        $voyage->setStatus(VoyageDeplacement::STATUS_DRAFT);
        $voyage->setCreatedAt(new \DateTimeImmutable());

        $voyage->setModeTransport($modeTransport);
        $voyage->setDateHeureDepart($depart);
        $voyage->setDateHeureRetour($retour);
        $voyage->setDistanceKm(isset($payload['distanceKm']) ? (float) $payload['distanceKm'] : 0.0);
        $voyage->setTypeVoyage(isset($payload['typeVoyage']) ? (string) $payload['typeVoyage'] : null);
        $voyage->setMotif(isset($payload['motif']) ? (string) $payload['motif'] : null);

        $voyage->setVilleDepartAller($payload['villeDepartAller'] ?? null);
        $voyage->setVilleArriveeAller($payload['villeArriveeAller'] ?? null);
        $voyage->setVilleDepartRetour($payload['villeDepartRetour'] ?? null);
        $voyage->setVilleArriveeRetour($payload['villeArriveeRetour'] ?? null);

        $voyage->setLatDepartAller(isset($payload['latDepartAller']) ? (float) $payload['latDepartAller'] : null);
        $voyage->setLonDepartAller(isset($payload['lonDepartAller']) ? (float) $payload['lonDepartAller'] : null);
        $voyage->setLatArriveeAller(isset($payload['latArriveeAller']) ? (float) $payload['latArriveeAller'] : null);
        $voyage->setLonArriveeAller(isset($payload['lonArriveeAller']) ? (float) $payload['lonArriveeAller'] : null);
        $voyage->setLatDepartRetour(isset($payload['latDepartRetour']) ? (float) $payload['latDepartRetour'] : null);
        $voyage->setLonDepartRetour(isset($payload['lonDepartRetour']) ? (float) $payload['lonDepartRetour'] : null);
        $voyage->setLatArriveeRetour(isset($payload['latArriveeRetour']) ? (float) $payload['latArriveeRetour'] : null);
        $voyage->setLonArriveeRetour(isset($payload['lonArriveeRetour']) ? (float) $payload['lonArriveeRetour'] : null);

        $em->persist($voyage);
        $em->flush();

        return $this->json($this->serializeVoyage($voyage), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_gestionnaire_voyages_update', methods: ['PUT'])]
    public function update(
        VoyageDeplacement $voyage,
        Request $request,
        EntityManagerInterface $em,
        GestionnaireService $gestionnaireService,
        VoyageDeplacementRepository $voyageRepo
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!in_array($voyage->getStatus(), [VoyageDeplacement::STATUS_DRAFT, VoyageDeplacement::STATUS_REJECTED], true)) {
            return new JsonResponse(['message' => 'Voyage cannot be edited in this status'], Response::HTTP_CONFLICT);
        }

        $typePaie = $voyage->getPeriodePaie()?->getTypePaie();
        $employees = $gestionnaireService->getManagedEmployeesByUser($user, $typePaie);
        $managedEmployeeIds = array_map(static fn($e) => $e->getId(), $employees);
        $voyageEmployeeId = $voyage->getEmployee()?->getId();
        if (null === $voyageEmployeeId || !in_array($voyageEmployeeId, $managedEmployeeIds, true)) {
            return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('dateHeureDepart', $payload)) {
            $depart = $this->parseDateTime($payload['dateHeureDepart']);
            if (null === $depart) {
                return new JsonResponse(['message' => 'Invalid dateHeureDepart'], Response::HTTP_BAD_REQUEST);
            }
            $voyage->setDateHeureDepart($depart);
        }

        if (array_key_exists('dateHeureRetour', $payload)) {
            $retour = $this->parseDateTime($payload['dateHeureRetour']);
            if (null === $retour) {
                return new JsonResponse(['message' => 'Invalid dateHeureRetour'], Response::HTTP_BAD_REQUEST);
            }
            $voyage->setDateHeureRetour($retour);
        }

        if (null !== $voyage->getDateHeureDepart() && null !== $voyage->getDateHeureRetour() && $voyage->getDateHeureRetour() < $voyage->getDateHeureDepart()) {
            return new JsonResponse(['message' => 'dateHeureRetour must be >= dateHeureDepart'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('modeTransport', $payload)) {
            $modeTransport = trim((string) $payload['modeTransport']);
            if ('' === $modeTransport) {
                return new JsonResponse(['message' => 'modeTransport cannot be empty'], Response::HTTP_BAD_REQUEST);
            }
            $voyage->setModeTransport($modeTransport);
        }

        if (array_key_exists('distanceKm', $payload)) {
            $voyage->setDistanceKm((float) $payload['distanceKm']);
        }
        if (array_key_exists('typeVoyage', $payload)) {
            $voyage->setTypeVoyage(null === $payload['typeVoyage'] ? null : (string) $payload['typeVoyage']);
        }
        if (array_key_exists('motif', $payload)) {
            $voyage->setMotif(null === $payload['motif'] ? null : (string) $payload['motif']);
        }

        foreach (['villeDepartAller', 'villeArriveeAller', 'villeDepartRetour', 'villeArriveeRetour'] as $field) {
            if (array_key_exists($field, $payload)) {
                $setter = 'set'.ucfirst($field);
                $voyage->$setter(null === $payload[$field] ? null : (string) $payload[$field]);
            }
        }

        foreach ([
            'latDepartAller', 'lonDepartAller', 'latArriveeAller', 'lonArriveeAller',
            'latDepartRetour', 'lonDepartRetour', 'latArriveeRetour', 'lonArriveeRetour',
        ] as $field) {
            if (array_key_exists($field, $payload)) {
                $setter = 'set'.ucfirst($field);
                $voyage->$setter(null === $payload[$field] ? null : (float) $payload[$field]);
            }
        }

        $employee = $voyage->getEmployee();
        $depart = $voyage->getDateHeureDepart();
        if ($employee && $depart && $voyageRepo->isDepartureOverlapping($employee, $depart, $voyage->getId())) {
            return new JsonResponse(['message' => 'Departure overlaps with another voyage'], Response::HTTP_CONFLICT);
        }

        $voyage->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json($this->serializeVoyage($voyage));
    }

    #[Route('/{id}/submit', name: 'api_gestionnaire_voyages_submit', methods: ['POST'])]
    public function submit(
        VoyageDeplacement $voyage,
        EntityManagerInterface $em,
        GestionnaireService $gestionnaireService,
        WorkflowInterface $voyage_deplacement
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $typePaie = $voyage->getPeriodePaie()?->getTypePaie();
        $employees = $gestionnaireService->getManagedEmployeesByUser($user, $typePaie);
        $managedEmployeeIds = array_map(static fn($e) => $e->getId(), $employees);
        $voyageEmployeeId = $voyage->getEmployee()?->getId();
        if (null === $voyageEmployeeId || !in_array($voyageEmployeeId, $managedEmployeeIds, true)) {
            return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if (!$voyage_deplacement->can($voyage, 'submit')) {
            return new JsonResponse(['message' => 'Transition not allowed'], Response::HTTP_CONFLICT);
        }

        $voyage_deplacement->apply($voyage, 'submit');
        $voyage->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json($this->serializeVoyage($voyage));
    }

    #[Route('/{id}', name: 'api_gestionnaire_voyages_delete', methods: ['DELETE'])]
    public function delete(
        VoyageDeplacement $voyage,
        EntityManagerInterface $em,
        GestionnaireService $gestionnaireService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!in_array($voyage->getStatus(), [VoyageDeplacement::STATUS_DRAFT, VoyageDeplacement::STATUS_REJECTED], true)) {
            return new JsonResponse(['message' => 'Voyage cannot be deleted in this status'], Response::HTTP_CONFLICT);
        }

        $typePaie = $voyage->getPeriodePaie()?->getTypePaie();
        $employees = $gestionnaireService->getManagedEmployeesByUser($user, $typePaie);
        $managedEmployeeIds = array_map(static fn($e) => $e->getId(), $employees);
        $voyageEmployeeId = $voyage->getEmployee()?->getId();
        if (null === $voyageEmployeeId || !in_array($voyageEmployeeId, $managedEmployeeIds, true)) {
            return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($voyage);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
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
            'villeDepartAller' => $voyage->getVilleDepartAller(),
            'villeArriveeAller' => $voyage->getVilleArriveeAller(),
            'villeDepartRetour' => $voyage->getVilleDepartRetour(),
            'villeArriveeRetour' => $voyage->getVilleArriveeRetour(),
            'coords' => [
                'latDepartAller' => $voyage->getLatDepartAller(),
                'lonDepartAller' => $voyage->getLonDepartAller(),
                'latArriveeAller' => $voyage->getLatArriveeAller(),
                'lonArriveeAller' => $voyage->getLonArriveeAller(),
                'latDepartRetour' => $voyage->getLatDepartRetour(),
                'lonDepartRetour' => $voyage->getLonDepartRetour(),
                'latArriveeRetour' => $voyage->getLatArriveeRetour(),
                'lonArriveeRetour' => $voyage->getLonArriveeRetour(),
            ],
            'createdAt' => $voyage->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $voyage->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}

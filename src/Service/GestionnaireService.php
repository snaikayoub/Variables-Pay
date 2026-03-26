<?php

namespace App\Service;

use App\Entity\EmployeeSituation;
use App\Repository\ServiceRepository;

class GestionnaireService
{
    public function __construct(
        private readonly ServiceRepository $serviceRepo
    ) {}

    /**
     * Retourne la période de paie ouverte pour un type donné
     */
    public function getManagedServicesByUser($user): ?array
    {

        $ManagedServicesByUser = $this->serviceRepo->createQueryBuilder('s')
            ->join('s.gestionnaire', 'g')
            ->where('g = :user')->setParameter('user', $user)
            ->getQuery()->getResult();
        return $ManagedServicesByUser;
    }

    public function getManagedEmployeesByUser($user, ?string $typePaie = null): array
    {
        $managedServices = $this->serviceRepo->createQueryBuilder('s')
            ->join('s.gestionnaire', 'g')
            ->where('g = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $employees = [];
        $now = new \DateTimeImmutable();

        foreach ($managedServices as $service) {
            foreach ($service->getEmployeeSituations() as $employeeSituation) {
                // ✅ Vérifier que la situation est active
                if (
                    $employeeSituation->getStartDate() <= $now &&
                    (null === $employeeSituation->getEndDate() || $employeeSituation->getEndDate() >= $now)
                ) {
                    // ✅ Filtrer par type de paie si spécifié
                    if ($typePaie !== null && $employeeSituation->getTypePaie() !== $typePaie) {
                        continue; // Ignorer si le type ne correspond pas
                    }

                    $employee = $employeeSituation->getEmployee();

                    if ($employee) {
                        $employees[$employee->getId()] = $employee;
                    }
                }
            }
        }

        return array_values($employees);
    }

    // Nombre total de collaborateurs (employeeSituations)
    public function countCollaborateursByServices(array $services): int
    {
        $collaborateursCount = 0;
        foreach ($services as $srv) {
            $collaborateursCount += $srv->getEmployeeSituations()
                ->filter(fn(EmployeeSituation $es) => $es->getStartDate() <= new \DateTimeImmutable() &&
                    (null === $es->getEndDate() || $es->getEndDate() >= new \DateTimeImmutable()))
                ->count();
        }
        return $collaborateursCount;
    }
}

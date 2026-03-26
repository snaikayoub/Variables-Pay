<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\Service;
use App\Entity\User;
use App\Entity\VoyageDeplacement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VoyageDeplacement>
 */
class VoyageDeplacementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoyageDeplacement::class);
    }

    /**
     * @return VoyageDeplacement[]
     */
    public function findByServiceValidatorAndStatusAndType(User $user, string $status, string $type): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('v')
            ->distinct()
            ->join('v.periodePaie', 'p')
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->andWhere('s.validateurService = :user')
            ->andWhere('v.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('today', $today)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByServiceValidatorAndStatusAndType(User $user, string $status, string $type): int
    {
        $today = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(DISTINCT v.id)')
            ->join('v.periodePaie', 'p')
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->andWhere('s.validateurService = :user')
            ->andWhere('v.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return VoyageDeplacement[]
     */
    public function findByDivisionValidatorAndStatusAndType(User $user, string $status, string $type): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('v')
            ->distinct()
            ->join('v.periodePaie', 'p')
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('d.validateurDivision = :user')
            ->andWhere('v.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('today', $today)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByDivisionValidatorAndStatusAndType(User $user, string $status, string $type): int
    {
        $today = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(DISTINCT v.id)')
            ->join('v.periodePaie', 'p')
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('d.validateurDivision = :user')
            ->andWhere('v.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return VoyageDeplacement[]
     */
    public function findByDivisionValidatorServiceAndStatusAndType(User $user, Service $service, string $status, string $type): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('v')
            ->distinct()
            ->join('v.periodePaie', 'p')
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('d.validateurDivision = :user')
            ->andWhere('s = :service')
            ->andWhere('v.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('user', $user)
            ->setParameter('service', $service)
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('today', $today)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByDivisionValidatorServiceAndStatusAndType(User $user, Service $service, string $status, string $type): int
    {
        $today = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(DISTINCT v.id)')
            ->join('v.periodePaie', 'p')
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('d.validateurDivision = :user')
            ->andWhere('s = :service')
            ->andWhere('v.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('user', $user)
            ->setParameter('service', $service)
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatusAndType(string $status, string $type): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.periodePaie', 'p')
            ->andWhere('v.status = :status')
            ->andWhere('p.typePaie = :type')
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Employee[] $employees
     * @return VoyageDeplacement[]
     */
    public function findByEmployeesAndFilters(array $employees, ?string $status = null, ?string $typePaie = null): array
    {
        if ([] === $employees) {
            return [];
        }

        $qb = $this->createQueryBuilder('v')
            ->join('v.periodePaie', 'p')
            ->andWhere('v.employee IN (:employees)')
            ->setParameter('employees', $employees)
            ->orderBy('v.createdAt', 'DESC');

        if (null !== $status && '' !== $status) {
            $qb->andWhere('v.status = :status')->setParameter('status', $status);
        }

        if (null !== $typePaie && '' !== $typePaie) {
            $qb->andWhere('p.typePaie = :typePaie')->setParameter('typePaie', $typePaie);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return VoyageDeplacement[]
     */
    public function findWithDivision(array $criteria, $divisionId = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.employee', 'e')
            ->leftJoin('e.employeeSituations', 'es')
            ->leftJoin('es.service', 's')
            ->leftJoin('s.division', 'd')
            ->leftJoin('v.periodePaie', 'p')
            ->addSelect('e', 'es', 's', 'd', 'p');

        foreach ($criteria as $k => $v) {
            $qb->andWhere("v.$k = :$k")->setParameter($k, $v);
        }
        if ($divisionId) {
            $qb->andWhere('d.id = :div')->setParameter('div', $divisionId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int,int> serviceId => count
     */
    public function countByServiceGrouped(string $status, ?string $type = null): array
    {
        $today = new \DateTimeImmutable('today');

        $qb = $this->createQueryBuilder('v')
            ->select('s.id AS serviceId, COUNT(DISTINCT v.id) AS cnt')
            ->join('v.periodePaie', 'p')
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->andWhere('v.status = :status')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('status', $status)
            ->setParameter('today', $today)
            ->groupBy('s.id');

        if (null !== $type && '' !== $type) {
            $qb->andWhere('p.typePaie = :type')->setParameter('type', $type);
        }

        $rows = $qb->getQuery()->getArrayResult();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['serviceId']] = (int) $r['cnt'];
        }

        return $out;
    }

    public function isDepartureOverlapping(Employee $employee, \DateTimeInterface $departureAt, ?int $excludeVoyageId = null): bool
    {
        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.employee = :employee')
            ->andWhere('v.dateHeureDepart <= :departureAt')
            ->andWhere('v.dateHeureRetour >= :departureAt')
            ->setParameter('employee', $employee)
            ->setParameter('departureAt', $departureAt);

        if (null !== $excludeVoyageId) {
            $qb->andWhere('v.id != :excludeId')
                ->setParameter('excludeId', $excludeVoyageId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param int[] $ids
     * @return VoyageDeplacement[]
     */
    public function findByIdsForServiceValidator(User $responsable, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('v')
            ->distinct()
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->andWhere('v.id IN (:ids)')
            ->andWhere('s.validateurService = :resp')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('ids', $ids)
            ->setParameter('resp', $responsable)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $ids
     * @return VoyageDeplacement[]
     */
    public function findByIdsForDivisionValidator(User $responsable, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('v')
            ->distinct()
            ->join('v.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('v.id IN (:ids)')
            ->andWhere('d.validateurDivision = :resp')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('ids', $ids)
            ->setParameter('resp', $responsable)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return VoyageDeplacement[] Returns an array of VoyageDeplacement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('v.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?VoyageDeplacement
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

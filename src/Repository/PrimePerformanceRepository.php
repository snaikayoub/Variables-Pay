<?php
// src/Repository/PrimePerformanceRepository.php

namespace App\Repository;

use App\Entity\PrimePerformance;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class PrimePerformanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrimePerformance::class);
    }

    /**
     * Compte le nombre de primes pour un validateur de division,
     * selon le type de paie et le statut.
     */
    public function countByTypeAndStatus(User $responsable, string $type, string $status): int
    {
        return (int) $this->createQueryBuilder('pp')
            ->select('COUNT(pp.id)')
            ->join('pp.periodePaie', 'p')
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->where('pp.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('d.validateurDivision = :resp')
            ->andWhere('es.startDate <= CURRENT_DATE()')
            ->andWhere('es.endDate IS NULL OR es.endDate >= CURRENT_DATE()')
            ->setParameter('status', $status)
            ->setParameter('type',   $type)
            ->setParameter('resp',   $responsable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les primes d’une division pour un validateur donné,
     * selon le statut et le type de paie.
     */
    public function findByDivisionAndStatusAndType(User $responsable, string $status, string $type): array
    {
        return $this->createQueryBuilder('pp')
            ->join('pp.periodePaie',        'p')
            ->join('pp.employee',           'e')
            ->join('e.employeeSituations',  'es')
            ->join('es.service',            's')
            ->join('s.division',            'd')
            ->where('pp.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('d.validateurDivision = :resp')
            ->andWhere('es.startDate <= CURRENT_DATE()')
            ->andWhere('es.endDate IS NULL OR es.endDate >= CURRENT_DATE()')
            ->setParameter('status', $status)
            ->setParameter('type',   $type)
            ->setParameter('resp',   $responsable)
            ->orderBy('p.annee', 'DESC')
            ->addOrderBy('p.mois', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function countSubmittedByType(User $responsable, string $type): int
    {
        return (int) $this->createQueryBuilder('pp')
            ->select('COUNT(pp.id)')
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('pp.periodePaie', 'p')
            ->where('pp.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('s.validateurService = :resp')
            ->andWhere('es.startDate <= CURRENT_DATE()')
            ->andWhere('es.endDate IS NULL OR es.endDate >= CURRENT_DATE()')
            ->setParameter('status', 'submitted')
            ->setParameter('type', $type)
            ->setParameter('resp', $responsable)
            ->getQuery()
            ->getSingleScalarResult();
    }
    public function findByServiceAndStatusAndType(User $responsable, string $status, string $type): array
    {
        return $this->createQueryBuilder('pp')
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('pp.periodePaie', 'p')
            ->where('pp.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('s.validateurService = :resp')
            ->andWhere('es.startDate <= CURRENT_DATE()')
            ->andWhere('es.endDate IS NULL OR es.endDate >= CURRENT_DATE()')
            ->setParameter('status', $status)
            ->setParameter('resp', $responsable)
            ->setParameter('type', $type)
            ->orderBy('pp.periodePaie', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('pp')
            ->select('COUNT(pp.id)')
            ->andWhere('pp.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
    public function findByStatusAndType(string $status, string $type): array
    {
        return $this->createQueryBuilder('pp')
            ->join('pp.periodePaie', 'p')
            ->where('pp.status = :status')
            ->andWhere('p.typePaie = :type')
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->orderBy('p.annee', 'DESC')
            ->addOrderBy('p.mois', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findWithDivision(array $criteria, $divisionId = null)
    {
        $qb = $this->createQueryBuilder('pp')
            ->leftJoin('pp.employee', 'e')
            ->leftJoin('e.employeeSituations', 'es')
            ->leftJoin('es.service', 's')
            ->leftJoin('s.division', 'd')
            ->addSelect('e', 'es', 's', 'd');

        foreach ($criteria as $k => $v) {
            $qb->andWhere("pp.$k = :$k")->setParameter($k, $v);
        }
        if ($divisionId) {
            $qb->andWhere('d.id = :div')->setParameter('div', $divisionId);
        }

        return $qb->getQuery()->getResult();
    }

    public function countPrimePerformanceSaisies(User $user): int
    {
        $currentMonth = (new \DateTimeImmutable())->format('m');
        $currentYear = (new \DateTimeImmutable())->format('Y');

        return (int) $this->createQueryBuilder('pp')
            ->select('COUNT(DISTINCT e.id)')
            ->join('pp.periodePaie', 'p')
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.gestionnaire', 'g')
            ->where('g = :user')
            ->andWhere('p.mois = :month')
            ->andWhere('p.annee = :year')
            ->setParameter('user', $user)
            ->setParameter('month', $currentMonth)
            ->setParameter('year', $currentYear)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPrimePerformanceEnAttente(User $user): int
    {
        return (int) $this->createQueryBuilder('pp')
            ->select('COUNT(DISTINCT e.id)')
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.gestionnaire', 'g')
            ->where('g = :user')
            ->andWhere('pp.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', PrimePerformance::STATUS_DRAFT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPrimePerformanceValidees(User $user): int
    {
        return (int) $this->createQueryBuilder('pp')
            ->select('COUNT(DISTINCT e.id)')
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.gestionnaire', 'g')
            ->where('g = :user')
            ->andWhere('pp.status != :status')
            ->setParameter('user', $user)
            ->setParameter('status', PrimePerformance::STATUS_DRAFT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return PrimePerformance[]
     */
    public function findByIdsForServiceValidator(User $responsable, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('pp')
            ->distinct()
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->andWhere('pp.id IN (:ids)')
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
     * @return PrimePerformance[]
     */
    public function findByIdsForDivisionValidator(User $responsable, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('pp')
            ->distinct()
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('pp.id IN (:ids)')
            ->andWhere('d.validateurDivision = :resp')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('ids', $ids)
            ->setParameter('resp', $responsable)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PrimePerformance[]
     */
    public function findByIdsForDivisionValidatorAndService(User $responsable, int $serviceId, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('pp')
            ->distinct()
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('pp.id IN (:ids)')
            ->andWhere('s.id = :serviceId')
            ->andWhere('d.validateurDivision = :resp')
            ->andWhere('es.startDate <= :today')
            ->andWhere('(es.endDate IS NULL OR es.endDate >= :today)')
            ->setParameter('ids', $ids)
            ->setParameter('serviceId', $serviceId)
            ->setParameter('resp', $responsable)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int,int> serviceId => count
     */
    public function countByServiceGrouped(string $status, ?string $type = null): array
    {
        $today = new \DateTimeImmutable('today');

        $qb = $this->createQueryBuilder('pp')
            ->select('s.id AS serviceId, COUNT(DISTINCT pp.id) AS cnt')
            ->join('pp.periodePaie', 'p')
            ->join('pp.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->andWhere('pp.status = :status')
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
}

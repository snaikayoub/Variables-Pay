<?php

namespace App\Repository;

use App\Entity\PrimeFonction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PrimeFonctionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrimeFonction::class);
    }

    public function countByTypeAndStatus(User $responsable, string $type, string $status): int
    {
        return (int) $this->createQueryBuilder('pf')
            ->select('COUNT(pf.id)')
            ->join('pf.periodePaie', 'p')
            ->join('pf.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->where('pf.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('d.validateurDivision = :resp')
            ->andWhere('es.startDate <= CURRENT_DATE()')
            ->andWhere('es.endDate IS NULL OR es.endDate >= CURRENT_DATE()')
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('resp', $responsable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByDivisionAndStatusAndType(User $responsable, string $status, string $type): array
    {
        return $this->createQueryBuilder('pf')
            ->join('pf.periodePaie', 'p')
            ->join('pf.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->where('pf.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('d.validateurDivision = :resp')
            ->andWhere('es.startDate <= CURRENT_DATE()')
            ->andWhere('es.endDate IS NULL OR es.endDate >= CURRENT_DATE()')
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('resp', $responsable)
            ->orderBy('p.annee', 'DESC')
            ->addOrderBy('p.mois', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countSubmittedByType(User $responsable, string $type): int
    {
        return (int) $this->createQueryBuilder('pf')
            ->select('COUNT(pf.id)')
            ->join('pf.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('pf.periodePaie', 'p')
            ->where('pf.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('s.validateurService = :resp')
            ->andWhere('es.startDate <= CURRENT_DATE()')
            ->andWhere('es.endDate IS NULL OR es.endDate >= CURRENT_DATE()')
            ->setParameter('status', PrimeFonction::STATUS_SUBMITTED)
            ->setParameter('type', $type)
            ->setParameter('resp', $responsable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByServiceAndStatusAndType(User $responsable, string $status, string $type): array
    {
        return $this->createQueryBuilder('pf')
            ->join('pf.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('pf.periodePaie', 'p')
            ->where('pf.status = :status')
            ->andWhere('p.typePaie = :type')
            ->andWhere('s.validateurService = :resp')
            ->andWhere('es.startDate <= CURRENT_DATE()')
            ->andWhere('es.endDate IS NULL OR es.endDate >= CURRENT_DATE()')
            ->setParameter('status', $status)
            ->setParameter('resp', $responsable)
            ->setParameter('type', $type)
            ->orderBy('p.annee', 'DESC')
            ->addOrderBy('p.mois', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('pf')
            ->select('COUNT(pf.id)')
            ->andWhere('pf.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByStatusAndType(string $status, string $type): array
    {
        return $this->createQueryBuilder('pf')
            ->join('pf.periodePaie', 'p')
            ->where('pf.status = :status')
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
        $qb = $this->createQueryBuilder('pf')
            ->leftJoin('pf.employee', 'e')
            ->leftJoin('e.employeeSituations', 'es')
            ->leftJoin('es.service', 's')
            ->leftJoin('s.division', 'd')
            ->addSelect('e', 'es', 's', 'd');

        foreach ($criteria as $k => $v) {
            $qb->andWhere("pf.$k = :$k")->setParameter($k, $v);
        }
        if ($divisionId) {
            $qb->andWhere('d.id = :div')->setParameter('div', $divisionId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return PrimeFonction[]
     */
    public function findByIdsForServiceValidator(User $responsable, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('pf')
            ->distinct()
            ->join('pf.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->andWhere('pf.id IN (:ids)')
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
     * @return PrimeFonction[]
     */
    public function findByIdsForDivisionValidator(User $responsable, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('pf')
            ->distinct()
            ->join('pf.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('pf.id IN (:ids)')
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
     * @return PrimeFonction[]
     */
    public function findByIdsForDivisionValidatorAndService(User $responsable, int $serviceId, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('pf')
            ->distinct()
            ->join('pf.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->join('s.division', 'd')
            ->andWhere('pf.id IN (:ids)')
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

        $qb = $this->createQueryBuilder('pf')
            ->select('s.id AS serviceId, COUNT(DISTINCT pf.id) AS cnt')
            ->join('pf.periodePaie', 'p')
            ->join('pf.employee', 'e')
            ->join('e.employeeSituations', 'es')
            ->join('es.service', 's')
            ->andWhere('pf.status = :status')
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

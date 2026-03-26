<?php

namespace App\Repository;

use App\Entity\PeriodePaie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PeriodePaie>
 */
class PeriodePaieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeriodePaie::class);
    }

    //    /**
    //     * @return PeriodePaie[] Returns an array of PeriodePaie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PeriodePaie
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    // src/Repository/PeriodePaieRepository.php

    public function fermerAutresPeriodesOuvertes(PeriodePaie $periodeCourante): void
    {
        $qb = $this->createQueryBuilder('p')
            ->update()
            ->set('p.statut', ':ferme')
            ->where('p.typePaie = :typePaie')
            ->andWhere('p.id != :id')
            ->andWhere('p.statut = :ouverte')
            ->setParameter('ferme', 'Fermée')
            ->setParameter('ouverte', 'Ouverte')
            ->setParameter('typePaie', $periodeCourante->getTypePaie())
            ->setParameter('id', $periodeCourante->getId());

        $qb->getQuery()->execute();
    }
}

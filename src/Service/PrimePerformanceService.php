<?php

namespace App\Service;

use App\Entity\CategoryTM;
use App\Entity\EmployeeSituation;
use App\Entity\PeriodePaie;
use App\Entity\PrimePerformance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class PrimePerformanceService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $prime_performance
    ) {}

    /**
     * Création ou mise à jour d'une prime de performance
     */
    public function submitPrime(
        EmployeeSituation $situation,
        PeriodePaie $periode,
        float $joursPerf,
        float $noteHierarchique
    ): PrimePerformance {

        $repo = $this->em->getRepository(PrimePerformance::class);

        $prime = $repo->findOneBy([
            'employee'    => $situation->getEmployee(),
            'periodePaie' => $periode
        ]) ?? new PrimePerformance();

        // Détermination du taux monétaire
        $tm = $this->resolveTauxMonetaire($situation);

        $prime
            ->setEmployee($situation->getEmployee())
            ->setPeriodePaie($periode)
            ->setTauxMonetaire($tm)
            ->setJoursPerf($joursPerf)
            ->setNoteHierarchique($noteHierarchique)
            ->setStatus(PrimePerformance::STATUS_DRAFT)
            ->calculerMontant();

        $this->em->persist($prime);

        // Transition workflow
        if ($this->prime_performance->can($prime, 'submit')) {
            $this->prime_performance->apply($prime, 'submit');
        }

        $this->em->flush();

        return $prime;
    }

    /**
     * Résolution du taux monétaire depuis CategoryTM
     */
    private function resolveTauxMonetaire(EmployeeSituation $situation): float
    {
        $model = $this->em->getRepository(CategoryTM::class)
            ->findOneBy([
                'grpPerf'  => $situation->getEmployee()->getGrpPerf(),
                'category' => $situation->getCategory()
            ]);

        return $model?->getTM() ?? 0.0;
    }
}

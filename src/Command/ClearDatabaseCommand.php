<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:clear-database',
    description: 'Vide toutes les entités de la base de données dans le bon ordre.'
)]
class ClearDatabaseCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Suppression des données en cours...</info>');

        $entities = [
            'App\Entity\VoyageDeplacement',
            'App\Entity\PrimeFonction',
            'App\Entity\PrimePerformance',
            'App\Entity\Conge',
            'App\Entity\EmployeeSituation',
            'App\Entity\Employee',
            'App\Entity\Service',
            'App\Entity\Division',
            'App\Entity\PeriodePaie',
            'App\Entity\CategoryTM',
            'App\Entity\Category',
            'App\Entity\CategorieFonction',
            'App\Entity\GrpPerf',
            'App\Entity\User',
        ];

        foreach ($entities as $entity) {
            $qb = $this->em->createQueryBuilder();
            $qb->delete($entity, 'e')
                ->getQuery()
                ->execute();
            $output->writeln("→ Table vidée : <comment>$entity</comment>");
        }

        $output->writeln('<info>✔ Toutes les entités ont été vidées avec succès.</info>');
        return Command::SUCCESS;
    }
}

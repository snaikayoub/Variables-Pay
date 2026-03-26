<?php
// src/Command/LoadPeriodePaieCommand.php
namespace App\Command;

use App\Entity\PeriodePaie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:load-periode-paie',
    description: 'Charge les périodes de paie (12 mensuelles et 24 quinzaine) pour une année donnée, sans doublons, statut initial “inactive”.'
)]
class LoadPeriodePaieCommand extends Command
{
    protected static $defaultName        = 'app:load-periode-paie';
    protected static $defaultDescription = 'Charge les périodes de paie pour une année (12 mensuelles et 24 quinzaine).';

    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('year', InputArgument::REQUIRED, 'Année pour laquelle créer les périodes (ex : 2025)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $year = (int) $input->getArgument('year');
        // Validation sommaire de l'année
        if ($year < 2000 || $year > ((int) date('Y') + 10)) {
            $output->writeln('<error>Année invalide. Choisissez une année raisonnable (entre 2000 et ' . ((int) date('Y') + 10) . ').</error>');
            return Command::FAILURE;
        }

        $repo    = $this->em->getRepository(PeriodePaie::class);
        $created = 0;

        // 1) Création des 12 périodes mensuelles
        for ($month = 1; $month <= 12; $month++) {
            $existsMensuelle = $repo->findOneBy([
                'typePaie' => 'mensuelle',
                'mois'     => $month,
                'annee'    => $year,
                'quinzaine'=> null,
            ]);

            if (!$existsMensuelle) {
                $p = new PeriodePaie();
                $p->setTypePaie('mensuelle')
                  ->setMois($month)
                  ->setAnnee($year)
                  ->setQuinzaine(null)
                  ->setStatut(PeriodePaie::STATUT_INACTIF);

                $this->em->persist($p);
                $created++;
                $output->writeln("→ Création période mensuelle : mois {$month}, année {$year}");
            } else {
                $output->writeln("→ Mensuelle déjà existante pour {$month}/{$year}, on la saute.");
            }
        }

        // 2) Création des 24 périodes quinzaine (2 par mois)
        for ($month = 1; $month <= 12; $month++) {
            for ($q = 1; $q <= 2; $q++) {
                $existsQuinzaine = $repo->findOneBy([
                    'typePaie' => 'quinzaine',
                    'mois'     => $month,
                    'annee'    => $year,
                    'quinzaine'=> $q,
                ]);

                if (!$existsQuinzaine) {
                    $pq = new PeriodePaie();
                    $pq->setTypePaie('quinzaine')
                       ->setMois($month)
                       ->setAnnee($year)
                       ->setQuinzaine($q)
                       ->setStatut(PeriodePaie::STATUT_INACTIF);

                    $this->em->persist($pq);
                    $created++;
                    $output->writeln("→ Création période quinzaine Q{$q} : mois {$month}, année {$year}");
                } else {
                    $output->writeln("→ Quinzaine Q{$q} ({$month}/{$year}) déjà existante, on la saute.");
                }
            }
        }

        // Enregistrer en base
        $this->em->flush();

        // Business rule: one (and only one) open period per type.
        // For the provided year, we open the "current" month when possible,
        // otherwise we default to January.
        $now = new \DateTimeImmutable();
        $openMonth = ((int) $now->format('Y') === $year) ? (int) $now->format('n') : 1;
        $openQ = ((int) $now->format('Y') === $year) ? (((int) $now->format('j')) <= 15 ? 1 : 2) : 1;

        // Mensuelle open
        $mensuelle = $repo->findOneBy([
            'typePaie' => 'mensuelle',
            'mois' => $openMonth,
            'annee' => $year,
            'quinzaine' => null,
        ]);
        if ($mensuelle instanceof PeriodePaie) {
            $mensuelle->setStatut(PeriodePaie::STATUT_OUVERT);
            $mensuelle->setScoreEquipe('100.00');
            $mensuelle->setScoreCollectif('100.00');
        }

        // Quinzaine open
        $quinzaine = $repo->findOneBy([
            'typePaie' => 'quinzaine',
            'mois' => $openMonth,
            'annee' => $year,
            'quinzaine' => $openQ,
        ]);
        if ($quinzaine instanceof PeriodePaie) {
            $quinzaine->setStatut(PeriodePaie::STATUT_OUVERT);
            $quinzaine->setScoreEquipe('100.00');
            $quinzaine->setScoreCollectif('100.00');
        }

        $this->em->flush();

        $output->writeln("<info>{$created} nouvelle(s) période(s) créées (statut : inactive).</info>");
        $output->writeln('<info>Une période mensuelle et une période quinzaine ont été ouvertes par défaut (scores: 100.00).</info>');
        return Command::SUCCESS;
    }
}

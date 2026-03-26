<?php

namespace App\Tests\Entity;

use App\Entity\PeriodePaie;
use App\Repository\PeriodePaieRepository;
use App\Tests\Support\DatabaseTestCase;

final class PeriodePaieOpenSubscriberTest extends DatabaseTestCase
{
    public function testFirstPeriodeAutoOpensAndDefaultsScores(): void
    {
        $em = $this->em();

        $periode = new PeriodePaie();
        $periode->setTypePaie('mensuelle');
        $periode->setMois(1);
        $periode->setAnnee(2026);
        // statut intentionally omitted to test auto-open

        $em->persist($periode);
        $em->flush();
        $em->clear();

        /** @var PeriodePaieRepository $repo */
        $repo = static::getContainer()->get(PeriodePaieRepository::class);
        $reloaded = $repo->find($periode->getId());

        $this->assertNotNull($reloaded);
        $this->assertSame(PeriodePaie::STATUT_OUVERT, $reloaded->getStatut());
        $this->assertSame('100.00', $reloaded->getScoreEquipe());
        $this->assertSame('100.00', $reloaded->getScoreCollectif());
    }

    public function testOpeningPeriodeClosesExistingOpenPeriode(): void
    {
        $em = $this->em();

        $first = new PeriodePaie();
        $first->setTypePaie('mensuelle');
        $first->setMois(1);
        $first->setAnnee(2026);
        $first->setStatut(PeriodePaie::STATUT_OUVERT);
        $first->setScoreEquipe('101.00');
        $first->setScoreCollectif('102.00');

        $em->persist($first);
        $em->flush();

        $second = new PeriodePaie();
        $second->setTypePaie('mensuelle');
        $second->setMois(2);
        $second->setAnnee(2026);
        $second->setStatut(PeriodePaie::STATUT_OUVERT);
        // scores intentionally missing to test defaulting when opening

        $em->persist($second);
        $em->flush();
        $em->clear();

        /** @var PeriodePaieRepository $repo */
        $repo = static::getContainer()->get(PeriodePaieRepository::class);

        $firstReloaded = $repo->find($first->getId());
        $secondReloaded = $repo->find($second->getId());

        $this->assertNotNull($firstReloaded);
        $this->assertNotNull($secondReloaded);

        $this->assertSame(PeriodePaie::STATUT_FERME, $firstReloaded->getStatut());
        $this->assertSame(PeriodePaie::STATUT_OUVERT, $secondReloaded->getStatut());
        $this->assertSame('100.00', $secondReloaded->getScoreEquipe());
        $this->assertSame('100.00', $secondReloaded->getScoreCollectif());
    }
}

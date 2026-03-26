<?php

namespace App\Tests\EventSubscriber;

use App\Entity\PeriodePaie;
use App\EventSubscriber\PeriodePaieOpenSubscriber;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

final class PeriodePaieOpenSubscriberUnitTest extends TestCase
{
    public function testPrePersistAutoOpensAndDefaultsScores(): void
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(id) FROM periode_paie WHERE type_paie = ? AND statut = ?',
                ['mensuelle', PeriodePaie::STATUT_OUVERT]
            )
            ->willReturn(0);

        $conn->expects($this->once())
            ->method('executeStatement')
            ->with(
                'UPDATE periode_paie SET statut = ? WHERE type_paie = ? AND statut = ?',
                [PeriodePaie::STATUT_FERME, 'mensuelle', PeriodePaie::STATUT_OUVERT]
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($conn);

        $periode = new PeriodePaie();
        $periode->setTypePaie('mensuelle');

        $args = new LifecycleEventArgs($periode, $em);

        $subscriber = new PeriodePaieOpenSubscriber();
        $subscriber->prePersist($args);

        $this->assertSame(PeriodePaie::STATUT_OUVERT, $periode->getStatut());
        $this->assertSame('100.00', $periode->getScoreEquipe());
        $this->assertSame('100.00', $periode->getScoreCollectif());
    }

    public function testPreUpdateClosesOtherOpenPeriodsAndDefaultsScores(): void
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('executeStatement')
            ->with(
                'UPDATE periode_paie SET statut = ? WHERE type_paie = ? AND statut = ? AND id <> ?',
                [PeriodePaie::STATUT_FERME, 'quinzaine', PeriodePaie::STATUT_OUVERT, 42]
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($conn);

        $periode = new PeriodePaie();
        $periode->setTypePaie('quinzaine');
        $periode->setStatut(PeriodePaie::STATUT_OUVERT);

        // force an id so the subscriber uses the "id <> ?" query
        $ref = new \ReflectionProperty(PeriodePaie::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($periode, 42);

        $changeSet = [];
        $args = new PreUpdateEventArgs($periode, $em, $changeSet);

        $subscriber = new PeriodePaieOpenSubscriber();
        $subscriber->preUpdate($args);

        $this->assertSame('100.00', $periode->getScoreEquipe());
        $this->assertSame('100.00', $periode->getScoreCollectif());
    }
}

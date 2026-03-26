<?php

namespace App\EventSubscriber;

use App\Entity\PeriodePaie;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Ensures business invariants for pay periods:
 * - At most one open period exists per typePaie (mensuelle / quinzaine)
 * - When a period is opened, missing scores default to 100.00
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class PeriodePaieOpenSubscriber
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof PeriodePaie) {
            return;
        }

        // If no open period exists for this type, default this new one to open.
        $em = $args->getObjectManager();
        if (!$em instanceof EntityManagerInterface) {
            return;
        }

        $conn = $em->getConnection();
        $typePaie = (string) $entity->getTypePaie();

        if ('' !== $typePaie) {
            $openCount = (int) $conn->fetchOne(
                'SELECT COUNT(id) FROM periode_paie WHERE type_paie = ? AND statut = ?',
                [$typePaie, PeriodePaie::STATUT_OUVERT]
            );

            // "Par defaut": only auto-open when the statut is not explicitly set.
            $statut = $entity->getStatut();
            if (0 === $openCount && (null === $statut || '' === trim((string) $statut))) {
                $entity->setStatut(PeriodePaie::STATUT_OUVERT);
            }
        }

        $this->normalizeIfOpening($args, $entity);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof PeriodePaie) {
            return;
        }

        $this->normalizeIfOpening($args, $entity);
    }

    private function normalizeIfOpening(LifecycleEventArgs $args, PeriodePaie $periode): void
    {
        if (PeriodePaie::STATUT_OUVERT !== $periode->getStatut()) {
            return;
        }

        // Default scores if missing.
        if (null === $periode->getScoreEquipe() || '' === trim((string) $periode->getScoreEquipe())) {
            $periode->setScoreEquipe('100.00');
        }
        if (null === $periode->getScoreCollectif() || '' === trim((string) $periode->getScoreCollectif())) {
            $periode->setScoreCollectif('100.00');
        }

        // Close any other open period of the same type.
        $em = $args->getObjectManager();
        if (!$em instanceof EntityManagerInterface) {
            return;
        }

        $conn = $em->getConnection();

        $typePaie = (string) $periode->getTypePaie();
        if ('' === $typePaie) {
            return;
        }

        $id = $periode->getId();
        if (null === $id) {
            // New entity: just close all others.
            $conn->executeStatement(
                'UPDATE periode_paie SET statut = ? WHERE type_paie = ? AND statut = ?',
                [PeriodePaie::STATUT_FERME, $typePaie, PeriodePaie::STATUT_OUVERT]
            );
        } else {
            $conn->executeStatement(
                'UPDATE periode_paie SET statut = ? WHERE type_paie = ? AND statut = ? AND id <> ?',
                [PeriodePaie::STATUT_FERME, $typePaie, PeriodePaie::STATUT_OUVERT, $id]
            );
        }
    }
}

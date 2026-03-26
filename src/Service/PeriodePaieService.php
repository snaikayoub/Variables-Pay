<?php

namespace App\Service;

use App\Entity\PeriodePaie;
use App\Repository\PeriodePaieRepository;

class PeriodePaieService
{
    public function __construct(
        private readonly PeriodePaieRepository $periodeRepo
    ) {}

    /**
     * Retourne la période de paie ouverte pour un type donné
     */
    public function getPeriodeOuverte(string $typePaie): ?PeriodePaie
    {
        return $this->periodeRepo->findOneBy([
            'typePaie' => $typePaie,
            'statut'   => PeriodePaie::STATUT_OUVERT
        ]);
    }

    /**
     * Vérifie que les scores sont bien configurés
     */
    public function isScoreConfigured(PeriodePaie $periode): bool
    {
        $scoreEquipe = $periode->getScoreEquipe();
        $scoreCollectif = $periode->getScoreCollectif();

        return null !== $scoreEquipe
            && '' !== trim((string) $scoreEquipe)
            && null !== $scoreCollectif
            && '' !== trim((string) $scoreCollectif);
    }
}

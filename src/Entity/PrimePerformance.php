<?php
namespace App\Entity;

use App\Repository\PrimePerformanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrimePerformanceRepository::class)]
class PrimePerformance
{
    public const STATUS_DRAFT             = 'draft';
    public const STATUS_SUBMITTED         = 'submitted';
    public const STATUS_SERVICE_VALIDATED = 'service_validated';
    public const STATUS_DIVISION_VALIDATED= 'division_validated';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'primePerformances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\ManyToOne(inversedBy: 'primePerformances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PeriodePaie $periodePaie = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?float $tauxMonetaire = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 90, max: 110)]
    private ?float $scoreEquipe = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 90, max: 110)]
    private ?float $scoreCollectif = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $montantPerf = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?float $joursPerf = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $noteHierarchique = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice([self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_SERVICE_VALIDATED, self::STATUS_DIVISION_VALIDATED])]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $calculatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    public function getPeriodePaie(): ?PeriodePaie
    {
        return $this->periodePaie;
    }

    public function setPeriodePaie(?PeriodePaie $periodePaie): self
    {
        $this->periodePaie = $periodePaie;
        return $this;
    }

    public function getTauxMonetaire(): ?float
    {
        return $this->tauxMonetaire;
    }

    public function setTauxMonetaire(float $tauxMonetaire): self
    {
        $this->tauxMonetaire = $tauxMonetaire;
        return $this;
    }

    public function getScoreEquipe(): ?float
    {
        return $this->scoreEquipe;
    }

    public function setScoreEquipe(float $scoreEquipe): self
    {
        $this->scoreEquipe = $scoreEquipe;
        return $this;
    }

    public function getScoreCollectif(): ?float
    {
        return $this->scoreCollectif;
    }

    public function setScoreCollectif(float $scoreCollectif): self
    {
        $this->scoreCollectif = $scoreCollectif;
        return $this;
    }

    public function getMontantPerf(): ?float
    {
        return $this->montantPerf;
    }

    public function setMontantPerf(?float $montantPerf): self
    {
        $this->montantPerf = $montantPerf;
        return $this;
    }

    public function getJoursPerf(): ?float
    {
        return $this->joursPerf;
    }

    public function setJoursPerf(float $joursPerf): self
    {
        $this->joursPerf = $joursPerf;
        return $this;
    }

    public function getNoteHierarchique(): ?float
    {
        return $this->noteHierarchique;
    }

    public function setNoteHierarchique(float $noteHierarchique): self
    {
        $this->noteHierarchique = $noteHierarchique;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCalculatedAt(): ?\DateTimeInterface
    {
        return $this->calculatedAt;
    }

    public function setCalculatedAt(?\DateTimeInterface $calculatedAt): self
    {
        $this->calculatedAt = $calculatedAt;
        return $this;
    }

    /**
     * Copie les scores depuis la période vers la PP pour garder l’historique.
     */
    public function synchroniserScoresDepuisPeriode(): self
    {
        if ($this->periodePaie) {
            $scoreEquipe = $this->periodePaie->getScoreEquipe();
            $scoreCollectif = $this->periodePaie->getScoreCollectif();

            $this->scoreEquipe = null === $scoreEquipe ? null : round((float) $scoreEquipe, 2);
            $this->scoreCollectif = null === $scoreCollectif ? null : round((float) $scoreCollectif, 2);
        }
        return $this;
    }

    /**
     * Calcule le montant de la prime selon la formule métier.
     * Lance une exception si un paramètre manque.
     */
    public function calculerMontant(): self
    {
        $this->synchroniserScoresDepuisPeriode();

        if (
            null === $this->tauxMonetaire
            || null === $this->joursPerf
            || null === $this->noteHierarchique
            || null === $this->scoreEquipe
            || null === $this->scoreCollectif
        ) {
            throw new \InvalidArgumentException('Tous les paramètres de calcul doivent être renseignés');
        }

        $this->montantPerf = $this->tauxMonetaire
            * $this->joursPerf
            * (
                (0.8 * ($this->scoreEquipe / 100) * ($this->noteHierarchique / 100))
                + (0.2 * ($this->scoreCollectif / 100))
            );
        $this->calculatedAt = new \DateTime();

        return $this;
    }

    /**
     * Vérifie si les scores dans la PP ont changé depuis le dernier calcul.
     */
    public function scoresOntChange(): bool
    {
        if (!$this->periodePaie) {
            return false;
        }

        $scoreEquipe = $this->periodePaie->getScoreEquipe();
        $scoreCollectif = $this->periodePaie->getScoreCollectif();
        if (null === $scoreEquipe || null === $scoreCollectif) {
            return false;
        }

        $scoreEquipe = round((float) $scoreEquipe, 2);
        $scoreCollectif = round((float) $scoreCollectif, 2);

        return
            $this->scoreEquipe    !== $scoreEquipe ||
            $this->scoreCollectif !== $scoreCollectif;
    }

    /**
     * Retourne le montant formaté pour l’affichage (MAD).
     */
    public function getMontantFormate(): string
    {
        return number_format($this->montantPerf ?? 0, 2, ',', ' ') . ' MAD';
    }
}

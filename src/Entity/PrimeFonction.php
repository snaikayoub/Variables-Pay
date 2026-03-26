<?php

namespace App\Entity;

use App\Repository\PrimeFonctionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrimeFonctionRepository::class)]
class PrimeFonction
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_SERVICE_VALIDATED = 'service_validated';
    public const STATUS_DIVISION_VALIDATED = 'division_validated';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'primeFonctions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\ManyToOne(inversedBy: 'primeFonctions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PeriodePaie $periodePaie = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Range(min: 5, max: 30)]
    private ?float $tauxMonetaireFonction = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?float $nombreJours = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 1)]
    private ?float $noteHierarchique = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $montantFonction = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice([
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_SERVICE_VALIDATED,
        self::STATUS_DIVISION_VALIDATED,
    ])]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
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

    public function getTauxMonetaireFonction(): ?float
    {
        return $this->tauxMonetaireFonction;
    }

    public function setTauxMonetaireFonction(float $tauxMonetaireFonction): self
    {
        $this->tauxMonetaireFonction = $tauxMonetaireFonction;

        return $this;
    }

    public function getNombreJours(): ?float
    {
        return $this->nombreJours;
    }

    public function setNombreJours(float $nombreJours): self
    {
        $this->nombreJours = $nombreJours;

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

    public function getMontantFonction(): ?float
    {
        return $this->montantFonction;
    }

    public function setMontantFonction(?float $montantFonction): self
    {
        $this->montantFonction = $montantFonction;

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

    public function calculerMontant(): self
    {
        // Le taux monetaire de la prime de fonction depend de la
        // categorie de fonction affectee au collaborateur.
        $tm = $this->employee?->getCategorieFonction()?->getTauxMonetaire();
        if (null !== $tm) {
            $this->tauxMonetaireFonction = (float) $tm;
        }

        if (null === $this->tauxMonetaireFonction || null === $this->nombreJours || null === $this->noteHierarchique) {
            throw new \InvalidArgumentException('Tous les champs doivent etre renseignes pour calculer la prime de fonction');
        }

        $this->montantFonction = $this->tauxMonetaireFonction * $this->nombreJours * $this->noteHierarchique;
        $this->calculatedAt = new \DateTime();

        return $this;
    }

    public function getMontantFormate(): string
    {
        return number_format($this->montantFonction ?? 0, 2, ',', ' ') . ' MAD';
    }
}

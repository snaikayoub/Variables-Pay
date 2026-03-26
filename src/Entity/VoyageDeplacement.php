<?php

namespace App\Entity;

use App\Repository\VoyageDeplacementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoyageDeplacementRepository::class)]
class VoyageDeplacement
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_SERVICE_VALIDATED = 'service_validated';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'voyageDeplacements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\ManyToOne(inversedBy: 'voyageDeplacements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PeriodePaie $periodePaie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeVoyage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(length: 255)]
    private ?string $modeTransport = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateHeureDepart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateHeureRetour = null;

    #[ORM\Column]
    private ?float $distanceKm = 0.0;

    // ───── Villes ─────
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $villeDepartAller = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $villeArriveeAller = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $villeDepartRetour = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $villeArriveeRetour = null;

    // ───── Coordonnées Aller ─────
    #[ORM\Column(nullable: true)]
    private ?float $latDepartAller = null;

    #[ORM\Column(nullable: true)]
    private ?float $lonDepartAller = null;

    #[ORM\Column(nullable: true)]
    private ?float $latArriveeAller = null;

    #[ORM\Column(nullable: true)]
    private ?float $lonArriveeAller = null;

    // ───── Coordonnées Retour ─────
    #[ORM\Column(nullable: true)]
    private ?float $latDepartRetour = null;

    #[ORM\Column(nullable: true)]
    private ?float $lonDepartRetour = null;

    #[ORM\Column(nullable: true)]
    private ?float $latArriveeRetour = null;

    #[ORM\Column(nullable: true)]
    private ?float $lonArriveeRetour = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): static
    {
        $this->employee = $employee;

        return $this;
    }

    public function getPeriodePaie(): ?PeriodePaie
    {
        return $this->periodePaie;
    }

    public function setPeriodePaie(?PeriodePaie $periodePaie): static
    {
        $this->periodePaie = $periodePaie;

        return $this;
    }

    public function getTypeVoyage(): ?string
    {
        return $this->typeVoyage;
    }

    public function setTypeVoyage(?string $typeVoyage): static
    {
        $this->typeVoyage = $typeVoyage;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getModeTransport(): ?string
    {
        return $this->modeTransport;
    }

    public function setModeTransport(string $modeTransport): static
    {
        $this->modeTransport = $modeTransport;

        return $this;
    }

    public function getDateHeureDepart(): ?\DateTimeInterface
    {
        return $this->dateHeureDepart;
    }

    public function setDateHeureDepart(\DateTimeInterface $dateHeureDepart): static
    {
        $this->dateHeureDepart = $dateHeureDepart;

        return $this;
    }

    public function getDateHeureRetour(): ?\DateTimeInterface
    {
        return $this->dateHeureRetour;
    }

    public function setDateHeureRetour(\DateTimeInterface $dateHeureRetour): static
    {
        $this->dateHeureRetour = $dateHeureRetour;

        return $this;
    }

    public function getDistanceKm(): ?float
    {
        return $this->distanceKm;
    }

    public function setDistanceKm(?float $distanceKm): static
    {
        $this->distanceKm = $distanceKm ?? 0.0;

        return $this;
    }

    // ───── Getters/Setters Villes ─────

    public function getVilleDepartAller(): ?string
    {
        return $this->villeDepartAller;
    }

    public function setVilleDepartAller(?string $villeDepartAller): static
    {
        $this->villeDepartAller = $villeDepartAller;
        return $this;
    }

    public function getVilleArriveeAller(): ?string
    {
        return $this->villeArriveeAller;
    }

    public function setVilleArriveeAller(?string $villeArriveeAller): static
    {
        $this->villeArriveeAller = $villeArriveeAller;
        return $this;
    }

    public function getVilleDepartRetour(): ?string
    {
        return $this->villeDepartRetour;
    }

    public function setVilleDepartRetour(?string $villeDepartRetour): static
    {
        $this->villeDepartRetour = $villeDepartRetour;
        return $this;
    }

    public function getVilleArriveeRetour(): ?string
    {
        return $this->villeArriveeRetour;
    }

    public function setVilleArriveeRetour(?string $villeArriveeRetour): static
    {
        $this->villeArriveeRetour = $villeArriveeRetour;
        return $this;
    }

    // ───── Getters/Setters Coordonnées Aller ─────

    public function getLatDepartAller(): ?float
    {
        return $this->latDepartAller;
    }

    public function setLatDepartAller(?float $latDepartAller): static
    {
        $this->latDepartAller = $latDepartAller;
        return $this;
    }

    public function getLonDepartAller(): ?float
    {
        return $this->lonDepartAller;
    }

    public function setLonDepartAller(?float $lonDepartAller): static
    {
        $this->lonDepartAller = $lonDepartAller;
        return $this;
    }

    public function getLatArriveeAller(): ?float
    {
        return $this->latArriveeAller;
    }

    public function setLatArriveeAller(?float $latArriveeAller): static
    {
        $this->latArriveeAller = $latArriveeAller;
        return $this;
    }

    public function getLonArriveeAller(): ?float
    {
        return $this->lonArriveeAller;
    }

    public function setLonArriveeAller(?float $lonArriveeAller): static
    {
        $this->lonArriveeAller = $lonArriveeAller;
        return $this;
    }

    // ───── Getters/Setters Coordonnées Retour ─────

    public function getLatDepartRetour(): ?float
    {
        return $this->latDepartRetour;
    }

    public function setLatDepartRetour(?float $latDepartRetour): static
    {
        $this->latDepartRetour = $latDepartRetour;
        return $this;
    }

    public function getLonDepartRetour(): ?float
    {
        return $this->lonDepartRetour;
    }

    public function setLonDepartRetour(?float $lonDepartRetour): static
    {
        $this->lonDepartRetour = $lonDepartRetour;
        return $this;
    }

    public function getLatArriveeRetour(): ?float
    {
        return $this->latArriveeRetour;
    }

    public function setLatArriveeRetour(?float $latArriveeRetour): static
    {
        $this->latArriveeRetour = $latArriveeRetour;
        return $this;
    }

    public function getLonArriveeRetour(): ?float
    {
        return $this->lonArriveeRetour;
    }

    public function setLonArriveeRetour(?float $lonArriveeRetour): static
    {
        $this->lonArriveeRetour = $lonArriveeRetour;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}

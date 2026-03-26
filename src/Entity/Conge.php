<?php

namespace App\Entity;

use App\Repository\CongeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CongeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Conge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'conges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(length: 50)]
    private ?string $typeConge = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $nombreJours = null;

    #[ORM\Column(length: 20, options: ['default' => 'en_attente'])]
    private string $statut = 'en_attente';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireGestionnaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateTraitement = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Employee $traitePar = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $demiJournee = false;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $periodeDemiJournee = null;

    #[ORM\PrePersist]
    public function setDateCreationValue(): void
    {
        $this->dateCreation = new \DateTime();
    }

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

    public function getTypeConge(): ?string
    {
        return $this->typeConge;
    }

    public function setTypeConge(string $typeConge): static
    {
        $this->typeConge = $typeConge;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getNombreJours(): ?string
    {
        return $this->nombreJours;
    }

    public function setNombreJours(string $nombreJours): static
    {
        $this->nombreJours = $nombreJours;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
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

    public function getCommentaireGestionnaire(): ?string
    {
        return $this->commentaireGestionnaire;
    }

    public function setCommentaireGestionnaire(?string $commentaireGestionnaire): static
    {
        $this->commentaireGestionnaire = $commentaireGestionnaire;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateTraitement(): ?\DateTimeInterface
    {
        return $this->dateTraitement;
    }

    public function setDateTraitement(?\DateTimeInterface $dateTraitement): static
    {
        $this->dateTraitement = $dateTraitement;
        return $this;
    }

    public function getTraitePar(): ?Employee
    {
        return $this->traitePar;
    }

    public function setTraitePar(?Employee $traitePar): static
    {
        $this->traitePar = $traitePar;
        return $this;
    }

    public function isDemiJournee(): bool
    {
        return $this->demiJournee;
    }

    public function setDemiJournee(bool $demiJournee): static
    {
        $this->demiJournee = $demiJournee;
        return $this;
    }

    public function getPeriodeDemiJournee(): ?string
    {
        return $this->periodeDemiJournee;
    }

    public function setPeriodeDemiJournee(?string $periodeDemiJournee): static
    {
        $this->periodeDemiJournee = $periodeDemiJournee;
        return $this;
    }

    // Méthodes utilitaires
    public function isEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function isApprouve(): bool
    {
        return $this->statut === 'approuve';
    }

    public function isRejete(): bool
    {
        return $this->statut === 'rejete';
    }

    public function getStatutLabel(): string
    {
        return match($this->statut) {
            'en_attente' => 'En attente',
            'approuve' => 'Approuvé',
            'rejete' => 'Rejeté',
            'annule' => 'Annulé',
            default => 'Inconnu'
        };
    }
}
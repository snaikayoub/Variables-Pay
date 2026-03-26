<?php

namespace App\Entity;

use App\Repository\PeriodePaieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PeriodePaieRepository::class)]
class PeriodePaie
{
    /**
     * Statuts possibles d'une période de paie
     */
    public const STATUT_INACTIF = 'Inactive';
    public const STATUT_OUVERT  = 'Ouverte';
    public const STATUT_FERME   = 'Fermée';
    public const STATUT_ARCHIVE = 'Archivée';

    // ...
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice(['mensuelle', 'quinzaine'])]
    private ?string $typePaie = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 12)]
    private ?int $mois = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Range(min: 2020, max: 2100)]
    private ?int $annee = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 2)]
    private ?int $quinzaine = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice([self::STATUT_INACTIF, self::STATUT_OUVERT, self::STATUT_FERME, self::STATUT_ARCHIVE])]
    private ?string $statut = null;

    /**
     * @var Collection<int, PrimePerformance>
     */
    #[ORM\OneToMany(targetEntity: PrimePerformance::class, mappedBy: 'periodePaie')]
    private Collection $primePerformances;

    /**
     * @var Collection<int, PrimeFonction>
     */
    #[ORM\OneToMany(targetEntity: PrimeFonction::class, mappedBy: 'periodePaie')]
    private Collection $primeFonctions;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 90, max: 110)]
    private ?string $scoreEquipe = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 90, max: 110)]
    private ?string $scoreCollectif = null;

    #[Assert\Callback]
    public function validateOpenPeriod(ExecutionContextInterface $context): void
    {
        if (self::STATUT_OUVERT !== $this->statut) {
            return;
        }

        if (null === $this->scoreEquipe || '' === trim((string) $this->scoreEquipe)) {
            $context->buildViolation('Le score equipe est obligatoire quand la periode est ouverte.')
                ->atPath('scoreEquipe')
                ->addViolation();
        }
        if (null === $this->scoreCollectif || '' === trim((string) $this->scoreCollectif)) {
            $context->buildViolation('Le score collectif est obligatoire quand la periode est ouverte.')
                ->atPath('scoreCollectif')
                ->addViolation();
        }
    }

    /**
     * @var Collection<int, VoyageDeplacement>
     */
    #[ORM\OneToMany(targetEntity: VoyageDeplacement::class, mappedBy: 'periodePaie')]
    private Collection $voyageDeplacements;


    public function __construct()
    {
        $this->primePerformances = new ArrayCollection();
        $this->primeFonctions = new ArrayCollection();
        $this->voyageDeplacements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypePaie(): ?string
    {
        return $this->typePaie;
    }

    public function setTypePaie(string $typePaie): static
    {
        $this->typePaie = $typePaie;

        return $this;
    }

    public function getMois(): ?int
    {
        return $this->mois;
    }

    public function setMois(int $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function getQuinzaine(): ?int
    {
        return $this->quinzaine;
    }

    public function setQuinzaine(?int $quinzaine): static
    {
        $this->quinzaine = $quinzaine;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * @return Collection<int, PrimePerformance>
     */
    public function getPrimePerformances(): Collection
    {
        return $this->primePerformances;
    }

    public function addPrimePerformance(PrimePerformance $primePerformance): static
    {
        if (!$this->primePerformances->contains($primePerformance)) {
            $this->primePerformances->add($primePerformance);
            $primePerformance->setPeriodePaie($this);
        }

        return $this;
    }

    public function removePrimePerformance(PrimePerformance $primePerformance): static
    {
        if ($this->primePerformances->removeElement($primePerformance)) {
            // set the owning side to null (unless already changed)
            if ($primePerformance->getPeriodePaie() === $this) {
                $primePerformance->setPeriodePaie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PrimeFonction>
     */
    public function getPrimeFonctions(): Collection
    {
        return $this->primeFonctions;
    }

    public function addPrimeFonction(PrimeFonction $primeFonction): static
    {
        if (!$this->primeFonctions->contains($primeFonction)) {
            $this->primeFonctions->add($primeFonction);
            $primeFonction->setPeriodePaie($this);
        }

        return $this;
    }

    public function removePrimeFonction(PrimeFonction $primeFonction): static
    {
        if ($this->primeFonctions->removeElement($primeFonction)) {
            if ($primeFonction->getPeriodePaie() === $this) {
                $primeFonction->setPeriodePaie(null);
            }
        }

        return $this;
    }

    /**
     * String representation of the PeriodePaie
     */
    public function __toString(): string
    {
        return $this->typePaie . ' - ' . $this->mois . '/' . $this->annee .
            ($this->quinzaine ? ' (Q' . $this->quinzaine . ')' : '');
    }

    public function getScoreEquipe(): ?string
    {
        return $this->scoreEquipe;
    }

    public function setScoreEquipe(?string $scoreEquipe): static
    {
        $this->scoreEquipe = $scoreEquipe;

        return $this;
    }

    public function getScoreCollectif(): ?string
    {
        return $this->scoreCollectif;
    }

    public function setScoreCollectif(?string $scoreCollectif): static
    {
        $this->scoreCollectif = $scoreCollectif;

        return $this;
    }

    /**
     * @return Collection<int, VoyageDeplacement>
     */
    public function getVoyageDeplacements(): Collection
    {
        return $this->voyageDeplacements;
    }

    public function addVoyageDeplacement(VoyageDeplacement $voyageDeplacement): static
    {
        if (!$this->voyageDeplacements->contains($voyageDeplacement)) {
            $this->voyageDeplacements->add($voyageDeplacement);
            $voyageDeplacement->setPeriodePaie($this);
        }

        return $this;
    }

    public function removeVoyageDeplacement(VoyageDeplacement $voyageDeplacement): static
    {
        if ($this->voyageDeplacements->removeElement($voyageDeplacement)) {
            // set the owning side to null (unless already changed)
            if ($voyageDeplacement->getPeriodePaie() === $this) {
                $voyageDeplacement->setPeriodePaie(null);
            }
        }

        return $this;
    }
}

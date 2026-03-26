<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $matricule = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\LessThan('today')]
    private ?\DateTimeInterface $date_naissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu_naissance = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $code_sexe = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $cin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $date_embauche = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    /**
     * @var Collection<int, EmployeeSituation>
     */
    #[ORM\OneToMany(targetEntity: EmployeeSituation::class, mappedBy: 'employee', orphanRemoval: true)]
    private Collection $employeeSituations;

    /**
     * @var Collection<int, PrimePerformance>
     */
    #[ORM\OneToMany(targetEntity: PrimePerformance::class, mappedBy: 'employee')]
    private Collection $primePerformances;

    /**
     * @var Collection<int, PrimeFonction>
     */
    #[ORM\OneToMany(targetEntity: PrimeFonction::class, mappedBy: 'employee')]
    private Collection $primeFonctions;

    #[ORM\ManyToOne(inversedBy: 'employees')]
    private ?GrpPerf $grpPerf = null;

    #[ORM\ManyToOne]
    private ?CategorieFonction $categorieFonction = null;

    /**
     * @var Collection<int, Conge>
     */
    #[ORM\OneToMany(targetEntity: Conge::class, mappedBy: 'employee')]
    private Collection $conges;

    /**
     * @var Collection<int, VoyageDeplacement>
     */
    #[ORM\OneToMany(targetEntity: VoyageDeplacement::class, mappedBy: 'employee')]
    private Collection $voyageDeplacements;

    public function __construct()
    {
        $this->employeeSituations = new ArrayCollection();
        $this->primePerformances = new ArrayCollection();
        $this->primeFonctions = new ArrayCollection();
        $this->conges = new ArrayCollection();
        $this->voyageDeplacements = new ArrayCollection();
    }

    public function getCurrentEmployeeSituation(): ?EmployeeSituation
    {
        $today = new \DateTimeImmutable('today');

        $current = null;
        foreach ($this->employeeSituations as $situation) {
            $startDate = $situation->getStartDate();
            $endDate = $situation->getEndDate();

            if (null === $startDate) {
                continue;
            }

            if ($startDate <= $today && (null === $endDate || $endDate >= $today)) {
                if (null === $current || $startDate > $current->getStartDate()) {
                    $current = $situation;
                }
            }
        }

        if (null !== $current) {
            return $current;
        }

        $latest = null;
        foreach ($this->employeeSituations as $situation) {
            $startDate = $situation->getStartDate();
            if (null === $startDate) {
                continue;
            }

            if (null === $latest || $startDate > $latest->getStartDate()) {
                $latest = $situation;
            }
        }

        return $latest;
    }

    public function getCurrentService(): ?Service
    {
        return $this->getCurrentEmployeeSituation()?->getService();
    }

    public function getCurrentServiceName(): string
    {
        return $this->getCurrentService()?->getNom() ?? '';
    }

    public function getCurrentCategory(): ?Category
    {
        return $this->getCurrentEmployeeSituation()?->getCategory();
    }

    public function getCurrentCategoryName(): string
    {
        return $this->getCurrentCategory()?->getCategoryName() ?? '';
    }

    public function getCategorieFonction(): ?CategorieFonction
    {
        return $this->categorieFonction;
    }

    public function setCategorieFonction(?CategorieFonction $categorieFonction): static
    {
        $this->categorieFonction = $categorieFonction;

        return $this;
    }

    public function getCategorieFonctionCode(): string
    {
        return $this->categorieFonction?->getCode() ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(\DateTimeInterface $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieu_naissance;
    }

    public function setLieuNaissance(?string $lieu_naissance): static
    {
        $this->lieu_naissance = $lieu_naissance;

        return $this;
    }

    public function getCodeSexe(): ?string
    {
        return $this->code_sexe;
    }

    public function setCodeSexe(string $code_sexe): static
    {
        $this->code_sexe = $code_sexe;

        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(string $cin): static
    {
        $this->cin = $cin;

        return $this;
    }

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->date_embauche;
    }

    public function setDateEmbauche(\DateTimeInterface $date_embauche): static
    {
        $this->date_embauche = $date_embauche;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * @return Collection<int, EmployeeSituation>
     */
    public function getEmployeeSituations(): Collection
    {
        return $this->employeeSituations;
    }

    public function addEmployeeSituation(EmployeeSituation $employeeSituation): static
    {
        if (!$this->employeeSituations->contains($employeeSituation)) {
            $this->employeeSituations->add($employeeSituation);
            $employeeSituation->setEmployee($this);
        }

        return $this;
    }

    public function removeEmployeeSituation(EmployeeSituation $employeeSituation): static
    {
        if ($this->employeeSituations->removeElement($employeeSituation)) {
            // set the owning side to null (unless already changed)
            if ($employeeSituation->getEmployee() === $this) {
                $employeeSituation->setEmployee(null);
            }
        }

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
            $primePerformance->setEmployee($this);
        }

        return $this;
    }

    public function removePrimePerformance(PrimePerformance $primePerformance): static
    {
        if ($this->primePerformances->removeElement($primePerformance)) {
            // set the owning side to null (unless already changed)
            if ($primePerformance->getEmployee() === $this) {
                $primePerformance->setEmployee(null);
            }
        }

        return $this;
    }
    public function __toString(): string
    {
        return $this->getMatricule() . ' - ' . $this->getNom() . ' ' . $this->getPrenom();
    }

    public function getGrpPerf(): ?GrpPerf
    {
        return $this->grpPerf;
    }

    public function setGrpPerf(?GrpPerf $grpPerf): static
    {
        $this->grpPerf = $grpPerf;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->getNom() . ' ' . $this->getPrenom();
    }

    /**
     * @return Collection<int, Conge>
     */
    public function getConges(): Collection
    {
        return $this->conges;
    }

    public function addConge(Conge $conge): static
    {
        if (!$this->conges->contains($conge)) {
            $this->conges->add($conge);
            $conge->setEmployee($this);
        }

        return $this;
    }

    public function removeConge(Conge $conge): static
    {
        if ($this->conges->removeElement($conge)) {
            // set the owning side to null (unless already changed)
            if ($conge->getEmployee() === $this) {
                $conge->setEmployee(null);
            }
        }

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
            $voyageDeplacement->setEmployee($this);
        }

        return $this;
    }

    public function removeVoyageDeplacement(VoyageDeplacement $voyageDeplacement): static
    {
        if ($this->voyageDeplacements->removeElement($voyageDeplacement)) {
            // set the owning side to null (unless already changed)
            if ($voyageDeplacement->getEmployee() === $this) {
                $voyageDeplacement->setEmployee(null);
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
            $primeFonction->setEmployee($this);
        }

        return $this;
    }

    public function removePrimeFonction(PrimeFonction $primeFonction): static
    {
        if ($this->primeFonctions->removeElement($primeFonction)) {
            if ($primeFonction->getEmployee() === $this) {
                $primeFonction->setEmployee(null);
            }
        }

        return $this;
    }

}

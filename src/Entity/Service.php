<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'managedServices')]
    #[ORM\JoinTable(name: 'service_gestionnaire')]
    private Collection $gestionnaire;

    #[ORM\ManyToOne(inversedBy: 'validatedServices')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validateurService = null;

    #[ORM\ManyToOne(inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Division $division = null;

    /**
     * @var Collection<int, EmployeeSituation>
     */
    #[ORM\OneToMany(targetEntity: EmployeeSituation::class, mappedBy: 'service')]
    private Collection $employeeSituations;

    public function __construct()
    {
        $this->gestionnaire = new ArrayCollection();
        $this->employeeSituations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, User>
     */
    public function getGestionnaire(): Collection
    {
        return $this->gestionnaire;
    }

    public function addGestionnaire(User $gestionnaire): static
    {
        if (!$this->gestionnaire->contains($gestionnaire)) {
            $this->gestionnaire->add($gestionnaire);
        }

        return $this;
    }

    public function removeGestionnaire(User $gestionnaire): static
    {
        $this->gestionnaire->removeElement($gestionnaire);

        return $this;
    }

    public function getValidateurService(): ?User
    {
        return $this->validateurService;
    }

    public function setValidateurService(?User $validateurService): static
    {
        $this->validateurService = $validateurService;

        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): static
    {
        $this->division = $division;

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
            $employeeSituation->setService($this);
        }

        return $this;
    }

    public function removeEmployeeSituation(EmployeeSituation $employeeSituation): static
    {
        if ($this->employeeSituations->removeElement($employeeSituation)) {
            // set the owning side to null (unless already changed)
            if ($employeeSituation->getService() === $this) {
                $employeeSituation->setService(null);
            }
        }

        return $this;
    }
    
    /**
     * Returns an array of gestionnaire names
     */
    public function getGestionnairesNamesArray(): array
    {
        return $this->gestionnaire->map(fn($u) => $u->getFullName())->toArray();
    }
    
    /**
     * Returns a comma-separated string of gestionnaire names
     */
    public function getGestionnairesNames(): string
    {
        $names = $this->getGestionnairesNamesArray();
        return !empty($names) ? implode(', ', $names) : '';
    }
    
    public function __toString(): string
    {
        return $this->nom ?? 'Unnamed Service';
    }
}
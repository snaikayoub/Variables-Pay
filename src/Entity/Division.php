<?php

namespace App\Entity;

use App\Repository\DivisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DivisionRepository::class)]
class Division
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * @var Collection<int, Service>
     */
    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'division')]
    private Collection $services;

    #[ORM\ManyToOne(inversedBy: 'validatedDivisions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $validateurDivision = null;

    public function __construct()
    {
        $this->services = new ArrayCollection();
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
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setDivision($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getDivision() === $this) {
                $service->setDivision(null);
            }
        }

        return $this;
    }

    public function getValidateurDivision(): ?User
    {
        return $this->validateurDivision;
    }

    public function setValidateurDivision(?User $validateurDivision): static
    {
        $this->validateurDivision = $validateurDivision;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}

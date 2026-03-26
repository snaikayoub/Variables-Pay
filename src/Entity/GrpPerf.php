<?php

namespace App\Entity;

use App\Repository\GrpPerfRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GrpPerfRepository::class)]
class GrpPerf
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nameGrp = null;

    /**
     * @var Collection<int, Employee>
     */
    #[ORM\OneToMany(targetEntity: Employee::class, mappedBy: 'grpPerf')]
    private Collection $employees;

    /**
     * @var Collection<int, CategoryTM>
     */
    #[ORM\OneToMany(targetEntity: CategoryTM::class, mappedBy: 'grpPerf')]
    private Collection $categoryTMs;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
        $this->categoryTMs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameGrp(): ?string
    {
        return $this->nameGrp;
    }

    public function setNameGrp(string $nameGrp): static
    {
        $this->nameGrp = $nameGrp;

        return $this;
    }

    /**
     * @return Collection<int, Employee>
     */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(Employee $employee): static
    {
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
            $employee->setGrpPerf($this);
        }

        return $this;
    }

    public function removeEmployee(Employee $employee): static
    {
        if ($this->employees->removeElement($employee)) {
            // set the owning side to null (unless already changed)
            if ($employee->getGrpPerf() === $this) {
                $employee->setGrpPerf(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CategoryTM>
     */
    public function getCategoryTMs(): Collection
    {
        return $this->categoryTMs;
    }

    public function addCategoryTM(CategoryTM $categoryTM): static
    {
        if (!$this->categoryTMs->contains($categoryTM)) {
            $this->categoryTMs->add($categoryTM);
            $categoryTM->setGrpPerf($this);
        }

        return $this;
    }

    public function removeCategoryTM(CategoryTM $categoryTM): static
    {
        if ($this->categoryTMs->removeElement($categoryTM)) {
            // set the owning side to null (unless already changed)
            if ($categoryTM->getGrpPerf() === $this) {
                $categoryTM->setGrpPerf(null);
            }
        }

        return $this;
    }
    public function __toString()
    {
        return $this->nameGrp ?? 'No Group Name';
    }
}

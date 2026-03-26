<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $categoryName = null;

    /**
     * @var Collection<int, EmployeeSituation>
     */
    #[ORM\OneToMany(targetEntity: EmployeeSituation::class, mappedBy: 'category')]
    private Collection $employeeSituations;

    /**
     * @var Collection<int, CategoryTM>
     */
    #[ORM\OneToMany(targetEntity: CategoryTM::class, mappedBy: 'category')]
    private Collection $categoryTMs;

    public function __construct()
    {
        $this->employeeSituations = new ArrayCollection();
        $this->categoryTMs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $categoryName): static
    {
        $this->categoryName = $categoryName;

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
            $employeeSituation->setCategory($this);
        }

        return $this;
    }

    public function removeEmployeeSituation(EmployeeSituation $employeeSituation): static
    {
        if ($this->employeeSituations->removeElement($employeeSituation)) {
            // set the owning side to null (unless already changed)
            if ($employeeSituation->getCategory() === $this) {
                $employeeSituation->setCategory(null);
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
            $categoryTM->setCategory($this);
        }

        return $this;
    }

    public function removeCategoryTM(CategoryTM $categoryTM): static
    {
        if ($this->categoryTMs->removeElement($categoryTM)) {
            // set the owning side to null (unless already changed)
            if ($categoryTM->getCategory() === $this) {
                $categoryTM->setCategory(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->categoryName ?? 'No Category Name';
    }
}

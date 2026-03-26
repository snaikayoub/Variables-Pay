<?php

namespace App\Entity;

use App\Repository\CategoryTMRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryTMRepository::class)]
#[ORM\UniqueConstraint(columns: ["category_id","grp_perf_id"])]
class CategoryTM
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'categoryTMs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'categoryTMs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GrpPerf $grpPerf = null;

    #[ORM\Column]
    #[Assert\Range(min: 5, max: 30)]
    private ?float $TM = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
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

    public function getTM(): ?float
    {
        return $this->TM;
    }

    public function setTM(float $TM): static
    {
        $this->TM = $TM;

        return $this;
    }
}

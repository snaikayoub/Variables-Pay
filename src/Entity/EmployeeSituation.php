<?php

namespace App\Entity;

use App\Repository\EmployeeSituationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeeSituationRepository::class)]
class EmployeeSituation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'employeeSituations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    private ?string $natureChangement = null;

    #[ORM\Column(length: 255)]
    private ?string $grade = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'employeeSituations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(length: 255)]
    private ?string $sitFamiliale = null;

    #[ORM\Column]
    private ?int $enf = null;

    #[ORM\Column]
    private ?int $enf_charge = null;

    #[ORM\Column(nullable: true)]
    private ?float $tauxHoraire = null;

    #[ORM\Column(length: 255)]
    private ?string $type_paie = null;

    #[ORM\ManyToOne(inversedBy: 'employeeSituations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getNatureChangement(): ?string
    {
        return $this->natureChangement;
    }

    public function setNatureChangement(string $natureChangement): static
    {
        $this->natureChangement = $natureChangement;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(string $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }
    
    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getSitFamiliale(): ?string
    {
        return $this->sitFamiliale;
    }

    public function setSitFamiliale(string $sitFamiliale): static
    {
        $this->sitFamiliale = $sitFamiliale;

        return $this;
    }

    public function getEnf(): ?int
    {
        return $this->enf;
    }

    public function setEnf(int $enf): static
    {
        $this->enf = $enf;

        return $this;
    }

    public function getEnfCharge(): ?int
    {
        return $this->enf_charge;
    }

    public function setEnfCharge(int $enf_charge): static
    {
        $this->enf_charge = $enf_charge;

        return $this;
    }

    public function getTauxHoraire(): ?float
    {
        return $this->tauxHoraire;
    }

    public function setTauxHoraire(?float $tauxHoraire): static
    {
        $this->tauxHoraire = $tauxHoraire;

        return $this;
    }

    public function getTypePaie(): ?string
    {
        return $this->type_paie;
    }

    public function setTypePaie(string $type_paie): static
    {
        $this->type_paie = $type_paie;

        return $this;
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
    public function getTauxPP(): ?float
{
    $category = $this->getCategory();
    $grpPerf = $this->getEmployee()?->getGrpPerf();

    if (!$category || !$grpPerf) {
        return null;
    }

    foreach ($category->getCategoryTMs() as $catTM) {
        if ($catTM->getGrpPerf() === $grpPerf) {
            return $catTM->getTM();
        }
    }

    return null;
}
    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%s)',
            $this->getEmployee()->getFullName(),
            $this->getNatureChangement(),
            $this->getStartDate()->format('Y-m-d')
        );
    }
}
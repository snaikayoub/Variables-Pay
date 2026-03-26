<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['code'])]
class CategorieFonction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $code = null;

    #[ORM\Column]
    private ?float $tauxMonetaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getTauxMonetaire(): ?float
    {
        return $this->tauxMonetaire;
    }

    public function setTauxMonetaire(float $tauxMonetaire): static
    {
        $this->tauxMonetaire = $tauxMonetaire;

        return $this;
    }

    public function __toString(): string
    {
        return $this->code ?? 'CategorieFonction';
    }
}

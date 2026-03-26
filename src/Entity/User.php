<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    /**
     * @var Collection<int, Service>
     */
    #[ORM\ManyToMany(targetEntity: Service::class, mappedBy: 'gestionnaire')]
    private Collection $managedServices;

    /**
     * @var Collection<int, Service>
     */
    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'validateurService')]
    private Collection $validatedServices;

    /**
     * @var Collection<int, Division>
     */
    #[ORM\OneToMany(targetEntity: Division::class, mappedBy: 'validateurDivision')]
    private Collection $validatedDivisions;

    public function __construct()
    {
        $this->managedServices = new ArrayCollection();
        $this->validatedServices = new ArrayCollection();
        $this->validatedDivisions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getManagedServices(): Collection
    {
        return $this->managedServices;
    }

    public function addManagedService(Service $service): static
    {
        if (!$this->managedServices->contains($service)) {
            $this->managedServices->add($service);
            $service->addGestionnaire($this);
        }

        return $this;
    }

    public function removeManagedService(Service $service): static
    {
        if ($this->managedServices->removeElement($service)) {
            $service->removeGestionnaire($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getValidatedServices(): Collection
    {
        return $this->validatedServices;
    }

    public function addValidatedService(Service $validatedService): static
    {
        if (!$this->validatedServices->contains($validatedService)) {
            $this->validatedServices->add($validatedService);
            $validatedService->setValidateurService($this);
        }

        return $this;
    }

    public function removeValidatedService(Service $validatedService): static
    {
        if ($this->validatedServices->removeElement($validatedService)) {
            // set the owning side to null (unless already changed)
            if ($validatedService->getValidateurService() === $this) {
                $validatedService->setValidateurService(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Division>
     */
    public function getValidatedDivisions(): Collection
    {
        return $this->validatedDivisions;
    }

    public function addValidatedDivision(Division $validatedDivision): static
    {
        if (!$this->validatedDivisions->contains($validatedDivision)) {
            $this->validatedDivisions->add($validatedDivision);
            $validatedDivision->setValidateurDivision($this);
        }

        return $this;
    }

    public function removeValidatedDivision(Division $validatedDivision): static
    {
        if ($this->validatedDivisions->removeElement($validatedDivision)) {
            // set the owning side to null (unless already changed)
            if ($validatedDivision->getValidateurDivision() === $this) {
                $validatedDivision->setValidateurDivision(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->fullName;
    }
}

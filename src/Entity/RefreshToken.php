<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\UniqueConstraint(name: 'UNIQ_REFRESH_TOKEN', columns: ['refresh_token'])]
class RefreshToken implements RefreshTokenInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'refresh_token', length: 128, unique: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $valid = null;

    public static function createForUserWithTtl(string $refreshToken, UserInterface $user, int $ttl): static
    {
        $valid = new DateTime();

        if ($ttl > 0) {
            $valid->modify('+'.$ttl.' seconds');
        } elseif ($ttl < 0) {
            $valid->modify($ttl.' seconds');
        }

        $model = new static();
        $model->setRefreshToken($refreshToken);
        $model->setUsername(method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : $user->getUsername());
        $model->setValid($valid);

        return $model;
    }

    public function __toString(): string
    {
        return !in_array($this->getRefreshToken(), [null, '', '0'], true) ? (string) $this->getRefreshToken() : '';
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setRefreshToken(string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setValid(DateTimeInterface $valid): static
    {
        $this->valid = $valid;

        return $this;
    }

    public function getValid(): ?DateTimeInterface
    {
        return $this->valid;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function isValid(): bool
    {
        return null !== $this->valid && $this->valid >= new DateTime();
    }
}

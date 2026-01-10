<?php

namespace App\Entity;

use App\Repository\ShareInvitationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: ShareInvitationRepository::class)]
class ShareInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $token = null;

    #[ORM\ManyToOne(inversedBy: 'shareInvitations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?Trip $trip = null;

    #[ORM\ManyToOne(inversedBy: 'shareInvitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $userToShareWith = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expireAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): static
    {
        $this->trip = $trip;

        return $this;
    }

    public function getUserToShareWith(): ?User
    {
        return $this->userToShareWith;
    }

    public function setUserToShareWith(?User $userToShareWith): static
    {
        $this->userToShareWith = $userToShareWith;

        return $this;
    }

    public function getExpireAt(): ?\DateTimeImmutable
    {
        return $this->expireAt;
    }

    public function setExpireAt(\DateTimeImmutable $expireAt): static
    {
        $this->expireAt = $expireAt;

        return $this;
    }
}

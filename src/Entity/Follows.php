<?php

namespace App\Entity;

use App\Repository\FollowsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowsRepository::class)]
class Follows
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'follows')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $followerId = null;

    #[ORM\ManyToOne(inversedBy: 'followedBy')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $followedId = null;

    #[ORM\Column]
    private ?bool $isApproved = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollowerId(): ?User
    {
        return $this->followerId;
    }

    public function setFollowerId(?User $followerId): static
    {
        $this->followerId = $followerId;

        return $this;
    }

    public function getFollowedId(): ?User
    {
        return $this->followedId;
    }

    public function setFollowedId(?User $followedId): static
    {
        $this->followedId = $followedId;

        return $this;
    }

    public function isIsApproved(): ?bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}

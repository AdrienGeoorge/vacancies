<?php

namespace App\Entity;

use App\Repository\VariousExpensiveRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VariousExpensiveRepository::class)]
class VariousExpensive
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?bool $paid = null;

    #[ORM\Column]
    private ?bool $perPerson = null;

    #[ORM\ManyToOne(inversedBy: 'variousExpensives')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trip $trip = null;

    #[ORM\ManyToOne]
    private ?TripTraveler $payedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): static
    {
        $this->paid = $paid;

        return $this;
    }

    public function isPerPerson(): ?bool
    {
        return $this->perPerson;
    }

    public function setPerPerson(bool $perPerson): static
    {
        $this->perPerson = $perPerson;

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

    public function getPayedBy(): ?TripTraveler
    {
        return $this->payedBy;
    }

    public function setPayedBy(?TripTraveler $payedBy): static
    {
        $this->payedBy = $payedBy;

        return $this;
    }
}

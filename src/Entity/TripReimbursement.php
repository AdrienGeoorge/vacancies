<?php

namespace App\Entity;

use App\Repository\TripReimbursementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: TripReimbursementRepository::class)]
class TripReimbursement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reimbursements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?Trip $trip = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TripTraveler $fromTraveler = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TripTraveler $toTraveler = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $date = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFromTraveler(): ?TripTraveler
    {
        return $this->fromTraveler;
    }

    public function setFromTraveler(?TripTraveler $fromTraveler): static
    {
        $this->fromTraveler = $fromTraveler;

        return $this;
    }

    public function getToTraveler(): ?TripTraveler
    {
        return $this->toTraveler;
    }

    public function setToTraveler(?TripTraveler $toTraveler): static
    {
        $this->toTraveler = $toTraveler;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }
}

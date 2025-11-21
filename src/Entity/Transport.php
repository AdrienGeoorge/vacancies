<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TransportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity(repositoryClass: TransportRepository::class)]
class Transport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $departure = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $departureDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $arrivalDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(nullable: true)]
    private ?int $subscriptionDuration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column]
    private bool $paid = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransportType $type = null;

    #[ORM\ManyToOne(inversedBy: 'transports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trip $trip = null;

    #[ORM\Column(nullable: true)]
    private ?float $estimatedToll = null;

    #[ORM\Column(nullable: true)]
    private ?float $estimatedGasoline = null;

    #[ORM\Column]
    private ?bool $perPerson = null;

    #[ORM\ManyToOne]
    private ?TripTraveler $payedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeparture(): ?string
    {
        return $this->departure;
    }

    public function setDeparture(?string $departure): static
    {
        $this->departure = $departure;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDepartureDate(): ?\DateTimeInterface
    {
        return $this->departureDate;
    }

    public function setDepartureDate(?\DateTimeInterface $departureDate): static
    {
        $this->departureDate = $departureDate;

        return $this;
    }

    public function getArrivalDate(): ?\DateTimeInterface
    {
        return $this->arrivalDate;
    }

    public function setArrivalDate(?\DateTimeInterface $arrivalDate): static
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getSubscriptionDuration(): ?int
    {
        return $this->subscriptionDuration;
    }

    public function setSubscriptionDuration(?int $subscriptionDuration): static
    {
        $this->subscriptionDuration = $subscriptionDuration;

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

    public function isPaid(): bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): static
    {
        $this->paid = $paid;

        return $this;
    }

    public function getType(): ?TransportType
    {
        return $this->type;
    }

    public function setType(?TransportType $type): static
    {
        $this->type = $type;

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

    public function getEstimatedToll(): ?float
    {
        return $this->estimatedToll;
    }

    public function setEstimatedToll(?float $estimatedToll): static
    {
        $this->estimatedToll = $estimatedToll;

        return $this;
    }

    public function getEstimatedGasoline(): ?float
    {
        return $this->estimatedGasoline;
    }

    public function setEstimatedGasoline(?float $estimatedGasoline): static
    {
        $this->estimatedGasoline = $estimatedGasoline;

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

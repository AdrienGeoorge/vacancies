<?php

namespace App\Entity;

use App\Repository\AccommodationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccommodationRepository::class)]
class Accommodation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private bool $booked = false;

    #[ORM\ManyToOne(inversedBy: 'accomodations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trip $trip = null;

    #[ORM\Column(nullable: true)]
    private ?float $deposit = null;

    #[ORM\OneToMany(targetEntity: AccommodationAdditional::class, mappedBy: 'accommodation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $additionalExpensive;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $arrivalDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $departureDate = null;

    public function __construct()
    {
        $this->additionalExpensive = new ArrayCollection();
    }

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

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

    public function isBooked(): bool
    {
        return $this->booked;
    }

    public function setBooked(bool $booked): static
    {
        $this->booked = $booked;

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

    public function getDeposit(): ?float
    {
        return $this->deposit;
    }

    public function setDeposit(?float $deposit): static
    {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * @return Collection<int, AccommodationAdditional>
     */
    public function getAdditionalExpensive(): Collection
    {
        return $this->additionalExpensive;
    }

    public function addAdditionalExpensive(AccommodationAdditional $AdditionalExpensive): static
    {
        if (!$this->additionalExpensive->contains($AdditionalExpensive)) {
            $this->additionalExpensive->add($AdditionalExpensive);
            $AdditionalExpensive->setAccommodation($this);
        }

        return $this;
    }

    public function removeAdditionalExpensive(AccommodationAdditional $AdditionalExpensive): static
    {
        if ($this->additionalExpensive->removeElement($AdditionalExpensive)) {
            // set the owning side to null (unless already changed)
            if ($AdditionalExpensive->getAccommodation() === $this) {
                $AdditionalExpensive->setAccommodation(null);
            }
        }

        return $this;
    }

    public function getTotalPrice()
    {
        $total = $this->getPrice();
        foreach ($this->additionalExpensive as $item) $total += $item->getPrice();
        return $total;
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

    public function getDepartureDate(): ?\DateTimeInterface
    {
        return $this->departureDate;
    }

    public function setDepartureDate(?\DateTimeInterface $departureDate): static
    {
        $this->departureDate = $departureDate;

        return $this;
    }
}

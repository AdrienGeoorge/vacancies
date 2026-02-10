<?php

namespace App\Entity;

use App\Repository\AccommodationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: AccommodationRepository::class)]
class Accommodation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
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

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $originalPrice = null;

    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(referencedColumnName: 'code', nullable: true)]
    private ?Currency $originalCurrency = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $convertedPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
    private ?string $exchangeRate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $convertedAt = null;

    #[ORM\Column]
    private bool $booked = false;

    #[ORM\ManyToOne(inversedBy: 'accommodations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?Trip $trip = null;

    #[ORM\Column(nullable: true)]
    private ?float $deposit = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $originalDeposit = null;

    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(referencedColumnName: 'code', nullable: true)]
    private ?Currency $originalDepositCurrency = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $convertedDeposit = null;

    #[ORM\OneToMany(targetEntity: AccommodationAdditional::class, mappedBy: 'accommodation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $additionalExpensive;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $arrivalDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $departureDate = null;

    #[ORM\ManyToOne]
    private ?TripTraveler $payedBy = null;

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

    public function getOriginalPrice(): ?string
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(?string $originalPrice): self
    {
        $this->originalPrice = $originalPrice;
        return $this;
    }

    public function getOriginalCurrency(): ?Currency
    {
        return $this->originalCurrency;
    }

    public function setOriginalCurrency(?Currency $originalCurrency): self
    {
        $this->originalCurrency = $originalCurrency;
        return $this;
    }

    public function getConvertedPrice(): ?string
    {
        return $this->convertedPrice;
    }

    public function setConvertedPrice(?string $convertedPrice): self
    {
        $this->convertedPrice = $convertedPrice;
        return $this;
    }

    public function getExchangeRate(): ?string
    {
        return $this->exchangeRate;
    }

    public function setExchangeRate(?string $exchangeRate): self
    {
        $this->exchangeRate = $exchangeRate;
        return $this;
    }

    public function getConvertedAt(): ?\DateTimeImmutable
    {
        return $this->convertedAt;
    }

    public function setConvertedAt(?\DateTimeImmutable $convertedAt): self
    {
        $this->convertedAt = $convertedAt;
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

    public function getOriginalDeposit(): ?string
    {
        return $this->originalDeposit;
    }

    public function setOriginalDeposit(?string $originalDeposit): static
    {
        $this->originalDeposit = $originalDeposit;

        return $this;
    }

    public function getOriginalDepositCurrency(): ?Currency
    {
        return $this->originalDepositCurrency;
    }

    public function setOriginalDepositCurrency(?Currency $originalDepositCurrency): static
    {
        $this->originalDepositCurrency = $originalDepositCurrency;

        return $this;
    }

    public function getConvertedDeposit(): ?string
    {
        return $this->convertedDeposit;
    }

    public function setConvertedDeposit(?string $convertedDeposit): static
    {
        $this->convertedDeposit = $convertedDeposit;

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

    public function getTotalPrice(): float
    {
        $total = 0;

        // Prix de l'hébergement
        $total += $this->getOriginalCurrency()?->getCode() !== 'EUR'
            ? $this->getConvertedPrice()
            : $this->getOriginalPrice();

        // Caution
        if ($this->getOriginalDeposit()) {
            $total += $this->getOriginalDepositCurrency()?->getCode() !== 'EUR'
                ? $this->getConvertedDeposit()
                : $this->getOriginalDeposit();
        }

        // Dépenses additionnelles
        foreach ($this->additionalExpensive as $item) {
            $total += $item->getOriginalCurrency()?->getCode() !== 'EUR'
                ? $item->getConvertedPrice()
                : $item->getOriginalPrice();
        }

        return round($total, 2);
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

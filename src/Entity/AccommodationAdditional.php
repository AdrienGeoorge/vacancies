<?php

namespace App\Entity;

use App\Repository\AccommodationAdditionalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: AccommodationAdditionalRepository::class)]
class AccommodationAdditional
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'additionalExpensive')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?Accommodation $accommodation = null;

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

    public function getAccommodation(): ?Accommodation
    {
        return $this->accommodation;
    }

    public function setAccommodation(?Accommodation $accommodation): static
    {
        $this->accommodation = $accommodation;

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
}

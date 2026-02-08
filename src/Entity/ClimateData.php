<?php

namespace App\Entity;

use App\Repository\ClimateDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClimateDataRepository::class)]
#[ORM\Table(name: 'climate_data')]
#[ORM\UniqueConstraint(name: 'unique_city_month', columns: ['city', 'month'])]
class ClimateData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $city = null;

    #[ORM\Column(type: 'integer')]
    private ?int $month = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $tempMinAvg = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $tempMaxAvg = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 1, nullable: true)]
    private ?string $precipitationMm = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rainyDays = null;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 1, nullable: true)]
    private ?string $sunshineHours = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $humidityAvg = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $lastUpdated = null;

    public function __construct()
    {
        $this->lastUpdated = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): self
    {
        $this->month = $month;
        return $this;
    }

    public function getTempMinAvg(): ?float
    {
        return $this->tempMinAvg !== null ? (float) $this->tempMinAvg : null;
    }

    public function setTempMinAvg(?float $tempMinAvg): self
    {
        $this->tempMinAvg = $tempMinAvg !== null ? (string) $tempMinAvg : null;
        return $this;
    }

    public function getTempMaxAvg(): ?float
    {
        return $this->tempMaxAvg !== null ? (float) $this->tempMaxAvg : null;
    }

    public function setTempMaxAvg(?float $tempMaxAvg): self
    {
        $this->tempMaxAvg = $tempMaxAvg !== null ? (string) $tempMaxAvg : null;
        return $this;
    }

    public function getPrecipitationMm(): ?float
    {
        return $this->precipitationMm !== null ? (float) $this->precipitationMm : null;
    }

    public function setPrecipitationMm(?float $precipitationMm): self
    {
        $this->precipitationMm = $precipitationMm !== null ? (string) $precipitationMm : null;
        return $this;
    }

    public function getRainyDays(): ?int
    {
        return $this->rainyDays;
    }

    public function setRainyDays(?int $rainyDays): self
    {
        $this->rainyDays = $rainyDays;
        return $this;
    }

    public function getSunshineHours(): ?float
    {
        return $this->sunshineHours !== null ? (float) $this->sunshineHours : null;
    }

    public function setSunshineHours(?float $sunshineHours): self
    {
        $this->sunshineHours = $sunshineHours !== null ? (string) $sunshineHours : null;
        return $this;
    }

    public function getHumidityAvg(): ?int
    {
        return $this->humidityAvg;
    }

    public function setHumidityAvg(?int $humidityAvg): self
    {
        $this->humidityAvg = $humidityAvg;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }
}
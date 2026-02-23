<?php

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CityRepository::class)]
#[ORM\Table(name: 'cities')]
#[ORM\UniqueConstraint(name: 'unique_city_country', columns: ['name', 'country'])]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private ?string $longitude = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude !== null ? (float) $this->latitude : null;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = (string) $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude !== null ? (float) $this->longitude : null;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = (string) $longitude;
        return $this;
    }
}

<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AccommodationRequestDTO
{
    #[Assert\NotBlank(message: 'Le nom de l\'hébergement est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le nom del\'hébergement doit faire au minimum 5 caractères.',
        maxMessage: 'Le nom de l\'hébergement doit faire au maximum 255 caractères.'
    )]
    public string $name;

    #[Assert\NotBlank(message: 'L\'adresse postale est obligatoire.')]
    public string $address;

    public ?string $zipCode;

    public ?string $city;

    public ?string $country;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $arrivalDate;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $departureDate;

    public ?string $description;

    #[Assert\NotBlank(message: 'Le prix de l\'hébergement est obligatoire.')]
    public float $price;

    public ?float $deposit;
    public array $additionalExpensive = [];
}
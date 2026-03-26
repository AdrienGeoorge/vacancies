<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class AccommodationRequestDTO
{
    #[Assert\NotBlank(message: 'Le nom de l\'hébergement est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le nom de l\'hébergement doit faire au minimum 5 caractères.',
        maxMessage: 'Le nom de l\'hébergement doit faire au maximum 255 caractères.'
    )]
    #[NoHtml]
    public string $name;

    #[Assert\NotBlank(message: 'L\'adresse postale est obligatoire.')]
    #[NoHtml]
    public string $address;

    #[NoHtml]
    public ?string $zipCode;

    #[Assert\NotBlank(message: 'La ville est obligatoire.')]
    #[NoHtml]
    public string $city;

    #[Assert\NotBlank(message: 'Le pays est obligatoire.')]
    #[NoHtml]
    public string $country;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $arrivalDate;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $departureDate;

    #[NoHtml]
    public ?string $description;

    #[Assert\NotBlank(message: 'Le prix de l\'hébergement est obligatoire.')]
    #[Assert\GreaterThan(0, message: 'Le prix doit être de minimum 1€.')]
    public float $originalPrice;

    #[Assert\NotBlank(message: 'Vous devez choisir une devise.')]
    public ?Currency $originalCurrency = null;

    public ?float $originalDeposit;

    public ?Currency $originalDepositCurrency = null;

    public array $additionalExpensive = [];
}

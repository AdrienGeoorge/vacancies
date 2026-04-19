<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class AccommodationRequestDTO
{
    #[Assert\NotBlank(message: 'accommodation.name.not_blank')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'accommodation.name.min_length',
        maxMessage: 'accommodation.name.max_length'
    )]
    #[NoHtml]
    public string $name;

    #[Assert\NotBlank(message: 'accommodation.address.not_blank')]
    #[NoHtml]
    public string $address;

    #[NoHtml]
    public ?string $zipCode;

    #[Assert\NotBlank(message: 'accommodation.city.not_blank')]
    #[NoHtml]
    public string $city;

    #[Assert\NotBlank(message: 'accommodation.country.not_blank')]
    #[NoHtml]
    public string $country;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $arrivalDate;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $departureDate;

    #[NoHtml]
    public ?string $description;

    #[Assert\NotBlank(message: 'accommodation.price.not_blank')]
    #[Assert\GreaterThan(0, message: 'accommodation.price.min')]
    public float $originalPrice;

    #[Assert\NotBlank(message: 'accommodation.currency.not_blank')]
    public ?Currency $originalCurrency = null;

    public ?float $originalDeposit;

    public ?Currency $originalDepositCurrency = null;

    public array $additionalExpensive = [];
}

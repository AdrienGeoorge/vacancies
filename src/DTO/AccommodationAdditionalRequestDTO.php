<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class AccommodationAdditionalRequestDTO
{
    public ?int $id = null;

    #[Assert\NotBlank(message: 'accommodation.additional.name.not_blank')]
    #[NoHtml]
    public string $name;

    #[Assert\NotBlank(message: 'accommodation.additional.price.not_blank')]
    public float $originalPrice;

    #[Assert\NotBlank(message: 'accommodation.additional.currency.not_blank')]
    public ?Currency $originalCurrency = null;
}

<?php

namespace App\DTO;

use App\Entity\Currency;
use Symfony\Component\Validator\Constraints as Assert;

class AccommodationAdditionalRequestDTO
{
    public ?int $id = null;

    #[Assert\NotBlank(message: 'Le type de dépense additionnelle est obligatoire.')]
    public string $name;

    #[Assert\NotBlank(message: 'Le prix de la dépense additionnelle est obligatoire.')]
    public float $originalPrice;

    #[Assert\NotBlank(message: 'Vous devez choisir une devise pour votre dépense additionnelle.')]
    public ?Currency $originalCurrency = null;
}
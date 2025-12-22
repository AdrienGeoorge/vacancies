<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AccommodationAdditionalRequestDTO
{
    public ?int $id = null;

    #[Assert\NotBlank(message: 'Le type de dépense additionnelle est obligatoire.')]
    public string $name;

    #[Assert\NotBlank(message: 'Le prix de la dépense additionnelle est obligatoire.')]
    public float $price;
}
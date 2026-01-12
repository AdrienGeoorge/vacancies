<?php

namespace App\DTO;

use App\Entity\Currency;
use Symfony\Component\Validator\Constraints as Assert;

class VariousExpensiveRequestDTO
{
    public function __construct(?Currency $originalCurrency)
    {
        $this->originalCurrency = $originalCurrency;
    }

    #[Assert\NotBlank(message: 'Le nom de la dépense est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le nom de la dépense doit faire au minimum 5 caractères.',
        maxMessage: 'Le nom de la dépense doit faire au maximum 255 caractères.'
    )]
    public ?string $name;

    public ?string $description;

    #[Assert\NotBlank(message: 'Le prix de la dépense est obligatoire.')]
    #[Assert\GreaterThan(0, message: 'Le prix doit être de minimum 1€.')]
    public ?float $originalPrice;

    #[Assert\NotBlank(message: 'Vous devez choisir une devise.')]
    public ?Currency $originalCurrency = null;

    public bool $perPerson = false;
}
<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Entity\EventType;
use Symfony\Component\Validator\Constraints as Assert;

class ActivityRequestDTO
{
    public function __construct(?EventType $activityType, ?Currency $currency)
    {
        $this->type = $activityType;
        $this->originalCurrency = $currency;
    }

    #[Assert\NotBlank(message: 'Vous devez choisir un type d\'activité.')]
    public ?EventType $type;

    #[Assert\NotBlank(message: 'Le nom de l\'hébergement est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le nom del\'hébergement doit faire au minimum 5 caractères.',
        maxMessage: 'Le nom de l\'hébergement doit faire au maximum 255 caractères.'
    )]
    public ?string $name;

    public ?string $description;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $date;

    #[Assert\NotBlank(message: 'Le prix de l\'hébergement est obligatoire.')]
    #[Assert\GreaterThan(0, message: 'Le prix doit être de minimum 1€.')]
    public ?float $originalPrice;

    #[Assert\NotBlank(message: 'Vous devez choisir une devise.')]
    public ?Currency $originalCurrency = null;

    public bool $perPerson = false;
}
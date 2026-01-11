<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Entity\TripTraveler;
use Symfony\Component\Validator\Constraints as Assert;

class OnSiteExpenseRequestDTO
{
    public function __construct(?TripTraveler $tripTraveler, ?Currency $currency)
    {
        $this->payedBy = $tripTraveler;
        $this->originalCurrency = $currency;
    }

    #[Assert\NotBlank(message: 'Le nom de la dépense est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le nom de la dépense doit faire au minimum 5 caractères.',
        maxMessage: 'Le nom de la dépense doit faire au maximum 255 caractères.'
    )]
    public ?string $name;

    #[Assert\NotNull(message: 'La date doit être renseignée.')]
    #[Assert\NotBlank(message: 'La date doit être renseignée.')]
    #[Assert\Type(\DateTime::class)]
    public \DateTime $purchaseDate;

    #[Assert\NotBlank(message: 'Le prix de la dépense est obligatoire.')]
    #[Assert\GreaterThan(0, message: 'Le prix doit être de minimum 1€.')]
    public ?float $originalPrice;

    #[Assert\NotBlank(message: 'Vous devez choisir une devise.')]
    public ?Currency $originalCurrency = null;

    #[Assert\NotNull(message: 'Vous devez choisir le voyageur à l\'origine de l\'achat.')]
    #[Assert\NotBlank(message: 'Vous devez choisir le voyageur à l\'origine de l\'achat.')]
    public TripTraveler $payedBy;
}
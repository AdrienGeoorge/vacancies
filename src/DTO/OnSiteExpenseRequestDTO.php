<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Entity\TripTraveler;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class OnSiteExpenseRequestDTO
{
    public function __construct(?TripTraveler $tripTraveler, ?Currency $currency)
    {
        $this->payedBy = $tripTraveler;
        $this->originalCurrency = $currency;
    }

    #[Assert\NotBlank(message: 'expense.name.not_blank')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'expense.name.min_length',
        maxMessage: 'expense.name.max_length'
    )]
    #[NoHtml]
    public ?string $name;

    #[Assert\NotNull(message: 'expense.date.not_null')]
    #[Assert\NotBlank(message: 'expense.date.not_blank')]
    #[Assert\Type(\DateTime::class)]
    public \DateTime $purchaseDate;

    #[Assert\NotBlank(message: 'expense.price.not_blank')]
    #[Assert\GreaterThan(0, message: 'expense.price.min')]
    public ?float $originalPrice;

    #[Assert\NotBlank(message: 'expense.currency.not_blank')]
    public ?Currency $originalCurrency = null;

    #[Assert\NotNull(message: 'expense.payed_by.not_null')]
    #[Assert\NotBlank(message: 'expense.payed_by.not_blank')]
    public TripTraveler $payedBy;
}

<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class VariousExpensiveRequestDTO
{
    public function __construct(?Currency $originalCurrency)
    {
        $this->originalCurrency = $originalCurrency;
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

    #[NoHtml]
    public ?string $description;

    #[Assert\NotBlank(message: 'expense.price.not_blank')]
    #[Assert\GreaterThan(0, message: 'expense.price.min')]
    public ?float $originalPrice;

    #[Assert\NotBlank(message: 'expense.currency.not_blank')]
    public ?Currency $originalCurrency = null;

    public bool $perPerson = false;
}

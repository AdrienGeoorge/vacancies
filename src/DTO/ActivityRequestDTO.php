<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Entity\EventType;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class ActivityRequestDTO
{
    public function __construct(?EventType $activityType, ?Currency $currency)
    {
        $this->type = $activityType;
        $this->originalCurrency = $currency;
    }

    #[Assert\NotBlank(message: 'activity.type.not_blank')]
    public ?EventType $type;

    #[Assert\NotBlank(message: 'activity.name.not_blank')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'activity.name.min_length',
        maxMessage: 'activity.name.max_length'
    )]
    #[NoHtml]
    public ?string $name;

    #[NoHtml]
    public ?string $description;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $date;

    #[Assert\NotBlank(message: 'activity.price.not_blank')]
    #[Assert\GreaterThan(0, message: 'activity.price.min')]
    public ?float $originalPrice;

    #[Assert\NotBlank(message: 'activity.currency.not_blank')]
    public ?Currency $originalCurrency = null;

    public bool $perPerson = false;
}

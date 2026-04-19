<?php

namespace App\DTO;

use App\Entity\Currency;
use App\Entity\TransportType;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class TransportRequestDTO
{
    public function __construct(?TransportType $transportType, ?Currency $currency)
    {
        $this->type = $transportType;
        $this->originalCurrency = $currency;
    }

    #[Assert\NotBlank(message: 'transport.type.not_blank')]
    public ?TransportType $type;

    #[NoHtml]
    public ?string $company;

    #[NoHtml]
    public ?string $description;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $departureDate = null;

    #[NoHtml]
    public ?string $departure = null;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $arrivalDate = null;

    #[NoHtml]
    public ?string $destination = null;

    #[Assert\When(
        expression: 'this.isPublicTransport()',
        constraints: [
            new Assert\NotBlank(message: 'transport.subscription.not_blank'),
            new Assert\GreaterThan(0, message: 'transport.subscription.min')
        ],
    )]
    public ?int $subscriptionDuration = null;

    #[Assert\When(
        expression: '!this.isCar()',
        constraints: [
            new Assert\NotBlank(message: 'transport.price.not_blank'),
            new Assert\GreaterThan(0, message: 'transport.price.min')
        ],
    )]
    public ?float $originalPrice = 0;

    #[Assert\When(
        expression: '!this.isCar()',
        constraints: [
            new Assert\NotBlank(message: 'transport.currency.not_blank'),
        ],
    )]
    public ?Currency $originalCurrency = null;

    public bool $perPerson = false;

    public bool $isRental = false;

    #[Assert\When(
        expression: 'this.isCar()',
        constraints: [
            new Assert\NotBlank(message: 'transport.toll.not_blank'),
            new Assert\GreaterThanOrEqual(0, message: 'transport.toll.min')
        ],
    )]
    public ?float $estimatedToll = null;

    #[Assert\When(
        expression: 'this.isCar()',
        constraints: [
            new Assert\NotBlank(message: 'transport.gasoline.not_blank'),
            new Assert\GreaterThanOrEqual(0, message: 'transport.gasoline.min')
        ],
    )]
    public ?float $estimatedGasoline = null;

    public function isPublicTransport(): bool
    {
        if ($this->type === null) return false;
        if ($this->type->getName() === 'Transports en commun') return true;

        return false;
    }

    public function isCar(): bool
    {
        if ($this->type === null) return false;
        if ($this->type->getName() === 'Voiture' && !$this->isRental) return true;

        return false;
    }
}

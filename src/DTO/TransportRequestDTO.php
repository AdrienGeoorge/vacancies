<?php

namespace App\DTO;

use App\Entity\TransportType;
use Symfony\Component\Validator\Constraints as Assert;

class TransportRequestDTO
{
    public function __construct(?TransportType $transportType)
    {
        $this->type = $transportType;
    }

    #[Assert\NotBlank(message: 'Vous devez choisir un type de transport.')]
    public ?TransportType $type;

    public ?string $company;

    public ?string $description;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $departureDate;

    public ?string $departure;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $arrivalDate;

    public ?string $destination;

    #[Assert\When(
        expression: 'this.isPublicTransport()',
        constraints: [
            new Assert\NotBlank(message: 'Vous devez indiquer la durée de votre abonnement.'),
            new Assert\GreaterThan(0, message: "Votre abonnement doit avoir une durée minimale de 1 jour.")
        ],
    )]
    public ?int $subscriptionDuration;

    #[Assert\When(
        expression: '!this.isCar()',
        constraints: [
            new Assert\NotBlank(message: 'Vous devez indiquer le prix de votre moyen de transport.'),
            new Assert\GreaterThan(0, message: 'Le prix doit être de minimum 1€.')
        ],
    )]
    public ?float $price;

    public bool $perPerson = false;

    #[Assert\When(
        expression: 'this.isCar()',
        constraints: [
            new Assert\NotBlank(message: "Vous devez indiquer l'estimation du prix du péage aller/retour"),
            new Assert\GreaterThanOrEqual(0, message: "L'estimation du péage doit être de minimum 0€.")
        ],
    )]
    public ?float $estimatedToll;

    #[Assert\When(
        expression: 'this.isCar()',
        constraints: [
            new Assert\NotBlank(message: "Vous devez indiquer l'estimation du prix du carburant aller/retour"),
            new Assert\GreaterThanOrEqual(0, message: "L'estimation du prix du carburant doit être de minimum 0€.")
        ],
    )]
    public ?float $estimatedGasoline;

    public function isPublicTransport(): bool
    {
        if ($this->type === null) return false;
        if ($this->type->getName() === 'Transports en commun') return true;

        return false;
    }

    public function isCar(): bool
    {
        if ($this->type === null) return false;
        if ($this->type->getName() === 'Voiture') return true;

        return false;
    }
}
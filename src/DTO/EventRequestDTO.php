<?php

namespace App\DTO;

use App\Entity\EventType;
use Symfony\Component\Validator\Constraints as Assert;

class EventRequestDTO
{
    public function __construct(?EventType $activityType)
    {
        $this->type = $activityType;
    }

    #[Assert\NotBlank(message: 'Vous devez choisir un type d\'activité.')]
    public ?EventType $type;

    #[Assert\NotBlank(message: 'Le nom de l\'évènement est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le nom del\'évènement doit faire au minimum 5 caractères.',
        maxMessage: 'Le nom de l\'évènement doit faire au maximum 255 caractères.'
    )]
    public ?string $title;

    public ?string $description;

    #[Assert\NotNull(message: 'La date de début doit être renseignée.')]
    #[Assert\NotBlank(message: 'La date de début doit être renseignée.')]
    #[Assert\Type(\DateTime::class)]
    public \DateTime $start;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $end = null;

    public ?int $timeToGo = 0;
}
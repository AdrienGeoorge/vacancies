<?php

namespace App\DTO;

use App\Entity\EventType;
use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class EventRequestDTO
{
    public function __construct(?EventType $activityType)
    {
        $this->type = $activityType;
    }

    #[Assert\NotBlank(message: 'event.type.not_blank')]
    public ?EventType $type;

    #[Assert\NotBlank(message: 'event.title.not_blank')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'event.title.min_length',
        maxMessage: 'event.title.max_length'
    )]
    #[NoHtml]
    public ?string $title;

    #[NoHtml]
    public ?string $description;

    #[Assert\NotNull(message: 'event.start.not_null')]
    #[Assert\NotBlank(message: 'event.start.not_blank')]
    #[Assert\Type(\DateTime::class)]
    public \DateTime $start;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $end = null;

    public ?int $timeToGo = 0;
}

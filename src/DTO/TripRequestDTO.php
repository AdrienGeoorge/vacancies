<?php

namespace App\DTO;

use App\Validator\NoHtml;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class TripRequestDTO
{
    #[Assert\NotBlank(message: 'trip.name.not_blank')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'trip.name.min_length',
        maxMessage: 'trip.name.max_length'
    )]
    #[NoHtml]
    public string $name;

    #[Assert\NotBlank(message: 'trip.destinations.not_blank')]
    #[Assert\Type('array', message: 'trip.destinations.type')]
    #[Assert\Count(
        min: 1,
        minMessage: 'trip.destinations.min_count'
    )]
    public array $destinations = [];

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $departureDate;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $returnDate;

    #[NoHtml]
    public ?string $description;

    #[Assert\File(mimeTypes: ['image/jpeg', 'image/png'])]
    public ?UploadedFile $image;

    #[Assert\NotBlank(message: 'trip.currency.not_blank', allowNull: true)]
    #[Assert\Length(exactly: 3, exactMessage: 'trip.currency.invalid')]
    #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'trip.currency.invalid')]
    public ?string $currency = null;
}

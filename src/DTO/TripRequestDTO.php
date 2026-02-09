<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class TripRequestDTO
{
    #[Assert\NotBlank(message: 'Le nom du voyage est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le nom du voyage doit faire au minimum 5 caractères.',
        maxMessage: 'Le nom du voyage doit faire au maximum 255 caractères.'
    )]
    public string $name;

    #[Assert\NotBlank(message: 'Vous devez sélectionner au moins une destination.')]
    #[Assert\Type('array', message: 'Les destinations doivent être un tableau.')]
    #[Assert\Count(
        min: 1,
        minMessage: 'Vous devez sélectionner au moins une destination.'
    )]
    public array $destinations = [];

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $departureDate;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $returnDate;

    public ?string $description;

    #[Assert\File(mimeTypes: ['image/jpeg', 'image/png'])]
    public ?UploadedFile $image;
}
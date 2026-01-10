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

    #[Assert\NotBlank(message: 'Vous devez sélectionner un pays.')]
    #[Assert\Country(message: 'Le pays choisi est incorrect.')]
    #[Assert\Length(exactly: 2, exactMessage: 'Le code du pays doit faire 2 caractères.')]
    public string $selectedCountry;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $departureDate;

    #[Assert\Type(\DateTime::class)]
    public ?\DateTime $returnDate;

    public ?string $description;

    #[Assert\File(mimeTypes: ['image/jpeg', 'image/png'])]
    public ?UploadedFile $image;
}
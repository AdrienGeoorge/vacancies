<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class TripPhotoRequestDTO
{
    public ?string $caption = null;

    #[Assert\NotNull(message: 'Le fichier image est obligatoire.')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        mimeTypesMessage: 'Ce type de fichier n\'est pas accepté. Seules les images sont autorisées.'
    )]
    public ?UploadedFile $file = null;
}

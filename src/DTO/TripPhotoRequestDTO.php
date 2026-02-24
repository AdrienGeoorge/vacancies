<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class TripPhotoRequestDTO
{
    public ?string $title = null;
    public ?string $caption = null;

    #[Assert\NotNull(message: 'L\'image est obligatoire.')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        maxSizeMessage: 'L\'image doit faire maximum 10 Mo.',
        mimeTypesMessage: 'Ce type de fichier n\'est pas accepté. Seules les images au format JPEG, PNG ou GIF sont autorisées.'
    )]
    public ?UploadedFile $file = null;
}

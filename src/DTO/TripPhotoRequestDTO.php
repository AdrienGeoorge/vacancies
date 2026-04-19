<?php

namespace App\DTO;

use App\Validator\NoHtml;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class TripPhotoRequestDTO
{
    #[NoHtml]
    public ?string $title = null;

    #[NoHtml]
    public ?string $caption = null;

    #[Assert\NotNull(message: 'photo.file.not_null')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        maxSizeMessage: 'photo.file.max_size',
        mimeTypesMessage: 'photo.file.mime_type'
    )]
    public ?UploadedFile $file = null;
}

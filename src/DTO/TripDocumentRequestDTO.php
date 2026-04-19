<?php

namespace App\DTO;

use App\Validator\NoHtml;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class TripDocumentRequestDTO
{
    #[Assert\NotBlank(message: 'document.name.not_blank')]
    #[NoHtml]
    public ?string $name;

    #[Assert\File(mimeTypes: [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/rtf',
        'application/vnd.oasis.opendocument.text',
        'application/pdf',
        'text/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ], mimeTypesMessage: 'document.file.mime_type')]
    public ?UploadedFile $file;
}

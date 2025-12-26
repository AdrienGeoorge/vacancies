<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class TripDocumentRequestDTO
{
    #[Assert\NotBlank(message: 'Le nom du document est obligatoire.')]
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
    ], mimeTypesMessage: 'Ce type de fichier n\'est pas accepté.')]
    public ?UploadedFile $file;
}
<?php

namespace App\DTO;

use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class ContactRequestDTO
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne doit pas dépasser 100 caractères.')]
    #[NoHtml]
    public string $name;

    #[Assert\NotBlank(message: "L'adresse email est obligatoire.")]
    #[Assert\Email(message: "L'adresse email est invalide.")]
    public string $email;

    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    #[Assert\Choice(choices: ['bug', 'other'], message: 'La catégorie est invalide.')]
    public string $category;

    #[Assert\NotBlank(message: 'Le sujet est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le sujet ne doit pas dépasser 255 caractères.')]
    #[NoHtml]
    public string $subject;

    #[Assert\NotBlank(message: 'Le message est obligatoire.')]
    #[Assert\Length(max: 5000, maxMessage: 'Le message ne doit pas dépasser 5000 caractères.')]
    #[NoHtml]
    public string $message;
}

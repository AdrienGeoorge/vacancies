<?php

namespace App\DTO;

use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

class ContactRequestDTO
{
    #[Assert\NotBlank(message: 'contact.name.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'contact.name.max_length')]
    #[NoHtml]
    public string $name;

    #[Assert\NotBlank(message: 'contact.email.not_blank')]
    #[Assert\Email(message: 'contact.email.invalid')]
    public string $email;

    #[Assert\NotBlank(message: 'contact.category.not_blank')]
    #[Assert\Choice(choices: ['bug', 'other'], message: 'contact.category.invalid')]
    public string $category;

    #[Assert\NotBlank(message: 'contact.subject.not_blank')]
    #[Assert\Length(max: 255, maxMessage: 'contact.subject.max_length')]
    #[NoHtml]
    public string $subject;

    #[Assert\NotBlank(message: 'contact.message.not_blank')]
    #[Assert\Length(max: 5000, maxMessage: 'contact.message.max_length')]
    #[NoHtml]
    public string $message;
}

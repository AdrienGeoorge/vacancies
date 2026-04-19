<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\ContactRequestDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/contact', name: 'api_contact_')]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly string $fromMail,
        private readonly TranslatorInterface $translator
    )
    {
    }

    #[Route('', name: 'send', methods: ['POST'])]
    public function send(Request $request, MailerInterface $mailer, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new ContactRequestDTO();
        $dto->name = trim($data['name'] ?? '');
        $dto->email = trim($data['email'] ?? '');
        $dto->category = $data['category'] ?? '';
        $dto->subject = trim($data['subject'] ?? '');
        $dto->message = trim($data['message'] ?? '');

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['message' => $errors[0]->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $categoryLabel = $dto->category === 'bug'
            ? $this->translator->trans('contact.category.bug')
            : $this->translator->trans('contact.category.other');

        $emailMessage = (new Email())
            ->from(new Address($this->fromMail, 'Triplaning'))
            ->to('contact@triplaning.com')
            ->replyTo(new Address($dto->email, $dto->name))
            ->subject("[Triplaning – $categoryLabel] $dto->subject")
            ->text("Nom : $dto->name\nEmail : $dto->email\nCatégorie : $categoryLabel\n\n$dto->message");

        $mailer->send($emailMessage);

        return $this->json(['message' => $this->translator->trans('contact.sent')]);
    }
}

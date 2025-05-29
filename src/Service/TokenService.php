<?php

namespace App\Service;

use App\Entity\PasswordReset;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class TokenService
{

    protected ManagerRegistry $managerRegistry;
    protected RouterInterface $router;
    protected MailerInterface $mailer;
    protected string $token;
    protected string $appSecret;

    public function __construct(ManagerRegistry $managerRegistry, RouterInterface $router, MailerInterface $mailer, string $appSecret, ?string $token_value = null)
    {
        $this->appSecret = $appSecret;
        $this->managerRegistry = $managerRegistry;
        $this->router = $router;
        $this->mailer = $mailer;

        try {
            $this->token = bin2hex(random_bytes(16));
        } catch (\Exception) {
        }

        if ($token_value) $this->token = $token_value;
    }

    public function create(User $user): bool
    {
        $passwordReset = (new PasswordReset())
            ->setUser($user)
            ->setEmail($user->getEmail())
            ->setToken($this->getHash())
            ->setTimestamp(time() + 900);

        $this->managerRegistry->getManager()->persist($passwordReset);
        $this->managerRegistry->getManager()->flush();

        return $this->sendMail($user);
    }

    private function sendMail(User $user): bool
    {
        $url = $this->router->generate('password_reset', ['token' => $this->token], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from('no-reply@adriengeorge.fr')
            ->to($user->getEmail())
            ->subject('Vacancies : réinitialisation de votre mot de passe')
            ->htmlTemplate('password-claim/mail.html.twig')
            ->context(['url' => $url]);

        try {
            $this->mailer->send($email);

            return true;
        } catch (TransportExceptionInterface) {
            return false;
        }
    }

    private function getHash(?string $token = null): string
    {
        if ($token) return hash_hmac('sha512', $token, $this->appSecret);

        return hash_hmac('sha512', $this->token, $this->appSecret);
    }

    public function getUserByToken(string $token)
    {
        return $this->managerRegistry->getRepository(PasswordReset::class)->findOneBy(['token' => $this->getHash($token)]);
    }
}

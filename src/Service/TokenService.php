<?php

namespace App\Service;

use App\Entity\PasswordReset;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class TokenService
{

    protected ManagerRegistry $managerRegistry;
    protected MailerInterface $mailer;
    protected string $token;
    protected string $appSecret;
    protected string $domain;

    public function __construct(ManagerRegistry $managerRegistry, MailerInterface $mailer, string $appSecret, string $domain, ?string $token_value = null)
    {
        $this->appSecret = $appSecret;
        $this->managerRegistry = $managerRegistry;
        $this->mailer = $mailer;
        $this->domain = $domain;

        try {
            $this->token = bin2hex(random_bytes(16));
        } catch (\Exception) {
        }

        if ($token_value) $this->token = $token_value;
    }

    public function create(User $user): bool
    {
        $hash = $this->getHash();
        dump($hash);
        $passwordReset = (new PasswordReset())
            ->setUser($user)
            ->setEmail($user->getEmail())
            ->setToken($hash)
            ->setTimestamp(time() + 900);

        $this->managerRegistry->getManager()->persist($passwordReset);
        $this->managerRegistry->getManager()->flush();

        return $this->sendMail($user, $hash);
    }

    private function sendMail(User $user, string $hash): bool
    {
        $email = (new TemplatedEmail())
            ->from('no-reply@adriengeorge.fr')
            ->to($user->getEmail())
            ->subject('Vacancies : réinitialisation de votre mot de passe')
            ->htmlTemplate('password-claim/mail.html.twig')
            ->context(['url' => $this->domain . '/password/reset/' . $hash]);

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
        return $this->managerRegistry->getRepository(PasswordReset::class)->findOneBy(['token' => $token]);
    }
}

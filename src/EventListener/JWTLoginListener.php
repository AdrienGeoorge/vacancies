<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class JWTLoginListener
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private TranslatorInterface $translator,
    ) {
    }

    public function onLexikJwtAuthenticationOnAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data = $event->getData();

        if ($user->getDisabled()) {
            $deletionDate = $user->getDisabled()->modify('+30 days');
            $now = new \DateTime();

            if ($deletionDate > $now) {
                $user->setDisabled(null);

                $this->managerRegistry->getManager()->persist($user);
                $this->managerRegistry->getManager()->flush();

                $locale = $user->getLanguage() ?? 'fr';
                $data['message'] = $this->translator->trans('auth.account.reactivated', [], 'messages', $locale);
            }
        }

        $data['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'completeName' => $user->getCompleteName(),
            'username' => $user->getUsername(),
            'avatar' => $user->getAvatar(),
            'biography' => $user->getBiography(),
            'language' => $user->getLanguage(),
        ];

        $event->setData($data);
    }
}

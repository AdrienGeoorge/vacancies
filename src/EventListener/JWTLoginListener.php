<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JWTLoginListener
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {

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

            // Si dans les 30 jours : réactiver
            if ($deletionDate > $now) {
                $user->setDisabled(null);

                $this->managerRegistry->getManager()->persist($user);
                $this->managerRegistry->getManager()->flush();

                $data['message'] = 'Votre compte a été réactivé avec succès. Bon retour parmi nous!';
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
            'biography' => $user->getBiography()
        ];

        $event->setData($data);
    }
}

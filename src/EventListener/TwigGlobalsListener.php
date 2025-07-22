<?php

namespace App\EventListener;

use App\Entity\ShareInvitation;
use App\Entity\UserNotifications;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class TwigGlobalsListener implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private Environment $twig;
    private ManagerRegistry $managerRegistry;

    public function __construct(TokenStorageInterface $tokenStorage, Environment $twig, ManagerRegistry $managerRegistry)
    {
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
        $this->managerRegistry = $managerRegistry;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->tokenStorage->getToken()) {
            $notifications = $this->managerRegistry->getRepository(UserNotifications::class)
                ->findBy(['user' => $this->tokenStorage->getToken()->getUser()->getId(), 'view' => false], ['receivedAt' => 'ASC']);
            $this->twig->addGlobal('notifications', $notifications);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}

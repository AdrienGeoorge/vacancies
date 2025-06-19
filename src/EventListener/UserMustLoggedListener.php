<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserMustLoggedListener implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private RouterInterface $router;

    public function __construct(TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->tokenStorage->getToken()) {
            if ('/login' !== $event->getRequest()->getPathInfo() &&
                '/register' !== $event->getRequest()->getPathInfo() &&
                '/connect/google' !== $event->getRequest()->getPathInfo() &&
                '/connect/google/check' !== $event->getRequest()->getPathInfo() &&
                '/countries.json' !== $event->getRequest()->getPathInfo() &&
                !str_contains($event->getRequest()->getPathInfo(), '/password')) {
                $response = new RedirectResponse($this->router->generate('auth_login'));
                $event->setResponse($response);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}

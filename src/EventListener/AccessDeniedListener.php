<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
class AccessDeniedListener
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $isForbiddenHttp = $exception instanceof HttpException
            && $exception->getStatusCode() === Response::HTTP_FORBIDDEN;
        $isAccessDenied = $exception instanceof AccessDeniedException;

        if (!$isForbiddenHttp && !$isAccessDenied) {
            return;
        }

        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $message = $exception->getMessage();
        $translated = $this->translator->trans($message);

        $event->setResponse(new JsonResponse(['message' => $translated], Response::HTTP_FORBIDDEN));
    }
}

<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class LocaleListener
{
    private const SUPPORTED_LOCALES = ['fr', 'en'];
    private const DEFAULT_LOCALE = 'fr';

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $locale = $request->headers->get('X-Locale');

        if (!$locale || !in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = $request->getPreferredLanguage(self::SUPPORTED_LOCALES);
        }

        if (!$locale || !in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = self::DEFAULT_LOCALE;
        }

        $request->setLocale($locale);
    }
}

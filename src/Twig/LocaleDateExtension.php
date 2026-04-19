<?php

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LocaleDateExtension extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('locale_date', [$this, 'localeDate']),
            new TwigFilter('locale_datetime', [$this, 'localeDateTime']),
            // Keep old names as aliases for templates not yet updated
            new TwigFilter('fr_date', [$this, 'localeDate']),
            new TwigFilter('fr_datetime', [$this, 'localeDateTime']),
        ];
    }

    /**
     * Formats a date as "Saturday 20 February" or "20 February" depending on locale and $withDay.
     */
    public function localeDate(\DateTimeInterface|string|null $date, bool $withDay = true, ?string $locale = null): string
    {
        if ($date === null) return '';
        if (is_string($date)) $date = new \DateTime($date);

        $locale = $locale !== null ? $this->toIntlLocale($locale) : $this->getIntlLocale();

        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            $withDay ? 'EEEE d MMMM' : 'd MMMM'
        );

        $result = $formatter->format($date);
        return mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
    }

    /**
     * Formats a datetime as "Saturday 20 February at 14:30" (locale-aware connector).
     */
    public function localeDateTime(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) return '';
        if (is_string($date)) $date = new \DateTime($date);

        $intlLocale = $locale !== null ? $this->toIntlLocale($locale) : $this->getIntlLocale();
        $at = $this->translator->trans('date.at', [], 'messages', $locale ?? $this->translator->getLocale());

        return $this->localeDate($date, true, $intlLocale) . ' ' . $at . ' ' . $date->format('H:i');
    }

    private function getIntlLocale(): string
    {
        return $this->toIntlLocale($this->translator->getLocale());
    }

    private function toIntlLocale(string $locale): string
    {
        return match ($locale) {
            'en', 'en_GB', 'en_US' => 'en_GB',
            'fr', 'fr_FR' => 'fr_FR',
            default => strlen($locale) === 2 ? $locale . '_' . strtoupper($locale) : $locale,
        };
    }
}

<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FrenchDateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('fr_date', [$this, 'frenchDate']),
            new TwigFilter('fr_datetime', [$this, 'frenchDateTime']),
        ];
    }

    /**
     * Formats a date as "samedi 20 février" or "20 février" depending on $withDay.
     */
    public function frenchDate(\DateTimeInterface|string|null $date, bool $withDay = true): string
    {
        if ($date === null) return '';
        if (is_string($date)) $date = new \DateTime($date);

        $formatter = new \IntlDateFormatter(
            'fr_FR',
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
     * Formats a datetime as "Samedi 20 février à 14:30".
     */
    public function frenchDateTime(\DateTimeInterface|string|null $date): string
    {
        if ($date === null) return '';
        if (is_string($date)) $date = new \DateTime($date);

        return $this->frenchDate($date, true) . ' à ' . $date->format('H:i');
    }
}

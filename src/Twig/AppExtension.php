<?php

namespace App\Twig;

use Symfony\Component\Intl\Countries;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('invert_sign', [$this, 'invertSign']),
            new TwigFilter('country_name', [$this, 'getCountryName']),
        ];
    }

    public function invertSign(int|float $value): string
    {
        $inverted = $value != 0 ? -$value : $value;
        return $inverted > 0 ? "+$inverted" : (string) $inverted;
    }

    public function getCountryName(?string $countryCode, string $locale = 'fr'): string
    {
        if (!$countryCode) {
            return '';
        }

        return Countries::getName($countryCode, $locale);
    }
}

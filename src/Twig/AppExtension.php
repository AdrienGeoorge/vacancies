<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('invert_sign', [$this, 'invertSign']),
        ];
    }

    public function invertSign(int|float $value): string
    {
        $inverted = $value != 0 ? -$value : $value;
        return $inverted > 0 ? "+$inverted" : (string) $inverted;
    }
}

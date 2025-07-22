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
            new TwigFilter('time_ago', [$this, 'getTimeAgo']),
        ];
    }

    public function invertSign(int|float $value): string
    {
        $inverted = $value != 0 ? -$value : $value;
        return $inverted > 0 ? "+$inverted" : (string) $inverted;
    }

    public function getTimeAgo(?\DateTime $date): ?string
    {
        if ($date) {
            $tokens = [
                31536000 => 'an',
                2592000 => 'mois',
                604800 => 'semaine',
                86400 => 'jour',
                3600 => 'heure',
                60 => 'minute',
                1 => 'seconde'
            ];

            $time = time() - $date->getTimestamp();
            $time = ($time < 1) ? 1 : $time;

            foreach ($tokens as $unit => $text) {
                if ($time < $unit) continue;
                $numberOfUnits = floor($time / $unit);

                return $numberOfUnits . ' ' . $text . (('mois' !== $text && $numberOfUnits > 1) ? 's' : '');
            }
        }

        return null;
    }
}

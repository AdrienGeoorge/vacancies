<?php

namespace App\Service;

class TimeAgoService
{
    public function get(?\DateTime $date): ?string
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
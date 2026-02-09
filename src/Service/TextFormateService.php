<?php

namespace App\Service;

class TextFormateService
{
    public function formatList(array $items, string $conjunction = 'et'): string
    {
        $count = count($items);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $items[0];
        }

        if ($count === 2) {
            return $items[0] . ' ' . $conjunction . ' ' . $items[1];
        }

        $last = array_pop($items);
        return implode(', ', $items) . ' ' . $conjunction . ' ' . $last;
    }
}
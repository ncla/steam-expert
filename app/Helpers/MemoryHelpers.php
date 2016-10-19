<?php

namespace App\Helpers;

class MemoryHelpers
{
    public static function convert($size)
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[ $i ];
    }
}

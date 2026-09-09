<?php

namespace App\Utils\Filament;

class FileHelper
{
    public static function extension(string $fileName): string
    {
        return match (true) {
            str($fileName)->endsWith('.csv') => 'csv',
            str($fileName)->endsWith('.xlsx') => 'xlsx',
            default => 'unknown',
        };
    }
}

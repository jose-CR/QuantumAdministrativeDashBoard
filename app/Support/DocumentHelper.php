<?php

namespace App\Support;

class DocumentHelper {

    public static function mask(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        return match ($type) {
            'DUI' => '99999999-9',
            'NIT' => '9999-999999-999-9',
            default => null,
        };
    }

}

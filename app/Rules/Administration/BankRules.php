<?php

namespace App\Rules\Administration;

class BankRules
{
    public static function import(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
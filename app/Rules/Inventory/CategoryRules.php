<?php

namespace App\Rules\Inventory;

class CategoryRules
{
    public static function import(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
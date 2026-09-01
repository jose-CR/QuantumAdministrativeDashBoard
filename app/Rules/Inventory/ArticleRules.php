<?php

namespace App\Rules\Inventory;

use Illuminate\Validation\Rule;

class ArticleRules
{
    public static function import(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'brand' => [
                'required',
                'string',
                'max:255',
            ],

            'model' => [
                'required',
                'string',
                'max:255',
            ],

            'year' => [
                'required',
                'integer',
                'between:1900,' . (date('Y') + 1),
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}

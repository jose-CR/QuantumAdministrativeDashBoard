<?php

namespace App\Rules\Inventory;

use Illuminate\Validation\Rule;

class ArticleUnitRules
{
    public static function import(): array
    {
        return [
            'article_id' => [
                'required',
                'integer',
                'exists:articles,id',
            ],

            'vin' => [
                'required',
                'string',
                'size:17',
                'unique:article_units,vin',
            ],

            'engine_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'plate' => [
                'nullable',
                'string',
                'max:255',
                'unique:article_units,plate',
            ],

            'color' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'reserved',
                    'sold',
                ]),
            ],
        ];
    }
}

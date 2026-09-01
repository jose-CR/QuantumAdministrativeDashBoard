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

            'color' => [
                'nullable',
                'string',
                'max:255',
            ],

            'cash_price' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'vin' => [
                'nullable',
                'string',
                'size:20',
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

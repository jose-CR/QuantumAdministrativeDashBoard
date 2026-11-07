<?php

namespace App\Rules\Inventory;

class ArticleUnitRules
{
    public static function import(): array
    {
        return [
            // Ya no es obligatorio: exigirlo forzaba a inventar ids en el Excel y
            // desfasaba la secuencia de Postgres (el error de "id duplicado").
            'id' => [
                'integer',
                'nullable',
            ],

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

            // El VIN/serie identifica la pieza; el modelo lo revalida al guardar.
            'vin' => [
                'required',
                'string',
                'max:20',
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
            ],

            'status' => [
                'nullable',
                'in:available,reserved,rented,returned,sold',
            ],
        ];
    }
}

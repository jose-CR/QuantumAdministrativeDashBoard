<?php

namespace App\Rules\Inventory;

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

            // Si viene vacío, el modelo lo arma con marca + modelo + año.
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sku' => [
                'nullable',
                'string',
                'max:64',
            ],

            'tracking' => [
                'nullable',
                'in:none,serial',
            ],

            // Opcionales: un casco o un repuesto no tiene marca/modelo/año de
            // vehículo, y antes estas columnas eran obligatorias en la base.
            'brand' => [
                'nullable',
                'string',
                'max:255',
            ],

            'model' => [
                'nullable',
                'string',
                'max:255',
            ],

            'year' => [
                'nullable',
                'integer',
                'between:1900,' . (date('Y') + 1),
            ],

            'cash_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'stock' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}

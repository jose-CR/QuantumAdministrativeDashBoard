<?php

namespace Database\Factories;

use App\Models\ArticleUnit;
use App\Models\Credit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditItem>
 */
class CreditItemFactory extends Factory
{
    public function definition(): array
    {
        $articleUnit = ArticleUnit::where('status', 'available')
            ->inRandomOrder()
            ->first();

        if (! $articleUnit) {
            throw new \RuntimeException(
                'No hay ArticleUnit disponibles para crear el CreditItem.'
            );
        }

        return [
            'credit_id' => Credit::factory(),

            'article_unit_id' => $articleUnit->id,

            'price' => $articleUnit->cash_price,
        ];
    }
}

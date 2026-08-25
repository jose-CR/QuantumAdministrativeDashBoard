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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $credit = Credit::factory();
        $articleUnit = ArticleUnit::factory();
        $price = fake()->randomFloat(2, 100, 5000);

        return [
            'credit_id' => $credit,
            'article_unit_id' => $articleUnit,
            'price' => $price,
        ];
    }
}

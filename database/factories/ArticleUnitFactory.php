<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArticleUnit>
 */
class ArticleUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),

            'color' => fake()->safeColorName(),

            'cash_price' => fake()->randomFloat(
                2,
                1000,
                15000
            ),

            'vin' => strtoupper(
                fake()->bothify('#################')
            ),

            'engine_number' => strtoupper(
                fake()->bothify('ENG-########')
            ),

            'plate' => strtoupper(
                fake()->bothify('P###-###')
            ),

            'status' => fake()->randomElement([
                'available',
                'reserved',
                'sold',
            ]),
        ];
    }
}

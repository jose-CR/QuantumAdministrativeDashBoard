<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArticleUnit>
 */
class ArticleUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),

            'vin' => strtoupper(fake()->bothify('#################')),

            'engine_number' => strtoupper(fake()->bothify('ENG-########')),

            'plate' => strtoupper(fake()->bothify('P###-###')),

            'color' => fake()->safeColorName(),

            'status' => fake()->randomElement([
                'available',
                'reserved',
                'sold',
            ]),
        ];
    }
}

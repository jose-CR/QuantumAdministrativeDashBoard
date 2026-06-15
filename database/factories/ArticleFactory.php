<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),

            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('???-###')),

            'year' => fake()->numberBetween(2020, now()->year),

            'color' => fake()->safeColorName(),

            'cash_price' => fake()->randomFloat(2, 1000, 15000),
            'credit_price' => fake()->randomFloat(2, 1500, 20000),

            'descripcion' => fake()->sentence(),
        ];
    }
}

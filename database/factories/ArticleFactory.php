<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),

            'brand' => fake()->company(),

            'model' => strtoupper(
                fake()->bothify('???-###')
            ),

            'year' => fake()->numberBetween(
                2020,
                now()->year
            ),

            'description' => fake()->sentence(),
        ];
    }
}
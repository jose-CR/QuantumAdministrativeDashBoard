<?php

namespace Database\Factories;

use App\Models\Outflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Outflow>
 */
class OutflowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Outflow::class;

    public function definition(): array
    {
        return [
            'invoice_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'company' => fake()->company(),
            'invoice_code' => fake()->unique()->bothify('FAC-####'),
            'quantity' => fake()->numberBetween(1, 50),
            'amount' => fake()->randomFloat(2, 5, 2000),
            'description' => fake()->sentence(),
            'source' => 'Caja',
            'area' => fake()->randomElement(['Taller', 'Ventas', 'Administración', 'Pintura', 'Bodega']),
        ];
    }
}

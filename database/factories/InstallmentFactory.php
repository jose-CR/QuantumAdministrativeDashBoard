<?php

namespace Database\Factories;

use App\Models\Credit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Installment>
 */
class InstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(
            2,
            50,
            500
        );

        return [
            'credit_id' => Credit::factory(),

            'number' => fake()->numberBetween(
                1,
                36
            ),

            'amount' => $amount,

            'remaining_balance' => $amount,

            'due_date' => fake()->dateTimeBetween(
                'now',
                '+2 years'
            ),

            'paid_at' => null,

            'status' => fake()->randomElement([
                'pending',
                'paid',
                'late',
            ]),
        ];
    }
}

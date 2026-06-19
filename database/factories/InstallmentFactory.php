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
            100,
            500
        );

        return [
            'credit_id' => Credit::factory(),
            'number' => 1,
            'amount' => $amount,
            'remaining_balance' => $amount,
            'due_date' => now()->addMonth(),
            'status' => 'pending',
            'paid_at' => null,
        ];
    }
}

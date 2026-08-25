<?php

namespace Database\Factories;

use App\Models\Credit;
use App\Models\Customer;
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
        return [
            'credit_id' => Credit::factory(),

            'number' => 1,

            'amount' => 100,

            'remaining_balance' => 100,

            'due_date' => now()->addMonth(),

            'status' => 'pending',

            'paid_at' => null,
        ];
    }
}

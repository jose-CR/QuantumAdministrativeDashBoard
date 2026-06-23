<?php

namespace Database\Factories;

use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentHistoryFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->randomFloat(
            2,
            50,
            1500
        );

        $previousBalance = fake()->randomFloat(
            2,
            $amount,
            15000
        );

        return [
            'credit_id' => Credit::factory(),

            'user_id' => User::query()->inRandomOrder()->value('id'),

            'amount' => $amount,

            'payment_date'
                => fake()->dateTimeBetween(
                    '-6 months',
                    'now'
                ),
            
            'payment_method' => fake()->randomElement([
                'cash',
                'bank_transfer',
                'card',
            ]),

            'receipt_number'
                => fake()->numerify(
                    'REC-#####'
                ),
            
            'previous_balance' => $previousBalance,

            'new_balance' => max(
                0,
                $previousBalance - $amount
            ),

            'notes'
                => fake()->optional()->sentence(),
        ];
    }
}
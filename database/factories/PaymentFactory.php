<?php

namespace Database\Factories;

use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'installment_id'
                => Installment::factory(),

            'amount'
                => fake()->randomFloat(
                    2,
                    25,
                    500
                ),

            'payment_date'
                => fake()->dateTimeBetween(
                    '-6 months',
                    'now'
                ),

            'receipt_number'
                => fake()->numerify(
                    'REC-#####'
                ),

            'notes'
                => fake()->optional()->sentence(),
        ];
    }
}
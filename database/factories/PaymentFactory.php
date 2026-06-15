<?php

namespace Database\Factories;

use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'installment_id' => Installment::factory(),

            'amount' => fake()->randomFloat(
                2,
                25,
                500
            ),

            'payment_date' => fake()->dateTimeBetween(
                '-1 year',
                'now'
            ),

            'receipt_number' => strtoupper(
                fake()->bothify('REC-#####')
            ),

            'notes' => fake()->optional()->sentence(),
        ];
    }
}

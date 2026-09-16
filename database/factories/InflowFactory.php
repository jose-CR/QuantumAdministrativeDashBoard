<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\Customer;
use App\Models\Inflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inflow>
 */
class InflowFactory extends Factory
{

    protected $model = Inflow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethod = fake()->randomElement(['cash', 'transfer']);

        return [
            'invoice_number' => fake()->unique()->bothify('INV-####'),
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'description' => fake()->sentence(),
            'customer_id' => Customer::factory(),
            'amount' => fake()->randomFloat(2, 20, 5000),
            'payment_method' => $paymentMethod,
            'transfer_number' => $paymentMethod === 'transfer' ? fake()->numerify('TRX-#######') : null,
            'bank_id' => $paymentMethod === 'transfer' ? Bank::inRandomOrder()->value('id') : null,
            'transfer_date' => $paymentMethod === 'transfer' ? fake()->dateTimeBetween('-6 months', 'now') : null,
            'payment_status' => fake()->randomElement(['pending', 'partial', 'paid']),
            'salesperson' => fake()->name(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

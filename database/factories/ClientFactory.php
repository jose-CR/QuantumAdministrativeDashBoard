<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),

            'identity_document' => fake()->unique()->numerify('#########'),

            'birth_date' => fake()->date(),

            'gender' => fake()->randomElement([
                'male',
                'female',
            ]),

            'phone_primary' => fake()->phoneNumber(),
            'phone_secondary' => fake()->optional()->phoneNumber(),

            'email' => fake()->unique()->safeEmail(),

            'address' => fake()->address(),

            'occupation' => fake()->jobTitle(),
            'workplace' => fake()->company(),

            'monthly_income' => fake()->randomFloat(2, 400, 5000),

            'marital_status' => fake()->randomElement([
                'single',
                'married',
                'divorced',
                'widowed',
            ]),

            'nationality' => 'Salvadoran',

            'is_active' => true,
        ];
    }
}

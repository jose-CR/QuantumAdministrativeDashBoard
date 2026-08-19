<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientReference>
 */

class ClientReferenceFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement([
            'family',
            'friend',
        ]);

        return [
            'customer_id' => Customer::factory(),

            'reference_type' => $type,

            'full_name' => fake()->name(),

            'relationship' => $type === 'family'
                ? fake()->randomElement([
                    'Father',
                    'Mother',
                    'Brother',
                    'Sister',
                    'Uncle',
                    'Aunt',
                    'Cousin',
                ])
                : null,

            'phone' => fake()->phoneNumber(),

            'address' => fake()->optional()->address(),

            'occupation' => fake()->optional()->jobTitle(),
        ];
    }
}

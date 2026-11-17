<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'EMP-' . fake()->unique()->numerify('####'),
            'name' => fake()->name(),
            'document_type' => 'DUI',
            'document_number' => fake()->unique()->numerify('#########'),
            'phone' => fake()->numerify('7#######'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'job_title' => fake()->randomElement(['vendedor', 'tecnico', 'bodega', 'administracion']),
            'hire_date' => fake()->dateTimeBetween('-6 years', 'now'),
            'termination_date' => null,
            'status' => Employee::STATUS_ACTIVE,
            'user_id' => null,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => Employee::STATUS_INACTIVE,
            'termination_date' => now()->subMonth(),
        ]);
    }
}

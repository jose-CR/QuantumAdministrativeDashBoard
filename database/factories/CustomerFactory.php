<?php

namespace Database\Factories;

use App\Support\ActividadesEconomicas;
use App\Support\ElSalvadorCatalogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        // Catálogos
        $actividades = ActividadesEconomicas::activities();
        $departamentos = ElSalvadorCatalogo::departments();

        // Actividad económica aleatoria
        $actividad = fake()->randomElement($actividades);

        // Departamento aleatorio
        $departamento = fake()->randomElement($departamentos);

                // Municipio perteneciente al departamento seleccionado
        $municipio = fake()->randomElement($departamento['municipios']);

        // Distrito perteneciente al municipio seleccionado
        $distrito = fake()->randomElement($municipio['distritos']);

        return [
            // Documento
            'document_type' => fake()->randomElement([
                'DUI',
                'NIT',
                'PASSPORT',
                'RES CARNET',
                'OTRO',
            ]),

            'document_number' => fake()->unique()->numerify('#########'),

            // Información del cliente
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone_primary' => fake()->numerify('7#######'),
            'phone_secondary' => fake()->optional()->numerify('7#######'),

            // Actividad económica
            'nrc' => fake()->optional()->numerify('########'),
            'economic_activity' => $actividad['actividad'],

            // Ubicación
            'department' => $departamento['nombre'],
            'municipality' => $municipio['nombre'],
            'district' => $distrito['nombre'],

            // Dirección
            'address' => fake()->address(),
        ];
    }
}

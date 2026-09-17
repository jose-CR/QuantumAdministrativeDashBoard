<?php

namespace Database\Factories;

use App\Support\ElSalvadorCatalogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transportation>
 */
class TransportationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $departamentos = ElSalvadorCatalogo::departments();

        // Departamento aleatorio
        $codigoDepartamento = fake()->randomElement(
            array_keys($departamentos)
        );

        // Municipio perteneciente al departamento seleccionado
        $municipios = ElSalvadorCatalogo::municipalities(
            $codigoDepartamento
        );

        $codigoMunicipio = fake()->randomElement(
            array_keys($municipios)
        );

        // Distrito perteneciente al municipio seleccionado
        $distritos = ElSalvadorCatalogo::districts(
            $codigoMunicipio
        );

        $codigoDistrito = fake()->randomElement(
            array_keys($distritos)
        );

        $price = fake()->randomFloat(
                2,
                50,
                1000
        );

        return [
            'department' => $codigoDepartamento,
            'municipality' => $codigoMunicipio,
            'district' => $codigoDistrito,
            'price' => $price,
        ];
    }
}

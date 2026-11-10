<?php

namespace App\Rules;

use App\Models\Customer;
use App\Support\ActividadesEconomicas;
use App\Support\ElSalvadorCatalogo;
use Illuminate\Validation\Rule;

class CustomerRules
{
    public static function import(): array
    {
        return [
            // El id no se importa: se resuelve por tipo + número de documento.
            'document_type' => [
                'required',
                'string',
                Rule::in(Customer::DOCUMENT_TYPES),
            ],

            'document_number' => [
                'required',
                'string',
                'max:30',
            ],

            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone_primary' => [
                'nullable',
                'string',
                'max:20',
            ],

            'phone_secondary' => [
                'nullable',
                'string',
                'max:20',
            ],

            'nrc' => [
                'nullable',
                'string',
                'max:255',
            ],

            'economic_activity' => [
                'nullable',
                'string',
                'max:255',
                Rule::in(array_keys(
                    ActividadesEconomicas::options()
                )),
            ],

            'department' => [
                'required',
                'string',
                'max:255',
                // El export escribe el nombre; el import acepta nombre o código.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (ElSalvadorCatalogo::resolveDepartment((string) $value) === null) {
                        $fail('El departamento no existe en el catálogo.');
                    }
                },
            ],

            'municipality' => [
                'required',
                'string',
                'max:255',
            ],

            'district' => [
                'required',
                'string',
                'max:255',
            ],

            'address' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
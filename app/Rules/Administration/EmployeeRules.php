<?php

namespace App\Rules\Administration;

use App\Models\Employee;

/**
 * Las mismas reglas para el formulario y para la importación: el import es la otra
 * puerta de entrada y no puede validar menos que la pantalla.
 */
class EmployeeRules
{
    public static function import(): array
    {
        return [
            'code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'document_type' => [
                'nullable',
                'string',
                'in:' . implode(',', Employee::DOCUMENT_TYPES),
            ],
            'document_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:25',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            // Acepta los puestos del catálogo y los que el taller agregue.
            'job_title' => [
                'nullable',
                'string',
                'max:30',
            ],
            'hire_date' => [
                'nullable',
                'date',
            ],
            'termination_date' => [
                'nullable',
                'date',
            ],
            'status' => [
                'nullable',
                'string',
                'in:' . implode(',', Employee::STATUSES),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}

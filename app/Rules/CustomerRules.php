<?php

namespace App\Rules;

use App\Support\ElSalvadorCatalogo;
use Illuminate\Validation\Rule;

class CustomerRules
{
    public static function import(): array
    {
        return [
            'document_type' => [
                'required',
                'string',
                Rule::in([
                    'DUI',
                    'NIT',
                    'Passport',
                    'Carnet RES',
                    'OTRO',
                ]),
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
            ],

            'department' => [
                'required',
                'string',
                'max:255',
                Rule::in(array_keys(
                    ElSalvadorCatalogo::departments()
                )),
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
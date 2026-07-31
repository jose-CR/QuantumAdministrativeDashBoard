<?php

namespace App\Rules;

class ClientRules
{
    public static function import(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'identity_document' => [
                'nullable',
                'string',
                'max:255',
            ],

            'birth_date' => [
                'nullable',
                'date',
            ],

            'gender' => [
                'required',
                'in:male,female,other',
            ],

            'phone_primary' => [
                'required',
                'string',
                'max:255',
            ],

            'phone_secondary' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'required',
                'string',
            ],

            'occupation' => [
                'nullable',
                'string',
                'max:255',
            ],

            'workplace' => [
                'nullable',
                'string',
                'max:255',
            ],

            'monthly_income' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'marital_status' => [
                'nullable',
                'string',
                'max:255',
            ],

            'nationality' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }
}
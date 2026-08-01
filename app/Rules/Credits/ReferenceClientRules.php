<?php

namespace App\Rules\Credits;

use Illuminate\Validation\Rule;

class ReferenceClientRules
{
    public static function import(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
            ],

            'reference_type' => [
                'required',
                Rule::in([
                    'family',
                    'friend',
                ]),
            ],

            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'relationship' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'occupation' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}

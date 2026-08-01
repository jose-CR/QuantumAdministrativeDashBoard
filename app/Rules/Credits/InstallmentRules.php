<?php

namespace App\Rules\Credits;

use Illuminate\Validation\Rule;

class InstallmentRulesRules
{
    public static function import(): array
    {
        return [
            'credit_id' => [
                'required',
                'integer',
                'exists:credits,id',
            ],

            'number' => [
                'required',
                'integer',
                'min:1',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'due_date' => [
                'required',
                'date',
            ],

            'paid_at' => [
                'nullable',
                'date',
                'after_or_equal:due_date',
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'paid',
                    'late',
                    'cancelled',
                    'refinanced',
                ]),
            ],

            'remaining_balance' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }
}
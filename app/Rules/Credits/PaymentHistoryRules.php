<?php

namespace App\Rules\Credits;

use Illuminate\Validation\Rule;

class PaymentHistoryRules
{
    public static function import(): array
    {
        return [
            'credit_id' => [
                'required',
                'integer',
                'exists:credits,id',
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'card',
                    'bank_transfer',
                ]),
            ],

            'bank_id' => [
                'nullable',
                'required_if:payment_method,bank_transfer',
                'integer',
                'exists:banks,id',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'receipt_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'previous_balance' => [
                'required',
                'numeric',
                'min:0',
            ],

            'new_balance' => [
                'required',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
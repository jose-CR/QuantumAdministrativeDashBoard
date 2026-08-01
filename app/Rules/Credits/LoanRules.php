<?php

namespace App\Rules\Credits;

use Illuminate\Validation\Rule;

class LoanRules
{
    public static function import(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
            ],

            'article_unit_id' => [
                'required',
                'integer',
                'exists:article_units,id',
            ],

            'assigned_user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'refinanced_from_id' => [
                'nullable',
                'integer',
                'exists:credits,id',
            ],

            'initial_amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'down_payment' => [
                'required',
                'numeric',
                'min:0',
            ],

            'installments' => [
                'required',
                'integer',
                'min:1',
            ],

            'installment_amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'periodicity' => [
                'required',
                Rule::in([
                    'weekly',
                    'monthly',
                    'yearly',
                ]),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'payment_day' => [
                'required',
                'integer',
                'between:1,31',
            ],

            'payment_month' => [
                'nullable',
                'integer',
                'between:1,12',
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'active',
                    'paid',
                    'refinanced',
                    'cancelled',
                    'defaulted',
                    'completed',
                ]),
            ],
        ];
    }
}
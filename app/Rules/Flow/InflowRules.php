<?php

namespace App\Rules\Flow;

class InflowRules
{
    public static function import(): array
    {
        return [
            'invoice_number' => [
                'required',
                'string',
                'max:255',
            ],

            'date' => [
                'required',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payment_method' => [
                'required',
                'in:cash,transfer',
            ],

            'transfer_number' => [
                'nullable',
                'string',
                'max:255',
                'required_if:payment_method,transfer',
            ],

            'bank_id' => [
                'nullable',
                'integer',
                'exists:banks,id',
                'required_if:payment_method,transfer',
            ],

            'transfer_date' => [
                'nullable',
                'date',
                'required_if:payment_method,transfer',
            ],

            'payment_status' => [
                'required',
                'in:pending,partial,paid',
            ],

            'salesperson' => [
                'required',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
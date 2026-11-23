<?php

namespace App\Rules\Flow;

use App\Support\DocumentTypes;
use Illuminate\Validation\Rule;

class OutflowRules
{
    public static function import(): array
    {
        return [
            'invoice_date' => [
                'required',
                'date',
            ],

            'company' => [
                'required',
                'string',
                'max:255',
            ],

            'invoice_code' => [
                'required',
                'string',
                'max:255',
            ],

            'document_type' => [
                'nullable',
                Rule::in(DocumentTypes::accepted(DocumentTypes::outflow())),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'source' => [
                'required',
                'string',
                'max:255',
            ],

            'area' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
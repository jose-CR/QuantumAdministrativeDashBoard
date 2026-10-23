<?php

return [

    'installments' => [
        'max' => (int) env('CREDITS_MAX_INSTALLMENTS', 100),
        'max_total_ratio' => (float) env('CREDITS_MAX_TOTAL_RATIO', 3)
    ],

    'periodicities' => ['weekly', 'monthly', 'yearly'],

    'statuses' => [
        'credit'      => ['active', 'completed', 'refinanced'],
        'installment' => ['pending', 'partial', 'late', 'paid', 'cancelled'],
    ],

    'currency' => 'USD',

];
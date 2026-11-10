<?php

return [

    'installments' => [
        'max' => (int) env('CREDITS_MAX_INSTALLMENTS', 100),
        'max_total_ratio' => (float) env('CREDITS_MAX_TOTAL_RATIO', 3)
    ],

    /* Tasa de interés por cuota cuando se pacta un interés fijo (%). */
    'interest' => [
        'max_rate' => (float) env('CREDITS_MAX_INTEREST_RATE', 20),
    ],

    'periodicities' => ['weekly', 'monthly', 'yearly'],

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    |
    | Esta lista documenta lo que acepta la base: los valores reales están en el
    | CHECK de `credits.status` e `installments.status`. Si una migración cambia
    | el CHECK, se actualiza acá. `partial` quedó fuera de cuotas: nunca se
    | escribió y la base no lo acepta.
    |
    */
    'statuses' => [
        'credit'      => ['pending', 'active', 'paid', 'refinanced', 'cancelled', 'defaulted', 'completed'],
        'installment' => ['pending', 'paid', 'late', 'cancelled', 'refinanced', 'completed'],
    ],

    'currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Python
    |--------------------------------------------------------------------------
    |
    | Los exports arman el ZIP (Excel + adjuntos) con python/scripts/cash_report.py.
    | `bin` es la ruta al intérprete; vacío = se resuelve solo (venv del proyecto,
    | luego /usr/bin/python3). Ver App\Support\PythonBinary.
    |
    */
    'python' => [
        'bin' => env('PYTHON_BIN'),
        'script' => 'python/scripts/cash_report.py',
    ],

];
<?php

return [
    'allowed_cidr' => env('ATTACHMENTS_ALLOWED_CIDR', '192.168.0.0/24'),

    'fallback_attributes' => ['full_name', 'name', 'title', 'description', 'code'],

    // Niveles de carpeta por modelo, de padre a hijo.
    // Cada nivel es un campo o una lista de alternativas (gana el primero con valor).
    // Admite relaciones con punto: 'customer.name'.
    'folders' => [
        \App\Models\Outflow::class => [
            'company',        // hijo
            'invoice_code',   // nieto
        ],
        // \App\Models\Inflow::class => ['customer.name', 'invoice_number'],
    ],
];

<?php

return [
    // Prefijo del panel en la URL: /admin
    'panel_path' => 'admin',

    // Dónde se guarda el estado que cambia sin deploy.
    'state_file' => storage_path('app/module-states.json'),

    // Estado base si el JSON no dice nada.
    'default_state' => 'enabled',

    // Quién pasa el bloqueo en "preview": rol de Shield que deja entrar.
    'bypass_role' => 'super_admin',

    /*
    |--------------------------------------------------------------------------
    | Claves de traducción
    |--------------------------------------------------------------------------
    |
    | El config no repite textos: apunta a lang/{locale}/modules.php. La clave
    | de cada módulo en 'modules' es también su clave de traducción:
    | 'credits' → modules.modules.credits.label y .message.
    |
    | Si renombrás una clave de módulo, renombrala también en lang/es y lang/en,
    | o la pantalla muestra el nombre técnico de la clave.
    |
    */
    'lang' => [
        'module' => 'modules.modules',
        'states' => 'modules.states',
        'state_descriptions' => 'modules.state_descriptions',
        'blocked' => 'modules.blocked',
        'admin' => 'modules.admin',
        'command' => 'modules.command',
    ],

    // Un módulo por app del panel. 'paths' son los slugs REALES del proyecto.
    'modules' => [
        'clients' => [
            'state' => 'enabled',
            'paths' => [
                'client/customers',
            ],
        ],

        'cash' => [
            'state' => 'enabled',
            'paths' => [
                'administration/inflows',
                'administration/outflows',
            ],
        ],

        'inventory' => [
            'state' => 'enabled',
            'paths' => [
                'inventary/article',
                'inventary/articleunit',
                'inventary/categories',
                'inventary/transportation',
            ],
        ],

        'credits' => [
            'state' => 'enabled',
            'paths' => [
                'creditos/loans',
                'creditos/installments',
                'creditos/history',
            ],
        ],

        'administration' => [
            'state' => 'enabled',
            'paths' => [
                'administration/banks',
                'users',
            ],
        ],

        'settings' => [
            'state' => 'enabled',
            'paths' => [
                'tags',
                'shield/roles',
                'translation-manager',
            ],
        ],
    ],
];
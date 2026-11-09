<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module switch — English copy
    |--------------------------------------------------------------------------
    */

    'states' => [
        'enabled' => 'Enabled',
        'preview' => 'In preview',
        'off' => 'Disabled',
    ],

    'state_descriptions' => [
        'enabled' => 'Everyone can get in.',
        'preview' => 'Users see the notice page; administrators pass through.',
        'off' => 'Nobody gets in, administrators included.',
    ],

    'blocked' => [
        'title' => 'Module unavailable',

        'panel' => 'Admin panel',
        'status' => 'Status',
        'module' => 'Module',
        'code' => 'Code',
        'code_value' => '403',

        'lost_nothing' => [
            'title' => 'Nothing was lost.',
            'text' => 'Records that already exist stay saved and in order.',
        ],
        'rest_works' => [
            'title' => 'The rest of the system works.',
            'text' => 'You can keep using the other modules as usual.',
        ],
        'reopens' => [
            'title' => 'It reopens on its own.',
            'text' => 'You do not have to do anything: we will let you know when it is ready.',
        ],

        'home' => 'Back to the panel',
        'retry' => 'Retry',
        'footer' => 'System operational · The rest of the panel stays available',
    ],

    'admin' => [
        'badge' => 'Administrator view.',
        'explain' => 'You are seeing this page because the :module module is in “:state”. We are checking the module before opening it to the whole staff. To enable it:',
        'command' => 'php artisan module:state :module enabled',
        'hint' => 'Regular users never see this box.',
    ],

    'command' => [
        'title' => 'Module states',
        'module' => 'Module',
        'state' => 'State',
        'routes' => 'Routes',
        'unknown_module' => 'There is no module ":module". Available: :available',
        'invalid_state' => 'Invalid state: ":state". Use: :states',
        'updated' => 'Module ":module" is now :state — :consequence',
        'reset' => 'To reset everything to factory state, delete: :file',
    ],

    'modules' => [
        'clients' => [
            'label' => 'Clients',
            'message' => 'We are adjusting the client record. Everything already saved is complete and nothing is lost.',
        ],

        'cash' => [
            'label' => 'Cash',
            'message' => 'We are reviewing inflows and outflows. Registered movements stay saved.',
        ],

        'inventory' => [
            'label' => 'Inventory',
            'message' => 'We are working on articles and units. Nothing already saved is lost.',
        ],

        'credits' => [
            'label' => 'Credits',
            'message' => 'We are testing the new version of the credits module. Credits and their payments are not lost: it is just unavailable for a few days.',
        ],

        'administration' => [
            'label' => 'Administration',
            'message' => 'We are tidying banks and users. The rest of the system works as usual.',
        ],

        'settings' => [
            'label' => 'Settings',
            'message' => 'We are adjusting tags, roles and translations. Workshop data is not affected.',
        ],
    ],
];

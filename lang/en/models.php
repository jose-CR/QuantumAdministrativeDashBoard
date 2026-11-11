<?php

return [
    'users' => [
        'navigation' => 'Users',
        'singular' => 'User',
        'plural' => 'Users',
    ],

    'clients' => [
        'navigation' => 'Clients',  
        'singular' => 'Client',
        'plural' => 'Clients', 
    ],

    'banks' => [
        'navigation' => 'Banks',
        'singular' => 'bank',
        'plural' => 'banks',
    ],

    'inventary' => [
        'category' => [
            'navigation' => 'Categories',
            'singular' => 'Category',
            'plural' => 'Categories',
        ],

        'article_units' => [
            'navigation' => 'Article Units',
            'singular' => 'Article Unit',
            'plural' => 'Article Units',
        ],

        'article' => [
            'navigation' => 'Articles',
            'singular' => 'Article',
            'plural' => 'Articles', 
        ],
    ],

    'credits' => [
        'payment_histories' => [
            'navigation' => 'Payment Histories',
            'singular' => 'Payment History',
            'plural' => 'Payment Histories', 
        ],

        'loans' => [
            'navigation' => 'Credits',
            'singular' => 'Credit',
            'plural' => 'Credits', 
        ],

        'installment' => [
            'navigation' => 'Installments',
            'singular' => 'Installment',
            'plural' => 'Installments', 
        ],
    ],

    'flow' => [
        'outflow' => [
            'navigation' => 'Outflows',
            'singular' => 'Outflow',
            'plural' => 'Outflows', 
        ],

        'inflow' => [
            'navigation' => 'Inflows',
            'singular' => 'Inflow',
            'plural' => 'Inflows', 
        ]
    ],

    'admin_panels' => [
        'translate' => 'Translations'
    ],

    'others' => [
        'administration' => 'administration',
        'inventary' => 'Inventary',
        'credits' => 'Credits',
        'clients' => 'Clients',
        'cash' => 'Cash',
        'settings' => 'Settings',
        'general' => 'General',
        'apps' => 'Apps',
        'data_transfer' => 'Import & export',
        'data_transfer_hint' => 'Every module spreadsheet, in one place.',
        'data_transfer_export' => 'Export',
        'data_transfer_import' => 'Import',
        'data_transfer_all' => 'See every area',
        'apps_subheading' => 'Pick a module to start working.',
        'descriptions' => [
            'clients' => 'Client records, references and attachments',
            'cash' => 'Inflows, outflows and cash flow',
            'inventory' => 'Articles, units, categories and transport',
            'credits' => 'Credits, installments and payment history',
            'administration' => 'Banks, users and roles',
            'settings' => 'Tags, roles and translations',
            'general' => 'General panel shortcuts',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Import & export (single data screen)
    |--------------------------------------------------------------------------
    */
    'data_transfer' => [
        'customers' => [
            'label' => 'Customers',
            'description' => 'Full record: document, contact, address and references.',
        ],
        'articles' => [
            'label' => 'Articles',
            'description' => 'Catalog with category, prices and stock.',
        ],
        'article_units' => [
            'label' => 'Serialized units',
            'description' => 'VIN or serial number, status and price of each unit.',
        ],
        'categories' => [
            'label' => 'Categories',
            'description' => 'Inventory category tree.',
        ],
        'loans' => [
            'label' => 'Credits',
            'description' => 'Whole credit: customer, amounts, installments and interest.',
        ],
        'installments' => [
            'label' => 'Installments',
            'description' => 'Installments of every credit, with balance and status.',
        ],
        'payments' => [
            'label' => 'Payments',
            'description' => 'Receipts: credit, installment, amount, bank and date.',
        ],
        'inflows' => [
            'label' => 'Inflows',
            'description' => 'Sales: invoice, amount, collected, receivable and items.',
        ],
        'outflows' => [
            'label' => 'Outflows',
            'description' => 'Purchases and expenses: vendor, invoice, amount and area.',
        ],
        'banks' => [
            'label' => 'Banks',
            'description' => 'Bank catalog used by payments.',
        ],
    ],
];

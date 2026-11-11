<?php

return [
    'users' => [
        'navigation' => 'Usuarios',
        'singular' => 'Usuario',
        'plural' => 'Usuarios',
    ],

    'clients' => [
        'navigation' => 'Clientes',
        'singular' => 'Cliente',
        'plural' => 'Clientes',
    ],

    'banks' => [
        'navigation' => 'Bancos',
        'singular' => 'Banco',
        'plural' => 'Bancos',
    ],

    'inventary' => [
        'category' => [
            'navigation' => 'Categorías',
            'singular' => 'Categoría',
            'plural' => 'Categorías',
        ],

        'article_units' => [
            'navigation' => 'Unidades de Artículo',
            'singular' => 'Unidad de Artículo',
            'plural' => 'Unidades de Artículo',
        ],

        'article' => [
            'navigation' => 'Artículos',
            'singular' => 'Artículo',
            'plural' => 'Artículos',
        ],
    ],

    'credits' => [
        'payment_histories' => [
            'navigation' => 'Historial de Pagos',
            'singular' => 'Historial de Pago',
            'plural' => 'Historiales de Pago',
        ],

        'loans' => [
            'navigation' => 'Créditos',
            'singular' => 'Crédito',
            'plural' => 'Créditos',
        ],

        'installment' => [
            'navigation' => 'Cuotas',
            'singular' => 'Cuota',
            'plural' => 'Cuotas',
        ],
    ],

    'flow' => [
        'outflow' => [
            'navigation' => 'Egresos',
            'singular' => 'Egreso',
            'plural' => 'Egresos',
        ],

        'inflow' => [
            'navigation' => 'Ingresos',
            'singular' => 'Ingreso',
            'plural' => 'Ingresos',
        ],
    ],

    'admin_panels' => [
        'translate' => 'Traducciones',
    ],

    'others' => [
        'administration' => 'Administración',
        'inventary' => 'Inventario',
        'credits' => 'Créditos',
        'clients' => 'Clientes',
        'cash' => 'Caja',
        'settings' => 'Configuración',
        'general' => 'General',
        'apps' => 'Aplicaciones',
        'data_transfer' => 'Importar y exportar',
        'data_transfer_hint' => 'Los Excel de todos los módulos, en un solo lugar.',
        'data_transfer_export' => 'Exportar',
        'data_transfer_import' => 'Importar',
        'data_transfer_all' => 'Ver todas las áreas',
        'apps_subheading' => 'Elige un módulo para empezar a trabajar.',
        'descriptions' => [
            'clients' => 'Fichas, referencias y adjuntos de clientes',
            'cash' => 'Ingresos, egresos y flujo de caja',
            'inventory' => 'Artículos, unidades, categorías y transportes',
            'credits' => 'Créditos, cuotas e historial de pagos',
            'administration' => 'Bancos, usuarios y roles',
            'settings' => 'Etiquetas, roles y traducciones',
            'general' => 'Accesos generales del panel',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Importar y exportar (pantalla única de datos)
    |--------------------------------------------------------------------------
    */
    'data_transfer' => [
        'customers' => [
            'label' => 'Clientes',
            'description' => 'Ficha completa: documento, contacto, dirección y referencias.',
        ],
        'articles' => [
            'label' => 'Artículos',
            'description' => 'Catálogo con categoría, precios y existencias.',
        ],
        'article_units' => [
            'label' => 'Unidades con serie',
            'description' => 'VIN o número de serie, estado y precio de cada unidad.',
        ],
        'categories' => [
            'label' => 'Categorías',
            'description' => 'Árbol de categorías del inventario.',
        ],
        'loans' => [
            'label' => 'Créditos',
            'description' => 'Crédito completo: cliente, montos, cuotas e interés.',
        ],
        'installments' => [
            'label' => 'Cuotas',
            'description' => 'Cuotas de todos los créditos, con saldo y estado.',
        ],
        'payments' => [
            'label' => 'Pagos',
            'description' => 'Recibos: crédito, cuota, monto, banco y fecha.',
        ],
        'inflows' => [
            'label' => 'Entradas',
            'description' => 'Ventas: factura, monto, cobrado, por cobrar y artículos.',
        ],
        'outflows' => [
            'label' => 'Salidas',
            'description' => 'Compras y gastos: proveedor, factura, monto y área.',
        ],
        'banks' => [
            'label' => 'Bancos',
            'description' => 'Catálogo de bancos para los pagos.',
        ],
    ],
];

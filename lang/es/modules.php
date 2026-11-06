<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Interruptor de módulos — textos en español
    |--------------------------------------------------------------------------
    |
    | Todo lo que ve una persona está aquí: el nombre del módulo, lo que lee el
    | usuario bloqueado y lo que lee el administrador. El config solo guarda
    | rutas y estados, así que traducir el panel traduce también esta pantalla.
    |
    */

    'states' => [
        'enabled' => 'Habilitado',
        'preview' => 'En pruebas',
        'off' => 'Deshabilitado',
    ],

    'state_descriptions' => [
        'enabled' => 'Todos pueden entrar.',
        'preview' => 'Los usuarios ven la página de aviso; los administradores pasan.',
        'off' => 'Nadie entra, ni los administradores.',
    ],

    'blocked' => [
        // Pestaña del navegador
        'title' => 'Módulo no disponible',

        'panel' => 'Panel administrativo',
        'status' => 'Estado',
        'module' => 'Módulo',
        'code' => 'Código',
        'code_value' => '403',

        'lost_nothing' => [
            'title' => 'No se perdió nada.',
            'text' => 'Los registros que ya existen siguen guardados y en orden.',
        ],
        'rest_works' => [
            'title' => 'El resto del sistema funciona.',
            'text' => 'Podés seguir usando los otros módulos con normalidad.',
        ],
        'reopens' => [
            'title' => 'Se habilita solo.',
            'text' => 'No hace falta que hagas nada: avisamos cuando esté listo.',
        ],

        'home' => 'Volver al panel',
        'retry' => 'Reintentar',
        'footer' => 'Sistema operativo · El resto del panel sigue disponible',
    ],

    'admin' => [
        'badge' => 'Vista de administrador.',
        'explain' => 'Estás viendo esta página porque el módulo :module está en «:state». Estamos verificando el módulo antes de abrirlo a todo el personal. Para habilitarlo:',
        'command' => 'php artisan module:state :module enabled',
        'hint' => 'Esta caja no la ve el usuario normal.',
    ],

    'command' => [
        'title' => 'Estado de los módulos',
        'module' => 'Módulo',
        'state' => 'Estado',
        'routes' => 'Rutas',
        'unknown_module' => 'No existe el módulo ":module". Disponibles: :available',
        'invalid_state' => 'Estado inválido: ":state". Usá: :states',
        'updated' => 'Módulo ":module" ahora está en :state — :consequence',
        'reset' => 'Para volver todo al estado de fábrica, borrá: :file',
    ],

    'modules' => [
        'clients' => [
            'label' => 'Clientes',
            'message' => 'Estamos ajustando la ficha de clientes. Los datos ya cargados están completos y no se pierde nada.',
        ],

        'cash' => [
            'label' => 'Caja',
            'message' => 'Estamos revisando los ingresos y egresos. Los movimientos registrados siguen guardados.',
        ],

        'inventory' => [
            'label' => 'Inventario',
            'message' => 'Estamos trabajando en el inventario de artículos y unidades. Nada de lo cargado se pierde.',
        ],

        'credits' => [
            'label' => 'Créditos',
            'message' => 'Estamos probando la nueva versión del módulo de créditos. Los créditos y sus pagos no se pierden: solo no está disponible por unos días.',
        ],

        'administration' => [
            'label' => 'Administración',
            'message' => 'Estamos ordenando bancos y usuarios. El resto del sistema funciona normal.',
        ],

        'settings' => [
            'label' => 'Configuración',
            'message' => 'Estamos ajustando etiquetas, roles y traducciones. No afecta los datos del taller.',
        ],
    ],
];

<?php

return [
    'users' => [
        'create' => [
            'title' => 'Usuario creado',
            'body' => 'El usuario fue creado correctamente.',
        ],

        'delete' => [
            'title' => 'Usuarios eliminados',
            'body' => 'Los usuarios seleccionados fueron eliminados correctamente.',
        ],

        'update' => [
            'title' => 'Usuario actualizado',
            'body' => 'El usuario fue actualizado correctamente.',
        ],
    ],

    'inventary' => [

        // Sufijo de la lista de créditos que impiden borrar: "#12 (Ana), #34 (Luis) y 3 más".
        'more_credits' => 'y :count más',

        'category' => [
            'create' => [
                'title' => 'Categoría creada',
                'body' => 'La categoría fue creada correctamente.',
            ],

            'update' => [
                'title' => 'Categoría actualizada',
                'body' => 'La categoría fue actualizada correctamente.',
            ],

            'delete' => [
                'title' => 'Categorías eliminadas',
                'body' => 'Las categorías seleccionadas fueron eliminadas correctamente.',
                // Se muestra en la confirmación antes de borrar.
                'cascade' => 'También se eliminarán :articles artículos con sus :units unidades. Esta acción no se puede deshacer.',
            ],

            'restore' => [
                'title' => 'Categoría restaurada',
                'body' => 'La categoría fue restaurada correctamente.',
            ],

            'force_delete' => [
                'title' => 'Categoría eliminada permanentemente',
                'body' => 'La categoría fue eliminada permanentemente.',
            ],

            // Bloqueo por integridad: hay unidades usadas en créditos.
            'blocked' => [
                'title' => 'No se puede eliminar la categoría',
                'body' => '«:name» tiene :units unidades usadas en créditos: :credits. Sácalas de esos créditos antes de borrar la categoría.',
                'bulk' => 'Estas categorías tienen unidades usadas en créditos: :names. No se eliminó ninguna.',
            ],
        ],

        'articleUnits' => [
            'create' => [
                'title' => 'Unidad de artículo creada',
                'body' => 'La unidad de artículo fue creada correctamente.',
            ],

            'update' => [
                'title' => 'Unidad de artículo actualizada',
                'body' => 'La unidad de artículo fue actualizada correctamente.',
            ],

            'delete' => [
                'title' => 'Unidades de artículo eliminadas',
                'body' => 'Las unidades de artículo seleccionadas fueron eliminadas correctamente.',
            ],

            'restore' => [
                'title' => 'Unidad de artículo restaurada',
                'body' => 'La unidad de artículo fue restaurada correctamente.',
            ],

            'force_delete' => [
                'title' => 'Unidad de artículo eliminada permanentemente',
                'body' => 'La unidad de artículo fue eliminada permanentemente.',
            ],
        ],

        'article' => [
            'create' => [
                'title' => 'Artículo creado',
                'body' => 'El artículo fue creado correctamente.',
            ],

            'update' => [
                'title' => 'Artículo actualizado',
                'body' => 'El artículo fue actualizado correctamente.',
            ],

            'delete' => [
                'title' => 'Artículos eliminados',
                'body' => 'Los artículos seleccionados fueron eliminados correctamente.',
                // Se muestra en la confirmación antes de borrar.
                'cascade' => 'También se eliminarán sus :units unidades. Esta acción no se puede deshacer.',
            ],

            'restore' => [
                'title' => 'Artículo restaurado',
                'body' => 'El artículo fue restaurado correctamente.',
            ],

            'force_delete' => [
                'title' => 'Artículo eliminado permanentemente',
                'body' => 'El artículo fue eliminado permanentemente.',
            ],

            // Bloqueo por integridad: hay unidades usadas en créditos.
            'blocked' => [
                'title' => 'No se puede eliminar el artículo',
                'body' => '«:name» tiene :units unidades usadas en créditos: :credits. Sácalas de esos créditos antes de borrar el artículo.',
                'bulk' => 'Estos artículos tienen unidades usadas en créditos: :names. No se eliminó ninguno.',
            ],
        ],
    ],

    'clients' => [
        'create' => [
            'title' => 'Cliente creado',
            'body' => 'El cliente fue creado correctamente.',
        ],

        'delete' => [
            'blocked_title' => 'No se puede eliminar el cliente',
            'blocked_modal' => 'Este cliente tiene historial: no se puede eliminar.',
            'blocked_body' => 'Tiene :credits créditos (:list) y :invoices facturas. Borrarlo se llevaría su historial de pagos.',
            'title' => 'Cliente eliminado',
            'body' => 'El cliente fue eliminado correctamente.',
        ],

        'update' => [
            'title' => 'Cliente actualizado',
            'body' => 'El cliente fue actualizado correctamente.',
        ],

        'deletes' => [
            'title' => 'Clientes eliminados',
            'body' => 'Los clientes seleccionados fueron eliminados correctamente.',
        ],
    ],

    'alert' => [
        'create' => [
            'title' => 'Alerta creada',
            'body' => 'La alerta fue creada correctamente.',
        ],

        'message' => '%s debe pagar la cuota #%d el %s.'
    ],
];
<?php

return [
    'users' => [
        'create' => [
            'title' => 'User created',
            'body' => 'The user was created successfully.',
        ],

        'delete' => [
            'title' => 'Users deleted',
            'body' => 'The selected users were deleted successfully.',
        ],

        'update' => [
            'title' => 'User updated',
            'body' => 'The user was updated successfully.',
        ],
    ],

    'inventary' => [
        'category' => [
            'create' => [
                'title' => 'Category created',
                'body' => 'The category was created successfully.',
            ],

            'update' => [
                'title' => 'Category updated',
                'body' => 'The category was updated successfully.',
            ],

            'delete' => [
                'title' => 'Categories deleted',
                'body' => 'The selected categories were deleted successfully.',
            ],

            'restore' => [
                'title' => 'Category restored',
                'body' => 'The category was restored successfully.',
            ],

            'force_delete' => [
                'title' => 'Category permanently deleted',
                'body' => 'The category was permanently deleted successfully.',
            ],

            'blocked' => [
                'title' => 'The category cannot be deleted',
                'body' => '":name" has articles linked to it. Move them to another category or delete them first.',
                'bulk' => 'These categories have articles: :names. None was deleted.',
            ],
        ],

        'articleUnits' => [
            'create' => [
                'title' => 'Article Unit created',
                'body' => 'The article unit was created successfully.',
            ],

            'update' => [
                'title' => 'Article Unit updated',
                'body' => 'The article unit was updated successfully.',
            ],

            'delete' => [
                'title' => 'Article Units deleted',
                'body' => 'The selected article units were deleted successfully.',
            ],

            'restore' => [
                'title' => 'Article Unit restored',
                'body' => 'The article unit was restored successfully.',
            ],

            'force_delete' => [
                'title' => 'Article Unit permanently deleted',
                'body' => 'The article unit was permanently deleted successfully.',
            ],
        ],

        'article' => [
            'create' => [
                'title' => 'Article created',
                'body' => 'The article was created successfully.',
            ],

            'update' => [
                'title' => 'Article updated',
                'body' => 'The article was updated successfully.',
            ],

            'delete' => [
                'title' => 'Articles deleted',
                'body' => 'The selected articles were deleted successfully.',
            ],

            'restore' => [
                'title' => 'Article restored',
                'body' => 'The article was restored successfully.',
            ],

            'force_delete' => [
                'title' => 'Article permanently deleted',
                'body' => 'The article was permanently deleted successfully.',
            ],

            'blocked' => [
                'title' => 'The article cannot be deleted',
                'body' => '":name" has registered units. Delete them first if it really no longer exists.',
                'bulk' => 'These articles have registered units: :names. None was deleted.',
            ],
        ],
    ],

    'clients' => [
        'create' => [
            'title' => 'User created',
            'body' => 'The user was created successfully.',
        ],

        'delete' => [
            'title' => 'Client deleted',
            'body' => 'The  clients were deleted successfully.',
        ],

        'update' => [
            'title' => 'User updated',
            'body' => 'The user was updated successfully.',
        ],

        'deletes' => [
            'title' => 'Clients deleted',
            'body' => 'The selected clients were deleted successfully.',
        ],
    ],

    'alert' => [
        'create' => [
            'title' => 'Alert created',
            'body' => 'The alert was created successfully.',
        ],
        
        'message' => '%s must pay installment #%d on %s.',
    ],
];
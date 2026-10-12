<?php

return [

    'users' => [

        'user' => 'Usuario',
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
        'last_seen' => 'Última conexión',
    ],

    'clients' => [

        'sections' => [

            'client' => 'Cliente',
            'financed_article' => 'Artículo financiado',
            'credit_summary' => 'Resumen del crédito',
            'credit_status' => 'Estado del crédito',
            'latest_payments' => 'Últimos pagos',
            'latest_payments_description' => 'Historial reciente de pagos del crédito.',
            'personal_information' => 'Información personal',
            'contact' => 'Contacto',
            'references' => 'Referencias',
            'credit' => 'Crédito',
        ],

        'fields' => [

            'full_name' => 'Nombre completo',
            'phones' => 'Teléfono',
            'identity_document' => 'Documento de identidad',

            'document_type' => [

                'document_type' => 'Tipo de documento',
                'DUI' => 'DUI',
                'NIT' => 'NIT',
                'PASSPORT' => 'Pasaporte',
                'RES_CARNET' => 'Carné de residencia',
                'Otro' => 'Otro',
            ],

            'document_number' => 'Número de documento',
            'identity_document_placeholder' => 'Ingrese el documento de identidad',
            'birth_date' => 'Fecha de nacimiento',
            'gender' => 'Género',
            'nationality' => 'Nacionalidad',
            'phone_primary' => 'Teléfono principal',
            'phone_secondary' => 'Teléfono secundario',
            'email' => 'Correo electrónico',
            'address' => 'Dirección',
            'vehicle' => 'Vehículo',
            'article' => 'Artículo',
            'initial_amount' => 'Monto inicial',
            'down_payment' => 'Prima',
            'installments' => 'Cuotas',
            'installment_amount' => 'Monto de la cuota',
            'total_amount' => 'Monto total',
            'pending_balance' => 'Saldo pendiente',
            'start_date' => 'Fecha de inicio',
            'payment_day' => 'Día de pago',
            'periodicity' => 'Frecuencia de pago',

            'status' => [

                'status' => 'Estado',
                'active' => 'Activo',
                'refinanced' => 'Refinanciado',
                'closed' => 'Cerrado',
            ],

            'refinanced_from' => 'Refinanciado de',
            'reference_type' => 'Tipo de referencia',
            'relationship' => 'Parentesco',
            'phone' => 'Teléfono',
            'occupation' => 'Ocupación',
            'remaining_installments' => 'Cuotas restantes',
            'credit_progress' => 'Progreso',
            'recent_payments' => 'Pagos recientes',
            'payment_date' => 'Fecha de pago',
            'amount' => 'Monto',
            'payment_method' => 'Método de pago',
            'receipt_number' => 'Número de comprobante',
            'marital_status' => 'Estado civil',
            'type' => 'Tipo',
            'bank' => 'Banco',
            'price' => 'Precio',

            'tags' => [

                'tags' => 'Etiquetas',
            ],

            'enterprise' => [

                'nrc' => 'NRC',
                'economic_activity' => 'Actividad económica',
                'location' => 'Ubicación',
                'departament' => 'Departamento',
                'municipality' => 'Municipio',
                'distric' => 'Distrito',
                'create_at' => 'Creado el',
                'updated_at' => 'Actualizado el',
            ],
        ],

        'genders' => [

            'male' => 'Masculino',
            'female' => 'Femenino',
        ],

        'marital_statuses' => [

            'single' => 'Soltero',
            'married' => 'Casado',
            'divorced' => 'Divorciado',
            'widowed' => 'Viudo',
        ],

        'reference_types' => [

            'family' => 'Familiar',
            'friend' => 'Amigo',
        ],

        'periodicities' => [

            'weekly' => 'Semanal',
            'biweekly' => 'Quincenal',
            'monthly' => 'Mensual',
        ],

        'statuses' => [

            'pending' => 'Pendiente',
            'partial' => 'Parcial',
            'active' => 'Activo',
            'paid' => 'Pagado',
            'cancelled' => 'Cancelado',
            'completed' => 'Completado',
        ],

        'messages' => [

            'no_credits' => 'No hay créditos registrados',
            'progress_empty' => '0%',
            'remaining_installments_format' => ':remaining de :total',
            'new_reference' => 'Nueva referencia',
        ],

        'actions' => [

            'add_reference' => 'Agregar referencia',
        ],
    ],

    'inventary' => [

        'category' => [

            'id' => 'ID',
            'name' => 'Categoría',
            'description' => 'Descripción',
        ],

        'article_units' => [

            'id' => 'ID',
            'article' => 'Artículo',
            'brand' => 'Marca',
            'model' => 'Modelo',
            'vin' => 'VIN',
            'engine_number' => 'Número de motor',
            'cash_price' => 'Precio al contado',
            'plate' => 'Placa',
            'color' => 'Color',
            'status' => 'Estado',
        ],

        'article' => [

            'id' => 'ID',
            'category' => 'Categoría',
            'article' => 'Artículo',
            'brand' => 'Marca',
            'model' => 'Modelo',
            'year' => 'Año',
            'color' => 'Color',
            'cash_price' => 'Precio al contado',
            'description' => 'Descripción',
            'created_at' => 'Creado el',
        ],
    ],

    'credits' => [

        'clients' => [

            'client' => 'Cliente',
            'identity_document' => 'DUI',
            'phone_primary' => 'Teléfono principal',
            'address' => 'Dirección',
            'vehicle' => 'Vehículo',
            'refinanced' => 'Refinanciar crédito',
            'status' => 'Estado',

            'pay_installment' => [

                'installment' => 'Cuota',
                'installment_to_pay' => 'Cuota a pagar',
                'amount' => 'Monto a pagar',
                'payment_method' => 'Método de pago',

                'payment_methods' => [

                    'cash' => 'Efectivo',
                    'card' => 'Tarjeta',
                    'bank_transfer' => 'Transferencia bancaria',
                    'transfer' => 'Transferencia',
                ],

                'bank' => 'Banco',
                'receipt_number' => 'Número de comprobante',
                'payment_date' => 'Fecha de pago',
                'installment_format' => 'Cuota #:number - Saldo: $:balance',
            ],

            'refinance' => [

                'current_credit_section' => 'Crédito actual',
                'current_credit_description' => 'Información del crédito que será refinanciado.',
                'new_credit_section' => 'Nuevo crédito',
                'new_credit_description' => 'Ingrese la información del nuevo crédito.',

                'current_credit' => 'Crédito actual',
                'pending_balance' => 'Saldo pendiente',
                'remaining_installments' => 'Cuotas restantes',

                'initial_amount' => 'Monto financiado',
                'down_payment' => 'Prima',
                'installments' => 'Cuotas',
                'installment_amount' => 'Monto de la cuota',
                'periodicity' => 'Frecuencia de pago',
                'start_date' => 'Fecha de inicio',
                'payment_day' => 'Día de pago',

                'helper_initial_amount' => 'Puede ser diferente al saldo pendiente.',
                'credit_format' => 'Crédito #:credit • :article • (:installments cuotas)',

                'weekly' => 'Semanal',
                'biweekly' => 'Quincenal',
                'monthly' => 'Mensual',
            ],
        ],

        'payment_histories' => [

            'amount' => 'Monto',
            'payment_method' => 'Método de pago',
            'bank' => 'Banco',
            'payment_date' => 'Fecha de pago',
            'receipt_number' => 'Número de comprobante',
            'previous_balance' => 'Saldo anterior',
            'new_balance' => 'Nuevo saldo',
        ],

        'credits' => [

            'vehicle' => 'Vehículo',
            'down_payment' => 'Prima',
            'financed_amount' => 'Monto financiado',
            'installments' => 'Cuotas',
            'installment_amount' => 'Monto de la cuota',
            'pending_balance' => 'Saldo pendiente',
            'status' => 'Estado',
            'initial_amount' => 'Monto inicial',
            'interest_rate' => 'Tasa de interés',
            'total_interest' => 'Interés total',
            'total_amount' => 'Monto total',
            'periodicity' => 'Frecuencia de pago',
            'start_date' => 'Fecha de inicio',
            'payment_day' => 'Día de pago',
            'payment_month' => 'Mes de pago',
            'originalCredit' => 'Crédito original',
        ],

        'installment' => [

            'credit' => 'Crédito',
            'vehicle' => 'Vehículo',
            'number' => 'Cuota',
            'amount' => 'Monto',
            'remaining_balance' => 'Saldo restante',
            'paid_amount' => 'Monto pagado',
            'due_date' => 'Fecha de vencimiento',
            'paid_at' => 'Fecha de pago',
            'status' => 'Estado',
        ],
    ],

    'alert' => [

        'label' => 'Alerta',
        'assigned_user' => 'Usuario asignado',
        'installment' => 'Cuota',
        'type' => 'Tipo de alerta',
        'title' => 'Título',
        'alert_at' => 'Fecha y hora de la alerta',
        'message' => 'Mensaje',
        'upcoming_message' => '%s debe pagar la cuota #%d el %s.',
        'upcoming_payment' => 'Próximo pago',
        'title_placeholder' => 'Ej.: Recordar llamar al cliente',
        'message_placeholder' => 'Escriba el mensaje de la alerta...',
        'installment_format' => 'Cuota #%d • Vence: %s • Saldo: $%s',
    ],

    'payment_history' => [

        'cash' => 'Efectivo',
        'card' => 'Tarjeta',
        'bank_transfer' => 'Transferencia bancaria',
    ],

    'flow' => [

        'attachment' => 'Adjunto',
        'delete' => 'Eliminar esta factura',
        'see_attachment' => 'Ver adjunto',
        'close' => 'Cerrar',

        'outflow' => [

            'date' => 'Fecha',
            'company' => 'Empresa',
            'cod_invoice' => 'Número de factura',
            'quantity' => 'Cantidad',
            'amount' => 'Monto',
            'source' => 'Caja',
            'area' => 'Área',
            'description' => 'Descripción',
        ],

        'inflow' => [

            'date' => 'Fecha',
            'client' => 'Cliente',
            'cod_invoice' => 'Número de factura',
            'amount' => 'Monto',
            'description' => 'Descripción',
            'payment_method' => 'Método de pago',
            'transfer_number' => 'Número de transferencia',
            'transfer_date' => 'Fecha de transferencia',
            'payment_status' => 'Estado del pago',
            'salesperson' => 'Vendedor',
            'notes' => 'Notas',
        ],
    ],
];
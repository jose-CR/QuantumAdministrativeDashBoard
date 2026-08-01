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
            'latest_payments_description' => 'Historial reciente de pagos del crédito',

            'personal_information' => 'Información personal',
            'contact' => 'Contacto',
            'references' => 'Referencias',
            'credit' => 'Crédito',
        ],

        'fields' => [
            'full_name' => 'Nombre completo',
            'phones' => 'Teléfono',

            'identity_document' => 'Documento de identidad',
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
            'periodicity' => 'Periodicidad',

            'status' => 'Estado',
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
            'id' => 'Id',
            'name' => 'Categoría',
            'description' => 'Descripción',
        ],

        'article_units' => [
            'id' => 'Id',
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
            'id' => 'Id',
            'category' => 'Categoría',
            'article' => 'Artículo',
            'brand' => 'Marca',
            'model' => 'Modelo',
            'year' => 'Año',
            'color' => 'Color',
            'cash_price' => 'Precio al contado',
            'description' => 'Descripción',
            'created_at' => 'Creado',
        ],
    ],

    'credits' => [
        'clients' => [
            'client' => 'Cliente',
            'identity_document' => 'DUI',
            'phone_primary' => 'Teléfonos',
            'address' => 'Dirección',
            'vehicle' => 'Vehículo',
            'refinanced' => 'Crédito refinanciado',
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

                'periodicity' => 'Periodicidad',
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
            'periodicity' => 'Periodicidad',
            'start_date' => 'Fecha de inicio',
            'payment_day' => 'Día de pago',
            'payment_month' => 'Mes de pago',
            'originalCredit' => 'Refinanciamiento',
        ],

        'installment' => [
            'credit' => 'Crédito',
            'vehicle' => 'Vehículo',
            'number' => 'Cuota',
            'amount' => 'Monto',
            'remaining_balance' => 'Saldo restante',
            'paid_amount' => 'Monto abonado',
            'due_date' => 'Fecha de vencimiento',
            'paid_at' => 'Fecha de pago',
            'status' => 'Estado',
        ],
    ],

    'alert' => [
        'label' => 'Alerta',

        'assigned_user' => 'Asignar a',
        'installment' => 'Cuota',
        'type' => 'Tipo de alerta',
        'title' => 'Título',
        'alert_at' => 'Fecha y hora de la alerta',
        'message' => 'Contenido',

        'title_placeholder' => 'Ej.: Recordar llamar al cliente',
        'message_placeholder' => 'Escriba el mensaje de la alerta...',

        'installment_format' => 'Cuota #%d • Vence: %s • Saldo: $%s',
    ],

    'payment_history' => [
        'cash' => 'Efectivo',
        'card' => 'Tarjeta',
        'bank_transfer' => 'Tranferencia Bancaria'
    ]
];
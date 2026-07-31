<?php

return [
    'users' => [
        'user' => 'User',
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'last_seen' => 'Last conexion',
    ],

    'clients' => [

        'sections' => [
            'client' => 'Client',
            'financed_article' => 'Financed Article',
            'credit_summary' => 'Credit Summary',
            'credit_status' => 'Credit Status',
            'latest_payments' => 'Latest Payments',
            'latest_payments_description' => 'Recent payment history for the credit.',

            'personal_information' => 'Personal Information',
            'contact' => 'Contact',
            'references' => 'References',
            'credit' => 'Credit',
        ],

        'fields' => [
            'full_name' => 'Full Name',
            'phones' => 'Phone',

            'identity_document' => 'Identity Document',
            'identity_document_placeholder' => 'Enter identity document',

            'birth_date' => 'Birth Date',
            'gender' => 'Gender',
            'nationality' => 'Nationality',

            'phone_primary' => 'Primary Phone',
            'phone_secondary' => 'Secondary Phone',
            'email' => 'Email',
            'address' => 'Address',

            'vehicle' => 'Vehicle',

            'article' => 'Article',

            'initial_amount' => 'Initial Amount',
            'down_payment' => 'Down Payment',
            'installments' => 'Installments',
            'installment_amount' => 'Installment Amount',
            'total_amount' => 'Total Amount',
            'pending_balance' => 'Pending Balance',

            'start_date' => 'Start Date',
            'payment_day' => 'Payment Day',
            'periodicity' => 'Periodicity',

            'status' => 'Status',
            'refinanced_from' => 'Refinanced From',

            'reference_type' => 'Reference Type',
            'relationship' => 'Relationship',
            'phone' => 'Phone',
            'occupation' => 'Occupation',

            'remaining_installments' => 'Remaining Installments',
            'credit_progress' => 'Progress',

            'recent_payments' => 'Recent Payments',

            'payment_date' => 'Payment Date',
            'amount' => 'Amount',
            'payment_method' => 'Payment Method',
            'receipt_number' => 'Receipt Number',
            'marital_status' => 'Marital Status',

            'type' => 'Type',

            'bank' => 'Bank',
        ],

        'genders' => [
            'male' => 'Male',
            'female' => 'Female',
        ],

        'marital_statuses' => [
            'single' => 'Single',
            'married' => 'Married',
            'divorced' => 'Divorced',
            'widowed' => 'Widowed',
        ],

        'reference_types' => [
            'family' => 'Family',
            'friend' => 'Friend',
        ],

        'periodicities' => [
            'weekly' => 'Weekly',
            'biweekly' => 'Biweekly',
            'monthly' => 'Monthly',
        ],

        'statuses' => [
            'pending' => 'Pending',
            'active' => 'Active',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        ],

        'messages' => [
            'no_credits' => 'No credits registered',
            'progress_empty' => '0%',
            'remaining_installments_format' => ':remaining of :total',
            'new_reference' => 'New Reference',
        ],

        'actions' => [
            'add_reference' => 'Add Reference',
        ],
    ],

    'inventary' => [
        'category' => [
            'name' => 'Category',
            'description' => 'description',
        ],

        'article_units' => [
            'brand' => 'Brand',
            'model' => 'Model',
            'vin' => 'Vin',
            'engine_number' => 'Engine number',
            'cash_price' => 'Cash price',
            'plate' => 'Plate',
            'color' => 'Color',
            'status' => 'Status',
        ],

        'article' => [
            'category' => 'Category',
            'article' => 'Article',
            'brand' => 'Brand',
            'model' => 'Model',
            'year' => 'Year',
            'color' => 'Color',
            'cash_price' => 'Cash price',
            'description' => 'description',
            'created_at' => 'created',
        ]
    ],

    'credits' => [
        'clients' => [
            'client' => 'Client',
            'identity_document' => 'DUI',
            'phone_primary' => 'Telephones',
            'address' => 'Address',
            'vehicle' => 'Vehicle',
            'refinanced' => 'Refinance Credit',
            'status' => 'Status',

            'pay_installment' => [
                'installment' => 'Installment',
                'installment_to_pay' => 'Installment to Pay',
                'amount' => 'Amount to Pay',
                'payment_method' => 'Payment Method',

                'payment_methods' => [
                    'cash' => 'Cash',
                    'card' => 'Card',
                    'bank_transfer' => 'Bank Transfer',
                ],

                'bank' => 'Bank',
                'receipt_number' => 'Receipt Number',
                'payment_date' => 'Payment Date',

                'installment_format' => 'Installment #:number - Balance: $:balance',
            ],

            'refinance' => [
                // Sections
                'current_credit_section' => 'Current Credit',
                'current_credit_description' => 'Information about the credit that will be refinanced.',

                'new_credit_section' => 'New Credit',
                'new_credit_description' => 'Enter the information for the new credit.',

                // Fields
                'current_credit' => 'Current Credit',
                'pending_balance' => 'Pending Balance',
                'remaining_installments' => 'Remaining Installments',

                'initial_amount' => 'Financed Amount',
                'down_payment' => 'Down Payment',
                'installments' => 'Installments',
                'installment_amount' => 'Installment Amount',

                'periodicity' => 'Periodicity',
                'start_date' => 'Start Date',
                'payment_day' => 'Payment Day',

                // Helper texts
                'helper_initial_amount' => 'It may be different from the pending balance.',
                'credit_format' => 'Credit #:credit • :article • (:installments installments)',

                // Options
                'weekly' => 'Weekly',
                'biweekly' => 'Biweekly',
                'monthly' => 'Monthly',
            ],
        ],

        'payment_histories' => [
            'amount' => 'Amount',
            'payment_method' => 'Payment Method',
            'bank' => 'Bank',
            'payment_date' => ' Dia de pago',
            'receipt_number' => 'Receipt Number',
            'previous_balance' => 'Previous Balance',
            'new_balance' => 'New Balance',
        ],

        'credits' => [
            'vehicle' => 'Vehicle',
            'down_payment' => 'Down Payment',
            'financed_amount' => 'Financed Amount',
            'installments' => 'Installments',
            'installment_amount' => 'Installment Amount',
            'pending_balance' => 'Pending Balance',
            'status' => 'Status',
            'initial_amount' => 'Initial Amount',
            'interest_rate' => 'Interest Rate',
            'total_interest' => 'Total Interest',
            'total_amount' => 'Total Amount',
            'periodicity' => 'Periodicity',
            'start_date' => 'Start Date',
            'payment_day' => 'Payment Day',
            'payment_month' => 'Payment Month',
            'originalCredit' => 'Refinanciamiento',
        ],

        'installment' => [
            'credit' => 'Credit',
            'vehicle' => 'Vehicle',
            'number' => 'Installment',
            'amount' => 'Amount',
            'remaining_balance' => 'Remaining Balance',
            'paid_amount' => 'Paid Amount',
            'due_date' => 'Due Date',
            'paid_at' => 'Paid At',
            'status' => 'Status',
        ],
    ],

    'alert' => [
        'label' => 'Alert',

        'assigned_user' => 'Assign To',
        'installment' => 'Installment',
        'type' => 'Alert Type',
        'title' => 'Title',
        'alert_at' => 'Alert Date and Time',
        'message' => 'Content',

        'title_placeholder' => 'E.g. Remember to call the client',
        'message_placeholder' => 'Write the alert message...',

        'installment_format' => 'Installment #%d • Due: %s • Balance: $%s',
    ],

    'payment_history' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'bank_transfer' => 'Bank Transfer'
    ]
];

<?php

return [

    'users' => [

        'user' => 'User',
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'last_seen' => 'Last Connection',
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

            'document_type' => [

                'document_type' => 'Document Type',
                'DUI' => 'DUI',
                'NIT' => 'NIT',
                'PASSPORT' => 'Passport',
                'RES_CARNET' => 'Residence Card',
                'Otro' => 'Other',
            ],

            'document_number' => 'Document Number',
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
            'periodicity' => 'Payment Frequency',

            'status' => [

                'status' => 'Status',
                'active' => 'Active',
                'refinanced' => 'Refinanced',
                'closed' => 'Closed',
            ],

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
            'price' => 'Price',

            'tags' => [

                'tags' => 'Tags',
            ],

            'enterprise' => [

                'nrc' => 'NRC',
                'economic_activity' => 'Economic Activity',
                'location' => 'Location',
                'departament' => 'Department',
                'municipality' => 'Municipality',
                'distric' => 'District',
                'create_at' => 'Created At',
                'updated_at' => 'Updated At',
            ],
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
            'partial' => 'Partial',
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

            'id' => 'ID',
            'name' => 'Category',
            'description' => 'Description',
        ],

        'article_units' => [

            'id' => 'ID',
            'article' => 'Article',
            'brand' => 'Brand',
            'model' => 'Model',
            'vin' => 'VIN',
            'engine_number' => 'Engine Number',
            'cash_price' => 'Cash Price',
            'plate' => 'License Plate',
            'color' => 'Color',
            'status' => 'Status',
        ],

        'article' => [

            'id' => 'ID',
            'category' => 'Category',
            'article' => 'Article',
            'brand' => 'Brand',
            'model' => 'Model',
            'year' => 'Year',
            'color' => 'Color',
            'cash_price' => 'Cash Price',
            'description' => 'Description',
            'created_at' => 'Created At',
        ],
    ],

    'credits' => [
        'clients' => [

            'client' => 'Client',
            'identity_document' => 'DUI',
            'phone_primary' => 'Primary Phone',
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
                    'transfer' => 'Transfer'
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
                'periodicity' => 'Payment Frequency',
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
            'payment_date' => 'Payment Date',
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
            'periodicity' => 'Payment Frequency',
            'start_date' => 'Start Date',
            'payment_day' => 'Payment Day',
            'payment_month' => 'Payment Month',
            'originalCredit' => 'Original Credit',
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
        'assigned_user' => 'Assigned User',
        'installment' => 'Installment',
        'type' => 'Alert Type',
        'title' => 'Title',
        'alert_at' => 'Alert Date and Time',
        'message' => 'Message',
        'upcoming_message' => '%s must pay installment #%d on %s.',
        'upcoming_payment' => 'Upcoming Payment',
        'title_placeholder' => 'E.g. Remember to call the client',
        'message_placeholder' => 'Write the alert message...',
        'installment_format' => 'Installment #%d • Due: %s • Balance: $%s',
    ],

    'payment_history' => [

        'cash' => 'Cash',
        'card' => 'Card',
        'bank_transfer' => 'Bank Transfer',
    ],

    'flow' => [

        'attachment' => 'Attachment',
        'delete' => 'Delete this invoice',
        'see_attachment' => 'View Attachment',
        'close' => 'Close',

        'outflow' => [
            'date' => 'Date',
            'company' => 'Company',
            'cod_invoice' => 'Invoice Number',
            'quantity' => 'Quantity',
            'amount' => 'Amount',
            'source' => 'Cash Register',
            'area' => 'Area',
            'description' => 'Description',
        ],

        'inflow' => [
            'date' => 'Date',
            'client' => 'Client',
            'cod_invoice' => 'Invoice Number',
            'amount' => 'Amount',
            'description' => 'Description',
            'payment_method' => 'Payment Method',
            'transfer_number' => 'Transfer Number',
            'transfer_date' => 'Transfer Date',
            'payment_status' => 'Payment Status',
            'salesperson' => 'Sales Person',
            'notes' => 'Notes'
        ],

    ],

];
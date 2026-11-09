<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE inflows DROP CONSTRAINT IF EXISTS inflows_payment_method_check');
        DB::statement("ALTER TABLE inflows ADD CONSTRAINT inflows_payment_method_check CHECK (payment_method::text IN ('cash', 'card', 'transfer', 'cheque', 'quedan'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inflows DROP CONSTRAINT IF EXISTS inflows_payment_method_check');
        DB::statement("ALTER TABLE inflows ADD CONSTRAINT inflows_payment_method_check CHECK (payment_method::text IN ('cash', 'transfer'))");
    }
};

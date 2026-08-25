<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE article_units
            DROP CONSTRAINT article_units_status_check
        ");

        DB::statement("
            ALTER TABLE article_units
            ADD CONSTRAINT article_units_status_check
            CHECK (
                status IN (
                    'available',
                    'reserved',
                    'sold',
                    'rented',
                    'returned'
                )
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE article_units
            DROP CONSTRAINT article_units_status_check
        ");

        DB::statement("
            ALTER TABLE article_units
            ADD CONSTRAINT article_units_status_check
            CHECK (
                status IN (
                    'available',
                    'reserved',
                    'sold'
                )
            )
        ");
    }
};

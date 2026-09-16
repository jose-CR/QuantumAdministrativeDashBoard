<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inflows', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->date('date');
            $table->string('description')->nullable();
            $table->foreignId('customer_id')->constrained('customers');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'transfer']);
            $table->string('transfer_number')->nullable();
            $table->foreignId('bank_id')->nullable()->constrained('banks');
            $table->date('transfer_date')->nullable();
            $table->enum('payment_status', ['pending', 'partial', 'paid']);
            $table->string('salesperson');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inflows');
    }
};

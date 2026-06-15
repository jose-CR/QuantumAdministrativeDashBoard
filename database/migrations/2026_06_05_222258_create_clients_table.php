<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            // Personal information
            $table->string('first_name');
            $table->string('last_name');

            $table->string('identity_document')->nullable()->unique();

            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);

            $table->string('phone_primary');
            $table->string('phone_secondary')->nullable();

            $table->string('email')->nullable()->unique();

            $table->text('address');

            $table->string('occupation')->nullable();
            $table->string('workplace')->nullable();

            $table->decimal('monthly_income', 12, 2)->nullable();

            $table->string('marital_status')->nullable();
            $table->string('nationality')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

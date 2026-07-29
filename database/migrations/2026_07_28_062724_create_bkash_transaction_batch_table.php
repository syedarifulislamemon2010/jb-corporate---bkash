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
        Schema::create('bkash_transaction_batch', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_name', 150);
            $table->string('transaction_type', 20)->nullable(); 
            $table->string('total_data', 10)->default('0');
            $table->string('status_id', 10)->default('813');
            $table->string('created_by', 50)->nullable();
            $table->dateTime('create_date')->nullable();
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bkash_transaction_batch');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkash_transaction_batch', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_name', 255)->unique();
            $table->string('transaction_type', 20)->nullable(); 
            $table->char('sha256', 64)->nullable(); // SHA-256 integrity hash
            $table->integer('total_data')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->integer('status_id')->default(1000);
            $table->string('created_by', 50)->nullable();
            $table->dateTime('create_date')->nullable();
            $table->timestamps();
            $table->softDeletes(); 

            $table->index(['file_name']);
            $table->index(['status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkash_transaction_batch');
    }
};
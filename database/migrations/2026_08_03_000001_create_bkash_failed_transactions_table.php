<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkash_failed_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_id', 36)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->integer('row_number')->nullable();
            $table->string('transaction_type', 20)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->string('debit_account_no', 34)->nullable();
            $table->string('credit_account_no', 34)->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('failure_code', 50)->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['batch_id']);
            $table->index(['file_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkash_failed_transactions');
    }
};

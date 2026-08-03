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
        Schema::create('bkash_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_id', 36)->nullable();
            
            // Primary Transaction Details
            $table->string('transaction_type', 20)->nullable(); // A2A / BEFTN / RTGS
            $table->string('reference_id', 100);
            $table->dateTime('create_date')->nullable();
            $table->dateTime('return_date')->nullable();
            
            // Account & Financial Details
            $table->string('debit_account_title', 150)->nullable();
            $table->string('debit_account_no', 34)->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('debit_routing', 20)->nullable();
            $table->string('credit_routing', 20)->nullable();
            $table->string('credit_bank', 100)->nullable();
            $table->string('credit_account_no', 34)->nullable();
            $table->string('credit_account_title', 150)->nullable();
            
            // Status & Identifiers
            $table->string('txn_id', 50)->nullable();
            $table->string('reject_reason', 255)->nullable();
            $table->integer('status_id')->default(1000);
            $table->string('reason', 255)->nullable();
            
            // User Approvals (Dual Authorization)
            $table->string('created_by', 50)->nullable();
            $table->string('checked_by', 50)->nullable();
            $table->string('approved_by_1', 50)->nullable();
            $table->string('approved_by_2', 50)->nullable();
            $table->string('admin_approved_by', 50)->nullable();
            
            // Approval Timestamps
            $table->dateTime('checked_at')->nullable();
            $table->dateTime('approved_at_1')->nullable();
            $table->dateTime('approved_at_2')->nullable();
            $table->dateTime('admin_approved_at')->nullable();
            $table->dateTime('cbs_success_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Oracle Composite Indexes for Performance
            $table->index(['batch_id', 'status_id']);
            $table->index(['reference_id']);
            $table->index(['debit_account_no']);
            $table->index(['txn_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bkash_transactions');
    }
};
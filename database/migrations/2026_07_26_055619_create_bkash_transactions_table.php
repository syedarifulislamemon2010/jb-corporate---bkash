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
            $table->id();
            
            // Primary Transaction Details
            $table->string('transaction_type', 2)->nullable();
            $table->string('reference_id', 60);
            $table->dateTime('create_date')->nullable();
            $table->dateTime('return_date')->nullable();
            
            // Account & Financial Details
            $table->string('debit_account_title')->nullable();
            $table->string('debit_account_no', 60)->nullable();
            $table->string('amount', 20);
            $table->string('debit_routing', 20)->nullable();
            $table->string('credit_routing', 10);
            $table->string('credit_bank', 10)->nullable();
            $table->string('credit_account_no', 6)->nullable();
            
            // Status & Identifiers
            $table->string('txn_id', 20)->nullable();
            $table->string('reject_reason', 30)->nullable();
            $table->string('status_id', 10)->nullable();
            
            // User Approvals
            $table->string('created_by', 10)->nullable();
            $table->string('approved_by', 10)->nullable();
            $table->string('confirmed_by', 10)->nullable();
            $table->string('admin_approved', 10)->nullable();
            
            // Approval Timestamps
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('admin_approved_at')->nullable();
            $table->dateTime('cbs_success_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Soft Delete support
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
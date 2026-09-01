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
        Schema::table('bkash_transactions', function (Blueprint $table) {
            $table->renameColumn('credit_account_no', 'source_account_no');
            $table->renameColumn('debit_account_no', 'beneficiary_account_no');
        });

        if (Schema::hasTable('bkash_failed_transactions')) {
            Schema::table('bkash_failed_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('bkash_failed_transactions', 'credit_account_no')) {
                    $table->renameColumn('credit_account_no', 'source_account_no');
                }
                if (Schema::hasColumn('bkash_failed_transactions', 'debit_account_no')) {
                    $table->renameColumn('debit_account_no', 'beneficiary_account_no');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bkash_transactions', function (Blueprint $table) {
            $table->renameColumn('source_account_no', 'credit_account_no');
            $table->renameColumn('beneficiary_account_no', 'debit_account_no');
        });

        if (Schema::hasTable('bkash_failed_transactions')) {
            Schema::table('bkash_failed_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('bkash_failed_transactions', 'source_account_no')) {
                    $table->renameColumn('source_account_no', 'credit_account_no');
                }
                if (Schema::hasColumn('bkash_failed_transactions', 'beneficiary_account_no')) {
                    $table->renameColumn('beneficiary_account_no', 'debit_account_no');
                }
            });
        }
    }
};
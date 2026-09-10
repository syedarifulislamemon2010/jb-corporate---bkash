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
        $driver = strtolower(DB::getDriverName());

        if (Schema::hasTable('bkash_failed_transactions')) {
            if (in_array($driver, ['oracle', 'oci8'])) {
                // Direct native Oracle DDL
                if (Schema::hasColumn('bkash_failed_transactions', 'reference') && !Schema::hasColumn('bkash_failed_transactions', 'reference_id')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN reference TO reference_id');
                }

                if (Schema::hasColumn('bkash_failed_transactions', 'credit_account') && !Schema::hasColumn('bkash_failed_transactions', 'source_account_no')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN credit_account TO source_account_no');
                }

                if (Schema::hasColumn('bkash_failed_transactions', 'debit_account') && !Schema::hasColumn('bkash_failed_transactions', 'beneficiary_account_no')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN debit_account TO beneficiary_account_no');
                }

                if (!Schema::hasColumn('bkash_failed_transactions', 'txn_id')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions ADD txn_id VARCHAR2(100)');
                }
            } else {
                // Fallback for sqlite / mysql / other drivers
                Schema::table('bkash_failed_transactions', function (Blueprint $table) {
                    if (Schema::hasColumn('bkash_failed_transactions', 'reference') && !Schema::hasColumn('bkash_failed_transactions', 'reference_id')) {
                        $table->renameColumn('reference', 'reference_id');
                    }
                    if (Schema::hasColumn('bkash_failed_transactions', 'credit_account') && !Schema::hasColumn('bkash_failed_transactions', 'source_account_no')) {
                        $table->renameColumn('credit_account', 'source_account_no');
                    }
                    if (Schema::hasColumn('bkash_failed_transactions', 'debit_account') && !Schema::hasColumn('bkash_failed_transactions', 'beneficiary_account_no')) {
                        $table->renameColumn('debit_account', 'beneficiary_account_no');
                    }
                    if (!Schema::hasColumn('bkash_failed_transactions', 'txn_id')) {
                        $table->string('txn_id', 100)->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = strtolower(DB::getDriverName());

        if (Schema::hasTable('bkash_failed_transactions')) {
            if (in_array($driver, ['oracle', 'oci8'])) {
                if (Schema::hasColumn('bkash_failed_transactions', 'reference_id')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN reference_id TO reference');
                }
                if (Schema::hasColumn('bkash_failed_transactions', 'source_account_no')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN source_account_no TO credit_account');
                }
                if (Schema::hasColumn('bkash_failed_transactions', 'beneficiary_account_no')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN beneficiary_account_no TO debit_account');
                }
            } else {
                Schema::table('bkash_failed_transactions', function (Blueprint $table) {
                    if (Schema::hasColumn('bkash_failed_transactions', 'reference_id')) {
                        $table->renameColumn('reference_id', 'reference');
                    }
                    if (Schema::hasColumn('bkash_failed_transactions', 'source_account_no')) {
                        $table->renameColumn('source_account_no', 'credit_account');
                    }
                    if (Schema::hasColumn('bkash_failed_transactions', 'beneficiary_account_no')) {
                        $table->renameColumn('beneficiary_account_no', 'debit_account');
                    }
                });
            }
        }
    }
};

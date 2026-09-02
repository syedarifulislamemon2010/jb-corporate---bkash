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

        if (in_array($driver, ['oracle', 'oci8'])) {
            // Direct native Oracle DDL (supported in Oracle 12c+ without doctrine/dbal)
            DB::statement('ALTER TABLE bkash_transactions RENAME COLUMN credit_account_no TO source_account_no');
            DB::statement('ALTER TABLE bkash_transactions RENAME COLUMN debit_account_no TO beneficiary_account_no');

            if (Schema::hasTable('bkash_failed_transactions')) {
                if (Schema::hasColumn('bkash_failed_transactions', 'credit_account_no')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN credit_account_no TO source_account_no');
                }
                if (Schema::hasColumn('bkash_failed_transactions', 'debit_account_no')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN debit_account_no TO beneficiary_account_no');
                }
            }
        } else {
            // Fallback for sqlite / mysql / other drivers (dev/test environments)
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = strtolower(DB::getDriverName());

        if (in_array($driver, ['oracle', 'oci8'])) {
            DB::statement('ALTER TABLE bkash_transactions RENAME COLUMN source_account_no TO credit_account_no');
            DB::statement('ALTER TABLE bkash_transactions RENAME COLUMN beneficiary_account_no TO debit_account_no');

            if (Schema::hasTable('bkash_failed_transactions')) {
                if (Schema::hasColumn('bkash_failed_transactions', 'source_account_no')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN source_account_no TO credit_account_no');
                }
                if (Schema::hasColumn('bkash_failed_transactions', 'beneficiary_account_no')) {
                    DB::statement('ALTER TABLE bkash_failed_transactions RENAME COLUMN beneficiary_account_no TO debit_account_no');
                }
            }
        } else {
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
    }
};
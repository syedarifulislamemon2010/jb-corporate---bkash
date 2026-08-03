<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bkash_transactions')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('bkash_transactions', 'created_by')) {
                    $table->string('created_by', 255)->nullable()->change();
                }
                if (Schema::hasColumn('bkash_transactions', 'approved_by')) {
                    $table->string('approved_by', 255)->nullable()->change();
                }
                if (Schema::hasColumn('bkash_transactions', 'confirmed_by')) {
                    $table->string('confirmed_by', 255)->nullable()->change();
                }
                if (Schema::hasColumn('bkash_transactions', 'credit_account_title')) {
                    $table->string('credit_account_title', 500)->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('bkash_transaction_batch')) {
            Schema::table('bkash_transaction_batch', function (Blueprint $table) {
                if (Schema::hasColumn('bkash_transaction_batch', 'created_by')) {
                    $table->string('created_by', 255)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};

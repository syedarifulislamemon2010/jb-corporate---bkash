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
                $table->string('transaction_type', 50)->change();
            });
        }

        if (Schema::hasTable('bkash_transaction_batch')) {
            Schema::table('bkash_transaction_batch', function (Blueprint $table) {
                $table->string('transaction_type', 50)->change();
            });
        }

        if (Schema::hasTable('bkash_failed_transactions')) {
            Schema::table('bkash_failed_transactions', function (Blueprint $table) {
                $table->string('transaction_type', 50)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkash_transactions')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                $table->string('transaction_type', 4)->change();
            });
        }

        if (Schema::hasTable('bkash_transaction_batch')) {
            Schema::table('bkash_transaction_batch', function (Blueprint $table) {
                $table->string('transaction_type', 4)->change();
            });
        }

        if (Schema::hasTable('bkash_failed_transactions')) {
            Schema::table('bkash_failed_transactions', function (Blueprint $table) {
                $table->string('transaction_type', 4)->change();
            });
        }
    }
};

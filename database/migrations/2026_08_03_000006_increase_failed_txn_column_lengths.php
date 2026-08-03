<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bkash_failed_transactions')) {
            Schema::table('bkash_failed_transactions', function (Blueprint $table) {
                $table->string('reference_id', 500)->nullable()->change();
                $table->string('debit_account_no', 255)->nullable()->change();
                $table->string('credit_account_no', 255)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkash_failed_transactions')) {
            Schema::table('bkash_failed_transactions', function (Blueprint $table) {
                $table->string('reference_id', 100)->nullable()->change();
                $table->string('debit_account_no', 50)->nullable()->change();
                $table->string('credit_account_no', 50)->nullable()->change();
            });
        }
    }
};

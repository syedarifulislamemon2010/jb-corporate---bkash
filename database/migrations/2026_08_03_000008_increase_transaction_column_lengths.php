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
                $table->string('credit_routing', 100)->nullable()->change();
                $table->string('credit_bank', 255)->nullable()->change();
                $table->string('reference_id', 255)->nullable()->change();
                $table->string('debit_account_no', 100)->nullable()->change();
                $table->string('credit_account_no', 100)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkash_transactions')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                $table->string('credit_routing', 10)->nullable()->change();
                $table->string('credit_bank', 100)->nullable()->change();
                $table->string('reference_id', 100)->nullable()->change();
                $table->string('debit_account_no', 50)->nullable()->change();
                $table->string('credit_account_no', 50)->nullable()->change();
            });
        }
    }
};

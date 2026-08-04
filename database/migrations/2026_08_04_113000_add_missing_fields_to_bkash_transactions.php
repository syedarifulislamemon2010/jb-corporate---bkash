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
        if (Schema::hasTable('bkash_transactions')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('bkash_transactions', 'debit_routing')) {
                    $table->string('debit_routing', 20)->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'debit_account_title')) {
                    $table->string('debit_account_title', 150)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bkash_transactions')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('bkash_transactions', 'debit_routing')) {
                    $table->dropColumn('debit_routing');
                }
                if (Schema::hasColumn('bkash_transactions', 'debit_account_title')) {
                    $table->dropColumn('debit_account_title');
                }
            });
        }
    }
};

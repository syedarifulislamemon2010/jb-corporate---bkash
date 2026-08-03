<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bkash_transaction_batch')) {
            Schema::table('bkash_transaction_batch', function (Blueprint $table) {
                if (!Schema::hasColumn('bkash_transaction_batch', 'sha256')) {
                    $table->char('sha256', 64)->nullable();
                }
                if (!Schema::hasColumn('bkash_transaction_batch', 'total_amount')) {
                    $table->decimal('total_amount', 18, 2)->default(0)->after('total_data');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkash_transaction_batch')) {
            Schema::table('bkash_transaction_batch', function (Blueprint $table) {
                if (Schema::hasColumn('bkash_transaction_batch', 'sha256')) {
                    $table->dropColumn('sha256');
                }
                if (Schema::hasColumn('bkash_transaction_batch', 'total_amount')) {
                    $table->dropColumn('total_amount');
                }
            });
        }
    }
};

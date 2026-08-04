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
                if (!Schema::hasColumn('bkash_transactions', 'value_date')) {
                    $table->date('value_date')->nullable()->after('create_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkash_transactions')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('bkash_transactions', 'value_date')) {
                    $table->dropColumn('value_date');
                }
            });
        }
    }
};

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
                if (!Schema::hasColumn('bkash_transactions', 'file_name')) {
                    $table->string('file_name', 255)->nullable()->after('batch_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkash_transactions')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('bkash_transactions', 'file_name')) {
                    $table->dropColumn('file_name');
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bkash_transactions', 'row_sequence')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                $table->unsignedInteger('row_sequence')->nullable()->after('file_name')
                      ->comment('Original row order from Excel file for chronological settlement');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bkash_transactions', 'row_sequence')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                $table->dropColumn('row_sequence');
            });
        }
    }
};

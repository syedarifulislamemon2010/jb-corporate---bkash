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
                if (!Schema::hasColumn('bkash_transactions', 'created_by_id')) {
                    $table->unsignedBigInteger('created_by_id')->nullable()->after('created_by');
                }
                if (!Schema::hasColumn('bkash_transactions', 'checked_by_id')) {
                    $table->unsignedBigInteger('checked_by_id')->nullable()->after('checked_by');
                }
                if (!Schema::hasColumn('bkash_transactions', 'approved_by_1_id')) {
                    $table->unsignedBigInteger('approved_by_1_id')->nullable()->after('approved_by_1');
                }
                if (!Schema::hasColumn('bkash_transactions', 'approved_by_2_id')) {
                    $table->unsignedBigInteger('approved_by_2_id')->nullable()->after('approved_by_2');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkash_transactions')) {
            Schema::table('bkash_transactions', function (Blueprint $table) {
                $table->dropColumn([
                    'created_by_id',
                    'checked_by_id',
                    'approved_by_1_id',
                    'approved_by_2_id',
                ]);
            });
        }
    }
};

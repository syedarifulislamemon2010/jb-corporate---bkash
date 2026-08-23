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
                if (!Schema::hasColumn('bkash_transactions', 'bb_reference_number')) {
                    $table->string('bb_reference_number', 100)->nullable()->after('reference_id');
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
                if (Schema::hasColumn('bkash_transactions', 'bb_reference_number')) {
                    $table->dropColumn('bb_reference_number');
                }
            });
        }
    }
};

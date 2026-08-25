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
        Schema::table('bkash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('bkash_transactions', 'response_id')) {
                $table->string('response_id', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('bkash_transactions', 'confirmed_by')) {
                $table->string('confirmed_by', 50)->nullable();
            }
            if (!Schema::hasColumn('bkash_transactions', 'confirmed_at')) {
                $table->dateTime('confirmed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bkash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bkash_transactions', 'response_id')) {
                $table->dropColumn('response_id');
            }
            if (Schema::hasColumn('bkash_transactions', 'confirmed_by')) {
                $table->dropColumn('confirmed_by');
            }
            if (Schema::hasColumn('bkash_transactions', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }
        });
    }
};

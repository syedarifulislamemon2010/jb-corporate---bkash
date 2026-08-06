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
                if (!Schema::hasColumn('bkash_transactions', 'checked_by')) {
                    $table->string('checked_by', 255)->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'checked_at')) {
                    $table->timestamp('checked_at')->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'approved_by_1')) {
                    $table->string('approved_by_1', 255)->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'approved_at_1')) {
                    $table->timestamp('approved_at_1')->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'approved_by_2')) {
                    $table->string('approved_by_2', 255)->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'approved_at_2')) {
                    $table->timestamp('approved_at_2')->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'admin_approved_by')) {
                    $table->string('admin_approved_by', 255)->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'admin_approved_at')) {
                    $table->timestamp('admin_approved_at')->nullable();
                }
                if (!Schema::hasColumn('bkash_transactions', 'cbs_success_at')) {
                    $table->timestamp('cbs_success_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Keep columns intact
    }
};

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
            $table->string('response_id', 100)->nullable()->index();
            $table->string('confirmed_by', 50)->nullable();
            $table->dateTime('confirmed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bkash_transactions', function (Blueprint $table) {
            $table->dropIndex(['response_id']);
            $table->dropColumn(['response_id', 'confirmed_by', 'confirmed_at']);
        });
    }
};

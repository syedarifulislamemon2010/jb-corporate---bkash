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
        Schema::create('mt940_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->string('account_no', 50)->index();
            $table->date('statement_date')->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('status', 50)->default('Generated Locally');
            $table->boolean('is_ok')->default(true);
            $table->dateTime('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt940_delivery_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_attempts', function (Blueprint $table) {
            $table->string('txn_id', 50)->primary(); // Unique Key mechanism for double payment defense
            $table->uuid('instruction_id')->nullable();
            $table->string('channel', 20)->nullable(); // A2A, BEFTN, RTGS
            $table->string('outcome', 20)->default('PENDING'); // SUCCESS, FAILED
            $table->string('external_ref', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_attempts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eft_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('txn_id', 50)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->string('original_file_name', 255)->nullable();
            $table->string('beneficiary_account', 34)->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('return_code', 50)->nullable();
            $table->text('return_reason')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->timestamps();

            $table->index(['txn_id']);
            $table->index(['reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eft_returns');
    }
};

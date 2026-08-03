<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_type', 50); // STAGE_1_SFTP, STAGE_2_CHECKED, STAGE_3_AUTH1, STAGE_4_AUTH2
            $table->string('file_name', 255);
            $table->integer('total_trn');
            $table->decimal('total_amount', 18, 2);
            $table->string('actor_name', 100)->nullable();
            $table->string('recipient_group', 50)->default('ALL_CHECKERS'); // ALL_CHECKERS, ALL_CHECKERS_AND_AUTHORIZERS
            $table->string('status', 20)->default('PENDING'); // PENDING, SENT, FAILED
            $table->text('sms_payload')->nullable();
            $table->text('email_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_outbox');
    }
};

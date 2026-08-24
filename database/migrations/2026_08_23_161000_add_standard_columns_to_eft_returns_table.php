<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('eft_returns')) {
            Schema::table('eft_returns', function (Blueprint $table) {
                if (!Schema::hasColumn('eft_returns', 'execution_date')) {
                    $table->date('execution_date')->nullable();
                }
                if (!Schema::hasColumn('eft_returns', 'return_date')) {
                    $table->date('return_date')->nullable();
                }
                if (!Schema::hasColumn('eft_returns', 'service_type')) {
                    $table->string('service_type', 50)->nullable()->default('BEFTN');
                }
                if (!Schema::hasColumn('eft_returns', 'bene_bank_name')) {
                    $table->string('bene_bank_name', 150)->nullable();
                }
                if (!Schema::hasColumn('eft_returns', 'bene_branch_name')) {
                    $table->string('bene_branch_name', 150)->nullable();
                }
                if (!Schema::hasColumn('eft_returns', 'bene_routing_no')) {
                    $table->string('bene_routing_no', 20)->nullable();
                }
                if (!Schema::hasColumn('eft_returns', 'bene_name')) {
                    $table->string('bene_name', 150)->nullable();
                }
                if (!Schema::hasColumn('eft_returns', 'particular')) {
                    $table->string('particular', 255)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('eft_returns')) {
            Schema::table('eft_returns', function (Blueprint $table) {
                $table->dropColumn([
                    'execution_date',
                    'return_date',
                    'service_type',
                    'bene_bank_name',
                    'bene_branch_name',
                    'bene_routing_no',
                    'bene_name',
                    'particular',
                ]);
            });
        }
    }
};

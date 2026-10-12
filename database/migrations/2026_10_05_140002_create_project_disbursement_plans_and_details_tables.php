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
        Schema::create('project_disbursement_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->unique('pdp_project_id_unique')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->name('fk_pdp_project_id');
            $table->decimal('project_total_cost', 15, 2);
            $table->decimal('total_planned_amount', 15, 2);
            $table->decimal('total_disbursed_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('draft')->index('pdp_status_idx');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_pdp_approved_by');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_pdp_created_by');
            $table->timestamps();
        });

        Schema::create('project_disbursement_plan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_disbursement_plan_id')
                ->constrained('project_disbursement_plans')
                ->cascadeOnDelete()
                ->name('fk_pdpd_plan_id');
            $table->unsignedInteger('sequence_number');
            $table->decimal('planned_amount', 15, 2);
            $table->text('disbursement_clearance_mechanism');
            $table->string('status', 30)->default('planned')->index('pdpd_status_idx');
            $table->decimal('disbursed_amount', 15, 2)->default(0);
            $table->timestamp('disbursed_at')->nullable();
            $table->string('erpnext_payment_id')->nullable()->index('pdpd_erp_pay_id_idx');
            $table->string('erpnext_payment_reference')->nullable()->unique('pdpd_erp_ref_unique');
            $table->timestamp('cleared_at')->nullable();
            $table->foreignId('cleared_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_pdpd_cleared_by');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_pdpd_created_by');
            $table->timestamps();

            $table->unique(['project_disbursement_plan_id', 'sequence_number'], 'pdpd_plan_seq_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_disbursement_plan_details');
        Schema::dropIfExists('project_disbursement_plans');
    }
};

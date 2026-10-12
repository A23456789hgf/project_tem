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
        Schema::create('project_clearance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->name('fk_pcr_project_id');
            $table->foreignId('disbursement_plan_detail_id')
                ->constrained('project_disbursement_plan_details')
                ->cascadeOnDelete()
                ->name('fk_pcr_detail_id');
            $table->string('request_number')->unique('pcr_req_num_unique');
            $table->decimal('installment_disbursed_amount', 15, 2);
            $table->decimal('total_cleared_amount', 15, 2);
            $table->decimal('technical_progress_percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending')->index('pcr_status_idx'); // pending, approved, rejected, cancelled
            $table->string('attachment_path')->nullable();
            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_pcr_submitted_by');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_pcr_approved_by');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('project_clearance_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_request_id')
                ->constrained('project_clearance_requests')
                ->cascadeOnDelete()
                ->name('fk_pcri_req_id');
            $table->string('execution_type', 50); // project_execution, preliminary_execution
            $table->unsignedBigInteger('execution_id');
            $table->decimal('amount_spent', 15, 2);
            $table->decimal('completion_percentage', 5, 2);
            $table->timestamps();

            $table->index(['execution_type', 'execution_id'], 'pcri_exec_idx');
        });

        Schema::create('project_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->name('fk_ppr_project_id');
            $table->foreignId('disbursement_plan_detail_id')
                ->constrained('project_disbursement_plan_details')
                ->cascadeOnDelete()
                ->name('fk_ppr_detail_id');
            $table->string('request_number')->unique('ppr_req_num_unique');
            $table->decimal('requested_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending')->index('ppr_status_idx'); // pending, approved, rejected, cancelled
            $table->string('attachment_path')->nullable();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_ppr_requested_by');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_ppr_approved_by');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_payment_requests');
        Schema::dropIfExists('project_clearance_request_items');
        Schema::dropIfExists('project_clearance_requests');
    }
};

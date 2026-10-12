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
        Schema::create('project_unified_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 100)->unique();
            $table->string('request_type', 50)->index();
            $table->foreignId('project_id')->constrained('projects', indexName: 'fk_pur_project')->cascadeOnDelete();

            // Mandatory Submitting Organization and User
            $table->string('submitting_type', 20)->default('internal');
            $table->foreignId('submitting_entity_id')->nullable()->constrained('internal_entities', indexName: 'fk_pur_sub_entity')->nullOnDelete();
            $table->foreignId('submitting_authority_id')->nullable()->constrained('authorities', indexName: 'fk_pur_sub_auth')->nullOnDelete();
            $table->foreignId('submitting_user_id')->nullable()->constrained('users', indexName: 'fk_pur_sub_user')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users', indexName: 'fk_pur_creator')->cascadeOnDelete();

            // General metadata
            $table->date('request_date');
            $table->string('status', 50)->default('pending')->index();
            $table->text('description')->nullable();

            // Clearance Component
            $table->foreignId('clearance_disbursement_plan_detail_id')->nullable()
                ->constrained('project_disbursement_plan_details', indexName: 'fk_pur_clr_detail')->nullOnDelete();
            $table->string('advance_recipient')->nullable();
            $table->decimal('original_advance_amount', 15, 2)->default(0.00);
            $table->decimal('cleared_amount', 15, 2)->default(0.00);
            $table->decimal('remaining_advance_amount', 15, 2)->default(0.00);
            $table->date('advance_receipt_date')->nullable();
            $table->decimal('technical_progress_percentage', 5, 2)->default(0.00);
            $table->text('clearance_description')->nullable();
            $table->foreignId('clearance_request_id')->nullable()
                ->constrained('project_clearance_requests', indexName: 'fk_pur_clr_req')->nullOnDelete();

            // Payment Component
            $table->foreignId('payment_disbursement_plan_detail_id')->nullable()
                ->constrained('project_disbursement_plan_details', indexName: 'fk_pur_pay_detail')->nullOnDelete();
            $table->unsignedInteger('payment_number')->nullable();
            $table->decimal('requested_payment_amount', 15, 2)->default(0.00);
            $table->string('payment_recipient')->nullable();
            $table->date('expected_clearance_date')->nullable();
            $table->text('payment_justification')->nullable();
            $table->foreignId('payment_request_id')->nullable()
                ->constrained('project_payment_requests', indexName: 'fk_pur_pay_req')->nullOnDelete();

            // Closure Component
            $table->text('closure_justification')->nullable();
            $table->date('project_completion_date')->nullable();
            $table->text('final_settlement_notes')->nullable();

            // Documents & Approvals
            $table->string('attachment_path')->nullable();
            $table->json('additional_documents')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users', indexName: 'fk_pur_approver')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('workflow_request_id')->nullable()
                ->constrained('project_workflow_requests', indexName: 'fk_pur_wf_req')->nullOnDelete();

            $table->timestamps();

            // Compound indexes
            $table->index(['project_id', 'status'], 'idx_pur_proj_stat');
            $table->index(['request_type', 'status'], 'idx_pur_typ_stat');
            $table->index(['submitting_type', 'submitting_entity_id'], 'idx_pur_sub_ent');
            $table->index(['submitting_type', 'submitting_authority_id'], 'idx_pur_sub_aut');
            $table->index(['submitting_user_id', 'status'], 'idx_pur_usr_stat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_unified_requests');
    }
};

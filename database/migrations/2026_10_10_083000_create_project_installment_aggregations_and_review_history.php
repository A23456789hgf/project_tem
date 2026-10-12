<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_installment_aggregations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_disbursement_plan_detail_id')
                ->constrained('project_disbursement_plan_details', 'id', 'pia_detail_fk')
                ->cascadeOnDelete();
            $table->string('status', 40)->default('ready_for_clearance');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload');
            $table->foreignId('clearance_request_id')->nullable()->constrained('project_clearance_requests')->nullOnDelete();
            $table->timestamps();
            $table->unique('project_disbursement_plan_detail_id', 'pia_detail_unique');
        });

        Schema::create('project_execution_review_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('execution_type', 40);
            $table->unsignedBigInteger('execution_id');
            $table->foreignId('reviewed_by')->constrained('users')->cascadeOnDelete();
            $table->string('action', 40);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['execution_type', 'execution_id'], 'perh_execution_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_execution_review_histories');
        Schema::dropIfExists('project_installment_aggregations');
    }
};

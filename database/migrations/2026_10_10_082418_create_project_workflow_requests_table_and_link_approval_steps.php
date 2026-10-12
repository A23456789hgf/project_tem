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
        Schema::create('project_workflow_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('request_type', 60);
            $table->string('status', 40)->default('pending')->index();
            $table->string('request_number', 80)->unique();
            $table->json('payload');
            $table->string('requestable_type')->nullable();
            $table->unsignedBigInteger('requestable_id')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
            $table->index(['requestable_type', 'requestable_id'], 'pwr_requestable_idx');
            $table->index(['project_id', 'request_type', 'status'], 'pwr_project_type_status_idx');
        });

        Schema::table('project_approvals', function (Blueprint $table): void {
            $table->foreignId('workflow_request_id')->nullable()->after('project_id')
                ->constrained('project_workflow_requests')->cascadeOnDelete();
            $table->index(['workflow_request_id', 'is_active', 'status'], 'pa_workflow_request_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_approvals', function (Blueprint $table): void {
            $table->dropIndex('pa_workflow_request_active_idx');
            $table->dropConstrainedForeignId('workflow_request_id');
        });

        Schema::dropIfExists('project_workflow_requests');
    }
};

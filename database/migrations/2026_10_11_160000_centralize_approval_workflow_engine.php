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
        // 1. entity_approval_stages
        Schema::table('entity_approval_stages', function (Blueprint $table) {
            if (! Schema::hasColumn('entity_approval_stages', 'workflow_type')) {
                $table->string('workflow_type', 50)->default('project_approval')->after('entity_id')->index();
            }
            if (! Schema::hasColumn('entity_approval_stages', 'required_attachments')) {
                $table->json('required_attachments')->nullable()->after('available_actions');
            }
            if (! Schema::hasColumn('entity_approval_stages', 'action_rules')) {
                $table->json('action_rules')->nullable()->after('required_attachments');
            }
            if (! Schema::hasColumn('entity_approval_stages', 'completion_requirements')) {
                $table->json('completion_requirements')->nullable()->after('action_rules');
            }
        });

        // Safe index refactoring for entity_approval_stages
        try {
            Schema::table('entity_approval_stages', function (Blueprint $table) {
                // Drop legacy unique index if present
                $table->dropUnique('entity_stage_unique');
            });
        } catch (Throwable $e) {
            // Index might have different name or not exist
        }

        try {
            Schema::table('entity_approval_stages', function (Blueprint $table) {
                $table->unique(['entity_id', 'workflow_type', 'stage'], 'entity_wf_stage_unique');
            });
        } catch (Throwable $e) {
            // Already created
        }

        // 2. authority_approval_stages
        Schema::table('authority_approval_stages', function (Blueprint $table) {
            if (! Schema::hasColumn('authority_approval_stages', 'workflow_type')) {
                $table->string('workflow_type', 50)->default('project_approval')->after('authority_id')->index();
            }
            if (! Schema::hasColumn('authority_approval_stages', 'stage_key')) {
                $table->string('stage_key', 60)->nullable()->after('stage');
            }
            if (! Schema::hasColumn('authority_approval_stages', 'required_attachments')) {
                $table->json('required_attachments')->nullable()->after('available_actions');
            }
            if (! Schema::hasColumn('authority_approval_stages', 'action_rules')) {
                $table->json('action_rules')->nullable()->after('required_attachments');
            }
            if (! Schema::hasColumn('authority_approval_stages', 'completion_requirements')) {
                $table->json('completion_requirements')->nullable()->after('action_rules');
            }
        });

        // 3. project_approvals (Workflow Execution Snapshot)
        Schema::table('project_approvals', function (Blueprint $table) {
            if (! Schema::hasColumn('project_approvals', 'unified_request_id')) {
                $table->foreignId('unified_request_id')->nullable()->after('workflow_request_id')
                    ->constrained('project_unified_requests')->nullOnDelete();
            }
            if (! Schema::hasColumn('project_approvals', 'required_attachments')) {
                $table->json('required_attachments')->nullable()->after('attachments');
            }
            if (! Schema::hasColumn('project_approvals', 'uploaded_attachments')) {
                $table->json('uploaded_attachments')->nullable()->after('required_attachments');
            }
            if (! Schema::hasColumn('project_approvals', 'action_rules')) {
                $table->json('action_rules')->nullable()->after('available_actions');
            }
            if (! Schema::hasColumn('project_approvals', 'workflow_version')) {
                $table->unsignedTinyInteger('workflow_version')->default(1)->after('is_active');
            }
            if (! Schema::hasColumn('project_approvals', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('workflow_version');
            }
            if (! Schema::hasColumn('project_approvals', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('submitted_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_approvals', function (Blueprint $table) {
            if (Schema::hasColumn('project_approvals', 'submitted_by')) {
                $table->dropConstrainedForeignId('submitted_by');
            }
            if (Schema::hasColumn('project_approvals', 'unified_request_id')) {
                $table->dropConstrainedForeignId('unified_request_id');
            }
            $table->dropColumn(array_intersect(
                ['required_attachments', 'uploaded_attachments', 'action_rules', 'workflow_version', 'submitted_at'],
                Schema::getColumnListing('project_approvals')
            ));
        });

        Schema::table('authority_approval_stages', function (Blueprint $table) {
            $table->dropColumn(array_intersect(
                ['workflow_type', 'stage_key', 'required_attachments', 'action_rules', 'completion_requirements'],
                Schema::getColumnListing('authority_approval_stages')
            ));
        });

        Schema::table('entity_approval_stages', function (Blueprint $table) {
            try {
                $table->dropUnique('entity_wf_stage_unique');
                $table->unique(['entity_id', 'stage'], 'entity_stage_unique');
            } catch (Throwable $e) {
            }

            $table->dropColumn(array_intersect(
                ['workflow_type', 'required_attachments', 'action_rules', 'completion_requirements'],
                Schema::getColumnListing('entity_approval_stages')
            ));
        });
    }
};

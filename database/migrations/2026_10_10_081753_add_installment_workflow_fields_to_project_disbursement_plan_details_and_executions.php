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
        Schema::table('project_disbursement_plan_details', function (Blueprint $table) {
            $table->foreignId('funding_source_id')
                ->nullable()
                ->after('sequence_number')
                ->constrained('funding_sources')
                ->nullOnDelete();
        });

        Schema::table('project_executions', function (Blueprint $table) {
            $table->string('workflow_review_status', 40)->nullable()->index();
            $table->text('workflow_return_note')->nullable();
        });

        Schema::table('preliminary_procedure_executions', function (Blueprint $table) {
            $table->string('workflow_review_status', 40)->nullable()->index();
            $table->text('workflow_return_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preliminary_procedure_executions', function (Blueprint $table) {
            $table->dropColumn(['workflow_review_status', 'workflow_return_note']);
        });

        Schema::table('project_executions', function (Blueprint $table) {
            $table->dropColumn(['workflow_review_status', 'workflow_return_note']);
        });

        Schema::table('project_disbursement_plan_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('funding_source_id');
        });
    }
};

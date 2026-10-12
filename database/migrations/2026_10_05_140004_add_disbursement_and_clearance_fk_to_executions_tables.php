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
        Schema::table('project_executions', function (Blueprint $table) {
            $table->foreignId('disbursement_plan_detail_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_disbursement_plan_details', 'id', 'fk_pe_pdp_detail_id')
                ->nullOnDelete();

            $table->foreignId('clearance_request_id')
                ->nullable()
                ->after('disbursement_plan_detail_id')
                ->constrained('project_clearance_requests', 'id', 'fk_pe_pcr_request_id')
                ->nullOnDelete();
        });

        Schema::table('preliminary_procedure_executions', function (Blueprint $table) {
            $table->foreignId('disbursement_plan_detail_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_disbursement_plan_details', 'id', 'fk_ppe_pdp_detail_id')
                ->nullOnDelete();

            $table->foreignId('clearance_request_id')
                ->nullable()
                ->after('disbursement_plan_detail_id')
                ->constrained('project_clearance_requests', 'id', 'fk_ppe_pcr_request_id')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preliminary_procedure_executions', function (Blueprint $table) {
            $table->dropForeign('fk_ppe_pcr_request_id');
            $table->dropForeign('fk_ppe_pdp_detail_id');
            $table->dropColumn(['clearance_request_id', 'disbursement_plan_detail_id']);
        });

        Schema::table('project_executions', function (Blueprint $table) {
            $table->dropForeign('fk_pe_pcr_request_id');
            $table->dropForeign('fk_pe_pdp_detail_id');
            $table->dropColumn(['clearance_request_id', 'disbursement_plan_detail_id']);
        });
    }
};

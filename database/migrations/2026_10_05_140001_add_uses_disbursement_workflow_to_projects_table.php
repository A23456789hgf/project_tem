<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('uses_disbursement_workflow')->default(true)->after('status')->index();
        });

        // Set false for legacy projects that already entered execution or have execution records
        DB::table('projects')
            ->whereIn('status', ['in_execution', 'completed'])
            ->orWhereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('project_executions')
                    ->whereColumn('project_executions.project_id', 'projects.id');
            })
            ->orWhereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('preliminary_procedure_executions')
                    ->whereColumn('preliminary_procedure_executions.project_id', 'projects.id');
            })
            ->update(['uses_disbursement_workflow' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('uses_disbursement_workflow');
        });
    }
};

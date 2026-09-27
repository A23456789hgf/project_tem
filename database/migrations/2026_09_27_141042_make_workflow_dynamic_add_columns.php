<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entity_approval_stages', function (Blueprint $table) {
            $table->string('stage_name_ar')->nullable()->after('stage');
            $table->string('stage_name_en')->nullable()->after('stage_name_ar');
            $table->json('available_actions')->nullable()->after('responsible_user_id');
        });

        Schema::table('authority_approval_stages', function (Blueprint $table) {
            $table->string('stage_name_ar')->nullable()->after('stage');
            $table->string('stage_name_en')->nullable()->after('stage_name_ar');
            $table->json('available_actions')->nullable()->after('responsible_user_id');
        });

        Schema::table('project_approvals', function (Blueprint $table) {
            // Because assigned_user_id already exists in some projects?
            // Actually, wait, let me check if assigned_user_id already exists.
            // My previous output for project_approvals: 
            // "assigned_user_id" is already there. So I don't need to add it!
            $table->string('stage_key')->nullable()->after('assigned_user_id');
            $table->string('stage_name')->nullable()->after('stage_key');
            $table->json('available_actions')->nullable()->after('stage_name');
        });

        // Data Migration: map existing 'stage' enum values to stage_name_ar and default actions
        $this->migrateExistingStagesData('entity_approval_stages');
        $this->migrateExistingStagesData('authority_approval_stages');
    }

    private function migrateExistingStagesData(string $tableName): void
    {
        DB::table($tableName)->get()->each(function ($row) use ($tableName) {
            $nameAr = '';
            $nameEn = '';
            $actions = ['approve', 'return']; // Default
            
            if (in_array($row->stage, ['TECHNICAL_REVIEW', 'technical_review'])) {
                $nameAr = 'مراجعة فنية';
                $nameEn = 'Technical Review';
                $actions = ['technical_review', 'return']; // based on what RequestDescendController has
            } elseif (in_array($row->stage, ['FINANCIAL_REVIEW', 'financial_review'])) {
                $nameAr = 'مراجعة مالية';
                $nameEn = 'Financial Review';
                $actions = ['financial_review', 'return'];
            } elseif (in_array($row->stage, ['APPROVAL', 'stage_approval', 'approval'])) {
                $nameAr = 'اعتماد';
                $nameEn = 'Approval';
                $actions = ['approve', 'return', 'reject'];
            } else {
                $nameAr = $row->stage;
                $nameEn = $row->stage;
            }

            DB::table($tableName)
                ->where('id', $row->id)
                ->update([
                    'stage_name_ar' => $nameAr,
                    'stage_name_en' => $nameEn,
                    'available_actions' => json_encode($actions),
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entity_approval_stages', function (Blueprint $table) {
            $table->dropColumn(['stage_name_ar', 'stage_name_en', 'available_actions']);
        });

        Schema::table('authority_approval_stages', function (Blueprint $table) {
            $table->dropColumn(['stage_name_ar', 'stage_name_en', 'available_actions']);
        });

        Schema::table('project_approvals', function (Blueprint $table) {
            $table->dropColumn(['stage_key', 'stage_name', 'available_actions']);
        });
    }
};

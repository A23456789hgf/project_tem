<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ إضافة الأعمدة فقط إذا لم تكن موجودة
        Schema::table('project_approvals', function (Blueprint $table) {
            if (! Schema::hasColumn('project_approvals', 'is_final_stage')) {
                $table->boolean('is_final_stage')->default(false)->after('is_completed');
            }
        });

        Schema::table('project_approvals', function (Blueprint $table) {
            if (! Schema::hasColumn('project_approvals', 'triggers_execution')) {
                $table->boolean('triggers_execution')->default(false)->after('is_final_stage');
            }
        });

        // ✅ لا نضيف أي فهارس هنا — لأنها موجودة مسبقاً في جدولك
    }

    public function down(): void
    {
        Schema::table('project_approvals', function (Blueprint $table) {
            if (Schema::hasColumn('project_approvals', 'triggers_execution')) {
                $table->dropColumn('triggers_execution');
            }
        });

        Schema::table('project_approvals', function (Blueprint $table) {
            if (Schema::hasColumn('project_approvals', 'is_final_stage')) {
                $table->dropColumn('is_final_stage');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['Task Reports View', 'task_reports.view', 'page'],
            ['Task Reports Create', 'task_reports.create', 'action'],
            ['Task Reports Edit', 'task_reports.edit', 'action'],
            ['Task Reports Submit', 'task_reports.submit', 'action'],
            ['Task Reports Review', 'task_reports.review', 'action'],
            ['Task Reports View All', 'task_reports.view_all', 'action'],
            ['Task Reports Export', 'task_reports.export', 'action'],
            ['Task Reports Delete', 'task_reports.delete', 'action'],
        ];
        foreach ($permissions as [$name, $slug, $type]) {
            DB::table('permissions')->updateOrInsert(['slug' => $slug], [
                'name' => $name,
                'module' => 'task',
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::create('task_achievement_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->decimal('overall_completion_percentage', 5, 2)->nullable();
            $table->longText('key_results')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('task_title_snapshot');
            $table->longText('task_description_snapshot')->nullable();
            $table->date('task_start_date_snapshot')->nullable();
            $table->date('task_due_date_snapshot')->nullable();
            $table->string('task_status_snapshot');
            $table->unsignedInteger('planned_duration_days_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('task_achievement_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('task_achievement_reports')->cascadeOnDelete();
            $table->text('achievement_description');
            $table->decimal('completion_percentage', 5, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['report_id', 'sort_order']);
        });

        Schema::create('task_achievement_report_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('task_achievement_reports')->cascadeOnDelete();
            $table->foreignId('worker_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('worker_role')->nullable();
            $table->text('achievement_description')->nullable();
            $table->decimal('completion_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['report_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_achievement_report_workers');
        Schema::dropIfExists('task_achievement_report_items');
        Schema::dropIfExists('task_achievement_reports');
        DB::table('permissions')->whereIn('slug', [
            'task_reports.view', 'task_reports.create', 'task_reports.edit', 'task_reports.submit',
            'task_reports.review', 'task_reports.view_all', 'task_reports.export', 'task_reports.delete',
        ])->delete();
        Cache::flush();
    }
};

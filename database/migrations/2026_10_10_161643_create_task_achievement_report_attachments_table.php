<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_achievement_report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('task_achievement_reports')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 150);
            $table->unsignedInteger('file_size');
            $table->timestamps();
            $table->index(['report_id', 'uploaded_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_achievement_report_attachments');
    }
};

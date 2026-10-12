<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskAchievementReport extends Model
{
    protected $fillable = [
        'task_id', 'user_id', 'status', 'overall_completion_percentage', 'key_results', 'submitted_at',
        'task_title_snapshot', 'task_description_snapshot', 'task_start_date_snapshot', 'task_due_date_snapshot',
        'task_status_snapshot', 'planned_duration_days_snapshot',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'overall_completion_percentage' => 'decimal:2', 'task_start_date_snapshot' => 'date', 'task_due_date_snapshot' => 'date'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TaskAchievementReportItem::class, 'report_id')->orderBy('sort_order');
    }

    public function workers(): HasMany
    {
        return $this->hasMany(TaskAchievementReportWorker::class, 'report_id')->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAchievementReportAttachment::class, 'report_id');
    }
}

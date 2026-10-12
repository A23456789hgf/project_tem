<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAchievementReportWorker extends Model
{
    protected $fillable = ['worker_user_id', 'worker_role', 'achievement_description', 'completion_percentage', 'notes', 'sort_order'];

    protected function casts(): array
    {
        return ['completion_percentage' => 'decimal:2'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(TaskAchievementReport::class, 'report_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_user_id');
    }
}

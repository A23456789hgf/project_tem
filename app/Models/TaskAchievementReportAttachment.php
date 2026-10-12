<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAchievementReportAttachment extends Model
{
    protected $fillable = ['report_id', 'uploaded_by', 'file_name', 'file_path', 'mime_type', 'file_size'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(TaskAchievementReport::class, 'report_id');
    }
}

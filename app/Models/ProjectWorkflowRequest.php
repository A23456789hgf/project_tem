<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectWorkflowRequest extends Model
{
    protected $fillable = [
        'project_id', 'request_type', 'status', 'request_number', 'payload',
        'requestable_type', 'requestable_id', 'submitted_by', 'decided_by',
        'decided_at', 'decision_notes',
    ];

    protected $casts = ['payload' => 'array', 'decided_at' => 'datetime'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ProjectApproval::class, 'workflow_request_id')->orderBy('step_order');
    }
}

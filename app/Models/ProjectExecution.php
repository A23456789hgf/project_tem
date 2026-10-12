<?php

namespace App\Models;

use App\Traits\HasDomainScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExecution extends Model
{
    use HasDomainScope, HasFactory;

    protected $fillable = [
        'project_id',
        'disbursement_plan_detail_id',
        'clearance_request_id',
        'executive_activity_action_id',
        'created_by_user_id',
        'entity',
        'sequence',
        'actual_start_date_gregorian',
        'actual_start_date_hijri',
        'actual_finish_date_gregorian',
        'actual_finish_date_hijri',
        'actual_amount',
        'amount_spent',
        'remaining_amount',
        'status',
        'completion_percentage',
        'technical_documents',
        'financial_documents',
        'notes',
        'approval_status',
        'workflow_review_status',
        'workflow_return_note',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'actual_start_date_gregorian' => 'date',
        'actual_finish_date_gregorian' => 'date',
        'actual_amount' => 'decimal:2',
        'amount_spent' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'technical_documents' => 'array',
        'financial_documents' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the project this execution belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function disbursementPlanDetail(): BelongsTo
    {
        return $this->belongsTo(ProjectDisbursementPlanDetail::class, 'disbursement_plan_detail_id');
    }

    public function clearanceRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectClearanceRequest::class, 'clearance_request_id');
    }

    /**
     * Get the action this execution is for
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(ExecutiveActivityAction::class, 'executive_activity_action_id');
    }

    public function scopeForAction($query, $actionId)
    {
        return $query->where('executive_activity_action_id', $actionId)->orderBy('sequence');
    }

    public function delayExplanation()
    {
        return $this->hasOne(ExecutionDelayExplanation::class, 'execution_type_id')
            ->where('execution_type', 'executive');
    }

    public function budgetJustification()
    {
        return $this->morphOne(ExecutionBudgetJustification::class, 'execution', 'execution_type', 'execution_type_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    public static function getTotalCompletionPercentage($projectId, $actionId)
    {
        return self::where('project_id', $projectId)
            ->where('executive_activity_action_id', $actionId)
            ->sum('completion_percentage');
    }
}

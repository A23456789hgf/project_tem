<?php

namespace App\Models;

use App\Enums\ApprovalPhase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'approver_scope',
        'entity_id',
        'authority_id',
        'assigned_user_id',
        'approval_flow_id',
        'stage_id',
        'stage_status_id',
        'step_order',
        'drop',
        'status',
        'notes',
        'attachment',
        'required_action',
        'rejection_reason',
        'financial_review_notes',
        'technical_review_notes',
        'financial_review_completed',
        'technical_review_completed',
        'financial_review_completed_at',
        'technical_review_completed_at',
        'financial_review_user_id',
        'technical_review_user_id',
        'reviewer_type',
        'attachments',
        'created_by',
        'reviewed_at',
        'reviewed_by',
        'resubmitted_at',
        'resubmitted_by',
        // New fields for entity-based workflow
        'financial_review_status',
        'technical_review_status',
        'financial_reviewer_id',
        'technical_reviewer_id',
        'financial_reviewed_at',
        'technical_reviewed_at',
        'financial_notes',
        'technical_notes',
        'financial_attachment',
        'technical_attachment',
        'returned_from_stage',
        'returned_at',
        'is_completed',
        // Dynamic workflow fields
        'phase',
        'is_active',
        'return_target',
        'returned_to_step_order',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'financial_review_completed' => 'boolean',
        'technical_review_completed' => 'boolean',
        'financial_review_completed_at' => 'datetime',
        'technical_review_completed_at' => 'datetime',
        'financial_reviewed_at' => 'datetime',
        'technical_reviewed_at' => 'datetime',
        'returned_at' => 'datetime',
        'is_completed' => 'boolean',
        'is_active' => 'boolean',
        'returned_to_step_order' => 'integer',
        'attachments' => 'array',
        'notes' => 'string',
        'financial_review_notes' => 'string',
        'technical_review_notes' => 'string',
        'required_action' => 'string',
        'rejection_reason' => 'string',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'entity_id');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'authority_id');
    }

    public function financialReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'financial_reviewer_id');
    }

    public function technicalReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technical_reviewer_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvalFlow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function stageStatus(): BelongsTo
    {
        return $this->belongsTo(StageStatus::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOrderedByStep($query)
    {
        return $query->orderBy('step_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isNeedAction(): bool
    {
        return in_array($this->status, ['need_action', 'requires_action'], true);
    }

    public function getPhaseEnum(): ?ApprovalPhase
    {
        if ($this->phase) {
            return ApprovalPhase::tryFrom($this->phase);
        }

        if ($this->drop && str_starts_with($this->drop, 'entity_')) {
            if (str_ends_with($this->drop, '_technical_review')) {
                return ApprovalPhase::TechnicalReview;
            }
            if (str_ends_with($this->drop, '_financial_review')) {
                return ApprovalPhase::FinancialReview;
            }
            if (str_ends_with($this->drop, '_stage_approval')) {
                return ApprovalPhase::StageApproval;
            }
        }

        return null;
    }

    public function getPhaseLabel(): string
    {
        if (!empty($this->stage_name)) {
            return $this->stage_name;
        }
        return $this->getPhaseEnum()?->label() ?? 'اعتماد';
    }

    public function getPhaseArabicName(): string
    {
        return $this->getPhaseLabel();
    }

    public function getResolvedStageName(): string
    {
        $entityName = $this->entity?->name ?? $this->authority?->agency_name ?? 'الجهة';
        $phaseLabel = $this->getPhaseLabel();

        return "{$entityName} - {$phaseLabel}";
    }

    public function isFinancialReview(): bool
    {
        return $this->status === 'financial_review' || $this->phase === 'financial_review';
    }

    public function isFinancialReviewer(): bool
    {
        return $this->reviewer_type === 'financial' || $this->phase === 'financial_review';
    }

    public function isTechnicalReviewer(): bool
    {
        return $this->reviewer_type === 'technical' || $this->phase === 'technical_review';
    }

    public function isGeneralReviewer(): bool
    {
        return $this->reviewer_type === 'general' || $this->reviewer_type === null;
    }

    public function scopeFinancialReview($query)
    {
        return $query->where('status', 'financial_review');
    }

    public function scopeByReviewerType($query, $type)
    {
        return $query->where('reviewer_type', $type);
    }

    protected function setNotesAttribute($value)
    {
        $this->attributes['notes'] = is_array($value) ? implode(', ', $value) : (string) ($value ?? '');
    }

    protected function setFinancialReviewNotesAttribute($value)
    {
        $this->attributes['financial_review_notes'] = is_array($value) ? implode(', ', $value) : (string) ($value ?? '');
    }

    protected function setTechnicalReviewNotesAttribute($value)
    {
        $this->attributes['technical_review_notes'] = is_array($value) ? implode(', ', $value) : (string) ($value ?? '');
    }

    protected function setRequiredActionAttribute($value)
    {
        $this->attributes['required_action'] = is_array($value) ? implode(', ', $value) : (string) ($value ?? '');
    }

    protected function setRejectionReasonAttribute($value)
    {
        $this->attributes['rejection_reason'] = is_array($value) ? implode(', ', $value) : (string) ($value ?? '');
    }
}

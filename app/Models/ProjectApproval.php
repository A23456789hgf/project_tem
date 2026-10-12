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

    /**
     * حالات المرحلة الممكنة.
     */
    public const STATUS_WAITING = 'waiting';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'project_id',
        'workflow_request_id',
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
        'stage_key',
        'stage_name',
        'available_actions',
        'unified_request_id',
        'required_attachments',
        'uploaded_attachments',
        'action_rules',
        'workflow_version',
        'submitted_at',
        'submitted_by',
        // ✅ Final stage flags
        'is_final_stage',
        'triggers_execution',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectApproval $approval) {
            if ($approval->step_order === null) {
                $approval->step_order = 1;
            }
        });
    }

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'financial_review_completed' => 'boolean',
        'technical_review_completed' => 'boolean',
        'financial_review_completed_at' => 'datetime',
        'technical_review_completed_at' => 'datetime',
        'financial_reviewed_at' => 'datetime',
        'technical_reviewed_at' => 'datetime',
        'returned_at' => 'datetime',
        'resubmitted_at' => 'datetime',
        'is_completed' => 'boolean',
        'is_active' => 'boolean',
        // ✅ Flags جديدة
        'is_final_stage' => 'boolean',
        'triggers_execution' => 'boolean',
        'workflow_version' => 'integer',
        'returned_to_step_order' => 'integer',
        'step_order' => 'integer',
        'attachments' => 'array',
        'available_actions' => 'array',
        'required_attachments' => 'array',
        'uploaded_attachments' => 'array',
        'action_rules' => 'array',
        'notes' => 'string',
        'financial_review_notes' => 'string',
        'technical_review_notes' => 'string',
        'required_action' => 'string',
        'rejection_reason' => 'string',
    ];

    // ============================================================
    // Relationships
    // ============================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workflowRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectWorkflowRequest::class, 'workflow_request_id');
    }

    public function unifiedRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectUnifiedRequest::class, 'unified_request_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
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

    // ============================================================
    // Scopes
    // ============================================================

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
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    public function scopeForEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    /**
     * ✅ المراحل النهائية فقط.
     */
    public function scopeFinalStage($query)
    {
        return $query->where('is_final_stage', true);
    }

    /**
     * ✅ المراحل التي تُفعِّل التنفيذ.
     */
    public function scopeTriggeringExecution($query)
    {
        return $query->where('triggers_execution', true);
    }

    /**
     * ✅ المراحل المكلّف بها مستخدم معيّن.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    // ============================================================
    // Helpers — Status
    // ============================================================

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
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return (bool) $this->is_completed;
    }

    public function isNeedAction(): bool
    {
        return in_array($this->status, ['need_action', 'requires_action'], true);
    }

    // ============================================================
    // Helpers — Final Stage & Execution
    // ============================================================

    /**
     * ✅ هل هذه المرحلة نهائية في السلسلة؟
     */
    public function isFinalStage(): bool
    {
        return (bool) $this->is_final_stage;
    }

    /**
     * ✅ هل اعتماد هذه المرحلة يُفعِّل التنفيذ؟
     */
    public function triggersExecution(): bool
    {
        return (bool) $this->triggers_execution;
    }

    // ============================================================
    // Helpers — Phase
    // ============================================================

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
        if (! empty($this->stage_name)) {
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

    // ============================================================
    // Helpers — Actions & Permissions
    // ============================================================

    public function isActionAllowed(string $action): bool
    {
        $actions = $this->available_actions;
        if (is_string($actions)) {
            $actions = json_decode($actions, true);
        }

        if (empty($actions) || ! is_array($actions)) {
            // Legacy fallback
            $legacyActions = ['submit', 'approve', 'return', 'reject'];
            if ($this->phase === 'financial_review' || $this->phase === 'technical_review') {
                $legacyActions[] = $this->phase;
            }

            return in_array($action, $legacyActions, true);
        }

        return in_array($action, $actions, true);
    }

    public function hasAction(string $action): bool
    {
        return $this->isActionAllowed($action);
    }

    public function isAssignedTo(int $userId): bool
    {
        if ($this->assigned_user_id !== null) {
            return (int) $this->assigned_user_id === $userId;
        }

        // Legacy fallback
        return $this->financial_reviewer_id === $userId || $this->technical_reviewer_id === $userId;
    }

    /**
     * ✅ هل يمكن للمستخدم تنفيذ إجراء على هذه المرحلة؟
     */
    public function canBeActedBy(?User $user, string $action = 'approve'): bool
    {
        if (! $user) {
            return false;
        }

        // المرحلة يجب أن تكون pending
        if (! $this->isPending()) {
            return false;
        }

        // الإجراء يجب أن يكون مسموحاً
        if (! $this->isActionAllowed($action)) {
            return false;
        }

        // المستخدم هو المسؤول أو مدير
        return $this->isAssignedTo($user->id) || ($user->isAdmin() ?? false);
    }

    public function scopeFinancialReview($query)
    {
        return $query->where('status', 'financial_review');
    }

    public function scopeByReviewerType($query, $type)
    {
        return $query->where('reviewer_type', $type);
    }

    // ============================================================
    // Mutators
    // ============================================================

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

    // ============================================================
    // Helpers — Attachments
    // ============================================================

    public function getMissingRequiredAttachments(): array
    {
        $requirements = $this->required_attachments;
        if (empty($requirements) || ! is_array($requirements)) {
            return [];
        }

        $uploaded = $this->uploaded_attachments ?? [];
        $missing = [];

        foreach ($requirements as $req) {
            $isMandatory = ! empty($req['required']);
            $key = $req['key'] ?? null;
            if ($isMandatory && $key && empty($uploaded[$key])) {
                $missing[] = [
                    'key' => $key,
                    'label' => $req['label'] ?? $key,
                ];
            }
        }

        return $missing;
    }

    public function hasAllRequiredAttachments(): bool
    {
        return empty($this->getMissingRequiredAttachments());
    }

    public function getActionRule(string $action): array
    {
        $rules = $this->action_rules;
        if (is_string($rules)) {
            $rules = json_decode($rules, true);
        }

        return $rules[$action] ?? [
            'notes_required' => false,
            'attachment_required' => false,
            'min_notes_length' => 0,
        ];
    }
}

<?php

namespace App\Models;

use App\Services\ApprovalService;
use App\Services\OrganizationResolver;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ProjectUnifiedRequest extends Model
{
    use Auditable, HasFactory;

    protected $table = 'project_unified_requests';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_CLEARANCE = 'clearance';

    public const TYPE_CLEARANCE_PAYMENT = 'clearance_payment';

    public const TYPE_CLEARANCE_CLOSURE = 'clearance_closure';

    public const TYPE_PROJECT_CLOSURE = 'project_closure';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'request_number',
        'request_type',
        'project_id',
        'submitting_type',
        'submitting_entity_id',
        'submitting_authority_id',
        'submitting_user_id',
        'created_by',
        'request_date',
        'status',
        'description',

        // Clearance fields
        'clearance_disbursement_plan_detail_id',
        'advance_recipient',
        'original_advance_amount',
        'cleared_amount',
        'remaining_advance_amount',
        'advance_receipt_date',
        'technical_progress_percentage',
        'clearance_description',
        'clearance_request_id',

        // Payment fields
        'payment_disbursement_plan_detail_id',
        'payment_number',
        'requested_payment_amount',
        'payment_recipient',
        'expected_clearance_date',
        'payment_justification',
        'payment_request_id',

        // Closure fields
        'closure_justification',
        'project_completion_date',
        'final_settlement_notes',

        // Documents & Approvals
        'attachment_path',
        'additional_documents',
        'confirmed_by',
        'confirmed_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'workflow_request_id',

        // ERPNext Integration
        'erpnext_payment_reference',
        'erpnext_payment_id',
        'erpnext_sync_status',
        'erpnext_sync_error',
        'erpnext_synced_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'advance_receipt_date' => 'date',
        'expected_clearance_date' => 'date',
        'project_completion_date' => 'date',
        'confirmed_at' => 'datetime',
        'approved_at' => 'datetime',
        'erpnext_synced_at' => 'datetime',
        'original_advance_amount' => 'decimal:2',
        'cleared_amount' => 'decimal:2',
        'remaining_advance_amount' => 'decimal:2',
        'requested_payment_amount' => 'decimal:2',
        'technical_progress_percentage' => 'decimal:2',
        'additional_documents' => 'array',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submittingEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'submitting_entity_id');
    }

    public function submittingAuthority(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'submitting_authority_id');
    }

    public function submittingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitting_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function clearanceInstallment(): BelongsTo
    {
        return $this->belongsTo(ProjectDisbursementPlanDetail::class, 'clearance_disbursement_plan_detail_id');
    }

    public function paymentInstallment(): BelongsTo
    {
        return $this->belongsTo(ProjectDisbursementPlanDetail::class, 'payment_disbursement_plan_detail_id');
    }

    public function clearanceRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectClearanceRequest::class, 'clearance_request_id');
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectPaymentRequest::class, 'payment_request_id');
    }

    public function workflowRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectWorkflowRequest::class, 'workflow_request_id');
    }

    public function paymentObligation(): MorphOne
    {
        return $this->morphOne(ProjectInstallmentPaymentObligation::class, 'requestable');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ProjectApproval::class, 'workflow_request_id', 'workflow_request_id')->orderBy('step_order');
    }

    public function activeApprovalStep(): ?ProjectApproval
    {
        if (! $this->workflow_request_id) {
            return null;
        }

        if ($this->relationLoaded('approvalSteps')) {
            return $this->approvalSteps->first(fn ($s) => (bool) $s->is_active && $s->status === 'pending');
        }

        return $this->approvalSteps()->where('is_active', true)->where('status', 'pending')->first();
    }

    public function currentStageName(): string
    {
        if ($this->isApproved()) {
            return 'معتمد نهائياً';
        }
        if ($this->isRejected()) {
            return 'مرفوض';
        }
        if ($this->isDraft()) {
            return 'مسودة';
        }
        if ($this->isConfirmed()) {
            return 'مسودة مؤكدة (جاهزة للإرسال)';
        }

        $activeStep = $this->activeApprovalStep();
        if ($activeStep) {
            $total = $this->relationLoaded('approvalSteps')
                ? $this->approvalSteps->count()
                : $this->approvalSteps()->count();
            $order = $activeStep->step_order;
            $stageLabel = $activeStep->stage_name ?: ($activeStep->drop ?: 'مرحلة الاعتماد');

            return "{$stageLabel} (الخطوة {$order} من {$total})";
        }

        return 'قيد المراجعة والاعتماد';
    }

    public function canUserActOnCurrentStep(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $activeStep = $this->activeApprovalStep();
        if (! $activeStep) {
            return false;
        }

        $approvalService = app(ApprovalService::class);

        return $approvalService->canUserActOnStep($user, $activeStep, 'approve');
    }

    // =========================================================================
    // DYNAMIC ACCESSORS
    // =========================================================================

    /**
     * Resolves the linked submitting organization model dynamically.
     */
    public function getSubmittingOrganizationAttribute(): InternalEntity|Authority|null
    {
        if ($this->submitting_type === OrganizationResolver::EXTERNAL) {
            return $this->relationLoaded('submittingAuthority')
                ? $this->getRelation('submittingAuthority')
                : ($this->submitting_authority_id ? Authority::withoutGlobalScopes()->find($this->submitting_authority_id) : null);
        }

        return $this->relationLoaded('submittingEntity')
            ? $this->getRelation('submittingEntity')
            : ($this->submitting_entity_id ? InternalEntity::withoutGlobalScopes()->find($this->submitting_entity_id) : null);
    }

    public function getSubmittingOrganizationNameAttribute(): string
    {
        $org = $this->submitting_organization;
        if (! $org) {
            return '—';
        }

        if ($org instanceof Authority) {
            return $org->agency_name ?: 'جهة خارجية';
        }

        return $org->name ?: 'جهة داخلية';
    }

    public function getSubmittingUserNameAttribute(): string
    {
        if ($this->relationLoaded('submittingUser') && $this->submittingUser) {
            return $this->submittingUser->name;
        }

        if ($this->submitting_user_id) {
            $user = User::withoutGlobalScopes()->find($this->submitting_user_id);
            if ($user) {
                return $user->name;
            }
        }

        // Compatible fallback for legacy records
        if ($this->relationLoaded('creator') && $this->creator) {
            return $this->creator->name.' (المنشئ)';
        }

        return '—';
    }

    // =========================================================================
    // TYPE & STATUS HELPERS
    // =========================================================================

    public function hasPaymentComponent(): bool
    {
        return in_array($this->request_type, [self::TYPE_PAYMENT, self::TYPE_CLEARANCE_PAYMENT], true);
    }

    public function hasClearanceComponent(): bool
    {
        return in_array($this->request_type, [self::TYPE_CLEARANCE, self::TYPE_CLEARANCE_PAYMENT, self::TYPE_CLEARANCE_CLOSURE], true);
    }

    public function hasClosureComponent(): bool
    {
        return in_array($this->request_type, [self::TYPE_CLEARANCE_CLOSURE, self::TYPE_PROJECT_CLOSURE], true);
    }

    public function getClosureReasonAttribute(): ?string
    {
        return $this->closure_justification;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canBeConfirmed(): bool
    {
        return $this->isDraft();
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_DRAFT, 'requires_action'], true);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function typeLabel(): string
    {
        return match ($this->request_type) {
            self::TYPE_CLEARANCE_PAYMENT => 'إخلاء وطلب دفعة',
            self::TYPE_CLEARANCE_CLOSURE => 'إخلاء وإغلاق المشروع',
            self::TYPE_PROJECT_CLOSURE => 'إغلاق المشروع',
            self::TYPE_CLEARANCE => 'إخلاء',
            self::TYPE_PAYMENT => 'طلب دفعة',
            default => $this->request_type,
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->request_type) {
            self::TYPE_CLEARANCE_PAYMENT => 'badge-info',
            self::TYPE_CLEARANCE_CLOSURE => 'badge-dark',
            self::TYPE_PROJECT_CLOSURE => 'badge-secondary',
            self::TYPE_CLEARANCE => 'badge-warning',
            self::TYPE_PAYMENT => 'badge-success',
            default => 'badge-light',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_CONFIRMED => 'مسودة مؤكدة',
            self::STATUS_PENDING => 'قيد المراجعة والاعتماد',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_REJECTED => 'مرفوض',
            self::STATUS_CANCELLED => 'ملغي',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_CONFIRMED => 'badge-primary',
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-secondary',
            default => 'badge-light',
        };
    }

    // =========================================================================
    // VISIBILITY SCOPE
    // =========================================================================

    public function scopeVisibleToUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('project', function ($q) {
            $q->visibleToUser('projects');
        });
    }

    public function getErpnextLastErrorAttribute(): ?string
    {
        return $this->erpnext_sync_error;
    }

    public function setErpnextLastErrorAttribute(?string $value): void
    {
        $this->attributes['erpnext_sync_error'] = $value;
    }
}

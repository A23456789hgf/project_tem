<?php

namespace App\Models;

use App\Enums\EntityResponsibilityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the configuration of a single approval stage for a given entity.
 *
 * A row in this table means:
 *   - The entity has this stage ENABLED.
 *   - The stage will be included (in stage_order) when generating an approval chain.
 *   - The responsible_user_id is the explicit user who will handle this stage.
 *
 * Source of Truth:
 *   entity_id + stage  → enabled (row exists means enabled)
 *   responsible_user_id → who will handle the stage
 *   is_final             → is this the final stage in the chain?
 *   triggers_execution   → does approving this stage push the request into execution?
 *
 * @property int $id
 * @property int $entity_id
 * @property string $stage (EntityResponsibilityType value)
 * @property int $stage_order
 * @property int|null $responsible_user_id
 * @property bool $is_final
 * @property bool $triggers_execution
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class EntityApprovalStage extends Model
{
    /**
     * الإجراءات (الحالات) المسموح بها في المراحل.
     *
     * هذه القائمة هي المصدر الوحيد للحقيقة، ويجب أن يشترك فيها:
     *   - EntityApprovalStageController (للتحقق)
     *   - AuthorityApprovalStage (نفس القائمة)
     *   - الواجهات (للعرض)
     */
    public const AVAILABLE_ACTIONS = [
        'submit',
        'approve',
        'return',
        'reject',
        'need_action',
        'consult',
        'forward',
        'comment',
    ];

    /**
     * أنواع طلبات سير العمل المدعومة في النظام.
     */
    public const WORKFLOW_TYPES = [
        'project_approval' => 'اعتماد مشروع',
        'clearance' => 'طلب إخلاء مالي',
        'payment' => 'طلب صرف دفعة',
        'clearance_payment' => 'طلب إخلاء ودفعة موحد',
        'clearance_closure' => 'طلب إخلاء وإغلاق مشروع',
        'project_closure' => 'طلب إغلاق مشروع',
    ];

    /**
     * الإجراءات الافتراضية عند عدم تحديد أي إجراء.
     */
    public const DEFAULT_ACTIONS = ['approve', 'return'];

    /**
     * الحالة الفعلية للمعاملات النشطة في جدول project_approvals.
     * مهم: يجب أن تطابق القيمة الفعلية المخزنة في DB (حرف صغير).
     */
    public const PENDING_STATUS = 'pending';

    protected $fillable = [
        'entity_id',
        'workflow_type',
        'stage',
        'stage_key',
        'stage_name_ar',
        'stage_name_en',
        'available_actions',
        'required_attachments',
        'action_rules',
        'completion_requirements',
        'is_active',
        'is_final',
        'triggers_execution',
        'stage_order',
        'responsible_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'stage_order' => 'integer',
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'triggers_execution' => 'boolean',
        'available_actions' => 'array',
        'required_attachments' => 'array',
        'action_rules' => 'array',
        'completion_requirements' => 'array',
    ];

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    public function entity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'entity_id');
    }

    /** The user explicitly configured as responsible for this stage. */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id')->withoutGlobalScopes();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ----------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------

    /** Return stages for an entity ordered by their stage_order. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('stage_order');
    }

    /** Return only stages flagged as final. */
    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /** Return only stages that trigger execution. */
    public function scopeTriggeringExecution($query)
    {
        return $query->where('triggers_execution', true);
    }

    /** Return only active stages. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /** Typed accessor for the stage enum. */
    public function stageEnum(): ?EntityResponsibilityType
    {
        return EntityResponsibilityType::tryFrom($this->stage)
            ?? EntityResponsibilityType::fromApprovalPhase($this->stage);
    }

    /**
     * Check whether the configured responsible user is valid:
     *   - Exists
     *   - Is Active
     *   - Belongs to this entity
     */
    public function hasValidResponsibleUser(): bool
    {
        $user = $this->responsibleUser;

        return $user !== null
            && in_array($user->status, ['Active', 'active'])
            && (int) $user->entity_id === (int) $this->entity_id;
    }

    /**
     * قائمة الإجراءات الفعلية، مع الافتراضي عند null/فراغ.
     *
     * الاستخدام:
     *   $stage->available_actions_list
     *   $stage->available_actions_list = ['approve', 'return']
     */
    public function getAvailableActionsListAttribute(): array
    {
        $actions = $this->available_actions;

        return is_array($actions) && ! empty($actions)
            ? array_values($actions)
            : self::DEFAULT_ACTIONS;
    }

    /**
     * هل هذه المرحلة النهائية في السلسلة؟
     * (المرحلة التي عند اعتمادها يدخل الطلب التنفيذ)
     */
    public function isFinalStage(): bool
    {
        return (bool) $this->is_final;
    }

    /**
     * هل اعتماد هذه المرحلة يُفعِّل التنفيذ؟
     */
    public function triggersExecution(): bool
    {
        return (bool) $this->triggers_execution;
    }
}

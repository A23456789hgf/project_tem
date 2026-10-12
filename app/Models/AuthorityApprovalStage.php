<?php

namespace App\Models;

use App\Enums\EntityResponsibilityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the configuration of a single approval stage for a given external authority.
 *
 * A row in this table means:
 *   - The authority has this stage ENABLED.
 *   - The stage will be included (in stage_order) when generating an approval chain.
 *   - The responsible_user_id is the explicit user who will handle this stage.
 *   - is_final             → is this the final stage in the chain?
 *   - triggers_execution   → does approving this stage push the request into execution?
 *
 * @property int $id
 * @property int $authority_id
 * @property string $stage (EntityResponsibilityType value)
 * @property int $stage_order
 * @property int|null $responsible_user_id
 * @property bool $is_final
 * @property bool $triggers_execution
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class AuthorityApprovalStage extends Model
{
    use HasFactory;

    /**
     * الإجراءات (الحالات) المسموح بها في المراحل.
     *
     * ملاحظة: يجب أن تبقى متزامنة مع EntityApprovalStage::AVAILABLE_ACTIONS.
     * عند الحاجة لمصدر موحّد، انقلها إلى trait مشترك أو enum.
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
     * الإجراءات الافتراضية عند عدم تحديد أي إجراء.
     */
    public const DEFAULT_ACTIONS = ['approve', 'return'];

    /**
     * الحالة الفعلية للمعاملات النشطة في جدول project_approvals.
     */
    public const PENDING_STATUS = 'pending';

    protected $fillable = [
        'authority_id',
        'workflow_type',
        'stage',
        'stage_key',
        'stage_name_ar',
        'stage_name_en',
        'available_actions',
        'required_attachments',
        'action_rules',
        'completion_requirements',
        'stage_order',
        'responsible_user_id',
        'is_active',
        'is_final',
        'triggers_execution',
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

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'authority_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ----------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------

    /** Return stages ordered by their stage_order. */
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

    public function stageEnum(): ?EntityResponsibilityType
    {
        return EntityResponsibilityType::tryFrom($this->stage)
            ?? EntityResponsibilityType::fromApprovalPhase($this->stage);
    }

    public function hasValidResponsibleUser(): bool
    {
        $user = $this->responsibleUser;

        return $user !== null
            && in_array($user->status, ['Active', 'active'])
            && (int) $user->authority_id === (int) $this->authority_id;
    }

    /**
     * قائمة الإجراءات الفعلية، مع الافتراضي عند null/فراغ.
     *
     * الاستخدام:
     *   $stage->available_actions_list
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

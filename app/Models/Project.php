<?php

namespace App\Models;

use App\Http\Controllers\Project\Services\ProjectService;
use App\Services\ProjectNumberGenerator;
use App\Traits\Auditable;
use App\Traits\HasCreatorTracking;
use App\Traits\HasProjectVisibility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// تم إزالة HasModelVisibility لإصلاح خطأ "Call to undefined method App\Scopes\DomainScope::applyEntityBasedScope()"
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    use Auditable, HasCreatorTracking, HasFactory, HasProjectVisibility; // بدون HasModelVisibility

    protected $fillable = [
        'project_type',
        'project_name',
        'program_id',
        'domain_id',
        'subdomain_id',
        'intervention_id',
        'start_date_gregorian',
        'start_date_hijri',
        'end_date_gregorian',
        'end_date_hijri',
        'number_of_beneficiaries',
        'main_directives',
        'subdirectives',
        'priority',
        'target_categories',
        'target_category_id',
        'priority_id',
        'status',
        'draft_saved_at',
        'finalized_at',
        'form_number',
        'qr_code',
        'current_approval_stage_id',
        'created_by_user_id',
        'approval_status',
        'last_saved_step',
        'frappe_synced',
        'frappe_sync_at',
        'frappe_sync_attempts',
        'frappe_project_id',
        'frappe_sync_error',
        'frappe_last_sync_attempt',
        'erpnext_project_id',
        'sync_status',
        'synced_to_erpnext_at',
        'frappe_project_name',
        'frappe_sync_status',
        'frappe_synced_at',
        'sync_error',
        'execution_started_at',
        'created_by_entity',
        'updated_by_user_id',
        'updated_by_entity',
        'current_stage',
        'current_stage_order',
        'completed_at',
        'internal_entity_id',
        'source_type',
        'authority_id',
        'created_by',
        'creator_username',
        'creator_entity_id',
        'is_data_completed',
        'data_completed_at',
        'entity_modified',
    ];

    protected $appends = ['project_duration', 'mainObjective', 'current_stage_name', 'execution_progress', 'draft_integrity'];

    protected $casts = [
        'draft_saved_at' => 'datetime',
        'finalized_at' => 'datetime',
        'completed_at' => 'datetime',
        'frappe_sync_at' => 'datetime',
        'frappe_last_sync_attempt' => 'datetime',
        'frappe_synced' => 'boolean',
        'synced_to_erpnext_at' => 'datetime',
        'frappe_synced_at' => 'datetime',
        'execution_started_at' => 'datetime',
        'is_data_completed' => 'boolean',
        'data_completed_at' => 'datetime',
        'entity_modified' => 'boolean',
    ];

    /**
     * تطبيق فلترة النطاق على استعلام المشاريع
     * فلترة على الجهات المنشئة (creator_entity_id) والجهات الداخلية (internal_entity_id)
     * تم استبعاد جميع العلاقات الأخرى
     *
     * @param  Builder  $query
     * @param  array  $entityIds  قائمة معرفات الجهات الداخلية المسموح بها
     * @return Builder
     */
    public function scopeForUserEntities($query, $entityIds)
    {
        // إذا كان المستخدم مسؤولاً (Admin) → لا نطبق أي فلترة (يرى كل شيء)
        if (Auth::check() && Auth::user()->isAdmin()) {
            return $query;
        }

        // إذا كانت قائمة المعرفات فارغة → لا نعرض أي شيء
        if (empty($entityIds)) {
            return $query->whereRaw('0=1');
        }

        // تأكد من أن $entityIds هي مصفوفة أرقام صحيحة
        $entityIds = array_filter($entityIds, function ($id) {
            return is_numeric($id) && $id > 0;
        });

        // إذا أصبحت المصفوفة فارغة بعد التنقية → لا نعرض أي شيء
        if (empty($entityIds)) {
            return $query->whereRaw('0=1');
        }

        // إعادة ترقيم المصفوفة
        $entityIds = array_values($entityIds);

        // الفلترة على creator_entity_id و internal_entity_id فقط
        return $query->where(function ($q) use ($entityIds) {
            $q->whereIn('creator_entity_id', $entityIds)
                ->orWhereIn('internal_entity_id', $entityIds);
        });
    }

    // --- Accessors ---

    /**
     * Check draft integrity and completion state.
     * Returns a structured report of the project's completeness without blocking persistence.
     */
    public function checkDraftIntegrity(): array
    {
        $missingFields = [];
        $completedSteps = [];
        $totalSteps = 6; // Steps 1 to 6 contain data fields

        // Step 1: Basic Information
        $step1Fields = ['project_name', 'program_id', 'domain_id', 'subdomain_id'];
        $step1Missing = [];
        foreach ($step1Fields as $field) {
            if (empty($this->$field)) {
                $step1Missing[] = $field;
            }
        }
        if (empty($step1Missing)) {
            $completedSteps[] = 1;
        } else {
            $missingFields['step_1'] = $step1Missing;
        }

        // Step 2: Project Details & Objectives
        $detail = $this->relationLoaded('detail') ? $this->detail : $this->detail()->first();
        $hasDetails = $detail && (! empty($detail->project_summary) || ! empty($detail->problem_and_justification) || ! empty($detail->project_introduction) || ! empty($detail->project_components));
        $mainObjectivesCount = isset($this->main_objectives_count) ? $this->main_objectives_count : ($this->relationLoaded('mainObjectives') ? $this->mainObjectives->count() : $this->mainObjectives()->count());
        $specialObjectivesCount = isset($this->special_objectives_count) ? $this->special_objectives_count : ($this->relationLoaded('specialObjectives') ? $this->specialObjectives->count() : $this->specialObjectives()->count());
        $hasObjectives = ($mainObjectivesCount > 0 || $specialObjectivesCount > 0);
        if ($hasDetails && $hasObjectives) {
            $completedSteps[] = 2;
        } else {
            $step2Missing = [];
            if (! $hasDetails) {
                $step2Missing[] = 'project_details';
            }
            if (! $hasObjectives) {
                $step2Missing[] = 'objectives';
            }
            $missingFields['step_2'] = $step2Missing;
        }

        // Step 3: Entities (Supervising & Implementing)
        $supervisingCount = isset($this->supervising_authorities_count) ? $this->supervising_authorities_count : ($this->relationLoaded('supervisingAuthorities') ? $this->supervisingAuthorities->count() : $this->supervisingAuthorities()->count());
        $implementingCount = isset($this->implementing_entities_count) ? $this->implementing_entities_count : ($this->relationLoaded('implementingEntities') ? $this->implementingEntities->count() : $this->implementingEntities()->count());
        $hasSupervising = $supervisingCount > 0;
        $hasImplementing = $implementingCount > 0;
        if ($hasSupervising && $hasImplementing) {
            $completedSteps[] = 3;
        } else {
            $step3Missing = [];
            if (! $hasSupervising) {
                $step3Missing[] = 'supervising_authorities';
            }
            if (! $hasImplementing) {
                $step3Missing[] = 'implementing_entities';
            }
            $missingFields['step_3'] = $step3Missing;
        }

        // Step 4 & 5: Activities (Preliminary or Executive)
        $preliminaryCount = isset($this->preliminary_activities_count) ? $this->preliminary_activities_count : ($this->relationLoaded('preliminaryActivities') ? $this->preliminaryActivities->count() : $this->preliminaryActivities()->count());
        $executiveCount = isset($this->executive_activities_count) ? $this->executive_activities_count : ($this->relationLoaded('executiveActivities') ? $this->executiveActivities->count() : $this->executiveActivities()->count());
        $hasPrelim = $preliminaryCount > 0;
        $hasExec = $executiveCount > 0;
        $hasActivities = ($hasPrelim || $hasExec);

        if ($hasPrelim) {
            $completedSteps[] = 4;
        }
        if ($hasExec) {
            $completedSteps[] = 5;
        }
        if ($hasActivities) {
            if (! $hasPrelim) {
                $completedSteps[] = 4;
            }
            if (! $hasExec) {
                $completedSteps[] = 5;
            }
        } else {
            $missingFields['step_4_5'] = ['activities'];
        }

        // Step 6: Costs & Financing
        $cost = $this->relationLoaded('cost') ? $this->cost : $this->cost()->first();
        $hasCost = $cost && $cost->total_cost > 0;
        $financingsCount = isset($this->financings_count) ? $this->financings_count : ($this->relationLoaded('financings') ? $this->financings->count() : $this->financings()->count());
        $hasFinancing = $financingsCount > 0;
        if ($hasCost && $hasFinancing) {
            $completedSteps[] = 6;
        } else {
            $step6Missing = [];
            if (! $hasCost) {
                $step6Missing[] = 'project_cost';
            }
            if (! $hasFinancing) {
                $step6Missing[] = 'financings';
            }
            $missingFields['step_6'] = $step6Missing;
        }

        $completedStepsCount = count($completedSteps);
        $completionPercentage = (int) (($completedStepsCount / $totalSteps) * 100);

        // The completion state strictly depends on whether there are missing required fields or not.
        $isValid = empty($missingFields);

        return [
            'is_valid' => $isValid,
            'completion_percentage' => $isValid ? 100 : $completionPercentage,
            'completed_steps' => $completedSteps,
            'missing_fields' => $missingFields,
            'completion_state' => $isValid ? 'ready_to_finalize' : 'raw_draft',
        ];
    }

    /**
     * Get the draft integrity state.
     */
    public function getDraftIntegrityAttribute(): array
    {
        return $this->checkDraftIntegrity();
    }

    /**
     * Check if project is in draft status (incomplete or completed draft).
     */
    public function isDraft(): bool
    {
        return in_array($this->status, ['draft', 'completed_draft'], true);
    }

    /**
     * Check if project is in completed draft status.
     */
    public function isCompletedDraft(): bool
    {
        return $this->status === 'completed_draft';
    }

    /**
     * Check if project is currently in the approval workflow.
     */
    public function isPendingApproval(): bool
    {
        return in_array($this->status, ['pending_approval', 'financial_technical_review', 'internally_approved', 'final'], true);
    }

    /**
     * Check if project was rolled back for action/completion.
     */
    public function isRolledBackForReview(): bool
    {
        return $this->status === 'rolled_back_for_review';
    }

    /**
     * Check if project is in execution.
     */
    public function isInExecution(): bool
    {
        return in_array($this->status, ['in_execution', 'in_progress', 'implementation', 'completed'], true);
    }

    /**
     * Get the single currently active approval step.
     */
    public function getActiveApprovalStep(): ?ProjectApproval
    {
        if ($this->relationLoaded('projectApprovals')) {
            $active = $this->projectApprovals->firstWhere('is_active', true);
            if ($active) {
                return $active;
            }
            if ($this->current_stage) {
                return $this->projectApprovals->firstWhere('drop', $this->current_stage);
            }
        }

        return $this->projectApprovals()
            ->where('is_active', true)
            ->first() ?? ($this->current_stage ? $this->projectApprovals()->where('drop', $this->current_stage)->first() : null);
    }

    public function getProjectDurationAttribute(): int
    {
        if (! $this->start_date_gregorian || ! $this->end_date_gregorian) {
            return 0;
        }

        $start = Carbon::parse($this->start_date_gregorian);
        $end = Carbon::parse($this->end_date_gregorian);

        return $end->diffInDays($start);
    }

    public function getMainObjectiveAttribute()
    {
        if ($this->relationLoaded('mainObjectives')) {
            return $this->mainObjectives->first();
        }

        return $this->mainObjectives()->first();
    }

    /**
     * Get the display name of the entity that created the project.
     *
     * Priority:
     *   1. creator_entity_id → name from internal_entities via creatorEntity() relation.
     *   2. created_by_entity (legacy string field) as a direct fallback.
     *   3. The creating user's department string.
     */
    public function getCreatorEntityNameAttribute(): string
    {
        // 1. External Authority Project
        if ($this->isExternal() || $this->authority_id) {
            if ($this->authority_id) {
                $name = $this->relationLoaded('authority')
                    ? optional($this->authority)->agency_name
                    : DB::table('authorities')->where('id', $this->authority_id)->value('agency_name');
                if ($name) {
                    return (string) $name;
                }
            }
        }

        // 2. Preferred for internal: use creator_entity_id FK relation
        if ($this->creator_entity_id) {
            $name = $this->relationLoaded('creatorEntity')
                ? optional($this->creatorEntity)->name
                : DB::table('internal_entities')
                    ->where('id', $this->creator_entity_id)
                    ->value('name');

            if ($name) {
                return (string) $name;
            }
        }

        // 3. Legacy fallback: string stored in created_by_entity
        if (! empty($this->created_by_entity)) {
            return (string) $this->created_by_entity;
        }

        // 4. Fallback: creator user's department
        if ($this->createdBy) {
            return $this->createdBy->department ?? '-';
        }

        return '-';
    }

    /**
     * Get the display name of the project's current status/stage.
     */
    public function getCurrentStageNameAttribute(): string
    {
        // 1. Identify specific system statuses that have priority labels
        if ($this->status === 'completed') {
            return 'مكتمل';
        } elseif ($this->status === 'in_progress' || $this->status === 'implementation' || $this->status === 'in_execution') {
            return 'قيد التنفيذ';
        } elseif ($this->status === 'rejected') {
            return 'مرفوض';
        }

        // 2. Try to resolve the stage name from the "current_stage" column
        // This handles hierarchical stages like "entity_41" or specific codes like "assembly"
        $stageCode = $this->current_stage ?? $this->status;

        if ($stageCode) {
            // Handle entity-based stages: "entity_{id}" or "entity_{id}_{phase}"
            if (str_starts_with($stageCode, 'entity_')) {
                if (preg_match('/^entity_(\d+)(?:_(technical_review|financial_review|stage_approval))?$/', $stageCode, $matches) === 1) {
                    $entityId = $matches[1];
                    static $stageEntityCache = [];
                    if (! array_key_exists($entityId, $stageEntityCache)) {
                        // Use direct DB query to bypass ALL scopes and models
                        $entityName = DB::table('internal_entities')
                            ->where('id', $entityId)
                            ->value('name');
                        $stageEntityCache[$entityId] = $entityName;
                    }
                    if ($stageEntityCache[$entityId]) {
                        $phaseNames = [
                            'technical_review' => 'مراجعة فنية',
                            'financial_review' => 'مراجعة مالية',
                            'stage_approval' => 'اعتماد للمرحلة',
                        ];

                        $phase = $matches[2] ?? null;

                        return $phase && isset($phaseNames[$phase])
                            ? (string) $stageEntityCache[$entityId].' - '.$phaseNames[$phase]
                            : (string) $stageEntityCache[$entityId];
                    }
                }
            }

            // Handle named stages via code mapping
            $namedStages = [
                'assembly' => 'موافقة الجمعية',
                'union' => 'موافقة الاتحاد',
                'committee' => 'موافقة اللجنة',
                'implementation' => 'التنفيذ',
                'financial_review' => 'مراجعة مالية',
            ];

            if (isset($namedStages[$stageCode])) {
                return $namedStages[$stageCode];
            }
        }

        // 3. Check for dedicated Stage through currentApprovalStage relation
        if ($this->current_approval_stage_id) {
            if ($this->relationLoaded('currentApprovalStage') && $this->currentApprovalStage) {
                return $this->currentApprovalStage->name_ar ?? $this->currentApprovalStage->name ?? $this->status;
            } else {
                static $stageCache = [];
                if (! array_key_exists($this->current_approval_stage_id, $stageCache)) {
                    $stage = DB::table('stages')
                        ->where('id', $this->current_approval_stage_id)
                        ->first(['name_ar', 'name']);
                    $stageCache[$this->current_approval_stage_id] = $stage ? ($stage->name_ar ?? $stage->name) : null;
                }

                if ($stageCache[$this->current_approval_stage_id]) {
                    return (string) $stageCache[$this->current_approval_stage_id];
                }
            }
        }

        // 4. Manual mapping for other generic statuses
        if ($this->status === 'pending_approval' || $this->status === 'final') {
            return 'بانتظار الاعتماد';
        } elseif ($this->status === 'internally_approved') {
            return 'معتمد داخلياً';
        }

        if (in_array($this->status, ['draft', 'completed_draft', 'rolled_back_for_review'])) {
            return $this->created_by_entity ?? 'مقدم المشروع';
        }

        return $this->status ?? '-';
    }

    public function getExecutionProgressAttribute(): int
    {
        if ($this->status !== 'in_execution') {
            return 0;
        }

        $preliminaryCount = $this->hasMany(PreliminaryProcedureExecution::class)->count();
        $executiveCount = $this->hasMany(ProjectExecution::class)->count();

        $totalCount = $preliminaryCount + $executiveCount;

        if ($totalCount === 0) {
            return 0;
        }

        $preliminarySum = $this->hasMany(PreliminaryProcedureExecution::class)->sum('completion_percentage');
        $executiveSum = $this->hasMany(ProjectExecution::class)->sum('completion_percentage');

        return (int) round(($preliminarySum + $executiveSum) / $totalCount);
    }

    // --- العلاقات BelongsTo ---

    public function program(): BelongsTo
    {
        // Bypass active_only scope: a project may reference a still-pending program.
        return $this->belongsTo(Program::class)->withoutGlobalScope('active_only');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class)->withoutGlobalScope('active_only');
    }

    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class)->withoutGlobalScope('active_only');
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class)->withoutGlobalScope('active_only');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id')->withoutGlobalScope('active_only');
    }

    public function targetCategory(): BelongsTo
    {
        return $this->belongsTo(TargetCategory::class, 'target_category_id');
    }

    public function currentApprovalStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'current_approval_stage_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function internalEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'internal_entity_id');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'authority_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creatorEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'creator_entity_id');
    }

    // --- العلاقات HasOne ---

    public function detail(): HasOne
    {
        return $this->hasOne(ProjectDetail::class);
    }

    public function cost(): HasOne
    {
        return $this->hasOne(ProjectCost::class);
    }

    public function getTotalCostAttribute(): float
    {
        return (float) ($this->cost?->total_cost ?? 0);
    }

    public function projectRequest(): HasOne
    {
        return $this->hasOne(ProjectRequest::class);
    }

    // --- العلاقات HasMany ---

    public function mainObjectives(): HasMany
    {
        return $this->hasMany(MainObjective::class);
    }

    public function specialObjectives(): HasMany
    {
        return $this->hasMany(SpecialObjective::class);
    }

    public function objectiveResults(): HasMany
    {
        return $this->hasMany(ObjectiveResult::class);
    }

    public function resultOutputs(): HasMany
    {
        return $this->hasMany(ResultOutput::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProjectLocation::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(ProjectRisk::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(ProjectCost::class);
    }

    public function financings(): HasMany
    {
        return $this->hasMany(ProjectFinancing::class);
    }

    public function supervisingAuthorities(): HasMany
    {
        return $this->hasMany(ProjectSupervisingAuthority::class, 'project_id');
    }

    public function correspondences(): HasMany
    {
        return $this->hasMany(Correspondence::class, 'project_id');
    }

    public function implementingEntities(): HasMany
    {
        return $this->hasMany(ProjectImplementingEntity::class, 'project_id');
    }

    public function participatingEntities(): HasMany
    {
        return $this->hasMany(ParticipatingEntity::class);
    }

    public function preliminaryActivities(): HasMany
    {
        return $this->hasMany(PreliminaryActivity::class);
    }

    public function preliminaryProcedures(): HasMany
    {
        return $this->hasMany(PreliminaryProcedure::class);
    }

    public function preliminaryCosts(): HasMany
    {
        return $this->hasMany(PreliminaryCost::class);
    }

    public function preliminaryFinancialSummaries(): HasMany
    {
        return $this->hasMany(PreliminaryFinancialSummary::class);
    }

    public function executiveActivities(): HasMany
    {
        return $this->hasMany(ExecutiveActivity::class);
    }

    public function executiveActivityActions(): HasMany
    {
        return $this->hasMany(ExecutiveActivityAction::class);
    }

    public function executiveActionAssigneds(): HasMany
    {
        return $this->hasMany(ExecutiveActionAssigned::class);
    }

    public function executiveActionCosts(): HasMany
    {
        return $this->hasMany(ExecutiveActionCost::class);
    }

    public function executiveFinancialSummaries(): HasMany
    {
        return $this->hasMany(ExecutiveFinancialSummary::class);
    }

    public function beneficiaryEntities(): HasMany
    {
        return $this->hasMany(BeneficiaryEntity::class);
    }

    public function getProjectLinkedEntities()
    {
        $entities = collect();

        // 1. Implementing entities
        foreach ($this->implementingEntities()->with(['internalEntity', 'authority'])->get() as $entity) {
            $name = $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
            if ($name) {
                $entities->push($name);
            }
        }

        // 2. Participating entities
        foreach ($this->participatingEntities()->with(['internalEntity', 'authority'])->get() as $entity) {
            $name = $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
            if ($name) {
                $entities->push($name);
            }
        }

        // 3. Supervising authorities
        foreach ($this->supervisingAuthorities()->with(['internalEntity', 'authority'])->get() as $entity) {
            $name = $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
            if ($name) {
                $entities->push($name);
            }
        }

        // 4. Beneficiary entities
        foreach ($this->beneficiaryEntities()->with(['internalEntity', 'authority'])->get() as $entity) {
            $name = $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
            if ($name) {
                $entities->push($name);
            }
        }

        return $entities->unique()->values();
    }

    public function projectEntities(): HasMany
    {
        return $this->hasMany(ProjectEntity::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ProjectApprovalRequest::class);
    }

    public function movementLogs(): HasMany
    {
        return $this->hasMany(ProjectMovementLog::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function projectApprovals(): HasMany
    {
        return $this->hasMany(ProjectApproval::class);
    }

    public function qualityRecords(): HasMany
    {
        return $this->hasMany(ProjectQuality::class);
    }

    public function activityHistory(): HasMany
    {
        return $this->hasMany(ProjectActivityHistory::class);
    }

    public function memoirs(): HasMany
    {
        return $this->hasMany(Memoir::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(ProjectReferral::class);
    }

    public function draftCollaborators(): HasMany
    {
        return $this->hasMany(ProjectDraftCollaborator::class);
    }

    public function draftActivities(): HasMany
    {
        return $this->hasMany(ProjectDraftActivity::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(ProjectAchievement::class)->latest();
    }

    public function erpnextSyncLogs(): HasMany
    {
        return $this->hasMany(ErpNextSyncLog::class)->latest();
    }

    // ==================== MANY-TO-MANY RELATIONSHIPS ====================

    public function beneficiaryGroups(): BelongsToMany
    {
        return $this->belongsToMany(BeneficiaryGroup::class, 'beneficiary_group_project');
    }

    // --- Draft Validation Methods ---

    /**
     * Get the ID of the entity that created the project.
     */
    public function getOriginEntityId(): ?int
    {
        if ($this->creator_entity_id) {
            return (int) $this->creator_entity_id;
        }

        if ($this->internal_entity_id) {
            return (int) $this->internal_entity_id;
        }

        if ($this->createdBy && $this->createdBy->entity_id) {
            return (int) $this->createdBy->entity_id;
        }

        if (! empty($this->created_by_entity)) {
            if (is_numeric($this->created_by_entity)) {
                return (int) $this->created_by_entity;
            }
            $entity = InternalEntity::withoutGlobalScopes()->where('name', trim($this->created_by_entity))->first();
            if ($entity) {
                return (int) $entity->id;
            }
        }

        return null;
    }

    /**
     * Get the ID of the external authority that created the project.
     * Never returns an internal entity ID — respects semantic separation.
     */
    public function getOriginAuthorityId(): ?int
    {
        if ($this->authority_id) {
            return (int) $this->authority_id;
        }

        if ($this->createdBy && $this->createdBy->authority_id) {
            return (int) $this->createdBy->authority_id;
        }

        return null;
    }

    /**
     * Get the origin type of the project: 'external' or 'internal'.
     * Relies on source_type when available; falls back to field inspection.
     */
    public function getOriginType(): string
    {
        if (! empty($this->source_type)) {
            return $this->source_type;
        }

        // Heuristic: if authority_id is set and creator_entity_id is not, it is external.
        if ($this->authority_id && ! $this->creator_entity_id && ! $this->internal_entity_id) {
            return 'external';
        }

        return 'internal';
    }

    public function isInternal(): bool
    {
        return $this->getOriginType() === 'internal';
    }

    public function isExternal(): bool
    {
        return $this->getOriginType() === 'external';
    }

    /**
     * Check if the project is currently in the stage belonging to the creator entity.
     */
    public function isInCreatorStage(): bool
    {
        $originEntityId = $this->getOriginEntityId();

        // 1. If current_stage is an entity stage (e.g. "entity_5_technical_review")
        if (! empty($this->current_stage) && str_starts_with($this->current_stage, 'entity_')) {
            if ($originEntityId && preg_match('/^entity_(\d+)/', $this->current_stage, $matches) === 1) {
                return (int) $matches[1] === $originEntityId;
            }

            return ($this->current_stage_order ?? 1) <= 1;
        }

        // 2. If current_stage is a named non-entity stage (e.g. "implementation")
        if ($this->current_stage === 'implementation') {
            return false;
        }

        // 3. Check by current_stage_order (stage 1 in dynamic hierarchy is always the origin entity)
        if (isset($this->current_stage_order)) {
            return (int) $this->current_stage_order <= 1;
        }

        // 4. Check current_approval_stage_id if present
        if ($this->current_approval_stage_id) {
            $stage = $this->currentApprovalStage ?? Stage::find($this->current_approval_stage_id);
            if ($stage) {
                if ($originEntityId && $stage->code === 'entity_'.$originEntityId) {
                    return true;
                }

                return ($stage->order ?? 1) <= 1;
            }
        }

        return false;
    }

    /**
     * Check if project can be reverted to draft status.
     */
    public function canBeRevertedToDraft(?User $user = null): bool
    {
        // Projects already in draft states or terminal states cannot be reverted
        if (in_array($this->status, ['draft', 'completed_draft', 'rolled_back_for_review', 'in_execution', 'completed', 'rejected', 'cancelled'], true)) {
            return false;
        }

        // Must be in the stage belonging to the entity that created the project
        if (! $this->isInCreatorStage()) {
            return false;
        }

        return true;
    }

    /**
     * Check if project has all required data for finalization
     */
    public function isDraftComplete(): bool
    {
        return $this->checkDraftIntegrity()['is_valid'];
    }

    /**
     * Get list of missing required fields as human-readable Arabic section names.
     * Keys must match the array keys returned by checkDraftIntegrity().
     */
    public function getMissingRequiredFields(): array
    {
        $integrity = $this->checkDraftIntegrity();
        if ($integrity['is_valid']) {
            return [];
        }

        $sectionLabels = [
            'step_1' => 'البيانات الأساسية (اسم المشروع، البرنامج، المجال، المجال الفرعي)',
            'step_2' => 'تفاصيل المشروع والأهداف',
            'step_3' => 'الجهات الإشرافية والمنفذة',
            'step_4_5' => 'الأنشطة التمهيدية أو التنفيذية',
            'step_6' => 'التمويل والتكلفة الإجمالية',
        ];

        $missing = [];
        foreach ($integrity['missing_fields'] as $key => $fields) {
            if (isset($sectionLabels[$key])) {
                $missing[] = $sectionLabels[$key];
            }
        }

        return $missing;
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): int
    {

        $totalSections = 6;
        $completedSections = 0;

        if ($this->validateBasicInfo()) {
            $completedSections++;
        }
        if ($this->validateObjectives()) {
            $completedSections++;
        }
        if ($this->validateLocations()) {
            $completedSections++;
        }
        if ($this->validateStakeholders()) {
            $completedSections++;
        }
        if ($this->validateActivities()) {
            $completedSections++;
        }
        if ($this->validateFinancing()) {
            $completedSections++;
        }

        return round(($completedSections / $totalSections) * 100);
    }

    /**
     * Validate basic information
     */
    public function validateBasicInfo(): bool
    {
        return ! empty($this->project_name) &&
            ! empty($this->program_id) &&
            ! empty($this->domain_id) &&
            (! empty($this->start_date_gregorian) || ! empty($this->start_date_hijri)) &&
            (! empty($this->end_date_gregorian) || ! empty($this->end_date_hijri));
    }

    /**
     * Validate objectives and results
     */
    public function validateObjectives(): bool
    {
        if (isset($this->main_objectives_count) && isset($this->special_objectives_count)) {
            return $this->main_objectives_count > 0 && $this->special_objectives_count > 0;
        }

        return $this->mainObjectives()->exists() &&
            $this->specialObjectives()->exists();
    }

    /**
     * Validate project locations
     */
    public function validateLocations(): bool
    {
        if (isset($this->locations_count)) {
            return $this->locations_count > 0;
        }

        return $this->locations()->exists();
    }

    /**
     * Validate stakeholders
     */
    public function validateStakeholders(): bool
    {
        if (isset($this->supervising_authorities_count) && isset($this->implementing_entities_count)) {
            return $this->supervising_authorities_count > 0 || $this->implementing_entities_count > 0;
        }

        return $this->supervisingAuthorities()->exists() ||
            $this->implementingEntities()->exists();
    }

    /**
     * Validate activities
     */
    public function validateActivities(): bool
    {
        if (isset($this->preliminary_activities_count) && isset($this->executive_activities_count)) {
            return $this->preliminary_activities_count > 0 || $this->executive_activities_count > 0;
        }

        return $this->preliminaryActivities()->exists() ||
            $this->executiveActivities()->exists();
    }

    /**
     * Validate financing and costs
     */
    public function validateFinancing(): bool
    {
        if (isset($this->financings_count)) {
            return $this->financings_count > 0;
        }

        return $this->financings()->exists() &&
            $this->cost()->exists();
    }

    // --- Booted Method (Global Scopes & Events) ---

    protected static function booted()
    {
        // تم إزالة الـ global scope الإضافي (project_visibility)
        // لأن DomainScope يتم تطبيقه تلقائياً عبر AppServiceProvider أو HasDomainScope trait

        /**
         * EVENT: Creating
         * Automatically generates a project form number.
         */
        static::creating(function ($project) {
            if ($project->form_number) {
                return;
            }
            $project->form_number = ProjectNumberGenerator::getNextProjectNumber();
        });

        /**
         * EVENT: Deleting
         * Handle cascade delete for all related models when rolling back an import or deleting a project.
         */
        static::deleting(function ($project) {
            $project->detail()->delete();
            $project->cost()->delete();
            $project->projectRequest()->delete();
            $project->mainObjectives()->delete();
            $project->specialObjectives()->delete();
            // ObjectiveResults related models (outputs) will be deleted if they have their own deleting events or via DB cascade.
            // For safety, we can just delete the direct relations.
            $project->objectiveResults()->delete();
            $project->locations()->delete();
            $project->risks()->delete();
            $project->financings()->delete();
            $project->supervisingAuthorities()->delete();
            $project->implementingEntities()->delete();
            $project->participatingEntities()->delete();
            $project->beneficiaryEntities()->delete();
            $project->preliminaryActivities()->delete();
            $project->preliminaryProcedures()->delete();
            $project->preliminaryCosts()->delete();
            $project->preliminaryFinancialSummaries()->delete();
            $project->executiveActivities()->delete();
            $project->executiveActivityActions()->delete();
            $project->executiveActionAssigneds()->delete();
            $project->executiveActionCosts()->delete();
            $project->executiveFinancialSummaries()->delete();
            $project->projectEntities()->delete();
            $project->transactions()->delete();
            $project->approvalRequests()->delete();
            $project->movementLogs()->delete();
            $project->documents()->delete();
            $project->projectApprovals()->delete();
            $project->qualityRecords()->delete();
            $project->activityHistory()->delete();
            $project->memoirs()->delete();
            $project->referrals()->delete();
            $project->draftCollaborators()->delete();
            $project->draftActivities()->delete();
            $project->achievements()->delete();
            // Many-to-many detachment
            $project->beneficiaryGroups()->detach();
        });
    }

    /**
     * Helper to bypass the DomainScope global scope (use with caution).
     * DomainScope is registered automatically via HasDomainScope trait.
     */
    public static function withoutVisibility(): Builder
    {
        return static::query();
    }

    protected array $visibilityCheckCache = [];

    /**
     * Check visibility for current user
     */
    public function checkVisibility($user = null): bool
    {
        $user = $user ?: auth()->user();

        if (! $user || ! $this->getKey()) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if ($user->role && $user->role->full_access) {
            return true;
        }

        $userId = $user->id;
        if (isset($this->visibilityCheckCache[$userId])) {
            return $this->visibilityCheckCache[$userId];
        }

        $query = static::withoutGlobalScopes()->where('projects.id', $this->getKey());
        app(ProjectService::class)->applyAccessibleProjectsQuery($query, $user);

        return $this->visibilityCheckCache[$userId] = $query->exists();
    }

    /**
     * Scope visibility
     */
    public function scopeCheckVisibility($query, $user = null)
    {
        $user = $user ?: auth()->user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        return app(ProjectService::class)->applyAccessibleProjectsQuery($query, $user);
    }

    /**
     * Scope: visibleToUser (compatibility wrapper)
     */
    public function scopeVisibleToUser($query, $module = null)
    {
        return $this->scopeCheckVisibility($query);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /**
     * Scope: forModule
     *
     * Compatibility alias used by ReportsController and other controllers.
     * Delegates to withVisibility() which applies the full geographic +
     * administrative visibility logic from HasProjectVisibility.
     *
     * NOTE: The $module parameter is accepted for API compatibility but is not
     * used in the query, since the Project model always scopes to 'projects'.
     *
     * @param  Builder  $query
     * @param  string|null  $module  (ignored)
     * @return Builder
     */
    public function scopeForModule($query, $module = null)
    {
        return $query->withVisibility(auth()->user());
    }
    // --- Relationships ---

}

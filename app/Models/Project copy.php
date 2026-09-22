<?php

namespace App\Models;

use App\Scopes\DomainScope;
use App\Services\ProjectNumberGenerator;
use App\Traits\Auditable;
use App\Traits\HasCreatorTracking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use Auditable, HasCreatorTracking, HasFactory;

    protected $fillable = [
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
        'completed_at',
        'internal_entity_id',
        'authority_id',
        'created_by',
        'creator_username',
        'creator_entity_id',
    ];

    protected $appends = ['project_duration', 'mainObjective'];

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
    ];

    /**
     * Scope for filtering projects by entity IDs (e.g., for reports or internal use)
     * This is not used for domain visibility anymore; kept for backward compatibility.
     */
    public function scopeForUserEntities($query, $entityIds)
    {
        $relations = [
            'supervisingAuthorities',
            'implementingEntities',
            'participatingEntities',
            'beneficiaryEntities',
        ];

        return $query->where(function ($q) use ($entityIds, $relations) {
            $q->whereIn('creator_entity_id', $entityIds)
                ->orWhereIn('internal_entity_id', $entityIds);
            foreach ($relations as $relation) {
                $q->orWhereHas($relation, function ($q2) use ($entityIds) {
                    $q2->whereIn('authority_id', $entityIds);
                });
            }
        });
    }

    // --- Accessors ---

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
        return $this->mainObjectives()->first();
    }

    /**
     * Get the name of the entity that created the project.
     * Handles both numeric IDs and string names stored in created_by_entity.
     */
    public function getCreatorEntityNameAttribute(): string
    {
        $entityId = $this->created_by_entity;

        if (is_numeric($entityId)) {
            static $entityNamesCache = [];

            if (! isset($entityNamesCache[$entityId])) {
                $entity = InternalEntity::select('name')->find($entityId);
                $entityNamesCache[$entityId] = $entity ? $entity->name : $entityId;
            }

            return (string) $entityNamesCache[$entityId];
        }

        if (empty($entityId) && $this->createdBy) {
            return $this->createdBy->department ?? '-';
        }

        return (string) ($entityId ?? '-');
    }

    // --- العلاقات BelongsTo ---

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
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
        return $this->hasMany(ProjectReferral::class, 'project_id');
    }

    // --- العلاقات BelongsToMany ---

    public function beneficiaryGroups(): BelongsToMany
    {
        return $this->belongsToMany(BeneficiaryGroup::class, 'beneficiary_group_project');
    }

    // --- Draft Validation Methods ---

    /**
     * Check if project can be reverted to draft status.
     */
    public function canBeRevertedToDraft(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (! $user || $user->id !== $this->created_by_user_id) {
            return false;
        }

        if ($this->status === 'draft') {
            return false;
        }

        // Check highest step reached (must be stage 2 or higher)
        $highestStep = $this->projectApprovals()->withTrashed()->max('step_order') ?? 0;
        if ($highestStep < 2) {
            return false;
        }

        // Check if project has been officially returned with status 'need_action' or 'rejected'
        $hasBeenReturned = $this->projectApprovals()
            ->whereIn('status', ['need_action', 'rejected'])
            ->exists();

        return (bool) $hasBeenReturned;
    }

    /**
     * Check if project has all required data for finalization
     */
    public function isDraftComplete(): bool
    {
        return $this->validateBasicInfo() &&
            $this->validateObjectives() &&
            $this->validateLocations() &&
            $this->validateStakeholders() &&
            $this->validateActivities() &&
            $this->validateFinancing();
    }

    /**
     * Get list of missing required fields
     */
    public function getMissingRequiredFields(): array
    {
        $missing = [];

        if (! $this->validateBasicInfo()) {
            $missing[] = 'البيانات الأساسية';
        }
        if (! $this->validateObjectives()) {
            $missing[] = 'الأهداف والنتائج';
        }
        if (! $this->validateLocations()) {
            $missing[] = 'المواقع';
        }
        if (! $this->validateStakeholders()) {
            $missing[] = 'الجهات ذات الصلة';
        }
        if (! $this->validateActivities()) {
            $missing[] = 'الأنشطة';
        }
        if (! $this->validateFinancing()) {
            $missing[] = 'التمويل والتكاليف';
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
            ! empty($this->start_date_gregorian) &&
            ! empty($this->end_date_gregorian);
    }

    /**
     * Validate objectives and results
     */
    public function validateObjectives(): bool
    {
        return $this->mainObjectives()->exists() &&
            $this->specialObjectives()->exists();
    }

    /**
     * Validate project locations
     */
    public function validateLocations(): bool
    {
        return $this->locations()->exists();
    }

    /**
     * Validate stakeholders
     */
    public function validateStakeholders(): bool
    {
        return $this->supervisingAuthorities()->exists() ||
            $this->implementingEntities()->exists();
    }

    /**
     * Validate activities
     */
    public function validateActivities(): bool
    {
        return $this->preliminaryActivities()->exists() ||
            $this->executiveActivities()->exists();
    }

    /**
     * Validate financing and costs
     */
    public function validateFinancing(): bool
    {
        return $this->financings()->exists() &&
            $this->cost()->exists();
    }

    // --- Boot Method with Global Scope Registration ---

    protected static function booted()
    {
        // ✅ Register DomainScope as the single source of truth for visibility filtering.
        // This scope implements geographic and administrative filtering based on user permissions.
        static::addGlobalScope(new DomainScope);

        // Event: Auto-generate project number when creating a new project
        static::creating(function ($project) {
            if ($project->form_number) {
                return;
            }
            $project->form_number = ProjectNumberGenerator::getNextProjectNumber();
        });
    }

    /**
     * Helper to temporarily bypass the DomainScope global scope.
     * Use with caution, typically for admin exports or system-level reports.
     */
    public static function withoutVisibility(): Builder
    {
        return static::withoutGlobalScope(DomainScope::class);
    }
}

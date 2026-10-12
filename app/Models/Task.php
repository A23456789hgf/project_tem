<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'value_chain_id',
        'chain_project_name',
        'chain_project_activity',
        'project_entities_id',
        'assigned_entity_id',
        'assigned_authority_id',
        'assigned_user_id',
        'assigned_organization_type',
        'assignment_type',
        'executive_activity_action_id',
        'title',
        'description',
        'status',
        'priority',
        'created_by',
        'start_date',
        'due_date',
        'completed_at',
        'is_within_activities',
        'activity_type',
        'activity_id',
        'procedure_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'is_within_activities' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function valueChain()
    {
        return $this->belongsTo(ValueChain::class);
    }

    public function projectEntity()
    {
        return $this->belongsTo(ProjectEntity::class, 'project_entities_id');
    }

    public function assignedEntity()
    {
        return $this->belongsTo(InternalEntity::class, 'assigned_entity_id');
    }

    public function assignedAuthority()
    {
        return $this->belongsTo(Authority::class, 'assigned_authority_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignedOrganization(): InternalEntity|Authority|null
    {
        return $this->assigned_organization_type === 'external'
            ? $this->assignedAuthority
            : $this->assignedEntity;
    }

    public function executiveAction()
    {
        return $this->belongsTo(ExecutiveActivityAction::class, 'executive_activity_action_id');
    }

    public function preliminaryActivity()
    {
        return $this->belongsTo(PreliminaryActivity::class, 'activity_id');
    }

    public function executiveActivity()
    {
        return $this->belongsTo(ExecutiveActivity::class, 'activity_id');
    }

    public function preliminaryProcedure()
    {
        return $this->belongsTo(PreliminaryProcedure::class, 'procedure_id');
    }

    public function getLinkedActivityNameAttribute(): ?string
    {
        if ($this->activity_type === 'preliminary') {
            return $this->preliminaryActivity?->name;
        } elseif ($this->activity_type === 'executive') {
            return $this->executiveActivity?->name;
        } elseif ($this->executiveAction) {
            return $this->executiveAction->executiveActivity?->name;
        }

        return null;
    }

    public function getLinkedProcedureNameAttribute(): ?string
    {
        if ($this->activity_type === 'preliminary') {
            return $this->preliminaryProcedure?->procedure_name;
        } elseif ($this->activity_type === 'executive') {
            return $this->executiveAction?->action;
        } elseif ($this->executiveAction) {
            return $this->executiveAction->action;
        }

        return null;
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function discussions()
    {
        return $this->hasMany(TaskDiscussion::class);
    }

    public function memos()
    {
        // Now memos are actually Correspondences linked to this task
        return $this->hasMany(Correspondence::class, 'task_id')->where('correspondence_type', 'memo');
    }

    public function documentNotes()
    {
        return $this->hasMany(TaskDocumentNote::class);
    }

    public function executionNotes()
    {
        return $this->hasMany(TaskExecutionNote::class);
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function activities()
    {
        return $this->hasMany(TaskActivity::class);
    }

    public function achievementReports()
    {
        return $this->hasMany(TaskAchievementReport::class);
    }

    public function isStandaloneGeneralTask(): bool
    {
        return $this->project_id === null
            && $this->value_chain_id === null
            && $this->assigned_entity_id === null
            && $this->assigned_authority_id === null
            && $this->assigned_user_id === null
            && ! $this->is_within_activities
            && $this->activity_type === null
            && $this->activity_id === null
            && $this->procedure_id === null
            && $this->executive_activity_action_id === null;
    }

    public function getOfficialExecutionProgressAttribute(): ?int
    {
        if (! $this->executive_activity_action_id) {
            return null;
        }

        if ($this->relationLoaded('executiveAction') && $this->executiveAction?->relationLoaded('executions')) {
            $progress = $this->executiveAction->executions
                ->where('project_id', $this->project_id)
                ->sum('completion_percentage');
        } else {
            $progress = ProjectExecution::where('project_id', $this->project_id)
                ->where('executive_activity_action_id', $this->executive_activity_action_id)
                ->sum('completion_percentage');
        }

        return (int) min(100, round((float) $progress));
    }

    /**
     * Check visibility for current user
     */
    public function checkVisibility($user = null): bool
    {
        $user = $user ?: auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // If it belongs to a project, delegate to project visibility
        if ($this->project_id) {
            $project = $this->project;

            return $project ? $project->checkVisibility($user) : false;
        }

        // Otherwise (general task), check if the user is the creator or assignee
        if ((int) $this->created_by === (int) $user->id) {
            return true;
        }

        if ($this->assignees()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($this->assigned_user_id && (int) $this->assigned_user_id === (int) $user->id) {
            return true;
        }

        if ($this->assigned_organization_type === 'external' && $this->assigned_authority_id) {
            $organization = $user->organization();

            return $organization['type'] === 'external'
                && $organization['id'] === (int) $this->assigned_authority_id;
        }

        // Or if the task is assigned to an entity within the user's scope
        if ($this->assigned_entity_id) {
            $userEntityIds = $user->getFilteredEntityIds();

            if (in_array((int) $this->assigned_entity_id, $userEntityIds)) {
                return true;
            }
        }

        return false;
    }
}

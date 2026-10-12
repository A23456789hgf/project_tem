<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\User;
use App\Services\OrganizationResolver;

class ProjectPolicy
{
    private OrganizationResolver $organizationResolver;

    public function __construct(?OrganizationResolver $organizationResolver = null)
    {
        $this->organizationResolver = $organizationResolver ?? new OrganizationResolver;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        if ($project->status === 'in_execution') {
            return $user->hasPermission('projects-implementation.view-details', $project) ||
                   $user->hasPermission('projects.view-details', $project);
        }

        return $user->hasPermission('projects.view-details', $project) ||
               $user->hasPermission('projects.view', $project);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('projects.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Lock rules:
     * - A project in pending_approval is locked for the creator once it has
     *   moved to a different entity's approval stage (current stage entity ≠
     *   the project's origin entity).
     * - Editing is re-enabled when the project is returned
     *   (status = rolled_back_for_review) to the creator's entity.
     * - Non-creator users can still edit if they belong to the current
     *   stage entity and hold the projects.edit permission.
     */
    public function update(User $user, Project $project): bool
    {
        // ── Lock during active approval transit ────────────────────────────
        if (in_array($project->status, ['pending_approval', 'internally_approved', 'final'])) {
            if ($user->isAdmin()) {
                return $user->hasPermission('projects.edit', $project);
            }

            // Allow the CURRENT stage responsible entity to edit if they have permission
            if (str_starts_with($project->current_stage, 'entity_')) {
                $stageEntityId = (int) str_replace('entity_', '', $project->current_stage);
                $allowedEntityIds = $project->getProjectAllowedEntityIds($user);

                if (in_array($stageEntityId, $allowedEntityIds) || in_array('all', $allowedEntityIds)) {
                    return $user->hasPermission('projects.edit', $project);
                }
            }

            return false;
        }

        // Admins bypass remaining entity restrictions
        if ($user->isAdmin()) {
            return $user->hasPermission('projects.edit', $project);
        }

        // ── Restrict drafts and returned projects to creating entity or current stage entity ─────────
        if (in_array($project->status, ['draft', 'completed_draft', 'rolled_back_for_review'])) {
            $originEntityId = $project->creator_entity_id ?? $project->internal_entity_id ?? null;
            $allowedEntityIds = $project->getProjectAllowedEntityIds($user);

            $currentResponsibleEntityId = $originEntityId;
            if ($project->status === 'rolled_back_for_review' && str_starts_with($project->current_stage, 'entity_')) {
                $currentResponsibleEntityId = (int) str_replace('entity_', '', $project->current_stage);
            }

            if ($project->created_by_user_id !== $user->id) {
                if ($currentResponsibleEntityId && ! in_array((int) $currentResponsibleEntityId, $allowedEntityIds, true) && ! in_array('all', $allowedEntityIds, true)) {
                    return false;
                }
            }
        }

        // ── Project returned for revision → creator can edit again ─────────
        // (rolled_back_for_review status is set by returnToPreviousStage when
        //  the stage drops back to the origin entity's level)
        // No extra restriction needed; fall through to permission check.

        // ── Projects in execution or other terminal states are read-only ───
        if (in_array($project->status, ['in_execution', 'rejected', 'cancelled'], true)) {
            return false;
        }

        return $user->hasPermission('projects.edit', $project);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        if ($project->project_type === 'old') {
            return false;
        }

        return $user->hasPermission('projects.delete', $project);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.edit', $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.delete', $project);
    }

    /**
     * Determine whether the user can export models.
     */
    public function export(User $user, $project = null): bool
    {
        return $user->hasPermission('projects.export', $project instanceof Project ? $project : null);
    }

    /**
     * Determine whether the user can review project drafts.
     */
    public function viewDraft(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    /**
     * Determine whether the user can resume a project draft.
     */
    public function resume(User $user, Project $project): bool
    {
        // Allow resuming in other stages if the user has permission to edit (which is already checked in update)
        // or if they are the creator/responsible entity.
        if (in_array($project->status, ['pending_approval', 'internally_approved', 'final'])) {
            // For active approval stages, just rely on the update logic which allows the current stage entity
            return $this->update($user, $project) || $user->hasPermission('projects.create', $project);
        }

        if (! in_array($project->status, ['draft', 'completed_draft', 'rolled_back_for_review'])) {
            return false;
        }

        $originEntityId = $project->creator_entity_id ?? $project->internal_entity_id ?? null;
        $allowedEntityIds = $project->getProjectAllowedEntityIds($user);

        $currentResponsibleEntityId = $originEntityId;
        if ($project->status === 'rolled_back_for_review' && str_starts_with($project->current_stage, 'entity_')) {
            $currentResponsibleEntityId = (int) str_replace('entity_', '', $project->current_stage);
        }

        if ($project->created_by_user_id !== $user->id) {
            if ($currentResponsibleEntityId && ! in_array((int) $currentResponsibleEntityId, $allowedEntityIds, true) && ! in_array('all', $allowedEntityIds, true)) {
                return false;
            }
        }

        return $user->hasPermission('projects.create', $project) ||
               $user->hasPermission('projects.edit', $project) ||
               $user->hasPermission('projects.view', $project);
    }

    /**
     * Determine whether the user can review a project.
     */
    public function review(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.review', $project);
    }

    /**
     * Determine whether the user can close the project draft and submit for approval.
     */
    public function closeDraft(User $user, Project $project): bool
    {
        if (! in_array($project->status, ['draft', 'completed_draft'], true) && ! $project->isDraft()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $isCreator = ($project->created_by_user_id === $user->id);

        if ($project->getOriginType() === 'external') {
            // External project: creator or member of the same authority.
            $originAuthorityId = $this->organizationResolver->resolveProject($project)['id'];
            $belongsToOriginAuthority = $this->organizationResolver->userBelongsTo($user, OrganizationResolver::EXTERNAL, $originAuthorityId);

            if (! $isCreator && ! $belongsToOriginAuthority) {
                return false;
            }
        } else {
            // Internal project: creator or member of the origin entity's hierarchy.
            $originEntityId = $project->getOriginEntityId() ?? $project->creator_entity_id ?? $project->internal_entity_id;
            $allowedEntityIds = $project->getProjectAllowedEntityIds($user);
            $belongsToOrigin = ($originEntityId && (in_array((int) $originEntityId, $allowedEntityIds, true) || in_array('all', $allowedEntityIds, true)));

            if (! $isCreator && ! $belongsToOrigin) {
                return false;
            }
        }

        return $user->hasPermission('projects.submit', $project) ||
               $user->hasPermission('projects.create', $project) ||
               $user->hasPermission('projects.edit', $project);
    }

    /**
     * Determine whether the user can approve a project stage.
     */
    public function approve(User $user, Project $project): bool
    {
        // Projects in terminal state or drafts cannot be approved
        if (in_array($project->status, ['draft', 'completed_draft', 'in_execution', 'rejected', 'cancelled'], true)) {
            return false;
        }

        // The creator of the project cannot approve their own project (unless admin)
        if ($project->created_by_user_id === $user->id && ! $user->isAdmin()) {
            return false;
        }

        // Check active step if available
        $activeStep = $project->projectApprovals()->where('is_active', true)->first()
            ?? ($project->current_stage ? $project->projectApprovals()->where('drop', $project->current_stage)->first() : null);

        if ($activeStep) {
            if (! $activeStep->is_active || $activeStep->status === 'locked' || $activeStep->status !== 'pending') {
                return false;
            }

            if (! $user->isAdmin()) {
                if (! $this->matchesApprovalStep($user, $activeStep)) {
                    return false;
                }
            }
        } elseif ($project->status === 'pending_approval' && ! $user->isAdmin()) {
            if (str_starts_with($project->current_stage ?? '', 'entity_')) {
                $stageEntityId = (int) str_replace('entity_', '', $project->current_stage);
                $allowedEntityIds = $project->getProjectAllowedEntityIds($user);

                // Only the entity currently responsible for the project can approve it
                if (! in_array($stageEntityId, $allowedEntityIds) && ! in_array('all', $allowedEntityIds)) {
                    return false;
                }
            }
        }

        return $user->hasPermission('approvals.approve', $project) ||
               $user->hasPermission('projects.approve', $project);
    }

    /**
     * Determine whether the user can reject a project stage.
     */
    public function reject(User $user, Project $project): bool
    {
        if (in_array($project->status, ['draft', 'completed_draft', 'in_execution', 'rejected', 'cancelled'], true)) {
            return false;
        }

        $activeStep = $project->projectApprovals()->where('is_active', true)->first()
            ?? ($project->current_stage ? $project->projectApprovals()->where('drop', $project->current_stage)->first() : null);

        if ($activeStep) {
            if (! $activeStep->is_active || $activeStep->status === 'locked' || $activeStep->status !== 'pending') {
                return false;
            }

            if (! $user->isAdmin()) {
                if (! $this->matchesApprovalStep($user, $activeStep)) {
                    return false;
                }
            }
        } elseif ($project->status === 'pending_approval' && ! $user->isAdmin()) {
            if (str_starts_with($project->current_stage ?? '', 'entity_')) {
                $stageEntityId = (int) str_replace('entity_', '', $project->current_stage);
                $allowedEntityIds = $project->getProjectAllowedEntityIds($user);

                if (! in_array($stageEntityId, $allowedEntityIds) && ! in_array('all', $allowedEntityIds)) {
                    return false;
                }
            }
        }

        return $user->hasPermission('approvals.reject', $project) ||
               $user->hasPermission('projects.reject', $project) ||
               $user->hasPermission('approvals.approve', $project) ||
               $user->hasPermission('projects.approve', $project);
    }

    /**
     * Determine whether the user can request action / completion on a project stage.
     */
    public function requestAction(User $user, Project $project): bool
    {
        if (in_array($project->status, ['draft', 'completed_draft', 'in_execution', 'rejected', 'cancelled'], true)) {
            return false;
        }

        $activeStep = $project->projectApprovals()->where('is_active', true)->first()
            ?? ($project->current_stage ? $project->projectApprovals()->where('drop', $project->current_stage)->first() : null);

        if ($activeStep) {
            if (! $activeStep->is_active || $activeStep->status === 'locked' || $activeStep->status !== 'pending') {
                return false;
            }

            if (! $user->isAdmin()) {
                if (! $this->matchesApprovalStep($user, $activeStep)) {
                    return false;
                }
            }
        } elseif ($project->status === 'pending_approval' && ! $user->isAdmin()) {
            if (str_starts_with($project->current_stage ?? '', 'entity_')) {
                $stageEntityId = (int) str_replace('entity_', '', $project->current_stage);
                $allowedEntityIds = $project->getProjectAllowedEntityIds($user);

                if (! in_array($stageEntityId, $allowedEntityIds) && ! in_array('all', $allowedEntityIds)) {
                    return false;
                }
            }
        }

        return $user->hasPermission('approvals.request-action', $project) ||
               $user->hasPermission('approvals.reject', $project) ||
               $user->hasPermission('approvals.approve', $project) ||
               $user->hasPermission('projects.approve', $project);
    }

    /**
     * Determine whether the user can refer a project for consultation.
     */
    public function refer(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasPermission('projects.refer', $project) ||
               $user->hasPermission('referrals.create', $project) ||
               $user->hasPermission('referrals.view', $project) ||
               $user->hasPermission('projects.review', $project) ||
               $user->hasPermission('approvals.approve', $project) ||
               $user->hasPermission('projects.approve', $project) ||
               $user->hasPermission('projects.view', $project);
    }

    /**
     * Determine whether the user can resubmit a project returned for review.
     */
    public function resubmit(User $user, Project $project): bool
    {
        if (! in_array($project->status, ['rolled_back_for_review'], true)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $isCreator = ($project->created_by_user_id === $user->id);

        if ($project->getOriginType() === 'external') {
            $originAuthorityId = $this->organizationResolver->resolveProject($project)['id'];
            $belongsToOriginAuthority = $this->organizationResolver->userBelongsTo($user, OrganizationResolver::EXTERNAL, $originAuthorityId);

            if (! $isCreator && ! $belongsToOriginAuthority) {
                return false;
            }
        } else {
            $originEntityId = $project->getOriginEntityId() ?? $project->creator_entity_id ?? $project->internal_entity_id;
            $allowedEntityIds = $project->getProjectAllowedEntityIds($user);
            $belongsToOrigin = ($originEntityId && (in_array((int) $originEntityId, $allowedEntityIds, true) || in_array('all', $allowedEntityIds, true)));

            if (! $isCreator && ! $belongsToOrigin) {
                return false;
            }
        }

        return $user->hasPermission('projects.create', $project) ||
               $user->hasPermission('projects.edit', $project) ||
               $user->hasPermission('projects.submit', $project);
    }

    /**
     * Determine whether the user can view any implementation projects or a specific one.
     */
    public function viewExecution(User $user, ?Project $project = null): bool
    {
        return $user->hasPermission('projects.execute', $project);
    }

    /**
     * Determine whether the user can create executions.
     */
    public function createExecution(User $user, ?Project $project = null): bool
    {
        return $user->hasPermission('execution.add', $project);
    }

    /**
     * Determine whether the user can update executions.
     */
    public function updateExecution(User $user, ?Project $project = null): bool
    {
        return $user->hasPermission('execution.edit', $project);
    }

    /**
     * Determine whether the user can delete executions.
     */
    public function deleteExecution(User $user, ?Project $project = null): bool
    {
        return $user->hasPermission('execution.delete', $project);
    }

    /**
     * Determine whether the user can view project execution details.
     */
    public function execute(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.execute', $project);
    }

    /**
     * Determine whether the user can view the project schedule.
     */
    public function viewSchedule(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.schedule', $project);
    }

    /**
     * Determine whether the user can perform financial review.
     */
    public function reviewFinancial(User $user, Project $project): bool
    {
        return $user->hasPermission('reviews.financial', $project);
    }

    /**
     * Determine whether the user can perform technical review.
     */
    public function reviewTechnical(User $user, Project $project): bool
    {
        return $user->hasPermission('reviews.technical', $project);
    }

    /**
     * Determine whether the user can print the project.
     */
    public function print(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.print', $project);
    }

    /**
     * Determine whether the user can view the project workflow.
     */
    public function viewWorkflow(User $user, Project $project): bool
    {
        return $user->hasPermission('stages.view', $project);
    }

    /**
     * Determine whether the user can import projects.
     */
    public function import(User $user): bool
    {
        return $user->hasPermission('projects.import');
    }

    /**
     * Determine whether the user can export to Excel.
     */
    public function exportExcel(User $user): bool
    {
        return $user->hasPermission('projects.export');
    }

    /**
     * Determine whether the user can export to PDF.
     */
    public function exportPdf(User $user): bool
    {
        return $user->hasPermission('projects.export');
    }

    /**
     * Determine whether the user can export to Pivot.
     */
    public function exportPivot(User $user): bool
    {
        return $user->hasPermission('projects.export');
    }

    /**
     * Determine whether the user can export comprehensive report.
     */
    public function exportComprehensive(User $user): bool
    {
        return $user->hasPermission('projects.export');
    }

    /**
     * Determine whether the user can sync to ERP.
     */
    public function sync(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.sync', $project);
    }

    /**
     * Determine whether the user can submit the project for approval.
     */
    public function submit(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.submit', $project);
    }

    /**
     * Determine whether the user can revert the project to draft.
     */
    public function revert(User $user, Project $project): bool
    {
        if (! $user->hasPermission('projects.revert', $project) || ! $project->canBeRevertedToDraft($user)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($project->created_by_user_id !== $user->id) {
            if ($project->getOriginType() === 'external') {
                $originAuthorityId = $project->getOriginAuthorityId();
                if (! $this->organizationResolver->userBelongsTo($user, OrganizationResolver::EXTERNAL, $originAuthorityId)) {
                    return false;
                }
            } else {
                $originEntityId = $project->getOriginEntityId();
                $allowedEntityIds = $project->getProjectAllowedEntityIds($user);
                if ($originEntityId && ! in_array((int) $originEntityId, $allowedEntityIds, true) && ! in_array('all', $allowedEntityIds, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine whether the user can finalize the project draft.
     */
    public function finalize(User $user, Project $project): bool
    {
        if (! in_array($project->status, ['draft', 'completed_draft', 'rolled_back_for_review'])) {
            return false;
        }

        return $user->hasPermission('projects.create', $project) ||
               $user->hasPermission('projects.edit', $project);
    }

    /**
     * Determine whether the user can search projects.
     */
    public function search(User $user, ?Project $project = null): bool
    {
        return $user->hasPermission('projects.search', $project);
    }

    /**
     * Determine whether the user can duplicate a project.
     */
    public function duplicate(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.duplicate', $project);
    }

    /**
     * Determine whether the user can export a project to Word.
     */
    public function exportWord(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.export-word', $project);
    }

    /**
     * Determine whether the user can complete project data (استكمال البيانات).
     */
    public function completeData(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.complete-data', $project);
    }

    /**
     * Determine whether the user can register project achievements (تسجيل إنجاز).
     */
    public function achievements(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.achievements', $project);
    }

    /**
     * Determine whether the user can edit the executing entity of a project.
     */
    public function editEntity(User $user, Project $project): bool
    {
        if ($project->project_type !== 'old' || $project->entity_modified) {
            return false;
        }

        // تحقق من الصلاحية الأساسية فقط دون التحقق من نطاق السجل
        // لأن المستخدم يرى المشروع بالفعل في قائمته (ضمن نطاقه)
        return $user->checkDatabasePermission('projects.edit-entity');
    }

    private function matchesApprovalStep(User $user, ProjectApproval $approval): bool
    {
        if ($approval->authority_id) {
            return $this->organizationResolver->userBelongsTo(
                $user,
                OrganizationResolver::EXTERNAL,
                (int) $approval->authority_id,
            );
        }

        if ($approval->entity_id) {
            return $this->organizationResolver->userBelongsTo(
                $user,
                OrganizationResolver::INTERNAL,
                (int) $approval->entity_id,
            );
        }

        return false;
    }
}

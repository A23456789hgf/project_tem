<?php

namespace App\Services;

use App\Enums\ApprovalStepStatus;
use App\Enums\ProjectStatus;
use App\Enums\ReturnTarget;
use App\Exceptions\InvalidWorkflowTransitionException;
use App\Exceptions\UnauthorizedWorkflowActionException;
use App\Exceptions\WorkflowValidationException;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectActivityHistory;
use App\Models\ProjectApproval;
use App\Models\ProjectReferral;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ApprovalService
{
    protected EntityHierarchyService $entityHierarchyService;

    public function __construct(EntityHierarchyService $entityHierarchyService)
    {
        $this->entityHierarchyService = $entityHierarchyService;
    }

    // =========================================================================
    // 1. DRAFT & CHAIN GENERATION (إغلاق المسودة وتوليد السلسلة)
    // =========================================================================

    /**
     * Close draft and generate dynamic approval chain from Creator Entity to Root.
     * Step 1 is set to ACTIVE (pending), all subsequent steps are LOCKED.
     *
     * @throws InvalidWorkflowTransitionException
     * @throws UnauthorizedWorkflowActionException
     */
    public function closeDraftAndGenerateApprovalChain(Project $project, User $user): Collection
    {
        // 1. Validate project is in draft state
        if (! $project->isDraft() && $project->status !== 'draft' && $project->status !== 'completed_draft') {
            throw new InvalidWorkflowTransitionException('المشروع ليس في حالة مسودة ليتم إغلاقها.');
        }

        // 2. Validate user belongs to creator entity/authority or is the creator or admin.
        $projectOriginType = $project->getOriginType();
        $originEntityId = $project->getOriginEntityId() ?? $user->entity_id;
        $originAuthorityId = $project->getOriginAuthorityId() ?? $user->authority_id;

        if (! $user->isAdmin()) {
            $isCreatorUser = ($project->created_by_user_id === $user->id);

            if ($projectOriginType === 'external') {
                // External project: user must be the creator OR belong to the same authority.
                $belongsToOriginAuthority = ($originAuthorityId && $user->authority_id === $originAuthorityId);

                if (! $isCreatorUser && ! $belongsToOriginAuthority) {
                    throw new UnauthorizedWorkflowActionException('فقط أعضاء الجهة الخارجية المنشئة للمشروع يمكنهم إغلاق المسودة وتقديم المشروع للاعتماد.');
                }
            } else {
                // Internal project: user must be the creator OR their allowed entities include the origin entity.
                $allowedEntityIds = $project->getProjectAllowedEntityIds($user);
                $belongsToOrigin = ($originEntityId && in_array((int) $originEntityId, $allowedEntityIds, true));

                if (! $isCreatorUser && ! $belongsToOrigin) {
                    throw new UnauthorizedWorkflowActionException('فقط أعضاء الجهة المنشئة للمشروع يمكنهم إغلاق المسودة وتقديم المشروع للاعتماد.');
                }
            }
        }

        return DB::transaction(function () use ($project, $user, $projectOriginType, $originEntityId) {
            // 3. Resolve dynamic stages hierarchy
            $stages = app(ApprovalChainBuilder::class)->buildChainForProject($project);

            if (empty($stages)) {
                throw new InvalidWorkflowTransitionException('تعذر توليد مسار الاعتمادات للجهة المنشئة. يرجى التحقق من شجرة الجهات.');
            }

            // 4. Remove any existing transient approval records for this project
            ProjectApproval::where('project_id', $project->id)->delete();

            $createdApprovals = collect();
            $firstStage = $stages[0];

            // 5. Create the approval chain steps
            foreach ($stages as $stageData) {
                $isFirst = ($stageData['order'] === 1);

                $responsibleUserId = $stageData['responsible_user_id'] ?? null;

                $approval = ProjectApproval::create([
                    'project_id' => $project->id,
                    'entity_id' => $stageData['entity_id'] ?? null,
                    'authority_id' => $stageData['authority_id'] ?? null,
                    'approver_scope' => $stageData['approver_scope'] ?? 'internal',
                    'drop' => $stageData['code'],
                    'phase' => $stageData['phase'] ?? null,
                    'step_order' => $stageData['order'],
                    'status' => $isFirst ? ApprovalStepStatus::Pending->value : ApprovalStepStatus::Locked->value,
                    'is_active' => $isFirst,
                    'created_by' => $user->id,
                    // Legacy hardcoded fields (kept null or unused for new dynamic workflow)
                    'financial_review_status' => null,
                    'technical_review_status' => null,
                    'technical_reviewer_id' => null,
                    'financial_reviewer_id' => null,
                    
                    // Unified Dynamic Fields
                    'assigned_user_id' => $responsibleUserId,
                    'stage_key' => $stageData['stage_key'] ?? null,
                    'stage_name' => $stageData['stage_name'] ?? null,
                    'available_actions' => isset($stageData['available_actions']) ? (is_array($stageData['available_actions']) ? json_encode($stageData['available_actions']) : $stageData['available_actions']) : null,
                ]);

                $createdApprovals->push($approval);
            }

            // 6. Update project state — maintain semantic separation between entity and authority.
            $projectUpdate = [
                'status' => ProjectStatus::PendingApproval->value,
                'approval_status' => 'pending',
                'current_stage' => $firstStage['code'],
                'current_stage_order' => 1,
                'finalized_at' => Carbon::now(),
            ];

            if ($projectOriginType === 'internal' && $originEntityId) {
                // Only stamp creator_entity_id for internal projects.
                // authority_id was already set during createProjectFromStep1 for external.
                $projectUpdate['creator_entity_id'] = $originEntityId;
            }

            $project->update($projectUpdate);

            // 7. Log in activity history
            $this->logActivity($project, 'finalized', [
                'user_id' => $user->id,
                'from_stage_name' => 'المسودة (Draft)',
                'from_stage_order' => 0,
                'to_stage_name' => $firstStage['name_ar'],
                'to_stage_order' => 1,
                'notes' => 'تم إغلاق المسودة وتوليد مسار الاعتمادات بنجاح.',
            ]);

            Log::info('Approval chain generated for project', [
                'project_id' => $project->id,
                'origin_type' => $projectOriginType,
                'origin_entity_id' => $originEntityId,
                'total_steps' => count($stages),
                'first_stage' => $firstStage['code'],
            ]);

            return $createdApprovals;
        });
    }

    // =========================================================================
    // 2. ACTIVE STEP RESOLUTION & AUTHORIZATION
    // =========================================================================

    /**
     * Get the single active approval step for the project
     */
    public function getActiveStep(Project $project): ?ProjectApproval
    {
        return $project->projectApprovals()
            ->where('is_active', true)
            ->first() ?? (
                $project->current_stage
                    ? $project->projectApprovals()->where('drop', $project->current_stage)->first()
                    : null
            );
    }

    /**
     * Check if a user is authorized to perform actions on a specific approval step
     */
    public function canUserActOnStep(User $user, ProjectApproval $step): bool
    {
        // 0. Administrator bypass: Admin can act on any step in any entity and phase
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // 1. Verify Scope and Organization Type matching
        if ($step->approver_scope === 'external') {
            if (! $user->isExternal()) {
                return false;
            }
            if ($step->authority_id && (int) $user->authority_id !== (int) $step->authority_id) {
                return false;
            }
        } else {
            if (! $user->isInternal()) {
                return false;
            }
            if ($step->entity_id && (int) $user->entity_id !== (int) $step->entity_id) {
                return false;
            }
        }

        $userEntityId = (int) $user->entity_id;
        $stepEntityId = (int) $step->entity_id;
        $userAuthorityId = (int) $user->authority_id;
        $stepAuthorityId = (int) $step->authority_id;

        // Check phase-specific permission if permissions table is populated
        $phaseEnum = $step->getPhaseEnum();
        if ($phaseEnum) {
            $requiredPermission = $phaseEnum->permissionSlug();
            $permissionExists = Schema::hasTable('permissions')
                && DB::table('permissions')->where('slug', $requiredPermission)->exists();

            if ($permissionExists && ! $user->hasPermission($requiredPermission)) {
                // Fallback check for alternate permission names
                $alternateMap = [
                    'approvals.technical-review' => ['reviews.technical', 'approvals.technical'],
                    'approvals.financial-review' => ['reviews.financial', 'approvals.financial'],
                    'approvals.approve' => ['projects.approve', 'approvals.stage-approve'],
                ];
                $alternates = $alternateMap[$requiredPermission] ?? [];
                $hasAlt = false;
                foreach ($alternates as $alt) {
                    if ($user->hasPermission($alt)) {
                        $hasAlt = true;
                        break;
                    }
                }
                if (! $hasAlt) {
                    return false;
                }
            }
        }
        // Unified dynamic stage logic with legacy fallback
        $assignedId = $step->assigned_user_id;
        
        if (!$assignedId) {
            if ($step->phase === 'technical_review') {
                $assignedId = $step->technical_reviewer_id ?? $step->technical_review_user_id;
            } elseif ($step->phase === 'financial_review') {
                $assignedId = $step->financial_reviewer_id ?? $step->financial_review_user_id;
            }
        }

        if ($assignedId) {
            if ((int) $assignedId !== (int) $user->id) {
                return false;
            }
        } else {
            if ($step->approver_scope === 'external') {
                if ($userAuthorityId !== $stepAuthorityId) {
                    return false;
                }
            } else {
                if ($userEntityId !== $stepEntityId) {
                    return false;
                }
            }
        }

        return true;
    }

    // =========================================================================
    // 3. APPROVE STEP & PROGRESSION (اعتماد الخطوة النشطة)
    // =========================================================================

    /**
     * Approve the current active step and progress to next step or finalize to in_execution
     *
     * @throws InvalidWorkflowTransitionException
     * @throws UnauthorizedWorkflowActionException
     */
    public function approveActiveStep(Project $project, User $user, ?string $notes = null, ?string $attachment = null): array
    {
        return DB::transaction(function () use ($project, $user, $notes, $attachment) {
            $activeStep = $this->getActiveStep($project);

            if (! $activeStep || ! $activeStep->isActive()) {
                throw new InvalidWorkflowTransitionException('لا يمكن تنفيذ الاعتماد؛ لا توجد مرحلة نشطة لهذا المشروع.');
            }

            if (! $this->canUserActOnStep($user, $activeStep)) {
                throw new UnauthorizedWorkflowActionException('ليس لديك الصلاحية لاعتماد هذه المرحلة.');
            }

            $timestamp = Carbon::now();
            $fromStageName = $activeStep->getResolvedStageName();
            $fromStageOrder = $activeStep->step_order;

            // 1. Mark current active step as completed
            $activeStep->update([
                'status' => ApprovalStepStatus::Approved->value,
                'is_active' => false,
                'is_completed' => true,
                'reviewed_by' => $user->id,
                'reviewed_at' => $timestamp,
                'notes' => $notes ?: 'تمت الموافقة بواسطة '.$user->name,
                'attachment' => $attachment,
                // إزالة تعيين المهمة بعد الإنجاز حتى تختفي من صندوق المستخدم
                'assigned_user_id' => null,
                'technical_reviewer_id' => null,
                'financial_reviewer_id' => null,
                'financial_review_status' => ($activeStep->phase === 'financial_review') ? 'approved' : $activeStep->financial_review_status,
                'technical_review_status' => ($activeStep->phase === 'technical_review') ? 'approved' : $activeStep->technical_review_status,
            ]);

            // 2. Find next step in chain
            $nextStep = ProjectApproval::where('project_id', $project->id)
                ->where('step_order', $fromStageOrder + 1)
                ->first();

            if ($nextStep) {
                // Activate next step
                $nextStep->update([
                    'status' => ApprovalStepStatus::Pending->value,
                    'is_active' => true,
                ]);

                $project->update([
                    'current_stage' => $nextStep->drop,
                    'current_stage_order' => $nextStep->step_order,
                    'status' => ProjectStatus::PendingApproval->value,
                ]);

                $this->logActivity($project, 'approved', [
                    'user_id' => $user->id,
                    'from_stage_name' => $fromStageName,
                    'from_stage_order' => $fromStageOrder,
                    'to_stage_name' => $nextStep->getResolvedStageName(),
                    'to_stage_order' => $nextStep->step_order,
                    'notes' => $notes ?: 'تمت الموافقة والانتقال للمرحلة التالية.',
                    'action_details' => $notes,
                ]);

                return [
                    'success' => true,
                    'is_final' => false,
                    'approved_step' => $activeStep,
                    'next_step' => $nextStep,
                    'project_status' => ProjectStatus::PendingApproval->value,
                    'message' => 'تمت الموافقة بنجاح والانتقال إلى: '.$nextStep->getResolvedStageName(),
                ];
            }

            // 3. No next step -> Root Stage Approval completed -> FINAL APPROVAL!

            // Validate constraint #10 before moving to in_execution
            $isInternal = $activeStep->approver_scope === 'internal';
            $isMinistryRoot = $activeStep->entity && ($activeStep->entity->is_ministry_root || (bool) $activeStep->is_ministry_root);
            $isApprovalPhase = $activeStep->phase === 'stage_approval' || $activeStep->phase === 'APPROVAL';

            if ($isInternal && $isMinistryRoot && $isApprovalPhase) {
                $project->update([
                    'status' => ProjectStatus::InExecution->value,
                    'approval_status' => 'approved',
                    'current_stage' => null,
                    'current_stage_order' => null,
                    'completed_at' => $timestamp,
                ]);

                $this->logActivity($project, 'approved_to_implementation', [
                    'user_id' => $user->id,
                    'from_stage_name' => $fromStageName,
                    'from_stage_order' => $fromStageOrder,
                    'to_stage_name' => 'مرحلة التنفيذ (In Execution)',
                    'to_stage_order' => $fromStageOrder + 1,
                    'notes' => 'اكتملت جميع مراحل الاعتماد بنجاح — تم نقل المشروع لمرحلة التنفيذ.',
                ]);

                // Safely trigger sync
                $this->safelySyncProjectOnExecution($project);

                return [
                    'success' => true,
                    'is_final' => true,
                    'approved_step' => $activeStep,
                    'next_step' => null,
                    'project_status' => ProjectStatus::InExecution->value,
                    'message' => 'تم اكتمال دورة الاعتمادات بالكامل ونقل المشروع إلى مرحلة التنفيذ.',
                ];
            } else {
                // If it doesn't meet the conditions, it's a configuration error since there are no more steps.
                throw new InvalidWorkflowTransitionException('فشل النقل لمرحلة التنفيذ: يجب أن تنتهي سلسلة الاعتمادات بمرحلة اعتماد نهائية (APPROVAL) من قبل وزارة جذرية (Ministry Root) تابعة للجهات الداخلية.');
            }
        });
    }

    // =========================================================================
    // 4. REJECT STEP (رفض المشروع)
    // =========================================================================

    /**
     * Reject the active step with mandatory reason
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     * @throws UnauthorizedWorkflowActionException
     */
    public function rejectActiveStep(Project $project, User $user, string $reason, ?string $attachment = null): ProjectApproval
    {
        $trimmedReason = trim($reason);
        if (empty($trimmedReason) || mb_strlen($trimmedReason) < 10) {
            throw new WorkflowValidationException('سبب الرفض إلزامي ويجب ألا يقل عن 10 أحرف.');
        }

        return DB::transaction(function () use ($project, $user, $trimmedReason, $attachment) {
            $activeStep = $this->getActiveStep($project);

            if (! $activeStep || ! $activeStep->isActive()) {
                throw new InvalidWorkflowTransitionException('لا يمكن تنفيذ الرفض؛ لا توجد مرحلة نشطة لهذا المشروع.');
            }

            if (! $this->canUserActOnStep($user, $activeStep)) {
                throw new UnauthorizedWorkflowActionException('ليس لديك الصلاحية لرفض المشروع في هذه المرحلة.');
            }

            $fromStageName = $activeStep->getResolvedStageName();
            $timestamp = Carbon::now();

            // Mark active step as rejected
            $activeStep->update([
                'status' => ApprovalStepStatus::Rejected->value,
                'is_active' => false,
                'rejection_reason' => $trimmedReason,
                'notes' => $trimmedReason,
                'reviewed_by' => $user->id,
                'reviewed_at' => $timestamp,
                'attachment' => $attachment,
                // إنهاء ارتباط المهمة بالمستخدم بعد الرفض
                'assigned_user_id' => null,
                'technical_reviewer_id' => null,
                'financial_reviewer_id' => null,
            ]);

            // Freeze future steps
            ProjectApproval::where('project_id', $project->id)
                ->where('step_order', '>', $activeStep->step_order)
                ->update([
                    'status' => ApprovalStepStatus::Locked->value,
                    'is_active' => false,
                ]);

            // Update project status
            $project->update([
                'status' => ProjectStatus::Rejected->value,
                'approval_status' => 'rejected',
            ]);

            // Log activity
            $this->logActivity($project, 'rejected', [
                'user_id' => $user->id,
                'from_stage_name' => $fromStageName,
                'from_stage_order' => $activeStep->step_order,
                'to_stage_name' => 'مرفوض',
                'notes' => $trimmedReason,
                'action_details' => $trimmedReason,
            ]);

            return $activeStep;
        });
    }

    /**
     * Reject the project (wrapper for rejectActiveStep).
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     * @throws UnauthorizedWorkflowActionException
     */
    public function rejectProject(Project $project, User $user, string $reason, ?string $attachment = null): ProjectApproval
    {
        return $this->rejectActiveStep($project, $user, $reason, $attachment);
    }

    // =========================================================================
    // 5. REQUEST COMPLETION (طلب استكمال / إرجاع)
    // =========================================================================

    /**
     * Request completion: either roll back to Creator Entity or to Previous Step
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     * @throws UnauthorizedWorkflowActionException
     */
    public function requestCompletion(Project $project, User $user, string $notes, ReturnTarget $target, ?string $attachment = null): array
    {
        $trimmedNotes = trim($notes);
        if (empty($trimmedNotes) || mb_strlen($trimmedNotes) < 10) {
            throw new WorkflowValidationException('ملاحظات طلب الاستكمال إلزامية ومفصلة (10 أحرف على الأقل).');
        }

        return DB::transaction(function () use ($project, $user, $trimmedNotes, $target, $attachment) {
            $activeStep = $this->getActiveStep($project);

            if (! $activeStep || ! $activeStep->isActive()) {
                throw new InvalidWorkflowTransitionException('لا يمكن طلب استكمال؛ لا توجد مرحلة نشطة لهذا المشروع.');
            }

            if (! $this->canUserActOnStep($user, $activeStep)) {
                throw new UnauthorizedWorkflowActionException('ليس لديك الصلاحية لطلب استكمال في هذه المرحلة.');
            }

            $timestamp = Carbon::now();
            $fromStageName = $activeStep->getResolvedStageName();
            $fromStageOrder = $activeStep->step_order;

            if ($target === ReturnTarget::CreatorEntity || $fromStageOrder <= 1) {
                // Mode A: Return to Creator Entity
                $activeStep->update([
                    'status' => ApprovalStepStatus::NeedAction->value,
                    'is_active' => false,
                    'return_target' => ReturnTarget::CreatorEntity->value,
                    'returned_to_step_order' => $fromStageOrder,
                    'notes' => $trimmedNotes,
                    'required_action' => $trimmedNotes,
                    'attachment' => $attachment,
                    'reviewed_by' => $user->id,
                    'reviewed_at' => $timestamp,
                    'assigned_user_id' => null,
                    'technical_reviewer_id' => null,
                    'financial_reviewer_id' => null,
                ]);

                $project->update([
                    'status' => ProjectStatus::RolledBackForReview->value,
                    'approval_status' => 'action_requested',
                ]);

                $this->logActivity($project, 'need_action', [
                    'user_id' => $user->id,
                    'from_stage_name' => $fromStageName,
                    'from_stage_order' => $fromStageOrder,
                    'to_stage_name' => 'الجهة المنشئة (المسودة)',
                    'to_stage_order' => 0,
                    'notes' => $trimmedNotes,
                    'action_details' => $trimmedNotes,
                ]);

                return [
                    'success' => true,
                    'target' => 'creator_entity',
                    'returned_step' => $activeStep,
                    'project_status' => ProjectStatus::RolledBackForReview->value,
                    'message' => 'تم طلب استكمال النواقص وإرجاع المشروع للجهة المنشئة لتعديله.',
                ];
            }

            // Mode B: Return to Previous Step
            $previousStep = ProjectApproval::where('project_id', $project->id)
                ->where('step_order', $fromStageOrder - 1)
                ->first();

            if (! $previousStep) {
                // Fallback to CreatorEntity
                return $this->requestCompletion($project, $user, $trimmedNotes, ReturnTarget::CreatorEntity, $attachment);
            }

            $activeStep->update([
                'status' => ApprovalStepStatus::Returned->value,
                'is_active' => false,
                'return_target' => ReturnTarget::PreviousStep->value,
                'returned_to_step_order' => $previousStep->step_order,
                'notes' => $trimmedNotes,
                'attachment' => $attachment,
                'reviewed_by' => $user->id,
                'reviewed_at' => $timestamp,
                'assigned_user_id' => null,
                'technical_reviewer_id' => null,
                'financial_reviewer_id' => null,
            ]);

            $previousStep->update([
                'status' => ApprovalStepStatus::Pending->value,
                'is_active' => true,
                'is_completed' => false,
            ]);

            $project->update([
                'current_stage' => $previousStep->drop,
                'current_stage_order' => $previousStep->step_order,
                'status' => ProjectStatus::PendingApproval->value,
            ]);

            $this->logActivity($project, 'returned', [
                'user_id' => $user->id,
                'from_stage_name' => $fromStageName,
                'from_stage_order' => $fromStageOrder,
                'to_stage_name' => $previousStep->getResolvedStageName(),
                'to_stage_order' => $previousStep->step_order,
                'notes' => $trimmedNotes,
                'action_details' => $trimmedNotes,
            ]);

            return [
                'success' => true,
                'target' => 'previous_step',
                'returned_step' => $activeStep,
                'active_step' => $previousStep,
                'project_status' => ProjectStatus::PendingApproval->value,
                'message' => 'تم إرجاع المشروع إلى المرحلة السابقة: '.$previousStep->getResolvedStageName(),
            ];
        });
    }

    // =========================================================================
    // 6. RESUBMIT PROJECT (إعادة التقديم بعد الاستكمال)
    // =========================================================================

    /**
     * Resubmit project from rolled_back_for_review back to the active step that requested action
     *
     * @throws InvalidWorkflowTransitionException
     * @throws UnauthorizedWorkflowActionException
     */
    public function resubmitProject(Project $project, User $user, ?string $notes = null, ?string $attachment = null): ProjectApproval
    {
        if ($project->status !== ProjectStatus::RolledBackForReview->value && $project->status !== 'rolled_back_for_review') {
            throw new InvalidWorkflowTransitionException('لا يمكن إعادة التقديم؛ المشروع ليس في حالة إعادة مراجعة واستكمال.');
        }

        // Validate creator authority
        if (! $user->isAdmin()) {
            $originEntityId = $project->getOriginEntityId();
            $allowedEntityIds = $project->getProjectAllowedEntityIds($user);
            $isCreatorUser = ($project->created_by_user_id === $user->id);
            $belongsToOrigin = ($originEntityId && in_array((int) $originEntityId, $allowedEntityIds, true));

            if (! $isCreatorUser && ! $belongsToOrigin) {
                throw new UnauthorizedWorkflowActionException('فقط أعضاء الجهة المنشئة للمشروع يمكنهم إعادة التقديم.');
            }
        }

        return DB::transaction(function () use ($project, $user, $notes, $attachment) {
            // Find the step that requested action
            $targetStep = ProjectApproval::where('project_id', $project->id)
                ->whereIn('status', [ApprovalStepStatus::NeedAction->value, 'requires_action', 'need_action'])
                ->latest('updated_at')
                ->first()
                ?? ProjectApproval::where('project_id', $project->id)
                    ->whereNotNull('returned_to_step_order')
                    ->latest('updated_at')
                    ->first()
                ?? ProjectApproval::where('project_id', $project->id)
                    ->orderBy('step_order')
                    ->first();

            if (! $targetStep) {
                throw new InvalidWorkflowTransitionException('تعذر العثور على مرحلة لإعادة التقديم إليها.');
            }

            // Reactivate target step
            $targetStep->update([
                'status' => ApprovalStepStatus::Pending->value,
                'is_active' => true,
                'notes' => $notes ?: 'تم استكمال المطلوب وإعادة تقديم المشروع.',
                'attachment' => $attachment ?: $targetStep->attachment,
            ]);

            // Update project
            $project->update([
                'status' => ProjectStatus::PendingApproval->value,
                'approval_status' => 'pending',
                'current_stage' => $targetStep->drop,
                'current_stage_order' => $targetStep->step_order,
            ]);

            // Log activity
            $this->logActivity($project, 'resubmitted', [
                'user_id' => $user->id,
                'from_stage_name' => 'الجهة المنشئة (المسودة)',
                'from_stage_order' => 0,
                'to_stage_name' => $targetStep->getResolvedStageName(),
                'to_stage_order' => $targetStep->step_order,
                'notes' => $notes ?: 'تمت إعادة تقديم المشروع بعد استكمال النواقص.',
            ]);

            return $targetStep;
        });
    }

    // =========================================================================
    // 7. CONSULTATION / REFERRAL (الاستشارة / الإحالة المستقلة)
    // =========================================================================

    /**
     * Record a consultation/referral without altering active step or approval chain
     */
    public function recordConsultation(Project $project, User $user, int $referredEntityId, string $referralText, ?array $attachments = null, ?int $referredUserId = null): ProjectReferral
    {
        return DB::transaction(function () use ($project, $user, $referredEntityId, $referralText, $attachments, $referredUserId) {
            $referral = ProjectReferral::create([
                'project_id' => $project->id,
                'drop' => $project->current_stage,
                'referring_entity_id' => $user->entity_id ?? $project->creator_entity_id,
                'referring_user_id' => $user->id,
                'referred_entity_id' => $referredEntityId,
                'referred_user_id' => $referredUserId,
                'referral_text' => $referralText,
                'referral_attachments' => $attachments,
                'status' => 'pending',
            ]);

            $referredEntity = InternalEntity::find($referredEntityId);
            $referredName = $referredEntity ? $referredEntity->name : "الجهة #{$referredEntityId}";

            $this->logActivity($project, 'referral', [
                'user_id' => $user->id,
                'from_stage_name' => $project->current_stage ?? 'مسار الاعتماد',
                'to_stage_name' => $referredName,
                'notes' => "تم طلب استشارة/إحالة إلى: {$referredName} (دون تغيير المرحلة النشطة).",
                'action_details' => $referralText,
            ]);

            return $referral;
        });
    }

    /**
     * Respond to a consultation/referral without altering active step or approval chain
     */
    public function respondToConsultation(
        ProjectReferral $referral,
        string $responseText,
        User $user,
        string $status = 'responded',
        ?array $attachments = null
    ): ProjectReferral {
        return DB::transaction(function () use ($referral, $responseText, $user, $status, $attachments) {
            $updateData = [
                'response_text' => $responseText,
                'responding_user_id' => $user->id,
                'responded_at' => now(),
                'status' => $status,
            ];

            if ($attachments !== null) {
                $updateData['response_attachments'] = $attachments;
            }

            $referral->update($updateData);

            $project = $referral->project;
            $statusLabel = $status === 'returned' ? 'إرجاع الاستشارة' : 'الرد على الاستشارة';
            $respondingEntityName = $referral->referredEntity?->name ?? 'الجهة المستشارة';

            if ($project) {
                $this->logActivity($project, 'referral_response', [
                    'user_id' => $user->id,
                    'from_stage_name' => $respondingEntityName,
                    'to_stage_name' => $referral->referringEntity?->name ?? 'الجهة الطالبة',
                    'notes' => "{$statusLabel} من قبل: {$respondingEntityName} (دون تغيير المرحلة النشطة).",
                    'action_details' => $responseText,
                    'metadata' => [
                        'referral_id' => $referral->id,
                        'status' => $status,
                        'attachments_count' => count($attachments ?? []),
                    ],
                ]);
            }

            return $referral->fresh(['referringEntity', 'referredEntity', 'referringUser', 'respondingUser']);
        });
    }

    /**
     * Close a consultation/referral
     */
    public function closeConsultation(ProjectReferral $referral, User $user, ?string $notes = null): ProjectReferral
    {
        return DB::transaction(function () use ($referral, $user, $notes) {
            $referral->update([
                'status' => 'closed',
            ]);

            $project = $referral->project;
            if ($project) {
                $this->logActivity($project, 'referral_closed', [
                    'user_id' => $user->id,
                    'notes' => $notes ?: 'تم إغلاق الاستشارة من قبل الجهة الطالبة.',
                    'metadata' => [
                        'referral_id' => $referral->id,
                    ],
                ]);
            }

            return $referral->fresh();
        });
    }

    // =========================================================================
    // 8. LOGGING & HELPERS
    // =========================================================================

    public function logActivity(Project $project, string $actionType, array $data = []): ProjectActivityHistory
    {
        $lastActivity = ProjectActivityHistory::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $metadata = $data['metadata'] ?? [];
        if ($lastActivity) {
            $metadata['elapsed_time'] = now()->diffForHumans($lastActivity->created_at);
            $metadata['time_seconds'] = now()->diffInSeconds($lastActivity->created_at);
        }

        $userId = Auth::id() ?? $data['user_id'] ?? $project->created_by_user_id ?? $project->created_by ?? 1;

        return ProjectActivityHistory::create([
            'project_id' => $project->id,
            'user_id' => $userId,
            'action_type' => $actionType,
            'from_stage_order' => $data['from_stage_order'] ?? null,
            'from_stage_name' => $data['from_stage_name'] ?? null,
            'to_stage_order' => $data['to_stage_order'] ?? null,
            'to_stage_name' => $data['to_stage_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'action_details' => $data['action_details'] ?? null,
            'metadata' => array_merge($metadata, [
                'performed_by' => $userId,
                'ip_address' => request()->ip(),
            ]),
        ]);
    }

    private function safelySyncProjectOnExecution(Project $project): void
    {
        try {
            if (class_exists(ProjectService::class)) {
                app(ProjectService::class)->syncProjectToEmpowermentDepartment($project);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to sync to empowerment on execution transition: '.$e->getMessage());
        }

        try {
            if (class_exists(FrappeAPIService::class)) {
                app(FrappeAPIService::class)->sendProjectOnExecution($project, true);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to sync to ERPNext on execution transition: '.$e->getMessage());
        }
    }

    // =========================================================================
    // 9. BACKWARD COMPATIBILITY METHODS
    // =========================================================================

    public function initializeRecursiveApprovals(Project $project): ?ProjectApproval
    {
        $user = auth()->user() ?? $project->createdBy;
        if (! $user) {
            return null;
        }

        try {
            $chain = $this->closeDraftAndGenerateApprovalChain($project, $user);

            return $chain->first();
        } catch (\Throwable $e) {
            Log::warning('initializeRecursiveApprovals fallback error: '.$e->getMessage());

            return null;
        }
    }

    public function initializeProjectWithStages(Project $project, ?Stage $stage = null): ?ProjectApproval
    {
        $user = auth()->user() ?? $project->createdBy;
        if (! $user) {
            return null;
        }

        try {
            $chain = $this->closeDraftAndGenerateApprovalChain($project, $user);

            return $chain->first();
        } catch (\Throwable $e) {
            Log::warning('initializeProjectWithStages fallback error: '.$e->getMessage());

            return null;
        }
    }

    public function progressToNextParentStage(ProjectApproval $currentApproval): ?ProjectApproval
    {
        $project = $currentApproval->project;
        $user = auth()->user() ?? User::find($currentApproval->created_by);
        if (! $project || ! $user) {
            return null;
        }

        try {
            $result = $this->approveActiveStep($project, $user);

            return $result['next_step'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('progressToNextParentStage error: '.$e->getMessage());

            return null;
        }
    }

    public function approveStage(
        ProjectApproval $approval,
        string $notes = '',
        ?string $attachment = null,
        ?int $userId = null
    ): ProjectApproval {
        $user = ($userId ? User::find($userId) : auth()->user()) ?? $approval->project?->createdBy;
        $project = $approval->project;

        if ($project && $user) {
            $this->approveActiveStep($project, $user, $notes, $attachment);
        }

        return $approval->fresh();
    }

    public function rejectStage(
        ProjectApproval $approval,
        string $reason = '',
        ?string $attachment = null,
        ?int $userId = null
    ): ProjectApproval {
        $user = ($userId ? User::find($userId) : auth()->user()) ?? $approval->project?->createdBy;
        $project = $approval->project;

        if ($project && $user) {
            $this->rejectActiveStep($project, $user, $reason ?: 'تم رفض المرحلة من قبل المستخدم', $attachment);
        }

        return $approval->fresh();
    }

    public function requestRevision(
        ProjectApproval $approval,
        string $notes = '',
        ?string $attachment = null,
        ?int $userId = null
    ): ProjectApproval {
        $user = ($userId ? User::find($userId) : auth()->user()) ?? $approval->project?->createdBy;
        $project = $approval->project;

        if ($project && $user) {
            $this->requestCompletion($project, $user, $notes ?: 'مطلوب استكمال النواقص والتعديلات.', ReturnTarget::CreatorEntity, $attachment);
        }

        return $approval->fresh();
    }

    public function splitToFinancialAndTechnicalReview(ProjectApproval $currentApproval): array
    {
        return [
            'success' => true,
            'approval' => $currentApproval,
        ];
    }

    public function submitReview(ProjectApproval $approval, string $type, ?string $notes = null, ?string $attachment = null): bool
    {
        $user = auth()->user() ?? User::find($approval->created_by);
        $project = $approval->project;

        if ($project && $user && $approval->isActive()) {
            $this->approveActiveStep($project, $user, $notes, $attachment);

            return true;
        }

        return false;
    }

    public function resetApproval(ProjectApproval $approval): ProjectApproval
    {
        $approval->update([
            'status' => ApprovalStepStatus::Pending->value,
            'is_active' => true,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'notes' => null,
        ]);

        return $approval;
    }

    public function getProjectApprovalSummary(Project $project): array
    {
        $approvals = $project->projectApprovals()->orderBy('step_order')->get();
        $total = $approvals->count();
        $approved = $approvals->where('status', ApprovalStepStatus::Approved->value)->count();

        return [
            'total_stages' => $total,
            'approved_stages' => $approved,
            'pending_stages' => $approvals->where('status', ApprovalStepStatus::Pending->value)->count(),
            'rejected_stages' => $approvals->where('status', ApprovalStepStatus::Rejected->value)->count(),
            'completion_percentage' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            'is_fully_approved' => $project->status === ProjectStatus::InExecution->value,
        ];
    }

    /**
     * Get the latest project approval state for list views.
     * Keeps main screens showing the current/latest state while details keep full history.
     */
    public function getLatestApprovalState(Project $project): ?ProjectApproval
    {
        return $project->projectApprovals()
            ->orderByDesc('updated_at')
            ->first();
    }

    public function getApprovalStages(Project $project): Collection
    {
        return $project->projectApprovals()->orderBy('step_order')->get();
    }

    public function createProjectApprovalStages(Project $project): Collection
    {
        return collect();
    }
}

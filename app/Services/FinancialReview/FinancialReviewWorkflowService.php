<?php

namespace App\Services\FinancialReview;

use App\Enums\ReturnTarget;
use App\Exceptions\InvalidWorkflowTransitionException;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\User;
use App\Services\ApprovalService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FinancialReviewWorkflowService
{
    public function __construct(
        protected ApprovalService $approvalService,
        protected FinancialReviewService $financialReviewService,
    ) {}

    /**
     * Approve the active Financial Review step.
     *
     * @throws ConflictHttpException
     */
    public function approve(
        Project $project,
        User $user,
        int $approvalId,
        ?string $notes = null,
        ?string $attachment = null,
        string $source = 'erpnext',
        ?string $externalUserId = null,
        ?string $externalUserName = null
    ): array {
        $this->ensureActiveFinancialReviewMatches($project, $approvalId);

        WorkflowActorContext::set($source, $externalUserId, $externalUserName);

        try {
            $result = $this->approvalService->approveActiveStep(
                $project,
                $user,
                $notes,
                $attachment,
                $approvalId
            );

            $project->refresh();
            $newActiveStep = $this->approvalService->getActiveStep($project);
            $stillRequires = $newActiveStep && $this->financialReviewService->isFinancialReviewApproval($newActiveStep);

            return [
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'completed_approval_id' => $approvalId,
                    'project_status' => $project->status,
                    'current_stage' => $project->current_stage,
                    'current_entity' => $newActiveStep ? [
                        'id' => $newActiveStep->entity_id ?? $newActiveStep->authority_id,
                        'name' => $newActiveStep->entity?->name
                            ?? $newActiveStep->authority?->agency_name
                            ?? $newActiveStep->authority?->name
                            ?? 'المركز الرئيسي',
                    ] : null,
                    'still_requires_financial_review' => $stillRequires,
                    'active_financial_review' => $stillRequires ? [
                        'approval_id' => $newActiveStep->id,
                        'stage_code' => $newActiveStep->drop,
                        'entity_id' => $newActiveStep->entity_id ?? $newActiveStep->authority_id,
                        'entity_name' => $newActiveStep->entity?->name
                            ?? $newActiveStep->authority?->agency_name
                            ?? $newActiveStep->authority?->name
                            ?? 'المركز الرئيسي',
                    ] : null,
                ],
            ];
        } catch (InvalidWorkflowTransitionException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        } finally {
            WorkflowActorContext::clear();
        }
    }

    /**
     * Reject the active Financial Review step.
     *
     * @throws ConflictHttpException
     */
    public function reject(
        Project $project,
        User $user,
        int $approvalId,
        string $reason,
        ?string $attachment = null,
        string $source = 'erpnext',
        ?string $externalUserId = null,
        ?string $externalUserName = null
    ): array {
        $this->ensureActiveFinancialReviewMatches($project, $approvalId);

        WorkflowActorContext::set($source, $externalUserId, $externalUserName);

        try {
            $rejectedStep = $this->approvalService->rejectActiveStep(
                $project,
                $user,
                $reason,
                $attachment,
                $approvalId
            );

            $project->refresh();

            return [
                'success' => true,
                'message' => 'تم رفض المشروع وإيقاف مسار الاعتماد بنجاح.',
                'data' => [
                    'completed_approval_id' => $approvalId,
                    'project_status' => $project->status,
                    'current_stage' => $project->current_stage,
                    'still_requires_financial_review' => false,
                ],
            ];
        } catch (InvalidWorkflowTransitionException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        } finally {
            WorkflowActorContext::clear();
        }
    }

    /**
     * Return / Roll back the active Financial Review step.
     *
     * @throws ConflictHttpException
     */
    public function return(
        Project $project,
        User $user,
        int $approvalId,
        string $notes,
        ReturnTarget $target,
        ?string $attachment = null,
        string $source = 'erpnext',
        ?string $externalUserId = null,
        ?string $externalUserName = null
    ): array {
        $this->ensureActiveFinancialReviewMatches($project, $approvalId);

        WorkflowActorContext::set($source, $externalUserId, $externalUserName);

        try {
            $result = $this->approvalService->requestCompletion(
                $project,
                $user,
                $notes,
                $target,
                $attachment,
                $approvalId
            );

            $project->refresh();
            $newActiveStep = $this->approvalService->getActiveStep($project);
            $stillRequires = $newActiveStep && $this->financialReviewService->isFinancialReviewApproval($newActiveStep);

            return [
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'completed_approval_id' => $approvalId,
                    'target' => $result['target'],
                    'project_status' => $project->status,
                    'current_stage' => $project->current_stage,
                    'still_requires_financial_review' => $stillRequires,
                    'active_financial_review' => $stillRequires ? [
                        'approval_id' => $newActiveStep->id,
                        'stage_code' => $newActiveStep->drop,
                        'entity_id' => $newActiveStep->entity_id ?? $newActiveStep->authority_id,
                        'entity_name' => $newActiveStep->entity?->name
                            ?? $newActiveStep->authority?->agency_name
                            ?? $newActiveStep->authority?->name
                            ?? 'المركز الرئيسي',
                    ] : null,
                ],
            ];
        } catch (InvalidWorkflowTransitionException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        } finally {
            WorkflowActorContext::clear();
        }
    }

    /**
     * Guard: Ensure that the project has an active Financial Review step matching the given approvalId.
     *
     * @throws ConflictHttpException
     */
    public function ensureActiveFinancialReviewMatches(Project $project, int $approvalId): ProjectApproval
    {
        $activeStep = $this->approvalService->getActiveStep($project);

        if (! $activeStep || ! $activeStep->isActive()) {
            throw new ConflictHttpException('المشروع ليس في مرحلة مراجعة مالية نشطة حالياً.');
        }

        if ((int) $activeStep->id !== (int) $approvalId) {
            throw new ConflictHttpException("المرحلة المحددة (ID: {$approvalId}) لم تعد المرحلة النشطة للمشروع (المرحلة النشطة الحالية: {$activeStep->id}).");
        }

        if (! $this->financialReviewService->isFinancialReviewApproval($activeStep)) {
            throw new ConflictHttpException('المرحلة النشطة الحالية ليست مرحلة مراجعة مالية.');
        }

        return $activeStep;
    }
}

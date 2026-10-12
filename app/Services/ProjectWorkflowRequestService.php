<?php

namespace App\Services;

use App\Models\PreliminaryProcedureExecution;
use App\Models\Project;
use App\Models\ProjectActivityHistory;
use App\Models\ProjectApproval;
use App\Models\ProjectClearanceRequest;
use App\Models\ProjectDisbursementPlanDetail;
use App\Models\ProjectExecution;
use App\Models\ProjectPaymentRequest;
use App\Models\ProjectUnifiedRequest;
use App\Models\ProjectWorkflowRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectWorkflowRequestService
{
    public function __construct(
        private ApprovalService $approvalService,
        private ApprovalChainBuilder $approvalChainBuilder,
        private ProjectFinancialWorkflowService $financialWorkflowService,
    ) {}

    public function submit(Project $project, string $type, array $payload, User $user, ?Model $requestable = null): ProjectWorkflowRequest
    {
        return DB::transaction(function () use ($project, $type, $payload, $user, $requestable): ProjectWorkflowRequest {
            Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
            $existing = ProjectWorkflowRequest::query()
                ->where('project_id', $project->id)
                ->where('request_type', $type)
                ->where('status', 'pending')
                ->where('payload->dedupe_key', $payload['dedupe_key'] ?? null)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages(['request' => 'A matching request is already awaiting approval.']);
            }

            if ($type === 'next_installment') {
                $detailId = $payload['detail_id'] ?? null;
                $detail = $detailId ? ProjectDisbursementPlanDetail::find($detailId) : null;
                if (! $detail || (int) $detail->plan?->project_id !== (int) $project->id) {
                    throw ValidationException::withMessages(['detail_id' => 'Invalid installment detail specified for this project.']);
                }
                if ($detail->sequence_number <= 1) {
                    throw ValidationException::withMessages(['detail_id' => 'The first installment is disbursed upon plan approval and does not require a request.']);
                }
                if ($detail->status !== 'planned') {
                    throw ValidationException::withMessages(['detail_id' => "The installment status ({$detail->status}) does not permit a payment request."]);
                }
                $prevDetail = $detail->plan->details()->where('sequence_number', $detail->sequence_number - 1)->first();
                if (! $prevDetail || $prevDetail->status !== 'cleared') {
                    throw ValidationException::withMessages(['detail_id' => 'The preceding installment must be fully cleared before requesting the next installment.']);
                }
            } elseif ($type === 'project_closure') {
                if ($project->uses_disbursement_workflow) {
                    $plan = $project->disbursementPlan;
                    if (! $plan) {
                        throw ValidationException::withMessages(['project' => 'Project does not have a disbursement plan.']);
                    }
                    $uncleared = $plan->details()->where('status', '!=', 'cleared')->count();
                    if ($uncleared > 0) {
                        throw ValidationException::withMessages(['project' => "Project has {$uncleared} uncleared installment(s). All installments must be cleared before requesting project closure."]);
                    }
                }
            }

            $stages = $this->approvalChainBuilder->buildChainForProject($project);
            if ($stages === []) {
                throw ValidationException::withMessages(['approval' => 'No configured approval chain is available for this project.']);
            }

            $request = ProjectWorkflowRequest::create([
                'project_id' => $project->id,
                'request_type' => $type,
                'status' => 'pending',
                'request_number' => strtoupper(Str::replace('_', '-', $type)).'-P'.$project->id.'-'.strtoupper(Str::random(6)),
                'payload' => $payload,
                'requestable_type' => $requestable?->getMorphClass(),
                'requestable_id' => $requestable?->getKey(),
                'submitted_by' => $user->id,
            ]);

            foreach ($stages as $stage) {
                $isFirst = (int) $stage['order'] === 1;
                ProjectApproval::create([
                    'project_id' => $project->id,
                    'workflow_request_id' => $request->id,
                    'entity_id' => $stage['entity_id'] ?? null,
                    'authority_id' => $stage['authority_id'] ?? null,
                    'approver_scope' => $stage['approver_scope'] ?? 'internal',
                    'drop' => $stage['code'],
                    'phase' => $stage['phase'] ?? null,
                    'step_order' => $stage['order'],
                    'status' => $isFirst ? 'pending' : 'locked',
                    'is_active' => $isFirst,
                    'created_by' => $user->id,
                    'assigned_user_id' => $stage['responsible_user_id'] ?? null,
                    'stage_key' => $stage['stage_key'] ?? null,
                    'stage_name' => $stage['stage_name'] ?? null,
                    'available_actions' => $stage['available_actions'] ?? null,
                ]);
            }

            ProjectActivityHistory::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'action_type' => 'financial_workflow_submitted',
                'notes' => "Submitted {$type} {$request->request_number}.",
                'metadata' => ['workflow_request_id' => $request->id, 'request_type' => $type],
            ]);

            return $request->load('approvalSteps');
        });
    }

    public function decide(ProjectWorkflowRequest $workflowRequest, User $user, string $decision, ?string $notes = null): ProjectWorkflowRequest
    {
        $decidedRequest = DB::transaction(function () use ($workflowRequest, $user, $decision, $notes): ProjectWorkflowRequest {
            $request = ProjectWorkflowRequest::whereKey($workflowRequest->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'This request has already been decided.']);
            }

            $step = $request->approvalSteps()->where('is_active', true)->where('status', 'pending')->lockForUpdate()->first();
            if (! $step || ! $this->approvalService->canUserActOnStep($user, $step, $decision)) {
                abort(403);
            }
            if (! $step->hasAction($decision)) {
                throw ValidationException::withMessages(['decision' => 'This action is not available at the current approval stage.']);
            }

            $step->update(['status' => $decision === 'approve' ? 'approved' : 'rejected', 'is_active' => false, 'reviewed_by' => $user->id, 'reviewed_at' => now(), 'notes' => $notes]);
            if ($decision === 'reject') {
                $request->update(['status' => 'rejected', 'decided_by' => $user->id, 'decided_at' => now(), 'decision_notes' => $notes]);
                if ($request->request_type === 'clearance_request') {
                    $clearanceId = $request->payload['clearance_request_id'] ?? $request->requestable_id;
                    $clearance = $clearanceId ? ProjectClearanceRequest::find($clearanceId) : null;
                    if ($clearance && $clearance->status === 'pending') {
                        $this->financialWorkflowService->rejectClearanceRequest($clearance, $user, $notes ?: 'Clearance request was rejected in Approval Center.');
                    }
                } elseif ($request->request_type === 'next_installment') {
                    $detailId = $request->payload['detail_id'] ?? null;
                    $detail = $detailId ? ProjectDisbursementPlanDetail::find($detailId) : null;
                    if ($detail && in_array($detail->status, ['payment_requested', 'planned'], true)) {
                        $detail->update(['status' => 'planned']);
                    }
                    if ($request->requestable instanceof ProjectPaymentRequest && $request->requestable->status === 'pending') {
                        $this->financialWorkflowService->rejectPaymentRequest($request->requestable, $user, $notes ?: 'Payment request rejected in Approval Center.');
                    }
                }

                $unifiedRequestId = $request->payload['unified_request_id'] ?? ($request->requestable_type === 'App\\Models\\ProjectUnifiedRequest' ? $request->requestable_id : null);
                if ($unifiedRequestId) {
                    $unifiedRequest = ProjectUnifiedRequest::find($unifiedRequestId);
                    if ($unifiedRequest && $unifiedRequest->status === 'pending') {
                        app(ProjectUnifiedRequestService::class)->rejectRequest($unifiedRequest, $user, $notes ?: 'تم رفض الطلب في مركز الاعتمادات.');
                    }
                }
            } else {
                $nextStep = $request->approvalSteps()->where('step_order', '>', $step->step_order)->orderBy('step_order')->lockForUpdate()->first();
                if ($nextStep) {
                    $nextStep->update(['status' => 'pending', 'is_active' => true]);
                } else {
                    $this->completeRequest($request, $user, $notes);
                }
            }

            ProjectActivityHistory::create([
                'project_id' => $request->project_id,
                'user_id' => $user->id,
                'action_type' => 'financial_workflow_'.$decision,
                'notes' => trim(($decision === 'approve' ? 'Approved ' : 'Rejected ').$request->request_number.' '.($notes ?? '')),
                'metadata' => ['workflow_request_id' => $request->id, 'approval_step_id' => $step->id],
            ]);

            return $request->fresh(['approvalSteps', 'project']);
        });

        if ($decision === 'approve' && $decidedRequest->request_type === 'next_installment' && $decidedRequest->status === 'approved') {
            $paymentRequest = $decidedRequest->requestable;
            if ($paymentRequest instanceof ProjectPaymentRequest && $paymentRequest->status === 'pending') {
                $result = $this->financialWorkflowService->approvePaymentRequest($paymentRequest, $user, $notes);
                if (! ($result['disbursement']['success'] ?? false)) {
                    $decidedRequest->update(['status' => 'disbursement_failed']);
                }
            }
        }

        return $decidedRequest->fresh(['approvalSteps', 'project', 'requestable']);
    }

    private function completeRequest(ProjectWorkflowRequest $request, User $user, ?string $notes): void
    {
        $payload = $request->payload ?? [];
        $project = $request->project;
        $request->update(['status' => 'approved', 'decided_by' => $user->id, 'decided_at' => now(), 'decision_notes' => $notes]);

        if ($request->request_type === 'financial_justification') {
            $common = [
                'project_id' => $project->id,
                'disbursement_plan_detail_id' => $payload['disbursement_plan_detail_id'],
                'created_by_user_id' => $request->submitted_by,
                'sequence' => (($payload['execution_type'] ?? null) === 'executive'
                    ? (ProjectExecution::where('executive_activity_action_id', $payload['executive_activity_action_id'])->max('sequence') ?? 0)
                    : (PreliminaryProcedureExecution::where('preliminary_procedure_id', $payload['preliminary_procedure_id'])->max('sequence') ?? 0)) + 1,
                'actual_start_date_gregorian' => $payload['actual_start_date_gregorian'] ?? null,
                'actual_start_date_hijri' => $payload['actual_start_date_hijri'] ?? null,
                'actual_finish_date_gregorian' => $payload['actual_finish_date_gregorian'] ?? null,
                'actual_finish_date_hijri' => $payload['actual_finish_date_hijri'] ?? null,
                'actual_amount' => $payload['actual_amount'] ?? 0,
                'amount_spent' => $payload['amount_spent'],
                'remaining_amount' => $payload['remaining_amount'] ?? 0,
                'status' => $payload['status'],
                'completion_percentage' => $payload['completion_percentage'] ?? 0,
                'notes' => $payload['notes'] ?? null,
                'approval_status' => 'pending',
                'workflow_review_status' => 'awaiting_manager_review',
            ];
            if (($payload['execution_type'] ?? null) === 'executive') {
                $execution = ProjectExecution::create($common + ['executive_activity_action_id' => $payload['executive_activity_action_id']]);
            } else {
                $execution = PreliminaryProcedureExecution::create($common + ['preliminary_procedure_id' => $payload['preliminary_procedure_id']]);
            }
            $request->update(['requestable_type' => $execution->getMorphClass(), 'requestable_id' => $execution->id]);
        } elseif ($request->request_type === 'clearance_request') {
            $clearanceRequest = ProjectClearanceRequest::findOrFail($payload['clearance_request_id']);
            $this->financialWorkflowService->approveClearanceRequest($clearanceRequest, $user, $notes, true);
        } elseif ($request->request_type === 'next_installment') {
            $detail = ProjectDisbursementPlanDetail::findOrFail($payload['detail_id']);
            $paymentRequest = $this->financialWorkflowService->submitPaymentRequest($project, $detail, ['notes' => $payload['notes'] ?? null], User::findOrFail($request->submitted_by));
            $request->update(['requestable_type' => $paymentRequest->getMorphClass(), 'requestable_id' => $paymentRequest->id]);
        } elseif ($request->request_type === 'project_closure') {
            if ($project->uses_disbursement_workflow) {
                $plan = $project->disbursementPlan;
                $uncleared = $plan ? $plan->details()->where('status', '!=', 'cleared')->count() : 1;
                if ($uncleared > 0) {
                    throw ValidationException::withMessages(['project' => 'Cannot close project: uncleared installments remain.']);
                }
            }
            $project->update(['status' => 'completed']);
        }

        $unifiedRequestId = $payload['unified_request_id'] ?? ($request->requestable_type === 'App\\Models\\ProjectUnifiedRequest' ? $request->requestable_id : null);
        if ($unifiedRequestId) {
            $unifiedRequest = ProjectUnifiedRequest::find($unifiedRequestId);
            if ($unifiedRequest && $unifiedRequest->status === 'pending') {
                app(ProjectUnifiedRequestService::class)->approveRequest($unifiedRequest, $user, $notes);
            }
        }
    }
}

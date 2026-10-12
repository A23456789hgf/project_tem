<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Models\ProjectWorkflowRequest;
use App\Services\ApprovalService;
use App\Services\ProjectWorkflowRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectWorkflowRequestController extends Controller
{
    public function __construct(private ProjectWorkflowRequestService $workflowRequestService, private ApprovalService $approvalService) {}

    public function show(ProjectWorkflowRequest $workflowRequest): View
    {
        $workflowRequest->load(['project', 'submitter', 'approvalSteps.entity', 'approvalSteps.authority', 'approvalSteps.assignedUser', 'decider']);
        $activeStep = $workflowRequest->approvalSteps->firstWhere('is_active', true);
        abort_unless(auth()->user()->isAdmin() || ($activeStep && $this->approvalService->canUserActOnStep(auth()->user(), $activeStep, 'read')) || (int) $workflowRequest->submitted_by === (int) auth()->id(), 403);
        $canApprove = $workflowRequest->status === 'pending' && $activeStep && $this->approvalService->canUserActOnStep(auth()->user(), $activeStep, 'approve');
        $canReject = $workflowRequest->status === 'pending' && $activeStep && $this->approvalService->canUserActOnStep(auth()->user(), $activeStep, 'reject');

        return view('approvals.workflow-request', compact('workflowRequest', 'activeStep', 'canApprove', 'canReject'));
    }

    public function approve(Request $request, ProjectWorkflowRequest $workflowRequest): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:4000']]);
        $this->workflowRequestService->decide($workflowRequest, $request->user(), 'approve', $data['notes'] ?? null);

        return redirect()->route('approvals.workflow-requests.show', $workflowRequest)->with('success', 'The request was approved.');
    }

    public function reject(Request $request, ProjectWorkflowRequest $workflowRequest): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'min:5', 'max:4000']]);
        $this->workflowRequestService->decide($workflowRequest, $request->user(), 'reject', $data['notes']);

        return redirect()->route('approvals.workflow-requests.show', $workflowRequest)->with('success', 'The request was rejected.');
    }
}

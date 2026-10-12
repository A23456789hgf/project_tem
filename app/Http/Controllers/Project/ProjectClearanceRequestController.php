<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Http\Requests\Project\StoreProjectClearanceRequestRequest;
use App\Models\PreliminaryProcedureExecution;
use App\Models\Project;
use App\Models\ProjectClearanceRequest;
use App\Models\ProjectDisbursementPlanDetail;
use App\Models\ProjectExecution;
use App\Models\ProjectWorkflowRequest;
use App\Services\ProjectFinancialWorkflowService;
use Illuminate\Http\Request;

class ProjectClearanceRequestController extends Controller
{
    public function __construct(
        protected ProjectFinancialWorkflowService $workflowService,
        protected ProjectService $projectService
    ) {}

    /**
     * List all clearance requests across all projects (global management page).
     */
    public function indexAll(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('clearances.view'), 403);

        $query = ProjectClearanceRequest::query()
            ->whereHas('project', fn ($q) => $q->visibleToUser('projects'))
            ->with(['project', 'installment', 'submittedBy', 'approvedBy'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->whereHas('project', fn ($q) => $q
                ->where('project_name', 'like', "%{$search}%")
                ->orWhere('form_number', 'like', "%{$search}%")
            );
        }

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        $executionProjects = Project::visibleToUser('projects')
            ->whereHas('disbursementPlan', function ($q) {
                $q->whereIn('status', ['approved', 'completed']);
            })
            ->with(['disbursementPlan.details'])
            ->select('id', 'project_name', 'form_number', 'status')
            ->orderBy('project_name')
            ->get();

        $clearanceRequests = $query->paginate(20)->withQueryString();

        return view('projects.disbursement_plans.clearances.index_all', compact('clearanceRequests', 'stats', 'executionProjects'));
    }

    public function index(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('clearances.view', $project), 403);

        $clearanceRequests = $project->clearanceRequests()
            ->with(['installment', 'submittedBy', 'approvedBy', 'items'])
            ->orderByDesc('id')
            ->get();

        return view('projects.disbursement_plans.clearances.index', compact('project', 'clearanceRequests'));
    }

    public function create(Project $project, ProjectDisbursementPlanDetail $detail)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('clearances.create', $project), 403);

        // Fetch un-cleared executions for this project
        $availableProjectExecutions = ProjectExecution::where('project_id', $project->id)
            ->whereNull('clearance_request_id')
            ->with(['action'])
            ->get();

        $availablePreliminaryExecutions = PreliminaryProcedureExecution::where('project_id', $project->id)
            ->whereNull('clearance_request_id')
            ->with(['procedure'])
            ->get();

        $arabicDisbursed = $this->workflowService->toArabicWords($detail->disbursed_amount);

        return view('projects.disbursement_plans.clearances.create', compact(
            'project',
            'detail',
            'availableProjectExecutions',
            'availablePreliminaryExecutions',
            'arabicDisbursed'
        ));
    }

    public function store(StoreProjectClearanceRequestRequest $request, Project $project, ProjectDisbursementPlanDetail $detail)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('clearances.create', $project), 403);

        try {
            $data = $request->validated();

            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store("projects/{$project->id}/clearances", 'public');
                $data['attachment_path'] = $path;
            }

            $clearanceRequest = $this->workflowService->submitClearanceRequest($project, $detail, $data, auth()->user());

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with('success', 'تم تقديم طلب إخلاء الدفعة رقم '.$detail->sequence_number.' بنجاح وهو قيد المراجعة والاعتماد.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Project $project, ProjectClearanceRequest $clearanceRequest)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('clearances.view', $project), 403);

        $clearanceRequest->load(['installment', 'submittedBy', 'approvedBy', 'items']);
        $arabicAmount = $this->workflowService->toArabicWords($clearanceRequest->total_cleared_amount);

        return view('projects.disbursement_plans.clearances.show', compact('project', 'clearanceRequest', 'arabicAmount'));
    }

    public function approve(Request $request, Project $project, ProjectClearanceRequest $clearanceRequest)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('clearances.approve', $project), 403);

        if (ProjectWorkflowRequest::where('requestable_type', $clearanceRequest->getMorphClass())
            ->where('requestable_id', $clearanceRequest->id)->where('request_type', 'clearance_request')->where('status', 'pending')->exists()) {
            return redirect()->route('approvals.index')->with('error', 'This clearance request must be decided in the Approval Center.');
        }

        try {
            $this->workflowService->approveClearanceRequest($clearanceRequest, auth()->user());

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with('success', 'تم اعتماد طلب الإخلاء بنجاح وإخلاء الدفعة بالكامل.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'فشل اعتماد طلب الإخلاء: '.$e->getMessage());
        }
    }

    public function reject(Request $request, Project $project, ProjectClearanceRequest $clearanceRequest)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('clearances.reject', $project), 403);

        if (ProjectWorkflowRequest::where('requestable_type', $clearanceRequest->getMorphClass())
            ->where('requestable_id', $clearanceRequest->id)->where('request_type', 'clearance_request')->where('status', 'pending')->exists()) {
            return redirect()->route('approvals.index')->with('error', 'This clearance request must be decided in the Approval Center.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:5|max:1000',
        ], [
            'rejection_reason.required' => 'سبب الرفض إلزامي.',
            'rejection_reason.min' => 'سبب الرفض يجب أن يحتوي على 5 أحرف على الأقل.',
        ]);

        try {
            $this->workflowService->rejectClearanceRequest($clearanceRequest, auth()->user(), $request->input('rejection_reason'));

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with('warning', 'تم رفض طلب الإخلاء وتحرير سجلات الإنجاز المرتبطة به بنجاح لإتاحة تصحيحها وتقديم طلب جديد.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'فشل رفض طلب الإخلاء: '.$e->getMessage());
        }
    }

    public function cancel(Request $request, Project $project, ProjectClearanceRequest $clearanceRequest)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('clearances.reject', $project), 403);

        try {
            $this->workflowService->cancelClearanceRequest($clearanceRequest, auth()->user(), $request->input('notes'));

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with('info', 'تم إلغاء طلب الإخلاء وتحرير سجلات الإنجاز بنجاح.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'فشل إلغاء طلب الإخلاء: '.$e->getMessage());
        }
    }
}

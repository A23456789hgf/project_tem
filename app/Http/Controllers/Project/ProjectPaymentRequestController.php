<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Http\Requests\Project\StoreProjectPaymentRequestRequest;
use App\Models\Project;
use App\Models\ProjectDisbursementPlanDetail;
use App\Models\ProjectPaymentRequest;
use App\Services\ProjectFinancialWorkflowService;
use App\Services\ProjectWorkflowRequestService;
use Illuminate\Http\Request;

class ProjectPaymentRequestController extends Controller
{
    public function __construct(
        protected ProjectFinancialWorkflowService $workflowService,
        protected ProjectService $projectService
    ) {}

    /**
     * List all payment requests across all projects (global management page).
     */
    public function indexAll(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('payments.view'), 403);

        $query = ProjectPaymentRequest::query()
            ->whereHas('project', fn ($q) => $q->visibleToUser('projects'))
            ->with(['project', 'installment', 'requestedBy', 'approvedBy'])
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
                $q->where('status', 'approved');
            })
            ->with(['disbursementPlan.details'])
            ->select('id', 'project_name', 'form_number', 'status')
            ->orderBy('project_name')
            ->get();

        $paymentRequests = $query->paginate(20)->withQueryString();

        return view('projects.disbursement_plans.payments.index_all', compact('paymentRequests', 'stats', 'executionProjects'));
    }

    public function index(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('payments.view', $project), 403);

        $paymentRequests = $project->paymentRequests()
            ->with(['installment', 'requestedBy', 'approvedBy'])
            ->orderByDesc('id')
            ->get();

        return view('projects.disbursement_plans.payments.index', compact('project', 'paymentRequests'));
    }

    public function create(Project $project, ProjectDisbursementPlanDetail $detail)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('payments.create', $project), 403);

        $arabicAmount = $this->workflowService->toArabicWords($detail->planned_amount);

        return view('projects.disbursement_plans.payments.create', compact('project', 'detail', 'arabicAmount'));
    }

    public function store(StoreProjectPaymentRequestRequest $request, Project $project, ProjectDisbursementPlanDetail $detail)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('payments.create', $project) || auth()->user()->hasPermission('disbursements.request', $project), 403);

        try {
            $data = $request->validated();

            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store("projects/{$project->id}/payments", 'public');
                $data['attachment_path'] = $path;
            }

            $workflowRequest = app(ProjectWorkflowRequestService::class)->submit($project, 'next_installment', [
                'detail_id' => $detail->id,
                'notes' => $data['notes'] ?? null,
                'dedupe_key' => 'installment-'.$detail->id,
            ], auth()->user());

            return redirect()->route('approvals.workflow-requests.show', $workflowRequest)
                ->with('success', 'Next installment request submitted to the Approval Center.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Project $project, ProjectPaymentRequest $paymentRequest)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('payments.view', $project), 403);

        $paymentRequest->load(['installment', 'requestedBy', 'approvedBy']);
        $arabicAmount = $this->workflowService->toArabicWords($paymentRequest->requested_amount);

        return view('projects.disbursement_plans.payments.show', compact('project', 'paymentRequest', 'arabicAmount'));
    }

    public function approve(Request $request, Project $project, ProjectPaymentRequest $paymentRequest)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('payments.approve', $project) || auth()->user()->hasPermission('disbursements.approve', $project), 403);

        try {
            $result = $this->workflowService->approvePaymentRequest($paymentRequest, auth()->user());
            $status = ($result['disbursement']['success'] ?? false) ? 'success' : 'warning';
            $msg = $result['disbursement']['message'] ?? ($result['disbursement']['error'] ?? 'تم اعتماد الطلب');

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with($status, 'تم اعتماد طلب الصرف بنجاح. '.$msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'فشل اعتماد طلب الصرف: '.$e->getMessage());
        }
    }

    public function reject(Request $request, Project $project, ProjectPaymentRequest $paymentRequest)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('payments.reject', $project) || auth()->user()->hasPermission('disbursements.reject', $project), 403);

        $request->validate([
            'rejection_reason' => 'required|string|min:5|max:1000',
        ], [
            'rejection_reason.required' => 'سبب الرفض إلزامي.',
            'rejection_reason.min' => 'سبب الرفض يجب أن يحتوي على 5 أحرف على الأقل.',
        ]);

        try {
            $this->workflowService->rejectPaymentRequest($paymentRequest, auth()->user(), $request->input('rejection_reason'));

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with('warning', 'تم رفض طلب الصرف وإعادة الدفعة لحالة "مخطط" لإتاحة تقديم طلب مصحح.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'فشل رفض طلب الصرف: '.$e->getMessage());
        }
    }

    public function cancel(Request $request, Project $project, ProjectPaymentRequest $paymentRequest)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('payments.reject', $project) || auth()->user()->hasPermission('disbursements.reject', $project), 403);

        try {
            $this->workflowService->cancelPaymentRequest($paymentRequest, auth()->user(), $request->input('notes'));

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with('info', 'تم إلغاء طلب الصرف بنجاح.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'فشل إلغاء طلب الصرف: '.$e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Project;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Http\Requests\Project\StoreProjectDisbursementPlanRequest;
use App\Models\FundingSource;
use App\Models\Project;
use App\Models\ProjectClearanceRequest;
use App\Models\ProjectDisbursementPlan;
use App\Models\ProjectDisbursementPlanDetail;
use App\Models\ProjectPaymentRequest;
use App\Services\ProjectFinancialWorkflowService;
use Illuminate\Http\Request;

class ProjectDisbursementPlanController extends Controller
{
    public function __construct(
        protected ProjectFinancialWorkflowService $workflowService,
        protected ProjectService $projectService
    ) {}

    /**
     * Display a listing of projects in the disbursement lifecycle
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('disbursements.view'), 403);

        $query = Project::visibleToUser('projects')
            ->where('uses_disbursement_workflow', true)
            ->where(function ($q) {
                $q->where('status', ProjectStatus::AwaitingDisbursementPlan->value)
                    ->orWhereHas('disbursementPlan');
            })
            ->with([
                'cost',
                'disbursementPlan.details',
                'creatorEntity',
            ]);

        // Search filter (name or project code)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        // Plan status filter
        if ($request->filled('plan_status')) {
            $status = $request->input('plan_status');
            if ($status === 'awaiting_plan') {
                $query->where('status', ProjectStatus::AwaitingDisbursementPlan->value)
                    ->whereDoesntHave('disbursementPlan');
            } elseif ($status === 'draft') {
                $query->whereHas('disbursementPlan', function ($q) {
                    $q->where('status', 'draft');
                });
            } elseif ($status === 'approved') {
                $query->whereHas('disbursementPlan', function ($q) {
                    $q->where('status', 'approved');
                });
            } elseif ($status === 'completed') {
                $query->whereHas('disbursementPlan', function ($q) {
                    $q->where('status', 'completed');
                });
            }
        }

        // Summary KPI statistics
        $baseQuery = Project::visibleToUser('projects')->where('uses_disbursement_workflow', true);

        $stats = [
            'total_projects' => (clone $baseQuery)->where(function ($q) {
                $q->where('status', ProjectStatus::AwaitingDisbursementPlan->value)->orWhereHas('disbursementPlan');
            })->count(),
            'awaiting_plan_count' => (clone $baseQuery)->where('status', ProjectStatus::AwaitingDisbursementPlan->value)->count(),
            'approved_plans_count' => (clone $baseQuery)->whereHas('disbursementPlan', fn ($q) => $q->where('status', 'approved'))->count(),
            'completed_plans_count' => (clone $baseQuery)->whereHas('disbursementPlan', fn ($q) => $q->where('status', 'completed'))->count(),
            'total_disbursed' => ProjectDisbursementPlan::whereHas('project', fn ($q) => $q->visibleToUser('projects'))->sum('total_disbursed_amount'),
            'pending_payments_count' => ProjectPaymentRequest::where('status', 'pending')
                ->whereHas('project', fn ($q) => $q->visibleToUser('projects'))->count(),
            'pending_clearances_count' => ProjectClearanceRequest::where('status', 'pending')
                ->whereHas('project', fn ($q) => $q->visibleToUser('projects'))->count(),
        ];

        $executionProjects = Project::visibleToUser('projects')
            ->where(function ($q) {
                $q->where('status', ProjectStatus::AwaitingDisbursementPlan->value)
                    ->orWhere('status', ProjectStatus::InExecution->value)
                    ->orWhere('uses_disbursement_workflow', true)
                    ->orWhereHas('disbursementPlan');
            })
            ->with(['disbursementPlan'])
            ->select('id', 'project_name', 'form_number', 'status')
            ->orderBy('project_name')
            ->get();

        $projects = $query->latest('updated_at')->paginate(15)->withQueryString();

        return view('projects.disbursement_plans.index', compact('projects', 'stats', 'executionProjects'));
    }

    /**
     * Show form to create or edit the project disbursement plan
     */
    public function create(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        abort_unless(auth()->user()->hasPermission('disbursements.create', $project), 403);

        $plan = $project->disbursementPlan;
        if ($plan && in_array($plan->status, ['approved', 'completed'], true)) {
            return redirect()->route('projects.disbursement-plan.show', $project);
        }

        $project->load(['cost', 'disbursementPlan.details']);
        $projectTotalCost = (float) ($project->cost->total_cost ?? 0);
        $arabicCostWords = $this->workflowService->toArabicWords($projectTotalCost);

        $fundingSources = FundingSource::query()->orderBy('name')->get();

        return view('projects.disbursement_plans.create', compact('project', 'plan', 'projectTotalCost', 'arabicCostWords', 'fundingSources'));
    }

    /**
     * Store or update draft disbursement plan
     */
    public function store(StoreProjectDisbursementPlanRequest $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('disbursements.create', $project), 403);

        try {
            $plan = $this->workflowService->createOrUpdatePlan($project, $request->validated(), auth()->user());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حفظ خطة الصرف كمسودة بنجاح.',
                    'plan' => $plan,
                ]);
            }

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with('success', 'تم حفظ خطة الصرف كمسودة بنجاح.');
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * View disbursement plan details and installments
     */
    public function show(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('disbursements.view', $project), 403);

        $plan = $project->disbursementPlan;
        if (! $plan) {
            return redirect()->route('projects.disbursement-plan.create', $project);
        }

        $plan->load(['details.clearanceRequests', 'details.paymentRequests', 'approvedBy', 'createdBy']);
        $project->load('cost');
        $projectTotalCost = (float) ($project->cost->total_cost ?? 0);
        $arabicCostWords = $this->workflowService->toArabicWords($projectTotalCost);

        return view('projects.disbursement_plans.show', compact('project', 'plan', 'projectTotalCost', 'arabicCostWords'));
    }

    /**
     * Approve disbursement plan and auto-trigger ERPNext disbursement for Detail #1
     */
    public function approve(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('disbursements.approve', $project), 403);

        $plan = $project->disbursementPlan;
        if (! $plan) {
            return redirect()->back()->with('error', 'لا توجد خطة صرف مسجلة لهذا المشروع.');
        }

        try {
            $result = $this->workflowService->approvePlan($plan, auth()->user());

            $disbMsg = $result['disbursement']['message'] ?? '';
            $status = ($result['disbursement']['success'] ?? false) ? 'success' : 'warning';

            return redirect()->route('projects.disbursement-plan.show', $project)
                ->with($status, 'تم اعتماد خطة الصرف بنجاح. '.$disbMsg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'فشل اعتماد خطة الصرف: '.$e->getMessage());
        }
    }

    /**
     * Retry disbursement for a failed installment in ERPNext
     */
    public function retryDisbursement(Request $request, Project $project, ProjectDisbursementPlanDetail $detail)
    {
        $this->projectService->authorizeProjectAccess($project);
        abort_unless(auth()->user()->hasPermission('disbursements.retry', $project), 403);

        try {
            $result = $this->workflowService->retryDisbursement($detail, auth()->user());

            $status = ($result['success'] ?? false) ? 'success' : 'error';
            $msg = $result['message'] ?? ($result['error'] ?? 'تمت المعالجة');

            return redirect()->route('projects.disbursement-plan.show', $project)->with($status, $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'فشلت إعادة محاولة الصرف: '.$e->getMessage());
        }
    }
}

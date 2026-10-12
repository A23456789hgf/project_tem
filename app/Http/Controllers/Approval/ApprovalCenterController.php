<?php

namespace App\Http\Controllers\Approval;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\RespondConsultationRequest;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\ProjectReferral;
use App\Models\ProjectWorkflowRequest;
use App\Services\ApprovalService;
use App\Services\EntityHierarchyService;
use App\Services\UserDataScopeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class ApprovalCenterController extends Controller
{
    protected ApprovalService $approvalService;

    protected EntityHierarchyService $entityHierarchyService;

    public function __construct(
        ApprovalService $approvalService,
        EntityHierarchyService $entityHierarchyService
    ) {
        $this->approvalService = $approvalService;
        $this->entityHierarchyService = $entityHierarchyService;
    }

    /**
     * Display the Unified Approval Center index page with ONE dynamic table,
     * DB-level filtering, server-side pagination, and Arabic RTL design.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
        $scopeService = app(UserDataScopeService::class);
        $allowedEntityIds = $user ? $scopeService->getUserEntityIds($user) : [];
        $allowedAuthorityIds = $user ? $scopeService->getUserAuthorityIds($user) : [];

        $search = trim($request->input('search', ''));
        $requestTypeFilter = $request->input('request_type');
        $statusFilter = $request->input('status', 'pending');
        $submittingEntityId = $request->input('submitting_entity_id');
        $responsibleEntityId = $request->input('responsible_entity_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sortBy = $request->input('sort_by', 'newest');

        $allItems = collect();

        // -------------------------------------------------------------
        // 1. Database Query for Standard Project Approvals
        // -------------------------------------------------------------
        if (! $requestTypeFilter || $requestTypeFilter === 'project_approval') {
            $paQuery = ProjectApproval::query()
                ->select('project_approvals.*')
                ->join('projects', 'projects.id', '=', 'project_approvals.project_id')
                ->with([
                    'project.creatorEntity',
                    'project.authority',
                    'project.createdBy',
                    'entity',
                    'authority',
                    'assignedUser',
                ])
                ->whereNull('project_approvals.workflow_request_id')
                ->where('project_approvals.is_active', true)
                ->where('project_approvals.status', 'pending')
                ->whereIn('projects.status', [ProjectStatus::PendingApproval->value, 'rolled_back_for_review']);

            if (! $isAdmin) {
                $userAuthorityId = (int) ($user?->authority_id ?? 0);
                $isExternal = $user && method_exists($user, 'isExternal') && $user->isExternal();

                $paQuery->where(function ($q) use ($user, $allowedEntityIds, $allowedAuthorityIds, $isExternal, $userAuthorityId) {
                    if ($isExternal) {
                        $targetAuths = ! empty($allowedAuthorityIds) ? $allowedAuthorityIds : ($userAuthorityId > 0 ? [$userAuthorityId] : []);
                        if (! empty($targetAuths)) {
                            $q->whereIn('project_approvals.authority_id', $targetAuths);
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    } elseif (! empty($allowedEntityIds)) {
                        $q->whereIn('project_approvals.entity_id', $allowedEntityIds);
                    } else {
                        $q->whereRaw('1 = 0');
                    }

                    if ($user) {
                        $q->where(function ($assignQ) use ($user) {
                            $assignQ->where('project_approvals.assigned_user_id', $user->id)
                                ->orWhereNull('project_approvals.assigned_user_id');
                        });
                    }
                });
            }

            if ($search !== '') {
                $paQuery->where(function ($q) use ($search) {
                    $q->where('projects.project_name', 'like', "%{$search}%")
                        ->orWhere('projects.form_number', 'like', "%{$search}%")
                        ->orWhere('projects.id', 'like', "%{$search}%");
                });
            }

            if ($submittingEntityId) {
                $paQuery->where('projects.creator_entity_id', $submittingEntityId);
            }

            if ($responsibleEntityId) {
                $paQuery->where('project_approvals.entity_id', $responsibleEntityId);
            }

            if ($dateFrom) {
                $paQuery->where('project_approvals.created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
            }

            if ($dateTo) {
                $paQuery->where('project_approvals.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
            }

            $projectApprovals = $paQuery->get()->unique('project_id');

            foreach ($projectApprovals as $pa) {
                $project = $pa->project;
                if (! $project) {
                    continue;
                }

                if (! $isAdmin && ! $this->approvalService->canUserActOnStep($user, $pa)) {
                    continue;
                }

                $allItems->push((object) [
                    'uid' => 'pa_'.$pa->id,
                    'project_id' => $project->id,
                    'project_number' => $project->form_number ?: ('PRJ-'.$project->id),
                    'project_name' => $project->project_name,
                    'request_type_key' => 'project_approval',
                    'request_type_label' => 'اعتماد مشروع',
                    'request_type_badge_class' => 'bg-primary text-white',
                    'submitting_entity_name' => $project->creator_entity_name ?: ($project->creatorEntity?->name ?? 'غير محدد'),
                    'current_stage_label' => $pa->getPhaseLabel(),
                    'responsible_entity_name' => $pa->entity?->name ?? $pa->authority?->agency_name ?? 'المركز الرئيسي',
                    'responsible_user_name' => $pa->assignedUser?->name,
                    'status' => $pa->status,
                    'status_label' => 'قيد المراجعة',
                    'updated_at' => $pa->updated_at ?? $pa->created_at,
                    'action_url' => route('approvals.show', $project->id),
                    'model' => $pa,
                ]);
            }
        }

        // -------------------------------------------------------------
        // 2. Database Query for Financial Workflow Requests
        // -------------------------------------------------------------
        if (! $requestTypeFilter || $requestTypeFilter !== 'project_approval') {
            $wrQuery = ProjectWorkflowRequest::query()
                ->select('project_workflow_requests.*')
                ->join('projects', 'projects.id', '=', 'project_workflow_requests.project_id')
                ->join('project_approvals', function ($join) {
                    $join->on('project_approvals.workflow_request_id', '=', 'project_workflow_requests.id')
                        ->where('project_approvals.is_active', '=', true)
                        ->where('project_approvals.status', '=', 'pending');
                })
                ->with([
                    'project.creatorEntity',
                    'submitter',
                    'approvalSteps' => function ($query) {
                        $query->where('is_active', true)->where('status', 'pending')
                            ->with(['entity', 'authority', 'assignedUser']);
                    },
                ])
                ->where('project_workflow_requests.status', 'pending');

            if (! $isAdmin) {
                $userAuthorityId = (int) ($user?->authority_id ?? 0);
                $isExternal = $user && method_exists($user, 'isExternal') && $user->isExternal();

                $wrQuery->where(function ($q) use ($user, $allowedEntityIds, $allowedAuthorityIds, $isExternal, $userAuthorityId) {
                    if ($isExternal) {
                        $targetAuths = ! empty($allowedAuthorityIds) ? $allowedAuthorityIds : ($userAuthorityId > 0 ? [$userAuthorityId] : []);
                        if (! empty($targetAuths)) {
                            $q->whereIn('project_approvals.authority_id', $targetAuths);
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    } elseif (! empty($allowedEntityIds)) {
                        $q->whereIn('project_approvals.entity_id', $allowedEntityIds);
                    } else {
                        $q->whereRaw('1 = 0');
                    }

                    if ($user) {
                        $q->where(function ($assignQ) use ($user) {
                            $assignQ->where('project_approvals.assigned_user_id', $user->id)
                                ->orWhereNull('project_approvals.assigned_user_id');
                        });
                    }
                });
            }

            if ($requestTypeFilter && $requestTypeFilter !== 'project_approval') {
                if ($requestTypeFilter === 'payment_request') {
                    $wrQuery->whereIn('project_workflow_requests.request_type', ['payment_request', 'next_installment', 'payment']);
                } elseif ($requestTypeFilter === 'clearance_request') {
                    $wrQuery->whereIn('project_workflow_requests.request_type', ['clearance_request', 'clearance']);
                } elseif ($requestTypeFilter === 'clearance_payment') {
                    $wrQuery->where('project_workflow_requests.request_type', 'clearance_payment');
                } elseif ($requestTypeFilter === 'clearance_closure') {
                    $wrQuery->where('project_workflow_requests.request_type', 'clearance_closure');
                } else {
                    $wrQuery->where('project_workflow_requests.request_type', $requestTypeFilter);
                }
            }

            if ($search !== '') {
                $wrQuery->where(function ($q) use ($search) {
                    $q->where('projects.project_name', 'like', "%{$search}%")
                        ->orWhere('projects.form_number', 'like', "%{$search}%")
                        ->orWhere('project_workflow_requests.request_number', 'like', "%{$search}%")
                        ->orWhere('projects.id', 'like', "%{$search}%");
                });
            }

            if ($submittingEntityId) {
                $wrQuery->where('projects.creator_entity_id', $submittingEntityId);
            }

            if ($responsibleEntityId) {
                $wrQuery->where('project_approvals.entity_id', $responsibleEntityId);
            }

            if ($dateFrom) {
                $wrQuery->where('project_workflow_requests.created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
            }

            if ($dateTo) {
                $wrQuery->where('project_workflow_requests.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
            }

            $workflowRequests = $wrQuery->get()->unique('id');

            foreach ($workflowRequests as $wr) {
                $project = $wr->project;
                $activeStep = $wr->approvalSteps->first();

                if (! $isAdmin) {
                    if (! $activeStep || (! $this->approvalService->canUserActOnStep($user, $activeStep, 'approve') && ! $this->approvalService->canUserActOnStep($user, $activeStep, 'reject'))) {
                        continue;
                    }
                }

                $reqTypeKey = $wr->request_type;
                $reqTypeMap = [
                    'clearance_request' => ['label' => 'طلب إخلاء', 'badge' => 'bg-info text-dark'],
                    'clearance' => ['label' => 'طلب إخلاء', 'badge' => 'bg-info text-dark'],
                    'payment_request' => ['label' => 'طلب دفعة', 'badge' => 'bg-warning text-dark'],
                    'payment' => ['label' => 'طلب دفعة', 'badge' => 'bg-warning text-dark'],
                    'next_installment' => ['label' => 'طلب دفعة', 'badge' => 'bg-warning text-dark'],
                    'clearance_payment' => ['label' => 'طلب إخلاء ودفعة', 'badge' => 'bg-primary text-white'],
                    'clearance_closure' => ['label' => 'طلب إخلاء وإغلاق', 'badge' => 'bg-danger text-white'],
                    'project_closure' => ['label' => 'طلب إغلاق مشروع', 'badge' => 'bg-danger text-white'],
                    'financial_justification' => ['label' => 'مبرر مالي', 'badge' => 'bg-dark text-white'],
                ];

                $typeInfo = $reqTypeMap[$reqTypeKey] ?? ['label' => str($reqTypeKey)->replace('_', ' ')->title(), 'badge' => 'bg-secondary text-white'];

                $unifiedRequestId = $wr->payload['unified_request_id'] ?? ($wr->requestable_type === 'App\\Models\\ProjectUnifiedRequest' ? $wr->requestable_id : null);
                $actionUrl = $unifiedRequestId
                    ? route('projects.requests.show', $unifiedRequestId)
                    : route('approvals.workflow-requests.show', $wr->id);

                $allItems->push((object) [
                    'uid' => 'wr_'.$wr->id,
                    'project_id' => $project?->id,
                    'project_number' => $project?->form_number ?: ($project ? 'PRJ-'.$project->id : '—'),
                    'project_name' => $project?->project_name ?? '—',
                    'request_type_key' => $reqTypeKey,
                    'request_type_label' => $typeInfo['label'],
                    'request_type_badge_class' => $typeInfo['badge'],
                    'submitting_entity_name' => $wr->submitter?->name ?: ($project?->creator_entity_name ?? 'غير محدد'),
                    'current_stage_label' => $activeStep?->getPhaseLabel() ?? ($activeStep?->stage_name ?? 'مراجعة طلب سير العمل'),
                    'responsible_entity_name' => $activeStep?->entity?->name ?? $activeStep?->authority?->agency_name ?? 'المركز الرئيسي',
                    'responsible_user_name' => $activeStep?->assignedUser?->name,
                    'status' => $wr->status,
                    'status_label' => 'قيد المراجعة',
                    'updated_at' => $wr->updated_at ?? $wr->created_at,
                    'action_url' => $actionUrl,
                    'model' => $wr,
                ]);
            }
        }

        // -------------------------------------------------------------
        // 3. Sorting
        // -------------------------------------------------------------
        if ($sortBy === 'oldest') {
            $allItems = $allItems->sortBy('updated_at');
        } elseif ($sortBy === 'project_number') {
            $allItems = $allItems->sortBy('project_number');
        } elseif ($sortBy === 'request_type') {
            $allItems = $allItems->sortBy('request_type_label');
        } else {
            $allItems = $allItems->sortByDesc('updated_at');
        }

        // -------------------------------------------------------------
        // 4. Server-Side Unified Pagination
        // -------------------------------------------------------------
        $perPage = 15;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $currentPageItems = $allItems->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $approvalRecords = new LengthAwarePaginator(
            $currentPageItems,
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $entities = InternalEntity::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('approvals.index', compact(
            'approvalRecords',
            'entities',
            'isAdmin'
        ));
    }

    /**
     * Backward-compatible delegation for referral details
     */
    public function showReferral(ProjectReferral $referral)
    {
        return app(ConsultationCenterController::class)->show($referral);
    }

    /**
     * Backward-compatible delegation for referral respond
     */
    public function respondReferral(RespondConsultationRequest $request, ProjectReferral $referral)
    {
        return app(ConsultationCenterController::class)->respond($request, $referral);
    }

    /**
     * Display the standalone Approval Center show page for a single project.
     */
    public function show(Project $project)
    {
        $user = Auth::user();
        $userEntityId = (int) ($user?->entity_id ?? 0);
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        $project->load([
            'createdBy.entity',
            'creatorEntity',
            'authority',
            'cost',
            'executiveActionCosts',
            'financings.fundingSource',
            'projectApprovals' => function ($q) {
                $q->with(['entity', 'authority', 'assignedUser', 'reviewedByUser', 'technicalReviewer', 'financialReviewer'])
                    ->orderBy('step_order');
            },
            'activityHistory' => function ($q) {
                $q->with('user')->orderByDesc('created_at');
            },
            'referrals' => function ($q) {
                $q->with(['referringEntity', 'referredEntity', 'referringUser', 'respondingUser'])
                    ->orderBy('created_at', 'desc');
            },
            'correspondences' => function ($q) {
                $q->with(['senderEntity', 'recipientEntity', 'senderUser'])
                    ->orderBy('created_at', 'desc');
            },
            'documents',
        ]);

        $activeStep = $this->approvalService->getActiveStep($project);
        if ($activeStep) {
            $activeStep->loadMissing(['entity', 'authority', 'assignedUser']);
        }
        $canActOnActiveStep = false;
        if ($activeStep && $user) {
            $canActOnActiveStep = $isAdmin || $this->approvalService->canUserActOnStep($user, $activeStep);
        }

        $approvalChain = $project->projectApprovals;

        $entitiesList = InternalEntity::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('id', '!=', $userEntityId)
            ->orderBy('name')
            ->get();

        return view('approvals.show', compact(
            'project',
            'activeStep',
            'canActOnActiveStep',
            'entitiesList',
            'approvalChain',
            'isAdmin'
        ));
    }
}

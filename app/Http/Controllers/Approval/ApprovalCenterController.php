<?php

namespace App\Http\Controllers\Approval;

use App\Enums\ApprovalPhase;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\RespondConsultationRequest;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\ProjectReferral;
use App\Services\ApprovalService;
use App\Services\EntityHierarchyService;
use Illuminate\Http\Request;
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
     * Display the standalone Approval Center index page with dynamic tabs, statistics, and filters.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userEntityId = (int) ($user?->entity_id ?? 0);
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
        // Determine active tab for admin view (default to my_action)
        $activeTab = $request->input('tab', 'my_action');

        // Base accessible projects query for the user
        $baseProjectsQuery = Project::query()
            ->with([
                'createdBy',
                'creatorEntity',
                'cost',
                'projectApprovals' => function ($q) {
                    $q->with(['entity', 'reviewedByUser'])->orderBy('step_order');
                },
            ]);

        $allowedEntityIds = $userEntityId > 0 ? [$userEntityId] : [];

        // Tabs are removed; we only display records where the current user needs action.
        // $requestedTab and $activeTab are no longer used.

        // 2. Base Task Inbox Query: ONLY records requiring action NOW from current user
        // (Active + Pending + User Authorized + Action Required)
        $inboxQuery = ProjectApproval::query()
            ->with([
                'project.creatorEntity',
                'project.authority',
                'project.createdBy',
                'project.cost',
                'entity',
                'authority',
                'assignedUser',
                'reviewedByUser',
                'technicalReviewer',
                'financialReviewer',
            ])
            ->where('is_active', true)
            ->where(function ($q) use ($user, $userEntityId, $allowedEntityIds, $isAdmin) {
                // Scenario A: Active pending approval step assigned to user's entity
                $q->where(function ($sub) use ($user, $userEntityId, $allowedEntityIds, $isAdmin) {
                    $sub->where('status', 'pending')
                        ->whereHas('project', function ($pq) {
                            $pq->where('status', ProjectStatus::PendingApproval->value);
                        });
                    if (! $isAdmin) {
                        if ($userEntityId) {
                            $sub->whereIn('entity_id', $allowedEntityIds);
                        } else {
                            $sub->whereRaw('1 = 0');
                        }

                        if ($user) {
                            $sub->where(function ($assignQ) use ($user) {
                                $assignQ->where(function ($tq) use ($user) {
                                    $tq->where('phase', 'technical_review')
                                        ->where(function ($q) use ($user) {
                                            $q->where('technical_reviewer_id', $user->id)
                                                ->orWhereNull('technical_reviewer_id');
                                        });
                                })->orWhere(function ($fq) use ($user) {
                                    $fq->where('phase', 'financial_review')
                                        ->where(function ($q) use ($user) {
                                            $q->where('financial_reviewer_id', $user->id)
                                                ->orWhereNull('financial_reviewer_id');
                                        });
                                })->orWhere(function ($oq) use ($user) {
                                    $oq->where(function ($q) {
                                        $q->whereNotIn('phase', ['technical_review', 'financial_review'])
                                            ->orWhereNull('phase');
                                    })
                                        ->where(function ($q) use ($user) {
                                            $q->where('assigned_user_id', $user->id)
                                                ->orWhereNull('assigned_user_id');
                                        });
                                });
                            });
                        } else {
                            $sub->whereRaw('1 = 0');
                        }
                    }
                })
                // Scenario B: Project rolled back for review where user's entity is creator entity needing resubmission
                    ->orWhere(function ($sub) use ($user, $allowedEntityIds, $isAdmin) {
                        $sub->whereHas('project', function ($pq) use ($user, $allowedEntityIds, $isAdmin) {
                            $pq->where('status', 'rolled_back_for_review');
                            if (! $isAdmin) {
                                $pq->where(function ($cpq) use ($user, $allowedEntityIds) {
                                    $cpq->whereIn('creator_entity_id', $allowedEntityIds)
                                        ->orWhere('created_by_user_id', $user->id);
                                });
                            }
                        });
                    });
            });

        // 4. Resolve Records for Display
        $approvalsQuery = clone $inboxQuery;

        $defaultRelations = [
            'project.creatorEntity',
            'project.authority',
            'project.createdBy',
            'project.cost',
            'entity',
            'authority',
            'assignedUser',
            'reviewedByUser',
            'technicalReviewer',
            'financialReviewer',
        ];

        // For non-admin users we keep the inboxQuery (already filtered to actions they can take).
        // For admin, show all approvals regardless of phase.
        if (! $isAdmin) {
            // No additional phase filtering; inboxQuery already limits to actionable records.
        } else {
            $approvalsQuery = ProjectApproval::query()->with($defaultRelations);
        }

        // Remove all legacy tab based filters and dynamic filters that affect scope.
        // Preserve search filter but limit it to already scoped records.
        if ($request->filled('search')) {
            $search = trim($request->search);
            $approvalsQuery->where(function ($q) use ($search) {
                $q->whereHas('project', function ($pq) use ($search) {
                    $pq->where('project_name', 'like', "%{$search}%")
                        ->orWhere('form_number', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                })->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Pagination after applying all scopes
        $approvalRecords = $approvalsQuery
            ->orderBy('updated_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        // العرض الرئيسي يعتمد على آخر حالة فقط لكل مشروع
        // مع إبقاء سجل الحركات الكامل داخل صفحة التفاصيل.
        $approvalRecords->setCollection(
            $approvalRecords->getCollection()
                ->unique('project_id')
                ->values()
        );

        // Compute can_user_act for each record (admin can act on all)
        foreach ($approvalRecords as $record) {
            $record->can_user_act = $record->is_active && $user && $this->approvalService->canUserActOnStep($user, $record);
        }

        // Filter out records the user cannot act on (except admin)
        if (! $isAdmin) {
            $filtered = $approvalRecords->getCollection()->filter(function ($rec) {
                // تظهر للمستخدم فقط المهمة الحالية المسندة له
                // وبعد الإنجاز تختفي من صندوق المهام.
                return $rec->can_user_act && $rec->status === 'pending' && $rec->is_active;
            });

            $approvalRecords->setCollection($filtered);
        }

        // Removed obsolete manual filtering; new logic applied earlier.

        $projects = $approvalRecords;

        // 5. Dynamic Filter Datasets (still needed for admin UI if any)
        $entities = InternalEntity::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $phases = ApprovalPhase::cases();

        return view('approvals.index', compact(
            'projects',
            'approvalRecords',
            'entities',
            'phases',
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

        // Eager load all necessary relationships
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

    /**
     * Store attachment file
     */
    private function storeAttachment($file, ?Project $project = null): string
    {
        $fileName = time().'_'.uniqid().'_'.preg_replace('/[^\w\.\-]/', '_', $file->getClientOriginalName());
        $folder = $project ? "projects/{$project->id}/referral_attachments" : 'referrals/attachments';

        return $file->storeAs($folder, $fileName, 'public');
    }
}

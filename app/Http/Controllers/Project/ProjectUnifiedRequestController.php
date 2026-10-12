<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Http\Requests\Project\StoreProjectUnifiedRequestRequest;
use App\Http\Requests\Project\UpdateProjectUnifiedRequestRequest;
use App\Models\Project;
use App\Models\ProjectUnifiedRequest;
use App\Services\HijriDateService;
use App\Services\LetterheadAccessService;
use App\Services\ProjectFinancialWorkflowService;
use App\Services\ProjectUnifiedRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectUnifiedRequestController extends Controller
{
    public function __construct(

        protected ProjectUnifiedRequestService $unifiedRequestService,

        protected ProjectFinancialWorkflowService $financialWorkflowService,

        protected ProjectService $projectService

    ) {}

    /**
     * Display a listing of all unified project requests across all four types.
     */
    public function index(Request $request)
    {

        $user = auth()->user();

        abort_unless(
            $user->isAdmin()
                || $user->hasPermission('project_requests.view')
                || $user->hasPermission('payments.view')
                || $user->hasPermission('clearances.view')
                || $user->hasPermission('disbursements.view'),
            403
        );

        $projectIds = Project::visibleToUser('projects')->select('projects.id');

        $query = ProjectUnifiedRequest::query()
            ->whereIn('project_id', $projectIds)

            ->visibleToUser($user)

            ->with([

                'project',

                'submittingEntity',

                'submittingAuthority',

                'submittingUser',

                'creator',

                'approvedBy',

                'clearanceInstallment',

                'paymentInstallment',

                'approvalSteps',

            ])

            ->orderByDesc('id');

        // Filter: Request Type

        if ($request->filled('type')) {

            $query->where('request_type', $request->input('type'));

        }

        // Filter: Status

        if ($request->filled('status')) {

            $query->where('status', $request->input('status'));

        }

        // Filter: Project ID

        if ($request->filled('project_id')) {

            $query->where('project_id', $request->input('project_id'));

        }

        // Filter: Submitting Entity

        if ($request->filled('submitting_entity_id')) {

            $entityVal = $request->input('submitting_entity_id');

            // Format can be "internal:12" or numeric

            if (str_contains($entityVal, ':')) {

                [$eType, $eId] = explode(':', $entityVal, 2);

                if ($eType === 'external') {

                    $query->where('submitting_authority_id', $eId);

                } else {

                    $query->where('submitting_entity_id', $eId);

                }

            } else {

                $query->where('submitting_entity_id', $entityVal);

            }

        }

        // Filter: Submitting User

        if ($request->filled('submitting_user_id')) {

            $query->where('submitting_user_id', $request->input('submitting_user_id'));

        }

        // Filter: Date Range

        if ($request->filled('date_from')) {

            $query->whereDate('request_date', '>=', app(HijriDateService::class)->toGregorianDate($request->input('date_from')));

        }

        if ($request->filled('date_to')) {

            $query->whereDate('request_date', '<=', app(HijriDateService::class)->toGregorianDate($request->input('date_to')));

        }

        // Filter: Search keyword (request number or project name/number)

        if ($request->filled('search')) {

            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {

                $q->where('request_number', 'like', "%{$search}%")

                    ->orWhereHas('project', function ($pq) use ($search) {

                        $pq->where('project_name', 'like', "%{$search}%")

                            ->orWhere('form_number', 'like', "%{$search}%");

                    });

            });

        }

        // Statistics (scoped to authenticated user's visibility)

        $statsBase = (clone $query);

        $stats = [

            'total' => (clone $statsBase)->count(),

            'draft' => (clone $statsBase)->where('status', ProjectUnifiedRequest::STATUS_DRAFT)->count(),

            'confirmed' => (clone $statsBase)->where('status', ProjectUnifiedRequest::STATUS_CONFIRMED)->count(),

            'pending' => (clone $statsBase)->where('status', ProjectUnifiedRequest::STATUS_PENDING)->count(),

            'approved' => (clone $statsBase)->where('status', ProjectUnifiedRequest::STATUS_APPROVED)->count(),

            'rejected' => (clone $statsBase)->where('status', ProjectUnifiedRequest::STATUS_REJECTED)->count(),

        ];

        $unifiedRequests = $query->paginate(20)->withQueryString();

        // Dropdown data for filter controls

        $availableEntities = $this->unifiedRequestService->getAvailableEntities($user);

        $projects = Project::visibleToUser('projects')

            ->select('id', 'project_name', 'form_number')

            ->orderBy('project_name')

            ->get();

        return view('projects.unified_requests.index', compact(

            'unifiedRequests',

            'stats',

            'availableEntities',

            'projects'

        ));

    }

    /**
     * Show the dynamic creation form for a new unified project request.
     */
    public function create(Request $request)
    {

        $user = auth()->user();

        abort_unless(
            $user->isAdmin()
                || $user->hasPermission('project_requests.create')
                || $user->hasPermission('payments.create')
                || $user->hasPermission('clearances.create')
                || $user->hasPermission('disbursements.create'),
            403
        );

        // Preselection: User defaults

        $defaultEntityType = $user->isExternal() ? 'external' : 'internal';

        $defaultEntityId = $user->isExternal() ? (int) $user->authority_id : (int) $user->entity_id;

        $defaultUserId = $user->id;

        // Authorized entities for the user

        $availableEntities = $this->unifiedRequestService->getAvailableEntities($user);

        $defaultEntity = $availableEntities->first(
            fn (array $entity) => $entity['type'] === $defaultEntityType
                && (int) $entity['id'] === $defaultEntityId
        ) ?? $availableEntities->first();

        if ($defaultEntity) {
            $defaultEntityType = $defaultEntity['type'];
            $defaultEntityId = (int) $defaultEntity['id'];
        }

        // Pre-load eligible users for the default entity

        $initialUsers = collect();

        if ($defaultEntityId > 0) {

            $initialUsers = $this->unifiedRequestService->getEligibleUsersForEntity($defaultEntityType, $defaultEntityId, $user);

        }

        // Authorized projects
        $projects = Project::visibleToUser('projects')
            ->whereHas('disbursementPlan', fn ($q) => $q->where('status', 'approved'))
            ->whereIn('status', ['in_execution', 'in_progress', 'implementation'])
            ->select('id', 'project_name', 'form_number', 'status')
            ->orderBy('project_name')
            ->get();

        // Optional preselected project or type from query parameters

        $preselectedProjectId = $request->input('project_id');

        $preselectedType = $request->input('type', ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT);

        $initialFinancialData = null;

        if ($preselectedProjectId) {

            $preProject = Project::visibleToUser('projects')->find($preselectedProjectId);

            if ($preProject) {

                $initialFinancialData = $this->unifiedRequestService->getProjectFinancialData($preProject);

            }

        }

        $todayHijri = app(HijriDateService::class)->toHijri(now());

        return view('projects.unified_requests.create', compact(

            'availableEntities',

            'initialUsers',

            'projects',

            'defaultEntityType',

            'defaultEntityId',

            'defaultUserId',

            'preselectedProjectId',

            'preselectedType',

            'initialFinancialData',
            'todayHijri'

        ));

    }

    /**
     * Store a newly created unified request in storage.
     */
    public function store(StoreProjectUnifiedRequestRequest $request)
    {

        $project = Project::visibleToUser('projects')->findOrFail((int) $request->validated('project_id'));
        $this->projectService->authorizeProjectAccess($project, $request->user());

        try {

            $data = $request->validated();

            // Handle file upload

            if ($request->hasFile('attachment')) {

                $projectId = $data['project_id'];

                $path = $request->file('attachment')->store("projects/{$projectId}/requests", 'public');

                $data['attachment_path'] = $path;

            }

            $unifiedRequest = $this->unifiedRequestService->createRequest($data, auth()->user());

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('success', "تم حفظ الطلب كمسودة بنجاح برقم: {$unifiedRequest->request_number}. يرجى مراجعة البيانات وتأكيد المسودة.");

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'فشل حفظ الطلب: '.$e->getMessage());
        }

    }

    /**
     * Display the specified unified project request.
     */
    public function show(ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            $user->isAdmin()

            || $user->hasPermission('project_requests.view', $unifiedRequest->project)

            || $user->hasPermission('payments.view', $unifiedRequest->project)

            || $user->hasPermission('clearances.view', $unifiedRequest->project)

            || $user->hasPermission('disbursements.view', $unifiedRequest->project),

            403

        );

        $unifiedRequest->load([

            'project.disbursementPlan.details',

            'submittingEntity',

            'submittingAuthority',

            'submittingUser',

            'creator',

            'approvedBy',

            'clearanceInstallment',

            'paymentInstallment',

            'clearanceRequest.items',

            'paymentRequest',

            'approvalSteps.entity',

            'approvalSteps.authority',

            'approvalSteps.assignedUser',

            'approvalSteps.reviewedByUser',

        ]);

        $arabicPaymentAmount = $unifiedRequest->hasPaymentComponent()

            ? $this->financialWorkflowService->toArabicWords($unifiedRequest->requested_payment_amount)

            : null;

        $arabicClearedAmount = $unifiedRequest->hasClearanceComponent()

            ? $this->financialWorkflowService->toArabicWords($unifiedRequest->cleared_amount)

            : null;

        $activeStep = $unifiedRequest->activeApprovalStep();

        $approvalSteps = $unifiedRequest->approvalSteps;

        $canActOnStep = $unifiedRequest->canUserActOnCurrentStep($user);

        $requestDateHijri = app(HijriDateService::class)->toHijri($unifiedRequest->request_date);

        return view('projects.unified_requests.show', compact(

            'unifiedRequest',

            'arabicPaymentAmount',

            'arabicClearedAmount',

            'activeStep',

            'approvalSteps',

            'canActOnStep',
            'requestDateHijri'

        ));

    }

    /**
     * Show the edit form for a request (draft or pending).
     */
    public function edit(ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            $user->isAdmin()

            || (int) $user->id === (int) $unifiedRequest->created_by

            || $user->hasPermission('project_requests.edit', $unifiedRequest->project)
            || $user->hasPermission('payments.create', $unifiedRequest->project)
            || $user->hasPermission('clearances.create', $unifiedRequest->project),

            403

        );

        if (! in_array($unifiedRequest->status, [ProjectUnifiedRequest::STATUS_DRAFT, ProjectUnifiedRequest::STATUS_PENDING], true)) {

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('error', 'لا يمكن تعديل طلب تم اعتماده أو رفضه.');

        }

        $availableEntities = $this->unifiedRequestService->getAvailableEntities($user);

        $currentEntityId = $unifiedRequest->submitting_type === 'external'

            ? $unifiedRequest->submitting_authority_id

            : $unifiedRequest->submitting_entity_id;

        $eligibleUsers = $currentEntityId

            ? $this->unifiedRequestService->getEligibleUsersForEntity($unifiedRequest->submitting_type, $currentEntityId, $user)

            : collect();

        $financialData = $this->unifiedRequestService->getProjectFinancialData($unifiedRequest->project);

        $requestDateHijri = app(HijriDateService::class)->toHijri($unifiedRequest->request_date);

        return view('projects.unified_requests.edit', compact(

            'unifiedRequest',

            'availableEntities',

            'eligibleUsers',

            'financialData',
            'requestDateHijri'

        ));

    }

    /**
     * Update the specified unified request.
     */
    public function update(UpdateProjectUnifiedRequestRequest $request, ProjectUnifiedRequest $unifiedRequest)
    {

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $request->user());

        abort_unless(
            $request->user()->isAdmin()
                || (int) $request->user()->id === (int) $unifiedRequest->created_by
                || $request->user()->hasPermission('project_requests.edit', $unifiedRequest->project)
                || $request->user()->hasPermission('payments.create', $unifiedRequest->project)
                || $request->user()->hasPermission('clearances.create', $unifiedRequest->project),
            403
        );

        try {
            $data = $request->validated();

            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store("projects/{$unifiedRequest->project_id}/requests", 'public');
                $data['attachment_path'] = $path;
            }

            $this->unifiedRequestService->updateRequest($unifiedRequest, $data, $request->user());

            return redirect()->route('projects.requests.show', $unifiedRequest)
                ->with('success', 'تم تحديث بيانات الطلب بنجاح.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'فشل تحديث الطلب: '.$e->getMessage());
        }

    }

    /**
     * Confirm a draft unified request.
     */
    public function confirmDraft(ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            $user->isAdmin()

            || (int) $user->id === (int) $unifiedRequest->created_by

            || $user->hasPermission('project_requests.edit', $unifiedRequest->project)

            || $user->hasPermission('project_requests.submit', $unifiedRequest->project),

            403

        );

        if (! $unifiedRequest->canBeConfirmed()) {

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('error', 'يمكن فقط تأكيد الطلبات التي في حالة المسودة.');

        }

        try {

            $this->unifiedRequestService->confirmDraft($unifiedRequest, $user);

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('success', 'تم تأكيد المسودة بنجاح! يمكنك الآن مراجعتها وإرسال الطلب للاعتماد.');

        } catch (\Throwable $e) {

            return redirect()->back()->with('error', 'فشل تأكيد المسودة: '.$e->getMessage());

        }

    }

    /**
     * Revert a confirmed request back to draft.
     */
    public function revertToDraft(ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            $user->isAdmin()

            || (int) $user->id === (int) $unifiedRequest->created_by

            || $user->hasPermission('project_requests.edit', $unifiedRequest->project),

            403

        );

        if (! $unifiedRequest->isConfirmed()) {

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('error', 'يمكن فقط إعادة الطلبات المؤكدة إلى حالة مسودة.');

        }

        try {

            $this->unifiedRequestService->revertToDraft($unifiedRequest, $user);

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('success', 'تمت إعادة الطلب إلى مسودة بنجاح لتعديل البيانات.');

        } catch (\Throwable $e) {

            return redirect()->back()->with('error', 'فشل إعادة الطلب إلى مسودة: '.$e->getMessage());

        }

    }

    /**
     * Submit a confirmed or draft unified request for approval.
     */
    public function submit(ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            $user->isAdmin()

            || (int) $user->id === (int) $unifiedRequest->created_by

            || $user->hasPermission('project_requests.submit', $unifiedRequest->project)
            || $user->hasPermission('project_requests.edit', $unifiedRequest->project)
            || $user->hasPermission('payments.create', $unifiedRequest->project)
            || $user->hasPermission('clearances.create', $unifiedRequest->project),

            403

        );

        if (! $unifiedRequest->canBeSubmitted()) {

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('error', 'يجب تأكيد المسودة أولاً قبل إرسال الطلب للاعتماد.');

        }

        try {

            $this->unifiedRequestService->submitRequest($unifiedRequest, $user);

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('success', 'تم إرسال الطلب للاعتماد بنجاح وتفعيل مسار الموافقات، ويظهر الآن في مركز الاعتمادات.');

        } catch (\Throwable $e) {

            return redirect()->back()->with('error', 'فشل تقديم الطلب: '.$e->getMessage());

        }

    }

    /**
     * Approve the active step in the request's approval chain.
     */
    public function approveStep(Request $request, ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            ($user->isAdmin() || $unifiedRequest->canUserActOnCurrentStep($user))
                && $unifiedRequest->activeApprovalStep()?->isActionAllowed('approve'),

            403

        );

        $request->validate([

            'notes' => 'nullable|string|max:1000',

            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',

        ]);

        try {

            $result = $this->unifiedRequestService->approveStep(

                $unifiedRequest,

                $user,

                $request->input('notes'),

                $request->file('attachment')

            );

            if ($request->wantsJson() || $request->ajax()) {

                return response()->json([

                    'success' => true,

                    'message' => $result['message'],

                    'is_final' => $result['is_final'],

                ]);

            }

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('success', $result['message']);

        } catch (\Throwable $e) {

            if ($request->wantsJson() || $request->ajax()) {

                return response()->json([

                    'success' => false,

                    'message' => 'فشل اعتماد المرحلة: '.$e->getMessage(),

                ], 422);

            }

            return redirect()->back()->with('error', 'فشل اعتماد المرحلة: '.$e->getMessage());

        }

    }

    /**
     * Reject the active step and reject the unified project request.
     */
    public function rejectStep(Request $request, ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            ($user->isAdmin() || $unifiedRequest->canUserActOnCurrentStep($user))
                && $unifiedRequest->activeApprovalStep()?->isActionAllowed('reject'),

            403

        );

        $request->validate([

            'rejection_reason' => 'required|string|min:3|max:1000',

        ], [

            'rejection_reason.required' => 'سبب الرفض إلزامي.',

            'rejection_reason.min' => 'سبب الرفض يجب أن يحتوي على 3 أحرف على الأقل.',

        ]);

        try {

            $result = $this->unifiedRequestService->rejectStep(

                $unifiedRequest,

                $user,

                $request->input('rejection_reason')

            );

            if ($request->wantsJson() || $request->ajax()) {

                return response()->json([

                    'success' => true,

                    'message' => $result['message'],

                ]);

            }

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('warning', $result['message']);

        } catch (\Throwable $e) {

            if ($request->wantsJson() || $request->ajax()) {

                return response()->json([

                    'success' => false,

                    'message' => 'فشل رفض الطلب: '.$e->getMessage(),

                ], 422);

            }

            return redirect()->back()->with('error', 'فشل رفض الطلب: '.$e->getMessage());

        }

    }

    /**
     * Return the active step for revision / correction (requires action).
     */
    public function returnStep(Request $request, ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            ($user->isAdmin() || $unifiedRequest->canUserActOnCurrentStep($user))
                && $unifiedRequest->activeApprovalStep()?->isActionAllowed('return'),

            403

        );

        $request->validate([

            'action_required' => 'required|string|min:3|max:1000',

        ], [

            'action_required.required' => 'بيان الإجراء المطلوب أو أسباب الإعادة إلزامي.',

            'action_required.min' => 'بيان الإجراء المطلوب يجب أن يحتوي على 3 أحرف على الأقل.',

        ]);

        try {

            $result = $this->unifiedRequestService->returnStep(

                $unifiedRequest,

                $user,

                $request->input('action_required')

            );

            if ($request->wantsJson() || $request->ajax()) {

                return response()->json([

                    'success' => true,

                    'message' => $result['message'],

                ]);

            }

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('warning', $result['message']);

        } catch (\Throwable $e) {

            if ($request->wantsJson() || $request->ajax()) {

                return response()->json([

                    'success' => false,

                    'message' => 'فشل إعادة الطلب: '.$e->getMessage(),

                ], 422);

            }

            return redirect()->back()->with('error', 'فشل إعادة الطلب: '.$e->getMessage());

        }

    }

    /**
     * Approve a unified project request directly (backward compatibility / admin override) and execute operations.
     */
    public function approve(Request $request, ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            $user->isAdmin()

            || $user->hasPermission('project_requests.approve', $unifiedRequest->project)

            || $user->hasPermission('payments.approve', $unifiedRequest->project)

            || $user->hasPermission('clearances.approve', $unifiedRequest->project)

            || $user->hasPermission('disbursements.approve', $unifiedRequest->project),

            403

        );

        if ($unifiedRequest->status !== ProjectUnifiedRequest::STATUS_PENDING) {

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('error', 'يمكن فقط اعتماد الطلبات التي في حالة الانتظار.');

        }

        try {

            $notes = $request->input('notes');

            $this->unifiedRequestService->approveRequest($unifiedRequest, $user, $notes);

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('success', 'تم اعتماد الطلب بنجاح وتنفيذ الإجراءات المالية والإدارية المرتبطة به.');

        } catch (\Throwable $e) {

            return redirect()->back()->with('error', 'فشل اعتماد الطلب: '.$e->getMessage());

        }

    }

    /**
     * Reject a unified project request directly (backward compatibility / admin override).
     */
    public function reject(Request $request, ProjectUnifiedRequest $unifiedRequest)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            $user->isAdmin()

            || $user->hasPermission('project_requests.reject', $unifiedRequest->project)

            || $user->hasPermission('payments.reject', $unifiedRequest->project)

            || $user->hasPermission('clearances.reject', $unifiedRequest->project),

            403

        );

        if ($unifiedRequest->status !== ProjectUnifiedRequest::STATUS_PENDING) {

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('error', 'يمكن فقط رفض الطلبات التي في حالة الانتظار.');

        }

        $request->validate([

            'rejection_reason' => 'required|string|min:5|max:1000',

        ], [

            'rejection_reason.required' => 'سبب الرفض إلزامي.',

            'rejection_reason.min' => 'سبب الرفض يجب أن يحتوي على 5 أحرف على الأقل.',

        ]);

        try {

            $this->unifiedRequestService->rejectRequest($unifiedRequest, $user, $request->input('rejection_reason'));

            return redirect()->route('projects.requests.show', $unifiedRequest)

                ->with('warning', 'تم رفض الطلب بنجاح وإعادة الموارد المرتبطة لحالتها السابقة.');

        } catch (\Throwable $e) {

            return redirect()->back()->with('error', 'فشل رفض الطلب: '.$e->getMessage());

        }

    }

    /**
     * Generate and display official Arabic print document with letterhead.
     */
    public function print(ProjectUnifiedRequest $unifiedRequest, LetterheadAccessService $letterheadService)
    {

        $user = auth()->user();

        $this->projectService->authorizeProjectAccess($unifiedRequest->project, $user);

        abort_unless(

            $user->isAdmin()

            || $user->hasPermission('project_requests.print', $unifiedRequest->project)

            || $user->hasPermission('payments.view', $unifiedRequest->project)

            || $user->hasPermission('clearances.view', $unifiedRequest->project),

            403

        );

        $unifiedRequest->load([

            'project.disbursementPlan',

            'submittingEntity',

            'submittingAuthority',

            'submittingUser',

            'creator',

            'approvedBy',

            'clearanceInstallment',

            'paymentInstallment',

        ]);

        // Resolve effective letterhead

        $orgType = $unifiedRequest->submitting_type;

        $orgId = $orgType === 'external' ? $unifiedRequest->submitting_authority_id : $unifiedRequest->submitting_entity_id;

        $resolution = $letterheadService->resolveEffectiveLetterhead($user, $orgType, $orgId);

        $letterhead = $resolution->letterhead;

        $arabicPaymentAmount = $unifiedRequest->hasPaymentComponent()

            ? $this->financialWorkflowService->toArabicWords($unifiedRequest->requested_payment_amount)

            : null;

        $arabicClearedAmount = $unifiedRequest->hasClearanceComponent()

            ? $this->financialWorkflowService->toArabicWords($unifiedRequest->cleared_amount)

            : null;

        $printDate = app(HijriDateService::class)->toHijri(now());
        $requestDateHijri = app(HijriDateService::class)->toHijri($unifiedRequest->request_date);

        return view('projects.unified_requests.print', compact(

            'unifiedRequest',

            'letterhead',

            'arabicPaymentAmount',

            'arabicClearedAmount',

            'printDate',
            'requestDateHijri'

        ));

    }

    /**
     * AJAX Endpoint: Retrieve eligible users belonging to the selected submitting entity.
     */
    public function getEntityUsers(Request $request): JsonResponse
    {

        $user = auth()->user();

        abort_unless(
            $user->isAdmin()
                || $user->hasPermission('project_requests.create')
                || $user->hasPermission('payments.create')
                || $user->hasPermission('clearances.create')
                || $user->hasPermission('disbursements.create'),
            403
        );

        $request->validate([

            'type' => ['required', 'string', 'in:internal,external'],

            'entity_id' => ['required', 'integer'],

        ]);

        try {

            $users = $this->unifiedRequestService->getEligibleUsersForEntity(

                $request->input('type'),

                (int) $request->input('entity_id'),

                $user

            );

            return response()->json([

                'success' => true,

                'users' => $users,

            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

                'users' => [],

            ], 422);

        }

    }

    /**
     * AJAX Endpoint: Retrieve financial balances and installment data for the selected project.
     */
    public function getProjectFinancials(Project $project): JsonResponse
    {

        $user = auth()->user();
        $this->projectService->authorizeProjectAccess($project, $user);

        abort_unless(
            $user->isAdmin()
                || $user->hasPermission('project_requests.create', $project)
                || $user->hasPermission('payments.create', $project)
                || $user->hasPermission('clearances.create', $project)
                || $user->hasPermission('disbursements.create', $project),
            403
        );

        $data = $this->unifiedRequestService->getProjectFinancialData($project);

        return response()->json([

            'success' => true,

            'data' => $data,

        ]);

    }
}

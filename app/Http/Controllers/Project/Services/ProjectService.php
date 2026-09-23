<?php

namespace App\Http\Controllers\Project\Services;

use App\Http\Controllers\Project\Components\ProjectDataNormalizer;
use App\Http\Controllers\Project\ProjectFinancingsController;
use App\Models\Authority;
use App\Models\BeneficiaryGroup;
use App\Models\Domain;
use App\Models\EmpowermentProject;
use App\Models\ExecutiveActionCost;
use App\Models\FinancialItem;
use App\Models\FinancingForm;
use App\Models\FinancingType;
use App\Models\FundingSource;
use App\Models\Governorate;
use App\Models\InternalEntity;
use App\Models\Intervention;
use App\Models\PreliminaryProcedureExecution;
use App\Models\Priority;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProjectDetail;
use App\Models\ProjectExecution;
use App\Models\ProjectImplementingEntity;
use App\Models\ProjectLocation;
use App\Models\ProjectSupervisingAuthority;
use App\Models\Subdomain;
use App\Models\SubFinancingForm;
use App\Models\Task;
use App\Models\Unit;
use App\Models\User;
use App\Scopes\DomainScope;
use App\Services\ApprovalService;
use App\Services\EntityHierarchyService;
use App\Services\ErpNextReportService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectService
{
    protected EntityHierarchyService $entityHierarchyService;

    protected ApprovalService $approvalService;

    public function __construct(EntityHierarchyService $entityHierarchyService, ApprovalService $approvalService)
    {
        $this->entityHierarchyService = $entityHierarchyService;
        $this->approvalService = $approvalService;
    }

    /**
     * Get relations needed for detailed project view
     */
    public function getProjectDetailedRelations(): array
    {
        return [
            'program',
            'domain',
            'subdomain',
            'intervention',
            'priority',
            'targetCategory',
            'detail',
            'locations.governorate.districts',
            'locations.directorate.subAreas',
            'locations.subArea.villages',
            'locations.village',
            'mainObjectives',
            'specialObjectives.results.outputs',
            'objectiveResults',
            'resultOutputs.objectiveResult',
            'resultOutputs.specialObjective',
            'risks',
            'cost',
            'preliminaryActivities.procedures.costs.financialItem',
            'preliminaryActivities.procedures.costs.unit',
            'executiveActivities.actions.costs.financialItem',
            'executiveActivities.actions.costs.unit',
            'executiveActivities.resultOutput',
            'executiveActivities.projectRisk',
            'financings',
            'financings.fundingSource',
            'financings.authority',
            'financings.financingType',
            'financings.financingForm',
            'financings.subFinancingForm',
            'supervisingAuthorities.authority',
            'supervisingAuthorities.parent',
            'supervisingAuthorities.internalEntity',
            'supervisingAuthorities.parentInternalEntity',
            'implementingEntities.authority',
            'implementingEntities.parent',
            'implementingEntities.internalEntity',
            'implementingEntities.parentInternalEntity',
            'participatingEntities.authority',
            'participatingEntities.parent',
            'participatingEntities.internalEntity',
            'participatingEntities.parentInternalEntity',
            'beneficiaryEntities.authority',
            'beneficiaryEntities.parent',
            'beneficiaryEntities.internalEntity',
            'beneficiaryEntities.parentInternalEntity',
            'beneficiaryGroups',
            'preliminaryActivities.procedures.costs.financialItem',
            'preliminaryActivities.procedures.costs.unit',
            'preliminaryFinancialSummaries.financialItem',
            'preliminaryFinancialSummaries.activity',
            'preliminaryFinancialSummaries.procedure',
            'preliminaryFinancialSummaries.cost',
            'executiveActivities.actions.assignedEntities',
            'executiveActivities.actions.costs.financialItem',
            'executiveActivities.actions.costs.unit',
            'executiveFinancialSummaries',
            'transactions.user',
            'projectApprovals.entity',
            'projectApprovals.createdBy',
            'documents',
            'activityHistory.user',
            'projectEntities',
            'authority',
            'creatorEntity',
            'internalEntity',
        ];
    }

    /**
     * Get all active domains
     */
    public function getDomains()
    {
        return Domain::withoutGlobalScope(DomainScope::class)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get subdomains for a domain as JSON
     */
    public function getSubdomainsJson($domainId)
    {
        $subdomains = Subdomain::withoutGlobalScope(DomainScope::class)
            ->where('domain_id', $domainId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($subdomains);
    }

    /**
     * Get interventions for a subdomain as JSON
     */
    public function getInterventionsJson($subdomainId)
    {
        $interventions = Intervention::withoutGlobalScope(DomainScope::class)
            ->where('subdomain_id', $subdomainId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($interventions);
    }

    /**
     * Get row HTML for executive activities
     */
    public function getExecutiveActivityRow(Request $request)
    {
        $index = $request->input('index', 0);

        return view('projects.partials.rows.executive_activity', compact('index'))->render();
    }

    /**
     * Get row HTML for executive activity actions
     */
    public function getExecutiveActivityActionRow(Request $request)
    {
        $activityIndex = $request->input('activity_index', 0);
        $actionIndex = $request->input('action_index', 0);

        return view('projects.partials.rows.executive_action', compact('activityIndex', 'actionIndex'))->render();
    }

    /**
     * Get row HTML for executive action assigned entities
     */
    public function getExecutiveActionAssignedRow(Request $request)
    {
        $activityIndex = $request->input('activity_index', 0);
        $actionIndex = $request->input('action_index', 0);
        $assignedIndex = $request->input('assigned_index', 0);
        $authorities = Authority::all();

        return view('projects.partials.rows.executive_assigned', compact('activityIndex', 'actionIndex', 'assignedIndex', 'authorities'))->render();
    }

    /**
     * Get row HTML for executive action costs
     */
    public function getExecutiveActionCostRow(Request $request)
    {
        $activityIndex = $request->input('activity_index', 0);
        $actionIndex = $request->input('action_index', 0);
        $costIndex = $request->input('cost_index', 0);
        $financialItems = FinancialItem::where('is_active', true)->orderBy('name')->get();

        return view('projects.partials.rows.executive_cost', compact('activityIndex', 'actionIndex', 'costIndex', 'financialItems'))->render();
    }

    /**
     * Get row HTML for financings
     */
    public function getFinancingRow(Request $request)
    {
        $index = $request->input('index', 0);
        $projectFinancingsController = new ProjectFinancingsController;
        $lookups = $projectFinancingsController->getFinancingLookups();

        return view('projects.partials.rows.financing', array_merge(['index' => $index], $lookups))->render();
    }

    /**
     * Get common form data for creating/editing projects
     */
    public function getCommonFormData(): array
    {
        $govQuery = Governorate::withoutGlobalScope(DomainScope::class)->where('is_active', true)->select('id', 'name')->orderBy('name');

        // For project forms, we load both approved (status=1) AND pending/draft (status=0) records.
        // This ensures a project referencing a still-pending lookup record can still render its form
        // without that option appearing blank. The approval of lookup records is independent from
        // the project completion workflow.
        return [
            'programs' => Program::withoutGlobalScopes()->whereIn('status', [0, 1])->select('id', 'name')->get(),
            'domains' => Domain::withoutGlobalScope(DomainScope::class)->withoutGlobalScope('active_only')->whereIn('status', [0, 1])->select('id', 'name')->orderBy('name')->get(),
            'subdomains' => collect(), // Will be populated dynamically via AJAX or pre-loaded in edit
            'interventions' => collect(), // Will be populated dynamically via AJAX or pre-loaded in edit
            'governorates' => $govQuery->get(),
            'fundingSources' => FundingSource::withoutGlobalScope('active_only')->select('id', 'name')->orderBy('name')->get(),
            'financingTypes' => FinancingType::withoutGlobalScope('active_only')->whereIn('status', [0, 1])->select('id', 'name')->orderBy('name')->get(),
            'financingForms' => FinancingForm::withoutGlobalScope('active_only')->select('id', 'name')->orderBy('name')->get(),
            'subFinancingForms' => SubFinancingForm::withoutGlobalScope('active_only')->select('id', 'name')->orderBy('name')->get(),
            'priorities' => Priority::withoutGlobalScope('active_only')->whereIn('status', [0, 1])->select('id', 'priority')->get(),
            'authorities' => Authority::withoutGlobalScope(DomainScope::class)->withoutGlobalScope('active_only')->whereIn('status', [0, 1])->select('id', 'agency_name', 'parent_id')->orderBy('agency_name')->get(),
            'internalEntities' => $this->getInternalEntitiesFromJson(),
            'financialItems' => FinancialItem::withoutGlobalScope('active_only')->whereIn('status', [0, 1])->select('id', 'name')->orderBy('name')->get(),
            'units' => Unit::withoutGlobalScope('active_only')->whereIn('status', [0, 1])->select('id', 'unit_name')->orderBy('unit_name')->get(),
            'beneficiaryGroups' => BeneficiaryGroup::withoutGlobalScope('active_only')->whereIn('status', [0, 1])->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /**
     * Get data for project creation form
     */
    public function getCreateData()
    {
        $data = $this->getCommonFormData();
        $projectFinancingsController = new ProjectFinancingsController;
        $data['financingLookups'] = $projectFinancingsController->getFinancingLookups();

        return view('projects.create', $data);
    }

    /**
     * Get data for project edit form
     */
    public function getEditData(Project $project)
    {
        $allowedStatuses = ['draft', 'completed_draft', 'rolled_back_for_review', 'financial_review', 'reviewed_completed'];

        if ($project->project_type !== 'old' && ! in_array($project->status, $allowedStatuses)) {
            session()->flash('error', 'لا يمكن فتح أو تعديل المسودة أثناء وجودها في مراحل الاعتماد والموافقة إلا بعد إعادتها إلى الجهة المنشئة.');

            return redirect()->route('projects.index');
        }

        $project->load($this->getProjectDetailedRelations());

        $data = $this->getCommonFormData();
        $projectFinancingsController = new ProjectFinancingsController;
        $data['financingLookups'] = $projectFinancingsController->getFinancingLookups();

        // Determine reviewer type for UI restrictions
        $data['reviewerType'] = $this->getCurrentReviewerType();
        $data['projectStatus'] = $project->status;

        // Pre-load subdomains if domain_id exists (include pending/draft records)
        if ($project->domain_id) {
            $data['subdomains'] = Subdomain::withoutGlobalScope(DomainScope::class)
                ->withoutGlobalScope('active_only')
                ->where('domain_id', $project->domain_id)
                ->whereIn('status', [0, 1])
                ->orderBy('name')
                ->get();
        }

        // Pre-load interventions if subdomain_id exists (include pending/draft records)
        if ($project->subdomain_id) {
            $data['interventions'] = Intervention::withoutGlobalScope(DomainScope::class)
                ->withoutGlobalScope('active_only')
                ->where('subdomain_id', $project->subdomain_id)
                ->whereIn('status', [0, 1])
                ->orderBy('name')
                ->get();
        }

        $data['project'] = $project;
        $data['isEdit'] = true;

        // Pass existing financings so _financing.blade.php renders them server-side
        $data['financings'] = $project->financings;

        // Pass cost summaries for display in step 6
        $data['preliminaryFinancialSummaries'] = $project->preliminaryFinancialSummaries;
        $data['preliminaryTotalCost'] = $project->preliminaryFinancialSummaries->sum('aggregated_total');

        // Always pass lastSavedStep (not only for drafts) so step indicator starts at correct step
        $data['lastSavedStep'] = $project->last_saved_step ?? 1;
        $data['isDraft'] = ($project->status === 'draft');

        return view('projects.edit', $data);
    }

    /**
     * Get data for completing project details (Domain, Locations, Entities)
     */
    public function completeData(Project $project)
    {
        // قائمة الحالات المسموح بها
        $allowedStatuses = ['draft', 'financial_review', 'reviewed_completed'];

        // التحقق: المشاريع القديمة التي تم إكمال بياناتها مسبقاً
        if ($project->project_type === 'old' && $project->is_data_completed) {
            return redirect()
                ->route('projects.show', $project->id)
                ->with('info', 'تم استكمال بيانات هذا المشروع القديم بالفعل ولا يمكن تعديلها مجدداً.');
        }

        // التحقق: المشاريع الجديدة يجب أن تكون في الحالات المسموحة
        if ($project->project_type !== 'old' && ! in_array($project->status, $allowedStatuses)) {
            return redirect()
                ->route('projects.index')
                ->with('error', 'فقط المشاريع المسودة أو التي تحت المراجعة يمكن تعديلها.');
        }

        // تحميل العلاقات المطلوبة
        $project->load($this->getProjectDetailedRelations());

        // الحصول على البيانات المشتركة للنماذج
        $data = $this->getCommonFormData();

        // تحميل الـ Subdomains إذا كان الـ Domain ID موجوداً (بما في ذلك السجلات المعلقة)
        if ($project->domain_id) {
            $data['subdomains'] = Subdomain::withoutGlobalScope(DomainScope::class)
                ->withoutGlobalScope('active_only')
                ->where('domain_id', $project->domain_id)
                ->whereIn('status', [0, 1])
                ->orderBy('name')
                ->get();
        }

        // تحميل الـ Interventions إذا كان الـ Subdomain ID موجوداً (بما في ذلك السجلات المعلقة)
        if ($project->subdomain_id) {
            $data['interventions'] = Intervention::withoutGlobalScope(DomainScope::class)
                ->withoutGlobalScope('active_only')
                ->where('subdomain_id', $project->subdomain_id)
                ->whereIn('status', [0, 1])
                ->orderBy('name')
                ->get();
        }

        // إضافة بيانات المشروع للعرض
        $data['project'] = $project;
        $data['isEdit'] = true;

        return view('projects.complete-data', $data);
    }

    /**
     * Compatibility shim for older callers/tests.
     * The real filtering logic now lives inside getProjects.
     */
    protected function applyProjectRequestFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('form_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('program')) {
            $query->where('program_id', $request->input('program'));
        }

        if ($request->filled('domain')) {
            $query->where('domain_id', $request->input('domain'));
        }

        if ($request->filled('subdomain')) {
            $query->where('subdomain_id', $request->input('subdomain'));
        }

        if ($request->filled('intervention')) {
            $query->where('intervention_id', $request->input('intervention'));
        }

        if ($request->filled('project_type')) {
            $query->where('project_type', $request->input('project_type'));
        }

        if ($request->filled('governorate')) {
            $governorate = $request->input('governorate');
            $query->whereHas('locations', function ($q) use ($governorate) {
                $q->where('governorate_id', $governorate);
            });
        }

        if ($request->filled('organization') || $request->filled('entity')) {
            $orgInput = trim($request->input('organization', $request->input('entity')));

            $normalizedId = is_numeric($orgInput) ? (int) $orgInput : null;
            $organizationName = ! is_numeric($orgInput) ? $orgInput : null;

            if ($normalizedId && ! $organizationName) {
                $organizationName = DB::table('internal_entities')
                    ->where('id', $normalizedId)
                    ->value('name');
            }

            $query->where(function ($q) use ($normalizedId, $organizationName, $orgInput): void {
                if ($normalizedId !== null) {
                    $q->where('creator_entity_id', $normalizedId)
                        ->orWhere('internal_entity_id', $normalizedId);
                }

                $q->orWhere('created_by_entity', (string) $orgInput);

                if (! empty($organizationName)) {
                    $cleanName = trim(preg_replace('/\s+/u', ' ', $organizationName));

                    $q->orWhere('created_by_entity', 'like', "%{$cleanName}%")
                        ->orWhereHas('creatorEntity', function ($entityQ) use ($cleanName): void {
                            $entityQ->where('name', 'like', "%{$cleanName}%");
                        })
                        ->orWhereHas('internalEntity', function ($entityQ) use ($cleanName): void {
                            $entityQ->where('name', 'like', "%{$cleanName}%");
                        });
                }
            });
        }

        if ($request->filled('hijri_year')) {
            $hijriYear = $request->input('hijri_year');
            $query->where('form_number', 'like', "PRO{$hijriYear}%");
        }

        return $query;
    }

    /**
     * Build the shared project listing query for the main listing and related views.
     * This delegates to getProjects so the filtering logic remains centralized there.
     */
    public function buildProjectsQuery(Request $request, ?array $statusFilters = null): Builder
    {
        return $this->getProjects($request, $statusFilters, null, [], true);
    }

    /**
     * Centralized Query: Apply accessibility rules to any projects query.
     * Integrates Admin full-access, external authority isolation, and internal ministry scope/workflow access.
     */
    public function applyAccessibleProjectsQuery(Builder $query, ?User $user = null, ?Request $request = null): Builder
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        // 1. Admin / Full Access
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return $query;
        }

        // 2. External Authority User (Strict Authority Isolation)
        if ($user->isExternal() || ($user->organization_type === 'external' && $user->authority_id)) {
            $userAuthorityId = (int) $user->authority_id;
            $hasWorkflowResponsibility = ! empty($user->responsibility);

            $query->where(function ($q) use ($user, $userAuthorityId, $hasWorkflowResponsibility) {
                // User always sees projects they personally created
                $q->where('projects.created_by_user_id', $user->id);

                if ($hasWorkflowResponsibility) {
                    // Authority user with responsibility sees all projects under their authority
                    $q->orWhere('projects.authority_id', $userAuthorityId);
                } else {
                    $authorityName = DB::table('authorities')->where('id', $userAuthorityId)->value('agency_name');
                    if ($authorityName) {
                        $q->orWhere('projects.created_by_entity', trim($authorityName));
                    }
                    $q->orWhere('projects.authority_id', $userAuthorityId);
                }
            });

            return $query;
        }

        // 3. Internal Ministry User
        $activeAdminScopeId = session('selected_administrative_scope_id', $user->administrative_scope_id);
        $entityIdsByEnt = $activeAdminScopeId ? InternalEntity::getAllChildrenIds($activeAdminScopeId) : [];

        $activeGeoScopes = session('selected_geographic_scopes');
        if ($activeGeoScopes !== null) {
            $entityIdsByGovAndDist = collect($activeGeoScopes)->map(fn ($s) => (object) $s);
        } else {
            $entityIdsByGovAndDist = $user->geographicScopes ?? collect();
        }

        $entityIdsByGeo = [];
        foreach ($entityIdsByGovAndDist as $scope) {
            $govId = is_array($scope) ? ($scope['governorate_id'] ?? null) : ($scope->governorate_id ?? null);
            $dirId = is_array($scope) ? ($scope['directorate_id'] ?? null) : ($scope->directorate_id ?? null);

            if (! empty($dirId)) {
                $ids = InternalEntity::getAllByDirectorate($dirId);
                $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
            } elseif (! empty($govId) || $govId === 'all' || $govId === 0 || $govId === '0') {
                if ($govId === 'all' || $govId === 0 || $govId === '0') {
                    $ids = DB::table('internal_entities')->pluck('id')->map(fn ($id) => (int) $id)->toArray();
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                } else {
                    $ids = InternalEntity::getAllByGovernorate($govId);
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                }
            }
        }

        $activeEntityId = session('selected_entity_id', $user->entity_id);
        $entityIdsByMyEnt = [];
        if (empty($entityIdsByEnt) && empty($entityIdsByGeo)) {
            $entityIdsByMyEnt = $activeEntityId ? InternalEntity::getAllChildrenIds($activeEntityId) : [];
        }

        $entityIds = array_unique(array_merge($entityIdsByEnt, $entityIdsByMyEnt, $entityIdsByGeo));
        $entityIds = array_values(array_filter($entityIds, fn ($id) => is_numeric($id) && $id > 0));

        if (! empty($entityIds)) {
            $entityNames = DB::table('internal_entities')->whereIn('id', $entityIds)->pluck('name')->filter()->toArray();
            $cleanEntityNames = array_filter(array_map(fn ($name) => trim(preg_replace('/\s+/u', ' ', $name)), $entityNames));

            $query->where(function ($q) use ($entityIds, $cleanEntityNames, $user) {
                $q->whereIn('projects.creator_entity_id', $entityIds)
                    ->orWhereIn('projects.internal_entity_id', $entityIds)
                    ->orWhere('projects.created_by_user_id', $user->id);

                if (! empty($cleanEntityNames)) {
                    $q->orWhereIn('projects.created_by_entity', $cleanEntityNames);
                }

                // Internal Ministry reviewer/approver can see external projects assigned to or in workflow at their entity
                $q->orWhereHas('projectApprovals', function ($appQ) use ($entityIds) {
                    $appQ->whereIn('project_approvals.entity_id', $entityIds);
                });
            });
        } else {
            $userEntityId = $user->entity_id;
            $userEntityName = $user->entity?->name;
            $cleanEntName = $userEntityName ? trim(preg_replace('/\s+/u', ' ', $userEntityName)) : null;

            $query->where(function ($q) use ($user, $userEntityId, $cleanEntName) {
                $q->where('projects.created_by_user_id', $user->id);
                if (! empty($userEntityId)) {
                    $q->orWhere('projects.creator_entity_id', $userEntityId)
                        ->orWhere('projects.internal_entity_id', $userEntityId)
                        ->orWhereHas('projectApprovals', function ($appQ) use ($userEntityId) {
                            $appQ->where('project_approvals.entity_id', $userEntityId);
                        });
                }
                if (! empty($cleanEntName)) {
                    $q->orWhere('projects.created_by_entity', $cleanEntName);
                }
            });
        }

        return $query;
    }

    /**
     * Authorize project access for a user. Throws 403 if unauthorized.
     */
    public function authorizeProjectAccess(Project $project, ?User $user = null): void
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            abort(401, 'يجب تسجيل الدخول أولاً');
        }

        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return;
        }

        $query = Project::withoutGlobalScopes()->where('projects.id', $project->id);
        $isAllowed = $this->applyAccessibleProjectsQuery($query, $user)->exists();

        if (! $isAllowed) {
            abort(403, 'ليس لديك الصلاحية لعرض أو الوصول إلى هذا المشروع');
        }
    }

    /**
     * Get projects with filters and pagination
     *
     * @return View|Builder
     */
    public function getProjects(Request $request, ?array $statusFilters = null, ?string $viewName = 'projects.index', array $extraViewData = [], bool $returnQueryOnly = false, bool $skipEagerLoading = false)
    {
        $user = auth()->user();

        // ================================================================
        // 1. بناء الاستعلام الأساسي مع العلاقات والعدادات
        // ================================================================

        $query = Project::withoutGlobalScopes();

        if (! $skipEagerLoading) {
            $eagerLoads = [
                'program',
                'domain',
                'subdomain',
                'intervention',
                'createdBy',
                'detail',
                'cost',
                'currentApprovalStage',
                'projectApprovals',
                'supervisingAuthorities',
                'implementingEntities',
                'authority',
                'creatorEntity',
            ];
            $query->with($eagerLoads)->withCount([
                'mainObjectives',
                'specialObjectives',
                'supervisingAuthorities',
                'implementingEntities',
                'preliminaryActivities',
                'executiveActivities',
                'financings',
            ]);
        }

        // ================================================================
        // 2. تطبيق فلترة الوصول الموحدة
        // ================================================================

        $this->applyAccessibleProjectsQuery($query, $user, $request);

        // ================================================================
        // 3. تطبيق الفلاتر الاختيارية من الطلب
        // ================================================================

        if (! empty($statusFilters)) {
            $query->whereIn('status', $statusFilters);
        }

        if ($request->filled('search')) {
            $searchString = trim($request->input('search'));
            $words = array_filter(explode(' ', $searchString));

            $query->where(function ($q) use ($searchString, $words) {
                // البحث برقم النموذج بالكلمة كاملة
                $q->where('form_number', 'like', "%{$searchString}%");

                // البحث باسم المشروع بجميع الكلمات في أي ترتيب
                $q->orWhere(function ($subQ) use ($words) {
                    foreach ($words as $word) {
                        $subQ->where('project_name', 'like', "%{$word}%");
                    }
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('program')) {
            $query->where('program_id', $request->input('program'));
        }

        if ($request->filled('domain')) {
            $query->where('domain_id', $request->input('domain'));
        }

        if ($request->filled('subdomain')) {
            $query->where('subdomain_id', $request->input('subdomain'));
        }

        if ($request->filled('intervention')) {
            $query->where('intervention_id', $request->input('intervention'));
        }

        if ($request->filled('project_type')) {
            $query->where('project_type', $request->input('project_type'));
        }

        if ($request->filled('governorate')) {
            $governorate = $request->input('governorate');
            $query->whereHas('locations', function ($q) use ($governorate) {
                $q->where('governorate_id', $governorate);
            });
        }

        if ($request->filled('organization') || $request->filled('entity')) {
            $orgInput = trim($request->input('organization', $request->input('entity')));

            $normalizedId = is_numeric($orgInput) ? (int) $orgInput : null;
            $organizationName = ! is_numeric($orgInput) ? $orgInput : null;

            if ($normalizedId && ! $organizationName) {
                $organizationName = DB::table('internal_entities')
                    ->where('id', $normalizedId)
                    ->value('name');
            }

            $query->where(function ($q) use ($normalizedId, $organizationName, $orgInput) {
                if ($normalizedId !== null) {
                    $q->where('creator_entity_id', $normalizedId)
                        ->orWhere('internal_entity_id', $normalizedId);
                }

                $q->orWhere('created_by_entity', (string) $orgInput);

                if (! empty($organizationName)) {
                    $cleanName = trim(preg_replace('/\s+/u', ' ', $organizationName));

                    $q->orWhere('created_by_entity', 'like', "%{$cleanName}%")
                        ->orWhereHas('creatorEntity', function ($entityQ) use ($cleanName) {
                            $entityQ->where('name', 'like', "%{$cleanName}%");
                        })
                        ->orWhereHas('internalEntity', function ($entityQ) use ($cleanName) {
                            $entityQ->where('name', 'like', "%{$cleanName}%");
                        });
                }
            });
        }

        if ($request->filled('hijri_year')) {
            $hijriYear = $request->input('hijri_year');
            $query->where('form_number', 'like', "PRO{$hijriYear}%");
        }

        // ================================================================
        // 4. تطبيق الترتيب
        // ================================================================

        $sortBy = $request->input('sort_by', 'creator_entity_id');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'form_number',
            'project_name',
            'project_type',
            'status',
            'creator_entity_id',
            'created_at',
            'hijri_year',
        ];

        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'creator_entity_id';
        }

        if ($sortBy === 'form_number') {
            $query->orderByRaw('CASE WHEN projects.form_number IS NULL OR projects.form_number = "" THEN 1 ELSE 0 END')
                ->orderBy('projects.form_number', $sortOrder)
                ->orderBy('projects.id', 'desc');
        } elseif ($sortBy === 'hijri_year') {
            $query->orderByRaw("SUBSTRING(projects.form_number, 4, 4) {$sortOrder}")
                ->orderBy('projects.id', 'desc');
        } else {
            $query->orderBy("projects.{$sortBy}", $sortOrder)
                ->orderBy('projects.id', 'desc');
        }

        // ================================================================
        // 5. التقسيم إلى صفحات أو إرجاع الاستعلام فقط
        // ================================================================

        $perPage = in_array((int) $request->input('per_page'), [15, 50, 100, 500])
            ? (int) $request->input('per_page')
            : 15;

        if ($returnQueryOnly) {
            return $query;
        }

        // تنفيذ الاستعلام مع التصفح
        $projects = $query->select('projects.*')->paginate($perPage)->withQueryString();

        // ================================================================
        // 6. جلب البيانات اللازمة لقوائم الفلاتر (Dropdowns)
        //    ملاحظة: تم تبسيط جلب الخيارات لعرض كل الخيارات المتاحة
        // ================================================================

        // قوائم الفلاتر شبه ثابتة، لذا نخزّنها مؤقتًا (15 دقيقة) لتجنّب إعادة جلبها في كل طلب.
        $cacheTtl = now()->addMinutes(15);

        $usedProgramIds = Cache::remember('filter_dropdown_used_program_ids', $cacheTtl, function () {
            return Project::withoutGlobalScopes()->whereNotNull('program_id')->distinct()->pluck('program_id');
        });
        $programs = Cache::remember('filter_dropdown_programs', $cacheTtl, function () use ($usedProgramIds) {
            return Program::whereIn('id', $usedProgramIds)->select('id', 'name')->orderBy('name')->get();
        });

        $usedDomainIds = Cache::remember('filter_dropdown_used_domain_ids', $cacheTtl, function () {
            return Project::withoutGlobalScopes()->whereNotNull('domain_id')->distinct()->pluck('domain_id');
        });
        $domains = Cache::remember('filter_dropdown_domains', $cacheTtl, function () use ($usedDomainIds) {
            return Domain::whereIn('id', $usedDomainIds)->select('id', 'name')->orderBy('name')->get();
        });

        $subdomains = collect();
        if ($request->filled('domain')) {
            $domainId = $request->input('domain');
            $subdomains = Cache::remember('filter_dropdown_subdomains_'.$domainId, $cacheTtl, function () use ($domainId) {
                return Subdomain::where('domain_id', $domainId)
                    ->select('id', 'name', 'domain_id')
                    ->get();
            });
        }

        $interventions = collect();
        if ($request->filled('subdomain')) {
            $subdomainId = $request->input('subdomain');
            $interventions = Cache::remember('filter_dropdown_interventions_'.$subdomainId, $cacheTtl, function () use ($subdomainId) {
                return Intervention::where('subdomain_id', $subdomainId)
                    ->select('id', 'name', 'subdomain_id')
                    ->get();
            });
        }

        $authorities = Cache::remember('filter_dropdown_authorities', $cacheTtl, function () {
            return Authority::select('id', 'agency_name as name')->get();
        });

        $governorates = Cache::remember('filter_dropdown_governorates', $cacheTtl, function () {
            return Governorate::whereIn(
                'id',
                ProjectLocation::whereNotNull('governorate_id')
                    ->distinct()
                    ->pluck('governorate_id')
            )->select('id', 'name')->orderBy('name')->get();
        });

        $hijriYears = Cache::remember('filter_dropdown_hijri_years', $cacheTtl, function () {
            return Project::withoutGlobalScopes()
                ->where('form_number', 'like', 'PRO%')
                ->selectRaw('SUBSTRING(form_number, 4, 4) as year')
                ->distinct()
                ->pluck('year')
                ->filter()
                ->sortDesc()
                ->values();
        });

        $entitiesQuery = DB::table('internal_entities')->orderBy('name')->select('id', 'name');
        if (! $user->isAdmin() || ! empty($entityIds)) {
            if (! empty($entityIds)) {
                $entitiesQuery->whereIn('id', $entityIds);
            } else {
                $entitiesQuery->where('id', $user->entity_id);
            }
        }
        $entities = $entitiesQuery->get();

        $allStatuses = [
            'draft' => 'مسودة',
            'pending' => 'قيد المراجعة',
            'under_review' => 'تحت الدراسة',
            'approved' => 'معتمد',
            'internally_approved' => 'معتمد داخلياً',
            'in_progress' => 'جارٍ التنفيذ',
            'in_execution' => 'قيد التنفيذ',
            'implementation' => 'مرحلة التنفيذ',
            'completed' => 'مكتمل',
            'suspended' => 'موقوف',
            'cancelled' => 'ملغي',
            'rejected' => 'مرفوض',
        ];

        $usedStatusKeys = Cache::remember('filter_dropdown_used_status_keys', $cacheTtl, function () {
            return Project::withoutGlobalScopes()
                ->whereNotNull('status')
                ->distinct()
                ->pluck('status');
        });

        $statuses = Cache::remember('filter_dropdown_statuses', $cacheTtl, function () use ($usedStatusKeys, $allStatuses) {
            return collect($allStatuses)->filter(function ($label, $key) use ($usedStatusKeys) {
                return $usedStatusKeys->contains($key);
            })->all();
        });

        // ================================================================
        // 7. تجميع البيانات وإرجاع العرض
        // ================================================================

        $viewData = compact(
            'projects',
            'programs',
            'domains',
            'subdomains',
            'interventions',
            'authorities',
            'statuses',
            'governorates',
            'entities',
            'hijriYears'
        );

        return view($viewName, array_merge($viewData, $extraViewData));
    }

    /**
     * تطبيق فلترة النطاق على استعلام معين (تُستخدم في دوال مساعدة للحصول على قوائم الفلاتر)
     */
    private function applyScopeFilter($query, $user, $selectedAdminScope, $selectedEntity, $selectedGeographicScopes)
    {
        // تحديد ما إذا كان المستخدم لديه نطاق محدد
        $hasScope = ! empty($selectedAdminScope) || ! empty($selectedEntity) || ! empty($selectedGeographicScopes);

        // إذا كان المستخدم أدمن وليس لديه نطاق، لا نطبق الفلترة (يرى كل شيء)
        if ($user->isAdmin() && ! $hasScope) {
            return;
        }

        // بناء قائمة المعرفات
        $entityIdsByEnt = $selectedAdminScope
            ? InternalEntity::getAllChildrenIds($selectedAdminScope)
            : [];

        $entityIdsByMyEnt = $selectedEntity
            ? InternalEntity::getAllChildrenIds($selectedEntity)
            : [];

        $entityIdsByGeo = [];
        if ($selectedGeographicScopes) {
            foreach ($selectedGeographicScopes as $scope) {
                $govId = is_array($scope) ? ($scope['governorate_id'] ?? null) : ($scope->governorate_id ?? null);
                $dirId = is_array($scope) ? ($scope['directorate_id'] ?? null) : ($scope->directorate_id ?? null);

                if (! empty($govId) && empty($dirId)) {
                    $ids = InternalEntity::getAllByGovernorate($govId);
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                } elseif (! empty($dirId)) {
                    $ids = InternalEntity::getAllByDirectorate($dirId);
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                }
            }
        }

        $entityIds = array_unique(array_merge($entityIdsByEnt, $entityIdsByGeo, $entityIdsByMyEnt));
        $entityIds = array_filter($entityIds, function ($id) {
            return is_numeric($id) && $id > 0;
        });
        $entityIds = array_values($entityIds);

        if (! empty($entityIds)) {
            $query->whereIn('creator_entity_id', $entityIds);
        } elseif (! empty($selectedEntity)) {
            $query->where('creator_entity_id', $selectedEntity);
        } elseif (! $user->isAdmin()) {
            // للمستخدمين غير الأدمن، إذا لم يكن لديهم نطاق، لا نعرض أي مشاريع
            $query->whereRaw('0=1');
        }
        // إذا كان المستخدم أدمن وليس لديه نطاق، تم تخطي الفلترة بالكامل
    }

    /**
     * Get project with all details for display
     */
    public function getProjectWithDetails(Project $project)
    {
        $project->load($this->getProjectDetailedRelations());
        $approvalStages = $this->getApprovalStages($project); // Fetch approval stages
        $reviewerType = $this->getCurrentReviewerType();

        // Generate QR Code
        $qrCodeUrl = route('projects.show', $project->id);
        $qrCodeBase64 = '';

        try {
            if (class_exists(QrCode::class)) {
                $qrCode = QrCode::create($qrCodeUrl);
                $writer = new SvgWriter;
                $qrCodeBase64 = $writer->write($qrCode)->getDataUri();
            }
        } catch (\Throwable $e) {
            \Log::error('QR Code generation failed: '.$e->getMessage());
            // Fallback to a placeholder or empty string if generation fails
        }

        $financialStatus = $this->calculateProjectFinancialStatus($project);

        if ($project->project_type === 'old') {
            return view('projects.show-old', compact('project', 'qrCodeBase64', 'financialStatus'));
        }

        return view('projects.show', compact('project', 'approvalStages', 'reviewerType', 'qrCodeBase64', 'financialStatus'));
    }

    /**
     * Calculate Project Financial Status according to strict accounting rules.
     */
    public function calculateProjectFinancialStatus(Project $project): array
    {
        $budget = (float) ($project->cost?->total_cost ?? ($project->costs ? $project->costs->sum('total_cost') : 0));

        $projectIdentifiers = array_filter([
            $project->erpnext_project_id,
            $project->frappe_project_name,
            $project->frappe_project_id,
            $project->project_name,
            $project->form_number,
        ]);

        $company = $project->created_by_entity;
        $erpService = app(ErpNextReportService::class);
        $glData = $erpService->getGlReport($company ? ['company' => $company] : [], true);

        // Fallback: if company-scoped query returned no data or failed, try fetching general GL report
        if (! isset($glData['success']) || ! $glData['success'] || empty($glData['result'])) {
            $fallbackGlData = $erpService->getGlReport([], true);
            if (isset($fallbackGlData['success']) && $fallbackGlData['success'] && ! empty($fallbackGlData['result'])) {
                $glData = $fallbackGlData;
            }
        }

        $accountsMap = $erpService->getAccountsMap();

        $projectGlEntries = [];
        $actualExpense = 0.0;
        $actualIncome = 0.0;
        $linkedExpenses = [];
        $unlinkedExpenses = [];

        $dbFinancialItems = FinancialItem::pluck('name')->toArray();

        if (isset($glData['success']) && $glData['success'] && isset($glData['result']) && is_array($glData['result'])) {
            foreach ($glData['result'] as $glRow) {
                $debit = (float) ($glRow['debit'] ?? 0);
                $credit = (float) ($glRow['credit'] ?? 0);

                if ($debit == 0 && $credit == 0) {
                    continue;
                }

                $glProject = trim($glRow['project'] ?? '');
                if (empty($glProject)) {
                    continue;
                }

                $isMatch = false;
                foreach ($projectIdentifiers as $ident) {
                    $trimmedIdent = trim($ident);
                    if (empty($trimmedIdent)) {
                        continue;
                    }

                    if (strcasecmp($glProject, $trimmedIdent) === 0 || str_contains(strtolower($glProject), strtolower($trimmedIdent)) || str_contains(strtolower($trimmedIdent), strtolower($glProject))) {
                        $isMatch = true;
                        break;
                    }
                }

                if (! $isMatch) {
                    continue;
                }

                $accName = trim($glRow['account'] ?? '', "'");
                $baseAccName = explode(' - ', $accName)[0] ?? '';
                $rootType = strtolower($accountsMap[$accName]['root_type'] ?? ($accountsMap[$baseAccName]['root_type'] ?? ($glRow['root_type'] ?? '')));
                $accountType = strtolower($accountsMap[$accName]['account_type'] ?? ($accountsMap[$baseAccName]['account_type'] ?? ($glRow['account_type'] ?? '')));

                // Intelligent fallback for rootType if missing from accountsMap
                if (empty($rootType) && empty($accountType)) {
                    $lowerAcc = strtolower($accName);
                    if (str_contains($lowerAcc, 'expense') || str_contains($accName, 'مصاريف') || str_contains($accName, 'نفقات') || str_contains($accName, 'إيجار') || str_contains($accName, 'ايجار')) {
                        $rootType = 'expense';
                    } elseif (str_contains($lowerAcc, 'income') || str_contains($accName, 'إيرادات') || str_contains($accName, 'ايرادات') || str_contains($accName, 'مبيعات')) {
                        $rootType = 'income';
                    }
                }

                $glRow['root_type'] = $rootType;
                $glRow['account_type'] = $accountType;

                $amount = 0;
                $isExpense = false;

                if ($rootType === 'income' || strpos($accountType, 'income') !== false) {
                    $amount = $credit - $debit;
                    $actualIncome += $amount;
                } elseif ($rootType === 'expense' || strpos($accountType, 'expense') !== false) {
                    $isExpense = true;
                    $amount = $debit - $credit;
                    $actualExpense += $amount;
                }

                $glRow['computed_amount'] = $amount;
                $projectGlEntries[] = $glRow;

                if ($isExpense) {
                    $claimType = trim($glRow['claim_expense_type'] ?? '');
                    if (! empty($claimType) && $claimType !== 'No Expense Type found' && in_array($claimType, $dbFinancialItems, true)) {
                        if (! isset($linkedExpenses[$claimType])) {
                            $linkedExpenses[$claimType] = 0.0;
                        }
                        $linkedExpenses[$claimType] += $amount;
                    } else {
                        $displayLabel = (empty($claimType) || $claimType === 'No Expense Type found') ? 'مصروف غير محدد البند' : $claimType.' (Unlinked)';
                        if (! isset($unlinkedExpenses[$displayLabel])) {
                            $unlinkedExpenses[$displayLabel] = 0.0;
                        }
                        $unlinkedExpenses[$displayLabel] += $amount;
                    }
                }
            }
        }

        $actionCosts = ExecutiveActionCost::where('project_id', $project->id)
            ->with(['activity', 'action', 'financialItem'])
            ->get();

        $activityBreakdown = [];
        $itemBudgets = [];

        foreach ($actionCosts as $costRow) {
            $actId = $costRow->executive_activity_id;
            $actName = $costRow->activity?->name ?? 'نشاط غير محدد';
            $actionId = $costRow->executive_activity_action_id;
            $actionName = $costRow->action?->action ?? 'إجراء غير محدد';
            $finItemId = $costRow->financial_item_id;
            $finItemName = $costRow->financialItem?->name ?? 'بند غير محدد';
            $qty = (float) $costRow->quantity;
            $unitCost = (float) $costRow->amount;
            $approvedBudget = (float) ($costRow->total ?: ($costRow->amount * $costRow->quantity));

            $activityBreakdown[] = [
                'activity_id' => $actId,
                'activity_name' => $actName,
                'action_id' => $actionId,
                'action_name' => $actionName,
                'financial_item_id' => $finItemId,
                'financial_item_name' => $finItemName,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'approved_budget' => $approvedBudget,
                'expense_link_status' => 'Unlinked',
            ];

            if ($finItemName && $finItemName !== 'بند غير محدد') {
                if (! isset($itemBudgets[$finItemName])) {
                    $itemBudgets[$finItemName] = 0.0;
                }
                $itemBudgets[$finItemName] += $approvedBudget;
            }
        }

        $allItemNames = array_unique(array_merge(
            array_keys($itemBudgets),
            array_keys($linkedExpenses)
        ));

        $itemReconciliation = [];
        foreach ($allItemNames as $itemName) {
            $itemBudget = $itemBudgets[$itemName] ?? 0.0;
            $linkedExp = $linkedExpenses[$itemName] ?? 0.0;
            $variance = $itemBudget - $linkedExp;

            $itemReconciliation[] = [
                'item_name' => $itemName,
                'approved_budget' => $itemBudget,
                'linked_expense' => $linkedExp,
                'variance' => $variance,
            ];
        }

        $activitiesTotalBudget = array_sum(array_column($activityBreakdown, 'approved_budget'));
        $hasBudgetMismatch = abs($activitiesTotalBudget - $budget) > 0.01;
        $mismatchDifference = $activitiesTotalBudget - $budget;

        $remaining = $budget - $actualExpense;
        $executionPercentage = $budget > 0 ? round(($actualExpense / $budget) * 100, 2) : 0.0;

        return [
            'budget' => $budget,
            'actual_expense' => $actualExpense,
            'actual_income' => $actualIncome,
            'remaining' => $remaining,
            'execution_percentage' => $executionPercentage,
            'linked_expenses' => $linkedExpenses,
            'unlinked_expenses' => $unlinkedExpenses,
            'gl_entries' => $projectGlEntries,
            'activity_breakdown' => $activityBreakdown,
            'item_reconciliation' => $itemReconciliation,
            'activities_total_budget' => $activitiesTotalBudget,
            'has_budget_mismatch' => $hasBudgetMismatch,
            'mismatch_difference' => $mismatchDifference,
        ];
    }

    /**
     * Revert a finalized project back to draft
     */
    public function revertProjectToDraft(Project $project)
    {
        $user = auth()->user();

        if (! $user->can('revert', $project)) {
            session()->flash('error', 'عذراً، ليس لديك صلاحية لإعادة هذا المشروع إلى حالة المسودة.');

            return redirect()->back();
        }

        $previousStageName = $project->current_stage_name ?? 'مرحلة الاعتماد';

        DB::transaction(function () use ($project, $previousStageName) {
            // Mark pending approvals as returned to preserve audit trail
            $project->projectApprovals()->where('status', 'pending')->update(['status' => 'returned']);

            $project->update([
                'status' => 'rolled_back_for_review',
                'finalized_at' => null,
                'current_stage' => null,
                'current_stage_order' => 1,
                'current_approval_stage_id' => null,
                'approval_status' => 'action_requested',
            ]);

            // Log activity
            $this->approvalService->logActivity($project, 'reverted_to_draft', [
                'from_stage_name' => $previousStageName,
                'to_stage_name' => 'المسودة (الجهة المنشئة)',
                'notes' => 'تم إعادة المشروع إلى حالة المسودة للجهة المنشئة من قبل المستخدم المخول.',
            ]);
        });

        session()->flash('success', 'تم إعادة المشروع إلى حالة المسودة وإرجاعها للجهة المنشئة بنجاح.');

        return redirect()->route('projects.index');
    }

    /**
     * Create a new project draft
     */
    public function createProject(array $data): Project
    {
        $user = auth()->user();
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_by_user_id'] = $user->id;
        $data['created_by_entity'] = $user->entity ? $user->entity->name : $user->department;
        $data['draft_saved_at'] = now();

        return Project::create($data);
    }

    /**
     * Update an existing project
     */
    public function updateProject(Project $project, array $data): bool
    {
        $user = auth()->user();
        $data['updated_by_user_id'] = $user->id;
        $data['updated_by_entity'] = $user->entity ? $user->entity->name : $user->department;
        $data['draft_saved_at'] = now();

        // Ensure original ownership is permanently preserved
        unset(
            $data['created_by_user_id'],
            $data['created_by_entity'],
            $data['internal_entity_id'],
            $data['creator_entity_id'],
            $data['created_by'],
            $data['creator_username']
        );

        return $project->update($data);
    }

    /**
     * Auto-save project (create or update)
     */
    public function autoSaveProject(array $data, $projectId = null): Project
    {
        $user = auth()->user();
        $data['status'] = 'draft';
        $data['draft_saved_at'] = now();

        if ($projectId) {
            $project = Project::findOrFail($projectId);
            $data['updated_by_user_id'] = $user->id;
            $data['updated_by_entity'] = $user->entity ? $user->entity->name : $user->department;

            // Ensure original ownership is permanently preserved
            unset(
                $data['created_by_user_id'],
                $data['created_by_entity'],
                $data['internal_entity_id'],
                $data['creator_entity_id'],
                $data['created_by'],
                $data['creator_username']
            );

            $project->update($data);

            return $project;
        }

        $data['created_by_user_id'] = $user->id;
        $data['created_by_entity'] = $user->entity ? $user->entity->name : $user->department;

        return Project::create($data);
    }

    /**
     * Auto-save update for specific project
     */
    public function autoSaveUpdateProject(Project $project, array $data): bool
    {
        $user = auth()->user();
        $data['status'] = 'draft';
        $data['updated_by_user_id'] = $user->id;
        $data['updated_by_entity'] = $user->entity ? $user->entity->name : $user->department;
        $data['draft_saved_at'] = now();

        // Ensure original ownership is permanently preserved
        unset(
            $data['created_by_user_id'],
            $data['created_by_entity'],
            $data['internal_entity_id'],
            $data['creator_entity_id'],
            $data['created_by'],
            $data['creator_username']
        );

        return $project->update($data);
    }

    /**
     * Process and sync all project components (Locations, Risks, Activities, etc.)
     */
    public function processProjectComponents(Project $project, array $data, Request $request, bool $isDraft = false): void
    {
        $reviewerType = $this->getCurrentReviewerType();
        $isFinancialReview = in_array($project->status, ['financial_review', 'financial_technical_review', 'reviewed_completed']);

        // Eager load nested relations before starting transaction to allow in-memory collection updates (Fix N+1 save issue)
        $project->load([
            'preliminaryActivities.procedures.costs',
            'executiveActivities.actions.costs',
            'financings',
            'locations',
        ]);

        DB::transaction(function () use ($project, $data, $request, $reviewerType, $isFinancialReview) {
            // 1. Process Details
            if ($request->has('project_summary') || $request->has('problem_and_justification')) {
                $this->syncProjectDetails($project, $data);
            }

            // 2. Process Locations
            if ($request->has('locations')) {
                $this->syncProjectLocations($project, $request->input('locations') ?? []);
            }

            // 3. Process Objectives, Results, and Outputs
            if (
                $request->has('main_objective') || $request->has('special_objectives') ||
                $request->has('objective_results') || $request->has('result_outputs')
            ) {
                $this->syncProjectObjectives($project, $data);
            }

            // 4. Process Risks
            if ($request->has('risks')) {
                $this->syncProjectRisks($project, $request->input('risks') ?? []);
            }

            // 5. Process Entities
            if (
                $request->has('supervising_authorities') || $request->has('implementing_entities') ||
                $request->has('participating_entities') || $request->has('beneficiary_entities') ||
                $request->has('beneficiary_groups')
            ) {
                $this->syncProjectEntities($project, $data);
            }

            // 6. Process Preliminary Activities
            if ($request->has('preliminary_activities')) {
                $this->syncPreliminaryActivities($project, $request->input('preliminary_activities') ?? [], $reviewerType, $isFinancialReview);
            }

            // 7. Process Executive Activities
            if ($request->has('executive_activities')) {
                $this->syncExecutiveActivities($project, $request->input('executive_activities') ?? [], $reviewerType, $isFinancialReview);
            }

            // 8. Process Financings
            if ($request->has('financings')) {
                $this->syncProjectFinancings($project, $request->input('financings') ?? []);
            }

            // 9. Process Overall Costs
            if (array_key_exists('project_cost', $data)) {
                $this->syncProjectOverallCosts($project, $data['project_cost'] ?? []);
            }
        });
    }

    /**
     * Normalize request data if needed
     */
    public function normalizeRequestData(Request $request): void
    {
        // Normalize custom reference inputs into pending DB records
        (new ProjectDataNormalizer)->normalize($request);

        // Handle legacy objectives mapping
        if ($request->has('main_objective') && ! $request->has('main_objectives')) {
            $val = $request->input('main_objective');
            $request->merge(['main_objectives' => ! empty($val) ? [['objective' => $val]] : []]);
        }

        if ($request->has('special_objective') && ! $request->has('special_objectives')) {
            $val = $request->input('special_objective');
            if (! empty($val)) {
                $request->merge([
                    'special_objectives' => [
                        [
                            'objective' => $val,
                            'target_value' => $request->input('special_indicator_value'),
                            'measurement_unit' => $request->input('special_indicator_unit'),
                        ],
                    ],
                ]);
            }
        }

        // Cast quantity fields to integers for preliminary activities
        if ($request->has('preliminary_activities')) {
            $preliminaryActivities = $request->input('preliminary_activities', []);
            foreach ($preliminaryActivities as $activityIndex => &$activity) {
                if (isset($activity['procedures']) && is_array($activity['procedures'])) {
                    foreach ($activity['procedures'] as $procedureIndex => &$procedure) {
                        if (isset($procedure['costs']) && is_array($procedure['costs'])) {
                            foreach ($procedure['costs'] as &$cost) {
                                if (isset($cost['quantity'])) {
                                    $cost['quantity'] = (int) $cost['quantity'];
                                }
                            }
                        }
                    }
                }
            }
            $request->merge(['preliminary_activities' => $preliminaryActivities]);
        }

        // Cast quantity fields to integers for executive activities
        if ($request->has('executive_activities')) {
            $executiveActivities = $request->input('executive_activities', []);
            foreach ($executiveActivities as $activityIndex => &$activity) {
                if (isset($activity['actions']) && is_array($activity['actions'])) {
                    foreach ($activity['actions'] as $actionIndex => &$action) {
                        if (isset($action['costs']) && is_array($action['costs'])) {
                            foreach ($action['costs'] as &$cost) {
                                if (isset($cost['quantity'])) {
                                    $cost['quantity'] = (int) $cost['quantity'];
                                }
                            }
                        }
                    }
                }
            }
            $request->merge(['executive_activities' => $executiveActivities]);
        }
    }

    /**
     * Delete project and its components
     */
    public function deleteProject(Project $project): bool
    {
        return DB::transaction(function () use ($project) {
            // Delete related components (Database handles cascaded deletes if configured,
            // otherwise manually delete them here)
            return $project->delete();
        });
    }

    /**
     * Prepare project data for review
     */
    public function reviewProject(Project $project): Project
    {
        return $project->load([
            'detail',
            'locations',
            'mainObjectives',
            'specialObjectives.results.outputs',
            'risks',
            'cost',
            'financings',
            'supervisingAuthorities',
            'implementingEntities',
            'participatingEntities',
            'beneficiaryEntities',
            'preliminaryActivities.procedures.costs.financialItem',
            'preliminaryActivities.procedures.costs.unit',
            'executiveActivities.actions.assignedEntities',
            'executiveActivities.actions.costs.financialItem',
            'executiveActivities.actions.costs.unit',
        ]);
    }

    /**
     * Finalize project (change status to final)
     */
    public function finalizeProject(Request $request, Project $project): bool
    {
        $success = $project->update([
            'status' => 'pending_approval',
            'finalized_at' => now(),
        ]);

        if ($success) {
            // Initialize into the prepared approval stages (assembly → union → committee)
            $this->approvalService->initializeProjectWithStages($project);

            // Trigger empowerment transfer at the first stage of approval
            try {
                $this->syncProjectToEmpowermentDepartment($project);
            } catch (\Exception $e) {
                Log::error('Failed to sync to empowerment on project finalization', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $success;
    }

    /**
     * تحويل المشروع تلقائياً إلى إدارة التمكين
     * يتم التحقق إذا اكتملت المسودة وتضمنت تمويل تمكين
     */
    public function syncProjectToEmpowermentDepartment(Project $project): void
    {
        $project->refresh();
        $project->load(['financings.fundingSource', 'financings.financingType', 'cost']);

        // البحث عن تمويل التمكين: مصدر التمويل أو نوع التمويل يحتوي على "تمكين" أو "قرض"
        $empowermentFinancings = $project->financings->filter(function ($financing) {
            $sourceName = optional($financing->fundingSource)->name ?? '';
            $typeName = optional($financing->financingType)->name ?? '';

            // التحقق من وجود "تمكين" أو "قرض" في أي من الحقلين
            $hasEmpowerment = str_contains($sourceName, 'تمكين') || str_contains($typeName, 'تمكين');
            $hasLoan = str_contains($sourceName, 'قرض') || str_contains($typeName, 'قرض');

            return $hasEmpowerment || $hasLoan;
        });

        if ($empowermentFinancings->isNotEmpty()) {
            $totalLoan = $empowermentFinancings->sum('financing_amount');
            $totalCost = optional($project->cost)->total_cost ?? 0;
            $loanPercentage = ($totalCost > 0) ? round(($totalLoan / $totalCost) * 100, 4) : 0;

            EmpowermentProject::updateOrCreate(
                ['project_id' => $project->id],
                [
                    'project_number' => $project->form_number,
                    'project_name' => $project->project_name,
                    'submitting_entity' => $project->creator_entity_id,
                    'total_project_cost' => $totalCost,
                    'total_loan_amount' => $totalLoan,
                    'loan_percentage' => $loanPercentage,
                    'number_of_beneficiaries' => $project->number_of_beneficiaries ?? 0,
                    'start_date_gregorian' => $project->start_date_gregorian,
                    'start_date_hijri' => $project->start_date_hijri,
                    'end_date_gregorian' => $project->end_date_gregorian,
                    'end_date_hijri' => $project->end_date_hijri,
                ]
            );

            \Log::info('تم تحويل المشروع إلى إدارة التمكين بنجاح', [
                'project_id' => $project->id,
                'total_loan' => $totalLoan,
                'financings_count' => $empowermentFinancings->count(),
            ]);
        } else {
            \Log::info('لم يتم العثور على تمويل تمكين للمشروع', [
                'project_id' => $project->id,
            ]);
        }
    }

    /**
     * Approve project internally
     */
    public function approveProjectInternally(Project $project): bool
    {
        return $project->update([
            'status' => 'internally_approved',
            'finalized_at' => now(),
        ]);
    }

    // ==================== INTERNAL SYNC METHODS ====================

    protected function syncProjectDetails(Project $project, array $data): void
    {
        $detailsData = [
            'is_part_of_plan' => $data['is_part_of_plan'] ?? false,
            'project_summary' => $data['project_summary'] ?? null,
            'project_introduction' => $data['project_introduction'] ?? null,
            'problem_and_justification' => $data['problem_and_justification'] ?? null,
            'project_components' => $data['project_components'] ?? null,
            'expected_impact' => $data['expected_impact'] ?? null,
        ];

        ProjectDetail::updateOrCreate(['project_id' => $project->id], $detailsData);
    }

    protected function syncProjectLocations(Project $project, array $locations): void
    {
        $keepIds = [];
        foreach ($locations as $location) {
            // Check if governorate_id is present and not empty (allowing '0' for All)
            if (isset($location['governorate_id']) && $location['governorate_id'] !== '') {
                // Convert '0' to null for all location fields to represent "All" consistently
                foreach (['governorate_id', 'directorate_id', 'sub_area_id', 'village_id'] as $field) {
                    if (isset($location[$field]) && ($location[$field] === '0' || $location[$field] === 0)) {
                        $location[$field] = null;
                    }
                    // Defensive: null-out any unresolved 'other' string to prevent DB type errors
                    if (isset($location[$field]) && $location[$field] === 'other') {
                        $location[$field] = null;
                    }
                }

                if (! empty($location['id'])) {
                    $existing = $project->locations->firstWhere('id', $location['id']);
                    if ($existing) {
                        $existing->update($location);
                        $keepIds[] = $existing->id;

                        continue;
                    }
                } else {
                    $existing = $project->locations()
                        ->where('governorate_id', $location['governorate_id'])
                        ->where('directorate_id', $location['directorate_id'] ?? null)
                        ->where('sub_area_id', $location['sub_area_id'] ?? null)
                        ->where('village_id', $location['village_id'] ?? null)
                        ->first();

                    if ($existing) {
                        $existing->update($location);
                        $keepIds[] = $existing->id;

                        continue;
                    }
                }

                $newLocation = $project->locations()->create($location);
                $keepIds[] = $newLocation->id;
            }
        }

        $project->locations()->whereNotIn('id', $keepIds)->delete();
    }

    protected function syncProjectRisks(Project $project, array $risks): void
    {
        $keepIds = [];
        foreach ($risks as $riskData) {
            if (! empty($riskData['risk'])) {
                if (isset($riskData['id']) && ! empty($riskData['id'])) {
                    $risk = $project->risks()->find($riskData['id']);
                    if ($risk) {
                        $risk->update($riskData);
                        $keepIds[] = $risk->id;
                    } else {
                        // ID provided but not found, create new
                        $newRisk = $project->risks()->create($riskData);
                        $keepIds[] = $newRisk->id;
                    }
                } else {
                    $newRisk = $project->risks()->create($riskData);
                    $keepIds[] = $newRisk->id;
                }
            }
        }

        // Delete risks that were not in the submitted list
        $project->risks()->whereNotIn('id', $keepIds)->delete();
    }

    /**
     * Sync project objectives, results, and outputs.
     */
    protected function syncProjectObjectives(Project $project, array $data): void
    {
        \Log::info('=== SYNC OBJECTIVES START ===', [
            'project_id' => $project->id,
            'has_main' => (isset($data['main_objectives']) || isset($data['main_objective'])),
            'has_special' => isset($data['special_objectives']),
        ]);

        // 1. Process Main Objectives (General Objective)
        if (isset($data['main_objectives']) || isset($data['main_objective'])) {
            $mainObjectivesData = $data['main_objectives'] ?? [['objective' => $data['main_objective'] ?? '']];
            $keepMainIds = [];

            foreach ($mainObjectivesData as $objectiveData) {
                if (empty($objectiveData['objective'])) {
                    continue;
                }

                $mainObj = null;
                if (isset($objectiveData['id'])) {
                    $mainObj = $project->mainObjectives()->find($objectiveData['id']);
                }

                if (! $mainObj) {
                    $mainObj = $project->mainObjectives()->first();
                }

                if ($mainObj) {
                    $mainObj->update(['objective' => $objectiveData['objective']]);
                } else {
                    $mainObj = $project->mainObjectives()->create(['objective' => $objectiveData['objective']]);
                }
                $keepMainIds[] = $mainObj->id;
            }

            if (! empty($keepMainIds)) {
                $project->mainObjectives()->whereNotIn('id', $keepMainIds)->delete();
            }
        }

        // 2. Process Special Objectives, Results, and Outputs
        $specialObjectives = $data['special_objectives'] ?? [];
        $objectiveResults = $data['objective_results'] ?? [];
        $resultOutputs = $data['result_outputs'] ?? [];

        $keepSpecialIds = [];

        foreach ($specialObjectives as $sIdx => $sData) {
            if (empty($sData['objective'])) {
                continue;
            }

            $specialObj = null;
            if (isset($sData['id'])) {
                $specialObj = $project->specialObjectives()->find($sData['id']);
            }

            if ($specialObj) {
                $specialObj->update($sData);
            } else {
                $specialObj = $project->specialObjectives()->create(array_merge($sData, ['project_id' => $project->id]));
            }
            $keepSpecialIds[] = $specialObj->id;

            // Process Results for this specific special objective
            $keepResultIds = [];
            foreach ($objectiveResults as $rIdx => $rData) {
                if (empty($rData['result_name'])) {
                    continue;
                }

                // Match result to parent by DB ID or by Index
                $isParentMatch = false;
                if (isset($rData['special_objective_id']) && $rData['special_objective_id'] !== '') {
                    $parentId = (string) $rData['special_objective_id'];
                    $isParentMatch = ($parentId === (string) $specialObj->id || $parentId === (string) $sIdx);
                }

                if ($isParentMatch) {
                    $result = null;
                    if (isset($rData['id'])) {
                        $result = $specialObj->results()->find($rData['id']);
                    }

                    $resultPayload = array_merge($rData, [
                        'project_id' => $project->id,
                        'special_objective_id' => $specialObj->id,
                    ]);

                    if ($result) {
                        $result->update($resultPayload);
                    } else {
                        $result = $specialObj->results()->create($resultPayload);
                    }
                    $keepResultIds[] = $result->id;

                    // Process Outputs for this specific result
                    $keepOutputIds = [];
                    foreach ($resultOutputs as $oData) {
                        if (empty($oData['output'])) {
                            continue;
                        }

                        $isOutputParentMatch = false;
                        if (isset($oData['objective_result_id']) && $oData['objective_result_id'] !== '') {
                            $resParentId = (string) $oData['objective_result_id'];
                            $isOutputParentMatch = ($resParentId === (string) $result->id || $resParentId === (string) $rIdx);
                        }

                        if ($isOutputParentMatch) {
                            $output = null;
                            if (isset($oData['id'])) {
                                $output = $result->outputs()->find($oData['id']);
                            }

                            $outputPayload = array_merge($oData, [
                                'project_id' => $project->id,
                                'special_objective_id' => $specialObj->id,
                                'objective_result_id' => $result->id,
                            ]);

                            if ($output) {
                                $output->update($outputPayload);
                            } else {
                                $output = $result->outputs()->create($outputPayload);
                            }
                            $keepOutputIds[] = $output->id;
                        }
                    }
                    $result->outputs()->whereNotIn('id', $keepOutputIds)->delete();
                }
            }
            $specialObj->results()->whereNotIn('id', $keepResultIds)->delete();
        }

        if (isset($data['special_objectives'])) {
            $project->specialObjectives()->whereNotIn('id', $keepSpecialIds)->delete();
        }
        \Log::info('=== SYNC OBJECTIVES END ===', ['kept_objectives' => count($keepSpecialIds)]);
    }

    protected function syncProjectEntities(Project $project, array $data): void
    {
        $sanitizeId = function ($val): ?int {
            if ($val === null || $val === '' || $val === 'undefined' || $val === 'null') {
                return null;
            }

            return (is_numeric($val) && (int) $val > 0) ? (int) $val : null;
        };

        // 1. Supervising Authorities
        if (array_key_exists('supervising_authorities', $data)) {
            $keepIds = [];
            if (! empty($data['supervising_authorities']) && is_array($data['supervising_authorities'])) {
                foreach ($data['supervising_authorities'] as $entity) {
                    $authType = $entity['authority_type'] ?? $entity['entity_type'] ?? 'internal';
                    $rawId = $entity['internal_entity_id'] ?? $entity['authority_id'] ?? $entity['entity_id'] ?? null;
                    $entityVal = $sanitizeId($rawId);
                    $parentId = $sanitizeId($entity['parent_id'] ?? null);

                    if ($entityVal) {
                        $payload = [
                            'authority_type' => $authType,
                            'parent_id' => $parentId,
                            'internal_entity_id' => $authType === 'internal' ? $entityVal : null,
                            'authority_id' => $authType === 'external' ? $entityVal : null,
                        ];

                        $existing = null;
                        if (! empty($entity['id']) && $sanitizeId($entity['id'])) {
                            $existing = $project->supervisingAuthorities()->find($entity['id']);
                        }
                        if (! $existing) {
                            $existing = $project->supervisingAuthorities()
                                ->where(function ($q) use ($authType, $entityVal) {
                                    if ($authType === 'internal') {
                                        $q->where('internal_entity_id', $entityVal);
                                    } else {
                                        $q->where('authority_id', $entityVal);
                                    }
                                })->first();
                        }

                        if ($existing) {
                            $existing->update($payload);
                            $keepIds[] = $existing->id;
                        } else {
                            $newEntity = $project->supervisingAuthorities()->create($payload);
                            $keepIds[] = $newEntity->id;
                        }
                    }
                }
            }
            $project->supervisingAuthorities()->whereNotIn('id', $keepIds)->delete();
        }

        // 2. Implementing Entities
        if (array_key_exists('implementing_entities', $data)) {
            $keepIds = [];
            if (! empty($data['implementing_entities']) && is_array($data['implementing_entities'])) {
                foreach ($data['implementing_entities'] as $entity) {
                    $authType = $entity['authority_type'] ?? $entity['entity_type'] ?? 'internal';
                    $rawId = $entity['internal_entity_id'] ?? $entity['authority_id'] ?? $entity['entity_id'] ?? null;
                    $entityVal = $sanitizeId($rawId);
                    $parentId = $sanitizeId($entity['parent_id'] ?? null);

                    if ($entityVal) {
                        $payload = [
                            'authority_type' => $authType,
                            'parent_id' => $parentId,
                            'internal_entity_id' => $authType === 'internal' ? $entityVal : null,
                            'authority_id' => $authType === 'external' ? $entityVal : null,
                        ];

                        $existing = null;
                        if (! empty($entity['id']) && $sanitizeId($entity['id'])) {
                            $existing = $project->implementingEntities()->find($entity['id']);
                        }
                        if (! $existing) {
                            $existing = $project->implementingEntities()
                                ->where(function ($q) use ($authType, $entityVal) {
                                    if ($authType === 'internal') {
                                        $q->where('internal_entity_id', $entityVal);
                                    } else {
                                        $q->where('authority_id', $entityVal);
                                    }
                                })->first();
                        }

                        if ($existing) {
                            $existing->update($payload);
                            $keepIds[] = $existing->id;
                        } else {
                            $newEntity = $project->implementingEntities()->create($payload);
                            $keepIds[] = $newEntity->id;
                        }
                    }
                }
            }
            $project->implementingEntities()->whereNotIn('id', $keepIds)->delete();
        }

        // 3. Participating Entities
        if (array_key_exists('participating_entities', $data)) {
            $keepIds = [];
            if (! empty($data['participating_entities']) && is_array($data['participating_entities'])) {
                foreach ($data['participating_entities'] as $entity) {
                    $authType = $entity['authority_type'] ?? $entity['entity_type'] ?? 'internal';
                    $rawId = $entity['internal_entity_id'] ?? $entity['authority_id'] ?? $entity['entity_id'] ?? null;
                    $entityVal = $sanitizeId($rawId);
                    $parentId = $sanitizeId($entity['parent_id'] ?? null);

                    if ($entityVal) {
                        $payload = [
                            'authority_type' => $authType,
                            'parent_id' => $parentId,
                            'internal_entity_id' => $authType === 'internal' ? $entityVal : null,
                            'authority_id' => $authType === 'external' ? $entityVal : null,
                        ];

                        $existing = null;
                        if (! empty($entity['id']) && $sanitizeId($entity['id'])) {
                            $existing = $project->participatingEntities()->find($entity['id']);
                        }
                        if (! $existing) {
                            $existing = $project->participatingEntities()
                                ->where(function ($q) use ($authType, $entityVal) {
                                    if ($authType === 'internal') {
                                        $q->where('internal_entity_id', $entityVal);
                                    } else {
                                        $q->where('authority_id', $entityVal);
                                    }
                                })->first();
                        }

                        if ($existing) {
                            $existing->update($payload);
                            $keepIds[] = $existing->id;
                        } else {
                            $newEntity = $project->participatingEntities()->create($payload);
                            $keepIds[] = $newEntity->id;
                        }
                    }
                }
            }
            $project->participatingEntities()->whereNotIn('id', $keepIds)->delete();
        }

        // 4. Beneficiary Entities
        if (array_key_exists('beneficiary_entities', $data)) {
            $keepIds = [];
            if (! empty($data['beneficiary_entities']) && is_array($data['beneficiary_entities'])) {
                foreach ($data['beneficiary_entities'] as $entity) {
                    $authType = $entity['authority_type'] ?? $entity['entity_type'] ?? 'internal';
                    $rawId = $entity['internal_entity_id'] ?? $entity['authority_id'] ?? $entity['entity_id'] ?? null;
                    $entityVal = $sanitizeId($rawId);
                    $parentId = $sanitizeId($entity['parent_id'] ?? null);

                    if ($entityVal) {
                        $payload = [
                            'authority_type' => $authType,
                            'parent_id' => $parentId,
                            'internal_entity_id' => $authType === 'internal' ? $entityVal : null,
                            'authority_id' => $authType === 'external' ? $entityVal : null,
                        ];

                        $existing = null;
                        if (! empty($entity['id']) && $sanitizeId($entity['id'])) {
                            $existing = $project->beneficiaryEntities()->find($entity['id']);
                        }
                        if (! $existing) {
                            $existing = $project->beneficiaryEntities()
                                ->where(function ($q) use ($authType, $entityVal) {
                                    if ($authType === 'internal') {
                                        $q->where('internal_entity_id', $entityVal);
                                    } else {
                                        $q->where('authority_id', $entityVal);
                                    }
                                })->first();
                        }

                        if ($existing) {
                            $existing->update($payload);
                            $keepIds[] = $existing->id;
                        } else {
                            $newEntity = $project->beneficiaryEntities()->create($payload);
                            $keepIds[] = $newEntity->id;
                        }
                    }
                }
            }
            $project->beneficiaryEntities()->whereNotIn('id', $keepIds)->delete();
        }

        // 5. Beneficiary Groups (Many-to-Many sync)
        if (array_key_exists('beneficiary_groups', $data)) {
            $project->beneficiaryGroups()->sync($data['beneficiary_groups'] ?? []);
        }

        // 6. Aggregated Project Entities
        if (array_key_exists('project_entities', $data)) {
            $keepIds = [];
            if (! empty($data['project_entities'])) {
                foreach ($data['project_entities'] as $entity) {
                    if (! empty($entity['entity_name'])) {
                        $existing = null;
                        if (! empty($entity['id'])) {
                            $existing = $project->projectEntities()->find($entity['id']);
                        }
                        if (! $existing) {
                            $existing = $project->projectEntities()->where('entity_name', $entity['entity_name'])->first();
                        }

                        if ($existing) {
                            $existing->update($entity);
                            $keepIds[] = $existing->id;
                        } else {
                            $newEntity = $project->projectEntities()->create($entity);
                            $keepIds[] = $newEntity->id;
                        }
                    }
                }
            }
            $project->projectEntities()->whereNotIn('id', $keepIds)->delete();
        }
    }

    protected function syncPreliminaryActivities(Project $project, array $activities, string $reviewerType = 'general', bool $isFinancialReview = false): void
    {
        // If in financial review and not an admin, perform surgical updates to preserve unauthorized fields
        if ($isFinancialReview && $reviewerType !== 'general') {
            foreach ($activities as $actData) {
                if (isset($actData['id'])) {
                    $activity = $project->preliminaryActivities->firstWhere('id', $actData['id']);
                    if ($activity) {
                        // Technical reviewer can update activity name
                        if ($reviewerType === 'technical' && isset($actData['name'])) {
                            $activity->update(['name' => $actData['name']]);
                        }

                        if (! empty($actData['procedures'])) {
                            foreach ($actData['procedures'] as $procData) {
                                if (isset($procData['id'])) {
                                    $procedure = $activity->procedures->firstWhere('id', $procData['id']);
                                    if ($procedure) {
                                        // Technical reviewer can update procedure details
                                        if ($reviewerType === 'technical') {
                                            $updateData = [];
                                            if (isset($procData['procedure_name'])) {
                                                $updateData['procedure_name'] = $procData['procedure_name'];
                                            }
                                            if (isset($procData['start_date'])) {
                                                $updateData['start_date'] = $procData['start_date'];
                                            }
                                            if (isset($procData['end_date'])) {
                                                $updateData['end_date'] = $procData['end_date'];
                                            }
                                            if (! empty($updateData)) {
                                                $procedure->update($updateData);
                                            }
                                        }

                                        // Financial reviewer can update costs
                                        if ($reviewerType === 'financial' && ! empty($procData['costs'])) {
                                            foreach ($procData['costs'] as $costData) {
                                                if (isset($costData['id'])) {
                                                    $cost = $procedure->costs->firstWhere('id', $costData['id']);
                                                    if ($cost) {
                                                        $quantity = $costData['quantity'] ?? $cost->quantity;
                                                        $amount = $costData['amount'] ?? $cost->amount;
                                                        $cost->update([
                                                            'quantity' => $quantity,
                                                            'amount' => $amount,
                                                            'total' => $quantity * $amount,
                                                        ]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return;
        }

        // Standard behavior for draft or admin: Preserve existing records using $keepIds
        $keepActIds = [];
        foreach ($activities as $actData) {
            if (empty($actData['name'])) {
                continue;
            }

            $activity = null;
            if (! empty($actData['id'])) {
                $activity = $project->preliminaryActivities->firstWhere('id', $actData['id']);
            }
            if (! $activity) {
                $activity = $project->preliminaryActivities->firstWhere('name', $actData['name']);
            }

            $actFillable = Arr::only($actData, ['name', 'weight']);

            if ($activity) {
                $activity->update($actFillable);
            } else {
                $activity = $project->preliminaryActivities()->create($actFillable);
            }
            $keepActIds[] = $activity->id;

            if (! empty($actData['procedures'])) {
                $keepProcIds = [];
                foreach ($actData['procedures'] as $procData) {
                    if (empty($procData['procedure_name'])) {
                        continue;
                    }

                    $procedure = null;
                    if (! empty($procData['id'])) {
                        $procedure = $activity->procedures->firstWhere('id', $procData['id']);
                    }
                    if (! $procedure) {
                        $procedure = $activity->procedures->firstWhere('procedure_name', $procData['procedure_name']);
                    }

                    $procFillable = Arr::only($procData, [
                        'project_entities_id', 'procedure_name', 'weight',
                        'start_date', 'end_date', 'start_date_hijri', 'end_date_hijri',
                        'verification_means',
                    ]);

                    if ($procedure) {
                        $procedure->update(array_merge($procFillable, ['project_id' => $project->id]));
                    } else {
                        $procedure = $activity->procedures()->create(array_merge($procFillable, ['project_id' => $project->id]));
                    }
                    $keepProcIds[] = $procedure->id;

                    if (! empty($procData['costs'])) {
                        $keepCostIds = [];
                        foreach ($procData['costs'] as $costData) {
                            if (! empty($costData['financial_item_id'])) {
                                $cost = null;
                                if (! empty($costData['id'])) {
                                    $cost = $procedure->costs->firstWhere('id', $costData['id']);
                                }
                                if (! $cost) {
                                    $cost = $procedure->costs->firstWhere('financial_item_id', $costData['financial_item_id']);
                                }

                                $costFillable = Arr::only($costData, [
                                    'financial_item_id', 'unit_id', 'amount', 'quantity', 'total',
                                ]);

                                $costPayload = array_merge($costFillable, [
                                    'project_id' => $project->id,
                                    'activity_id' => $activity->id,
                                ]);

                                if ($cost) {
                                    $cost->update($costPayload);
                                } else {
                                    $cost = $procedure->costs()->create($costPayload);
                                }
                                $keepCostIds[] = $cost->id;
                            }
                        }
                        $procedure->costs()->whereNotIn('id', $keepCostIds)->delete();
                    }
                }
                $activity->procedures()->whereNotIn('id', $keepProcIds)->delete();
            }
        }
        $project->preliminaryActivities()->whereNotIn('id', $keepActIds)->delete();

    }

    protected function syncExecutiveActivities(Project $project, array $activities, string $reviewerType = 'general', bool $isFinancialReview = false): void
    {
        // If in financial review and not an admin, perform surgical updates to preserve unauthorized fields
        if ($isFinancialReview && $reviewerType !== 'general') {
            foreach ($activities as $actData) {
                if (isset($actData['id'])) {
                    $activity = $project->executiveActivities->firstWhere('id', $actData['id']);
                    if ($activity) {
                        // Technical reviewer can update activity name
                        if ($reviewerType === 'technical' && isset($actData['name'])) {
                            $activity->update(['name' => $actData['name']]);
                        }

                        if (! empty($actData['actions'])) {
                            foreach ($actData['actions'] as $actionData) {
                                if (isset($actionData['id'])) {
                                    $action = $activity->actions->firstWhere('id', $actionData['id']);
                                    if ($action) {
                                        // Technical reviewer can update action details
                                        if ($reviewerType === 'technical') {
                                            $updateData = [];
                                            if (isset($actionData['action'])) {
                                                $updateData['action'] = $actionData['action'];
                                            }
                                            if (isset($actionData['start_date'])) {
                                                $updateData['start_date'] = $actionData['start_date'];
                                            }
                                            if (isset($actionData['end_date'])) {
                                                $updateData['end_date'] = $actionData['end_date'];
                                            }
                                            if (! empty($updateData)) {
                                                $action->update($updateData);
                                            }

                                            // Process assigned entities (also technical)
                                            if (isset($actionData['assigned_entities'])) {
                                                // Simplified: clear and recreate for the specific action
                                                $action->assignedEntities()->delete();
                                                foreach ($actionData['assigned_entities'] as $entityData) {
                                                    $action->assignedEntities()->create($entityData);
                                                }
                                            }
                                        }

                                        // Financial reviewer can update costs
                                        if ($reviewerType === 'financial' && ! empty($actionData['costs'])) {
                                            foreach ($actionData['costs'] as $costData) {
                                                if (isset($costData['id'])) {
                                                    $cost = $action->costs->firstWhere('id', $costData['id']);
                                                    if ($cost) {
                                                        $quantity = $costData['quantity'] ?? $cost->quantity;
                                                        $amount = $costData['amount'] ?? $cost->amount;
                                                        $cost->update([
                                                            'quantity' => $quantity,
                                                            'amount' => $amount,
                                                            'total' => $quantity * $amount,
                                                        ]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return;
        }

        // Standard behavior: Preserve existing records using $keepIds
        // ... Load mappings etc.
        $outputsMap = $project->resultOutputs()->get()->pluck('id', 'output');
        $risksMap = $project->risks()->get()->pluck('id', 'risk');

        $keepActIds = [];
        foreach ($activities as $actData) {
            if (empty($actData['name'])) {
                continue;
            }

            // Resolve IDs mapping logic...
            if (isset($actData['result_output_id']) && ! empty($actData['result_output_id']) && ! is_numeric($actData['result_output_id'])) {
                $actData['result_output_id'] = $outputsMap->get($actData['result_output_id']);
            }
            if (isset($actData['project_risk_id']) && ! empty($actData['project_risk_id']) && ! is_numeric($actData['project_risk_id'])) {
                $actData['project_risk_id'] = $risksMap->get($actData['project_risk_id']);
            }

            $activity = null;
            if (! empty($actData['id'])) {
                $activity = $project->executiveActivities->firstWhere('id', $actData['id']);
            }
            if (! $activity) {
                $activity = $project->executiveActivities->firstWhere('name', $actData['name']);
            }

            if ($activity) {
                $activity->update(Arr::only($actData, ['name', 'weight', 'output', 'risk', 'result_output_id', 'project_risk_id']));
            } else {
                $activity = $project->executiveActivities()->create(Arr::only($actData, ['name', 'weight', 'output', 'risk', 'result_output_id', 'project_risk_id']));
            }
            $keepActIds[] = $activity->id;

            if (! empty($actData['actions'])) {
                $keepActionIds = [];
                foreach ($actData['actions'] as $actionData) {
                    if (empty($actionData['action'])) {
                        continue;
                    }

                    $action = null;
                    if (! empty($actionData['id'])) {
                        $action = $activity->actions->firstWhere('id', $actionData['id']);
                    }
                    if (! $action) {
                        $action = $activity->actions()->where('action', $actionData['action'])->first();
                    }

                    $actionFillable = Arr::only($actionData, [
                        'action', 'weight', 'start_date', 'end_date', 'start_date_hijri', 'end_date_hijri', 'verification_means',
                    ]);

                    if ($action) {
                        $action->update(array_merge($actionFillable, ['project_id' => $project->id]));
                    } else {
                        $action = $activity->actions()->create(array_merge($actionFillable, ['project_id' => $project->id]));
                    }
                    $keepActionIds[] = $action->id;

                    if (! empty($actionData['assigned_entities'])) {
                        $keepEntityIds = [];
                        foreach ($actionData['assigned_entities'] as $entityData) {
                            $entity = null;
                            if (! empty($entityData['id'])) {
                                $entity = $action->assignedEntities()->find($entityData['id']);
                            }
                            if (! $entity) {
                                $query = $action->assignedEntities();
                                if (! empty($entityData['entity_id'])) {
                                    $query->where('entity_id', $entityData['entity_id']);
                                } elseif (! empty($entityData['internal_entity_id'])) {
                                    $query->where('internal_entity_id', $entityData['internal_entity_id']);
                                } else {
                                    $query->where('id', -1);
                                }
                                $entity = $query->first();
                            }

                            $entityFillable = Arr::only($entityData, [
                                'entity', 'name', 'task',
                            ]);

                            $entityPayload = array_merge($entityFillable, [
                                'project_id' => $project->id,
                                'executive_activity_id' => $activity->id,
                            ]);

                            if ($entity) {
                                $entity->update($entityPayload);
                            } else {
                                $entity = $action->assignedEntities()->create($entityPayload);
                            }
                            $keepEntityIds[] = $entity->id;
                        }
                        $action->assignedEntities()->whereNotIn('id', $keepEntityIds)->delete();
                    }

                    if (! empty($actionData['costs'])) {
                        $keepCostIds = [];
                        foreach ($actionData['costs'] as $costData) {
                            if (! empty($costData['financial_item_id'])) {
                                $cost = null;
                                if (! empty($costData['id'])) {
                                    $cost = $action->costs->firstWhere('id', $costData['id']);
                                }
                                if (! $cost) {
                                    $cost = $action->costs->firstWhere('financial_item_id', $costData['financial_item_id']);
                                }

                                $costFillable = Arr::only($costData, [
                                    'financial_item_id', 'unit_id', 'amount', 'quantity', 'total',
                                ]);

                                $costPayload = array_merge($costFillable, [
                                    'project_id' => $project->id,
                                    'executive_activity_id' => $activity->id,
                                ]);

                                if ($cost) {
                                    $cost->update($costPayload);
                                } else {
                                    $cost = $action->costs()->create($costPayload);
                                }
                                $keepCostIds[] = $cost->id;
                            }
                        }
                        $action->costs()->whereNotIn('id', $keepCostIds)->delete();
                    }
                }
                $activity->actions()->whereNotIn('id', $keepActionIds)->delete();
            }
        }
        $project->executiveActivities()->whereNotIn('id', $keepActIds)->delete();
    }

    protected function syncProjectFinancings(Project $project, array $financings): void
    {
        $keepIds = [];
        foreach ($financings as $financing) {
            if (! empty($financing['funding_source_id'])) {
                $existing = null;
                if (! empty($financing['id'])) {
                    $existing = $project->financings->firstWhere('id', $financing['id']);
                }
                if (! $existing) {
                    $existing = $project->financings->firstWhere('funding_source_id', $financing['funding_source_id']);
                }

                if ($existing) {
                    $existing->update($financing);
                    $keepIds[] = $existing->id;
                } else {
                    $newFinancing = $project->financings()->create($financing);
                    $keepIds[] = $newFinancing->id;
                }
            }
        }
        $project->financings()->whereNotIn('id', $keepIds)->delete();
    }

    protected function syncProjectOverallCosts(Project $project, array $costData): void
    {
        Log::info('ProjectService: Syncing overall costs', [
            'project_id' => $project->id,
            'cost_data' => $costData,
        ]);

        // Only keep total_cost to prevent query exceptions on dropped database columns
        $filteredData = [];
        if (array_key_exists('total_cost', $costData)) {
            $filteredData['total_cost'] = ($costData['total_cost'] === '' || $costData['total_cost'] === null) ? null : $costData['total_cost'];
        }

        // Check if there is any meaningful data to save
        $hasMeaningfulData = ! empty(array_filter($filteredData, fn ($v) => $v !== null && $v !== ''));

        if ($hasMeaningfulData) {
            ProjectCost::updateOrCreate(['project_id' => $project->id], $filteredData);
        }
    }

    // ==================== FINANCIAL SUMMARY METHODS ====================

    public function getPreliminaryFinancialSummary(Project $project): array
    {
        return [
            'total_cost' => $project->preliminaryFinancialSummaries()->sum('amount'),
            // Add more summary logic as needed
        ];
    }

    public function getExecutiveFinancialSummary(Project $project): array
    {
        return [
            'total_cost' => $project->executiveFinancialSummaries()->sum('amount'),
        ];
    }

    public function getFinancingsSummary(Project $project): array
    {
        return [
            'total_financing' => $project->financings()->sum('financing_amount'),
        ];
    }

    public function getPreliminaryFinancialSummaries(Project $project)
    {
        return $project->preliminaryFinancialSummaries;
    }

    public function getProjectRisks(Project $project, Request $request): array
    {
        return ['risks' => $project->risks];
    }

    public function analyzeProjectRisks(Project $project, Request $request): array
    {
        return ['analysis' => 'Risk analysis logic here'];
    }

    public function deleteProjectRisk(Project $project, $riskId, Request $request): array
    {
        $project->risks()->where('id', $riskId)->delete();

        return ['success' => true, 'message' => 'Risk deleted'];
    }

    /**
     * Get approval stages for a project
     */
    public function getApprovalStages(Project $project): array
    {
        try {
            // ── 1. Ensure creator relationship is loaded ───────────────────────
            if (! $project->relationLoaded('createdBy')) {
                $project->load('createdBy');
            }

            // ── 2. Resolve origin entity ID ────────────────────────────────────
            // Same priority order as ApprovalService::initializeProjectWithStages
            // so that both methods agree on the approval chain.
            // Helper Closure to validate if entity ID actually exists in internal_entities
            $resolveEntity = function ($id) {
                if (! $id || ! is_numeric($id)) {
                    return null;
                }
                $ent = InternalEntity::withoutGlobalScopes()->find((int) $id);

                return $ent ? $ent->id : null;
            };

            $originEntityId = $resolveEntity($project->creator_entity_id)
                ?? $resolveEntity($project->internal_entity_id)
                ?? $resolveEntity(optional($project->createdBy)->entity_id)
                ?? $resolveEntity(optional($project->createdBy)->creator_entity_id);

            // Fallback: string-based created_by_entity field
            if (! $originEntityId && $project->created_by_entity) {
                if (is_numeric($project->created_by_entity)) {
                    $originEntityId = $resolveEntity($project->created_by_entity);
                } else {
                    $entity = InternalEntity::withoutGlobalScopes()
                        ->where('name', trim($project->created_by_entity))
                        ->first();
                    if (! $entity) {
                        // Try fuzzy match
                        $entity = InternalEntity::withoutGlobalScopes()
                            ->where('name', 'like', '%'.trim($project->created_by_entity).'%')
                            ->first();
                    }
                    $originEntityId = $entity ? $entity->id : null;
                }
            }

            // Fallback: extract from current_stage string  (e.g. "entity_5_technical_review" → 5)
            if (! $originEntityId && $project->current_stage && str_starts_with($project->current_stage, 'entity_')) {
                if (preg_match('/^entity_(\d+)/', $project->current_stage, $matches) === 1) {
                    $originEntityId = $resolveEntity((int) $matches[1]);
                }
            }

            // Fallback: look at the earliest approval record for this project
            if (! $originEntityId) {
                $anyApproval = $project->projectApprovals()
                    ->whereNotNull('entity_id')
                    ->orderBy('step_order')
                    ->first();
                if ($anyApproval && $anyApproval->entity_id) {
                    $originEntityId = $resolveEntity($anyApproval->entity_id);
                }
            }

            // Fallback: look at implementing entities for this project
            if (! $originEntityId) {
                $imp = ProjectImplementingEntity::where('project_id', $project->id)
                    ->whereNotNull('internal_entity_id')
                    ->first();
                if ($imp && $imp->internal_entity_id) {
                    $originEntityId = $resolveEntity($imp->internal_entity_id);
                }
            }

            // Fallback: look at internal supervising entities for this project
            if (! $originEntityId) {
                $sup = ProjectSupervisingAuthority::where('project_id', $project->id)
                    ->whereNotNull('internal_entity_id')
                    ->first();
                if ($sup && $sup->internal_entity_id) {
                    $originEntityId = $resolveEntity($sup->internal_entity_id);
                }
            }

            // Fallback: current authenticated user's entity (last resort)
            if (! $originEntityId) {
                $authUser = auth()->user();
                $originEntityId = $resolveEntity($authUser?->entity_id)
                    ?? $resolveEntity($authUser?->creator_entity_id);
            }

            // Fallback: first active internal entity
            if (! $originEntityId) {
                $firstEntity = InternalEntity::withoutGlobalScopes()->where('is_active', true)->first();
                if ($firstEntity) {
                    $originEntityId = $firstEntity->id;
                }
            }

            if (! $originEntityId) {
                \Log::warning('getApprovalStages: Cannot determine origin entity', [
                    'project_id' => $project->id,
                    'creator_entity_id' => $project->creator_entity_id,
                    'internal_entity_id' => $project->internal_entity_id,
                    'created_by_entity' => $project->created_by_entity,
                    'created_by_user_id' => $project->created_by_user_id,
                    'current_stage' => $project->current_stage,
                    'auth_entity_id' => auth()->user()?->entity_id,
                ]);

                return [];
            }

            // ── 3. Generate the full hierarchy chain ───────────────────────────
            $dynamicStages = $this->entityHierarchyService
                ->generateApprovalStagesFromSelectedEntity($originEntityId);

            \Log::info('getApprovalStages: generated stages', [
                'count' => count($dynamicStages),
                'originEntityId' => $originEntityId,
                'project_id' => $project->id,
            ]);

            if (empty($dynamicStages)) {
                return [];
            }

            // ── 4. Map stages with display metadata ────────────────────────────
            $allApprovals = $project->projectApprovals()->withTrashed()->get();
            $activeApprovals = $project->projectApprovals;
            $highestStepOrder = $allApprovals->max('step_order') ?? 0;
            $currentStageOrder = $project->current_stage_order ?? 1;

            $mappedStages = collect($dynamicStages)->map(function ($stage) use (
                $project, $activeApprovals, $highestStepOrder, $currentStageOrder
            ) {
                $isCurrent = ($currentStageOrder == $stage['order']);

                $existingApproval = $activeApprovals
                    ->where('step_order', $stage['order'])
                    ->where('status', '!=', 'pending')
                    ->sortByDesc('updated_at')
                    ->first();

                $showResubmit = $highestStepOrder > $stage['order'];
                $showApprove = true;
                if ($existingApproval && $existingApproval->status === 'approved') {
                    $showApprove = $project->updated_at->gt($existingApproval->updated_at);
                }

                return [
                    'drop' => $stage['code'],
                    'drop_order' => $stage['order'],
                    'stage_name' => $stage['name_ar'],
                    'stage_name_en' => $stage['name_en'] ?? $stage['name_ar'],
                    'authority_id' => $stage['entity_id'],
                    'entity_id' => $stage['entity_id'],
                    'is_current_stage' => $isCurrent,
                    'is_entity_stage' => $stage['is_entity_stage'],
                    'is_implementation' => $stage['is_implementation'],
                    'entity_name' => $stage['entity_name'] ?? $stage['name_ar'],
                    'phase' => $stage['phase'] ?? null,
                    'phase_name_ar' => $stage['phase_name_ar'] ?? null,
                    'phase_name_en' => $stage['phase_name_en'] ?? null,
                    'show_resubmit' => $showResubmit,
                    'show_approve' => $showApprove,
                    'allowed_authorities' => [],
                ];
            });

            $result = $mappedStages->values()->toArray();

            // Safety net: ensure at least one stage is marked current
            $hasCurrentStage = collect($result)->contains('is_current_stage', true);
            if (! $hasCurrentStage && count($result) > 0) {
                $result[0]['is_current_stage'] = true;
            }

            return $result;

        } catch (\Exception $e) {
            \Log::error('getApprovalStages error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Migrate project data to implementation phase
     */
    public function migrateToImplementation(Project $project): void
    {
        $project->load([
            'preliminaryActivities.procedures.costs',
            'executiveActivities.actions.costs',
        ]);

        DB::transaction(function () use ($project) {
            // 1. Migrate Preliminary Procedures
            foreach ($project->preliminaryActivities as $activity) {
                foreach ($activity->procedures as $procedure) {
                    $plannedAmount = $procedure->costs->sum('total');

                    PreliminaryProcedureExecution::updateOrCreate(
                        ['project_id' => $project->id, 'preliminary_procedure_id' => $procedure->id],
                        [
                            'created_by_user_id' => auth()->id() ?? $project->created_by_user_id,
                            'sequence' => 1,
                            'status' => 'not_started',
                            'completion_percentage' => 0,
                            'actual_amount' => $plannedAmount,
                            'amount_spent' => 0,
                            'remaining_amount' => $plannedAmount,
                        ]
                    );
                }
            }

            // 2. Migrate Executive Activity Actions
            foreach ($project->executiveActivities as $activity) {
                foreach ($activity->actions as $action) {
                    $plannedAmount = $action->costs->sum('total');

                    ProjectExecution::updateOrCreate(
                        ['project_id' => $project->id, 'executive_activity_action_id' => $action->id],
                        [
                            'created_by_user_id' => auth()->id() ?? $project->created_by_user_id,
                            'sequence' => 1,
                            'status' => 'not_started',
                            'completion_percentage' => 0,
                            'actual_amount' => $plannedAmount,
                            'amount_spent' => 0,
                            'remaining_amount' => $plannedAmount,
                        ]
                    );

                    // Auto-create or link a follow-up task for this executive action.
                    $task = Task::where('project_id', $project->id)
                        ->where('executive_activity_action_id', $action->id)
                        ->first();

                    if (! $task) {
                        $task = Task::where('project_id', $project->id)
                            ->whereNull('executive_activity_action_id')
                            ->where('title', $action->action)
                            ->first();
                    }

                    if ($task) {
                        $task->update([
                            'executive_activity_action_id' => $action->id,
                            'start_date' => $task->start_date ?? $action->start_date,
                            'due_date' => $task->due_date ?? $action->end_date,
                        ]);
                    } else {
                        Task::create([
                            'project_id' => $project->id,
                            'executive_activity_action_id' => $action->id,
                            'title' => $action->action,
                            'description' => 'تابع للنشاط التنفيذي: '.$activity->name,
                            'status' => 'todo',
                            'priority' => 'medium',
                            'created_by' => auth()->id() ?? $project->created_by_user_id,
                            'start_date' => $action->start_date,
                            'due_date' => $action->end_date,
                        ]);
                    }
                }
            }
        });

        Log::info('Project data migrated to implementation phase', ['project_id' => $project->id]);
    }

    private function getInternalEntitiesFromJson(): array
    {
        try {
            // Always read from database because legacy data/entities.json doesn't contain 'id'
            // which causes "Undefined array key 'id'" in blade views.

            // Fallback to database if JSON doesn't exist
            return InternalEntity::withoutGlobalScope(DomainScope::class)
                ->withoutGlobalScope('active_only')
                ->whereIn('status', [0, 1])
                ->with('parent')
                ->orderBy('name')
                ->get()
                ->map(function ($entity) {
                    return [
                        'id' => $entity->id,
                        'entity_name' => $entity->name,
                        'father_name' => $entity->parent ? $entity->parent->name : 'لا توجد جهة أب',
                        'parent_id' => $entity->parent_id,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading internal entities from JSON: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Determine current user's reviewer type
     */
    public function getCurrentReviewerType(): string
    {
        $user = auth()->user();
        if (! $user) {
            return 'general';
        }

        $hasFinancial = $user->can('approvals.financial-review');
        $hasTechnical = $user->can('approvals.technical-review');

        if ($hasFinancial && $hasTechnical) {
            return 'general';
        }
        if ($hasFinancial) {
            return 'financial';
        }
        if ($hasTechnical) {
            return 'technical';
        }

        return 'general';
    }
}

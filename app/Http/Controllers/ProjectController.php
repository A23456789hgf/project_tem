<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Project\Services\ProjectExportService;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Http\Controllers\Project\Traits\ProjectExportTrait;
use App\Http\Controllers\Project\Traits\ProjectHandlingTrait;
use App\Http\Controllers\Project\Traits\ProjectValidationTrait;
use App\Models\Action;
use App\Models\Authority;
use App\Models\Domain;
use App\Models\FinancialItem;
use App\Models\InternalEntity;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Subdomain;
use App\Models\SyncLog;
use App\Models\Unit;
use App\Services\FrappeAPIService;
use App\Services\NotificationService;
use App\Traits\ProjectPivotExportTrait;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectController extends Controller
{
    use ProjectExportTrait, ProjectHandlingTrait, ProjectPivotExportTrait, ProjectValidationTrait;

    /**
     * Service instances
     *
     * @var ProjectService
     * @var ProjectExportService
     */
    protected $projectService;

    protected $exportService;

    protected $frappeService;

    protected $notificationService;

    /**
     * Constructor
     */
    public function __construct(
        ProjectService $projectService,
        ProjectExportService $exportService,
        FrappeAPIService $frappeService,
        NotificationService $notificationService
    ) {
        $this->projectService = $projectService;
        $this->exportService = $exportService;
        $this->frappeService = $frappeService;
        $this->notificationService = $notificationService;
    }

    // ==================== BASIC CRUD OPERATIONS ====================

    /**
     * Validate draft completeness and auto-update status.
     * Called on page load (GET) to synchronize project status with checkDraftIntegrity().
     * Returns JSON with integrity result, updated status, and human-readable missing sections.
     */
    public function validateDraftStatus(Project $project): JsonResponse
    {
        // Only apply to draft-stage projects
        if (! in_array($project->status, ['draft', 'completed_draft', 'rolled_back_for_review'])) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'status' => $project->status,
                'message' => 'المشروع ليس في مرحلة المسودة.',
            ]);
        }

        // Load relations required by checkDraftIntegrity() to avoid N+1 queries
        $project->load([
            'detail',
            'mainObjectives',
            'specialObjectives',
            'supervisingAuthorities',
            'implementingEntities',
            'preliminaryActivities',
            'executiveActivities',
            'financings',
            'cost',
        ]);

        $integrity = $project->checkDraftIntegrity();
        $isComplete = $integrity['is_valid'];
        $targetStatus = $project->status === 'rolled_back_for_review'
            ? 'rolled_back_for_review'
            : ($isComplete ? 'completed_draft' : 'draft');

        // Update status and flag only if they have changed
        if ($project->status !== $targetStatus || (bool) $project->is_data_completed !== $isComplete) {
            $project->update([
                'status' => $targetStatus,
                'is_data_completed' => $isComplete,
            ]);
        }

        // Build human-readable list of missing sections
        $sectionLabels = [
            'step_1' => 'البيانات الأساسية (اسم المشروع، البرنامج، المجال، المجال الفرعي)',
            'step_2' => 'تفاصيل المشروع والأهداف',
            'step_3' => 'الجهات الإشرافية والمنفذة',
            'step_4_5' => 'الأنشطة التمهيدية أو التنفيذية',
            'step_6' => 'التمويل والتكلفة الإجمالية',
        ];

        $missingSections = [];
        foreach ($integrity['missing_fields'] as $key => $fields) {
            if (isset($sectionLabels[$key])) {
                $missingSections[] = $sectionLabels[$key];
            }
        }

        return response()->json([
            'success' => true,
            'is_complete' => $isComplete,
            'status' => $targetStatus,
            'completion_pct' => $integrity['completion_percentage'],
            'missing_sections' => $missingSections,
            'missing_fields' => $integrity['missing_fields'],
            'message' => $isComplete
                ? 'المسودة مكتملة – يمكنك الآن إغلاق المسودة وإرسالها للاعتماد.'
                : 'المسودة غير مكتملة – يرجى استكمال البيانات الناقصة.',
        ]);
    }

    /**
     * Display a listing of projects
     *
     * @return View|JsonResponse
     */
    /**
     * Store Step 1: Basic Information (CREATE project)
     * This is the ONLY step that creates a new project record
     */
    public function storeStep1(Request $request)
    {
        // Check if project_id exists in request or session to determine if we update or create
        $projectId = $request->input('project_id') ?? session('current_project_id');

        if ($projectId) {
            return $this->stepSave($request, 1, false);
        }

        return $this->stepSave($request, 1, true);
    }

    /**
     * Store Step 2: Details & Objectives (UPDATE project)
     */
    public function storeStep2(Request $request)
    {
        return $this->stepSave($request, 2, false);
    }

    /**
     * Store Step 3: Risks & Entities (UPDATE project)
     */
    public function storeStep3(Request $request)
    {
        return $this->stepSave($request, 3, false);
    }

    /**
     * Store Step 4: Preliminary Activities (UPDATE project)
     */
    public function storeStep4(Request $request)
    {
        return $this->stepSave($request, 4, false);
    }

    /**
     * Store Step 5: Executive Activities (UPDATE project)
     */
    public function storeStep5(Request $request)
    {
        return $this->stepSave($request, 5, false);
    }

    /**
     * Store Step 6: Costs & Financing (UPDATE project)
     */
    public function storeStep6(Request $request)
    {
        return $this->stepSave($request, 6, false);
    }

    /**
     * Store Step 7: Final Review (UPDATE project)
     */
    public function storeStep7(Request $request)
    {
        return $this->stepSave($request, 7, false);
    }

    /**
     * Generic helper to handle saving for a specific step
     *
     * @param  int  $step  Current step number (1-7)
     * @param  bool  $isCreate  Whether this is step 1 (create) or subsequent steps (update)
     */
    protected function stepSave(Request $request, int $step, bool $isCreate = false)
    {
        DB::beginTransaction();
        try {
            $this->normalizeRequestData($request);

            $navDir = $request->input('navigation_direction');
            // A save is considered a draft save (bypassing validation) if the overall status is draft
            // AND we are explicitly saving as draft or navigating to previous step.
            // If we are navigating to the next step, we strictly validate.
            $isDraft = $request->input('status') === 'draft';
            $isDraftSave = $isDraft || in_array($navDir, ['draft', 'prev', 'back']);
            // "Complete draft" = explicit "حفظ المشروع كمسودة مكتملة" button (step 7, draft direction).
            // It still saves the data but then VERIFIES completeness before reporting success.
            $isCompleteDraft = ($navDir === 'draft' && $step === 7);

            // CREATE or UPDATE project based on step and existing IDs
            $projectId = $request->input('project_id') ?? session('current_project_id');
            $project = $projectId ? Project::find($projectId) : null;

            // Calculate the last_saved_step (never decrease it, but increment if navigating next)
            $currentLastSavedStep = $project ? ($project->last_saved_step ?? 1) : 1;
            $newLastSavedStep = $currentLastSavedStep;

            if ($navDir === 'next' && $step < 7) {
                $newLastSavedStep = max($currentLastSavedStep, $step + 1);
            } else {
                $newLastSavedStep = max($currentLastSavedStep, $step);
            }

            // Prepare validated data
            $validated = [];

            if ($isCompleteDraft) {
                // Persist the data as a draft, but completeness is verified AFTER the save (below).
                if ($step === 1) {
                    $request->validate(['project_name' => 'required|string|max:255']);
                }
                $validated = $request->all();
                $validated['last_saved_step'] = $newLastSavedStep;
                $validated['draft_saved_at'] = now();
            } elseif ($isDraftSave) {
                if ($step === 1) {
                    $request->validate(['project_name' => 'required|string|max:255']);
                }
                $validated = $request->all();
                $validated['last_saved_step'] = $newLastSavedStep;
                $validated['draft_saved_at'] = now();
            } else {
                // For final saves, apply validation
                $method = "getStep{$step}Rules";
                if (! method_exists($this, $method)) {
                    throw new \Exception("Validation rules for step {$step} not found.");
                }

                // For finalization from a draft, merge existing data for fields not in the request
                if ($project && $step === 7 && ! $isDraft) {
                    $basicFields = [
                        'project_name',
                        'program_id',
                        'domain_id',
                        'subdomain_id',
                        'intervention_id',
                        'priority_id',
                    ];

                    foreach ($basicFields as $field) {
                        if (! $request->has($field) || empty($request->input($field))) {
                            if ($project->$field) {
                                $request->merge([$field => $project->$field]);
                            }
                        }
                    }
                }

                $rules = $this->$method(false);
                $validator = Validator::make($request->all(), $rules);

                // Apply final project validation requirements (weight validation, etc.) only when finalized
                if ($request->input('status') === 'final') {
                    $validator->after(function ($validator) use ($request, $project) {
                        $this->validateFinalProjectRequirements($validator, $request, $project);
                    });
                }

                $validated = $validator->validate();
                if ($request->input('status') === 'draft') {
                    $validated['last_saved_step'] = $newLastSavedStep;
                    $validated['draft_saved_at'] = now();
                } else {
                    $validated['last_saved_step'] = $newLastSavedStep;
                }
            }

            if ($step === 1 && ! $project) {
                // ===================================================================
                // ERPNext Verification (Pre-check before creating the project)
                // ===================================================================
                $user = auth()->user();
                $entity = $user->entity;

                // ERPNext validation only applies to internal entity users.
                // External authority users (organization_type === 'external') do not have
                // an associated InternalEntity, so this block is intentionally skipped for them.
                if ($entity) {
                    $frappeService = app(FrappeAPIService::class);

                    try {
                        // 1. Verify/Create in ERPNext
                        $ensureResult = $frappeService->resolveAndEnsureEntityInErpNext($entity->name);

                        if (! $ensureResult['erpnext_found'] && ! $ensureResult['erpnext_created']) {
                            \Log::error('ERPNext Verification Failed: Could not find or create entity.', ['entity' => $entity->name, 'result' => $ensureResult]);
                            throw ValidationException::withMessages([
                                'erpnext' => ['فشل التحقق من الجهة أو إنشاؤها في نظام ERPNext. لا يمكن إنشاء المشروع.'],
                            ]);
                        }

                        // 2. Resolve Parent Company for Departments
                        $companyName = $entity->name;
                        if ($entity->entity_type === 'Department') {
                            $companyName = $frappeService->findParentCompanyName($entity);
                            if (! $companyName) {
                                \Log::error('ERPNext Verification Failed: Could not resolve parent Company for Department.', ['department' => $entity->name]);
                                throw ValidationException::withMessages([
                                    'erpnext' => ['فشل تحديد الشركة الأب (Company) المرتبطة بهذه الإدارة. تأكد من الهيكل التنظيمي للجهة.'],
                                ]);
                            }
                        }

                        // 3. Fetch Financial Items
                        $items = $frappeService->getExpenseClaimTypes($companyName);

                        if ($items === false || is_null($items)) {
                            \Log::error('ERPNext Verification Failed: API error while fetching financial items.', ['company' => $companyName]);
                            throw ValidationException::withMessages([
                                'erpnext' => ['فشل الاتصال بنظام ERPNext لجلب البنود المالية للجهة (Expense Claim Types). تأكد من صحة الاتصال.'],
                            ]);
                        }

                        if (empty($items)) {
                            \Log::info('ERPNext Verification: Fetched items successfully, but count is 0.', ['company' => $companyName]);
                            // We do NOT block saving if count is 0, just log it.
                        } else {
                            \Log::info('ERPNext Verification: Successfully fetched items.', ['company' => $companyName, 'count' => count($items)]);
                        }

                    } catch (ValidationException $e) {
                        throw $e; // Re-throw validation exceptions
                    } catch (\Exception $e) {
                        \Log::error('ERPNext Verification Exception: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                        // If it's a 401 or network error from FrappeService, it will likely be caught here or return false above.
                        throw ValidationException::withMessages([
                            'erpnext' => ['حدث خطأ أثناء التخاطب مع نظام ERPNext: '.$e->getMessage()],
                        ]);
                    }
                }

                // STEP 1: CREATE new project (only if no ID exists and ERPNext passed)
                $project = $this->createProjectFromStep1($validated);
                session(['current_project_id' => $project->id]);
            } else {
                // UPDATE existing project (Step 1 correction OR Steps 2-7)
                if (! $project) {
                    throw new \Exception('No project ID found. Please start from step 1.');
                }

                if ($step === 1) {
                    // UPDATE existing Step 1 record
                    $project->update($validated);
                } else {
                    // STEPS 2-7: UPDATE existing project
                    $this->updateProjectForStep($project, $validated, $step);
                }
                // Ensure session ID is synced
                session(['current_project_id' => $project->id]);
            }

            // Process all project components (activities, costs, risks, etc.)
            $this->projectService->processProjectComponents($project, $validated, $request, $isDraft);

            DB::commit();

            // ===================================================================
            // Completeness verification and status update: draft vs completed_draft
            // Data was already saved above; now confirm if all required data is complete.
            // ===================================================================
            $project->refresh();
            $isComplete = $project->isDraftComplete();

            $targetStatus = $project->status === 'rolled_back_for_review'
                ? 'rolled_back_for_review'
                : ($isComplete ? 'completed_draft' : 'draft');

            $project->update([
                'is_data_completed' => $isComplete,
                'last_saved_step' => $newLastSavedStep,
                'status' => $targetStatus,
                'draft_saved_at' => now(),
            ]);

            $missingFields = $isComplete ? [] : $project->getMissingRequiredFields();
            $message = $isComplete ? 'تم حفظ المشروع كمسودة مكتملة بنجاح' : 'تم حفظ المسودة بنجاح';
            if (! $isComplete && ! empty($missingFields) && ($step === 7 || $navDir === 'draft')) {
                $message = 'تم حفظ المشروع كمسودة، لكن هناك بيانات ناقصة: '.implode('، ', $missingFields);
            }

            return response()->json([
                'success' => true,
                'project_id' => $project->id,
                'message' => $message,
                'status' => $targetStatus,
                'step' => $step,
                'last_saved_step' => $newLastSavedStep,
                'is_draft_complete' => $isComplete,
                'missing_fields' => $missingFields,
                'is_create' => $isCreate,
                'is_draft' => true,
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();

            return $this->handleValidationError($request, $e);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('stepSave error: '.$e->getMessage()."\n".$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Save failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * CREATE a new project from Step 1 data
     * This is called ONLY once when starting a new project
     */
    private function createProjectFromStep1(array $validated): Project
    {
        $user = auth()->user();
        $validated['created_by_user_id'] = $user->id;
        $validated['status'] = 'draft';
        $validated['last_saved_step'] = 1;
        $validated['draft_saved_at'] = now();

        if ($user->organization_type === 'external') {
            // External authority user: track authority and mark source as external.
            // creator_entity_id is intentionally left null — HasCreatorTracking will not
            // find an entity for this user, which is correct.
            $validated['source_type'] = 'external';
            $validated['authority_id'] = $user->authority_id;
            // Store authority name as the "entity" string for display/search purposes.
            $validated['created_by_entity'] = $user->authority?->agency_name ?? $user->department;
        } else {
            // Internal entity user: mark source as internal.
            // creator_entity_id and created_by_entity are handled by HasCreatorTracking
            // (bootHasCreatorTracking) which fires automatically on Project::create().
            $validated['source_type'] = 'internal';
            // Always store the entity NAME string so geo-scope filtering works correctly.
            $validated['created_by_entity'] = $user->entity?->name ?? $user->department;
        }

        $project = Project::create($validated);

        return $project;
    }

    /**
     * UPDATE existing project for steps 2-7
     * This reuses the same project_id created in step 1
     */
    private function updateProjectForStep(Project $project, array $validated, int $step): void
    {
        $updateData = $validated;
        // Don't blindly overwrite last_saved_step if it was already calculated correctly (e.g., keeping it 6 at step 7)
        if (! isset($updateData['last_saved_step'])) {
            $updateData['last_saved_step'] = $step === 7 ? 6 : $step;
        }
        $updateData['draft_saved_at'] = now();

        $user = auth()->user();
        if ($user) {
            $updateData['updated_by_user_id'] = $user->id;
            $updateData['updated_by_entity'] = $user->entity?->name ?? $user->department;
        }

        $project->update($updateData);
    }

    public function index(Request $request)
    {
        if ($request->has('draft_saved')) {
            session()->flash('success', 'تم حفظ المشروع كمسودة بنجاح.');
        }

        return $this->projectService->getProjects($request);
    }

    /**
     * Display a listing of projects in implementation phase
     *
     * @return View
     */
    public function implementationIndex(Request $request)
    {
        try {
            $query = $this->projectService->buildProjectsQuery($request, [
                'approved',
                'internally_approved',
                'in_execution',
                'implementation',
                'in_progress',
            ]);

            $projects = $query->with([
                'program',
                'preliminaryActivities.procedures.executions',
                'executiveActivities.actions.executions',
            ])->orderBy('created_at', 'desc')->paginate(15);
            $programs = Program::all();

            return view('projects.partials.implementation.index', compact('projects', 'programs'));
        } catch (\Exception $e) {
            return redirect()->route('projects.index')
                ->with('error', 'فشل تحميل المشاريع قيد التنفيذ: '.$e->getMessage());
        }
    }

    /**
     * Manually sync a project to ERPNext
     *
     * @return RedirectResponse
     */
    public function syncProjectToErp(Project $project)
    {
        try {
            // Prepare data and send to ERPNext
            $result = $this->frappeService->sendProjectOnExecution($project, true);

            if (isset($result['success']) && $result['success']) {
                $frappeProjectId = $result['data']['name'] ?? $result['name'] ?? null;

                // Update project with sync info
                $project->update([
                    'erpnext_project_id' => $frappeProjectId,
                    'frappe_project_id' => $frappeProjectId,
                    'frappe_project_name' => $result['data']['project_name'] ?? null,
                    'sync_status' => 'synced',
                    'frappe_sync_status' => 'success',
                    'synced_to_erpnext_at' => now(),
                    'frappe_synced_at' => now(),
                    'sync_error' => null,
                ]);

                // Log success
                SyncLog::create([
                    'syncable_id' => $project->id,
                    'syncable_type' => Project::class,
                    'status' => 'success',
                    'sync_type' => 'manual_sync',
                    'response_data' => $result,
                    'message' => 'تمت المزامنة بنجاح',
                ]);

                return redirect()->back()->with('success', 'تمت مزامنة المشروع مع ERPNext بنجاح. معرف المشروع: '.$frappeProjectId);
            } else {
                // Log failure
                $errorMessage = $result['message'] ?? 'فشل غير معروف في المزامنة';

                $project->update([
                    'sync_status' => 'failed',
                    'frappe_sync_status' => 'failed',
                    'sync_error' => $errorMessage,
                ]);

                SyncLog::create([
                    'syncable_id' => $project->id,
                    'syncable_type' => Project::class,
                    'status' => 'failed',
                    'sync_type' => 'manual_sync',
                    'message' => $errorMessage,
                    'response_data' => $result,
                ]);

                return redirect()->back()->with('error', 'فشلت المزامنة مع ERPNext: '.$errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('Manual ERP sync error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء المزامنة: '.$e->getMessage());
        }
    }

    /**
     * Bulk sync all eligible projects to ERPNext
     *
     * @return RedirectResponse
     */
    public function bulkSyncProjectsToErp(Request $request)
    {
        try {
            $projects = Project::whereIn('status', ['approved', 'internally_approved', 'in_execution', 'implementation', 'in_progress'])
                ->where(function ($query) {
                    $query->whereNull('erpnext_project_id')
                        ->orWhere('sync_status', 'failed');
                })
                ->get();

            if ($projects->isEmpty()) {
                return redirect()->back()->with('info', 'لا توجد مشاريع بحاجة للمزامنة حالياً.');
            }

            $successCount = 0;
            $failCount = 0;
            $errors = [];

            foreach ($projects as $project) {
                try {
                    $result = $this->frappeService->sendProjectOnExecution($project, true);

                    if (isset($result['success']) && $result['success']) {
                        $frappeProjectId = $result['data']['name'] ?? $result['name'] ?? null;

                        $project->update([
                            'erpnext_project_id' => $frappeProjectId,
                            'frappe_project_id' => $frappeProjectId,
                            'frappe_project_name' => $result['data']['project_name'] ?? null,
                            'sync_status' => 'synced',
                            'frappe_sync_status' => 'success',
                            'synced_to_erpnext_at' => now(),
                            'frappe_synced_at' => now(),
                            'sync_error' => null,
                        ]);

                        SyncLog::create([
                            'syncable_id' => $project->id,
                            'syncable_type' => Project::class,
                            'status' => 'success',
                            'sync_type' => 'bulk_sync',
                            'response_data' => $result,
                            'message' => 'تمت المزامنة بنجاح ضمن المزامنة الجماعية',
                        ]);

                        $successCount++;
                    } else {
                        $errorMessage = $result['message'] ?? 'فشل غير معروف';
                        $project->update([
                            'sync_status' => 'failed',
                            'frappe_sync_status' => 'failed',
                            'sync_error' => $errorMessage,
                        ]);

                        SyncLog::create([
                            'syncable_id' => $project->id,
                            'syncable_type' => Project::class,
                            'status' => 'failed',
                            'sync_type' => 'bulk_sync',
                            'message' => $errorMessage,
                            'response_data' => $result,
                        ]);

                        $failCount++;
                        $errors[] = "مشروع '{$project->project_name}': {$errorMessage}";
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = "مشروع '{$project->project_name}': ".$e->getMessage();

                    SyncLog::create([
                        'syncable_id' => $project->id,
                        'syncable_type' => Project::class,
                        'status' => 'failed',
                        'sync_type' => 'bulk_sync_exception',
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $message = "تمت عملية المزامنة الجماعية: بنجاح ({$successCount})";
            if ($failCount > 0) {
                $message .= "، فشل ({$failCount})";

                return redirect()->back()->with('warning', $message.'. الأخطاء: '.implode(' | ', array_slice($errors, 0, 3)));
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Bulk ERP sync error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء المزامنة الجماعية: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new project
     *
     * @return View
     */
    public function create()
    {
        return $this->projectService->getCreateData();
    }

    /**
     * Store a newly created project
     *
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request)
    {
        return $this->handleProjectStore($request);
    }

    /**
     * Display the specified project
     *
     * @return View
     */
    public function show(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        return $this->projectService->getProjectWithDetails($project);
    }

    /**
     * Print financial status of the specified project
     *
     * @return View
     */
    public function printFinancial(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $financialStatus = $this->projectService->calculateProjectFinancialStatus($project);

        return view('projects.reports.print_project_financial', compact('project', 'financialStatus'));
    }

    /**
     * Show the form for editing the specified project
     *
     * @return View
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return $this->projectService->getEditData($project);
    }

    /**
     * Show the form for completing an old project's missing data.
     *
     * @return View|RedirectResponse
     */
    public function completeData(Project $project)
    {
        return $this->projectService->completeData($project);
    }

    /**
     * Update the specified project
     *
     * @return RedirectResponse|JsonResponse
     */
    public function update(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $this->authorize('update', $project);

        return $this->handleProjectUpdate($request, $project);
    }

    /**
     * Remove the specified project
     *
     * @return RedirectResponse
     */
    public function destroy(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $this->authorize('delete', $project);

        try {
            if ($project->status !== 'draft') {
                return redirect()->route('projects.index')
                    ->with('error', 'Cannot delete projects that are under approval. Only draft projects can be deleted.');
            }

            $this->projectService->deleteProject($project);

            return redirect()->route('projects.index')
                ->with('success', "Project '{$project->project_name}' has been deleted successfully.");
        } catch (\Exception $e) {
            return redirect()->route('projects.index')
                ->with('error', 'Failed to delete project: '.$e->getMessage());
        }
    }

    // ==================== AUTO-SAVE OPERATIONS ====================

    /**
     * Auto-save project data (for new projects)
     */
    public function autoSave(Request $request): JsonResponse
    {
        return $this->handleAutoSave($request);
    }

    /**
     * Auto-save project data (for existing projects)
     */
    public function autoSaveUpdate(Request $request, Project $project): JsonResponse
    {
        return $this->handleAutoSaveUpdate($request, $project);
    }

    // ==================== RISK MANAGEMENT OPERATIONS ====================

    /**
     * Get project risks
     *
     * @return View|JsonResponse
     */
    public function getRisks(Project $project, Request $request)
    {
        return $this->projectService->getProjectRisks($project, $request);
    }

    /**
     * Get project risks list for dropdowns
     *
     * @return JsonResponse
     */
    public function getRisksList(Project $project)
    {
        return response()->json([
            'risks' => $project->risks()->select('id', 'risk', 'risk_rate')->get(),
        ]);
    }

    /**
     * Get project outputs list for dropdowns
     *
     * @return JsonResponse
     */
    public function getOutputsList(Project $project)
    {
        return response()->json([
            'outputs' => $project->resultOutputs()->select('id', 'output')->get(),
        ]);
    }

    /**
     * Analyze project risks
     *
     * @return RedirectResponse|JsonResponse
     */
    public function analyzeRisks(Project $project, Request $request)
    {
        return $this->projectService->analyzeProjectRisks($project, $request);
    }

    /**
     * Delete project risk
     *
     * @param  int  $riskId
     * @return JsonResponse
     */
    public function deleteRisk(Project $project, $riskId, Request $request)
    {
        return $this->projectService->deleteProjectRisk($project, $riskId, $request);
    }

    // ==================== REVIEW & FINALIZE OPERATIONS ====================

    /**
     * Review project before finalization
     *
     * @return View
     */
    public function review(Project $project)
    {
        $project = $this->projectService->reviewProject($project);

        return view('projects.review', compact('project'));
    }

    /**
     * Approve project internally
     */
    public function approveInternally(Project $project)
    {
        // Check both permission and record-level scope using the unified hasPermission.
        // If the record is out of scope, hasPermission returns false.
        if (! auth()->user()->hasPermission('projects.approve', $project)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا المشروع أو لا تملك الصلاحية المطلوبة لاعتماده.');
        }

        // تنفيذ الاعتماد
        $this->projectService->approveProjectInternally($project);

        return redirect()
            ->route('projects.index')
            ->with('success', 'تم اعتماد المشروع داخلياً بنجاح.');
    }

    /**
     * Finalize project
     *
     * @return RedirectResponse|JsonResponse
     */
    public function finalize(Request $request, Project $project)
    {
        try {
            $success = $this->projectService->finalizeProject($request, $project);

            if ($success) {
                return redirect()->route('projects.show', $project->id)
                    ->with('success', 'تم تأكيد المسودة بنجاح. انتقل المشروع إلى مرحلة الموافقة.');
            }

            return redirect()->route('projects.show', $project->id)
                ->with('error', 'فشل تأكيد المسودة. يرجى المحاولة مرة أخرى.');
        } catch (\Exception $e) {
            Log::error('Error finalizing project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('projects.show', $project->id)
                ->with('error', 'حدث خطأ أثناء تأكيد المسودة: '.$e->getMessage());
        }
    }

    /**
     * Revert a finalized project back to draft
     * Allows user to edit the project again
     */
    public function revertToDraft(Request $request, Project $project)
    {
        return $this->projectService->revertProjectToDraft($project);
    }

    // ==================== EXPORT OPERATIONS ====================

    /**
     * Export project to PDF (simple version)
     *
     * @return BinaryFileResponse
     */
    public function exportPdf(Project $project)
    {
        return $this->exportService->exportProjectPdf($project);
    }

    /**
     * Export project to PDF (detailed version)
     *
     * @return BinaryFileResponse
     */
    public function exportProjectPdf(Project $project)
    {
        return $this->exportService->exportDetailedProjectPdf($project);
    }

    /**
     * Export project to Excel
     *
     * @return BinaryFileResponse
     */
    public function exportProjectExcel(Project $project)
    {
        return $this->exportService->exportProjectExcel($project);
    }

    /**
     * Export project to Comprehensive Excel (Single Sheet)
     */
    public function exportProjectExcelComprehensive(Project $project)
    {
        return $this->exportService->exportProjectExcelComprehensive($project);
    }

    /**
     * Export all projects to Excel with filters
     *
     * @return BinaryFileResponse
     */
    public function exportAllProjectsExcel(Request $request)
    {
        return $this->exportService->exportAllProjectsExcel($request);
    }

    /**
     * Export all projects to Comprehensive Excel (Single Sheet)
     */
    public function exportAllProjectsExcelComprehensive(Request $request)
    {
        return $this->exportService->exportAllProjectsExcelComprehensive($request);
    }

    /**
     * Export all projects to PDF with filters
     *
     * @return BinaryFileResponse
     */
    public function exportAllProjectsPdf(Request $request)
    {
        return $this->exportService->exportAllProjectsPdf($request);
    }

    /**
     * Export projects to Excel (legacy method)
     *
     * @return BinaryFileResponse
     */
    public function exportExcel()
    {
        return $this->exportService->exportProjectsExcel();
    }

    /**
     * Print project in simple format
     *
     * @return View
     */
    public function print(Project $project)
    {
        $project->load([
            'program',
            'domain',
            'subdomain',
            'intervention',
            'priority',
            'targetCategory',
            'detail',
            'locations.governorate',
            'locations.directorate',
            'locations.subArea',
            'locations.village',
            'mainObjectives',
            'specialObjectives.results.outputs',
            'objectiveResults',
            'resultOutputs',
            'risks',
            'cost',
            'financings.fundingSource',
            'financings.authority',
            'financings.financingType',
            'financings.financingForm',
            'financings.subFinancingForm',
            'supervisingAuthorities.authority',
            'supervisingAuthorities.parent',
            'implementingEntities.authority',
            'implementingEntities.parent',
            'participatingEntities.authority',
            'participatingEntities.parent',
            'beneficiaryEntities.authority',
            'beneficiaryEntities.parent',
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
            'executiveFinancialSummaries.financialItem',
            'executiveFinancialSummaries.activity',
            'executiveFinancialSummaries.action',
            'transactions.user',
            'projectApprovals.entity',
            'projectApprovals.approvalFlow',
            'projectApprovals.createdBy',
            'documents',
        ]);

        $qrCodeUrl = route('projects.show', $project->id);
        $qrCodeImage = '';
        if (class_exists(QrCode::class)) {
            try {
                $qrCode = new QrCode($qrCodeUrl);
                $writer = new SvgWriter;
                $qrCodeImage = $writer->write($qrCode)->getDataUri();
            } catch (\Throwable $e) {
            }
        }

        $totalFinancing = $project->financings()->sum('financing_amount');
        $totalProjectCost = $project->cost->total_cost ?? $totalFinancing;

        // تحويل الشعار إلى Base64
        $logoPath = public_path('images/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,'.base64_encode($logoData);
        }

        // Prepare Preliminary Activities Data
        $preliminaryTotal = $project->preliminaryActivities->flatMap(fn ($a) => $a->procedures)->flatMap(fn ($p) => $p->costs)->sum('total');
        $preliminaryCosts = $project->preliminaryActivities->flatMap(fn ($a) => $a->procedures)->flatMap(fn ($p) => $p->costs)->groupBy('financial_item_id');

        // Prepare Executive Activities Data
        $executiveTotal = $project->executiveActivities->flatMap(fn ($a) => $a->actions)->flatMap(fn ($ac) => $ac->costs)->sum('total');
        $executiveCosts = $project->executiveActivities->flatMap(fn ($a) => $a->actions)->flatMap(fn ($ac) => $ac->costs)->groupBy('financial_item_id');

        return view('projects.print', [
            'project' => $project,
            'qrCodeBase64' => $qrCodeImage,
            'logoBase64' => $logoBase64,
            'qrCodeUrl' => $qrCodeUrl,
            'user' => auth()->user(),
            'printDate' => now()->format('Y-m-d'),
            'exportDate' => now()->format('Y-m-d'),
            'totalProjectCost' => $totalProjectCost,
            'preliminaryTotal' => $preliminaryTotal,
            'preliminaryCosts' => $preliminaryCosts,
            'executiveTotal' => $executiveTotal,
            'executiveCosts' => $executiveCosts,
        ]);
    }

    public function execution(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        // للمشاريع القديمة: توجيه المستخدم لرفع وتسجيل الإنجازات عبر achievements
        if ($project->project_type === 'old') {
            return redirect()->route('projects.achievements.create', $project->id);
        }

        try {
            $project->load([
                'preliminaryActivities.assignments.assignedTo',
                'preliminaryActivities.procedures.costs.financialItem',
                'preliminaryActivities.procedures.executions.delayExplanation',
                'preliminaryActivities.procedures.assignments.assignedTo',
                'preliminaryActivities.procedures.budgetJustification',
                'preliminaryActivities.procedures.budgetJustifications',
                'preliminaryActivities.procedures.procedureBudgetJustification.createdBy',
                'preliminaryActivities.procedures.procedureBudgetJustification.reviewedBy',
                'preliminaryActivities.procedures.technicalJustification',
                'preliminaryActivities.procedures.technicalJustifications.createdBy',
                'preliminaryActivities.procedures.technicalJustifications.reviewedBy',
                'preliminaryActivities.procedures.assignedEntity',
                'preliminaryFinancialSummaries.financialItem',
                'preliminaryFinancialSummaries.activity',
                'preliminaryFinancialSummaries.procedure',
                'preliminaryFinancialSummaries.cost',
                'executiveActivities.assignments.assignedTo',
                'executiveActivities.actions.assignedEntities',
                'executiveActivities.actions.costs.financialItem',
                'executiveActivities.actions.executions.delayExplanation',
                'executiveActivities.actions.execution.delayExplanation',
                'executiveActivities.actions.assignments.assignedTo',
                'executiveActivities.actions.budgetJustification.createdBy',
                'executiveActivities.actions.budgetJustification.reviewedBy',
                'executiveActivities.actions.technicalJustification',
                'executiveActivities.actions.technicalJustifications.createdBy',
                'executiveActivities.actions.technicalJustifications.reviewedBy',
                'executiveFinancialSummaries',
                'cost',
            ]);
        } catch (\Exception $e) {
            // If eager loading fails, load what we can
            $project->load([
                'preliminaryActivities.procedures.costs.financialItem',
                'preliminaryActivities.procedures.assignments.assignedTo',
                'preliminaryActivities.procedures.budgetJustification',
                'preliminaryActivities.procedures.budgetJustifications',
                'preliminaryActivities.procedures.technicalJustifications',
                'executiveActivities.actions.costs.financialItem',
                'executiveActivities.actions.assignments.assignedTo',
                'executiveActivities.actions.budgetJustification',
                'executiveActivities.actions.technicalJustifications',
            ]);
        }

        return view('projects.execution', compact('project'));
    }

    public function schedule(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        try {
            $project->load([
                'preliminaryActivities.procedures.costs.financialItem',
                'preliminaryActivities.procedures.executions',
                'executiveActivities.actions',
            ]);
        } catch (\Exception $e) {
            $project->load([
                'preliminaryActivities.procedures',
                'executiveActivities.actions',
            ]);
        }

        return view('projects.schedule', compact('project'));
    }

    // ==================== AJAX DYNAMIC FORM OPERATIONS ====================

    /**
     * Get domains for dynamic dropdown
     *
     * @return JsonResponse
     */
    public function getDomains()
    {
        return response()->json($this->projectService->getDomains());
    }

    /**
     * Get subdomains for specific domain
     *
     * @param  int  $domainId
     * @return JsonResponse
     */
    public function getSubdomains($domainId)
    {
        return $this->projectService->getSubdomainsJson($domainId);
    }

    /**
     * Get interventions for specific subdomain
     *
     * @param  int  $subdomainId
     * @return JsonResponse
     */
    public function getInterventions($subdomainId)
    {
        return $this->projectService->getInterventionsJson($subdomainId);
    }

    /**
     * Get subdomains as JSON (alias for getSubdomains)
     *
     * @param  int  $domainId
     * @return JsonResponse
     */
    public function getSubdomainsJson($domainId)
    {
        return $this->projectService->getSubdomainsJson($domainId);
    }

    /**
     * Get interventions as JSON (alias for getInterventions)
     *
     * @param  int  $subdomainId
     * @return JsonResponse
     */
    public function getInterventionsJson($subdomainId)
    {
        return $this->projectService->getInterventionsJson($subdomainId);
    }

    // ==================== FINANCIAL SUMMARY OPERATIONS ====================

    /**
     * Get preliminary financial summary
     *
     * @return View|JsonResponse
     */
    public function getPreliminaryFinancialSummary(Project $project)
    {
        return $this->projectService->getPreliminaryFinancialSummary($project);
    }

    /**
     * Get executive financial summary
     *
     * @return View|JsonResponse
     */
    public function getExecutiveFinancialSummary(Project $project)
    {
        return $this->projectService->getExecutiveFinancialSummary($project);
    }

    /**
     * Get financings summary
     *
     * @return View|JsonResponse
     */
    public function getFinancingsSummary(Project $project)
    {
        return $this->projectService->getFinancingsSummary($project);
    }

    /**
     * Get preliminary financial summaries
     *
     * @return View|JsonResponse
     */
    public function getPreliminaryFinancialSummaries(Project $project)
    {
        return $this->projectService->getPreliminaryFinancialSummaries($project);
    }

    // ==================== DYNAMIC FORM ROWS OPERATIONS ====================

    /**
     * Get executive activity row HTML
     *
     * @return Response
     */
    public function getExecutiveActivityRow(Request $request)
    {
        return $this->projectService->getExecutiveActivityRow($request);
    }

    /**
     * Get executive activity action row HTML
     *
     * @return Response
     */
    public function getExecutiveActivityActionRow(Request $request)
    {
        return $this->projectService->getExecutiveActivityActionRow($request);
    }

    /**
     * Get executive action assigned row HTML
     *
     * @return Response
     */
    public function getExecutiveActionAssignedRow(Request $request)
    {
        return $this->projectService->getExecutiveActionAssignedRow($request);
    }

    /**
     * Get executive action cost row HTML
     *
     * @return Response
     */
    public function getExecutiveActionCostRow(Request $request)
    {
        return $this->projectService->getExecutiveActionCostRow($request);
    }

    /**
     * Get financing row HTML
     *
     * @return Response
     */
    public function getFinancingRow(Request $request)
    {
        return $this->projectService->getFinancingRow($request);
    }

    // ==================== DOCUMENT OPERATIONS (Delegated to Document Controller) ====================

    /**
     * Upload project documents
     *
     * @return RedirectResponse|JsonResponse
     */
    public function uploadDocuments(Request $request, Project $project)
    {
        $request->validate([
            'project_document' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx|max:20480',
            'project_card' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480',
            'approval_request' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'other_documents' => 'nullable|array',
            'other_documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx|max:20480',
        ]);

        $uploadedDocuments = [];

        $documentTypes = [
            'project_document' => 'project_document',
            'project_card' => 'project_card',
            'approval_request' => 'approval_request',
        ];

        foreach ($documentTypes as $fieldName => $documentType) {
            if ($request->hasFile($fieldName)) {
                $file = $request->file($fieldName);
                $path = $file->store("projects/{$project->id}/documents", 'public');

                $document = ProjectDocument::create([
                    'project_id' => $project->id,
                    'document_type' => $documentType,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);

                $uploadedDocuments[] = $document;
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم رفع الوثائق بنجاح',
                'documents' => $uploadedDocuments,
            ]);
        }

        return redirect()->back()->with('success', 'تم رفع الوثائق بنجاح');
    }

    /**
     * Get project documents
     */
    public function getProjectDocuments(Project $project): JsonResponse
    {
        $documents = $project->documents()
            ->select('id', 'document_type', 'file_name', 'file_size', 'mime_type', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'documents' => $documents,
        ]);
    }

    /**
     * Upload document via AJAX
     */
    public function uploadDocument(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx|max:20480',
            'document_type' => 'required|string|in:project_document,project_card,approval_request,other',
        ]);

        $file = $request->file('document');
        $path = $file->store("projects/{$project->id}/documents", 'public');

        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الوثيقة بنجاح',
            'document' => $document,
        ]);
    }

    // public function getLastDraft(): JsonResponse
    // {
    //     $draftProject = Project::where('status', 'draft')
    //         ->orderByDesc('draft_saved_at')
    //         ->first();

    //     if (!$draftProject) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'لا توجد مسودات محفوظة',
    //             'has_draft' => false,
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'has_draft' => true,
    //         'draft' => [
    //             'id' => $draftProject->id,
    //             'project_name' => $draftProject->project_name,
    //             'last_saved_step' => $draftProject->last_saved_step ?? 1,
    //             'draft_saved_at' => $draftProject->draft_saved_at?->format('Y-m-d H:i'),
    //         ],
    //     ]);
    // }

    /**
     * Resume a draft project - loads create form at last saved step
     */
    public function getLastDraft(): JsonResponse
    {
        $user = auth()->user();
        if ($user->isExternal()) {
            $entityIds = $user->authority_id ? [$user->authority_id] : [];
        } else {
            $entityIds = InternalEntity::getAllChildrenIds($user->entity_id);
        }

        $draftProject = Project::where('status', 'draft')
            ->where(function ($q) use ($entityIds) {

                // نفس منطق الفلترة المستخدم في النظام
                $q->whereIn('creator_entity_id', $entityIds)
                    ->orWhereIn('created_by_entity', $entityIds)
                    ->orWhereIn('internal_entity_id', $entityIds)

                    ->orWhereHas('supervisingAuthorities', function ($q2) use ($entityIds) {
                        $q2->whereIn('entities.id', $entityIds);
                    })

                    ->orWhereHas('implementingEntities', function ($q2) use ($entityIds) {
                        $q2->whereIn('entities.id', $entityIds);
                    })

                    ->orWhereHas('participatingEntities', function ($q2) use ($entityIds) {
                        $q2->whereIn('entities.id', $entityIds);
                    })

                    ->orWhereHas('beneficiaryEntities', function ($q2) use ($entityIds) {
                        $q2->whereIn('entities.id', $entityIds);
                    });
            })
            ->orderByDesc('draft_saved_at')
            ->first();

        if (! $draftProject) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد مسودات محفوظة',
                'has_draft' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'has_draft' => true,
            'draft' => [
                'id' => $draftProject->id,
                'project_name' => $draftProject->project_name,
                'last_saved_step' => $draftProject->last_saved_step ?? 1,
                'draft_saved_at' => $draftProject->draft_saved_at?->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function resumeDraft(Project $project)
    {
        $this->authorize('resume', $project);

        return $this->projectService->getEditData($project);
    }

    /**
     * Get draft project data for AJAX request
     */
    public function getDraftData(Project $project): JsonResponse
    {
        if ($project->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'هذا المشروع ليس مسودة',
            ], 400);
        }

        $detailedRelations = $this->projectService->getProjectDetailedRelations();
        $project->load($detailedRelations);

        return response()->json([
            'success' => true,
            'project' => $project,
        ]);
    }

    /**
     * Show the project wizard for creating a new project
     */
    public function wizardCreate()
    {
        $authorities = Authority::where('is_active', true)->orderBy('agency_name')->get();
        $internalEntities = @json_decode(@file_get_contents(storage_path('app/data/entities.json')), true) ?: [];
        $financialItems = FinancialItem::where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('unit_name')->get();

        return view('projects.wizard', compact('authorities', 'internalEntities', 'financialItems', 'units'));
    }

    /**
     * Show the project wizard for editing an existing project
     */
    public function wizardEdit(Project $project)
    {
        $project->load($this->projectService->getProjectDetailedRelations());

        $authorities = Authority::where('is_active', true)->orderBy('agency_name')->get();
        $internalEntities = @json_decode(@file_get_contents(storage_path('app/data/entities.json')), true) ?: [];

        // Get selected IDs for each category
        $selectedExecuting = $project->implementingEntities->pluck('authority_id')->toArray();
        $selectedFunding = $project->financings->pluck('authority_id')->toArray();
        $selectedParticipating = $project->participatingEntities->pluck('authority_id')->toArray();
        $selectedSupervising = $project->supervisingAuthorities->pluck('authority_id')->toArray();

        return view('projects.wizard', compact(
            'project',
            'authorities',
            'internalEntities',
            'selectedExecuting',
            'selectedFunding',
            'selectedParticipating',
            'selectedSupervising'
        ));
    }

    public function updateEntity(Request $request, Project $project)
    {
        \Log::error("updateEntity method REACHED for project {$project->id}");

        try {
            $this->authorize('editEntity', $project);
            \Log::error('updateEntity authorize passed');
        } catch (\Exception $e) {
            \Log::error('updateEntity authorize failed: '.$e->getMessage().' - Class: '.get_class($e));
            throw $e;
        }

        if ($project->project_type !== 'old') {
            return redirect()->back()->with('error', 'هذه العملية متاحة للمشاريع القديمة فقط.');
        }

        if ($project->entity_modified) {
            return redirect()->back()->with('error', 'تم تعديل الجهة المقدمة لهذا المشروع مسبقاً ولا يمكن تعديلها مرة أخرى.');
        }

        $request->validate([
            'creator_entity_id' => 'required|integer|exists:internal_entities,id',
        ]);

        $entity = InternalEntity::find($request->creator_entity_id);

        $project->creator_entity_id = $entity->id;
        $project->internal_entity_id = $entity->id;
        $project->created_by_entity = $entity->name;
        $project->entity_modified = true;
        $project->save();

        // ─── إشعار: إبلاغ مستخدمي الجهة الجديدة بأن المشروع أُسند إليهم ───
        $this->notificationService->notifyOldProjectEntityChanged($project, $entity);

        return redirect()->back()->with('success', 'تم تعديل الجهة المقدمة للمشروع بنجاح. وقد تم إشعار الجهة المُسندة إليها.');
    }

    /**
     * Check if an exact project name already exists in database
     */
    public function checkName(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'exclude_id' => 'nullable|integer',
        ]);

        // Basic string cleaning: trim & normalize multiple spaces
        $cleanName = preg_replace('~\s+~u', ' ', trim($request->project_name));

        $query = Project::whereRaw('LOWER(TRIM(project_name)) = LOWER(?)', [$cleanName]);

        if ($request->filled('exclude_id')) {
            $query->where('id', '!=', $request->exclude_id);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'هذا المشروع موجود مسبقاً في قاعدة البيانات!' : null,
        ]);
    }

    /**
     * Normalize request data before validation
     */
    protected function normalizeRequestData(Request $request)
    {
        return $this->projectService->normalizeRequestData($request);
    }

    /**
     * Handle validation errors and return JSON response
     */
    protected function handleValidationError(Request $request, ValidationException $e)
    {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ في التحقق من البيانات',
            'errors' => $e->errors(),
        ], 422);
    }
}

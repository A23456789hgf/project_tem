<?php

namespace App\Http\Controllers\Planning;

use App\Exports\PlansExport;
use App\Http\Controllers\Controller;
use App\Imports\PlansImport;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\FundingSource;
use App\Models\InternalEntity;
use App\Models\Plan;
use App\Models\Priority;
use App\Services\DocumentLetterheadService;
use App\Services\ImportTrackingService;
use App\Services\NotificationService;
use App\Services\OrganizationResolver;
use App\Services\UserDataScopeService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PlanController extends Controller
{
    public function __construct(private OrganizationResolver $organizationResolver) {}

    /**
     * Display a listing of the plans.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Plan::class);

        $user = auth()->user();

        $query = Plan::with([
            'priority',
            'submittingEntity',
            'submittingAuthority',
            'creator',
        ]);

        /*
        |--------------------------------------------------------------------------
        | تطبيق النطاق الإداري والجغرافي للمستخدم
        |--------------------------------------------------------------------------
        | Admin يرى جميع الخطط.
        | بقية المستخدمين يرون فقط الخطط التابعة للجهات الداخلية أو الخارجية
        | الواقعة ضمن النطاق الإداري والجغرافي المسموح لهم به.
        */
        if (! $user->isAdmin()) {
            $internalEntityQuery = InternalEntity::withoutGlobalScopes();

            $this->organizationResolver->applyVisibleInternalEntities(
                $internalEntityQuery,
                $user
            );

            $entityIds = $internalEntityQuery
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $authorityQuery = Authority::withoutGlobalScopes();

            $this->organizationResolver->applyVisibleAuthorities(
                $authorityQuery,
                $user
            );

            $authorityIds = $authorityQuery
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $query->where(function ($q) use ($entityIds, $authorityIds) {
                $hasCondition = false;

                if (! empty($entityIds)) {
                    $q->where(function ($internal) use ($entityIds) {
                        $internal->where('submitting_type', 'internal')
                            ->whereIn('submitting_entity_id', $entityIds);
                    });

                    $hasCondition = true;
                }

                if (! empty($authorityIds)) {
                    $method = $hasCondition ? 'orWhere' : 'where';

                    $q->{$method}(function ($external) use ($authorityIds) {
                        $external->where('submitting_type', 'external')
                            ->whereIn('submitting_authority_id', $authorityIds);
                    });

                    $hasCondition = true;
                }

                if (! $hasCondition) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('plan_number', 'like', "%{$search}%")
                    ->orWhereHas('submittingEntity', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('submittingAuthority', function ($sq) use ($search) {
                        $sq->where('agency_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('priority', function ($sq) use ($search) {
                        $sq->where('priority', 'like', "%{$search}%");
                    });
            });
        }

        $plans = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('planning.plans.index', compact('plans'));
    }

    public function create()
    {

        $this->authorize('create', Plan::class);

        $priorities = Priority::where('is_enabled', true)->get();

        $organizations = $this->organizationResolver->optionsFor(auth()->user());

        $entities = InternalEntity::where('is_active', true)->get();

        $fundingSources = FundingSource::all();

        return view('planning.plans.create', compact('priorities', 'entities', 'organizations', 'fundingSources'));

    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(Request $request)
    {

        $this->authorize('create', Plan::class);

        Log::info('بدء عملية حفظ خطة جديدة', ['user_id' => Auth::id(), 'request_data' => $request->all()]);

        // ✅ تعديل: أخذ priority_id من أول مشروع إذا لم يكن موجوداً في الجذر

        $priorityId = $request->priority_id ?? ($request->projects[0]['priority_id'] ?? null);

        $request->validate([

            'projects' => 'required|array|min:1',

            'projects.*.name' => 'required|string|max:255',

            'projects.*.importance' => 'required|in:normal,important,very_important',

            'projects.*.status' => 'required|in:new,terminated',

            'projects.*.cost_type' => 'required|in:YER,USD,EUR',

            'projects.*.cost' => 'required|numeric|min:0',

            'projects.*.participating_entity_id' => 'required|exists:internal_entities,id',

            'projects.*.priority_id' => 'nullable|exists:priorities,id',

        ]);

        // ✅ التحقق من وجود priority_id

        if (! $priorityId) {

            return back()->withInput()->with('error', 'يجب تحديد الأولوية للخطة');

        }

        try {

            DB::beginTransaction();

            Log::info('بدء Transaction لحفظ الخطة');

            // ✅ تعديل: إضافة الحقول الجديدة (التواريخ)

            $organization = $this->organizationResolver->resolve($request->user());

            $plan = Plan::create([

                'priority_id' => $priorityId,

                'submitting_type' => $organization['type'],

                'submitting_entity_id' => $organization['type'] === 'internal' ? $organization['id'] : null,

                'submitting_authority_id' => $organization['type'] === 'external' ? $organization['id'] : null,

                'created_by' => Auth::id(),

                'start_date_g' => $request->start_date_g ?? null,

                'end_date_g' => $request->end_date_g ?? null,

                'start_date_h' => $request->start_date_h ?? null,

                'end_date_h' => $request->end_date_h ?? null,

            ]);

            Log::info('تم إنشاء السجل الأساسي للخطة', ['plan_id' => $plan->id]);

            AuditLog::create([

                'user_id' => Auth::id(),

                'action' => 'create',

                'model_type' => 'Plan',

                'model_id' => $plan->id,

                'new_values' => $plan->toArray(),

                'description' => 'تم إنشاء خطة سنوية جديدة برقم: '.($plan->plan_number ?? $plan->id).' بواسطة: '.(Auth::user()->name ?? Auth::user()->username ?? 'غير معروف'),

                'ip_address' => $request->ip(),

            ]);

            foreach ($request->projects as $index => $projectData) {

                Log::info("معالجة المشروع رقم {$index}", ['project_name' => $projectData['name'] ?? '']);

                $activities = [];

                if (! empty($projectData['activities_json'])) {

                    $jsonString = is_string($projectData['activities_json']) ? $projectData['activities_json'] : json_encode($projectData['activities_json']);

                    $activities = json_decode($jsonString, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {

                        throw new \Exception("بيانات الأنشطة للمشروع '".($projectData['name'] ?? '')."' غير صيغة JSON صحيحة.");
                    }

                    if (is_array($activities)) {

                        $totalWeight = array_reduce($activities, function ($sum, $item) {

                            return $sum + (float) ($item['weight'] ?? 0);

                        }, 0);

                        if (abs($totalWeight - 100) > 0.01) {

                            throw new \Exception("إجمالي أوزان الأنشطة للمشروع '".($projectData['name'] ?? '')."' يجب أن يكون 100% (المجموع الحالي: ".round($totalWeight, 2).'%)');
                        }

                    }

                }

                // ✅ تعديل: إضافة goals_json إذا كان موجوداً

                $project = $plan->projects()->create([

                    'name' => $projectData['name'],

                    'importance' => $projectData['importance'],

                    'status' => $projectData['status'],

                    'indicators' => $projectData['indicators'] ?? null,

                    'outputs' => $projectData['outputs'] ?? null,

                    'baseline' => $projectData['baseline'] ?? null,

                    'target_value' => $projectData['target_value'] ?? 0,

                    'cost_type' => $projectData['cost_type'],

                    'cost' => $projectData['cost'],

                    'funding_availability' => isset($projectData['funding_availability']) && $projectData['funding_availability'] == '1',

                    'funding_source_id' => $projectData['funding_source_id'] ?? null,

                    'participating_entity_id' => $projectData['participating_entity_id'],

                    'priority_id' => $projectData['priority_id'] ?? $priorityId,

                    'goals' => $projectData['goals_json'] ?? null,

                ]);

                Log::info('تم إنشاء المشروع', ['project_id' => $project->id]);

                if (is_array($activities)) {

                    foreach ($activities as $actIndex => $activityData) {

                        $actions = $activityData['actions'] ?? [];

                        if (! empty($actions) && ! is_array($actions)) {

                            $actions = json_decode($actions, true) ?? [];

                        }

                        if (! empty($actions)) {

                            $actionsWeight = array_reduce($actions, function ($sum, $item) {

                                return $sum + (float) ($item['weight'] ?? 0);

                            }, 0);

                            if (abs($actionsWeight - 100) > 0.01) {

                                throw new \Exception("إجمالي أوزان الإجراءات للنشاط '".($activityData['name'] ?? 'غير مسمى')."' في مشروع '".$project->name."' يجب أن يكون 100% (المجموع الحالي: ".round($actionsWeight, 2).'%)');
                            }

                        }

                        $activity = $project->activities()->create([

                            'name' => $activityData['name'] ?? 'بدون اسم',

                            'weight' => $activityData['weight'] ?? 0,

                        ]);

                        foreach ($actions as $actionData) {

                            $activity->actions()->create([

                                'name' => $actionData['name'] ?? 'بدون اسم',

                                'weight' => $actionData['weight'] ?? 0,

                                'start_date_g' => ! empty($actionData['start_date_g']) ? $actionData['start_date_g'] : null,

                                'start_date_h' => $actionData['start_date_h'] ?? null,

                                'end_date_g' => ! empty($actionData['end_date_g']) ? $actionData['end_date_g'] : null,

                                'end_date_h' => $actionData['end_date_h'] ?? null,

                                'duration' => $actionData['duration'] ?? 0,

                            ]);

                        }

                    }

                }

            }

            DB::commit();

            Log::info('✅ تم حفظ الخطة بنجاح', ['plan_id' => $plan->id]);

            try {

                app(NotificationService::class)->notifyPlan($plan, 'created', auth()->user());

            } catch (\Exception $e) {

                Log::error('Notification error in PlanController store: '.$e->getMessage());

            }

            return redirect()->route('plans.index')->with('success', 'تم حفظ الخطة بنجاح');

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('❌ فشل حفظ الخطة', [

                'message' => $e->getMessage(),

                'trace' => $e->getTraceAsString(),

                'line' => $e->getLine(),

                'file' => $e->getFile(),

                'request_data' => $request->all(),

            ]);

            return back()->withInput()->with('error', 'فشل حفظ الخطة: '.$e->getMessage());

        }

    }

    public function show(Plan $plan)
    {

        $this->authorize('view', $plan);

        $plan->load(['projects.priority', 'projects.participatingEntity', 'projects.fundingSource', 'projects.activities.actions', 'submittingEntity', 'priority', 'creator']);

        $qrCodeData = '';

        if (class_exists(QrCode::class)) {

            try {

                $qrCode = QrCode::create(route('plans.show', $plan->id));

                $writer = new PngWriter;

                $result = $writer->write($qrCode);

                $qrCodeData = $result->getDataUri();

            } catch (\Throwable $e) {

            }

        }

        return view('planning.plans.show', compact('plan', 'qrCodeData'));

    }

    public function print(Plan $plan)
    {

        $this->authorize('print', $plan);

        $plan->load(['projects.priority', 'projects.participatingEntity', 'projects.fundingSource', 'projects.activities.actions', 'submittingEntity', 'priority', 'creator']);

        $qrCodeData = '';

        if (class_exists(QrCode::class)) {

            try {

                $qrCode = QrCode::create(route('plans.show', $plan->id));

                $writer = new PngWriter;

                $result = $writer->write($qrCode);

                $qrCodeData = $result->getDataUri();

            } catch (\Throwable $e) {

            }

        }

        $printDate = now()->format('Y/m/d');

        $letterheadData = app(DocumentLetterheadService::class)->resolveForDocument($plan);
        $letterhead = $letterheadData->letterhead;

        return view('planning.plans.print', compact('plan', 'letterhead', 'letterheadData', 'qrCodeData', 'printDate'));

    }

    public function printImplementation(Plan $plan)
    {

        $this->authorize('printImplementation', $plan);

        $plan->load(['projects.priority', 'projects.participatingEntity', 'projects.fundingSource', 'projects.activities.actions', 'submittingEntity', 'priority', 'creator']);

        $qrCodeData = '';

        if (class_exists(QrCode::class)) {

            try {

                $qrCode = QrCode::create(route('plans.show', $plan->id));

                $writer = new PngWriter;

                $result = $writer->write($qrCode);

                $qrCodeData = $result->getDataUri();

            } catch (\Throwable $e) {

            }

        }

        $printDate = now()->format('Y/m/d');

        $letterheadData = app(DocumentLetterheadService::class)->resolveForDocument($plan);
        $letterhead = $letterheadData->letterhead;

        return view('planning.plans.print_implementation', compact('plan', 'letterhead', 'letterheadData', 'qrCodeData', 'printDate'));

    }

    public function edit(Plan $plan)
    {

        $this->authorize('update', $plan);

        $plan->load('projects.activities.actions');

        $priorities = Priority::where('is_enabled', true)->get();

        $entities = InternalEntity::where('is_active', true)->get();

        $fundingSources = FundingSource::all();

        return view('planning.plans.edit', compact('plan', 'priorities', 'entities', 'fundingSources'));

    }

    public function update(Request $request, Plan $plan)
    {

        $this->authorize('update', $plan);

        Log::info('بدء عملية تحديث خطة', ['plan_id' => $plan->id, 'user_id' => Auth::id()]);

        $priorityId = $request->priority_id ?? ($request->projects[0]['priority_id'] ?? null);

        $request->validate([

            'projects' => 'required|array|min:1',

            'projects.*.name' => 'required|string|max:255',

            'projects.*.importance' => 'required|in:normal,important,very_important',

            'projects.*.status' => 'required|in:new,terminated',

            'projects.*.cost_type' => 'required|in:YER,USD,EUR',

            'projects.*.cost' => 'required|numeric|min:0',

            'projects.*.participating_entity_id' => 'required|exists:internal_entities,id',

            'projects.*.priority_id' => 'nullable|exists:priorities,id',

        ]);

        if (! $priorityId) {

            return back()->withInput()->with('error', 'يجب تحديد الأولوية للخطة');

        }

        try {

            DB::beginTransaction();

            $oldValues = $plan->toArray();

            $plan->update([

                'priority_id' => $priorityId,

                'start_date_g' => $request->start_date_g ?? null,

                'end_date_g' => $request->end_date_g ?? null,

                'start_date_h' => $request->start_date_h ?? null,

                'end_date_h' => $request->end_date_h ?? null,

            ]);

            AuditLog::create([

                'user_id' => Auth::id(),

                'action' => 'update',

                'model_type' => 'Plan',

                'model_id' => $plan->id,

                'old_values' => $oldValues,

                'new_values' => $plan->toArray(),

                'description' => 'تم تحديث البيانات العامة للخطة رقم: '.($plan->plan_number ?? $plan->id),

                'ip_address' => $request->ip(),

            ]);

            foreach ($plan->projects as $oldProject) {

                foreach ($oldProject->activities as $oldActivity) {

                    $oldActivity->actions()->delete();

                }

                $oldProject->activities()->delete();

            }

            $plan->projects()->delete();

            Log::info('تم حذف المشاريع والأنشطة القديمة لإعادة إنشائها');

            foreach ($request->projects as $index => $projectData) {

                Log::info("معالجة المشروع المحدث رقم {$index}", ['project_name' => $projectData['name'] ?? '']);

                $activities = [];

                if (! empty($projectData['activities_json'])) {

                    $jsonString = is_string($projectData['activities_json']) ? $projectData['activities_json'] : json_encode($projectData['activities_json']);

                    $activities = json_decode($jsonString, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {

                        throw new \Exception("بيانات الأنشطة للمشروع '".($projectData['name'] ?? '')."' غير صيغة JSON صحيحة.");
                    }

                    if (is_array($activities)) {

                        $totalWeight = array_reduce($activities, function ($sum, $item) {

                            return $sum + (float) ($item['weight'] ?? 0);

                        }, 0);

                        if (abs($totalWeight - 100) > 0.01) {

                            throw new \Exception("إجمالي أوزان الأنشطة للمشروع '".($projectData['name'] ?? '')."' يجب أن يكون 100% (المجموع الحالي: ".round($totalWeight, 2).'%)');
                        }

                    }

                }

                $project = $plan->projects()->create([

                    'name' => $projectData['name'],

                    'importance' => $projectData['importance'],

                    'status' => $projectData['status'],

                    'indicators' => $projectData['indicators'] ?? null,

                    'outputs' => $projectData['outputs'] ?? null,

                    'baseline' => $projectData['baseline'] ?? null,

                    'target_value' => $projectData['target_value'] ?? 0,

                    'cost_type' => $projectData['cost_type'],

                    'cost' => $projectData['cost'],

                    'funding_availability' => isset($projectData['funding_availability']) && $projectData['funding_availability'] == '1',

                    'funding_source_id' => $projectData['funding_source_id'] ?? null,

                    'participating_entity_id' => $projectData['participating_entity_id'],

                    'priority_id' => $projectData['priority_id'] ?? $priorityId,

                    'goals' => $projectData['goals_json'] ?? null,

                ]);

                if (is_array($activities)) {

                    foreach ($activities as $actIndex => $activityData) {

                        $actions = $activityData['actions'] ?? [];

                        if (! empty($actions) && ! is_array($actions)) {

                            $actions = json_decode($actions, true) ?? [];

                        }

                        if (! empty($actions)) {

                            $actionsWeight = array_reduce($actions, function ($sum, $item) {

                                return $sum + (float) ($item['weight'] ?? 0);

                            }, 0);

                            if (abs($actionsWeight - 100) > 0.01) {

                                throw new \Exception("إجمالي أوزان الإجراءات للنشاط '".($activityData['name'] ?? 'غير مسمى')."' في مشروع '".$project->name."' يجب أن يكون 100% (المجموع الحالي: ".round($actionsWeight, 2).'%)');
                            }

                        }

                        $activity = $project->activities()->create([

                            'name' => $activityData['name'] ?? 'بدون اسم',

                            'weight' => $activityData['weight'] ?? 0,

                        ]);

                        foreach ($actions as $actionData) {

                            $activity->actions()->create([

                                'name' => $actionData['name'] ?? 'بدون اسم',

                                'weight' => $actionData['weight'] ?? 0,

                                'start_date_g' => ! empty($actionData['start_date_g']) ? $actionData['start_date_g'] : null,

                                'start_date_h' => $actionData['start_date_h'] ?? null,

                                'end_date_g' => ! empty($actionData['end_date_g']) ? $actionData['end_date_g'] : null,

                                'end_date_h' => $actionData['end_date_h'] ?? null,

                                'duration' => $actionData['duration'] ?? 0,

                            ]);

                        }

                    }

                }

            }

            DB::commit();

            Log::info('✅ تم تحديث الخطة بنجاح', ['plan_id' => $plan->id]);

            try {

                app(NotificationService::class)->notifyPlan($plan, 'updated', auth()->user());

            } catch (\Exception $e) {

                Log::error('Notification error in PlanController update: '.$e->getMessage());

            }

            return redirect()->route('plans.index')->with('success', 'تم تحديث الخطة بنجاح');

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('❌ فشل تحديث الخطة', [

                'message' => $e->getMessage(),

                'trace' => $e->getTraceAsString(),

                'line' => $e->getLine(),

                'file' => $e->getFile(),

            ]);

            return back()->withInput()->with('error', 'فشل تحديث الخطة: '.$e->getMessage());

        }

    }

    public function destroy(Plan $plan)
    {

        $this->authorize('delete', $plan);

        try {

            foreach ($plan->projects as $project) {

                foreach ($project->activities as $activity) {

                    $activity->actions()->delete();

                }

                $project->activities()->delete();

            }

            $plan->projects()->delete();

            $plan->delete();

            return redirect()->route('plans.index')->with('success', 'تم حذف الخطة بنجاح');

        } catch (\Exception $e) {

            Log::error('فشل حذف الخطة', ['message' => $e->getMessage()]);

            return back()->with('error', 'حدث خطأ أثناء حذف الخطة');

        }

    }

    public function implementation(Plan $plan)
    {

        $this->authorize('implementation', $plan);

        $plan->load(['projects.priority', 'projects.activities.actions', 'projects.participatingEntity', 'projects.fundingSource', 'submittingEntity', 'priority']);

        $fundingSources = FundingSource::all();

        $priorities = Priority::where('is_enabled', true)->get();

        $entities = InternalEntity::where('is_active', true)->get();

        return view('planning.plans.implementation_plan', compact('plan', 'fundingSources', 'priorities', 'entities'));

    }

    public function updateImplementation(Request $request, Plan $plan)
    {

        $this->authorize('implementation', $plan);

        Log::info('بدء تحديث الخطة التنفيذية', ['plan_id' => $plan->id]);

        $request->validate([

            'projects' => 'required|array|min:1',

            'projects.*.priority_id' => 'nullable|exists:priorities,id',

        ]);

        try {

            DB::beginTransaction();

            foreach ($request->projects as $projectId => $projectData) {

                $project = $plan->projects()->findOrFail($projectId);

                if (isset($projectData['priority_id'])) {

                    $project->update(['priority_id' => $projectData['priority_id']]);

                }

                $activitiesData = $projectData['activities'] ?? [];

                if (! empty($activitiesData) && ! is_array($activitiesData)) {

                    $activitiesData = json_decode($activitiesData, true) ?? [];

                }

                if (is_array($activitiesData)) {

                    $totalWeight = array_reduce($activitiesData, function ($sum, $item) {

                        return $sum + (float) ($item['weight'] ?? 0);

                    }, 0);

                    if (abs($totalWeight - 100) > 0.01) {

                        throw new \Exception("إجمالي أوزان الأنشطة للمشروع '".$project->name."' يجب أن يكون 100% (المجموع الحالي: ".round($totalWeight, 2).'%)');
                    }

                }

                foreach ($project->activities as $oldActivity) {

                    $oldActivity->actions()->delete();

                }

                $project->activities()->delete();

                foreach ($activitiesData as $actData) {

                    $activity = $project->activities()->create([

                        'name' => $actData['name'] ?? 'بدون اسم',

                        'weight' => $actData['weight'] ?? 0,

                    ]);

                    $actionsData = $actData['actions'] ?? [];

                    if (! empty($actionsData) && ! is_array($actionsData)) {

                        $actionsData = json_decode($actionsData, true) ?? [];

                    }

                    if (! empty($actionsData)) {

                        $actionsWeight = array_reduce($actionsData, function ($sum, $item) {

                            return $sum + (float) ($item['weight'] ?? 0);

                        }, 0);

                        if (abs($actionsWeight - 100) > 0.01) {

                            throw new \Exception("إجمالي أوزان الإجراءات للنشاط '".($actData['name'] ?? 'غير مسمى')."' في مشروع '".$project->name."' يجب أن يكون 100% (المجموع الحالي: ".round($actionsWeight, 2).'%)');
                        }

                    }

                    foreach ($actionsData as $actionData) {

                        $activity->actions()->create([

                            'name' => $actionData['name'] ?? 'بدون اسم',

                            'weight' => $actionData['weight'] ?? 0,

                            'start_date_g' => ! empty($actionData['start_date_g']) ? $actionData['start_date_g'] : null,

                            'start_date_h' => null,

                            'end_date_g' => ! empty($actionData['end_date_g']) ? $actionData['end_date_g'] : null,

                            'end_date_h' => null,

                            'duration' => $actionData['duration'] ?? 0,

                        ]);

                    }

                }

            }

            AuditLog::create([

                'user_id' => Auth::id(),

                'action' => 'update_implementation',

                'model_type' => 'Plan',

                'model_id' => $plan->id,

                'description' => 'تم تحديث أنشطة وإجراءات الخطة التنفيذية للخطه رقم: '.($plan->plan_number ?? $plan->id),

                'ip_address' => $request->ip(),

            ]);

            DB::commit();

            Log::info('✅ تم حفظ الخطة التنفيذية بنجاح', ['plan_id' => $plan->id]);

            try {

                app(NotificationService::class)->notifyPlan($plan, 'implementation_updated', auth()->user());

            } catch (\Exception $e) {

                Log::error('Notification error in PlanController updateImplementation: '.$e->getMessage());

            }

            return redirect()->route('plans.index')->with('success', 'تم حفظ الخطة التنفيذية بنجاح');

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('❌ فشل حفظ الخطة التنفيذية', [

                'message' => $e->getMessage(),

                'trace' => $e->getTraceAsString(),

            ]);

            return back()->withInput()->with('error', 'فشل حفظ الخطة التنفيذية: '.$e->getMessage());

        }

    }

    public function batchPrint(Request $request)
    {

        $this->authorize('batchPrint', Plan::class);

        $query = Plan::with([

            'projects.priority',

            'projects.participatingEntity',

            'projects.fundingSource',

            'projects.activities.actions',

            'submittingEntity',

            'priority',

            'creator',

        ]);

        $this->applyPlanGeoScope($query);

        if ($request->has('search') && ! empty($request->search)) {

            $search = $request->get('search');

            $query->where(function ($q) use ($search) {

                $q->where('plan_number', 'like', "%{$search}%")

                    ->orWhereHas('submittingEntity', function ($sq) use ($search) {

                        $sq->where('name', 'like', "%{$search}%");

                    })->orWhereHas('priority', function ($sq) use ($search) {

                        $sq->where('priority', 'like', "%{$search}%");

                    });

            });

        }

        $plans = $query->latest()->get();

        $printDate = now()->format('Y/m/d');

        return view('planning.plans.print_batch', compact('plans', 'printDate'));

    }

    public function comprehensiveBatchPrint(Request $request)
    {

        $this->authorize('comprehensiveBatchPrint', Plan::class);

        $query = Plan::with([

            'projects.priority',

            'projects.participatingEntity',

            'projects.fundingSource',

            'projects.activities.actions',

            'submittingEntity',

            'priority',

            'creator',

        ]);

        $this->applyPlanGeoScope($query);

        if ($request->has('search') && ! empty($request->search)) {

            $search = $request->get('search');

            $query->where(function ($q) use ($search) {

                $q->where('plan_number', 'like', "%{$search}%")

                    ->orWhereHas('submittingEntity', function ($sq) use ($search) {

                        $sq->where('name', 'like', "%{$search}%");

                    })->orWhereHas('priority', function ($sq) use ($search) {

                        $sq->where('priority', 'like', "%{$search}%");

                    });

            });

        }

        $plans = $query->latest()->get();

        $printDate = now()->format('Y/m/d');

        return view('planning.plans.print_comprehensive_batch', compact('plans', 'printDate'));

    }

    public function exportExcel(Request $request)
    {

        $this->authorize('export', Plan::class);

        $search = $request->get('search');

        $planId = $request->get('plan_id');

        $filename = 'المصفوفة_التشغيلية_'.now()->format('Y-m-d').'.xlsx';

        $geoFilter = $this->buildPlanGeoScopeFilter();

        return Excel::download(new PlansExport($search, $planId, $geoFilter), $filename);

    }

    public function showImport()
    {

        $this->authorize('import', Plan::class);

        return view('planning.plans.import');

    }

    public function processImport(Request $request)
    {

        $this->authorize('import', Plan::class);

        $request->validate([

            'file' => 'required|file|mimes:xlsx,xls,csv',

            'import_mode' => 'required|in:add,update',

        ]);

        try {

            $trackingService = new ImportTrackingService;

            $importLog = $trackingService->startImport('الخطط', Plan::class, $request->file('file')->getClientOriginalName());

            $import = new PlansImport($request->import_mode, $trackingService, $importLog);

            Excel::import($import, $request->file('file'));

            $msg = "تم الاستيراد بنجاح: {$import->importedPlans} خطة، {$import->importedProjects} مشروع، {$import->importedActivities} نشاط، {$import->importedActions} إجراء.";

            if (! empty($import->errors)) {

                $msg .= ' تحذيرات: '.implode(' | ', $import->errors);

            }

            $trackingService->finishImport($importLog, $import->importedPlans + count($import->errors), $import->importedPlans, count($import->errors), $import->errors);

            if (! empty($import->errors)) {

                return redirect()->route('plans.index')->with('warning', $msg);

            }

            AuditLog::create([

                'user_id' => Auth::id(),

                'action' => 'import',

                'model_type' => 'Plan',

                'description' => $msg,

                'ip_address' => $request->ip(),

            ]);

            return redirect()->route('plans.index')->with('success', $msg);

        } catch (\Exception $e) {

            Log::error('فشل الاستيراد', ['message' => $e->getMessage()]);

            return back()->with('error', 'فشل الاستيراد: '.$e->getMessage());

        }

    }

    private function applyPlanGeoScope(Builder $query): void
    {
        app(UserDataScopeService::class)->applyScope($query);
    }

    private function buildPlanGeoScopeFilter(): ?\Closure
    {

        $user = Auth::user();

        if (! $user) {

            return null;

        }

        if ($user->isAdmin()) {

            return null;

        }

        $adminScopes = is_array($user->role?->module_scopes) ? $user->role->module_scopes : [];

        $geoScope = $user->getModuleGeoScope('planning');

        $adminScope = $adminScopes['planning'] ?? null;

        if ($geoScope === null && $adminScope === null) {

            return null;

        }

        if ($geoScope === 'none') {

            return fn (Builder $q) => $q->whereRaw('1 = 0');

        }

        if ($geoScope === 'all' || ($geoScope === null && $adminScope === 'all')) {

            return null;

        }

        $userGovId = $user->governorate_id ?? $user->getAssignedGovernorateId();

        $userDirId = $user->directorate_id ?? $user->getAssignedDirectorateId();

        if ($geoScope === 'same_governorate') {

            if ($userGovId) {

                return fn (Builder $q) => $q->whereHas('submittingEntity', function ($sq) use ($userGovId) {

                    $sq->where('governorate_id', $userGovId);

                });

            }

            return $this->buildPlanAdminScopeFilter($adminScope, $user);

        }

        if ($geoScope === 'same_directorate') {

            if ($userDirId) {

                return fn (Builder $q) => $q->whereHas('submittingEntity', function ($sq) use ($userDirId) {

                    $sq->where('directorate_id', $userDirId);

                });

            }

            return $this->buildPlanAdminScopeFilter($adminScope, $user);

        }

        if ($geoScope === 'custom') {

            return $this->buildPlanAdminScopeFilter($adminScope, $user);

        }

        return $this->buildPlanAdminScopeFilter($adminScope ?? 'none', $user);

    }

    private function buildPlanAdminScopeFilter(?string $adminScope, $user): ?\Closure
    {

        if (! $adminScope || $adminScope === 'all') {

            return null;

        }

        if ($adminScope === 'none') {

            return fn (Builder $q) => $q->whereRaw('1 = 0');

        }

        if ($adminScope === 'own' || $adminScope === 'user') {

            $entityId = $user->entity_id;

            if (! $entityId) {

                return fn (Builder $q) => $q->whereRaw('1 = 0');

            }

            return fn (Builder $q) => $q->where('submitting_entity_id', $entityId);

        }

        if (in_array($adminScope, ['dept_in_gen_dir', 'gen_dir_in_sector'])) {

            $entityId = $user->entity_id;

            if (! $entityId) {

                return fn (Builder $q) => $q->whereRaw('1 = 0');

            }

            $entity = InternalEntity::find($entityId);

            if (! $entity) {

                return fn (Builder $q) => $q->whereRaw('1 = 0');

            }

            $parentId = $adminScope === 'dept_in_gen_dir' ? $entity->parent_id : ($entity->parent?->parent_id ?? $entity->parent_id);

            if (! $parentId) {

                return fn (Builder $q) => $q->whereRaw('1 = 0');

            }

            $siblingIds = InternalEntity::where('parent_id', $parentId)->pluck('id')->toArray();

            if (empty($siblingIds)) {

                return fn (Builder $q) => $q->whereRaw('1 = 0');

            }

            return fn (Builder $q) => $q->whereIn('submitting_entity_id', $siblingIds);

        }

        return null;

    }
}

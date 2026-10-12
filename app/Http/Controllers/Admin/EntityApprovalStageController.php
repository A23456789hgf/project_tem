<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\AuthorityApprovalRoute;
use App\Models\AuthorityApprovalStage;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\ProjectApproval;
use App\Models\User;
use App\Services\ApprovalChainBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EntityApprovalStageController extends Controller
{
    /**
     * الإجراءات (الحالات) المسموح بها في المراحل.
     */
    public const ALLOWED_ACTIONS = [
        'submit',
        'approve',
        'return',
        'reject',
        'comment',
        'upload_attachment',
        'need_action',
        'consult',
        'forward',
    ];

    /**
     * الإجراءات الافتراضية عند عدم تحديد أي إجراء.
     */
    public const DEFAULT_ACTIONS = ['submit', 'approve', 'return', 'reject'];

    /**
     * أنواع مسارات العمل المدعومة في النظام.
     */
    public const WORKFLOW_TYPES = [
        'project_approval' => 'اعتماد وتدقيق المشاريع (Project Approval)',
        'clearance' => 'طلبات إخلاء العهد والتسوية (Clearance)',
        'payment' => 'طلبات صرف الدفعات (Payment)',
        'clearance_payment' => 'طلبات الصرف والإخلاء المشتركة (Clearance & Payment)',
        'clearance_closure' => 'طلبات الإخلاء والإغلاق (Clearance & Closure)',
        'project_closure' => 'طلبات إغلاق المشاريع (Project Closure)',
    ];

    /**
     * الحالة الفعلية للمعاملات النشطة في جدول project_approvals.
     */
    public const PENDING_STATUS = 'pending';

    /**
     * القيم الافتراضية لقواعد كل إجراء.
     */
    public const DEFAULT_ACTION_RULES = [
        'notes_required' => false,
        'attachment_required' => false,
        'min_notes_length' => 0,
    ];

    /**
     * التسميات العربية للإجراءات (للاستخدام في الواجهة).
     */
    public const ACTION_LABELS = [
        'submit' => 'إرسال (Submit)',
        'approve' => 'اعتماد (Approve)',
        'return' => 'إعادة (Return)',
        'reject' => 'رفض (Reject)',
        'comment' => 'تعليق (Comment)',
        'upload_attachment' => 'رفع مرفق (Upload)',
        'need_action' => 'بحاجة لإجراء (Need Action)',
        'consult' => 'استشارة (Consult)',
        'forward' => 'تحويل (Forward)',
    ];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin');
    }

    /**
     * الصفحة الرئيسية: عرض بطاقة لكل جهة تحتوي على جميع مراحلها.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'internal');
        $workflowType = $request->get('workflow_type', 'project_approval');
        $workflowTypes = self::WORKFLOW_TYPES;
        $actionLabels = self::ACTION_LABELS;

        if ($tab === 'internal') {
            $entityIds = EntityApprovalStage::query()
                ->where('workflow_type', $workflowType)
                ->distinct()
                ->pluck('entity_id')
                ->filter()
                ->unique()
                ->values();

            $entitiesQuery = InternalEntity::query()
                ->whereIn('id', $entityIds);

            if ($request->filled('entity_id')) {
                $entitiesQuery->where('id', $request->entity_id);
            }

            if ($request->filled('search')) {
                $entitiesQuery->where('name', 'like', '%'.$request->search.'%');
            }

            $entities = $entitiesQuery->orderBy('name')->get();

            $stagesByEntity = EntityApprovalStage::query()
                ->where('workflow_type', $workflowType)
                ->whereIn('entity_id', $entities->pluck('id'))
                ->with(['responsibleUser', 'createdBy', 'updatedBy'])
                ->orderBy('stage_order')
                ->get()
                ->groupBy('entity_id');

            $stats = [
                'total_entities' => $entities->count(),
                'total_stages' => $stagesByEntity->flatten()->count(),
                'entities_with_final' => $stagesByEntity->filter(function ($stages) {
                    return $stages->contains(fn ($s) => ! empty($s->is_final));
                })->count(),
                'entities_without_final' => $stagesByEntity->filter(function ($stages) {
                    return ! $stages->contains(fn ($s) => ! empty($s->is_final));
                })->count(),
            ];

            $stages = $this->buildFlatPaginator($stagesByEntity);

            return view($this->resolveView('index'), compact(
                'entities', 'stagesByEntity', 'stages',
                'tab', 'workflowType', 'workflowTypes', 'actionLabels', 'stats'
            ));
        } else {
            $authorityIds = AuthorityApprovalStage::query()
                ->where('workflow_type', $workflowType)
                ->distinct()
                ->pluck('authority_id')
                ->filter()
                ->unique()
                ->values();

            $authoritiesQuery = Authority::query()
                ->whereIn('id', $authorityIds);

            if ($request->filled('authority_id')) {
                $authoritiesQuery->where('id', $request->authority_id);
            }

            if ($request->filled('search')) {
                $authoritiesQuery->where(function ($q) use ($request) {
                    $q->where('agency_name', 'like', '%'.$request->search.'%')
                        ->orWhere('name', 'like', '%'.$request->search.'%');
                });
            }

            $entities = $authoritiesQuery->orderBy('agency_name')->get();

            $stagesByEntity = AuthorityApprovalStage::query()
                ->where('workflow_type', $workflowType)
                ->whereIn('authority_id', $entities->pluck('id'))
                ->with(['responsibleUser', 'creator', 'updater'])
                ->orderBy('stage_order')
                ->get()
                ->groupBy('authority_id');

            $routes = AuthorityApprovalRoute::with(['authority', 'destinationAuthority'])
                ->whereIn('authority_id', $entities->pluck('id'))
                ->get()
                ->keyBy('authority_id');

            $stats = [
                'total_entities' => $entities->count(),
                'total_stages' => $stagesByEntity->flatten()->count(),
                'entities_with_final' => $stagesByEntity->filter(function ($stages) {
                    return $stages->contains(fn ($s) => ! empty($s->is_final));
                })->count(),
                'entities_without_final' => $stagesByEntity->filter(function ($stages) {
                    return ! $stages->contains(fn ($s) => ! empty($s->is_final));
                })->count(),
            ];

            $stages = $this->buildFlatPaginator($stagesByEntity);

            return view($this->resolveView('index'), compact(
                'entities', 'stagesByEntity', 'stages', 'routes',
                'tab', 'workflowType', 'workflowTypes', 'actionLabels', 'stats'
            ));
        }
    }

    /**
     * بناء paginator مسطّح من المراحل المجمّعة (للتوافق الخلفي).
     */
    private function buildFlatPaginator($stagesByEntity): LengthAwarePaginator
    {
        $allStages = $stagesByEntity->flatten()->sortBy('stage_order')->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;

        $paginatedItems = $allStages->forPage($currentPage, $perPage)->values();

        return new LengthAwarePaginator(
            $paginatedItems,
            $allStages->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    public function create(Request $request)
    {
        $tab = $request->get('tab', 'internal');
        $workflowType = $request->get('workflow_type', 'project_approval');
        $workflowTypes = self::WORKFLOW_TYPES;
        $allowedActions = self::ALLOWED_ACTIONS;
        $defaultActionRules = self::DEFAULT_ACTION_RULES;
        $actionLabels = self::ACTION_LABELS;

        if ($tab === 'internal') {
            $entities = InternalEntity::orderBy('name')->get();

            return view($this->resolveView('create'), compact(
                'entities', 'tab', 'workflowType', 'workflowTypes',
                'allowedActions', 'defaultActionRules', 'actionLabels'
            ));
        } else {
            $authorities = Authority::orderBy('agency_name')->get();

            return view($this->resolveView('create'), compact(
                'authorities', 'tab', 'workflowType', 'workflowTypes',
                'allowedActions', 'defaultActionRules', 'actionLabels'
            ));
        }
    }

    public function store(Request $request)
    {
        $tab = $request->get('tab', 'internal');
        $workflowType = $request->input('workflow_type', 'project_approval');

        $baseRules = [
            'workflow_type' => ['required', 'string', Rule::in(array_keys(self::WORKFLOW_TYPES))],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.stage' => ['required', 'string', 'distinct'],
            'stages.*.stage_name_ar' => ['required', 'string'],
            'stages.*.stage_name_en' => ['nullable', 'string'],
            'stages.*.available_actions' => ['nullable', 'array'],
            'stages.*.available_actions.*' => ['string', Rule::in(self::ALLOWED_ACTIONS)],
            'stages.*.responsible_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'stages.*.required_attachments' => ['nullable', 'array'],
            'stages.*.action_rules' => ['nullable', 'array'],
            'stages.*.action_rules.*.notes_required' => ['nullable', 'boolean'],
            'stages.*.action_rules.*.attachment_required' => ['nullable', 'boolean'],
            'stages.*.action_rules.*.min_notes_length' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'stages.*.is_final' => ['nullable', 'boolean'],
            'stages.*.triggers_execution' => ['nullable', 'boolean'],
        ];

        if ($tab === 'internal') {
            $request->validate(array_merge($baseRules, [
                'entity_id' => ['required', 'integer', Rule::exists('internal_entities', 'id')],
            ]), $this->validationMessages());

            $entityId = $request->entity_id;
            $selectedStages = $request->stages;

            $this->ensureSingleFinalStage($selectedStages);

            DB::transaction(function () use ($entityId, $workflowType, $selectedStages) {
                EntityApprovalStage::where('entity_id', $entityId)
                    ->where('workflow_type', $workflowType)
                    ->update(['is_final' => false, 'triggers_execution' => false]);

                $order = 1;
                foreach ($selectedStages as $stageData) {
                    EntityApprovalStage::updateOrCreate(
                        ['entity_id' => $entityId, 'workflow_type' => $workflowType, 'stage' => $stageData['stage']],
                        [
                            'stage_name_ar' => $stageData['stage_name_ar'],
                            'stage_name_en' => $stageData['stage_name_en'] ?? $stageData['stage_name_ar'],
                            'available_actions' => $this->encodeActions($stageData['available_actions'] ?? null),
                            'action_rules' => $this->encodeActionRules($stageData['action_rules'] ?? [], $stageData['available_actions'] ?? []),
                            'required_attachments' => $this->encodeAttachments($stageData['required_attachments'] ?? []),
                            'stage_order' => $order++,
                            'responsible_user_id' => $stageData['responsible_user_id'] ?? null,
                            'is_active' => true,
                            'is_final' => ! empty($stageData['is_final']),
                            'triggers_execution' => ! empty($stageData['triggers_execution']),
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]
                    );
                }
            });

            return redirect()->route('admin.entity-stages.index', ['tab' => 'internal', 'workflow_type' => $workflowType])
                ->with('success', 'تم حفظ وتكوين مسار الاعتماد للجهة الداخلية بنجاح.');
        } else {
            $request->validate(array_merge($baseRules, [
                'authority_id' => ['required', 'integer', Rule::exists('authorities', 'id')],
                'route_destination_type' => ['nullable', 'string', Rule::in(['ministry', 'authority'])],
                'destination_authority_id' => [
                    'nullable', 'integer', Rule::exists('authorities', 'id'),
                    Rule::requiredIf(fn () => $request->input('route_destination_type') === 'authority'),
                ],
            ]), $this->validationMessages());

            $authorityId = $request->authority_id;
            $selectedStages = $request->stages;

            $this->ensureSingleFinalStage($selectedStages);

            try {
                DB::transaction(function () use ($authorityId, $workflowType, $selectedStages, $request) {
                    AuthorityApprovalStage::where('authority_id', $authorityId)
                        ->where('workflow_type', $workflowType)
                        ->update(['is_final' => false, 'triggers_execution' => false]);

                    $order = 1;
                    foreach ($selectedStages as $stageData) {
                        AuthorityApprovalStage::updateOrCreate(
                            ['authority_id' => $authorityId, 'workflow_type' => $workflowType, 'stage' => $stageData['stage']],
                            [
                                'stage_name_ar' => $stageData['stage_name_ar'],
                                'stage_name_en' => $stageData['stage_name_en'] ?? $stageData['stage_name_ar'],
                                'available_actions' => $this->encodeActions($stageData['available_actions'] ?? null),
                                'action_rules' => $this->encodeActionRules($stageData['action_rules'] ?? [], $stageData['available_actions'] ?? []),
                                'required_attachments' => $this->encodeAttachments($stageData['required_attachments'] ?? []),
                                'stage_order' => $order++,
                                'responsible_user_id' => $stageData['responsible_user_id'] ?? null,
                                'is_active' => true,
                                'is_final' => ! empty($stageData['is_final']),
                                'triggers_execution' => ! empty($stageData['triggers_execution']),
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                            ]
                        );
                    }

                    if ($request->filled('route_destination_type')) {
                        AuthorityApprovalRoute::updateOrCreate(
                            ['authority_id' => $authorityId],
                            [
                                'destination_type' => $request->route_destination_type,
                                'destination_authority_id' => $request->route_destination_type === 'authority'
                                    ? $request->destination_authority_id : null,
                                'is_active' => true,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                            ]
                        );

                        $builder = app(ApprovalChainBuilder::class);
                        $validation = $builder->validateAuthorityRoute($authorityId);
                        if (! $validation['is_valid']) {
                            throw new \Exception($validation['error']);
                        }
                    }
                });
            } catch (\Exception $e) {
                return back()->with('error', 'فشل في حفظ المسار: '.$e->getMessage())->withInput();
            }

            return redirect()->route('admin.entity-stages.index', ['tab' => 'external', 'workflow_type' => $workflowType])
                ->with('success', 'تم حفظ وتكوين مسار الاعتماد للجهة الخارجية بنجاح.');
        }
    }

    public function edit($id, Request $request)
    {
        $tab = $request->get('tab', 'internal');
        $workflowType = $request->get('workflow_type', 'project_approval');
        $workflowTypes = self::WORKFLOW_TYPES;
        $allowedActions = self::ALLOWED_ACTIONS;
        $defaultActionRules = self::DEFAULT_ACTION_RULES;
        $actionLabels = self::ACTION_LABELS;

        if ($tab === 'internal') {
            $entity = InternalEntity::findOrFail($id);
            $stages = EntityApprovalStage::where('entity_id', $id)
                ->where('workflow_type', $workflowType)
                ->orderBy('stage_order')
                ->get();
            $entities = InternalEntity::orderBy('name')->get();

            return view($this->resolveView('edit'), compact(
                'entity', 'stages', 'entities', 'tab', 'workflowType',
                'workflowTypes', 'allowedActions', 'defaultActionRules', 'actionLabels'
            ));
        } else {
            $authority = Authority::findOrFail($id);
            $stages = AuthorityApprovalStage::where('authority_id', $id)
                ->where('workflow_type', $workflowType)
                ->orderBy('stage_order')
                ->get();
            $route = AuthorityApprovalRoute::where('authority_id', $id)->first();
            $authorities = Authority::where('id', '!=', $id)->orderBy('agency_name')->get();

            return view($this->resolveView('edit'), compact(
                'authority', 'stages', 'route', 'authorities', 'tab', 'workflowType',
                'workflowTypes', 'allowedActions', 'defaultActionRules', 'actionLabels'
            ));
        }
    }

    public function update(Request $request, $id)
    {
        $tab = $request->get('tab', 'internal');
        $workflowType = $request->input('workflow_type', 'project_approval');

        $rules = [
            'workflow_type' => ['nullable', 'string', Rule::in(array_keys(self::WORKFLOW_TYPES))],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.stage' => ['required', 'string', 'distinct'],
            'stages.*.stage_name_ar' => ['required', 'string'],
            'stages.*.stage_name_en' => ['nullable', 'string'],
            'stages.*.available_actions' => ['nullable', 'array'],
            'stages.*.available_actions.*' => ['string', Rule::in(self::ALLOWED_ACTIONS)],
            'stages.*.responsible_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'stages.*.required_attachments' => ['nullable', 'array'],
            'stages.*.action_rules' => ['nullable', 'array'],
            'stages.*.action_rules.*.notes_required' => ['nullable', 'boolean'],
            'stages.*.action_rules.*.attachment_required' => ['nullable', 'boolean'],
            'stages.*.action_rules.*.min_notes_length' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'stages.*.is_final' => ['nullable', 'boolean'],
            'stages.*.triggers_execution' => ['nullable', 'boolean'],
        ];

        if ($tab === 'external') {
            $rules['route_destination_type'] = ['nullable', 'string', Rule::in(['ministry', 'authority'])];
            $rules['destination_authority_id'] = [
                'nullable', 'integer', Rule::exists('authorities', 'id'),
                Rule::requiredIf(fn () => $request->input('route_destination_type') === 'authority'),
            ];
        }

        $request->validate($rules, $this->validationMessages());
        $stages = $request->stages;
        $errors = [];

        $this->ensureSingleFinalStage($stages);

        if ($tab === 'internal') {
            $entity = InternalEntity::findOrFail($id);
            try {
                DB::transaction(function () use ($entity, $workflowType, $stages, &$errors) {
                    EntityApprovalStage::where('entity_id', $entity->id)
                        ->where('workflow_type', $workflowType)
                        ->update(['is_final' => false, 'triggers_execution' => false]);

                    $existingStages = EntityApprovalStage::where('entity_id', $entity->id)
                        ->where('workflow_type', $workflowType)
                        ->get()
                        ->keyBy('stage');
                    $newStageKeys = collect($stages)->pluck('stage')->filter()->toArray();

                    foreach ($existingStages as $stageKey => $existing) {
                        if (! in_array($stageKey, $newStageKeys)) {
                            $hasPending = ProjectApproval::where('entity_id', $entity->id)
                                ->where('stage_key', $stageKey)
                                ->where('status', self::PENDING_STATUS)
                                ->exists();

                            if ($hasPending) {
                                $errors[] = "لا يمكن حذف مرحلة [{$existing->stage_name_ar}] لأن هناك معاملات نشطة تعتمد عليها.";
                            } else {
                                $existing->delete();
                            }
                        }
                    }

                    $order = 1;
                    foreach ($stages as $stageData) {
                        EntityApprovalStage::updateOrCreate(
                            ['entity_id' => $entity->id, 'workflow_type' => $workflowType, 'stage' => $stageData['stage']],
                            [
                                'stage_name_ar' => $stageData['stage_name_ar'],
                                'stage_name_en' => $stageData['stage_name_en'] ?? $stageData['stage_name_ar'],
                                'available_actions' => $this->encodeActions($stageData['available_actions'] ?? null),
                                'action_rules' => $this->encodeActionRules($stageData['action_rules'] ?? [], $stageData['available_actions'] ?? []),
                                'required_attachments' => $this->encodeAttachments($stageData['required_attachments'] ?? []),
                                'stage_order' => $order++,
                                'responsible_user_id' => $stageData['responsible_user_id'] ?? null,
                                'is_active' => true,
                                'is_final' => ! empty($stageData['is_final']),
                                'triggers_execution' => ! empty($stageData['triggers_execution']),
                                'updated_by' => Auth::id(),
                            ]
                        );
                    }
                });
            } catch (\Exception $e) {
                return back()->with('error', 'فشل في حفظ البيانات: '.$e->getMessage())->withInput();
            }
        } else {
            $authority = Authority::findOrFail($id);
            try {
                DB::transaction(function () use ($authority, $workflowType, $stages, $request, &$errors) {
                    AuthorityApprovalStage::where('authority_id', $authority->id)
                        ->where('workflow_type', $workflowType)
                        ->update(['is_final' => false, 'triggers_execution' => false]);

                    $existingStages = AuthorityApprovalStage::where('authority_id', $authority->id)
                        ->where('workflow_type', $workflowType)
                        ->get()
                        ->keyBy('stage');
                    $newStageKeys = collect($stages)->pluck('stage')->filter()->toArray();

                    foreach ($existingStages as $stageKey => $existing) {
                        if (! in_array($stageKey, $newStageKeys)) {
                            $hasPending = ProjectApproval::where('authority_id', $authority->id)
                                ->where('stage_key', $stageKey)
                                ->where('status', self::PENDING_STATUS)
                                ->exists();

                            if ($hasPending) {
                                $errors[] = "لا يمكن حذف مرحلة [{$existing->stage_name_ar}] لأن هناك معاملات نشطة تعتمد عليها.";
                            } else {
                                $existing->delete();
                            }
                        }
                    }

                    $order = 1;
                    foreach ($stages as $stageData) {
                        AuthorityApprovalStage::updateOrCreate(
                            ['authority_id' => $authority->id, 'workflow_type' => $workflowType, 'stage' => $stageData['stage']],
                            [
                                'stage_name_ar' => $stageData['stage_name_ar'],
                                'stage_name_en' => $stageData['stage_name_en'] ?? $stageData['stage_name_ar'],
                                'available_actions' => $this->encodeActions($stageData['available_actions'] ?? null),
                                'action_rules' => $this->encodeActionRules($stageData['action_rules'] ?? [], $stageData['available_actions'] ?? []),
                                'required_attachments' => $this->encodeAttachments($stageData['required_attachments'] ?? []),
                                'stage_order' => $order++,
                                'responsible_user_id' => $stageData['responsible_user_id'] ?? null,
                                'is_active' => true,
                                'is_final' => ! empty($stageData['is_final']),
                                'triggers_execution' => ! empty($stageData['triggers_execution']),
                                'updated_by' => Auth::id(),
                            ]
                        );
                    }

                    if ($request->filled('route_destination_type')) {
                        AuthorityApprovalRoute::updateOrCreate(
                            ['authority_id' => $authority->id],
                            [
                                'destination_type' => $request->route_destination_type,
                                'destination_authority_id' => $request->route_destination_type === 'authority'
                                    ? $request->destination_authority_id : null,
                                'is_active' => true,
                                'updated_by' => Auth::id(),
                            ]
                        );

                        $builder = app(ApprovalChainBuilder::class);
                        $validation = $builder->validateAuthorityRoute($authority->id);
                        if (! $validation['is_valid']) {
                            throw new \Exception($validation['error']);
                        }
                    }
                });
            } catch (\Exception $e) {
                return back()->with('error', 'فشل في حفظ المسار: '.$e->getMessage())->withInput();
            }
        }

        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput()
                ->with('warning', 'تم حفظ بعض المراحل مع وجود أخطاء في حذف مراحل مرتبطة بمعاملات.');
        }

        return redirect()->route('admin.entity-stages.index', ['tab' => $tab, 'workflow_type' => $workflowType])
            ->with('success', 'تم تحديث الإعدادات بنجاح.');
    }

    public function toggleActive($id, Request $request)
    {
        $tab = $request->get('tab', 'internal');

        if ($tab === 'internal') {
            $stage = EntityApprovalStage::findOrFail($id);
            if ($stage->is_active) {
                $hasPending = ProjectApproval::where('entity_id', $stage->entity_id)
                    ->where('stage_key', $stage->stage)
                    ->where('status', self::PENDING_STATUS)
                    ->exists();

                if ($hasPending) {
                    return back()->with('error', 'لا يمكن إيقاف هذه المرحلة لوجود معاملات نشطة تعتمد عليها.');
                }
            }
            $stage->update(['is_active' => ! $stage->is_active]);
        } else {
            $stage = AuthorityApprovalStage::findOrFail($id);
            if ($stage->is_active) {
                $hasPending = ProjectApproval::where('authority_id', $stage->authority_id)
                    ->where('stage_key', $stage->stage)
                    ->where('status', self::PENDING_STATUS)
                    ->exists();

                if ($hasPending) {
                    return back()->with('error', 'لا يمكن إيقاف هذه المرحلة لوجود معاملات نشطة تعتمد عليها.');
                }
            }
            $stage->update(['is_active' => ! $stage->is_active]);
        }

        return back()->with('success', 'تم تغيير حالة المرحلة بنجاح.');
    }

    public function destroy($id, Request $request)
    {
        $tab = $request->get('tab', 'internal');

        if ($tab === 'internal') {
            $stage = EntityApprovalStage::findOrFail($id);
            $hasPending = ProjectApproval::where('entity_id', $stage->entity_id)
                ->where('stage_key', $stage->stage)
                ->where('status', self::PENDING_STATUS)
                ->exists();

            if ($hasPending) {
                return back()->with('error', 'لا يمكن حذف هذه المرحلة لوجود معاملات نشطة تعتمد عليها.');
            }
            $stage->delete();
        } else {
            $stage = AuthorityApprovalStage::findOrFail($id);
            $hasPending = ProjectApproval::where('authority_id', $stage->authority_id)
                ->where('stage_key', $stage->stage)
                ->where('status', self::PENDING_STATUS)
                ->exists();

            if ($hasPending) {
                return back()->with('error', 'لا يمكن حذف هذه المرحلة لوجود معاملات نشطة تعتمد عليها.');
            }
            $stage->delete();
        }

        return back()->with('success', 'تم حذف المرحلة بنجاح.');
    }

    /**
     * حذف كل مراحل جهة معينة (المسار بالكامل).
     */
    public function destroyEntityPath($id, Request $request)
    {
        $tab = $request->get('tab', 'internal');
        $workflowType = $request->input('workflow_type', 'project_approval');

        if ($tab === 'internal') {
            $entity = InternalEntity::findOrFail($id);
            $stages = EntityApprovalStage::where('entity_id', $entity->id)
                ->where('workflow_type', $workflowType)
                ->get();

            foreach ($stages as $stage) {
                $hasPending = ProjectApproval::where('entity_id', $entity->id)
                    ->where('stage_key', $stage->stage)
                    ->where('status', self::PENDING_STATUS)
                    ->exists();

                if ($hasPending) {
                    return back()->with('error', "لا يمكن حذف المسار: المرحلة [{$stage->stage_name_ar}] مرتبطة بمعاملات نشطة.");
                }
            }

            EntityApprovalStage::where('entity_id', $entity->id)
                ->where('workflow_type', $workflowType)
                ->delete();

            return back()->with('success', "تم حذف مسار الجهة [{$entity->name}] بالكامل.");
        } else {
            $authority = Authority::findOrFail($id);
            $stages = AuthorityApprovalStage::where('authority_id', $authority->id)
                ->where('workflow_type', $workflowType)
                ->get();

            foreach ($stages as $stage) {
                $hasPending = ProjectApproval::where('authority_id', $authority->id)
                    ->where('stage_key', $stage->stage)
                    ->where('status', self::PENDING_STATUS)
                    ->exists();

                if ($hasPending) {
                    return back()->with('error', "لا يمكن حذف المسار: المرحلة [{$stage->stage_name_ar}] مرتبطة بمعاملات نشطة.");
                }
            }

            AuthorityApprovalStage::where('authority_id', $authority->id)
                ->where('workflow_type', $workflowType)
                ->delete();

            AuthorityApprovalRoute::where('authority_id', $authority->id)->delete();

            $name = $authority->agency_name ?? $authority->name;

            return back()->with('success', "تم حذف مسار الجهة [{$name}] بالكامل.");
        }
    }

    public function eligibleUsers(Request $request)
    {
        $tab = $request->get('tab', 'internal');

        $request->validate([
            'entity_id' => [$tab === 'internal' ? 'required' : 'nullable', 'integer'],
            'authority_id' => [$tab === 'external' ? 'required' : 'nullable', 'integer'],
        ]);

        if ($tab === 'internal') {
            $users = User::withoutGlobalScopes()
                ->where('entity_id', $request->entity_id)
                ->where('status', 'Active')
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $users = User::withoutGlobalScopes()
                ->where('authority_id', $request->authority_id)
                ->where('status', 'Active')
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return response()->json($users);
    }

    public function availableActions(): JsonResponse
    {
        return response()->json([
            'allowed_actions' => self::ALLOWED_ACTIONS,
            'default_actions' => self::DEFAULT_ACTIONS,
            'default_action_rules' => self::DEFAULT_ACTION_RULES,
            'action_labels' => self::ACTION_LABELS,
        ]);
    }

    /**
     * ✅ تحديد ملف العرض بشكل ذكي.
     * الأولوية:
     *   1. admin.entity-stages (الجديد)
     *   2. approvals.configurations (القديم — توافق خلفي)
     */
    private function resolveView(string $name): string
    {
        if (view()->exists("admin.entity-stages.{$name}")) {
            return "admin.entity-stages.{$name}";
        }

        if (view()->exists("approvals.configurations.{$name}")) {
            return "approvals.configurations.{$name}";
        }

        return "admin.entity-stages.{$name}";
    }

    private function encodeAttachments(array $attachments): array
    {
        $clean = [];
        foreach ($attachments as $att) {
            if (! empty($att['key']) && ! empty($att['label'])) {
                $clean[] = [
                    'key' => trim($att['key']),
                    'label' => trim($att['label']),
                    'required' => ! empty($att['required']),
                    'allowed_types' => $att['allowed_types'] ?? ['pdf', 'png', 'jpg'],
                ];
            }
        }

        return $clean;
    }

    private function encodeActions(?array $actions): array
    {
        return ! empty($actions) ? array_values($actions) : self::DEFAULT_ACTIONS;
    }

    private function encodeActionRules(array $rules, array $allowedActions): array
    {
        $allowedActions = ! empty($allowedActions)
            ? array_values(array_intersect($allowedActions, self::ALLOWED_ACTIONS))
            : self::DEFAULT_ACTIONS;

        $final = [];
        foreach ($allowedActions as $action) {
            $actionRules = is_array($rules[$action] ?? null) ? $rules[$action] : [];

            $final[$action] = [
                'notes_required' => (bool) ($actionRules['notes_required'] ?? self::DEFAULT_ACTION_RULES['notes_required']),
                'attachment_required' => (bool) ($actionRules['attachment_required'] ?? self::DEFAULT_ACTION_RULES['attachment_required']),
                'min_notes_length' => (int) ($actionRules['min_notes_length'] ?? self::DEFAULT_ACTION_RULES['min_notes_length']),
            ];
        }

        return $final;
    }

    private function ensureSingleFinalStage(array $stages): void
    {
        $finalCount = 0;
        foreach ($stages as $stage) {
            if (! empty($stage['is_final'])) {
                $finalCount++;
            }
        }

        if ($finalCount === 0) {
            throw ValidationException::withMessages([
                'stages' => 'يجب تحديد مرحلة نهائية واحدة على الأقل (is_final) في سلسلة الاعتماد.',
            ]);
        }

        if ($finalCount > 1) {
            throw ValidationException::withMessages([
                'stages' => 'لا يمكن تحديد أكثر من مرحلة نهائية واحدة في نفس سلسلة الاعتماد.',
            ]);
        }
    }

    private function validationMessages(): array
    {
        return [
            'stages.*.stage.distinct' => 'لا يمكن تكرار نفس المرحلة في الطلب.',
            'stages.*.available_actions.*.in' => 'إجراء غير مسموح به في المرحلة.',
            'stages.*.action_rules.*.min_notes_length.integer' => 'الحد الأدنى لطول الملاحظات يجب أن يكون رقماً.',
            'stages.*.action_rules.*.min_notes_length.min' => 'الحد الأدنى لطول الملاحظات لا يمكن أن يكون سالباً.',
            'destination_authority_id.required_if' => 'يجب تحديد الجهة المستقبِلة عند اختيار التوجيه إلى جهة.',
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntityResponsibilityType;
use App\Enums\UserResponsibilityType;
use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\AuthorityApprovalRoute;
use App\Models\AuthorityApprovalStage;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\ProjectApproval;
use App\Models\User;
use App\Services\ApprovalChainBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EntityApprovalStageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin');
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'internal');

        if ($tab === 'internal') {
            $query = EntityApprovalStage::with(['entity', 'responsibleUser']);
            if ($request->filled('entity_id')) {
                $query->where('entity_id', $request->entity_id);
            }
            $stages = $query->orderBy('entity_id')->orderBy('stage_order')->paginate(20)
                ->appends(['tab' => 'internal', 'entity_id' => $request->entity_id]);
            $entities = InternalEntity::orderBy('name')->get();

            return view('admin.entity-stages.index', compact('stages', 'entities', 'tab'));
        } else {
            $query = AuthorityApprovalStage::with(['authority', 'responsibleUser']);
            if ($request->filled('authority_id')) {
                $query->where('authority_id', $request->authority_id);
            }
            $stages = $query->orderBy('authority_id')->orderBy('stage_order')->paginate(20)
                ->appends(['tab' => 'external', 'authority_id' => $request->authority_id]);

            $authorities = Authority::orderBy('agency_name')->get();
            $routes = AuthorityApprovalRoute::with(['authority', 'destinationAuthority'])->get();

            return view('admin.entity-stages.index', compact('stages', 'authorities', 'routes', 'tab'));
        }
    }

    public function create(Request $request)
    {
        $tab = $request->get('tab', 'internal');
        $stageTypes = EntityResponsibilityType::orderedCases();

        if ($tab === 'internal') {
            $entities = InternalEntity::orderBy('name')->get();

            return view('admin.entity-stages.create', compact('entities', 'stageTypes', 'tab'));
        } else {
            $authorities = Authority::orderBy('agency_name')->get();

            return view('admin.entity-stages.create', compact('authorities', 'stageTypes', 'tab'));
        }
    }

    public function store(Request $request)
    {
        $tab = $request->get('tab', 'internal');

        if ($tab === 'internal') {
            $request->validate([
                'entity_id' => ['required', 'integer', Rule::exists('internal_entities', 'id')],
                'stages' => ['required', 'array', 'min:1'],
                'stages.*.stage' => ['required', 'string'],
                'stages.*.stage_name_ar' => ['required', 'string'],
                'stages.*.stage_name_en' => ['nullable', 'string'],
                'stages.*.available_actions' => ['nullable', 'array'],
            ]);

            $entityId = $request->entity_id;
            $selectedStages = $request->stages;

            DB::transaction(function () use ($entityId, $selectedStages) {
                $order = 1;
                foreach ($selectedStages as $stageData) {
                    EntityApprovalStage::updateOrCreate(
                        [
                            'entity_id' => $entityId,
                            'stage' => $stageData['stage'],
                        ],
                        [
                            'stage_name_ar' => $stageData['stage_name_ar'],
                            'stage_name_en' => $stageData['stage_name_en'] ?? $stageData['stage_name_ar'],
                            'available_actions' => isset($stageData['available_actions']) ? json_encode($stageData['available_actions']) : json_encode(['approve', 'return']),
                            'stage_order' => $order++,
                            'created_by' => Auth::id(),
                        ]
                    );
                }
            });

            return redirect()->route('admin.entity-stages.index', ['tab' => 'internal'])->with('success', 'تم إضافة المراحل للجهة الداخلية بنجاح.');
        } else {
            $request->validate([
                'authority_id' => ['required', 'integer', Rule::exists('authorities', 'id')],
                'stages' => ['required', 'array', 'min:1'],
                'stages.*.stage' => ['required', 'string'],
                'stages.*.stage_name_ar' => ['required', 'string'],
                'stages.*.stage_name_en' => ['nullable', 'string'],
                'stages.*.available_actions' => ['nullable', 'array'],
                'route_destination_type' => ['nullable', 'string', Rule::in(['ministry', 'authority'])],
                'destination_authority_id' => ['nullable', 'integer', Rule::exists('authorities', 'id')],
            ]);

            $authorityId = $request->authority_id;
            $selectedStages = $request->stages;

            try {
                DB::transaction(function () use ($authorityId, $selectedStages, $request) {
                    $order = 1;
                    foreach ($selectedStages as $stageData) {
                        AuthorityApprovalStage::updateOrCreate(
                            [
                                'authority_id' => $authorityId,
                                'stage' => $stageData['stage'],
                            ],
                            [
                                'stage_name_ar' => $stageData['stage_name_ar'],
                                'stage_name_en' => $stageData['stage_name_en'] ?? $stageData['stage_name_ar'],
                                'available_actions' => isset($stageData['available_actions']) ? json_encode($stageData['available_actions']) : json_encode(['approve', 'return']),
                                'stage_order' => $order++,
                                'created_by' => Auth::id(),
                            ]
                        );
                    }

                    // Save route if provided
                    if ($request->filled('route_destination_type')) {
                        AuthorityApprovalRoute::updateOrCreate(
                            ['authority_id' => $authorityId],
                            [
                                'destination_type' => $request->route_destination_type,
                                'destination_authority_id' => $request->route_destination_type === 'authority' ? $request->destination_authority_id : null,
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

            return redirect()->route('admin.entity-stages.index', ['tab' => 'external'])->with('success', 'تم إضافة المراحل للجهة الخارجية بنجاح.');
        }
    }

    public function edit($id, Request $request)
    {
        $tab = $request->get('tab', 'internal');
        $stageTypes = EntityResponsibilityType::orderedCases();

        if ($tab === 'internal') {
            $entity = InternalEntity::findOrFail($id);
            $entity->load('approvalStages');

            return view('admin.entity-stages.edit', compact('entity', 'stageTypes', 'tab'));
        } else {
            $authority = Authority::findOrFail($id);
            $stages = AuthorityApprovalStage::where('authority_id', $id)->get();
            $route = AuthorityApprovalRoute::where('authority_id', $id)->first();
            $authorities = Authority::where('id', '!=', $id)->orderBy('name')->get();

            return view('admin.entity-stages.edit', compact('authority', 'stages', 'route', 'authorities', 'stageTypes', 'tab'));
        }
    }

    public function update(Request $request, $id)
    {
        $tab = $request->get('tab', 'internal');
        $stageTypes = EntityResponsibilityType::orderedCases();

        $rules = [];
        foreach ($stageTypes as $stageType) {
            $key = $stageType->value;
            $rules["stages.{$key}.enabled"] = ['nullable', 'boolean'];
            $rules["stages.{$key}.responsible_user_id"] = [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ];
        }

        if ($tab === 'external') {
            $rules['route_destination_type'] = ['nullable', 'string', Rule::in(['ministry', 'authority'])];
            $rules['destination_authority_id'] = ['nullable', 'integer', Rule::exists('authorities', 'id')];
        }

        $validated = $request->validate($rules);
        $stages = $validated['stages'] ?? [];
        $errors = [];

        if ($tab === 'internal') {
            $entity = InternalEntity::findOrFail($id);
            try {
                DB::transaction(function () use ($entity, $stageTypes, $stages, &$errors) {
                    foreach ($stageTypes as $stageType) {
                        $key = $stageType->value;
                        $isEnabled = ! empty($stages[$key]['enabled']);
                        $userId = $stages[$key]['responsible_user_id'] ?? null;

                        if (! $isEnabled) {
                            $hasPending = ProjectApproval::where('entity_id', $entity->id)
                                ->where('phase', $stageType->phaseCode())
                                ->where('status', 'Pending')
                                ->exists();

                            if ($hasPending) {
                                $errors[$key] = "لا يمكن حذف مرحلة [{$stageType->label()}] لأن هناك معاملات نشطة تعتمد عليها.";

                                continue;
                            }

                            EntityApprovalStage::where('entity_id', $entity->id)
                                ->where('stage', $key)
                                ->update(['is_active' => false]);

                            continue;
                        }

                        if ($userId) {
                            $user = User::withoutGlobalScopes()->find($userId);
                            if (! $user || $user->status !== 'Active' || (int) $user->entity_id !== (int) $entity->id) {
                                $errors[$key] = "المستخدم المحدد لمرحلة [{$stageType->label()}] غير صالح أو لا ينتمي للجهة.";

                                continue;
                            }

                            $requiredResponsibility = UserResponsibilityType::forStage($stageType);
                            if ($user->responsibility !== $requiredResponsibility) {
                                $errors[$key] = 'المستخدم المحدد مسؤوليته لا تتوافق مع نوع المرحلة.';

                                continue;
                            }
                        }

                        EntityApprovalStage::updateOrCreate(
                            ['entity_id' => $entity->id, 'stage' => $key],
                            [
                                'stage_order' => $stageType->stageOrder(),
                                'is_active' => true,
                                'responsible_user_id' => $userId ?: null,
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
                DB::transaction(function () use ($authority, $stageTypes, $stages, $request, &$errors) {
                    foreach ($stageTypes as $stageType) {
                        $key = $stageType->value;
                        $isEnabled = ! empty($stages[$key]['enabled']);
                        $userId = $stages[$key]['responsible_user_id'] ?? null;

                        if (! $isEnabled) {
                            $hasPending = ProjectApproval::where('authority_id', $authority->id)
                                ->where('phase', $stageType->phaseCode())
                                ->where('status', 'Pending')
                                ->exists();

                            if ($hasPending) {
                                $errors[$key] = "لا يمكن حذف مرحلة [{$stageType->label()}] لأن هناك معاملات نشطة تعتمد عليها.";

                                continue;
                            }

                            AuthorityApprovalStage::where('authority_id', $authority->id)
                                ->where('stage', $key)
                                ->update(['is_active' => false]);

                            continue;
                        }

                        if ($userId) {
                            $user = User::withoutGlobalScopes()->find($userId);
                            if (! $user || $user->status !== 'Active' || (int) $user->authority_id !== (int) $authority->id) {
                                $errors[$key] = "المستخدم المحدد لمرحلة [{$stageType->label()}] غير صالح أو لا ينتمي للجهة الخارجية.";

                                continue;
                            }
                        }

                        AuthorityApprovalStage::updateOrCreate(
                            ['authority_id' => $authority->id, 'stage' => $key],
                            [
                                'stage_order' => $stageType->stageOrder(),
                                'is_active' => true,
                                'responsible_user_id' => $userId ?: null,
                                'updated_by' => Auth::id(),
                            ]
                        );
                    }

                    // Route update
                    if ($request->filled('route_destination_type')) {
                        AuthorityApprovalRoute::updateOrCreate(
                            ['authority_id' => $authority->id],
                            [
                                'destination_type' => $request->route_destination_type,
                                'destination_authority_id' => $request->route_destination_type === 'authority' ? $request->destination_authority_id : null,
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
            return back()->withErrors($errors)->withInput()->with('warning', 'تم حفظ بعض المراحل مع وجود أخطاء.');
        }

        return redirect()->route('admin.entity-stages.index', ['tab' => $tab])->with('success', 'تم تحديث الإعدادات بنجاح.');
    }

    public function toggleActive($id, Request $request)
    {
        $tab = $request->get('tab', 'internal');

        if ($tab === 'internal') {
            $stage = EntityApprovalStage::findOrFail($id);
            if ($stage->is_active) {
                $stageType = EntityResponsibilityType::from($stage->stage);
                $hasPending = ProjectApproval::where('entity_id', $stage->entity_id)
                    ->where('phase', $stageType->phaseCode())
                    ->where('status', 'Pending')
                    ->exists();

                if ($hasPending) {
                    return back()->with('error', 'لا يمكن إيقاف هذه المرحلة لوجود معاملات نشطة تعتمد عليها.');
                }
            }
            $stage->update(['is_active' => ! $stage->is_active]);
        } else {
            $stage = AuthorityApprovalStage::findOrFail($id);
            if ($stage->is_active) {
                $stageType = EntityResponsibilityType::from($stage->stage);
                $hasPending = ProjectApproval::where('authority_id', $stage->authority_id)
                    ->where('phase', $stageType->phaseCode())
                    ->where('status', 'Pending')
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
            $stageType = EntityResponsibilityType::from($stage->stage);
            $hasPending = ProjectApproval::where('entity_id', $stage->entity_id)
                ->where('phase', $stageType->phaseCode())
                ->where('status', 'Pending')
                ->exists();

            if ($hasPending) {
                return back()->with('error', 'لا يمكن حذف هذه المرحلة لوجود معاملات نشطة تعتمد عليها.');
            }
            $stage->delete();
        } else {
            $stage = AuthorityApprovalStage::findOrFail($id);
            $stageType = EntityResponsibilityType::from($stage->stage);
            $hasPending = ProjectApproval::where('authority_id', $stage->authority_id)
                ->where('phase', $stageType->phaseCode())
                ->where('status', 'Pending')
                ->exists();

            if ($hasPending) {
                return back()->with('error', 'لا يمكن حذف هذه المرحلة لوجود معاملات نشطة تعتمد عليها.');
            }
            $stage->delete();
        }

        return back()->with('success', 'تم حذف المرحلة بنجاح.');
    }

    public function eligibleUsers(Request $request)
    {
        $tab = $request->get('tab', 'internal');

        $request->validate([
            'entity_id' => [$tab === 'internal' ? 'required' : 'nullable', 'integer'],
            'authority_id' => [$tab === 'external' ? 'required' : 'nullable', 'integer'],
            'stage' => ['required', 'string', Rule::in(array_column(EntityResponsibilityType::cases(), 'value'))],
        ]);

        $stageType = EntityResponsibilityType::from($request->stage);

        if ($tab === 'internal') {
            $requiredResp = UserResponsibilityType::forStage($stageType);
            $users = User::withoutGlobalScopes()
                ->where('entity_id', $request->entity_id)
                ->where('status', 'Active')
                ->where('responsibility', $requiredResp->value)
                ->orderBy('name')
                ->get(['id', 'name', 'user_id']);
        } else {
            $users = User::withoutGlobalScopes()
                ->where('authority_id', $request->authority_id)
                ->where('status', 'Active')
                ->orderBy('name')
                ->get(['id', 'name', 'user_id']);
        }

        return response()->json($users);
    }
}

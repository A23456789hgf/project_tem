<?php

namespace App\Http\Controllers;

use App\Enums\EntityResponsibilityType;
use App\Enums\UserResponsibilityType;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\AuthorityApprovalStage;
use App\Models\Directorate;
use App\Models\EntityApprovalStage;
use App\Models\Governorate;
use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGeographicScope;
use App\Services\ErpNextUserService;
use App\Services\NotificationService;
use App\Services\UserDataScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin')->except('index', 'show');
    }

    public function index(Request $request)
    {
        $query = User::withInactive();
        app(UserDataScopeService::class)->applyScope($query, auth()->user());

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->filled('organization_type')) {
            $query->where('organization_type', $request->organization_type);
        }

        if ($request->filled('authority_id')) {
            $query->where('authority_id', $request->authority_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('user_id', 'like', "%$search%")
                ->orWhere('name', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%");
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortColumns = [
            'id',
            'user_id',
            'name',
            'phone',
            'status',
            'created_at',
            'role_id',
            'entity_id',
        ];

        if (! in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        if ($sortBy === 'role_id') {
            $query->join('roles', 'users.role_id', '=', 'roles.id')
                ->select('users.*')
                ->orderBy('roles.name', $sortOrder);
        } elseif ($sortBy === 'entity_id') {
            $query->join('internal_entities', 'users.entity_id', '=', 'internal_entities.id')
                ->select('users.*')
                ->orderBy('internal_entities.name', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $users = $query->with(['entity.authority.governorate', 'entity.authority.directorate', 'role', 'geographicScopes', 'creatorEntity', 'createdBy.entity'])->paginate(15);
        $roles = Role::orderBy('name')->pluck('name', 'id');
        $statuses = ['Active', 'Disabled'];
        $entities = InternalEntity::active()->visibleToUser()->orderBy('name')->get();
        $authorities = Authority::active()->orderBy('agency_name')->get();

        $sortOptions = [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ];

        return view('user.index', compact('users', 'roles', 'statuses', 'entities', 'authorities', 'sortOptions'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name', 'id');
        $statuses = ['Active', 'Disabled'];
        $entities = InternalEntity::active()
            ->with([
                'governorate',
                'directorate',
                'authority' => function ($q) {
                    $q->withoutGlobalScopes()->with(['governorate', 'directorate']);
                },
            ])
            ->visibleToUser()->orderBy('name')->get();

        $governorates = Governorate::orderBy('name')->get();
        $directorates = Directorate::orderBy('name')->get();

        $geographicScopes = [];

        $authorities = Authority::active()->orderBy('agency_name')->get();

        return view('user.create', compact('roles', 'statuses', 'entities', 'governorates', 'directorates', 'geographicScopes', 'authorities'));
    }

    public function store(Request $request)
    {
        // إكمال القيم الافتراضية لكلمة المرور وحالة الحساب عند عدم إرسالها من النموذج
        if (! $request->filled('password')) {
            $request->merge(['password' => '123456']);
        }

        // المستخدم الجديد يكون فعالاً افتراضياً إذا لم يتم إرسال الحالة من النموذج
        $request->merge([
            'status' => $request->filled('status') ? $request->status : 'Active',
        ]);

        // ضبط الجهة حسب نوع المستخدم
        if ($request->organization_type === 'internal') {
            $request->merge([
                'authority_id' => null,
            ]);
        } elseif ($request->organization_type === 'external') {
            $request->merge([
                'entity_id' => null,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
                Rule::requiredIf((bool) $request->boolean('is_financial_user')),
            ],
            'is_financial_user' => 'nullable',
            'password' => 'required|string|min:1|max:50',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:Active,Disabled',
            'phone' => 'nullable|string|max:20',
            'organization_type' => 'required|in:internal,external',

            'entity_id' => [
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->organization_type === 'internal') {
                        $role = ! empty($request->role_id) ? Role::find($request->role_id) : null;
                        $isFullAccess = $role && ($role->full_access || in_array(strtolower($role->name), ['admin', 'مدير النظام']));

                        if (empty($value) && ! $isFullAccess) {
                            $fail('الجهة الداخلية مطلوبة للمستخدم الداخلي ذو النطاق المحدد.');
                        }

                        if (! empty($value) && ! InternalEntity::where('id', $value)->exists()) {
                            $fail('الجهة الداخلية المحددة غير صالحة.');
                        }
                    }
                },
            ],

            'authority_id' => [
                'nullable',
                'required_if:organization_type,external',
                'exists:authorities,id',
            ],

            'administrative_scope_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (! empty($value)) {
                        if ($request->organization_type === 'external') {
                            if (! Authority::where('id', $value)->exists()) {
                                $fail('الجهة الخارجية المحددة للنطاق الإداري غير صالحة.');
                            }
                        } else {
                            if (! InternalEntity::where('id', $value)->exists()) {
                                $fail('الجهة الداخلية المحددة للنطاق الإداري غير صالحة.');
                            }
                        }
                    }
                },
            ],

            'work' => 'nullable|string|max:255',

            'responsibility' => [
                'nullable',
                Rule::in(array_column(
                    UserResponsibilityType::cases(),
                    'value'
                )),
            ],

            'geographic_scopes' => 'nullable|array',

            'geographic_scopes.*.governorate_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (
                        $value !== 'all'
                        && ! empty($value)
                        && ! Governorate::where('id', $value)->exists()
                    ) {
                        $fail('المحافظة المحددة غير صالحة.');
                    }
                },
            ],

            'geographic_scopes.*.directorate_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (
                        $value !== 'all'
                        && ! empty($value)
                        && ! Directorate::where('id', $value)->exists()
                    ) {
                        $fail('المديرية المحددة غير صالحة.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $userId = User::generateNextUserId();

            $user = User::create([
                'username' => $userId,
                'user_id' => $userId,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,

                // ستكون Active افتراضياً
                'status' => $request->status,

                'phone' => $request->phone,

                // الداخلي يستخدم entity_id
                'entity_id' => $request->organization_type === 'internal'
                    ? $request->entity_id
                    : null,

                'organization_type' => $request->organization_type,

                // الخارجي يستخدم authority_id
                'authority_id' => $request->organization_type === 'external'
                    ? $request->authority_id
                    : null,

                'administrative_scope_id' => $request->administrative_scope_id,
                'work' => $request->work,
                'responsibility' => $request->responsibility ?: null,

                // إجبار المستخدم على تغيير كلمة المرور بعد أول دخول
                'must_change_password' => true,

                'is_financial_user' => $request->boolean('is_financial_user'),

                'created_by' => auth()->id(),
            ]);

            // حفظ النطاقات الجغرافية
            $this->syncGeographicScopes(
                $user,
                $request->geographic_scopes ?? []
            );

            // تحديث مسؤولية المرحلة تلقائياً
            $this->syncUserStageResponsibility($user);

            // تسجيل عملية إنشاء المستخدم
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'model_type' => 'User',
                'model_id' => $user->id,
                'new_values' => $user->toArray(),
                'description' => 'تم إنشاء مستخدم جديد: '.$user->name,
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            // ✅ مزامنة مع ERPNext للمستخدمين الماليين مع تمرير كلمة المرور الصريحة
            $erpSyncWarning = null;
            if ($user->is_financial_user) {
                try {
                    $syncResult = app(ErpNextUserService::class)
                        ->createFinancialUser($user, $request->password);
                    if (! ($syncResult['success'] ?? false)) {
                        $erpSyncWarning = 'تم إنشاء المستخدم محلياً بنجاح، لكن فشلت المزامنة مع ERPNext: '.($syncResult['error'] ?? 'خطأ في الاتصال').'.';
                    }
                } catch (\Throwable $e) {
                    \Log::error('ERPNext sync error in UserController store: '.$e->getMessage(), [
                        'user_id' => $user->id,
                    ]);
                    $erpSyncWarning = 'تم إنشاء المستخدم محلياً بنجاح، ولكن تعذر الاتصال بـ ERPNext.';
                }
            }

            // إرسال إشعار إنشاء الحساب
            try {
                app(NotificationService::class)
                    ->notifyUserAccount(
                        $user,
                        'created',
                        auth()->user()
                    );
            } catch (\Exception $e) {
                \Log::error(
                    'Notification error in UserController store: '.
                    $e->getMessage()
                );
            }

            $redirect = redirect()
                ->route('users.index')
                ->with(
                    'success',
                    'تم إنشاء المستخدم بنجاح والحساب مفعل ويمكنه تسجيل الدخول.'
                );

            if ($erpSyncWarning) {
                $redirect->with('warning', $erpSyncWarning);
            }

            return $redirect;

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('User creation error: '.$e->getMessage(), [
                'organization_type' => $request->organization_type,
                'entity_id' => $request->entity_id,
                'authority_id' => $request->authority_id,
                'phone' => $request->phone,
            ]);

            return back()
                ->with(
                    'error',
                    'حدث خطأ أثناء إنشاء المستخدم: '.$e->getMessage()
                )
                ->withInput();
        }
    }

    public function show(User $user)
    {
        app(UserDataScopeService::class)->authorizeRecord($user, auth()->user());
        $user->load(['geographicScopes.governorate', 'geographicScopes.directorate']);

        return view('user.show', compact('user'));
    }

    public function edit(User $user)
    {
        if ($user->username === 'root') {
            return back()->with('error', 'لا يمكن تعديل المستخدم الافتراضي root.');
        }

        $roles = Role::orderBy('name')->pluck('name', 'id');
        $statuses = ['Active', 'Disabled'];
        $entities = InternalEntity::active()
            ->with([
                'governorate',
                'directorate',
                'authority' => function ($q) {
                    $q->withoutGlobalScopes()->with(['governorate', 'directorate']);
                },
            ])
            ->visibleToUser()->orderBy('name')->get();

        $user->load('geographicScopes');
        $governorates = Governorate::orderBy('name')->get();
        $directorates = Directorate::orderBy('name')->get();

        $geographicScopes = $user->geographicScopes->map(function ($scope) {
            return [
                'id' => $scope->id,
                'governorate_id' => $scope->governorate_id !== null ? $scope->governorate_id : '',
                'directorate_id' => $scope->directorate_id !== null ? $scope->directorate_id : '',
            ];
        })->filter(function ($scope) {
            return ! empty($scope['governorate_id']) || ! empty($scope['directorate_id']);
        })->values()->toArray();

        $authorities = Authority::active()->orderBy('agency_name')->get();

        return view('user.edit', compact('user', 'roles', 'statuses', 'entities', 'governorates', 'directorates', 'geographicScopes', 'authorities'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->username === 'root') {
            return back()->with(
                'error',
                'لا يمكن تعديل المستخدم الافتراضي root.'
            );
        }

        /*
         * إذا لم يرسل النموذج status نحافظ على حالة المستخدم الحالية.
         * هذا يمنع إعادة تفعيل مستخدم Disabled بالخطأ عند تعديل بياناته.
         */
        $request->merge([
            'status' => $request->filled('status')
                ? $request->status
                : $user->status,
        ]);

        // ضبط الجهة حسب نوع المستخدم
        if ($request->organization_type === 'internal') {
            $request->merge([
                'authority_id' => null,
            ]);
        } elseif ($request->organization_type === 'external') {
            $request->merge([
                'entity_id' => null,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::requiredIf((bool) $request->boolean('is_financial_user')),
            ],
            'is_financial_user' => 'nullable',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:Active,Disabled',
            'phone' => 'nullable|string|max:20',
            'organization_type' => 'required|in:internal,external',

            'entity_id' => [
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->organization_type === 'internal') {
                        $role = ! empty($request->role_id) ? Role::find($request->role_id) : null;
                        $isFullAccess = $role && ($role->full_access || in_array(strtolower($role->name), ['admin', 'مدير النظام']));

                        if (empty($value) && ! $isFullAccess) {
                            $fail('الجهة الداخلية مطلوبة للمستخدم الداخلي ذو النطاق المحدد.');
                        }

                        if (! empty($value) && ! InternalEntity::where('id', $value)->exists()) {
                            $fail('الجهة الداخلية المحددة غير صالحة.');
                        }
                    }
                },
            ],

            'authority_id' => [
                'nullable',
                'required_if:organization_type,external',
                'exists:authorities,id',
            ],

            'administrative_scope_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (! empty($value)) {
                        if ($request->organization_type === 'external') {
                            if (! Authority::where('id', $value)->exists()) {
                                $fail(
                                    'الجهة الخارجية المحددة للنطاق الإداري غير صالحة.'
                                );
                            }
                        } else {
                            if (! InternalEntity::where('id', $value)->exists()) {
                                $fail(
                                    'الجهة الداخلية المحددة للنطاق الإداري غير صالحة.'
                                );
                            }
                        }
                    }
                },
            ],

            'work' => 'nullable|string|max:255',

            'responsibility' => [
                'nullable',
                Rule::in(array_column(
                    UserResponsibilityType::cases(),
                    'value'
                )),
            ],

            'geographic_scopes' => 'nullable|array',

            'geographic_scopes.*.governorate_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (
                        $value !== 'all'
                        && $value !== null
                        && $value !== ''
                        && ! Governorate::where('id', $value)->exists()
                    ) {
                        $fail('المحافظة المحددة غير صالحة.');
                    }
                },
            ],

            'geographic_scopes.*.directorate_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (
                        $value !== 'all'
                        && $value !== null
                        && $value !== ''
                        && ! Directorate::where('id', $value)->exists()
                    ) {
                        $fail('المديرية المحددة غير صالحة.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        /*
         * منع تعطيل المستخدم أو تغيير الجهة/المسؤولية
         * إذا كان مسؤولاً فعلياً عن مرحلة اعتماد مفعلة.
         */
        $isDeactivating = (
            $user->status === 'Active'
            && $request->status === 'Disabled'
        );

        /*
         * دعم تغيير الجهة للمستخدم الداخلي والخارجي.
         */
        $isChangingOrganization = false;

        if ($request->organization_type !== $user->organization_type) {
            $isChangingOrganization = true;
        } elseif ($request->organization_type === 'internal') {
            $isChangingOrganization =
                (int) $request->entity_id !== (int) $user->entity_id;
        } elseif ($request->organization_type === 'external') {
            $isChangingOrganization =
                (int) $request->authority_id !== (int) $user->authority_id;
        }

        $currentResponsibility = $user->responsibility instanceof UserResponsibilityType
            ? $user->responsibility->value
            : $user->responsibility;

        $isChangingResp = (
            $request->responsibility !== $currentResponsibility
            && $user->responsibility !== null
        );

        if (
            $isDeactivating
            || $isChangingOrganization
            || $isChangingResp
        ) {
            /*
             * هذا الجزء موجود أصلاً للمستخدم الداخلي.
             * نحافظ عليه كما هو لمنع كسر إعدادات مراحل الاعتماد.
             */
            $user->loadMissing('responsibleApprovalStages.entity');

            if ($user->responsibleApprovalStages->isNotEmpty()) {
                $stageNames = $user->responsibleApprovalStages
                    ->map(function ($stage) {
                        $entityName = $stage->entity?->name
                            ?? "#{$stage->entity_id}";

                        $stageLabel =
                            EntityResponsibilityType::tryFrom($stage->stage)?->label()
                            ?? $stage->stage;

                        return $entityName.' – '.$stageLabel;
                    })
                    ->join('، ');

                return back()
                    ->with(
                        'error',
                        "لا يمكن تعديل هذا المستخدم لأنه مسؤول فعلي عن المراحل التالية: [{$stageNames}]. يرجى إعادة تعيين تلك المراحل أولاً من صفحة مراحل الجهات."
                    )
                    ->withInput();
            }
        }

        $oldValues = $user->toArray();
        $oldEmail = $user->email;

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'is_financial_user' => $request->boolean('is_financial_user'),
                'role_id' => $request->role_id,
                'status' => $request->status,
                'phone' => $request->phone,

                'organization_type' => $request->organization_type,

                // الجهة الداخلية
                'entity_id' => $request->organization_type === 'internal'
                    ? $request->entity_id
                    : null,

                // الجهة الخارجية
                'authority_id' => $request->organization_type === 'external'
                    ? $request->authority_id
                    : null,

                'administrative_scope_id' => $request->administrative_scope_id,
                'work' => $request->work,
                'responsibility' => $request->responsibility ?: null,
                'updated_by' => auth()->id(),
            ]);

            // تحديث النطاقات الجغرافية
            $this->syncGeographicScopes(
                $user,
                $request->geographic_scopes ?? []
            );

            // تحديث مسؤولية المرحلة تلقائياً
            $this->syncUserStageResponsibility($user);

            // تحديث البيانات من قاعدة البيانات
            $user->refresh();

            // تسجيل التغييرات
            $changes = [];

            foreach ($oldValues as $key => $value) {
                $newValue = $user->getAttribute($key);

                if ($value != $newValue) {
                    $changes[$key] = [
                        'old' => $value,
                        'new' => $newValue,
                    ];
                }
            }

            if (! empty($changes)) {
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'update',
                    'model_type' => 'User',
                    'model_id' => $user->id,
                    'old_values' => $oldValues,
                    'new_values' => $user->toArray(),
                    'description' => 'تم تحديث بيانات المستخدم: '.$user->name,
                    'ip_address' => $request->ip(),
                ]);
            }

            DB::commit();

            // ✅ مزامنة مع ERPNext عند تحديث المستخدم (مع كلمة المرور إن وُجدت)
            $erpSyncWarning = null;
            try {
                if ($user->is_financial_user || $user->isLinkedToErpNext()) {
                    $syncResult = app(ErpNextUserService::class)->syncUser(
                        $user,
                        'update',
                        $oldEmail,
                        $request->filled('password') ? $request->password : null
                    );
                    if (! ($syncResult['success'] ?? false)) {
                        $erpSyncWarning = 'تم تحديث بيانات المستخدم محلياً، ولكن فشلت المزامنة مع ERPNext: '.($syncResult['error'] ?? 'خطأ في الاتصال').'.';
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('ERPNext sync error in UserController update: '.$e->getMessage(), [
                    'user_id' => $user->id,
                ]);
                $erpSyncWarning = 'تم تحديث بيانات المستخدم محلياً، ولكن تعذر الاتصال بـ ERPNext.';
            }

            $redirect = redirect()
                ->route('users.show', $user)
                ->with(
                    'success',
                    'تم تحديث بيانات المستخدم بنجاح'
                );

            if ($erpSyncWarning) {
                $redirect->with('warning', $erpSyncWarning);
            }

            return $redirect;

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('User update error: '.$e->getMessage(), [
                'user_id' => $user->id,
                'organization_type' => $request->organization_type,
                'entity_id' => $request->entity_id,
                'authority_id' => $request->authority_id,
            ]);

            return back()
                ->with(
                    'error',
                    'حدث خطأ أثناء تحديث المستخدم: '.$e->getMessage()
                )
                ->withInput();
        }
    }

    public function destroy(User $user)
    {
        if ($user->username === 'root') {
            return back()->with('error', 'لا يمكن حذف المستخدم الافتراضي root.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص');
        }

        $userName = $user->name;

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'model_type' => 'User',
            'model_id' => $user->id,
            'old_values' => $user->toArray(),
            'description' => 'تم حذف المستخدم: '.$userName,
            'ip_address' => request()->ip(),
        ]);

        $user->delete();

        return redirect()->route('users.index')->with('success', 'تم حذف المستخدم بنجاح');
    }

    public function disable(User $user)
    {
        if ($user->username === 'root') {
            return back()->with('error', 'لا يمكن تعطيل المستخدم الافتراضي root.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الخاص');
        }

        $user->update(['status' => 'Disabled']);

        // تعطيل الحساب المرتبط في ERPNext فوراً إذا كان المستخدم مالياً أو مرتبطاً
        $erpSyncWarning = null;
        try {
            if ($user->is_financial_user || $user->isLinkedToErpNext()) {
                $syncResult = app(ErpNextUserService::class)->syncUser($user, 'disable');
                if (! ($syncResult['success'] ?? false)) {
                    $erpSyncWarning = 'تم تعطيل المستخدم محلياً، ولكن تعذر تعطيله في ERPNext: '.($syncResult['error'] ?? 'خطأ في الاتصال').'.';
                }
            }
        } catch (\Throwable $e) {
            \Log::error('ERPNext sync error in UserController disable: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);
            $erpSyncWarning = 'تم تعطيل المستخدم محلياً، ولكن تعذر الاتصال بـ ERPNext.';
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'disable',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'تم تعطيل المستخدم: '.$user->name,
            'ip_address' => request()->ip(),
        ]);

        try {
            app(NotificationService::class)->notifyUserAccount($user, 'disabled', auth()->user());
        } catch (\Exception $e) {
            \Log::error('Notification error in UserController disable: '.$e->getMessage());
        }

        $response = back()->with('success', 'تم تعطيل المستخدم بنجاح');
        if ($erpSyncWarning) {
            $response->with('warning', $erpSyncWarning);
        }

        return $response;
    }

    public function enable(User $user)
    {
        $user->update(['status' => 'Active']);

        // إعادة تفعيل الحساب المرتبط في ERPNext فوراً إذا كان المستخدم مالياً أو مرتبطاً
        $erpSyncWarning = null;
        try {
            if ($user->is_financial_user || $user->isLinkedToErpNext()) {
                $syncResult = app(ErpNextUserService::class)->syncUser($user, 'reactivate');
                if (! ($syncResult['success'] ?? false)) {
                    $erpSyncWarning = 'تم تفعيل المستخدم محلياً، ولكن تعذر تفعيله في ERPNext: '.($syncResult['error'] ?? 'خطأ في الاتصال').'.';
                }
            }
        } catch (\Throwable $e) {
            \Log::error('ERPNext sync error in UserController enable: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);
            $erpSyncWarning = 'تم تفعيل المستخدم محلياً، ولكن تعذر الاتصال بـ ERPNext.';
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'enable',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'تم تفعيل المستخدم: '.$user->name,
            'ip_address' => request()->ip(),
        ]);

        try {
            app(NotificationService::class)->notifyUserAccount($user, 'enabled', auth()->user());
        } catch (\Exception $e) {
            \Log::error('Notification error in UserController enable: '.$e->getMessage());
        }

        $response = back()->with('success', 'تم تفعيل المستخدم بنجاح');
        if ($erpSyncWarning) {
            $response->with('warning', $erpSyncWarning);
        }

        return $response;
    }

    public function resetPassword(User $user)
    {
        return view('user.reset-password', compact('user'));
    }

    public function updatePassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:1|max:50',
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => true,
        ]);

        // ✅ مزامنة كلمة المرور الجديدة مع ERPNext
        $erpSyncWarning = null;
        try {
            if ($user->is_financial_user || $user->isLinkedToErpNext()) {
                $syncResult = app(ErpNextUserService::class)
                    ->syncUser($user, 'password', null, $request->password);
                if (! ($syncResult['success'] ?? false)) {
                    $erpSyncWarning = 'تم إعادة تعيين كلمة المرور محلياً، ولكن فشلت المزامنة مع ERPNext: '.($syncResult['error'] ?? 'خطأ في الاتصال').'.';
                }
            }
        } catch (\Throwable $e) {
            \Log::error('ERPNext sync error in UserController updatePassword: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);
            $erpSyncWarning = 'تم إعادة تعيين كلمة المرور محلياً، ولكن تعذر الاتصال بـ ERPNext.';
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'reset_password',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'تم إعادة تعيين كلمة مرور المستخدم: '.$user->name,
            'ip_address' => $request->ip(),
        ]);

        try {
            app(NotificationService::class)->notifyUserAccount($user, 'password_reset', auth()->user());
        } catch (\Exception $e) {
            \Log::error('Notification error in UserController updatePassword: '.$e->getMessage());
        }

        $response = redirect()->route('users.show', $user)->with('success', 'تم إعادة تعيين كلمة المرور بنجاح');
        if ($erpSyncWarning) {
            $response->with('warning', $erpSyncWarning);
        }

        return $response;
    }

    public function rolesPermissions()
    {
        return view('user.roles_permissions');
    }

    public function userActivityLog(Request $request, User $user)
    {
        $query = AuditLog::with('user')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('model_type', 'User')
                            ->where('model_id', $user->id);
                    });
            });

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);
        $actions = AuditLog::distinct()->pluck('action');

        return view('user.user-activity-log', compact('user', 'logs', 'actions'));
    }

    /**
     * جلب المديريات بناءً على المحافظة (AJAX)
     *
     * @return JsonResponse
     */
    public function getDirectoratesByGovernorate(Request $request)
    {
        $request->validate([
            'governorate_id' => 'required|exists:governorates,id',
        ]);

        $directorates = Directorate::where('governorate_id', $request->governorate_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($directorates);
    }

    /**
     * جلب النطاق الجغرافي للكيان (AJAX)
     *
     * @return JsonResponse
     */
    public function getEntityGeographicScope(Request $request)
    {
        $request->validate([
            'entity_id' => 'required|exists:internal_entities,id',
        ]);

        $entity = InternalEntity::with(['governorate', 'directorate', 'authority'])
            ->find($request->entity_id);

        if (! $entity) {
            return response()->json([
                'governorate_id' => null,
                'directorate_id' => null,
            ]);
        }

        // التحقق من وجود محافظة ومديرية في الكيان نفسه
        if ($entity->governorate_id && $entity->directorate_id) {
            return response()->json([
                'governorate_id' => $entity->governorate_id,
                'directorate_id' => $entity->directorate_id,
            ]);
        }

        // التحقق من وجود محافظة ومديرية في السلطة التابعة لها
        if ($entity->authority) {
            return response()->json([
                'governorate_id' => $entity->authority->governorate_id,
                'directorate_id' => $entity->authority->directorate_id,
            ]);
        }

        return response()->json([
            'governorate_id' => null,
            'directorate_id' => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * مزامنة النطاقات الجغرافية للمستخدم
     * تقوم بحذف النطاقات القديمة وإضافة النطاقات الجديدة
     *
     * @return void
     */
    private function syncGeographicScopes(User $user, array $scopes)
    {
        // حذف النطاقات القديمة
        $user->geographicScopes()->delete();

        $primaryGovId = null;
        $primaryDirId = null;
        $hasValidScope = false;

        // تصفية النطاقات الفارغة وإضافة النطاقات الجديدة
        foreach ($scopes as $scope) {
            $govInput = $scope['governorate_id'] ?? '';
            $dirInput = $scope['directorate_id'] ?? '';

            // التحقق من أن المستخدم اختار محافظة محددة أو كل المحافظات
            if ($govInput !== '' && $govInput !== null) {
                $hasValidScope = true;
                $govId = ($govInput === 'all') ? null : (int) $govInput;
                $dirId = (! empty($dirInput) && $dirInput !== 'all') ? (int) $dirInput : null;

                if ($primaryGovId === null && $govId !== null) {
                    $primaryGovId = $govId;
                    $primaryDirId = $dirId;
                }

                // تحقق من عدم وجود تكرار
                $exists = UserGeographicScope::where('user_id', $user->id)
                    ->where('governorate_id', $govId)
                    ->where('directorate_id', $dirId)
                    ->exists();

                if (! $exists) {
                    UserGeographicScope::create([
                        'user_id' => $user->id,
                        'governorate_id' => $govId,
                        'directorate_id' => $dirId,
                    ]);
                }
            }
        }

        // تحديث الحقول المباشرة في جدول المستخدمين لضمان التوافق وتصفيرها إذا اختار المستخدم بدون تحديد
        $user->update([
            'governorate_id' => $primaryGovId,
            'directorate_id' => $primaryDirId,
        ]);
    }

    /**
     * استنتاج المحافظة والمديرية من الكيان
     */
    private function resolveGeoFromEntity(?int $entityId): array
    {
        if (! $entityId) {
            return [null, null];
        }

        $entity = InternalEntity::withoutGlobalScopes()
            ->find($entityId);

        if (! $entity) {
            return [null, null];
        }

        if ($entity->governorate_id || $entity->directorate_id) {
            return [
                $entity->governorate_id,
                $entity->directorate_id,
            ];
        }

        $authority = Authority::withoutGlobalScopes()->find($entity->authority_id);

        return [
            $authority?->governorate_id,
            $authority?->directorate_id,
        ];
    }

    /**
     * تلقائياً، في حال تعيين مسؤولية ونوع جهة للمستخدم النشط، يتم تعيينه كمسؤول عن مرحلة المراجعة في إعدادات المراحل.
     */
    private function syncUserStageResponsibility(User $user): void
    {
        if (! $user->responsibility || $user->status !== 'Active') {
            return;
        }

        $responsibilityEnum = $user->responsibility instanceof UserResponsibilityType
            ? $user->responsibility
            : (is_string($user->responsibility) ? UserResponsibilityType::tryFrom($user->responsibility) : null);

        if (! $responsibilityEnum) {
            return;
        }

        $stageType = $responsibilityEnum->coversStage();

        if ($user->organization_type === 'internal' && $user->entity_id) {
            EntityApprovalStage::updateOrCreate(
                [
                    'entity_id' => $user->entity_id,
                    'stage' => $stageType->value,
                ],
                [
                    'stage_order' => $stageType->stageOrder(),
                    'is_active' => true,
                    'responsible_user_id' => $user->id,
                    'updated_by' => auth()->id(),
                ]
            );
        } elseif ($user->organization_type === 'external' && $user->authority_id) {
            AuthorityApprovalStage::updateOrCreate(
                [
                    'authority_id' => $user->authority_id,
                    'stage' => $stageType->value,
                ],
                [
                    'stage_order' => $stageType->stageOrder(),
                    'is_active' => true,
                    'responsible_user_id' => $user->id,
                    'updated_by' => auth()->id(),
                ]
            );
        }
    }

    /**
     * إعادة محاولة المزامنة يدوياً مع ERPNext
     */
    public function retryErpNextSync(User $user)
    {
        if (! $user->is_financial_user && ! $user->isLinkedToErpNext()) {
            return back()->with('error', 'هذا المستخدم ليس مستخدماً مالياً أو مرتبطاً بـ ERPNext.');
        }

        try {
            $result = app(ErpNextUserService::class)->syncUser($user, 'update');
            if ($result['success'] ?? false) {
                return back()->with('success', 'تمت مزامنة بيانات المستخدم مع ERPNext بنجاح.');
            }

            return back()->with('error', 'فشلت المزامنة مع ERPNext: '.($result['error'] ?? 'خطأ غير محدد'));
        } catch (\Throwable $e) {
            \Log::error('ERPNext manual retry sync error: '.$e->getMessage(), ['user_id' => $user->id]);

            return back()->with('error', 'تعذر الاتصال بـ ERPNext: '.$e->getMessage());
        }
    }
}

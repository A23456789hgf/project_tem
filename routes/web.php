<?php

use App\Http\Controllers\ActivityAssignmentController;
use App\Http\Controllers\Admin\EntityApprovalStageController;
use App\Http\Controllers\Admin\UIShowcaseController;
use App\Http\Controllers\Api\ErpUomController;
use App\Http\Controllers\Approval\ApprovalCenterController;
use App\Http\Controllers\Approval\ConsultationCenterController;
use App\Http\Controllers\AssociationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorityController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\BeneficiaryGroupController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConfigImportExportController;
use App\Http\Controllers\Configuration\SmsAdminController;
use App\Http\Controllers\CorrespondenceController;
use App\Http\Controllers\DirectorateController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\DonorController;
use App\Http\Controllers\EntityAuthorityController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityOfficialController;
use App\Http\Controllers\ExecutorController;
use App\Http\Controllers\FinancialItemController;
use App\Http\Controllers\FinancingTypeController;
use App\Http\Controllers\FormFinancingController;
use App\Http\Controllers\FundedEntityController;
use App\Http\Controllers\FundingSourceController;
use App\Http\Controllers\GlobalFinancingController;
use App\Http\Controllers\GovernorateController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportLogController;
use App\Http\Controllers\InternalEntityController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MainRouterController;
use App\Http\Controllers\MemoirController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParticipationController;
use App\Http\Controllers\PermissionsReportController;
use App\Http\Controllers\Planning\PlanController;
use App\Http\Controllers\PrintableSignatureController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\Project\EmpowermentBeneficiaryController;
use App\Http\Controllers\Project\EmpowermentProjectController;
use App\Http\Controllers\Project\ERPNextFinancialReportController;
use App\Http\Controllers\Project\ExecutionController;
use App\Http\Controllers\Project\ExecutiveActivitiesController;
use App\Http\Controllers\Project\ProjectAchievementController;
use App\Http\Controllers\Project\ProjectApprovalController;
use App\Http\Controllers\Project\ProjectDocumentController;
use App\Http\Controllers\Project\ProjectImportController;
use App\Http\Controllers\Project\ProjectSupervisingAuthoritiesController;
use App\Http\Controllers\Project\QualityController;
use App\Http\Controllers\Project\ReportsController;
use App\Http\Controllers\ProjectCardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectRequestController;
use App\Http\Controllers\Projects\BudgetWebController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\Report\PlExpenseSummaryController;
use App\Http\Controllers\Report\ProfitAndLossController;
use App\Http\Controllers\ReportTypeController;
use App\Http\Controllers\RequestDescendController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\SubAreaController;
use App\Http\Controllers\SubdomainController;
use App\Http\Controllers\SubFinancingFormController;
use App\Http\Controllers\SubRouterController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TargetCategoryController;
use App\Http\Controllers\TaskActivityController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDiscussionController;
use App\Http\Controllers\TaskDocumentNoteController;
use App\Http\Controllers\TaskExecutionNoteController;
use App\Http\Controllers\TaskMemoController;
use App\Http\Controllers\TypeEntityController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValueChain\ChainPlanController;
use App\Http\Controllers\ValueChainController;
use App\Http\Controllers\ValueChainFinancingController;
use App\Http\Controllers\ValueChainFinancingTypeController;
use App\Http\Controllers\ValueChainMemberController;
use App\Http\Controllers\ValueChainParticipatingEntityController;
use App\Http\Controllers\VillageController;
use App\Models\Directorate;
use App\Models\Governorate;
use App\Models\Project;
use App\Models\SubArea;
use App\Models\User;
use App\Models\Village;
use App\Services\FileImportService;
use App\Services\SmppSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ============================================
// الصفحة الرئيسية - Route 'home'
// ============================================
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

// ============================================
// Route للتحقق من الجلسة (للتشخيص)
// ============================================
Route::get('/check-session', [AuthController::class, 'checkSession'])->middleware('auth');

// ============================================
// Routes للزوار (غير مسجلين الدخول)
// ============================================
Route::middleware('guest')->group(function () {
    // SMS Debug Route
    Route::get('/resend-sms-temp', function () {
        $user = User::find(32);
        if ($user) {
            $smsService = app(SmppSmsService::class);
            $message = 'تم انشاء مستخدم  في نظام متابعة المشاريع برقمك '.$user->user_id.' وكلمة مرور 123456  عليك الدخول https://project.mafwr.gov.ye/ وقم بتغيرها';
            $result = $smsService->sendSMS($user->id, $user->phone, $message, 'manual_retry_user_registered');

            return response()->json($result);
        }

        return 'User not found';
    });

    // Routes المصادقة
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    // مسارات استعادة كلمة المرور عبر OTP
    Route::post('/password/otp/send', [AuthController::class, 'sendOtp'])->name('password.otp.send');
    Route::post('/password/otp/verify', [AuthController::class, 'verifyOtp'])->name('password.otp.verify');
    Route::post('/password/otp/reset', [AuthController::class, 'resetPasswordWithOtp'])->name('password.otp.reset');

    // مسارات استعادة كلمة المرور التقليدية
    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request');

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('auth.send-reset-link');

    Route::get('/reset-password/{token}', function (string $token) {
        return view('auth.reset-password', ['token' => $token]);
    })->middleware('guest')->name('password.reset');

    Route::post('/reset-password', function (Request $request) {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    })->middleware('guest')->name('password.update');

    // Debug Route
    Route::get('/debug-auth', function () {
        try {
            $username = 'MAFWRPRO0003';
            $user = User::withoutGlobalScopes()
                ->where(function ($query) use ($username) {
                    $query->where('username', $username)
                        ->orWhere('email', $username)
                        ->orWhere('user_id', $username);
                })->first();

            if ($user) {
                return [
                    'found' => true,
                    'username' => $user->username,
                    'user_id' => $user->user_id,
                    'status' => $user->status,
                    'password_check_123456' => Hash::check('123456', $user->password),
                ];
            }

            return [
                'found' => false,
                'message' => 'User not found',
                'first_5_users' => User::withoutGlobalScopes()->limit(5)->get(['username', 'user_id']),
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    });
});

// ============================================
// Routes للمستخدمين المسجلين (مع مصادقة)
// ============================================
Route::middleware('auth')->group(function () {
    // ========================================
    // تسجيل الخروج
    // ========================================
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ========================================
    // تغيير كلمة المرور الإجباري
    // ========================================
    Route::get('/force-change-password', [AuthController::class, 'showForceChangePassword'])->name('password.change.forced');
    Route::post('/force-change-password', [AuthController::class, 'forceChangePassword'])->name('password.change.update');

    // ========================================
    // لوحة التحكم
    // ========================================
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard')->middleware('can:dashboard.view');

    // ========================================
    // الملف الشخصي
    // ========================================
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show')->middleware('can:profile.view');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update')->middleware('can:profile.edit');
    Route::post('/profile/signature/setup', [ProfileController::class, 'setupSignature'])->name('profile.signature.setup');
    Route::get('/my-assignments', [ActivityAssignmentController::class, 'myAssignments'])->name('assignments.my')->middleware('can:profile.assignments.view');

    // ========================================
    // الإشعارات
    // ========================================
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/latest', [NotificationController::class, 'getLatest'])->name('notifications.latest');

    // ========================================
    // الدردشة
    // ========================================
    Route::prefix('chat')->middleware('can:chat.view')->group(function () {
        Route::get('/users', [ChatController::class, 'getUsers'])->name('chat.users');
        Route::get('/messages/{userId}', [ChatController::class, 'getMessages'])->name('chat.messages');
        Route::post('/messages', [ChatController::class, 'sendMessage'])->name('chat.send');
        Route::get('/poll', [ChatController::class, 'poll'])->name('chat.poll');
    });

    // ========================================
    // البحث الموحد
    // ========================================
    Route::get('/lookup/search', [LookupController::class, 'search'])->name('lookup.search');
    Route::get('/units/by-financial-item', [UnitController::class, 'byFinancialItem'])->name('lookup.units.by_financial_item');

    // ========================================
    // الاقتراحات
    // ========================================
    Route::get('/api/suggestions', [SuggestionController::class, 'index'])->name('suggestions.index');
    Route::post('/api/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store')->middleware('can:suggestions.create');
    Route::post('/api/suggestions/{id}/toggle-complete', [SuggestionController::class, 'toggleComplete'])->name('suggestions.toggle-complete')->middleware('can:suggestions.complete');

    // ========================================
    // Routes التطوير والاختبار
    // ========================================
    Route::middleware('can:configuration.view')->group(function () {
        // Design System Demo
        Route::get('/design-system-demo', function () {
            return view('design-system-demo');
        })->name('design-system-demo');

        Route::get('/sample-multistep', function () {
            return view('sample-multistep');
        })->name('sample.form');

        Route::post('/sample-multistep', function () {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ البيانات بنجاح!',
                'redirect' => '/',
            ]);
        })->name('sample.store');

        // Test cascading dropdowns
        Route::get('/test-locations', function () {
            $governorates = Governorate::where('is_active', true)->orderBy('name')->get();

            return view('test-locations', compact('governorates'));
        })->name('test.locations');

        // Test import functionality
        Route::get('/test-import', function () {
            try {
                $service = new FileImportService;
                $templateData = [
                    'headers' => ['id', 'governorate_name', 'directorate_name', 'name', 'is_active'],
                    'sample_data' => [
                        [1, 'صنعاء', 'مديرية الثورة', 'حي الحصبة الجديد', 1],
                        [2, 'عدن', 'مديرية المعلا', 'حي المعلا الشمالي', 1],
                        [3, 'تعز', 'مديرية الوازعية', 'منطقة الوازعية الشرقية', 1],
                    ],
                ];
                $filePath = $service->createTemplate($templateData, 'xlsx');

                return response()->json([
                    'success' => true,
                    'message' => 'Import functionality is working',
                    'template_path' => $filePath,
                    'governorates' => Governorate::count(),
                    'directorates' => Directorate::count(),
                    'sub_areas' => SubArea::count(),
                    'villages' => Village::count(),
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        })->name('test.import');

        // Debug Scope Route
        Route::get('/debug-scope/{username}', function (string $username) {
            $user = User::where('username', $username)
                ->orWhere('email', $username)
                ->first();

            if (! $user) {
                return response()->json(['error' => "User '{$username}' not found"], 404);
            }

            $user->load('role');
            $geoScope = $user->getModuleGeoScope('projects');
            $adminScope = $user->getModuleAdminScope('projects');
            $govId = $user->getAssignedGovernorateId();
            $dirId = $user->getAssignedDirectorateId();

            $roleData = [
                'id' => $user->role?->id,
                'name' => $user->role?->name,
                'full_access' => $user->role?->full_access,
                'module_scopes_raw' => $user->role?->module_scopes,
                'module_geo_scopes_raw' => $user->role?->module_geo_scopes,
            ];

            try {
                Illuminate\Support\Facades\DB::enableQueryLog();
                $query = Project::visibleToUser($user)->toSql();
                $bindings = Project::visibleToUser($user)->getBindings();
                $count = Project::visibleToUser($user)->count();
                $logs = Illuminate\Support\Facades\DB::getQueryLog();
                Illuminate\Support\Facades\DB::disableQueryLog();
            } catch (Throwable $e) {
                $query = 'ERROR: '.$e->getMessage();
                $bindings = [];
                $count = null;
                $logs = [];
            }

            $govName = null;
            if ($govId) {
                $govName = Governorate::find($govId)?->name;
            }

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username ?? $user->email,
                    'name' => $user->name,
                    'governorate_id' => $user->governorate_id,
                    'directorate_id' => $user->directorate_id,
                    'entity_id' => $user->entity_id,
                    'isAdmin' => $user->isAdmin(),
                    'isCentralUser' => $user->isCentralUser(),
                    'isGeographicUser' => $user->isGeographicUser(),
                ],
                'resolved' => [
                    'geoScope' => $geoScope,
                    'adminScope' => $adminScope,
                    'govId' => $govId,
                    'govName' => $govName,
                    'dirId' => $dirId,
                ],
                'role' => $roleData,
                'query' => [
                    'sql' => $query,
                    'bindings' => $bindings,
                    'project_count_visible' => $count,
                ],
                'queryLog' => array_map(fn ($l) => [
                    'sql' => $l['query'],
                    'bindings' => $l['bindings'],
                ], array_slice($logs, -3)),
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        })->name('debug.scope');
    });

    // ========================================
    // إدارة الـ SMS
    // ========================================
    Route::prefix('configuration/sms')->name('configuration.sms.')->group(function () {
        Route::get('/settings', [SmsAdminController::class, 'settings'])->name('settings')->middleware('can:sms.settings.view');
        Route::post('/settings', [SmsAdminController::class, 'updateSettings'])->name('update-settings')->middleware('can:sms.settings.update');
        Route::get('/logs', [SmsAdminController::class, 'logs'])->name('logs')->middleware('can:sms.logs.view');
        Route::get('/manual', [SmsAdminController::class, 'manual'])->name('manual')->middleware('can:sms.manage');
        Route::post('/manual', [SmsAdminController::class, 'sendManual'])->name('send-manual')->middleware('can:sms.manual.send');
    });

    // ========================================
    // إدارة المجالات (Domains)
    // ========================================
    Route::middleware(['can:domains.view'])->group(function () {
        Route::get('/domains/active', [DomainController::class, 'activeDomains'])->name('domains.active');
        Route::resource('domains', DomainController::class)->except(['show']);
        Route::patch('/domains/{domain}/toggle-status', [DomainController::class, 'toggleStatus'])->name('domains.toggleStatus')->middleware('can:domains.edit');
    });

    // ========================================
    // إدارة المنفذين (Executors)
    // ========================================
    Route::middleware(['can:executors.view'])->group(function () {
        Route::get('/executors/active', [ExecutorController::class, 'activeExecutors'])->name('executors.active');
        Route::resource('executors', ExecutorController::class)->except(['show']);
        Route::patch('/executors/{executor}/toggle', [ExecutorController::class, 'toggle'])->name('executors.toggle')->middleware('can:executors.edit');
    });

    // ========================================
    // إدارة المراحل (Stages)
    // ========================================
    Route::middleware(['can:stages.view'])->group(function () {
        Route::get('/stages/active', [StageController::class, 'activeStages'])->name('stages.active');
        Route::resource('stages', StageController::class);
        Route::get('/stages/{stage}/approval-path', [StageController::class, 'showApprovalPath'])->name('stages.show-approval-path');
    });

    // ========================================
    // التقارير (Reports)
    // ========================================
    Route::middleware('can:reports.view')->group(function () {
        Route::prefix('projects/reports')->name('projects.reports.')->group(function () {
            Route::get('/', [ReportsController::class, 'index'])->name('index');
            Route::get('/implementation', [ReportsController::class, 'implementation'])->name('implementation');
            Route::get('/quality', [ReportsController::class, 'quality'])->name('quality');
            Route::get('/financial', [ReportsController::class, 'financial'])->name('financial');
            Route::get('/financial-erpnext', [ERPNextFinancialReportController::class, 'index'])->name('financial_erpnext');
            Route::get('/financial-erpnext/data', [ERPNextFinancialReportController::class, 'getFinancialData'])->name('financial_erpnext_data');
            Route::get('/profit-and-loss', [ProfitAndLossController::class, 'index'])->name('profit_and_loss');
            Route::get('/pl-expense-summary', [PlExpenseSummaryController::class, 'index'])->name('pl_expense_summary');
            Route::get('/pl-expense-summary/print-general', [PlExpenseSummaryController::class, 'printGeneral'])->name('pl_expense_summary.print_general');
            Route::get('/pl-expense-summary/print-items', [PlExpenseSummaryController::class, 'printItems'])->name('pl_expense_summary.print_items');
            Route::get('/pl-expense-summary/export-general-excel', [PlExpenseSummaryController::class, 'exportGeneralExcel'])->name('pl_expense_summary.export_general_excel');
            Route::get('/pl-expense-summary/export-items-excel', [PlExpenseSummaryController::class, 'exportItemsExcel'])->name('pl_expense_summary.export_items_excel');
            Route::get('/progress', [ReportsController::class, 'progress'])->name('progress');
            Route::get('/status', [ReportsController::class, 'status'])->name('status');
            Route::get('/overview', [ReportsController::class, 'overview'])->name('overview');
            Route::get('/print', [ReportsController::class, 'printIndex'])->name('print_options');
            Route::post('/print', [ReportsController::class, 'printGenerate'])->name('print_generate');
            Route::get('/official-summary', [ReportsController::class, 'downloadOfficialReport'])->name('official_summary');
        });
    });

    // ========================================
    // تقارير المخزون (ERP Stock Reports)
    // ========================================
    Route::prefix('projects/reports')->name('projects.reports.')->group(function () {
        // Shared Lookup (Controller handles canAny authorization)
        Route::get('/material-requests/api/lookup', [App\Http\Controllers\Project\ErpStockReportController::class, 'apiLookup'])->name('material_requests.api_lookup');

        // طلبات المواد
        Route::middleware('can:reports.material_requests.view')->group(function () {
            Route::get('/material-requests', [App\Http\Controllers\Project\ErpStockReportController::class, 'materialRequests'])->name('material_requests');
            Route::get('/material-requests/items/{name}', [App\Http\Controllers\Project\ErpStockReportController::class, 'materialRequestItems'])->name('material_requests.items');
        });

        // صرف المواد
        Route::middleware('can:reports.stock_entries.view')->group(function () {
            Route::get('/stock-entries', [App\Http\Controllers\Project\ErpStockReportController::class, 'stockEntries'])->name('stock_entries');
            Route::get('/stock-entries/items/{name}', [App\Http\Controllers\Project\ErpStockReportController::class, 'stockEntryItems'])->name('stock_entries.items');
        });

        // سجل المخزون
        Route::middleware('can:reports.stock_ledger.view')->group(function () {
            Route::get('/stock-ledger', [App\Http\Controllers\Project\ErpStockReportController::class, 'stockLedger'])->name('stock_ledger');
        });

        // فواتير الشراء
        Route::middleware('can:reports.purchase_invoices.view')->group(function () {
            Route::get('/purchase-invoices', [App\Http\Controllers\Project\PurchaseInvoiceController::class, 'purchaseInvoices'])->name('purchase_invoices');
            Route::get('/purchase-invoices/items/{name}', [App\Http\Controllers\Project\PurchaseInvoiceController::class, 'purchaseInvoiceItems'])->name('purchase_invoices.items');
        });
    });

    // ========================================
    // إدارة المشاريع (Projects)
    // ========================================
    Route::group(['prefix' => 'projects', 'as' => 'projects.'], function () {
        // القائمة الرئيسية
        Route::get('/', [ProjectController::class, 'index'])->name('index')->middleware('can:projects.view');
        Route::get('/create', [ProjectController::class, 'create'])->name('create')->middleware('can:projects.create');

        // الموازنات (ERPNext Budget DocType)
        Route::get('/budgets', [BudgetWebController::class, 'index'])->name('budgets.index')->middleware('can:projects.view');
        Route::post('/', [ProjectController::class, 'store'])->name('store')->middleware('can:projects.create');
        Route::put('/{project}/update-entity', [ProjectController::class, 'updateEntity'])->name('update-entity')->middleware('can:projects.edit');
        Route::post('/bulk-destroy', [ProjectController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('can:projects.delete');
        Route::post('/check-name', [ProjectController::class, 'checkName'])->name('check-name')->middleware('can:projects.view');

        // خطوات النموذج
        Route::post('/step/1', [ProjectController::class, 'storeStep1'])->name('step.1')->middleware('can:projects.create');
        Route::post('/step/2', [ProjectController::class, 'storeStep2'])->name('step.2')->middleware('can:projects.edit');
        Route::post('/step/3', [ProjectController::class, 'storeStep3'])->name('step.3')->middleware('can:projects.edit');
        Route::post('/step/4', [ProjectController::class, 'storeStep4'])->name('step.4')->middleware('can:projects.edit');
        Route::post('/step/5', [ProjectController::class, 'storeStep5'])->name('step.5')->middleware('can:projects.edit');
        Route::post('/step/6', [ProjectController::class, 'storeStep6'])->name('step.6')->middleware('can:projects.edit');
        Route::post('/step/7', [ProjectController::class, 'storeStep7'])->name('step.7')->middleware('can:projects.edit');

        // Auto-save & Documents
        Route::post('/auto-save', [ProjectController::class, 'autoSave'])->name('auto-save')->middleware('can:projects.view');
        Route::put('/{project}/auto-save', [ProjectController::class, 'autoSaveUpdate'])->name('auto-save-update')->middleware('can:projects.edit');
        Route::post('/{project}/upload-documents', [ProjectController::class, 'uploadDocuments'])->name('upload-documents')->middleware('can:projects.edit');

        // إدارة التمكين (Empowerment)
        Route::get('/empowerment/beneficiaries', [EmpowermentBeneficiaryController::class, 'all'])->name('empowerment.beneficiaries.all')->middleware('can:empowerment.beneficiaries.view');
        Route::get('/empowerment', [EmpowermentProjectController::class, 'index'])->name('empowerment')->middleware('can:empowerment.view');
        Route::get('/empowerment/{empowermentProject}', [EmpowermentProjectController::class, 'show'])->name('empowerment.show')->middleware('can:empowerment.view');
        Route::post('/empowerment/{empowermentProject}/status', [EmpowermentProjectController::class, 'updateStatus'])->name('empowerment.update-status')->middleware('can:empowerment.edit');
        Route::get('/empowerment/{empowermentProject}/beneficiaries', [EmpowermentBeneficiaryController::class, 'index'])->name('empowerment.beneficiaries.index')->middleware('can:empowerment.beneficiaries.view');
        Route::post('/empowerment/{empowermentProject}/beneficiaries', [EmpowermentBeneficiaryController::class, 'store'])->name('empowerment.beneficiaries.store')->middleware('can:empowerment.beneficiaries.create');
        Route::delete('/empowerment/{empowermentProject}/beneficiaries/{beneficiary}', [EmpowermentBeneficiaryController::class, 'destroy'])->name('empowerment.beneficiaries.destroy')->middleware('can:empowerment.beneficiaries.delete');

        // الموافقات (Approvals)
        Route::middleware('can:approvals.view')->group(function () {
            Route::get('/approval', [ProjectApprovalController::class, 'index'])->name('approval.index');
            Route::post('/{project}/approval/submit', [ProjectApprovalController::class, 'submitForApproval'])->name('approval.submit');
            Route::get('/{project}/approval', [ProjectApprovalController::class, 'show'])->name('approval.show');
            Route::post('/{project}/approval/approve', [ProjectApprovalController::class, 'approve'])->name('approval.approve')->middleware(['can:approvals.approve', 'require_signature']);
            Route::post('/{project}/approval/reject', [ProjectApprovalController::class, 'reject'])->name('approval.reject')->middleware('can:approvals.reject');
            Route::post('/{project}/approval/request-action', [ProjectApprovalController::class, 'requestAction'])->name('approval.requestAction')->middleware('can:approvals.request-action');
            Route::post('/{project}/approval/resubmit', [ProjectApprovalController::class, 'resubmit'])->name('approval.resubmit');
            Route::post('/{project}/approval/referral', [ProjectApprovalController::class, 'submitReferral'])->name('approval.referral');
            Route::post('/{project}/approval/update-restricted', [ProjectApprovalController::class, 'updateRestrictedFields'])->name('approval.updateRestrictedFields')->middleware('can:approvals.approve');
        });

        // الجودة
        Route::get('/quality', [QualityController::class, 'index'])->name('quality.index')->middleware('can:quality.view');
        Route::prefix('/{project}/quality')->name('quality.')->middleware('can:quality.view')->group(function () {
            Route::get('/', [QualityController::class, 'show'])->name('show');
            Route::post('/', [QualityController::class, 'store'])->name('store')->middleware('can:quality.edit');
            Route::put('/{quality}', [QualityController::class, 'update'])->name('update')->middleware('can:quality.edit');
            Route::delete('/{quality}', [QualityController::class, 'destroy'])->name('destroy')->middleware('can:quality.edit');
            Route::get('/{quality}/attachment/{filename}', [QualityController::class, 'downloadAttachment'])->name('attachment.download');
        });

        // CRUD المشاريع
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show')->middleware('can:projects.view-details');
        Route::get('/{project}/complete-data', [ProjectController::class, 'completeData'])->name('complete-data')->middleware('can:projects.edit');
        Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit')->middleware('can:projects.edit');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update')->middleware('can:projects.edit');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy')->middleware('can:projects.delete');

        // إنجازات المشروع
        Route::get('/{project}/achievements/create', [ProjectAchievementController::class, 'create'])->name('achievements.create')->middleware('can:projects.edit');
        Route::post('/{project}/achievements', [ProjectAchievementController::class, 'store'])->name('achievements.store')->middleware('can:projects.edit');

        // المهام (Tasks)
        Route::get('/api/tasks/organizations', [TaskController::class, 'getOrganizations'])->name('api.tasks.organizations');
        Route::get('/api/entities/{entityId}/users', [TaskController::class, 'getEntityUsers'])->name('api.entities.users');
        Route::prefix('{project}/tasks')->as('tasks.')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::post('/', [TaskController::class, 'store'])->name('store');
            Route::get('/{task}', [TaskController::class, 'show'])->name('show');
            Route::put('/{task}', [TaskController::class, 'update'])->name('update');
            Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
            Route::post('/{task}/stop', [TaskController::class, 'stopTask'])->name('stop');

            Route::post('/{task}/discussions', [TaskDiscussionController::class, 'store'])->name('discussions.store');
            Route::post('/{task}/memos', [TaskMemoController::class, 'store'])->name('memos.store');
            Route::post('/{task}/memos/{memo}/sign', [TaskMemoController::class, 'sign'])->name('memos.sign');
            Route::delete('/{task}/memos/{memo}', [TaskMemoController::class, 'destroy'])->name('memos.destroy');
            Route::post('/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
            Route::delete('/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
            Route::post('/{task}/execution-notes', [TaskExecutionNoteController::class, 'store'])->name('execution-notes.store');
            Route::post('/{task}/document-notes', [TaskDocumentNoteController::class, 'store'])->name('document-notes.store');
            Route::get('/{task}/activities', [TaskActivityController::class, 'index'])->name('activities.index');
        });

        // مزامنة مع ERP
        Route::post('/{project}/sync-to-erp', [ProjectController::class, 'syncProjectToErp'])->name('sync-to-erp')->middleware('can:projects.sync');

        // تصدير المشاريع
        Route::get('/{project}/export/pdf', [ProjectController::class, 'exportProjectPdf'])->name('export-pdf')->middleware('can:projects.export');
        Route::get('/{project}/export/excel', [ProjectController::class, 'exportProjectExcel'])->name('export-excel-single')->middleware('can:projects.export');
        Route::get('/{project}/export/excel/comprehensive', [ProjectController::class, 'exportProjectExcelComprehensive'])->name('export-excel-comprehensive')->middleware('can:projects.export');
        Route::get('/{project}/print', [ProjectController::class, 'print'])->name('print')->middleware('can:projects.print');
        Route::get('/{project}/print-financial', [ProjectController::class, 'printFinancial'])->name('print-financial')->middleware('can:projects.print');
        Route::get('/{project}/export/word', function (Project $project) {
            return redirect()->route('projects.export-pdf', $project->id)->with('info', 'تصدير Word غير متاح حالياً، تم تحويلك إلى تصدير PDF.');
        })->name('export-word')->middleware('can:projects.export');

        // استيراد المشاريع
        Route::get('/import/form', [ProjectImportController::class, 'showImportForm'])->name('import')->middleware('can:projects.import');
        Route::get('/import/list', function () {
            return view('projects.import-list');
        })->name('import-list')->middleware('can:projects.import');
        Route::get('/import/download-template', [ProjectImportController::class, 'downloadTemplate'])->name('download-template')->middleware('can:projects.import');
        Route::post('/import/preview', [ProjectImportController::class, 'previewImport'])->name('preview-import')->middleware('can:projects.import');
        Route::post('/import/process', [ProjectImportController::class, 'processImport'])->name('process-import')->middleware('can:projects.import');
        Route::match(['get', 'post'], '/import/failure-report', [ProjectImportController::class, 'downloadFailureReport'])->name('failure-report')->middleware('can:projects.import');

        // Phase 2: معالجة القيم الناقصة وإعادة الاستيراد — جميعها محمية بنفس صلاحية الاستيراد
        Route::get('/import/dropdown-options/{fieldKey}', [ProjectImportController::class, 'getDropdownOptions'])->name('import-dropdown-options')->middleware('can:projects.import');
        Route::post('/import/save-mapping', [ProjectImportController::class, 'saveMissingValueMapping'])->name('import-save-mapping')->middleware('can:projects.import');
        Route::post('/import/re-import-skipped', [ProjectImportController::class, 'reImportSkipped'])->name('import-re-import-skipped')->middleware('can:projects.import');
        Route::get('/import/download-dropdown-report', [ProjectImportController::class, 'downloadDropdownReport'])->name('import-download-dropdown-report')->middleware('can:projects.import');

        // المسودة (Draft)
        Route::get('/{project}/draft/resume', [ProjectController::class, 'resumeDraft'])->name('draft.resume')->middleware('can:resume,project');
        Route::get('/{project}/validate-draft', [ProjectController::class, 'validateDraftStatus'])->name('validate-draft')->middleware('can:projects.view');
        Route::middleware('can:project-drafts.view-last')->group(function () {
            Route::get('/draft/last', [ProjectController::class, 'getLastDraft'])->name('draft.last');
            Route::get('/{project}/draft/data', [ProjectController::class, 'getDraftData'])->name('draft.data');
        });

        // سير عمل الموافقات
        Route::prefix('/{project}')->middleware('can:approvals.view')->group(function () {
            Route::post('/approval-workflow/approve', [ProjectApprovalController::class, 'approveProject'])->name('approveProject')->middleware(['can:approvals.approve', 'require_signature']);
            Route::get('/approval-workflow/status', [ProjectApprovalController::class, 'getApprovalStatus'])->name('getApprovalStatus');
            Route::get('/approval-workflow/timeline', [ProjectApprovalController::class, 'getApprovalTimeline'])->name('getApprovalTimeline');
            Route::post('/approval-workflow/reset/{approvalId}', [ProjectApprovalController::class, 'resetApprovalStage'])->name('resetApprovalStage')->middleware('can:approvals.approve');
            Route::get('/approval-workflow/movement-log', [ProjectApprovalController::class, 'getProjectMovementLog'])->name('getProjectMovementLog');
            Route::get('/approval-workflow/attachments', [ProjectApprovalController::class, 'getApprovalAttachments'])->name('getApprovalAttachments');
            Route::get('/approval-workflow/pending', [ProjectApprovalController::class, 'getProjectPendingApprovals'])->name('getProjectPendingApprovals');
            Route::get('/approval-workflow/requiring-action', [ProjectApprovalController::class, 'getProjectTransactionsRequiringAction'])->name('getProjectTransactionsRequiringAction');
            Route::get('/approval-workflow/audit-log', [ProjectApprovalController::class, 'getApprovalAuditLog'])->name('getApprovalAuditLog');
            Route::get('/financial-data', [ProjectApprovalController::class, 'getFinancialData'])->name('getFinancialData');

            // المراجعة المالية والفنية
            Route::get('/review/financial', [ProjectApprovalController::class, 'showFinancialReview'])->name('review.financial')->middleware('can:reviews.financial');
            Route::post('/review/financial', [ProjectApprovalController::class, 'submitFinancialReview'])->name('review.financial.submit')->middleware('can:reviews.financial');
            Route::get('/review/technical', [ProjectApprovalController::class, 'showTechnicalReview'])->name('review.technical')->middleware('can:reviews.technical');
            Route::post('/review/technical', [ProjectApprovalController::class, 'submitTechnicalReview'])->name('review.technical.submit')->middleware('can:reviews.technical');
            Route::get('/activity/export', [ProjectApprovalController::class, 'exportActivityHistory'])->name('activity.export');
        });

        Route::get('/approvals/pending', [ProjectApprovalController::class, 'getPendingApprovals'])->name('approvals.pending')->middleware('can:approvals.view');

        // تصدير جميع المشاريع
        Route::get('/export/excel', [ProjectController::class, 'exportAllProjectsExcel'])->name('export-excel')->middleware('can:projects.export');
        Route::get('/export/excel/comprehensive', [ProjectController::class, 'exportAllProjectsExcelComprehensive'])->name('export-excel-comprehensive-all')->middleware('can:projects.export');
        Route::get('/export/pdf', [ProjectController::class, 'exportAllProjectsPdf'])->name('export-pdf-all')->middleware('can:projects.export');
        Route::get('/export/pivot', [ProjectController::class, 'exportAllProjectsPivot'])->name('export-pivot-all')->middleware('can:projects.export');

        // التنفيذ والجدولة
        Route::get('/{project}/execution', [ProjectController::class, 'execution'])->name('execution')->middleware('can:projects.execute');
        Route::get('/{project}/schedule', [ProjectController::class, 'schedule'])->name('schedule')->middleware('can:projects.schedule');

        // التنفيذ (Execution)
        Route::middleware('can:execution.view')->group(function () {
            Route::post('/{project}/execution/save', [ExecutionController::class, 'store'])->name('execution.store')->middleware('can:execution.edit');
            Route::put('/{project}/execution/{execution}', [ExecutionController::class, 'update'])->name('execution.update')->middleware('can:execution.edit');
            Route::delete('/{project}/execution/{execution}', [ExecutionController::class, 'destroy'])->name('execution.destroy')->middleware('can:execution.delete');
            Route::delete('/{project}/execution/{execution}/technical-document', [ExecutionController::class, 'deleteTechnicalDocument'])->name('execution.delete-technical')->middleware('can:execution.edit');
            Route::delete('/{project}/execution/{execution}/financial-document', [ExecutionController::class, 'deleteFinancialDocument'])->name('execution.delete-financial')->middleware('can:execution.edit');
            Route::get('/{project}/execution/{execution}/download', [ExecutionController::class, 'downloadDocument'])->name('execution.download');

            Route::post('/{project}/execution-delay/store', [ExecutionController::class, 'storeDelayExplanation'])->name('execution.storeDelayExplanation')->middleware('can:execution.edit');
            Route::post('/{project}/execution-delay/{explanation}/approve', [ExecutionController::class, 'approveDelayExplanation'])->name('execution.approveDelayExplanation')->middleware('can:execution.approve');

            Route::post('/{project}/execution/budget-justification', [ExecutionController::class, 'storeBudgetJustification'])->name('execution.storeBudgetJustification')->middleware('can:execution.edit');
            Route::post('/{project}/execution/budget-justification/{justification}/approve', [ExecutionController::class, 'approveBudgetJustification'])->name('execution.approveBudgetJustification')->middleware('can:execution.approve');

            Route::post('/{project}/execution/preliminary/{execution}/approve', [ExecutionController::class, 'approvePreliminaryExecution'])->name('execution.preliminary.approve')->middleware('can:execution.approve');
            Route::post('/{project}/execution/preliminary/{execution}/reject', [ExecutionController::class, 'rejectPreliminaryExecution'])->name('execution.preliminary.reject')->middleware('can:execution.reject');
            Route::post('/{project}/execution/executive/{execution}/approve', [ExecutionController::class, 'approveExecutiveExecution'])->name('execution.executive.approve')->middleware('can:execution.approve');
            Route::post('/{project}/execution/executive/{execution}/reject', [ExecutionController::class, 'rejectExecutiveExecution'])->name('execution.executive.reject')->middleware('can:execution.reject');

            Route::get('/{project}/execution/preliminary', [ExecutionController::class, 'preliminaryIndex'])->name('execution.preliminary');
            Route::get('/{project}/execution/executive', [ExecutionController::class, 'executiveIndex'])->name('execution.executive');
            Route::get('/{project}/execution/print', [ExecutionController::class, 'printExecution'])->name('execution.print');

            // التكليفات
            Route::post('/{project}/assign', [ActivityAssignmentController::class, 'store'])->name('assignments.store')->middleware('can:execution.assign');
            Route::delete('/assignments/{assignment}', [ActivityAssignmentController::class, 'destroy'])->name('assignments.destroy')->middleware('can:execution.assign');
            Route::get('/assignments/users', [ActivityAssignmentController::class, 'getUsers'])->name('assignments.users');
        });

        // تتبع التنفيذ
        Route::get('/execution-tracking', [ExecutionController::class, 'trackingIndex'])->name('execution.tracking')->middleware('can:execution.view');

        // تفاصيل المشروع وبطاقة المشروع
        Route::middleware('can:projects.view')->group(function () {
            Route::get('/{project}/card', [ProjectCardController::class, 'show'])->name('card.show');
            Route::get('/{project}/card/print', [ProjectCardController::class, 'print'])->name('card.print');
            Route::get('/{project}/card/pdf', [ProjectCardController::class, 'exportPdf'])->name('card.export-pdf');
            Route::get('/{project}/card/table', [ProjectCardController::class, 'exportTable'])->name('card.export-table');
            Route::get('/{project}/card/print-reviews', [ProjectCardController::class, 'printReviews'])->name('card.print-reviews');
            Route::post('/{project}/finalize', [ProjectController::class, 'finalize'])->name('finalize')->middleware('can:projects.finalize');
            Route::post('/{project}/revert-draft', [ProjectController::class, 'revertToDraft'])->name('revert-draft')->middleware('can:revert,project');

            // API للمناطق
            Route::get('/get-subdomains/{domain_id}', [ProjectController::class, 'getSubdomains'])->name('getSubdomains');
            Route::get('/get-interventions', [ProjectController::class, 'getInterventions'])->name('getInterventions');
            Route::get('/api/subdomains/{domain_id}', [ProjectController::class, 'getSubdomainsJson'])->name('api.subdomains');
            Route::get('/api/interventions/{subdomain_id}', [ProjectController::class, 'getInterventionsJson'])->name('api.interventions');
        });

        // المراجعة والمخاطر
        Route::middleware('can:projects.view')->group(function () {
            Route::get('/{project}/review', [ProjectController::class, 'review'])->name('review')->middleware('can:projects.review');
            Route::post('/{project}/approve-internally', [ProjectController::class, 'approveInternally'])->name('approve-internally')->middleware('can:projects.approve');

            Route::get('/{project}/risks', [ProjectController::class, 'getRisks'])->name('risks.index');
            Route::get('/{project}/risks-list', [ProjectController::class, 'getRisksList'])->name('risks.list');
            Route::get('/{project}/outputs-list', [ProjectController::class, 'getOutputsList'])->name('outputs.list');
            Route::post('/{project}/risks/analyze', [ProjectController::class, 'analyzeRisks'])->name('risks.analyze')->middleware('can:projects.edit');
            Route::delete('/{project}/risks/{risk}', [ProjectController::class, 'deleteRisk'])->name('risks.destroy')->middleware('can:projects.delete');

            // المعالج (Wizard)
            Route::get('/wizard/create', [ProjectController::class, 'wizardCreate'])->name('wizard.create')->middleware('can:projects.create');
            Route::get('/{project}/wizard/edit', [ProjectController::class, 'wizardEdit'])->name('wizard.edit')->middleware('can:projects.edit');
            Route::post('/convert-to-hijri', [ProjectController::class, 'convertToHijri'])->name('convert-to-hijri');
            Route::get('/convert-to-hijri', [ProjectController::class, 'convertToHijri'])->name('projects.convertToHijri');
        });

        // الأنشطة التنفيذية
        Route::middleware('can:executive-activities.view')->group(function () {
            Route::get('/{project}/executive-activities', [ExecutiveActivitiesController::class, 'index'])->name('executive-activities.index');
            Route::post('/{project}/executive-activities', [ExecutiveActivitiesController::class, 'store'])->name('executive-activities.store');
            Route::get('/{project}/executive-activities/{executiveActivity}', [ExecutiveActivitiesController::class, 'show'])->name('executive-activities.show');
            Route::put('/{project}/executive-activities/{executiveActivity}', [ExecutiveActivitiesController::class, 'update'])->name('executive-activities.update');
            Route::delete('/{project}/executive-activities/{executiveActivity}', [ExecutiveActivitiesController::class, 'destroy'])->name('executive-activities.destroy');
            Route::get('/{project}/executive-activities/export', [ExecutiveActivitiesController::class, 'export'])->name('executive-activities.export');

            Route::post('/{project}/executive-activities/{executiveActivity}/actions', [ExecutiveActivitiesController::class, 'storeAction'])->name('executive-activities.actions.store');
            Route::put('/{project}/executive-activities/{executiveActivity}/actions/{action}', [ExecutiveActivitiesController::class, 'updateAction'])->name('executive-activities.actions.update');
            Route::delete('/{project}/executive-activities/{executiveActivity}/actions/{action}', [ExecutiveActivitiesController::class, 'destroyAction'])->name('executive-activities.actions.destroy');

            Route::post('/{project}/executive-activities/{executiveActivity}/actions/{action}/assignees', [ExecutiveActivitiesController::class, 'storeAssignee'])->name('executive-activities.actions.assignees.store');
            Route::post('/{project}/executive-activities/{executiveActivity}/actions/{action}/costs', [ExecutiveActivitiesController::class, 'storeCost'])->name('executive-activities.actions.costs.store');

            Route::get('/{project}/executive-activities/financial-summary', [ExecutiveActivitiesController::class, 'getFinancialSummary'])->name('executive-activities.financial-summary');
            Route::get('/{project}/executive-activities/{executiveActivity}/financial-summary', [ExecutiveActivitiesController::class, 'getFinancialSummary'])->name('executive-activities.activity.financial-summary');

            Route::resource('supervising-entities', ProjectSupervisingAuthoritiesController::class);
            Route::get('/authorities', [ProjectSupervisingAuthoritiesController::class, 'getAuthorities'])->name('supervising-entities.authorities');
        });

        // المستندات
        Route::prefix('{project}')->middleware('can:projects.view')->group(function () {
            Route::get('/documents', [ProjectDocumentController::class, 'index'])->name('documents.index');
            Route::post('/documents', [ProjectDocumentController::class, 'store'])->name('documents.store')->middleware('can:projects.edit');
            Route::get('/documents/{document}', [ProjectDocumentController::class, 'show'])->name('documents.show');
            Route::get('/documents/{document}/download', [ProjectDocumentController::class, 'download'])->name('documents.download');
            Route::delete('/documents/{document}', [ProjectDocumentController::class, 'destroy'])->name('documents.destroy')->middleware('can:projects.delete');

            Route::get('/documents-ajax', [ProjectDocumentController::class, 'getDocuments'])->name('documents.ajax');
            Route::post('/documents-upload', [ProjectDocumentController::class, 'uploadDocument'])->name('documents.upload')->middleware('can:projects.edit');
        });

        // إنجازات المشروع - عرض الكل
        Route::middleware(['auth'])->get('projects-achievements', [ProjectAchievementController::class, 'listAll'])->name('projects.achievements.all');
    });

    // ========================================
    // مركز المراجعة والاعتمادات (Approval Center)
    // ========================================
    Route::group(['prefix' => 'approvals', 'as' => 'approvals.', 'middleware' => ['can:approvals.view']], function () {
        Route::get('/', [ApprovalCenterController::class, 'index'])->name('index');
        Route::get('/referrals/{referral}', [ApprovalCenterController::class, 'showReferral'])->name('referral.show');
        Route::post('/referrals/{referral}/respond', [ApprovalCenterController::class, 'respondReferral'])->name('referral.respond');
        Route::get('/{project}', [ApprovalCenterController::class, 'show'])->name('show');
        Route::post('/{project}/approve', [ProjectApprovalController::class, 'approve'])->name('approve')->middleware(['can:approvals.approve', 'require_signature']);
        Route::post('/{project}/reject', [ProjectApprovalController::class, 'reject'])->name('reject')->middleware('can:approvals.reject');
        Route::post('/{project}/request-action', [ProjectApprovalController::class, 'requestAction'])->name('requestAction')->middleware('can:approvals.request-action');
        Route::post('/{project}/resubmit', [ProjectApprovalController::class, 'resubmit'])->name('resubmit');
        Route::post('/{project}/referral', [ProjectApprovalController::class, 'submitReferral'])->name('referral');
    });

    // ========================================
    // طلبات المشاريع (Project Requests)
    // ========================================
    Route::group(['prefix' => 'project-requests', 'as' => 'project-requests.'], function () {
        Route::get('/', [ProjectRequestController::class, 'index'])->name('index')->middleware('can:project-requests.view');
        Route::get('/create', [ProjectRequestController::class, 'create'])->name('create')->middleware('can:project-requests.create');
        Route::post('/', [ProjectRequestController::class, 'store'])->name('store')->middleware('can:project-requests.create');
        Route::get('/{projectRequest}', [ProjectRequestController::class, 'show'])->name('show')->middleware('can:project-requests.view');
        Route::get('/{projectRequest}/edit', [ProjectRequestController::class, 'edit'])->name('edit')->middleware('can:project-requests.edit');
        Route::put('/{projectRequest}', [ProjectRequestController::class, 'update'])->name('update')->middleware('can:project-requests.edit');
        Route::post('/{projectRequest}/submit', [ProjectRequestController::class, 'submit'])->name('submit')->middleware('can:project-requests.edit');
        Route::post('/{projectRequest}/approve', [ProjectRequestController::class, 'approve'])->name('approve')->middleware('can:project-requests.approve');
        Route::post('/{projectRequest}/reject', [ProjectRequestController::class, 'reject'])->name('reject')->middleware('can:project-requests.reject');
        Route::post('/{projectRequest}/transfer', [ProjectRequestController::class, 'transfer'])->name('transfer')->middleware('can:project-requests.transfer');
        Route::delete('/{projectRequest}', [ProjectRequestController::class, 'destroy'])->name('destroy')->middleware('can:project-requests.delete');
    });

    // ========================================
    // الاستشارات والإحالات (Consultations Center)
    // ========================================
    Route::group(['prefix' => 'consultations', 'as' => 'consultations.', 'middleware' => ['can:referrals.view']], function () {
        Route::get('/', [ConsultationCenterController::class, 'index'])->name('index');
        Route::get('/{referral}', [ConsultationCenterController::class, 'show'])->name('show');
        Route::post('/{referral}/respond', [ConsultationCenterController::class, 'respond'])->name('respond');
        Route::post('/{referral}/close', [ConsultationCenterController::class, 'close'])->name('close');
    });

    // ========================================
    // إحالات المشاريع (Legacy Project Referrals - Compatibility)
    // ========================================
    Route::group(['prefix' => 'project-referrals', 'as' => 'project-referrals.'], function () {
        Route::get('/', function () {
            return redirect()->route('consultations.index');
        })->name('index')->middleware('can:referrals.view');

        Route::get('/{referral}', [ConsultationCenterController::class, 'show'])->name('show')->middleware('can:referrals.view');
        Route::post('/{referral}/respond', [ConsultationCenterController::class, 'respond'])->name('respond')->middleware('can:referrals.view');
    });

    // ========================================
    // الإحالات (Referrals)
    // ========================================
    Route::group(['prefix' => 'referrals', 'as' => 'referrals.'], function () {
        Route::get('/', [ReferralController::class, 'index'])->name('index')->middleware('can:referrals.view');
        Route::post('/store', [ReferralController::class, 'store'])->name('store')->middleware('can:referrals.view');
        Route::get('/{topic}', [ReferralController::class, 'show'])->name('show')->middleware('can:referrals.view');
        Route::post('/{topic}/refer', [ReferralController::class, 'refer'])->name('refer')->middleware('can:referrals.view');
        Route::post('/{activity}/respond', [ReferralController::class, 'respond'])->name('respond')->middleware('can:referrals.view');
        Route::get('/{topic}/print-topic', [ReferralController::class, 'printTopic'])->name('print_topic')->middleware('can:referrals.view');
        Route::get('/{activity}/print', [ReferralController::class, 'print'])->name('print')->middleware('can:referrals.view');
        Route::get('/{topic}/export', [ReferralController::class, 'exportExcel'])->name('export')->middleware('can:referrals.view');
    });

    // ========================================
    // المراسلات (Correspondence)
    // ========================================
    Route::group(['prefix' => 'correspondence', 'as' => 'correspondence.'], function () {
        Route::get('/', [CorrespondenceController::class, 'index'])->name('index')->middleware('can:correspondence.view');
        Route::get('/overdue', [CorrespondenceController::class, 'overdue'])->name('overdue')->middleware('can:correspondence.view');
        Route::get('/statistics', [CorrespondenceController::class, 'statistics'])->name('statistics')->middleware('can:correspondence.view');
        Route::get('/deleted', [CorrespondenceController::class, 'deleted'])->name('deleted')->middleware('can:correspondence.view');
        Route::get('/create', [CorrespondenceController::class, 'create'])->name('create')->middleware('can:correspondence.create');
        Route::post('/', [CorrespondenceController::class, 'store'])->name('store')->middleware('can:correspondence.create');
        Route::get('/search', [CorrespondenceController::class, 'advancedSearch'])->name('search')->middleware('can:correspondence.view');
        Route::get('/referrals', [CorrespondenceController::class, 'referrals'])->name('referrals.index')->middleware('can:correspondence.view');
        Route::get('/{id}', [CorrespondenceController::class, 'show'])->name('show')->middleware('can:correspondence.view');
        Route::get('/{id}/edit', [CorrespondenceController::class, 'edit'])->name('edit')->middleware('can:correspondence.edit');
        Route::put('/{id}', [CorrespondenceController::class, 'update'])->name('update')->middleware('can:correspondence.edit');
        Route::delete('/{id}', [CorrespondenceController::class, 'destroy'])->name('destroy')->middleware('can:correspondence.delete');
        Route::post('/{id}/restore', [CorrespondenceController::class, 'restore'])->name('restore')->middleware('can:correspondence.delete');
        Route::post('/{id}/reply', [CorrespondenceController::class, 'storeReply'])->name('reply')->middleware('can:correspondence.reply');
        Route::post('/{id}/referral', [CorrespondenceController::class, 'storeReferral'])->name('referral')->middleware('can:correspondence.referral');
        Route::post('/{id}/forward', [CorrespondenceController::class, 'storeForward'])->name('forward')->middleware('can:correspondence.forward');
        Route::post('/referrals/{referralId}/update-status', [CorrespondenceController::class, 'updateReferralStatus'])->name('referrals.update-status')->middleware('can:correspondence.view');
        Route::post('/{id}/close', [CorrespondenceController::class, 'close'])->name('close')->middleware('can:correspondence.close');
        Route::get('/{id}/download/{type}/{index}', [CorrespondenceController::class, 'downloadAttachment'])->name('download')->middleware('can:correspondence.view');
        Route::get('/{id}/movement-log', [CorrespondenceController::class, 'getMovementLog'])->name('movement-log')->middleware('can:correspondence.view');
        Route::get('/{id}/print', [CorrespondenceController::class, 'print'])->name('print')->middleware('can:correspondence.print');
        Route::get('/{id}/preview', [CorrespondenceController::class, 'preview'])->name('preview')->middleware('can:correspondence.print');
        Route::get('/api/search-projects', [CorrespondenceController::class, 'searchProjects'])->name('search-projects');
        Route::put('/referrals/{referral}/update-status', [CorrespondenceController::class, 'updateReferralStatus'])->name('referral.update-status');
    });

    // ========================================
    // التخطيط (Planning)
    // ========================================
    Route::group(['middleware' => ['auth', 'can:plans.view']], function () {
        Route::get('planning/plans/batch/print', [PlanController::class, 'batchPrint'])->name('plans.batch-print');
        Route::get('planning/plans/batch/comprehensive', [PlanController::class, 'comprehensiveBatchPrint'])->name('plans.batch-print.comprehensive');
        Route::get('planning/plans/{plan}/print', [PlanController::class, 'print'])->name('plans.print');
        Route::get('planning/plans/{plan}/implementation', [PlanController::class, 'implementation'])->name('plans.implementation');
        Route::get('planning/plans/{plan}/implementation-print', [PlanController::class, 'printImplementation'])->name('plans.implementation.print');
        Route::put('planning/plans/{plan}/implementation', [PlanController::class, 'updateImplementation'])->name('plans.implementation.update');
        Route::get('planning/plans/export/excel', [PlanController::class, 'exportExcel'])->name('plans.export');
        Route::get('planning/plans/import/template', [PlanController::class, 'downloadTemplate'])->name('plans.download-template');
        Route::get('planning/plans/import/form', [PlanController::class, 'showImport'])->name('plans.show-import');
        Route::post('planning/plans/import/process', [PlanController::class, 'processImport'])->name('plans.process-import');
        Route::resource('planning/plans', PlanController::class)->names('plans');
    });

    // ========================================
    // الـ Routers الرئيسية والفرعية
    // ========================================
    Route::resource('main-routers', MainRouterController::class);
    Route::resource('sub-routers', SubRouterController::class);

    // ========================================
    // سلاسل القيمة - خطط السلسلة (Value Chains - Chain Plans)
    // ========================================
    Route::middleware('can:value_chains.view')->group(function () {
        Route::prefix('value-chains/chain-plans')->name('chain_plans.')->group(function () {
            Route::get('/batch-print', [ChainPlanController::class, 'batchPrint'])->name('batch_print')->middleware('can:value_chains.view');
            Route::get('/export', [ChainPlanController::class, 'export'])->name('export')->middleware('can:value_chains.export');
            Route::get('/import', [ChainPlanController::class, 'importForm'])->name('import')->middleware('can:value_chains.import');
            Route::get('/import/template', [ChainPlanController::class, 'downloadTemplate'])->name('download_template')->middleware('can:value_chains.import');
            Route::post('/import/preview', [ChainPlanController::class, 'previewImport'])->name('import.preview')->middleware('can:value_chains.import');
            Route::post('/import/process', [ChainPlanController::class, 'processImport'])->name('import.process')->middleware('can:value_chains.import');
            Route::get('/projects-by-chain', [ChainPlanController::class, 'getProjectsByChain'])->name('projects_by_chain');
            Route::get('/activities-by-project', [ChainPlanController::class, 'getActivitiesByProject'])->name('activities_by_project');
            Route::get('/{chainPlan}/print', [ChainPlanController::class, 'print'])->name('print')->middleware('can:value_chains.view');
        });
        Route::resource('value-chains/chain-plans', ChainPlanController::class)->names('chain_plans')->parameters(['chain-plans' => 'chainPlan']);
    });

    // ========================================
    // سلاسل القيمة (Value Chains)
    // ========================================
    Route::middleware('can:value-chains.view')->group(function () {
        Route::resource('value-chains.financing', ValueChainFinancingController::class)->except(['show'])->names([
            'index' => 'value-chains.financing.index',
            'create' => 'value-chains.financing.create',
            'store' => 'value-chains.financing.store',
            'edit' => 'value-chains.financing.edit',
            'update' => 'value-chains.financing.update',
            'destroy' => 'value-chains.financing.destroy',
        ]);
        Route::resource('value-chains.participating-entities', ValueChainParticipatingEntityController::class)->except(['show'])->names([
            'index' => 'value-chains.participating-entities.index',
            'create' => 'value-chains.participating-entities.create',
            'store' => 'value-chains.participating-entities.store',
            'edit' => 'value-chains.participating-entities.edit',
            'update' => 'value-chains.participating-entities.update',
            'destroy' => 'value-chains.participating-entities.destroy',
        ]);
        Route::resource('value-chains', ValueChainController::class);
        Route::resource('global-financings', GlobalFinancingController::class);
    });
    Route::middleware('can:value_chain_members.view')->group(function () {
        Route::resource('value-chain-members', ValueChainMemberController::class);
    });
    Route::middleware('can:value-chain-financing-types.view')->group(function () {
        Route::resource('value-chain-financing-types', ValueChainFinancingTypeController::class);
    });

    // ========================================
    // استيراد سلاسل القيمة
    // ========================================
    Route::middleware('can:value-chains.view')->group(function () {
        Route::get('value-chains/import/form', [ValueChainController::class, 'showImportForm'])->name('value-chains.import.form')->middleware('can:value_chains.import');
        Route::post('value-chains/import/preview', [ValueChainController::class, 'previewImport'])->name('value-chains.import.preview')->middleware('can:value_chains.import');
        Route::post('value-chains/import/process', [ValueChainController::class, 'processImport'])->name('value-chains.import.process')->middleware('can:value_chains.import');
        Route::get('value-chains/import/template', [ValueChainController::class, 'downloadTemplate'])->name('value-chains.import.template')->middleware('can:value_chains.import');
    });

    // ========================================
    // التنفيذ والمزامنة
    // ========================================
    Route::get('/projects-implementation', [ProjectController::class, 'implementationIndex'])->name('projects.implementation.index')->middleware('can:execution.view');
    Route::post('/projects/bulk-sync-to-erp', [ProjectController::class, 'bulkSyncProjectsToErp'])->name('projects.bulk-sync-to-erp')->middleware('can:projects.sync');
    Route::get('/erp/uoms', [ErpUomController::class, 'index']);

    // ========================================
    // المبررات المالية
    // ========================================
    Route::middleware('can:financial-justifications.view')->group(function () {
        Route::get('/financial-justifications', [ExecutionController::class, 'allFinancialJustifications'])->name('financial-justifications.index');
        Route::post('/financial-justifications/update-status', [ExecutionController::class, 'updateStatus'])->name('financial-justifications.update-status');
    });

    // ========================================
    // المذكرات (Memoirs)
    // ========================================
    Route::middleware(['can:memoirs.view'])->group(function () {
        Route::resource('memoirs', MemoirController::class);
        Route::post('/memoirs/{memoir}/prepare-print', [MemoirController::class, 'preparePrint'])->name('memoirs.prepare-print');
        Route::get('/memoirs/{memoir}/print/{token?}', [MemoirController::class, 'print'])->name('memoirs.print');
    });

    // ========================================
    // المهام (Tasks) - العامة
    // ========================================
    Route::middleware('can:task.create')->group(function () {
        Route::get('/tasks/create', [TaskController::class, 'createGlobal'])->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'storeGlobal'])->name('tasks.store');
    });

    Route::middleware('can:task.view')->group(function () {
        Route::get('/tasks', [TaskController::class, 'globalIndex'])->name('tasks.index');
        Route::get('/tasks/general', [TaskController::class, 'generalTasksIndex'])->name('tasks.general');
        Route::get('/tasks/activities', [TaskController::class, 'getActivities'])->name('tasks.activities');
        Route::get('/tasks/procedures', [TaskController::class, 'getProcedures'])->name('tasks.procedures');
        Route::get('/tasks/chain-projects', [TaskController::class, 'getChainProjects'])->name('tasks.chain_projects');
        Route::get('/tasks/chain-project-activities', [TaskController::class, 'getChainProjectActivities'])->name('tasks.chain_project_activities');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    });
    Route::put('/tasks/{task}', [TaskController::class, 'updateGlobal'])->name('tasks.updateGlobal')->middleware('can:task.edit');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroyGlobal'])->name('tasks.destroyGlobal')->middleware('can:task.delete');
    Route::post('/tasks/{task}/stop', [TaskController::class, 'stopGlobalTask'])->name('tasks.stopGlobal')->middleware('can:task.edit');

    // ========================================
    // طباعة المهام
    // ========================================
    Route::middleware('can:tasks.print')->group(function () {
        Route::get('projects/{project}/tasks/print', [TaskController::class, 'printProjectTasksIndex'])->name('projects.tasks.print_index');
        Route::get('projects/{project}/tasks/{task}/print', [TaskController::class, 'print'])->name('projects.tasks.print');
        Route::get('tasks/print', [TaskController::class, 'printGeneralTasksIndex'])->name('tasks.print_index');
        Route::get('tasks/{task}/print', [TaskController::class, 'printGeneral'])->name('tasks.print');
    });

    // ========================================
    // التدخلات (Interventions)
    // ========================================
    Route::middleware(['can:interventions.view'])->group(function () {
        Route::post('interventions/{intervention}/approve', [InterventionController::class, 'approve'])->name('interventions.approve');
        Route::post('interventions/{intervention}/reject', [InterventionController::class, 'reject'])->name('interventions.reject');
        Route::resource('interventions', InterventionController::class);
        Route::get('interventions/get-subdomains/{domainId}', [InterventionController::class, 'getSubdomains'])->name('interventions.getSubdomains');
    });

    // ========================================
    // المشاركات (Participation)
    // ========================================
    Route::middleware(['can:participation.view'])->group(function () {
        Route::get('/participation/active', [ParticipationController::class, 'activeParticipations'])->name('participation.active');
        Route::resource('participation', ParticipationController::class);
        Route::patch('participation/{participant}/patch-update', [ParticipationController::class, 'patchUpdate'])->name('participation.patchUpdate');
    });

    // ========================================
    // البرامج (Programs)
    // ========================================
    Route::middleware(['can:programs.view'])->group(function () {
        Route::post('/programs/{program}/approve', [ProgramController::class, 'approve'])->name('programs.approve');
        Route::post('/programs/{program}/reject', [ProgramController::class, 'reject'])->name('programs.reject');
        Route::get('/programs/active', [ProgramController::class, 'activePrograms'])->name('programs.active');
        Route::resource('programs', ProgramController::class)->except(['show']);
        Route::get('/programs/get-programs', [ProgramController::class, 'getPrograms'])->name('programs.get');
        Route::get('/programs/import', [ProgramController::class, 'showImportForm'])->name('programs.import')->middleware('can:programs.import');
        Route::post('/programs/preview-import', [ProgramController::class, 'previewImport'])->name('programs.preview-import')->middleware('can:programs.import');
        Route::post('/programs/process-import', [ProgramController::class, 'processImport'])->name('programs.process-import')->middleware('can:programs.import');
        Route::delete('/programs/undo-import/{fileName}', [ProgramController::class, 'undoImport'])->name('programs.undo-import')->middleware('can:programs.delete');
        Route::get('/programs/download-template', [ProgramController::class, 'downloadTemplate'])->name('programs.download-template');
        Route::get('/programs/export', [ProgramController::class, 'showExportForm'])->name('programs.export')->middleware('can:programs.export');
        Route::get('/programs/download-export', [ProgramController::class, 'downloadExport'])->name('programs.download-export')->middleware('can:programs.export');
        Route::get('/programs/export-excel', [ProgramController::class, 'exportExcel'])->name('programs.export-excel')->middleware('can:programs.export');
        Route::get('/programs/export/pdf', [ProgramController::class, 'exportPdf'])->name('programs.export.pdf')->middleware('can:programs.export');
    });

    Route::get('/programs/template', function () {
        $file = public_path('templates/programs_import_template.xlsx');

        return file_exists($file) ? response()->download($file, 'programs_import_template.xlsx') : abort(404, 'الملف غير موجود');
    })->name('programs.template');

    // ========================================
    // النطاقات الفرعية (Subdomains)
    // ========================================
    Route::middleware(['can:subdomains.view'])->group(function () {
        Route::post('/subdomains/{subdomain}/approve', [SubdomainController::class, 'approve'])->name('subdomains.approve');
        Route::post('/subdomains/{subdomain}/reject', [SubdomainController::class, 'reject'])->name('subdomains.reject');
        Route::get('/subdomains/active', [SubdomainController::class, 'activeSubdomains'])->name('subdomains.active');
        Route::resource('subdomains', SubdomainController::class)->except(['show']);
        Route::get('/domains/{domain}/subdomains', [SubdomainController::class, 'getSubdomains'])->name('domains.subdomains');
        Route::get('/subdomains/fetch-by-domain/{domainId}', [SubdomainController::class, 'fetchByDomain'])->name('subdomains.fetchByDomain');
    });

    // ========================================
    // المشرفون (Supervisors)
    // ========================================
    Route::middleware(['can:supervisors.view'])->group(function () {
        Route::resource('supervisors', SupervisorController::class)->except(['show']);
        Route::patch('/supervisors/{supervisor}/toggle-status', [SupervisorController::class, 'toggleStatus'])->name('supervisors.toggle-status')->middleware('can:supervisors.edit');
    });

    // ========================================
    // إدارة المواقع (Locations)
    // ========================================
    // المحافظات
    Route::middleware(['can:governorates.view'])->group(function () {
        Route::get('/governorates/active', [GovernorateController::class, 'activeGovernorates'])->name('governorates.active');
        Route::resource('governorates', GovernorateController::class)->except(['show']);
        Route::prefix('governorates')->group(function () {
            Route::get('import', [GovernorateController::class, 'showImportForm'])->name('governorates.import')->middleware('can:governorates.import');
            Route::get('export-excel', [GovernorateController::class, 'exportExcel'])->name('governorates.export-excel')->middleware('can:governorates.export');
            Route::get('download-template', [GovernorateController::class, 'downloadTemplate'])->name('governorates.download-template');
            Route::post('preview-import', [GovernorateController::class, 'previewImport'])->name('governorates.preview-import')->middleware('can:governorates.import');
            Route::post('process-import', [GovernorateController::class, 'processImport'])->name('governorates.process-import')->middleware('can:governorates.import');
            Route::post('undo-import/{fileName}', [GovernorateController::class, 'undoImport'])->name('governorates.undo-import')->middleware('can:governorates.import');
        });
    });

    // المديريات
    Route::middleware(['can:directorates.view'])->group(function () {
        Route::prefix('directorates')->group(function () {
            Route::get('/', [DirectorateController::class, 'index'])->name('directorates.index');
            Route::get('/create', [DirectorateController::class, 'create'])->name('directorates.create')->middleware('can:directorates.create');
            Route::post('/', [DirectorateController::class, 'store'])->name('directorates.store')->middleware('can:directorates.create');
            Route::get('/{directorate}/edit', [DirectorateController::class, 'edit'])->name('directorates.edit')->middleware('can:directorates.edit');
            Route::put('/{directorate}', [DirectorateController::class, 'update'])->name('directorates.update')->middleware('can:directorates.edit');
            Route::delete('/{directorate}', [DirectorateController::class, 'destroy'])->name('directorates.destroy')->middleware('can:directorates.delete');
            Route::patch('/{id}/toggle-status', [DirectorateController::class, 'toggleStatus'])->name('directorates.toggle-status')->middleware('can:directorates.edit');

            Route::get('import', [DirectorateController::class, 'showImportForm'])->name('directorates.import')->middleware('can:directorates.import');
            Route::get('export', [DirectorateController::class, 'showExport'])->name('directorates.export')->middleware('can:directorates.export');
            Route::get('download-template', [DirectorateController::class, 'downloadTemplate'])->name('directorates.download-template');
            Route::post('preview-import', [DirectorateController::class, 'previewImport'])->name('directorates.preview-import')->middleware('can:directorates.import');
            Route::post('process-import', [DirectorateController::class, 'processImport'])->name('directorates.process-import')->middleware('can:directorates.import');

            Route::get('/by-governorate', [DirectorateController::class, 'getByGovernorate'])->name('directorates.by-governorate');
            Route::get('/active', [DirectorateController::class, 'activeDirectorates'])->name('active');
        });
    });

    // المناطق الفرعية
    Route::middleware('can:sub-areas.view')->group(function () {
        Route::prefix('sub-areas')->group(function () {
            Route::get('/', [SubAreaController::class, 'index'])->name('sub-areas.index');
            Route::get('/active', [SubAreaController::class, 'activeSubAreas'])->name('sub-areas.active');
            Route::get('/create', [SubAreaController::class, 'create'])->name('sub-areas.create')->middleware('can:sub-areas.create');
            Route::post('/', [SubAreaController::class, 'store'])->name('sub-areas.store')->middleware('can:sub-areas.create');
            Route::get('/{subArea}/edit', [SubAreaController::class, 'edit'])->name('sub-areas.edit')->middleware('can:sub-areas.edit');
            Route::put('/{subArea}', [SubAreaController::class, 'update'])->name('sub-areas.update')->middleware('can:sub-areas.edit');
            Route::delete('/{subArea}', [SubAreaController::class, 'destroy'])->name('sub-areas.destroy')->middleware('can:sub-areas.delete');

            Route::get('/get-directorates/{governorate_id}', [SubAreaController::class, 'getDirectorates'])->name('sub-areas.get-directorates');

            Route::get('import', [SubAreaController::class, 'showImportForm'])->name('sub-areas.import')->middleware('can:sub-areas.import');
            Route::get('export-excel', [SubAreaController::class, 'exportExcel'])->name('sub-areas.export-excel')->middleware('can:sub-areas.export');
            Route::get('download-template', [SubAreaController::class, 'downloadTemplate'])->name('sub-areas.download-template');
            Route::post('preview-import', [SubAreaController::class, 'previewImport'])->name('sub-areas.preview-import')->middleware('can:sub-areas.import');
            Route::post('process-import', [SubAreaController::class, 'processImport'])->name('sub-areas.process-import')->middleware('can:sub-areas.import');
            Route::get('download-error-report', [SubAreaController::class, 'downloadErrorReport'])->name('sub-areas.download-error-report');
            Route::post('undo-import/{fileName}', [SubAreaController::class, 'undoImport'])->name('sub-areas.undo-import')->middleware('can:sub-areas.import');
        });
    });

    // ========================================
    // API للمواقع والجهات (API Locations & Entities)
    // ========================================
    Route::group([], function () {
        Route::get('/api/entities/{entityId}/users', [TaskController::class, 'getEntityUsers'])->name('api.entities.users');
        Route::get('/get-directorates-by-sub-area/{subAreaId}', [SubAreaController::class, 'getDirectorates']);
        Route::get('/api/directorates/all', [DirectorateController::class, 'getAllDirectorates']);
        Route::get('/api/directorates/{governorateId}', [DirectorateController::class, 'getByGovernorate']);
        Route::get('/api/sub-areas/all', [SubAreaController::class, 'getAllSubAreas']);
        Route::get('/api/sub-areas/{governorateId}/{directorateId}', [SubAreaController::class, 'getByDirectorate']);
        Route::get('/api/villages/all', [VillageController::class, 'getAllVillages']);
        Route::get('/api/villages/{governorateId}/{directorateId}/{subAreaId}', [VillageController::class, 'getBySubArea']);
        Route::get('/api/empowerment-projects/{project}/details', [EmpowermentProjectController::class, 'apiDetails'])->middleware('can:projects.view');
        Route::get('/directorates/{governorate}', [ChainPlanController::class, 'getDirectoratesByGovernorate']);
    });

    // ========================================
    // المستفيدين (Beneficiaries)
    // ========================================
    Route::middleware('can:beneficiaries.view')->group(function () {
        Route::resource('beneficiaries', BeneficiaryController::class);
    });

    // ========================================
    // الجمعيات (Associations)
    // ========================================
    Route::middleware('can:associations.view')->group(function () {
        Route::get('/associations/active', [AssociationController::class, 'activeAssociations'])->name('associations.active');
        Route::resource('associations', AssociationController::class);
    });

    // ========================================
    // أنواع التمويل (Financing Types)
    // ========================================
    Route::middleware('can:financing-types.view')->group(function () {
        Route::get('/financing-types/active', [FinancingTypeController::class, 'activeFinancingTypes'])->name('financing-types.active');
        Route::resource('financing-types', FinancingTypeController::class);
    });

    // ========================================
    // طلب النزول (Request Descend)
    // ========================================
    Route::middleware('can:requests_descend.view')->group(function () {
        Route::resource('requests_descend', RequestDescendController::class);
        Route::post('requests_descend/{id}/send-for-approval', [RequestDescendController::class, 'sendForApproval'])->name('requests_descend.sendForApproval');
        Route::post('requests_descend/{id}/process-approval', [RequestDescendController::class, 'processApproval'])->name('requests_descend.processApproval');
        Route::get('requests_descend/{id}/financial', [RequestDescendController::class, 'financial'])->name('requests_descend.financial');
        Route::post('requests_descend/{id}/financial/status', [RequestDescendController::class, 'updateFinancialStatus'])->name('requests_descend.financial.status');
        Route::post('requests_descend/{id}/financial/member', [RequestDescendController::class, 'storeMember'])->name('requests_descend.financial.member.store');
        Route::put('requests_descend/{id}/financial/member/{memberId}', [RequestDescendController::class, 'updateMember'])->name('requests_descend.financial.member.update');
        Route::delete('requests_descend/{id}/financial/member/{memberId}', [RequestDescendController::class, 'destroyMember'])->name('requests_descend.financial.member.destroy');
    });

    // ========================================
    // المانحين (Donors)
    // ========================================
    Route::middleware('can:donors.view')->group(function () {
        Route::prefix('donors')->name('donors.')->group(function () {
            Route::get('/active', [DonorController::class, 'activeDonors'])->name('active');
            Route::get('/', [DonorController::class, 'index'])->name('index');
            Route::get('/create', [DonorController::class, 'create'])->name('create');
            Route::post('/', [DonorController::class, 'store'])->name('store');
            Route::get('/{donor}/edit', [DonorController::class, 'edit'])->name('edit');
            Route::put('/{donor}', [DonorController::class, 'update'])->name('update');
            Route::patch('/{donor}', [DonorController::class, 'updatePartial'])->name('update.partial');
            Route::delete('/{donor}', [DonorController::class, 'destroy'])->name('destroy');
        });
    });

    // ========================================
    // نماذج التمويل (Financing Forms)
    // ========================================
    Route::middleware('can:financing-forms.view')->group(function () {
        Route::prefix('formfinancing')->name('formfinancing.')->group(function () {
            Route::get('/', [FormFinancingController::class, 'index'])->name('index');
            Route::get('/create', [FormFinancingController::class, 'create'])->name('create');
            Route::post('/', [FormFinancingController::class, 'store'])->name('store');
            Route::get('/{financingForm}/edit', [FormFinancingController::class, 'edit'])->name('edit');
            Route::put('/{financingForm}', [FormFinancingController::class, 'update'])->name('update');
            Route::delete('/{financingForm}', [FormFinancingController::class, 'destroy'])->name('destroy');
        });
    });

    // ========================================
    // الكيانات الممولة (Funded Entities)
    // ========================================
    Route::middleware(['can:funded-entities.view'])->group(function () {
        Route::get('/funded-entities/active', [FundedEntityController::class, 'activeFundedEntities'])->name('funded-entities.active');
        Route::resource('funded-entities', FundedEntityController::class);
    });

    // ========================================
    // الأولويات (Priorities)
    // ========================================
    Route::middleware(['can:priorities.view'])->group(function () {
        Route::resource('priorities', PriorityController::class);
    });

    // ========================================
    // أنواع التقارير (Report Types)
    // ========================================
    Route::middleware(['can:report-types.view'])->group(function () {
        Route::resource('report-types', ReportTypeController::class)->except(['show']);
        Route::get('/report-types-export', [ReportTypeController::class, 'export'])->name('report-types.export')->middleware('can:report-types.export');
        Route::get('/report-types-import', [ReportTypeController::class, 'importForm'])->name('report-types.import-form')->middleware('can:report-types.import');
        Route::post('/report-types-import', [ReportTypeController::class, 'import'])->name('report-types.import')->middleware('can:report-types.import');
        Route::get('/report-types-template', [ReportTypeController::class, 'downloadTemplate'])->name('report-types.download-template')->middleware('can:report-types.import');
    });

    // ========================================
    // نماذج التمويل الفرعية (Sub Financing Forms)
    // ========================================
    Route::middleware(['can:financing-forms.view'])->group(function () {
        Route::prefix('subfinancing-forms')->group(function () {
            Route::get('/', [SubFinancingFormController::class, 'index'])->name('subfinancing-forms.index');
            Route::get('/create', [SubFinancingFormController::class, 'create'])->name('subfinancing-forms.create')->middleware('can:financing-forms.create');
            Route::post('/', [SubFinancingFormController::class, 'store'])->name('subfinancing-forms.store')->middleware('can:financing-forms.create');
            Route::get('/{subFinancingForm}/edit', [SubFinancingFormController::class, 'edit'])->name('subfinancing-forms.edit')->middleware('can:financing-forms.edit');
            Route::put('/{subFinancingForm}', [SubFinancingFormController::class, 'update'])->name('subfinancing-forms.update')->middleware('can:financing-forms.edit');
            Route::delete('/{subFinancingForm}', [SubFinancingFormController::class, 'destroy'])->name('subfinancing-forms.destroy')->middleware('can:financing-forms.delete');
            Route::get('/by-financing-form/{financingFormId}', [SubFinancingFormController::class, 'getByFinancingForm'])->name('subfinancing-forms.by-financing-form');
        });
    });

    // ========================================
    // السلطات (Authorities)
    // ========================================
    Route::middleware(['can:authorities.view'])->group(function () {
        Route::get('/authorities/active', [AuthorityController::class, 'activeAuthorities'])->name('authorities.active');
        Route::get('authorities/download-template', [AuthorityController::class, 'downloadTemplate'])->name('authorities.downloadTemplate');
        Route::get('authorities/download-error-report/{fileName?}', [AuthorityController::class, 'downloadErrorReport'])->name('authorities.download-error-report');
        Route::get('authorities/get-children', [AuthorityController::class, 'getChildren'])->name('authorities.getChildren');
        Route::get('authorities/import/report', [AuthorityController::class, 'importReport'])->name('authorities.importReport');
        Route::get('authorities/import', [AuthorityController::class, 'showImport'])->name('authorities.showImport')->middleware('can:authorities.import');
        Route::post('authorities/import/preview', [AuthorityController::class, 'previewImport'])->name('authorities.previewImport')->middleware('can:authorities.import');
        Route::post('authorities/import', [AuthorityController::class, 'import'])->name('authorities.import')->middleware('can:authorities.import');
        Route::post('authorities/process-import', [AuthorityController::class, 'import'])->name('authorities.process-import')->middleware('can:authorities.import');
        Route::get('authorities/export', [AuthorityController::class, 'showExport'])->name('authorities.showExport')->middleware('can:authorities.export');
        Route::get('authorities/download-export', [AuthorityController::class, 'export'])->name('authorities.download-export')->middleware('can:authorities.export');
        Route::post('authorities/auto-save', [AuthorityController::class, 'autoSave'])->name('authorities.autoSave');
        Route::get('authorities/bulk-edit-page', [AuthorityController::class, 'bulkEditPage'])->name('authorities.bulk-edit-page')->middleware('can:authorities.bulk-edit');
        Route::post('authorities/bulk-save', [AuthorityController::class, 'bulkSave'])->name('authorities.bulk-save')->middleware('can:authorities.bulk-edit');
        Route::post('authorities/bulk-update', [AuthorityController::class, 'bulkUpdate'])->name('authorities.bulk-update')->middleware('can:authorities.bulk-edit');
        Route::delete('authorities/bulk-destroy', [AuthorityController::class, 'bulkDestroy'])->name('authorities.bulk-destroy')->middleware('can:authorities.delete');
        Route::get('authorities/get-directorates/{governorate_id}', [AuthorityController::class, 'getDirectorates'])->name('authorities.get-directorates');
        Route::resource('authorities', AuthorityController::class);
    });

    // ========================================
    // تقرير الصلاحيات (Permissions Report)
    // ========================================
    Route::middleware(['can:permissions-report.view'])->group(function () {
        Route::get('/permissions-report', [PermissionsReportController::class, 'index'])->name('permissions.report');
        Route::post('/permissions-report/auto-register', [PermissionsReportController::class, 'autoRegister'])->name('permissions.auto-register');
    });

    // ========================================
    // الكيانات الداخلية (Internal Entities)
    // ========================================
    Route::middleware(['can:internal-entities.view'])->group(function () {
        Route::prefix('internal-entities')->name('internal-entities.')->group(function () {
            Route::delete('/bulk-destroy', [InternalEntityController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('can:internal-entities.delete');
            Route::get('/', [InternalEntityController::class, 'index'])->name('index');
            Route::get('/active', [InternalEntityController::class, 'activeInternalEntities'])->name('active');
            Route::get('/create', [InternalEntityController::class, 'create'])->name('create')->middleware('can:internal-entities.create');
            Route::post('/', [InternalEntityController::class, 'store'])->name('store')->middleware('can:internal-entities.create');
            Route::get('/{internalEntity}', [InternalEntityController::class, 'show'])->name('show')->middleware('can:internal-entities.view');
            Route::get('/{internalEntity}/edit', [InternalEntityController::class, 'edit'])->name('edit')->middleware('can:internal-entities.edit');
            Route::put('/{internalEntity}', [InternalEntityController::class, 'update'])->name('update')->middleware('can:internal-entities.edit');
            Route::patch('/{internalEntity}/toggle-type', [InternalEntityController::class, 'toggleType'])->name('toggle-type')->middleware('can:internal-entities.edit');
            Route::delete('/{internalEntity}', [InternalEntityController::class, 'destroy'])->name('destroy')->middleware('can:internal-entities.delete');
            Route::get('/hierarchy/tree', [InternalEntityController::class, 'hierarchyTree'])->name('hierarchy-tree');
            Route::get('/export', [InternalEntityController::class, 'export'])->name('export')->middleware('can:internal-entities.export');
            Route::get('/download-template', [InternalEntityController::class, 'downloadTemplate'])->name('download-template');
            Route::post('/preview-import', [InternalEntityController::class, 'previewImport'])->name('preview-import');
            Route::post('/process-import', [InternalEntityController::class, 'processImport'])->name('process-import');
        });
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::patch('entity-stages/{stage}/toggle', [EntityApprovalStageController::class, 'toggleActive'])->name('entity-stages.toggle');
        Route::get('entity-stages/eligible-users', [EntityApprovalStageController::class, 'getEligibleUsers'])->name('entity-stages.eligible-users');
        Route::resource('entity-stages', EntityApprovalStageController::class)
            ->except(['show'])
            ->parameters(['entity-stages' => 'entity']);
    });

    // ========================================
    // مسؤولي الكيانات (Entity Officers)
    // ========================================
    Route::middleware(['can:entity_officers.view'])->group(function () {
        Route::resource('entity-officers', EntityOfficialController::class)->names('entity-officers');
    });

    // ========================================
    // سلطات الكيانات (Entity Authorities)
    // ========================================
    Route::middleware(['can:entity-authorities.view'])->group(function () {
        Route::get('/entity-authorities', [EntityAuthorityController::class, 'index'])->name('entity-authorities.index');
        Route::post('/entity-authorities/update', [EntityAuthorityController::class, 'update'])->name('entity-authorities.update');
    });

    // ========================================
    // القرى - إدارة كاملة
    // ========================================
    Route::middleware('can:villages.view')->group(function () {
        Route::prefix('villages')->group(function () {
            Route::get('/active', [VillageController::class, 'activeVillages'])->name('villages.active');
            Route::get('/import', [VillageController::class, 'showImportForm'])->name('villages.import')->middleware('can:villages.import');
            Route::post('/preview-import', [VillageController::class, 'previewImport'])->name('villages.preview-import')->middleware('can:villages.import');
            Route::post('/process-import', [VillageController::class, 'processImport'])->name('villages.process-import')->middleware('can:villages.import');
            Route::get('/download-template', [VillageController::class, 'downloadTemplate'])->name('villages.download-template');
            Route::get('/download-error-report', [VillageController::class, 'downloadErrorReport'])->name('villages.download-error-report');
            Route::post('/undo-import/{fileName}', [VillageController::class, 'undoImport'])->name('villages.undo-import')->middleware('can:villages.import');
            Route::get('/export', [VillageController::class, 'exportExcel'])->name('villages.export')->middleware('can:villages.export');
            Route::get('/directorates/{governorate_id}', [VillageController::class, 'getDirectorates'])->name('villages.directorates');
            Route::get('/sub-areas/{directorate_id}', [VillageController::class, 'getSubAreas'])->name('villages.sub-areas');
            Route::get('/by-sub-area/{governorateId}/{directorateId}/{subAreaId}', [VillageController::class, 'getBySubArea'])->name('villages.by-sub-area');
        });

        Route::resource('villages', VillageController::class);

        Route::prefix('api')->group(function () {
            Route::prefix('v1')->group(function () {
                Route::get('/villages', [VillageController::class, 'index'])->name('api.villages.index');
                Route::get('/villages/{id}', [VillageController::class, 'show'])->name('api.villages.show');
                Route::get('/villages/by-sub-area/{governorateId}/{directorateId}/{subAreaId}', [VillageController::class, 'getBySubArea'])->name('api.villages.by-sub-area');
                Route::get('/directorates/{governorate_id}/villages', [VillageController::class, 'getByDirectorate'])->name('api.directorates.villages');
            });
        });
    });

    // ========================================
    // الوحدات (Units)
    // ========================================
    Route::middleware('can:units.view')->group(function () {
        Route::post('/units/{unit}/approve', [UnitController::class, 'approve'])->name('units.approve')->middleware('can:units.approve');
        Route::post('/units/{unit}/reject', [UnitController::class, 'reject'])->name('units.reject')->middleware('can:units.reject');
        Route::get('/units/active', [UnitController::class, 'activeUnits'])->name('units.active');
        Route::get('/units/api/fetch', [UnitController::class, 'apiFetch'])->name('units.api.fetch');
        Route::resource('units', UnitController::class);
    });

    // ========================================
    // مجموعات المستفيدين (Beneficiary Groups)
    // ========================================
    Route::middleware('can:beneficiary-groups.view')->group(function () {
        Route::post('/beneficiary-groups/{beneficiaryGroup}/approve', [BeneficiaryGroupController::class, 'approve'])->name('beneficiary-groups.approve')->middleware('can:beneficiary-groups.approve');
        Route::post('/beneficiary-groups/{beneficiaryGroup}/reject', [BeneficiaryGroupController::class, 'reject'])->name('beneficiary-groups.reject')->middleware('can:beneficiary-groups.reject');
        Route::get('/beneficiary-groups/active', [BeneficiaryGroupController::class, 'activeBeneficiaryGroups'])->name('beneficiary-groups.active');
        Route::resource('beneficiary-groups', BeneficiaryGroupController::class);
    });

    // ========================================
    // إدارة المستخدمين والأدوار
    // ========================================
    Route::middleware(['can:users.view'])->group(function () {
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/disable', [UserController::class, 'disable'])->name('users.disable')->middleware('can:users.disable');
        Route::post('/users/{user}/enable', [UserController::class, 'enable'])->name('users.enable')->middleware('can:users.enable');
        Route::get('/users/{user}/activity-log', [UserController::class, 'userActivityLog'])->name('users.activity-log');
        Route::get('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword')->middleware('can:users.reset-password');
        Route::post('/users/{user}/reset-password', [UserController::class, 'updatePassword'])->name('users.updatePassword')->middleware('can:users.reset-password');
    });

    // ========================================
    // سجلات التدقيق (Audit Logs)
    // ========================================
    Route::middleware(['can:audit-logs.view'])->group(function () {
        Route::get('/ui-components', [UIShowcaseController::class, 'index'])->name('admin.ui-showcase');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('admin.audit-logs.export')->middleware('can:audit-logs.export');
        Route::get('/audit-logs/export-excel', [AuditLogController::class, 'exportExcel'])->name('admin.audit-logs.export-excel')->middleware('can:audit-logs.export');
        Route::get('/audit-logs/export-pdf', [AuditLogController::class, 'exportPdf'])->name('admin.audit-logs.export-pdf')->middleware('can:audit-logs.export');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');
    });

    // ========================================
    // سجلات الاستيراد الشاملة (Import Logs)
    // ========================================
    Route::middleware(['can:import-logs.view'])->group(function () {
        Route::get('/import-logs', [ImportLogController::class, 'index'])->name('import-logs.index');
        Route::get('/import-logs/{importLog}', [ImportLogController::class, 'show'])->name('import-logs.show');
        Route::post('/import-logs/{importLog}/rollback', [ImportLogController::class, 'rollback'])->name('import-logs.rollback')->middleware('can:import-logs.rollback');
    });

    // ========================================
    // إدارة الأدوار والصلاحيات
    // ========================================
    Route::middleware(['can:roles-permissions.view'])->group(function () {
        Route::get('/roles-permissions', [RolePermissionController::class, 'index'])->name('roles-permissions.index');
        Route::post('/roles-permissions/toggle', [RolePermissionController::class, 'togglePermission'])->name('roles-permissions.toggle')->middleware('can:roles-permissions.edit');
        Route::post('/roles-permissions/toggle-scope', [RolePermissionController::class, 'toggleScope'])->name('roles-permissions.toggle-scope')->middleware('can:roles-permissions.edit');
        Route::post('/roles-permissions/update-granular-scope', [RolePermissionController::class, 'updateGranularScope'])->name('roles-permissions.update-granular-scope')->middleware('can:roles-permissions.edit');
        Route::post('/roles-permissions/get-scopes-by-type', [RolePermissionController::class, 'getScopesByType'])->name('roles-permissions.get-scopes-by-type')->middleware('can:roles-permissions.view');
        Route::post('/roles-permissions/update-permission-scope', [RolePermissionController::class, 'updateGranularScope'])->name('roles-permissions.update-permission-scope')->middleware('can:roles-permissions.edit');
        Route::post('/roles/update-permissions', [RoleController::class, 'updatePermissions'])->name('roles.update-permissions');
        Route::get('/roles/matrix-module-rows', [RoleController::class, 'getMatrixModuleRows'])->name('roles.matrix-module-rows');
        Route::post('/admin/roles/{roleId}/update-scope', [RoleController::class, 'updateScope'])->name('roles.update-scope');
        Route::get('/roles/{role}/permissions', [RoleController::class, 'getRolePermissions'])->name('roles.permissions');
        Route::resource('roles', RoleController::class);
        Route::patch('/roles/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle')->middleware('can:roles-permissions.edit');
    });

    // ========================================
    // الكيانات (Entities)
    // ========================================
    Route::middleware(['can:configuration.view'])->group(function () {
        Route::get('/entities', [EntityController::class, 'index'])->name('entities.index');
    });

    // ========================================
    // أنواع الكيانات (Type Entities)
    // ========================================
    Route::middleware('can:configuration.view')->prefix('type-entity')->group(function () {
        Route::get('/', [TypeEntityController::class, 'index'])->name('type-entity.index');
        Route::get('/create', [TypeEntityController::class, 'create'])->name('type-entity.create')->middleware('can:configuration.create');
        Route::post('/store', [TypeEntityController::class, 'store'])->name('type-entity.store')->middleware('can:configuration.create');
        Route::get('/edit/{id}', [TypeEntityController::class, 'edit'])->name('type-entity.edit')->middleware('can:configuration.edit');
        Route::put('/update/{id}', [TypeEntityController::class, 'update'])->name('type-entity.update')->middleware('can:configuration.edit');
        Route::delete('/delete/{id}', [TypeEntityController::class, 'destroy'])->name('type-entity.destroy')->middleware('can:configuration.delete');
    });

    // ========================================
    // عناصر التمويل (Financial Items)
    // ========================================
    Route::middleware(['can:financial-items.view'])->group(function () {
        Route::post('/financial-items/{financialItem}/approve', [FinancialItemController::class, 'approve'])->name('financial-items.approve')->middleware('can:financial-items.approve');
        Route::post('/financial-items/{financialItem}/reject', [FinancialItemController::class, 'reject'])->name('financial-items.reject')->middleware('can:financial-items.reject');
        Route::get('/financial-items/active', [FinancialItemController::class, 'activeFinancialItems'])->name('financial-items.active');
        Route::resource('financial-items', FinancialItemController::class);
        Route::patch('/financial-items/{financialItem}/toggle-status', [FinancialItemController::class, 'toggleStatus'])->name('financial-items.toggle-status')->middleware('can:financial-items.edit');
    });

    Route::middleware(['can:configuration.view'])->group(function () {
        Route::get('/target-categories/active', [TargetCategoryController::class, 'activeTargetCategories'])->name('target-categories.active');
        Route::resource('target-categories', TargetCategoryController::class);
        Route::get('/funding-sources/active', [FundingSourceController::class, 'activeFundingSources'])->name('funding-sources.active');
        Route::resource('funding-sources', FundingSourceController::class);

        // التوقيعات
        Route::get('api/signatures/active', [PrintableSignatureController::class, 'getActiveSignatures'])->name('signatures.active');
        Route::resource('signatures', PrintableSignatureController::class);

        // مركز الاستيراد/التصدير
        Route::prefix('config')->group(function () {
            Route::get('/import-export', [ConfigImportExportController::class, 'index'])->name('config.import-export');
            Route::get('{entity}/import', [ConfigImportExportController::class, 'showImport'])->name('config.import');
            Route::get('{entity}/export-excel', [ConfigImportExportController::class, 'export'])->name('config.export');
            Route::get('{entity}/download-template', [ConfigImportExportController::class, 'downloadTemplate'])->name('config.download-template');
            Route::post('{entity}/preview-import', [ConfigImportExportController::class, 'previewImport'])->name('config.preview-import');
            Route::post('{entity}/process-import', [ConfigImportExportController::class, 'processImport'])->name('config.process-import');
        });
    });

    // ========================================
    // ميزات إضافية
    // ========================================
    Route::get('/entities/get-geo', [EntityController::class, 'getGeo'])->name('entities.get-geo');
    Route::get('/directorates/by-governorate/{id}', [ValueChainMemberController::class, 'getDirectorates'])->name('directorates.byGovernorate');

    // ========================================
    // أداء النظام - Debug
    // ========================================
    Route::get('/debug-perf-measure', function () {
        $user = User::first();
        if (! $user) {
            return response()->json(['error' => 'No users found in database.']);
        }
        Auth::login($user);

        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            app(HomeController::class)->index();
            $dashboardQueries = DB::getQueryLog();
        } catch (Throwable $e) {
            $dashboardQueries = [['error' => $e->getMessage()]];
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            app(ActivityAssignmentController::class)->myAssignments();
            $assignmentsQueries = DB::getQueryLog();
        } catch (Throwable $e) {
            $assignmentsQueries = [['error' => $e->getMessage()]];
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            request()->merge(['per_page' => 15]);
            app(UserController::class)->index(request());
            $usersQueries = DB::getQueryLog();
        } catch (Throwable $e) {
            $usersQueries = [['error' => $e->getMessage()]];
        }

        return response()->json([
            'user' => $user->name.' (ID: '.$user->id.')',
            'dashboard' => [
                'count' => count($dashboardQueries),
                'queries' => array_map(fn ($q) => $q['query'] ?? $q, $dashboardQueries),
            ],
            'my-assignments' => [
                'count' => count($assignmentsQueries),
                'queries' => array_map(fn ($q) => $q['query'] ?? $q, $assignmentsQueries),
            ],
            'users' => [
                'count' => count($usersQueries),
                'queries' => array_map(fn ($q) => $q['query'] ?? $q, $usersQueries),
            ],
        ]);
    });
});
Route::middleware(['auth'])->get('projects-achievements', [ProjectAchievementController::class, 'listAll'])->name('projects.achievements.all');
Route::get('/execution-tracking', [ExecutionController::class, 'trackingIndex'])->name('execution.tracking')->middleware('can:execution.view');

// Routes for AJAX requests
Route::get('/directorates/by-governorate', [UserController::class, 'getDirectoratesByGovernorate'])
    ->name('directorates.by-governorate');

Route::get('/entities/get-geo', [UserController::class, 'getEntityGeographicScope'])
    ->name('entities.get-geo');
Route::post(
    '/projects/{project}/duplicate',
    [ProjectController::class, 'duplicate']
)->name('projects.duplicate')->middleware('can:projects.create');
Route::get('/directorates', [DirectorateController::class, 'index'])->name('directorates.index');
// مسار إنجازات المشاريع
Route::get('/projects/{project}/achievements', [ProjectAchievementController::class, 'index'])->name('projects.achievements.index')->middleware('can:projects.view');
Route::get('/projects/{project}/achievements/print', [ProjectAchievementController::class, 'print'])->name('projects.achievements.print')->middleware('can:projects.print');
Route::get('/projects/{project}/achievements/{achievement}', [ProjectAchievementController::class, 'show'])->name('projects.achievements.show')->middleware('can:projects.view');
Route::get('/projects/{project}/achievements/{achievement}/print', [ProjectAchievementController::class, 'printSingle'])->name('projects.achievements.print-single')->middleware('can:projects.print');

Route::post('/switch-scope', function (Request $request) {
    session(['selected_administrative_scope_id' => $request->scope_id]);
    // اختياري: مسح الكاش الخاص بالنطاق السابق
    Cache::forget('projects_hijri_years_'.$request->old_scope);

    return back();
});

Route::post('/log-js-error', function (Request $request) {
    Log::error('JS Error: '.$request->input('message').' at '.$request->input('url').':'.$request->input('line'));

    return response()->json(['status' => 'logged']);
});
Route::delete('/authorities/bulk-destroy',
    [AuthorityController::class, 'bulkDestroy']
)->name('authorities.bulk.destroy');
Route::get('authorities/import/template',
    [AuthorityController::class, 'downloadTemplate']
)->name('authorities.import.template');
Route::get('/authorities/export',
    [AuthorityController::class, 'export']
)->name('authorities.export');

// Add bulk delete route
Route::delete('internal-entities/bulk', [InternalEntityController::class, 'bulkDestroy'])
    ->name('internal-entities.bulk.destroy');

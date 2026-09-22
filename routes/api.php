<?php

use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CompletedProjectController;
use App\Http\Controllers\Api\ErpUomController;
use App\Http\Controllers\Api\FrappeDataController;
use App\Http\Controllers\Api\ProjectImplementingAgencyController;
use App\Http\Controllers\Api\ProjectSupervisingEntityController;
use App\Http\Controllers\FetchController;
use App\Http\Controllers\Project\ProjectApprovalController;
use App\Http\Controllers\Project\ProjectReferralController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned to the "api" middleware group.
|
*/

// Authenticated user
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Frappe Project Draft API Routes
 */
// Public API Information (no auth required)
Route::get('frappe-project', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Frappe Project API',
        'version' => '1.0',
        'authentication' => 'All endpoints require Sanctum authentication',
        'endpoints' => [
            'POST /api/frappe-project/save' => 'Create a new project draft',
            'PUT /api/frappe-project/update/{id}' => 'Update an existing draft',
            'GET /api/frappe-project/my-drafts' => 'Get all user drafts',
            'GET /api/frappe-project/{id}' => 'Get specific draft',
            'POST /api/frappe-project/submit/{id}' => 'Submit project to implementation and sync with Frappe',
            'DELETE /api/frappe-project/delete/{id}' => 'Delete a draft',
            'GET /api/frappe-project/validate/{id}' => 'Validate draft completeness',
        ],
    ]);
})->name('frappe.project.index');

Route::prefix('frappe-project')->middleware(['auth:sanctum', 'can:projects.create'])->group(function () {
    // إنشاء مسودة
    Route::post('save', [FrappeDataController::class, 'saveDraft'])->name('frappe.project.draft.save');

    // تحديث مسودة (PUT/POST)
    Route::put('update/{projectId}', [FrappeDataController::class, 'updateDraft'])->name('frappe.project.draft.update');
    Route::post('update/{projectId}', [FrappeDataController::class, 'updateDraft']); // دعم POST أيضاً

    // إرسال المشروع للاعتماد وبدء التنفيذ والمزامنة مع Frappe
    Route::post('submit/{projectId}', [FrappeDataController::class, 'submitToImplementation'])
        ->name('frappe.project.submit');

    // جلب مسودة واحدة
    Route::get('{projectId}', [FrappeDataController::class, 'getDraft'])->name('frappe.project.draft.get');

    // جلب جميع المسودات للمستخدم الحالي
    Route::get('drafts', [FrappeDataController::class, 'getUserDrafts'])->name('frappe.project.drafts.user');

    // حذف مسودة
    Route::delete('delete/{projectId}', [FrappeDataController::class, 'deleteDraft'])->name('frappe.project.draft.delete');

    // التحقق من جاهزية المشروع للاعتماد
    Route::get('{projectId}/validate', [FrappeDataController::class, 'validateDraft'])->name('frappe.project.draft.validate');
});

Route::middleware(['auth:sanctum', 'can:configuration.view'])->group(function () {
    /**
     * Project Supervising Entities API Routes
     */
    Route::apiResource('project-supervising-entities', ProjectSupervisingEntityController::class);
    Route::post('project-supervising-entities/update-sequence', [ProjectSupervisingEntityController::class, 'updateSequence']);

    /**
     * Project Implementing Agencies API Routes
     */
    Route::apiResource('project-implementing-agencies', ProjectImplementingAgencyController::class);
    Route::post('project-implementing-agencies/update-sequence', [ProjectImplementingAgencyController::class, 'updateSequence']);
});

/**
 * Locations API Routes (Governorates, Directorates, Sub-areas, Villages)
 */
Route::prefix('locations')->group(function () {
    Route::get('directorates/all', [FetchController::class, 'getAllDirectorates']);
    Route::get('directorates/{governorate_id}', [FetchController::class, 'getDirectoratesByGovernorate']);

    Route::get('sub-areas/all', [FetchController::class, 'getAllSubAreas']);
    Route::get('sub-areas/{governorate_id}/{directorate_id}', [FetchController::class, 'getSubAreasByDirectorate']);

    Route::get('villages/all', [FetchController::class, 'getAllVillages']);
    Route::get('villages/{governorate_id}/{directorate_id}/{sub_area_id}', [FetchController::class, 'getVillagesBySubArea']);
});

/**
 * Completed Projects API Routes
 */
Route::prefix('completed-projects')->middleware(['auth:sanctum', 'can:projects.view'])->group(function () {
    Route::get('/', [CompletedProjectController::class, 'index']);
    Route::get('{project}', [CompletedProjectController::class, 'show']);
    Route::post('/', [CompletedProjectController::class, 'store'])->middleware('can:projects.create');
});

/**
 * ERPNext UOMs
 */
Route::get('/erp/uoms', [ErpUomController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Frappe Project API Routes
|--------------------------------------------------------------------------
|
| Routes API لإدارة المشاريع المسودة والمعتمدة عبر FrappeDataController.
| جميع الروتس تستخدم Middleware auth:sanctum للمصادقة.
|
*/

Route::middleware(['auth:sanctum', 'can:projects.create'])->prefix('frappe-project')->group(function () {

    // إنشاء مشروع مسودة
    Route::post('save', [FrappeDataController::class, 'saveDraft']);

    // تحديث مشروع مسودة (PUT أو POST)
    Route::match(['put', 'post'], 'update/{projectId}', [FrappeDataController::class, 'updateDraft'])->middleware('can:projects.edit');

    // جلب بيانات مشروع مسودة محدد
    Route::get('{projectId}', [FrappeDataController::class, 'getDraft'])->middleware('can:projects.view');

    // جلب كل المسودات الخاصة بالمستخدم
    Route::get('my-drafts', [FrappeDataController::class, 'getUserDrafts'])->middleware('can:projects.view');

    // جلب البنود المالية من ERPNext (مع مزامنة الجهة المنشئة أولاً)
    Route::get('financial-items', [FrappeDataController::class, 'getFinancialItems'])->name('frappe.financial_items');

    // التحقق من الجهة في ERPNext وجلب البنود المالية — يُستخدم عند إنشاء مشروع (قبل الحفظ)
    Route::get('verify-entity-financial-items', [FrappeDataController::class, 'verifyEntityAndGetFinancialItems'])
        ->name('frappe.verify_entity_financial_items');

    // اعتماد المشروع وإرساله للتنفيذ
    Route::post('submit/{projectId}', [FrappeDataController::class, 'submitToImplementation']);

    // حذف مشروع مسودة
    Route::delete('delete/{projectId}', [FrappeDataController::class, 'deleteDraft'])->middleware('can:projects.delete');

    // التحقق من صحة المشروع قبل الاعتماد
    Route::get('validate/{projectId}', [FrappeDataController::class, 'validateDraft'])->middleware('can:projects.view');
});

/**
 * Project Approval API Routes
 */
Route::prefix('projects')->middleware(['web', 'auth'])->group(function () {
    Route::post('{project}/approve', [ProjectApprovalController::class, 'approveProject'])->name('api.projects.approve')->middleware('can:approvals.approve');
    Route::get('{project}/update-info', [ProjectApprovalController::class, 'getUpdateInfo'])->name('api.projects.update-info')->middleware('can:projects.view');
});

/**
 * Project Referral API Routes
 */
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('projects/{project}/referrals', [ProjectReferralController::class, 'createReferral'])->name('api.projects.referrals.create')->middleware('can:referrals.create');
    Route::post('referrals/{referral}/respond', [ProjectReferralController::class, 'respondToReferral'])->name('api.referrals.respond')->middleware('can:referrals.respond');
    Route::get('projects/{project}/referrals', [ProjectReferralController::class, 'getReferralsForProject'])->name('api.projects.referrals.index')->middleware('can:referrals.view');
});

// Web-authenticated route for departments (supports both web session and API token auth)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('entities/{entity}/departments', [ProjectReferralController::class, 'getDepartmentsInAdministration'])->name('api.entities.departments')->middleware('can:projects.view');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/tasks/organizations', [TaskController::class, 'organizations'])
        ->name('api.tasks.organizations');
});

/**
 * Budgets API Routes - ERPNext Budget DocType
 * Uses FrappeAPIService for ERPNext connectivity
 * Authorization: entity-based company filtering
 */
Route::middleware(['web', 'auth', 'can:projects.view'])->prefix('budgets')->group(function () {
    Route::get('/', [BudgetController::class, 'index'])->name('api.budgets.index');
    Route::get('/{name}', [BudgetController::class, 'show'])->name('api.budgets.show');
});

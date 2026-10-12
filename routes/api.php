<?php

use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CompletedProjectController;
use App\Http\Controllers\Api\ErpNextWebhookController;
use App\Http\Controllers\Api\ErpUomController;
use App\Http\Controllers\Api\FinancialReviewController;
use App\Http\Controllers\Api\FrappeDataController;
use App\Http\Controllers\Api\ProjectImplementingAgencyController;
use App\Http\Controllers\Api\ProjectSupervisingEntityController;
use App\Http\Controllers\FetchController;
use App\Http\Controllers\Project\ProjectApprovalController;
use App\Http\Controllers\Project\ProjectReferralController;
use App\Http\Controllers\TaskController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
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

Route::middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    'auth:sanctum,web',
    'throttle:api',
])->prefix('financial-reviews')->group(function () {
    Route::get('/', [FinancialReviewController::class, 'index'])
        ->middleware('can:financial-review.read');
    Route::get('{project}', [FinancialReviewController::class, 'show'])
        ->middleware('can:financial-review.read');
    Route::patch('{project}', [FinancialReviewController::class, 'update'])
        ->middleware('can:financial-review.update');
    Route::post('{project}/notes', [FinancialReviewController::class, 'addNote'])
        ->middleware('can:financial-review.update');
    Route::post('{project}/approve', [FinancialReviewController::class, 'approve'])
        ->middleware('can:financial-review.approve');
    Route::post('{project}/reject', [FinancialReviewController::class, 'reject'])
        ->middleware('can:financial-review.reject');
    Route::post('{project}/return', [FinancialReviewController::class, 'return'])
        ->middleware('can:financial-review.return');
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

Route::middleware('auth:sanctum')->prefix('frappe-project')->group(function () {

    Route::post('save', [FrappeDataController::class, 'saveDraft'])
        ->middleware('can:projects.create')
        ->name('frappe.project.draft.save');

    Route::put('update/{projectId}', [FrappeDataController::class, 'updateDraft'])
        ->middleware('can:projects.edit')
        ->name('frappe.project.draft.update');
    Route::post('update/{projectId}', [FrappeDataController::class, 'updateDraft'])
        ->middleware('can:projects.edit');

    Route::get('drafts', [FrappeDataController::class, 'getUserDrafts'])
        ->middleware('can:projects.view')
        ->name('frappe.project.drafts.user');
    Route::get('my-drafts', [FrappeDataController::class, 'getUserDrafts'])
        ->middleware('can:projects.view');

    Route::get('financial-items', [FrappeDataController::class, 'getFinancialItems'])
        ->middleware('can:projects.create')
        ->name('frappe.financial_items');

    Route::get('verify-entity-financial-items', [FrappeDataController::class, 'verifyEntityAndGetFinancialItems'])
        ->middleware('can:projects.create')
        ->name('frappe.verify_entity_financial_items');

    Route::post('submit/{projectId}', [FrappeDataController::class, 'submitToImplementation'])
        ->middleware('can:projects.create')
        ->name('frappe.project.submit');

    Route::delete('delete/{projectId}', [FrappeDataController::class, 'deleteDraft'])
        ->middleware('can:projects.delete')
        ->name('frappe.project.draft.delete');

    Route::get('validate/{projectId}', [FrappeDataController::class, 'validateDraft'])
        ->middleware('can:projects.view');
    Route::get('{projectId}/validate', [FrappeDataController::class, 'validateDraft'])
        ->middleware('can:projects.view')
        ->name('frappe.project.draft.validate');
    Route::get('{projectId}', [FrappeDataController::class, 'getDraft'])
        ->middleware('can:projects.view')
        ->name('frappe.project.draft.get');
});

/**
 * ERPNext Webhook
 */
Route::post('webhooks/erpnext', [ErpNextWebhookController::class, 'handle'])
    ->name('api.webhooks.erpnext');

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
    Route::get('/tasks/users-by-scope', [TaskController::class, 'getUsersByScope'])
        ->name('api.tasks.users-by-scope')
        ->middleware('can:task.assign');
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

<?php

namespace Tests\Feature;

use App\Enums\EntityResponsibilityType;
use App\Enums\ProjectStatus;
use App\Enums\ReturnTarget;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApprovalCenterTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected InternalEntity $childEntity;

    protected InternalEntity $parentEntity;

    protected InternalEntity $otherEntity;

    protected User $childCreatorUser;

    protected User $childReviewerUser;

    protected User $parentUser;

    protected User $otherUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('signatures/test_signature.png', 'fake_signature_binary_data');

        $this->approvalService = app(ApprovalService::class);

        PermissionResolver::clearCache();
        Cache::flush();
        $this->seed(PermissionsSeeder::class);

        $permissionSlugs = [
            'main_modules.projects',
            'main_modules.correspondence',
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            'approvals.request-action',
            'projects.submit',
            'projects.create',
            'projects.edit',
            'projects.view',
            'projects.view-details',
            'projects.approve',
            'projects.reject',
            'projects.refer',
            'referrals.view',
            'reviews.financial',
            'reviews.technical',
        ];

        foreach ($permissionSlugs as $slug) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $slug,
                    'module' => explode('.', $slug)[0],
                    'type' => 'action',
                ]
            );
        }

        PermissionResolver::clearCache();
        Cache::flush();

        // Only create roles if we are using a real database and role system requires it
        $role = Role::create([
            'name' => 'RegularRole',
            'is_active' => true,
            'full_access' => true,
        ]);

        $permissions = Permission::all();
        foreach ($permissions as $perm) {
            RolePermission::firstOrCreate([
                'role_id' => $role->id,
                'permission_id' => $perm->id,
            ]);
        }
        User::incrementRolePermissionsVersion($role->id);

        $this->parentEntity = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'الاتحاد العام (Parent)',
            'type' => 'union',
            'is_active' => true,
        ]);

        $this->childEntity = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'الجمعية المحلية (Child)',
            'type' => 'assembly',
            'parent_id' => $this->parentEntity->id,
            'is_active' => true,
        ]);

        $this->otherEntity = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'جمعية أخرى (Other)',
            'type' => 'assembly',
            'parent_id' => $this->parentEntity->id,
            'is_active' => true,
        ]);

        $this->childCreatorUser = User::withoutGlobalScopes()->create([
            'name' => 'منشئ المشروع بالجمعية',
            'username' => 'creator_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'creator_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->childEntity->id,
            'role_id' => $role->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
        ]);

        $this->childReviewerUser = User::withoutGlobalScopes()->create([
            'name' => 'مراجع الجمعية',
            'username' => 'reviewer_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'reviewer_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->childEntity->id,
            'role_id' => $role->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
        ]);

        $this->parentUser = User::withoutGlobalScopes()->create([
            'name' => 'مستخدم الاتحاد',
            'username' => 'parent_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'parent_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->parentEntity->id,
            'role_id' => $role->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
        ]);

        $this->otherUser = User::withoutGlobalScopes()->create([
            'name' => 'مستخدم آخر',
            'username' => 'other_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'other_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->otherEntity->id,
            'role_id' => $role->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
        ]);

        $this->adminUser = User::withoutGlobalScopes()->create([
            'name' => 'المدير العام',
            'username' => 'admin_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'admin_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
            'user_type' => 'admin',
        ]);

        // Entity Approval Stages Configuration
        foreach ([
            EntityResponsibilityType::TechnicalReview,
            EntityResponsibilityType::FinancialReview,
            EntityResponsibilityType::Approval,
        ] as $type) {
            EntityApprovalStage::create([
                'entity_id' => $this->childEntity->id,
                'stage' => $type->value,
                'stage_order' => $type->stageOrder(),
                'responsible_user_id' => $this->childReviewerUser->id,
            ]);

            EntityApprovalStage::create([
                'entity_id' => $this->parentEntity->id,
                'stage' => $type->value,
                'stage_order' => $type->stageOrder(),
                'responsible_user_id' => $this->parentUser->id,
            ]);
        }
    }

    protected function createProject(string $status = 'draft'): Project
    {
        return Project::withoutGlobalScopes()->create([
            'project_name' => 'مشروع مركز الاعتمادات التجريبي',
            'form_number' => 'PRJ-TEST-'.rand(100, 999),
            'status' => $status,
            'creator_entity_id' => $this->childEntity->id,
            'internal_entity_id' => $this->childEntity->id,
            'created_by_user_id' => $this->childCreatorUser->id,
            'created_by' => $this->childCreatorUser->id,
        ]);
    }

    // =========================================================================
    // 1. ACCESS TESTS
    // =========================================================================

    public function test_guest_is_redirected_from_approval_center(): void
    {
        $response = $this->get(route('approvals.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_access_approval_center_index(): void
    {
        $response = $this->actingAs($this->childReviewerUser)->get(route('approvals.index'));
        $response->assertStatus(200);
        $response->assertSee('مركز المراجعة والاعتمادات');
        $response->assertSee('لا توجد مهام حالياً'); // Since there are no projects in this test
    }

    // =========================================================================
    // 2. INDEX TABS & DYNAMIC COUNTS
    // =========================================================================

    public function test_index_displays_dynamic_counts_for_user_actionable_tasks(): void
    {
        // Project 1: Pending with Child Entity active step
        $project1 = $this->createProject('draft');
        $this->approvalService->closeDraftAndGenerateApprovalChain($project1, $this->childCreatorUser);

        // Project 2: In execution / Completed
        $project2 = $this->createProject('in_execution');

        // Check view for child reviewer user (Step 1 is active on Child Entity)
        $response = $this->actingAs($this->childReviewerUser)->get(route('approvals.index'));
        $response->assertStatus(200);
        $response->assertSee($project1->form_number);

        // For parent user: Project 1 step 1 is with child entity, so parent user cannot act on it and does NOT see it in Approval Center
        $parentResponse = $this->actingAs($this->parentUser)->get(route('approvals.index'));
        $parentResponse->assertStatus(200);
        $parentResponse->assertDontSee($project1->form_number);

        // Regular user does NOT see completed projects in Approval Center
        $response->assertDontSee($project2->form_number);
    }

    // =========================================================================
    // 3. SHOW PAGE & DYNAMIC TRACKER
    // =========================================================================

    public function test_approval_show_displays_project_details_dynamic_tracker_and_history(): void
    {
        $project = $this->createProject('draft');
        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childCreatorUser);

        $response = $this->actingAs($this->childReviewerUser)->get(route('approvals.show', $project));
        $response->assertStatus(200);
        $response->assertSee($project->project_name);
        $response->assertDontSee('مسار وسلسلة الاعتمادات');
        $response->assertSee('المرحلة الحالية:');
        $response->assertSee('لوحة اتخاذ القرار');
        $response->assertSee('الملخص المالي ومصادر التمويل');
        $response->assertSee('سجل الإجراءات والتدقيق');
    }

    public function test_unauthorized_user_cannot_access_approval_show_page(): void
    {
        $project = $this->createProject('draft');
        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childCreatorUser);

        // Parent user attempts to view project approval page while Step 1 is active on Child entity
        $response = $this->actingAs($this->parentUser)->get(route('approvals.show', $project));
        $response->assertStatus(403);
    }

    // =========================================================================
    // 4. ACTION ENDPOINTS (APPROVE, REJECT, REQUEST COMPLETION, RESUBMIT)
    // =========================================================================

    public function test_approve_action_advances_step_via_approval_center_route(): void
    {
        $project = $this->createProject('draft');
        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childCreatorUser);

        $activeStepBefore = $this->approvalService->getActiveStep($project);
        $this->assertEquals(1, $activeStepBefore->step_order);
        $this->assertEquals($this->childEntity->id, $activeStepBefore->entity_id);

        $response = $this->actingAs($this->childReviewerUser)->post(route('approvals.approve', $project), [
            'notes' => 'تمت مراجعة المرحلة الأولى واعتمادها',
        ]);

        $response->assertRedirect();
        $project->refresh();

        $activeStepAfter = $this->approvalService->getActiveStep($project);
        $this->assertEquals(2, $activeStepAfter->step_order);
    }

    public function test_reject_action_rejects_project_with_mandatory_reason(): void
    {
        $project = $this->createProject('draft');
        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childCreatorUser);

        $response = $this->actingAs($this->childReviewerUser)->post(route('approvals.reject', $project), [
            'reason' => 'المشروع غير متوافق مع الموازنة التقديرية المعتمدة',
        ]);

        $response->assertRedirect();
        $project->refresh();
        $this->assertEquals(ProjectStatus::Rejected->value, $project->status);
    }

    public function test_request_completion_action_rolls_back_project_for_review(): void
    {
        $this->withoutExceptionHandling();
        $project = $this->createProject('draft');
        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childCreatorUser);

        $response = $this->actingAs($this->childReviewerUser)->post(route('approvals.requestAction', $project), [
            'return_target' => 'creator_entity',
            'reason' => 'يرجى تزويدنا بالمستندات الناقصة ودراسة الأثر التفصيلية',
        ]);

        if ($response->status() !== 302) {

        }

        $response->assertRedirect();
        $project->refresh();
        $this->assertEquals('rolled_back_for_review', $project->status);
    }

    public function test_resubmit_action_restores_project_to_active_approval(): void
    {
        $this->withoutExceptionHandling();
        $project = $this->createProject('draft');
        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childCreatorUser);
        $this->approvalService->requestCompletion(
            $project,
            $this->childReviewerUser,
            'استكمال المستندات',
            ReturnTarget::CreatorEntity
        );

        $this->assertEquals('rolled_back_for_review', $project->fresh()->status);

        $response = $this->actingAs($this->childCreatorUser)->post(route('approvals.resubmit', $project), [
            'notes' => 'تم إرفاق جميع المستندات المطلوبة وتعديل البيانات بنجاح',
        ]);

        if ($response->status() !== 302) {

        }

        $response->assertRedirect();
        $project->refresh();
        $this->assertEquals(ProjectStatus::PendingApproval->value, $project->status);
    }

    // =========================================================================
    // 5. SEPARATION VERIFICATION
    // =========================================================================

    public function test_projects_show_does_not_contain_active_decision_board(): void
    {
        $project = $this->createProject('draft');
        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childCreatorUser);

        $response = $this->actingAs($this->childReviewerUser)->get(route('projects.show', $project));
        $response->assertStatus(200);

        // Must NOT contain approval decision form buttons or inputs
        $response->assertDontSee('approvalForm');
        $response->assertDontSee('masterSubmitBtn');
        $response->assertDontSee('decision-board');
        $response->assertSee('مركز المراجعة والاعتمادات');
    }
}

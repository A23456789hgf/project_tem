<?php

namespace Tests\Feature;

use App\Enums\ApprovalPhase;
use App\Enums\ProjectStatus;
use App\Enums\ReturnTarget;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectApproval;
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

class ApprovalInboxTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected InternalEntity $creatorEntity;

    protected InternalEntity $reviewEntity;

    protected InternalEntity $otherEntity;

    protected User $creatorUser;

    protected User $assignedReviewer;

    protected User $unassignedSameEntityUser;

    protected User $otherEntityUser;

    protected User $adminUser;

    protected Role $reviewRole;

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
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            'approvals.request-action',
            'approvals.technical-review',
            'approvals.financial-review',
            'projects.submit',
            'projects.create',
            'projects.edit',
            'projects.view',
            'projects.view-details',
            'projects.approve',
            'projects.reject',
            'reviews.technical',
            'reviews.financial',
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

        $this->reviewRole = Role::create([
            'name' => 'ReviewerRole',
            'is_active' => true,
            'full_access' => false,
        ]);

        foreach (Permission::all() as $perm) {
            RolePermission::firstOrCreate([
                'role_id' => $this->reviewRole->id,
                'permission_id' => $perm->id,
            ]);
        }
        User::incrementRolePermissionsVersion($this->reviewRole->id);

        $this->creatorEntity = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'Creator Entity',
            'type' => 'assembly',
            'is_active' => true,
        ]);

        $this->reviewEntity = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'Review Entity',
            'type' => 'assembly',
            'is_active' => true,
        ]);

        $this->otherEntity = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'Other Entity',
            'type' => 'assembly',
            'is_active' => true,
        ]);

        $this->creatorUser = User::withoutGlobalScopes()->create([
            'name' => 'Creator User',
            'username' => 'creator_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'creator_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->creatorEntity->id,
            'role_id' => $this->reviewRole->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
            'is_active' => true,
        ]);

        $this->assignedReviewer = User::withoutGlobalScopes()->create([
            'name' => 'Assigned Reviewer',
            'username' => 'reviewer_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'reviewer_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->reviewEntity->id,
            'role_id' => $this->reviewRole->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
            'is_active' => true,
        ]);

        $this->unassignedSameEntityUser = User::withoutGlobalScopes()->create([
            'name' => 'Same Entity Colleague',
            'username' => 'colleague_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'colleague_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->reviewEntity->id,
            'role_id' => $this->reviewRole->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
            'is_active' => true,
        ]);

        $this->otherEntityUser = User::withoutGlobalScopes()->create([
            'name' => 'Unrelated User',
            'username' => 'unrel_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'unrel_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->otherEntity->id,
            'role_id' => $this->reviewRole->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
            'is_active' => true,
        ]);

        $adminRole = Role::create([
            'name' => 'Admin',
            'is_active' => true,
            'full_access' => true,
        ]);

        $this->adminUser = User::withoutGlobalScopes()->create([
            'name' => 'Admin User',
            'username' => 'admin_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'admin_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => null,
            'role_id' => $adminRole->id,
            'signature' => 'signatures/test_signature.png',
            'signature_path' => 'signatures/test_signature.png',
            'is_active' => true,
            'user_type' => 'admin',
        ]);
    }

    protected function createProjectWithAssignedTechnicalReview(): array
    {
        $project = Project::withoutGlobalScopes()->create([
            'project_name' => 'Inbox Test Project',
            'form_number' => 'PRJ-TEST-'.rand(1000, 9999),
            'creator_entity_id' => $this->creatorEntity->id,
            'internal_entity_id' => $this->creatorEntity->id,
            'created_by_user_id' => $this->creatorUser->id,
            'created_by' => $this->creatorUser->id,
            'status' => ProjectStatus::PendingApproval->value,
            'current_stage' => 'entity_'.$this->reviewEntity->id.'_technical_review',
            'current_stage_order' => 1,
        ]);

        $approval = ProjectApproval::create([
            'project_id' => $project->id,
            'entity_id' => $this->reviewEntity->id,
            'step_order' => 1,
            'status' => 'pending',
            'phase' => ApprovalPhase::TechnicalReview->value,
            'is_active' => true,
            'is_completed' => false,
            'technical_reviewer_id' => $this->assignedReviewer->id,
            'drop' => 'entity_'.$this->reviewEntity->id.'_technical_review',
        ]);

        ProjectApproval::create([
            'project_id' => $project->id,
            'entity_id' => $this->reviewEntity->id,
            'step_order' => 2,
            'status' => 'locked',
            'phase' => ApprovalPhase::StageApproval->value,
            'is_active' => false,
            'is_completed' => false,
            'assigned_user_id' => $this->creatorUser->id,
            'drop' => 'entity_'.$this->reviewEntity->id.'_approval',
        ]);

        return [$project, $approval];
    }

    public function test_assigned_review_user_sees_approval_task_in_inbox(): void
    {
        [$project, $approval] = $this->createProjectWithAssignedTechnicalReview();

        $response = $this->actingAs($this->assignedReviewer)->get(route('approvals.index'));

        $response->assertStatus(200);
        $records = $response->viewData('approvalRecords');
        $this->assertTrue($records->contains('id', $approval->id));
    }

    public function test_unassigned_user_in_same_entity_does_not_see_assigned_task(): void
    {
        [$project, $approval] = $this->createProjectWithAssignedTechnicalReview();

        $response = $this->actingAs($this->unassignedSameEntityUser)->get(route('approvals.index'));

        $response->assertStatus(200);
        $records = $response->viewData('approvalRecords');
        $this->assertFalse($records->contains('id', $approval->id));
    }

    public function test_user_in_different_entity_does_not_see_approval_task(): void
    {
        [$project, $approval] = $this->createProjectWithAssignedTechnicalReview();

        $response = $this->actingAs($this->otherEntityUser)->get(route('approvals.index'));

        $response->assertStatus(200);
        $records = $response->viewData('approvalRecords');
        $this->assertFalse($records->contains('id', $approval->id));
    }

    public function test_admin_user_sees_all_approval_records(): void
    {
        [$project, $approval] = $this->createProjectWithAssignedTechnicalReview();

        $response = $this->actingAs($this->adminUser)->get(route('approvals.index'));

        $response->assertStatus(200);
        $records = $response->viewData('approvalRecords');
        $this->assertTrue($records->contains('id', $approval->id));
    }

    public function test_task_disappears_from_inbox_after_approve_and_record_remains_in_db(): void
    {
        [$project, $approval] = $this->createProjectWithAssignedTechnicalReview();

        // 1. Initially visible
        $resBefore = $this->actingAs($this->assignedReviewer)->get(route('approvals.index'));
        $this->assertTrue($resBefore->viewData('approvalRecords')->contains('id', $approval->id));

        // 2. Approve the step
        $this->approvalService->approveActiveStep($project, $this->assignedReviewer, 'اعتماد فني مكتمل');

        // 3. Disappears from Assigned Reviewer inbox
        $resAfter = $this->actingAs($this->assignedReviewer)->get(route('approvals.index'));
        $this->assertFalse($resAfter->viewData('approvalRecords')->contains('id', $approval->id));

        // 4. Historical integrity: Record still exists in database
        $this->assertDatabaseHas('project_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
            'is_active' => false,
        ]);
    }

    public function test_task_disappears_from_inbox_after_reject_and_record_remains_in_db(): void
    {
        [$project, $approval] = $this->createProjectWithAssignedTechnicalReview();

        // 1. Reject the project
        $this->approvalService->rejectProject($project, $this->assignedReviewer, 'تم رفض المشروع لعدم الجدوى الفنية.');

        // 2. Disappears from Assigned Reviewer inbox
        $resAfter = $this->actingAs($this->assignedReviewer)->get(route('approvals.index'));
        $this->assertFalse($resAfter->viewData('approvalRecords')->contains('id', $approval->id));

        // 3. Historical integrity: Record still exists in database
        $this->assertDatabaseHas('project_approvals', [
            'id' => $approval->id,
            'status' => 'rejected',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Rejected->value,
        ]);
    }

    public function test_task_disappears_from_inbox_after_request_completion_and_record_remains_in_db(): void
    {
        [$project, $approval] = $this->createProjectWithAssignedTechnicalReview();

        // 1. Request completion back to creator entity
        $this->approvalService->requestCompletion(
            $project,
            $this->assignedReviewer,
            'يرجى توضيح المواصفات الفنية المرفقة للمشروع بدقة أكبر.',
            ReturnTarget::CreatorEntity
        );

        // 2. Disappears from Assigned Reviewer inbox
        $resAfter = $this->actingAs($this->assignedReviewer)->get(route('approvals.index'));
        $this->assertFalse($resAfter->viewData('approvalRecords')->contains('id', $approval->id));

        // 3. Historical integrity: Record still exists in database
        $this->assertDatabaseHas('project_approvals', [
            'id' => $approval->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::RolledBackForReview->value,
        ]);
    }

    public function test_task_moves_from_user_a_to_user_b_and_returns_to_user_a_as_a_new_step(): void
    {
        $project = Project::withoutGlobalScopes()->create([
            'project_name' => 'A B A Workflow Project',
            'form_number' => 'PRJ-A-B-A',
            'creator_entity_id' => $this->creatorEntity->id,
            'internal_entity_id' => $this->creatorEntity->id,
            'created_by_user_id' => $this->creatorUser->id,
            'created_by' => $this->creatorUser->id,
            'status' => ProjectStatus::PendingApproval->value,
            'current_stage' => 'user_a_first_step',
            'current_stage_order' => 1,
        ]);

        $firstUserAStep = ProjectApproval::create([
            'project_id' => $project->id,
            'entity_id' => $this->reviewEntity->id,
            'step_order' => 1,
            'status' => 'pending',
            'phase' => ApprovalPhase::TechnicalReview->value,
            'is_active' => true,
            'technical_reviewer_id' => $this->assignedReviewer->id,
            'drop' => 'user_a_first_step',
        ]);

        $userBStep = ProjectApproval::create([
            'project_id' => $project->id,
            'entity_id' => $this->otherEntity->id,
            'step_order' => 2,
            'status' => 'locked',
            'phase' => ApprovalPhase::StageApproval->value,
            'is_active' => false,
            'assigned_user_id' => $this->otherEntityUser->id,
            'drop' => 'user_b_step',
        ]);

        $secondUserAStep = ProjectApproval::create([
            'project_id' => $project->id,
            'entity_id' => $this->reviewEntity->id,
            'step_order' => 3,
            'status' => 'locked',
            'phase' => ApprovalPhase::FinancialReview->value,
            'is_active' => false,
            'financial_reviewer_id' => $this->assignedReviewer->id,
            'drop' => 'user_a_second_step',
        ]);

        $userAInbox = $this->actingAs($this->assignedReviewer)->get(route('approvals.index'));
        $this->assertTrue($userAInbox->viewData('approvalRecords')->contains('id', $firstUserAStep->id));

        $this->approvalService->approveActiveStep($project, $this->assignedReviewer, 'A completed step one');

        $userAAfter = $this->actingAs($this->assignedReviewer)->get(route('approvals.index'));
        $this->assertFalse($userAAfter->viewData('approvalRecords')->contains('project_id', $project->id));
        $this->actingAs($this->assignedReviewer)->get(route('approvals.show', $project))->assertForbidden();

        $userBInbox = $this->actingAs($this->otherEntityUser)->get(route('approvals.index'));
        $this->assertTrue($userBInbox->viewData('approvalRecords')->contains('id', $userBStep->id));

        $this->approvalService->approveActiveStep($project->fresh(), $this->otherEntityUser, 'B completed step two');

        $userAReturnedInbox = $this->actingAs($this->assignedReviewer)->get(route('approvals.index'));
        $this->assertTrue($userAReturnedInbox->viewData('approvalRecords')->contains('id', $secondUserAStep->id));
        $this->assertFalse($userAReturnedInbox->viewData('approvalRecords')->contains('id', $firstUserAStep->id));
    }

    public function test_query_parameters_and_direct_ids_cannot_widen_approval_scope(): void
    {
        [$project, $approval] = $this->createProjectWithAssignedTechnicalReview();

        $requests = [
            ['tab' => 'all'],
            ['tab' => 'completed', 'status' => 'approved'],
            ['entity_id' => $this->reviewEntity->id, 'phase' => ApprovalPhase::TechnicalReview->value],
            ['search' => $project->form_number],
            ['id' => $approval->id, 'scope' => 'all', 'unexpected' => 'true'],
        ];

        foreach ($requests as $parameters) {
            $response = $this->actingAs($this->unassignedSameEntityUser)
                ->get(route('approvals.index', $parameters));

            $response->assertOk();
            $this->assertFalse($response->viewData('approvalRecords')->contains('id', $approval->id));
        }

        $this->actingAs($this->unassignedSameEntityUser)
            ->get(route('approvals.show', $project))
            ->assertForbidden();
    }
}

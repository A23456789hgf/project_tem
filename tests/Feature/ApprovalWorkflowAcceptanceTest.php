<?php

namespace Tests\Feature;

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

class ApprovalWorkflowAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected InternalEntity $entityA;

    protected InternalEntity $entityB;

    protected InternalEntity $ministryRoot;

    protected User $adminUser;

    protected User $userEntityA;

    protected User $userEntityB;

    protected User $userMinistry;

    protected Role $adminRole;

    protected Role $reviewerRole;

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

        $this->adminRole = Role::create([
            'name' => 'Admin',
            'is_active' => true,
            'full_access' => true,
        ]);

        $this->reviewerRole = Role::create([
            'name' => 'Reviewer',
            'is_active' => true,
            'full_access' => false,
        ]);

        foreach (Permission::all() as $perm) {
            RolePermission::firstOrCreate([
                'role_id' => $this->reviewerRole->id,
                'permission_id' => $perm->id,
            ]);
        }

        // 1. Setup Hierarchy: Entity A -> Entity B -> Ministry Root
        $this->ministryRoot = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'وزارة التخطيط (Root)',
            'type' => 'ministry',
            'is_ministry_root' => true,
            'parent_id' => null,
            'is_active' => true,
        ]);

        $this->entityB = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'الإدارة العامة للمشاريع (Entity B)',
            'type' => 'general_directorate',
            'is_ministry_root' => false,
            'parent_id' => $this->ministryRoot->id,
            'is_active' => true,
        ]);

        $this->entityA = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'إدارة التخطيط والدراسات (Entity A)',
            'type' => 'department',
            'is_ministry_root' => false,
            'parent_id' => $this->entityB->id,
            'is_active' => true,
        ]);

        // 2. Setup Users
        $this->adminUser = User::withoutGlobalScopes()->create([
            'name' => 'مدير النظام (Admin)',
            'username' => 'admin_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'admin_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
            'user_type' => 'admin',
            'signature' => 'signatures/test_signature.png',
        ]);

        $this->userEntityA = User::withoutGlobalScopes()->create([
            'name' => 'مراجع الجهة A',
            'username' => 'user_a_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'usera_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->entityA->id,
            'role_id' => $this->reviewerRole->id,
            'is_active' => true,
            'signature' => 'signatures/test_signature.png',
        ]);

        $this->userEntityB = User::withoutGlobalScopes()->create([
            'name' => 'مراجع الجهة B',
            'username' => 'user_b_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'userb_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->entityB->id,
            'role_id' => $this->reviewerRole->id,
            'is_active' => true,
            'signature' => 'signatures/test_signature.png',
        ]);

        $this->userMinistry = User::withoutGlobalScopes()->create([
            'name' => 'معتمد الوزارة',
            'username' => 'user_min_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'usermin_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->ministryRoot->id,
            'role_id' => $this->reviewerRole->id,
            'is_active' => true,
            'signature' => 'signatures/test_signature.png',
        ]);

        // 3. Setup EntityApprovalStages for each entity (3 stages each)
        $this->setupStagesForEntity($this->entityA, $this->userEntityA);
        $this->setupStagesForEntity($this->entityB, $this->userEntityB);
        $this->setupStagesForEntity($this->ministryRoot, $this->userMinistry);
    }

    protected function setupStagesForEntity(InternalEntity $entity, User $responsibleUser): void
    {
        EntityApprovalStage::create([
            'entity_id' => $entity->id,
            'stage' => 'technical_review',
            'order' => 1,
            'is_active' => true,
            'responsible_user_id' => $responsibleUser->id,
        ]);

        EntityApprovalStage::create([
            'entity_id' => $entity->id,
            'stage' => 'financial_review',
            'order' => 2,
            'is_active' => true,
            'responsible_user_id' => $responsibleUser->id,
        ]);

        EntityApprovalStage::create([
            'entity_id' => $entity->id,
            'stage' => 'approval',
            'order' => 3,
            'is_active' => true,
            'responsible_user_id' => $responsibleUser->id,
        ]);
    }

    /**
     * Requirement 1 & 3: Entity & Phase Transitions Test:
     * Entity A technical_review -> approved -> Entity A financial_review -> approved -> Entity A stage_approval -> approved -> Entity B technical_review
     */
    public function test_entity_and_phase_progression_workflow(): void
    {
        // 1. Create project in draft and generate chain
        $project = Project::withoutGlobalScopes()->create([
            'project_name' => 'مشروع اختبار تسلسل الموافقات',
            'form_number' => 'PRJ-FLOW-01',
            'creator_entity_id' => $this->entityA->id,
            'internal_entity_id' => $this->entityA->id,
            'created_by_user_id' => $this->userEntityA->id,
            'created_by' => $this->userEntityA->id,
            'status' => 'draft',
        ]);

        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->userEntityA);

        // --- STEP 1: Entity A / technical_review ---
        $step1 = $this->approvalService->getActiveStep($project->fresh());
        $this->assertNotNull($step1);
        $this->assertEquals(1, $step1->step_order);
        $this->assertEquals($this->entityA->id, $step1->entity_id);
        $this->assertEquals('technical_review', $step1->phase);
        $this->assertTrue($step1->isActive());

        // Verify Blade render at technical_review
        $response1 = $this->actingAs($this->adminUser)->get(route('approvals.show', $project->fresh()));
        $response1->assertOk();
        $response1->assertSee('مراجعة فنية');
        $response1->assertSee('فتح شاشة المراجعة الفنية وتعديل الأوزان');
        $response1->assertSee(route('projects.review.technical', $project));

        // Action: Approve Step 1 by User A
        $result1 = $this->approvalService->approveActiveStep($project->fresh(), $this->userEntityA, 'موافقة فنية من الجهة A');
        $this->assertTrue($result1['success']);
        $this->assertFalse($result1['is_final']);

        // --- STEP 2: Entity A / financial_review ---
        $step2 = $this->approvalService->getActiveStep($project->fresh());
        $this->assertNotNull($step2);
        $this->assertEquals(2, $step2->step_order);
        $this->assertEquals($this->entityA->id, $step2->entity_id, 'Must remain on Entity A');
        $this->assertEquals('financial_review', $step2->phase, 'Phase must be financial_review');
        $this->assertTrue($step2->isActive());

        // Verify Blade render at financial_review
        $response2 = $this->actingAs($this->adminUser)->get(route('approvals.show', $project->fresh()));
        $response2->assertOk();
        $response2->assertSee('المراجعة المالية');
        $response2->assertSee('فتح شاشة المراجعة المالية وتعديل التكاليف');
        $response2->assertSee(route('projects.review.financial', $project));

        // Action: Approve Step 2 by User A
        $result2 = $this->approvalService->approveActiveStep($project->fresh(), $this->userEntityA, 'موافقة مالية من الجهة A');
        $this->assertTrue($result2['success']);
        $this->assertFalse($result2['is_final']);

        // --- STEP 3: Entity A / stage_approval ---
        $step3 = $this->approvalService->getActiveStep($project->fresh());
        $this->assertNotNull($step3);
        $this->assertEquals(3, $step3->step_order);
        $this->assertEquals($this->entityA->id, $step3->entity_id, 'Must remain on Entity A');
        $this->assertEquals('stage_approval', $step3->phase, 'Phase must be stage_approval');
        $this->assertTrue($step3->isActive());

        // Verify Blade render at stage_approval
        $response3 = $this->actingAs($this->adminUser)->get(route('approvals.show', $project->fresh()));
        $response3->assertOk();
        $response3->assertSee('اعتماد المرحلة');
        $response3->assertSee('اعتماد المرحلة لدى الجهة الحالية');
        $response3->assertSee('مراجعة بيانات المشروع الكاملة');

        // Action: Approve Step 3 by User A (End of Entity A)
        $result3 = $this->approvalService->approveActiveStep($project->fresh(), $this->userEntityA, 'اعتماد مرحلة الجهة A');
        $this->assertTrue($result3['success']);
        $this->assertFalse($result3['is_final']);

        // --- STEP 4: Entity B / technical_review ---
        $step4 = $this->approvalService->getActiveStep($project->fresh());
        $this->assertNotNull($step4);
        $this->assertEquals(4, $step4->step_order);
        $this->assertEquals($this->entityB->id, $step4->entity_id, 'Must transition to Entity B');
        $this->assertEquals('technical_review', $step4->phase, 'Must start at technical_review for Entity B');
        $this->assertTrue($step4->isActive());

        // Verify Blade render for Entity B
        $response4 = $this->actingAs($this->adminUser)->get(route('approvals.show', $project->fresh()));
        $response4->assertOk();
        $response4->assertSee($this->entityB->name);
        $response4->assertSee('مراجعة فنية');
        $response4->assertSee('فتح شاشة المراجعة الفنية وتعديل الأوزان');
    }

    /**
     * Requirement 2: Completion Return (PreviousStep) Test:
     * financial_review -> requestCompletion(previous_step) -> returns to technical_review of same entity.
     * stage_approval -> requestCompletion(previous_step) -> returns to financial_review of same entity.
     */
    public function test_completion_return_to_previous_step(): void
    {
        $project = Project::withoutGlobalScopes()->create([
            'project_name' => 'مشروع اختبار استكمال الإجراءات',
            'form_number' => 'PRJ-COMPLETION-01',
            'creator_entity_id' => $this->entityA->id,
            'internal_entity_id' => $this->entityA->id,
            'created_by_user_id' => $this->userEntityA->id,
            'created_by' => $this->userEntityA->id,
            'status' => 'draft',
        ]);

        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->userEntityA);

        // Move to financial_review (Step 2)
        $this->approvalService->approveActiveStep($project->fresh(), $this->userEntityA, 'Step 1 Tech ok');
        $activeStep = $this->approvalService->getActiveStep($project->fresh());
        $this->assertEquals('financial_review', $activeStep->phase);
        $this->assertEquals(2, $activeStep->step_order);

        // Case A: financial_review -> request completion (PreviousStep)
        $resA = $this->approvalService->requestCompletion(
            $project->fresh(),
            $this->userEntityA,
            'يرجى مراجعة الأوزان الفنية ومطابقتها قبل الاعتماد المالي.',
            ReturnTarget::PreviousStep
        );

        $this->assertTrue($resA['success']);
        $this->assertEquals('previous_step', $resA['target']);

        // Check active step is now step 1 (technical_review of Entity A)
        $revertedStep1 = $this->approvalService->getActiveStep($project->fresh());
        $this->assertNotNull($revertedStep1);
        $this->assertEquals(1, $revertedStep1->step_order);
        $this->assertEquals('technical_review', $revertedStep1->phase);
        $this->assertEquals($this->entityA->id, $revertedStep1->entity_id);
        $this->assertTrue($revertedStep1->isActive());

        // Re-approve step 1 and step 2 to reach step 3 (stage_approval)
        $this->approvalService->approveActiveStep($project->fresh(), $this->userEntityA, 'Step 1 Tech fixed');
        $this->approvalService->approveActiveStep($project->fresh(), $this->userEntityA, 'Step 2 Fin ok');

        $activeStep3 = $this->approvalService->getActiveStep($project->fresh());
        $this->assertEquals('stage_approval', $activeStep3->phase);
        $this->assertEquals(3, $activeStep3->step_order);

        // Case B: stage_approval -> request completion (PreviousStep)
        $resB = $this->approvalService->requestCompletion(
            $project->fresh(),
            $this->userEntityA,
            'يرجى إعادة احتساب التكاليف الإجمالية وتعديل بنود الصرف.',
            ReturnTarget::PreviousStep
        );

        $this->assertTrue($resB['success']);
        $this->assertEquals('previous_step', $resB['target']);

        // Check active step is now step 2 (financial_review of Entity A)
        $revertedStep2 = $this->approvalService->getActiveStep($project->fresh());
        $this->assertNotNull($revertedStep2);
        $this->assertEquals(2, $revertedStep2->step_order);
        $this->assertEquals('financial_review', $revertedStep2->phase);
        $this->assertEquals($this->entityA->id, $revertedStep2->entity_id);
        $this->assertTrue($revertedStep2->isActive());
    }

    /**
     * Requirement 3: Entity Source Relationship Test:
     * Confirm $activeStep->entity is an instance of InternalEntity and linked to internal_entities table.
     */
    public function test_active_step_entity_source_is_internal_entity(): void
    {
        $project = Project::withoutGlobalScopes()->create([
            'project_name' => 'مشروع اختبار علاقة الجهة',
            'form_number' => 'PRJ-ENTITY-REL-01',
            'creator_entity_id' => $this->entityA->id,
            'internal_entity_id' => $this->entityA->id,
            'created_by_user_id' => $this->userEntityA->id,
            'created_by' => $this->userEntityA->id,
            'status' => 'draft',
        ]);

        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->userEntityA);
        $activeStep = $this->approvalService->getActiveStep($project->fresh());

        $this->assertNotNull($activeStep);
        $this->assertNotNull($activeStep->entity);
        $this->assertInstanceOf(InternalEntity::class, $activeStep->entity);
        $this->assertEquals('internal_entities', $activeStep->entity->getTable());
        $this->assertEquals($this->entityA->id, $activeStep->entity->id);
        $this->assertNull($activeStep->authority_id);
        $this->assertEquals('internal', $activeStep->approver_scope);
    }

    /**
     * Requirement 4: New Project Chain Generation Test:
     * Verify closeDraftAndGenerateApprovalChain() creates 3 stages per entity in correct hierarchy.
     */
    public function test_new_project_approval_chain_generation_hierarchy(): void
    {
        $project = Project::withoutGlobalScopes()->create([
            'project_name' => 'مشروع كامل التسلسل من المسودة حتى الوزارة',
            'form_number' => 'PRJ-CHAIN-FULL-01',
            'creator_entity_id' => $this->entityA->id,
            'internal_entity_id' => $this->entityA->id,
            'created_by_user_id' => $this->userEntityA->id,
            'created_by' => $this->userEntityA->id,
            'status' => 'draft',
        ]);

        $approvals = $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->userEntityA);

        // 3 entities (Entity A -> Entity B -> Ministry Root) * 3 stages = 9 steps
        $this->assertCount(9, $approvals);

        // Expected progression
        $expectedSequence = [
            1 => ['entity_id' => $this->entityA->id, 'phase' => 'technical_review', 'is_active' => true, 'status' => 'pending'],
            2 => ['entity_id' => $this->entityA->id, 'phase' => 'financial_review', 'is_active' => false, 'status' => 'locked'],
            3 => ['entity_id' => $this->entityA->id, 'phase' => 'stage_approval', 'is_active' => false, 'status' => 'locked'],

            4 => ['entity_id' => $this->entityB->id, 'phase' => 'technical_review', 'is_active' => false, 'status' => 'locked'],
            5 => ['entity_id' => $this->entityB->id, 'phase' => 'financial_review', 'is_active' => false, 'status' => 'locked'],
            6 => ['entity_id' => $this->entityB->id, 'phase' => 'stage_approval', 'is_active' => false, 'status' => 'locked'],

            7 => ['entity_id' => $this->ministryRoot->id, 'phase' => 'technical_review', 'is_active' => false, 'status' => 'locked'],
            8 => ['entity_id' => $this->ministryRoot->id, 'phase' => 'financial_review', 'is_active' => false, 'status' => 'locked'],
            9 => ['entity_id' => $this->ministryRoot->id, 'phase' => 'stage_approval', 'is_active' => false, 'status' => 'locked'],
        ];

        foreach ($approvals as $approval) {
            $order = $approval->step_order;
            $this->assertArrayHasKey($order, $expectedSequence);
            $expected = $expectedSequence[$order];

            $this->assertEquals($expected['entity_id'], $approval->entity_id, "Step {$order} entity_id mismatch");
            $this->assertEquals($expected['phase'], $approval->phase, "Step {$order} phase mismatch");
            $this->assertEquals($expected['is_active'], (bool) $approval->is_active, "Step {$order} is_active mismatch");
            $this->assertEquals($expected['status'], $approval->status, "Step {$order} status mismatch");
        }

        // Verify Project status updated
        $freshProject = $project->fresh();
        $this->assertEquals(ProjectStatus::PendingApproval->value, $freshProject->status);
        $this->assertEquals(1, $freshProject->current_stage_order);
    }
}

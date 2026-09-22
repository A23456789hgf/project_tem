<?php

namespace Tests\Feature;

use App\Enums\EntityResponsibilityType;
use App\Enums\ProjectStatus;
use App\Models\Authority;
use App\Models\AuthorityApprovalRoute;
use App\Models\AuthorityApprovalStage;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicEntityStagesVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $ministry;

    protected $entityBranch;

    protected $authority;

    protected $adminUser;

    protected $techUser;

    protected $finUser;

    protected $appUser;

    protected $branchUser;

    protected $authTechUser;

    protected $authAppUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ministry = InternalEntity::forceCreate([
            'name' => 'وزارة الزراعة والري',
            'entity_type' => 'Company',
            'is_active' => true,
            'is_ministry_root' => true,
        ]);

        $this->entityBranch = InternalEntity::forceCreate([
            'name' => 'فرع تعز',
            'entity_type' => 'Department',
            'parent_id' => $this->ministry->id,
            'is_active' => true,
            'is_ministry_root' => false,
        ]);

        $this->authority = Authority::forceCreate([
            'agency_name' => 'هيئة حماية البيئة',
            'is_active' => true,
        ]);

        $this->adminUser = User::forceCreate([
            'user_id' => 'u_admin',
            'phone' => '770000000',
            'username' => 'admin_user',
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->ministry->id,
            'organization_type' => 'internal',
            'status' => 'Active',
        ]);

        $this->techUser = User::forceCreate([
            'user_id' => 'u_tech',
            'phone' => '770000001',
            'username' => 'tech_reviewer',
            'name' => 'Technical Reviewer',
            'email' => 'tech@example.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->ministry->id,
            'organization_type' => 'internal',
            'responsibility' => 'TECHNICAL',
            'status' => 'Active',
        ]);

        $this->finUser = User::forceCreate([
            'user_id' => 'u_fin',
            'phone' => '770000002',
            'username' => 'fin_reviewer',
            'name' => 'Financial Reviewer',
            'email' => 'fin@example.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->ministry->id,
            'organization_type' => 'internal',
            'responsibility' => 'FINANCIAL',
            'status' => 'Active',
        ]);

        $this->appUser = User::forceCreate([
            'user_id' => 'u_app',
            'phone' => '770000003',
            'username' => 'entity_approver',
            'name' => 'Entity Approver',
            'email' => 'app@example.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->ministry->id,
            'organization_type' => 'internal',
            'responsibility' => 'ENTITY_APPROVER',
            'status' => 'Active',
        ]);

        $this->branchUser = User::forceCreate([
            'user_id' => 'u_branch',
            'phone' => '770000004',
            'username' => 'branch_user',
            'name' => 'Branch User',
            'email' => 'branch@example.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->entityBranch->id,
            'organization_type' => 'internal',
            'responsibility' => 'ENTITY_APPROVER',
            'status' => 'Active',
        ]);

        $this->authTechUser = User::forceCreate([
            'user_id' => 'u_auth_tech',
            'phone' => '770000005',
            'username' => 'auth_tech',
            'name' => 'Auth Tech Reviewer',
            'email' => 'authtech@example.com',
            'password' => bcrypt('password'),
            'authority_id' => $this->authority->id,
            'organization_type' => 'external',
            'responsibility' => 'TECHNICAL',
            'status' => 'Active',
        ]);

        $this->authAppUser = User::forceCreate([
            'user_id' => 'u_auth_app',
            'phone' => '770000006',
            'username' => 'auth_app',
            'name' => 'Auth Approver',
            'email' => 'authapp@example.com',
            'password' => bcrypt('password'),
            'authority_id' => $this->authority->id,
            'organization_type' => 'external',
            'responsibility' => 'ENTITY_APPROVER',
            'status' => 'Active',
        ]);
    }

    /**
     * Test 1: Internal Stages are loaded dynamically from EntityApprovalStage in custom order.
     */
    public function test_internal_stages_follow_configured_stages_and_orders(): void
    {
        // 1. Configure Ministry with Order: Stage A(Tech)=1, Stage B(Fin)=2, Stage C(App)=3
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::TechnicalReview->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $this->techUser->id,
        ]);
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::FinancialReview->value,
            'stage_order' => 2,
            'is_active' => true,
            'responsible_user_id' => $this->finUser->id,
        ]);
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 3,
            'is_active' => true,
            'responsible_user_id' => $this->appUser->id,
        ]);

        // Create Project 1 and submit
        $project1 = Project::forceCreate([
            'project_name' => 'مشروع داخلي 1',
            'status' => ProjectStatus::Draft->value,
            'source_type' => 'internal',
            'internal_entity_id' => $this->ministry->id,
            'creator_entity_id' => $this->ministry->id,
            'created_by_user_id' => $this->techUser->id,
        ]);

        $service = app(ApprovalService::class);
        $approvals1 = $service->closeDraftAndGenerateApprovalChain($project1, $this->techUser);

        $this->assertCount(3, $approvals1);
        $this->assertEquals(1, $approvals1[0]->step_order);
        $this->assertEquals('technical_review', $approvals1[0]->phase);
        $this->assertEquals(2, $approvals1[1]->step_order);
        $this->assertEquals('financial_review', $approvals1[1]->phase);
        $this->assertEquals(3, $approvals1[2]->step_order);
        $this->assertEquals('stage_approval', $approvals1[2]->phase);

        // 2. Now Admin changes order in EntityApprovalStage: Stage C(App)=1, Stage A(Tech)=2, Stage B(Fin)=3
        EntityApprovalStage::where('entity_id', $this->ministry->id)
            ->where('stage', EntityResponsibilityType::Approval->value)
            ->update(['stage_order' => 1]);

        EntityApprovalStage::where('entity_id', $this->ministry->id)
            ->where('stage', EntityResponsibilityType::TechnicalReview->value)
            ->update(['stage_order' => 2]);

        EntityApprovalStage::where('entity_id', $this->ministry->id)
            ->where('stage', EntityResponsibilityType::FinancialReview->value)
            ->update(['stage_order' => 3]);

        // Create a NEW Project 2 and submit
        $project2 = Project::forceCreate([
            'project_name' => 'مشروع داخلي 2',
            'status' => ProjectStatus::Draft->value,
            'source_type' => 'internal',
            'internal_entity_id' => $this->ministry->id,
            'creator_entity_id' => $this->ministry->id,
            'created_by_user_id' => $this->techUser->id,
        ]);

        $approvals2 = $service->closeDraftAndGenerateApprovalChain($project2, $this->techUser);

        $this->assertCount(3, $approvals2);
        // Verify Project 2 received the NEW order dynamically
        $this->assertEquals(1, $approvals2[0]->step_order);
        $this->assertEquals('stage_approval', $approvals2[0]->phase);
        $this->assertEquals(2, $approvals2[1]->step_order);
        $this->assertEquals('technical_review', $approvals2[1]->phase);
        $this->assertEquals(3, $approvals2[2]->step_order);
        $this->assertEquals('financial_review', $approvals2[2]->phase);
    }

    /**
     * Test 2: Deactivating a stage in settings excludes it from future project submissions.
     */
    public function test_deactivated_stage_is_excluded_from_future_projects(): void
    {
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::TechnicalReview->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $this->techUser->id,
        ]);
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::FinancialReview->value,
            'stage_order' => 2,
            'is_active' => false, // DEACTIVATED BY ADMIN
            'responsible_user_id' => $this->finUser->id,
        ]);
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 3,
            'is_active' => true,
            'responsible_user_id' => $this->appUser->id,
        ]);

        $project = Project::forceCreate([
            'project_name' => 'مشروع بدون مراجعة مالية',
            'status' => ProjectStatus::Draft->value,
            'source_type' => 'internal',
            'internal_entity_id' => $this->ministry->id,
            'creator_entity_id' => $this->ministry->id,
            'created_by_user_id' => $this->techUser->id,
        ]);

        $service = app(ApprovalService::class);
        $approvals = $service->closeDraftAndGenerateApprovalChain($project, $this->techUser);

        $this->assertCount(2, $approvals);
        $this->assertEquals('technical_review', $approvals[0]->phase);
        $this->assertEquals('stage_approval', $approvals[1]->phase);
        $this->assertFalse($approvals->contains('phase', 'financial_review'));
    }

    /**
     * Test 3: External stages and routes are dynamically read from AuthorityApprovalStage and AuthorityApprovalRoute.
     */
    public function test_external_stages_and_routes_dynamically_generated(): void
    {
        // 1. Setup Authority Stages
        AuthorityApprovalStage::create([
            'authority_id' => $this->authority->id,
            'stage' => EntityResponsibilityType::TechnicalReview->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $this->authTechUser->id,
        ]);
        AuthorityApprovalStage::create([
            'authority_id' => $this->authority->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 2,
            'is_active' => true,
            'responsible_user_id' => $this->authAppUser->id,
        ]);

        // 2. Setup Authority Route -> Ministry
        AuthorityApprovalRoute::create([
            'authority_id' => $this->authority->id,
            'destination_type' => 'ministry',
            'is_active' => true,
        ]);

        // 3. Setup Ministry Stage
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $this->appUser->id,
        ]);

        $project = Project::forceCreate([
            'project_name' => 'مشروع هيئة حماية البيئة',
            'status' => ProjectStatus::Draft->value,
            'source_type' => 'external',
            'authority_id' => $this->authority->id,
            'created_by_user_id' => $this->authTechUser->id,
        ]);

        $service = app(ApprovalService::class);
        $approvals = $service->closeDraftAndGenerateApprovalChain($project, $this->authTechUser);

        // Chain should be: Step 1 (Authority Tech) -> Step 2 (Authority App) -> Step 3 (Ministry App)
        $this->assertCount(3, $approvals);
        $this->assertEquals('external', $approvals[0]->approver_scope);
        $this->assertEquals($this->authority->id, $approvals[0]->authority_id);
        $this->assertEquals('technical_review', $approvals[0]->phase);

        $this->assertEquals('external', $approvals[1]->approver_scope);
        $this->assertEquals($this->authority->id, $approvals[1]->authority_id);
        $this->assertEquals('stage_approval', $approvals[1]->phase);

        $this->assertEquals('internal', $approvals[2]->approver_scope);
        $this->assertEquals($this->ministry->id, $approvals[2]->entity_id);
        $this->assertEquals('stage_approval', $approvals[2]->phase);
    }

    /**
     * Test 4: In-flight submitted projects retain their historical snapshot and do NOT change retroactively.
     */
    public function test_in_flight_projects_retain_immutable_snapshot_when_admin_edits_stages(): void
    {
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::TechnicalReview->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $this->techUser->id,
        ]);
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 2,
            'is_active' => true,
            'responsible_user_id' => $this->appUser->id,
        ]);

        $project = Project::forceCreate([
            'project_name' => 'مشروع تاريخي قيد المعاملة',
            'status' => ProjectStatus::Draft->value,
            'source_type' => 'internal',
            'internal_entity_id' => $this->ministry->id,
            'creator_entity_id' => $this->ministry->id,
            'created_by_user_id' => $this->techUser->id,
        ]);

        $service = app(ApprovalService::class);
        $approvals = $service->closeDraftAndGenerateApprovalChain($project, $this->techUser);

        $this->assertCount(2, $approvals);
        $step1Id = $approvals[0]->id;
        $step2Id = $approvals[1]->id;

        // Admin modifies the settings afterwards: deletes stage 1 and creates new stages
        EntityApprovalStage::where('entity_id', $this->ministry->id)->delete();
        EntityApprovalStage::create([
            'entity_id' => $this->ministry->id,
            'stage' => EntityResponsibilityType::FinancialReview->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $this->finUser->id,
        ]);

        // Verify that the existing project's ProjectApproval records remain untouched
        $persistedApprovals = ProjectApproval::where('project_id', $project->id)->orderBy('step_order')->get();
        $this->assertCount(2, $persistedApprovals);
        $this->assertEquals($step1Id, $persistedApprovals[0]->id);
        $this->assertEquals('technical_review', $persistedApprovals[0]->phase);
        $this->assertEquals($step2Id, $persistedApprovals[1]->id);
        $this->assertEquals('stage_approval', $persistedApprovals[1]->phase);
    }
}

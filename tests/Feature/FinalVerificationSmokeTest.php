<?php

namespace Tests\Feature;

use App\Enums\EntityResponsibilityType;
use App\Models\Authority;
use App\Models\AuthorityApprovalRoute;
use App\Models\AuthorityApprovalStage;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\User;
use App\Services\ApprovalChainBuilder;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalVerificationSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup base entities
        $this->ministry = InternalEntity::create(['name' => 'Ministry Root', 'entity_type' => 'Company', 'is_active' => true, 'is_ministry_root' => true]);
        $this->governorate = InternalEntity::create(['name' => 'Governorate Sanaa', 'entity_type' => 'Department', 'is_active' => true, 'parent_id' => $this->ministry->id]);

        $this->authorityA = Authority::create(['name' => 'Authority A', 'agency_name' => 'Agency A', 'is_active' => true]);
        $this->authorityB = Authority::create(['name' => 'Authority B', 'agency_name' => 'Agency B', 'is_active' => true]);

        // Setup users
        $this->userMinistryTech = User::create(['user_id' => 'umt', 'phone' => '111', 'username' => 'min_tech', 'name' => 'Min Tech', 'email' => 'min_tech@example.com', 'password' => 'password', 'entity_id' => $this->ministry->id, 'organization_type' => 'internal', 'responsibility' => 'TECHNICAL', 'status' => 'Active']);
        $this->userMinistryFin = User::create(['user_id' => 'umf', 'phone' => '112', 'username' => 'min_fin', 'name' => 'Min Fin', 'email' => 'min_fin@example.com', 'password' => 'password', 'entity_id' => $this->ministry->id, 'organization_type' => 'internal', 'responsibility' => 'FINANCIAL', 'status' => 'Active']);
        $this->userMinistryApp = User::create(['user_id' => 'uma', 'phone' => '113', 'username' => 'min_app', 'name' => 'Min App', 'email' => 'min_app@example.com', 'password' => 'password', 'entity_id' => $this->ministry->id, 'organization_type' => 'internal', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);

        $this->userGovApp = User::create(['user_id' => 'uga', 'phone' => '114', 'username' => 'gov_app', 'name' => 'Gov App', 'email' => 'gov_app@example.com', 'password' => 'password', 'entity_id' => $this->governorate->id, 'organization_type' => 'internal', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);

        $this->userAuthATech = User::create(['user_id' => 'uat', 'phone' => '115', 'username' => 'autha_tech', 'name' => 'AuthA Tech', 'email' => 'autha_tech@example.com', 'password' => 'password', 'authority_id' => $this->authorityA->id, 'organization_type' => 'external', 'responsibility' => 'TECHNICAL', 'status' => 'Active']);
        $this->userAuthAFin = User::create(['user_id' => 'uaf', 'phone' => '116', 'username' => 'autha_fin', 'name' => 'AuthA Fin', 'email' => 'autha_fin@example.com', 'password' => 'password', 'authority_id' => $this->authorityA->id, 'organization_type' => 'external', 'responsibility' => 'FINANCIAL', 'status' => 'Active']);
        $this->userAuthAApp = User::create(['user_id' => 'uaa', 'phone' => '117', 'username' => 'autha_app', 'name' => 'AuthA App', 'email' => 'autha_app@example.com', 'password' => 'password', 'authority_id' => $this->authorityA->id, 'organization_type' => 'external', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);

        $this->userAuthBApp = User::create(['user_id' => 'uba', 'phone' => '118', 'username' => 'authb_app', 'name' => 'AuthB App', 'email' => 'authb_app@example.com', 'password' => 'password', 'authority_id' => $this->authorityB->id, 'organization_type' => 'external', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);

        // Set up Ministry Stages
        EntityApprovalStage::create(['entity_id' => $this->ministry->id, 'stage' => EntityResponsibilityType::TechnicalReview->value, 'stage_order' => 1, 'is_active' => true, 'responsible_user_id' => $this->userMinistryTech->id]);
        EntityApprovalStage::create(['entity_id' => $this->ministry->id, 'stage' => EntityResponsibilityType::FinancialReview->value, 'stage_order' => 2, 'is_active' => true, 'responsible_user_id' => $this->userMinistryFin->id]);
        EntityApprovalStage::create(['entity_id' => $this->ministry->id, 'stage' => EntityResponsibilityType::Approval->value, 'stage_order' => 3, 'is_active' => true, 'responsible_user_id' => $this->userMinistryApp->id]);

        // Set up Governorate Stages
        EntityApprovalStage::create(['entity_id' => $this->governorate->id, 'stage' => EntityResponsibilityType::Approval->value, 'stage_order' => 1, 'is_active' => true, 'responsible_user_id' => $this->userGovApp->id]);

        // Set up Authority A Stages
        AuthorityApprovalStage::create(['authority_id' => $this->authorityA->id, 'stage' => EntityResponsibilityType::TechnicalReview->value, 'stage_order' => 1, 'is_active' => true, 'responsible_user_id' => $this->userAuthATech->id]);
        AuthorityApprovalStage::create(['authority_id' => $this->authorityA->id, 'stage' => EntityResponsibilityType::FinancialReview->value, 'stage_order' => 2, 'is_active' => true, 'responsible_user_id' => $this->userAuthAFin->id]);
        AuthorityApprovalStage::create(['authority_id' => $this->authorityA->id, 'stage' => EntityResponsibilityType::Approval->value, 'stage_order' => 3, 'is_active' => true, 'responsible_user_id' => $this->userAuthAApp->id]);

        // Set up Authority B Stages
        AuthorityApprovalStage::create(['authority_id' => $this->authorityB->id, 'stage' => EntityResponsibilityType::Approval->value, 'stage_order' => 1, 'is_active' => true, 'responsible_user_id' => $this->userAuthBApp->id]);

    }

    public function test_smoke_path_1_external_direct_to_ministry()
    {
        AuthorityApprovalRoute::create(['authority_id' => $this->authorityA->id, 'destination_type' => 'ministry', 'is_active' => true]);

        $builder = app(ApprovalChainBuilder::class);
        $chain = $builder->buildExternalChain($this->authorityA->id);

        $this->assertCount(6, $chain);
        // Stages: AuthA (Tech, Fin, App) -> Ministry (Tech, Fin, App)
        $this->assertEquals($this->authorityA->id, $chain[0]['authority_id']);
        $this->assertEquals('technical_review', $chain[0]['phase']);

        $this->assertEquals($this->authorityA->id, $chain[2]['authority_id']);
        $this->assertEquals('stage_approval', $chain[2]['phase']);

        $this->assertEquals($this->ministry->id, $chain[3]['entity_id']);
        $this->assertEquals('technical_review', $chain[3]['phase']);

        $this->assertEquals($this->ministry->id, $chain[5]['entity_id']);
        $this->assertEquals('stage_approval', $chain[5]['phase']);

        // Now test the service logic
        $project = Project::factory()->create([
            'authority_id' => $this->authorityA->id,
            'source_type' => 'external',
            'status' => 'draft',
            'created_by_user_id' => $this->userAuthAApp->id,
        ]);
        $approvalService = app(ApprovalService::class);
        $approvalService->closeDraftAndGenerateApprovalChain($project, $this->userAuthAApp);

        $this->assertEquals(6, ProjectApproval::where('project_id', $project->id)->count());
        $firstStep = ProjectApproval::where('project_id', $project->id)->orderBy('step_order')->first();
        $this->assertEquals('pending', $firstStep->status);
        $this->assertEquals('external', $firstStep->approver_scope);
        $this->assertEquals($this->authorityA->id, $firstStep->authority_id);
    }

    public function test_smoke_path_2_external_to_governorate_to_ministry()
    {
        AuthorityApprovalRoute::create([
            'authority_id' => $this->authorityB->id,
            'destination_type' => 'authority',
            'destination_authority_id' => $this->authorityA->id,
            'is_active' => true,
        ]);
        AuthorityApprovalRoute::create([
            'authority_id' => $this->authorityA->id,
            'destination_type' => 'ministry',
            'is_active' => true,
        ]);

        $builder = app(ApprovalChainBuilder::class);
        $chain = $builder->buildExternalChain($this->authorityB->id);

        // Authority B (App) -> Authority A (Tech, Fin, App) -> Ministry (Tech, Fin, App)
        $this->assertCount(7, $chain);
        $this->assertEquals($this->authorityB->id, $chain[0]['authority_id']);
        $this->assertEquals($this->authorityA->id, $chain[1]['authority_id']);
        $this->assertEquals($this->ministry->id, $chain[4]['entity_id']);
    }

    public function test_smoke_visibility_external_user()
    {
        $projectA = Project::factory()->create(['authority_id' => $this->authorityA->id, 'source_type' => 'external', 'project_name' => 'Project A']);
        $projectB = Project::factory()->create(['authority_id' => $this->authorityB->id, 'source_type' => 'external', 'project_name' => 'Project B']);
        $projectInternal = Project::factory()->create(['internal_entity_id' => $this->ministry->id, 'source_type' => 'internal', 'project_name' => 'Project Internal']);

        // Login as User from Authority A
        $this->actingAs($this->userAuthAApp);

        // Projects visible to Authority A should only be Project A
        $visibleProjects = Project::visibleToUser()->get();
        $this->assertTrue($visibleProjects->contains('id', $projectA->id));
        $this->assertFalse($visibleProjects->contains('id', $projectB->id));
        $this->assertFalse($visibleProjects->contains('id', $projectInternal->id));
    }
}

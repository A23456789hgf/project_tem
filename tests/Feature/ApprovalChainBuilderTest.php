<?php

namespace Tests\Feature;

use App\Enums\EntityResponsibilityType;
use App\Exceptions\ConfigurationException;
use App\Models\Authority;
use App\Models\AuthorityApprovalRoute;
use App\Models\AuthorityApprovalStage;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\User;
use App\Services\ApprovalChainBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalChainBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalChainBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = app(ApprovalChainBuilder::class);
    }

    public function test_it_throws_exception_if_no_ministry_root_exists_for_external_route()
    {
        $authority = Authority::create(['name' => 'Auth1', 'agency_name' => 'Agency 1', 'is_active' => true]);
        AuthorityApprovalRoute::create([
            'authority_id' => $authority->id,
            'destination_type' => 'ministry',
            'is_active' => true,
        ]);

        $this->expectException(ConfigurationException::class);
        $this->builder->buildExternalChain($authority->id);
    }

    public function test_it_detects_circular_authority_routes()
    {
        $authorityA = Authority::create(['name' => 'AuthA', 'agency_name' => 'Agency A', 'is_active' => true]);
        $authorityB = Authority::create(['name' => 'AuthB', 'agency_name' => 'Agency B', 'is_active' => true]);

        AuthorityApprovalRoute::create([
            'authority_id' => $authorityA->id,
            'destination_type' => 'authority',
            'destination_authority_id' => $authorityB->id,
            'is_active' => true,
        ]);

        AuthorityApprovalRoute::create([
            'authority_id' => $authorityB->id,
            'destination_type' => 'authority',
            'destination_authority_id' => $authorityA->id,
            'is_active' => true,
        ]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('تم اكتشاف حلقة دائرية');

        $this->builder->buildExternalChain($authorityA->id);
    }

    public function test_it_validates_and_builds_valid_external_chain()
    {
        $ministry = InternalEntity::create(['name' => 'Ministry', 'entity_type' => 'Company', 'is_active' => true, 'is_ministry_root' => true]);
        $authorityA = Authority::create(['name' => 'AuthA', 'agency_name' => 'Agency A', 'is_active' => true]);
        $authorityB = Authority::create(['name' => 'AuthB', 'agency_name' => 'Agency B', 'is_active' => true]);

        // Users for stages
        $userA = User::create(['user_id' => 'ua', 'phone' => '771000101', 'username' => 'usera', 'name' => 'User A', 'email' => 'usera@example.com', 'password' => 'password', 'authority_id' => $authorityA->id, 'organization_type' => 'external', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);
        $userB = User::create(['user_id' => 'ub', 'phone' => '771000102', 'username' => 'userb', 'name' => 'User B', 'email' => 'userb@example.com', 'password' => 'password', 'authority_id' => $authorityB->id, 'organization_type' => 'external', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);
        $userMinistry = User::create(['user_id' => 'um', 'phone' => '771000103', 'username' => 'usermin', 'name' => 'User Min', 'email' => 'usermin@example.com', 'password' => 'password', 'entity_id' => $ministry->id, 'organization_type' => 'internal', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);

        // Route: A -> B -> Ministry
        AuthorityApprovalRoute::create([
            'authority_id' => $authorityA->id,
            'destination_type' => 'authority',
            'destination_authority_id' => $authorityB->id,
            'is_active' => true,
        ]);

        AuthorityApprovalRoute::create([
            'authority_id' => $authorityB->id,
            'destination_type' => 'ministry',
            'is_active' => true,
        ]);

        // Stages for A
        AuthorityApprovalStage::create([
            'authority_id' => $authorityA->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $userA->id,
        ]);

        // Stages for B
        AuthorityApprovalStage::create([
            'authority_id' => $authorityB->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $userB->id,
        ]);

        // Stages for Ministry
        EntityApprovalStage::create([
            'entity_id' => $ministry->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $userMinistry->id,
        ]);

        $chain = $this->builder->buildExternalChain($authorityA->id);

        $this->assertCount(3, $chain);
        $this->assertEquals($authorityA->id, $chain[0]['authority_id']);
        $this->assertEquals($authorityB->id, $chain[1]['authority_id']);
        $this->assertEquals($ministry->id, $chain[2]['entity_id']);
        $this->assertTrue($chain[2]['is_ministry_root']);
    }

    public function test_it_builds_internal_chain()
    {
        $ministry = InternalEntity::create(['name' => 'Ministry', 'entity_type' => 'Company', 'is_active' => true, 'is_ministry_root' => true]);
        $department = InternalEntity::create(['name' => 'Dept', 'entity_type' => 'Department', 'is_active' => true, 'parent_id' => $ministry->id]);

        $userDept = User::create(['user_id' => 'ud', 'phone' => '771000104', 'username' => 'userdept', 'name' => 'User Dept', 'email' => 'userdept@example.com', 'password' => 'password', 'entity_id' => $department->id, 'organization_type' => 'internal', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);
        $userMinistry = User::create(['user_id' => 'umi', 'phone' => '771000105', 'username' => 'userminint', 'name' => 'User Min Int', 'email' => 'userminint@example.com', 'password' => 'password', 'entity_id' => $ministry->id, 'organization_type' => 'internal', 'responsibility' => 'ENTITY_APPROVER', 'status' => 'Active']);

        EntityApprovalStage::create([
            'entity_id' => $department->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $userDept->id,
        ]);

        EntityApprovalStage::create([
            'entity_id' => $ministry->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $userMinistry->id,
        ]);

        $chain = $this->builder->buildInternalChain($department->id);

        $this->assertCount(2, $chain);
        $this->assertEquals($department->id, $chain[0]['entity_id']);
        $this->assertEquals($ministry->id, $chain[1]['entity_id']);
        $this->assertTrue($chain[1]['is_ministry_root']);
    }
}

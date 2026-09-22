<?php

namespace Tests\Feature;

use App\Enums\EntityResponsibilityType;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\User;
use App\Services\ApprovalChainBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_internal_workflow_is_preserved()
    {
        $ministry = InternalEntity::create(['name' => 'Ministry', 'entity_type' => 'Company', 'is_active' => true, 'is_ministry_root' => true]);
        $dept = InternalEntity::create(['name' => 'Department', 'entity_type' => 'Department', 'is_active' => true, 'parent_id' => $ministry->id]);

        $userDept = User::create([
            'user_id' => 'ud1',
            'username' => 'dept_user',
            'name' => 'Dept User',
            'email' => 'dept@example.com',
            'phone' => '222222222',
            'password' => 'password',
            'entity_id' => $dept->id,
            'organization_type' => 'internal',
            'responsibility' => 'ENTITY_APPROVER',
            'status' => 'Active',
        ]);

        $userMin = User::create([
            'user_id' => 'um1',
            'username' => 'min_user',
            'name' => 'Ministry User',
            'email' => 'min@example.com',
            'phone' => '333333333',
            'password' => 'password',
            'entity_id' => $ministry->id,
            'organization_type' => 'internal',
            'responsibility' => 'ENTITY_APPROVER',
            'status' => 'Active',
        ]);

        EntityApprovalStage::create([
            'entity_id' => $dept->id,
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
            'responsible_user_id' => $userMin->id,
        ]);

        $builder = new ApprovalChainBuilder;
        $chain = $builder->buildInternalChain($dept->id);

        $this->assertCount(2, $chain);
        $this->assertEquals($dept->id, $chain[0]['entity_id']);
        $this->assertEquals($ministry->id, $chain[1]['entity_id']);
    }
}

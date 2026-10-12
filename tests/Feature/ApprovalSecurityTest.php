<?php

namespace Tests\Feature;

use App\Exceptions\InvalidWorkflowTransitionException;
use App\Exceptions\UnauthorizedWorkflowActionException;
use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->approvalService = app(ApprovalService::class);
    }

    public function test_external_internal_isolation_prevents_id_collision()
    {
        // Create InternalEntity ID 999
        $internalEntity = InternalEntity::factory()->create([
            'id' => 999,
            'is_ministry_root' => false,
        ]);

        // Create Authority ID 999
        $authority = Authority::forceCreate([
            'id' => 999,
            'agency_name' => 'Authority 999',
            'is_active' => true,
        ]);

        // Create a user for InternalEntity 999
        $role = Role::firstOrCreate(['name' => 'Reviewer', 'full_access' => false]);
        $internalUser = User::factory()->create([
            'entity_id' => $internalEntity->id,
            'organization_type' => 'internal',
            'authority_id' => null,
            'is_admin' => false,
            'role_id' => $role->id,
        ]);

        // Create an active step for Authority 999 (External Scope)
        $project = Project::factory()->create();
        $step = ProjectApproval::forceCreate([
            'project_id' => $project->id,
            'entity_id' => null,
            'authority_id' => $authority->id,
            'approver_scope' => 'external',
            'status' => 'pending',
            'is_active' => true,
            'phase' => 'approval',
            'drop' => 'approval',
            'step_order' => 1,
        ]);

        // Act & Assert
        // The internalUser has entity_id 999, the step is for authority_id 999.
        // It must NOT allow approval.
        $this->assertFalse($this->approvalService->canUserActOnStep($internalUser, $step));

        $this->expectException(UnauthorizedWorkflowActionException::class);
        $this->approvalService->approveActiveStep($project, $internalUser);
    }

    public function test_prevent_cross_authority_approval()
    {
        $authorityA = Authority::forceCreate(['agency_name' => 'Auth A', 'is_active' => true]);
        $authorityB = Authority::forceCreate(['agency_name' => 'Auth B', 'is_active' => true]);

        $role = Role::firstOrCreate(['name' => 'Reviewer', 'full_access' => false]);
        $userB = User::factory()->create([
            'entity_id' => null,
            'authority_id' => $authorityB->id,
            'organization_type' => 'external',
            'is_admin' => false,
            'role_id' => $role->id,
        ]);

        $project = Project::factory()->create();
        $step = ProjectApproval::forceCreate([
            'project_id' => $project->id,
            'entity_id' => null,
            'authority_id' => $authorityA->id,
            'approver_scope' => 'external',
            'status' => 'pending',
            'is_active' => true,
            'phase' => 'approval',
            'drop' => 'approval',
            'step_order' => 1,
        ]);

        $this->assertFalse($this->approvalService->canUserActOnStep($userB, $step));
    }

    public function test_in_execution_blocks_non_ministry_root()
    {
        $nonMinistryEntity = InternalEntity::factory()->create(['is_ministry_root' => false]);

        $user = User::factory()->create([
            'entity_id' => $nonMinistryEntity->id,
            'organization_type' => 'internal',
        ]);

        $project = Project::factory()->create(['status' => 'pending_approval']);

        // This is the LAST step (no next step exists), so approving it attempts to go to in_execution
        $step = ProjectApproval::forceCreate([
            'project_id' => $project->id,
            'entity_id' => $nonMinistryEntity->id,
            'approver_scope' => 'internal',
            'status' => 'pending',
            'is_active' => true,
            'phase' => 'stage_approval',
            'drop' => 'approval',
            'step_order' => 1,
        ]);

        $this->expectException(InvalidWorkflowTransitionException::class);
        $this->expectExceptionMessage('يجب أن تنتهي سلسلة الاعتمادات بمرحلة اعتماد نهائية');

        $this->approvalService->approveActiveStep($project, $user);
    }

    public function test_in_execution_succeeds_for_ministry_root()
    {
        $ministryEntity = InternalEntity::factory()->create(['is_ministry_root' => true]);

        $user = User::factory()->create([
            'entity_id' => $ministryEntity->id,
            'organization_type' => 'internal',
        ]);

        $project = Project::factory()->create(['status' => 'pending_approval']);

        // This is the LAST step (no next step exists)
        $step = ProjectApproval::forceCreate([
            'project_id' => $project->id,
            'entity_id' => $ministryEntity->id,
            'approver_scope' => 'internal',
            'status' => 'pending',
            'is_active' => true,
            'phase' => 'stage_approval',
            'drop' => 'approval',
            'step_order' => 1,
        ]);

        $result = $this->approvalService->approveActiveStep($project, $user);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_final']);
        $this->assertEquals('in_execution', $project->fresh()->status);
    }
}

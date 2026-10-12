<?php

namespace Tests\Feature;

use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\ProjectReferral;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    private function createReviewerRole(): Role
    {
        return Role::create([
            'name' => 'Reviewer',
            'is_active' => true,
            'full_access' => false,
        ]);
    }

    public function test_assigned_user_can_see_and_act_on_approval()
    {
        $entity = InternalEntity::create(['name' => 'Test Entity']);
        $reviewerRole = $this->createReviewerRole();

        $assignedUser = User::factory()->create(['entity_id' => $entity->id, 'role_id' => $reviewerRole->id]);
        $otherUser = User::factory()->create(['entity_id' => $entity->id, 'role_id' => $reviewerRole->id]);
        $adminRole = Role::create(['name' => 'admin']);
        $adminUser = clone User::factory()->create(['entity_id' => $entity->id, 'role_id' => $adminRole->id]);

        $project = Project::create([
            'project_name' => 'Test Project',
            'status' => 'pending_approval',
            'creator_entity_id' => $entity->id,
            'created_by_user_id' => $assignedUser->id,
        ]);

        $approval = ProjectApproval::create([
            'project_id' => $project->id,
            'entity_id' => $entity->id,
            'assigned_user_id' => $assignedUser->id,
            'status' => 'pending',
            'is_active' => true,
            'step_order' => 1,
            'approval_type' => 'approval',
        ]);

        // Assigned user can see it in inbox
        // Removed global Gate::before to enforce real policy checks

        $this->actingAs($assignedUser);
        $response = $this->get('/approvals');
        $response->assertStatus(200);

        $response->assertSee($project->project_name);

        $approvalService = app(ApprovalService::class);
        $this->assertTrue($approvalService->canUserActOnStep($assignedUser, $approval));

        // Restore approval status for further tests
        $approval->update(['status' => 'pending']);

        // Other user cannot see it in inbox
        $this->actingAs($otherUser);
        $response = $this->get('/approvals');
        $response->assertDontSee($project->project_name);

        $this->assertFalse($approvalService->canUserActOnStep($otherUser, $approval));

        // Admin can see it
        $this->actingAs($adminUser);
        $response = $this->get('/approvals');
        $response->assertSee($project->project_name);

        $this->assertTrue($approvalService->canUserActOnStep($adminUser, $approval));
    }

    public function test_entity_assigned_approval_is_visible_to_authorized_entity_user()
    {
        $entity = InternalEntity::create(['name' => 'Test Entity']);
        $reviewerRole = $this->createReviewerRole();

        $user = User::factory()->create(['entity_id' => $entity->id, 'role_id' => $reviewerRole->id]);

        $project = Project::create([
            'project_name' => 'Test Project',
            'status' => 'pending_approval',
            'creator_entity_id' => $entity->id,
            'created_by_user_id' => $user->id,
        ]);

        $approval = ProjectApproval::create([
            'project_id' => $project->id,
            'entity_id' => $entity->id,
            'assigned_user_id' => null,
            'status' => 'pending',
            'is_active' => true,
            'step_order' => 1,
            'approval_type' => 'approval',
        ]);

        // With no individual assignee, the authorized entity is responsible.
        $this->actingAs($user);
        $response = $this->get('/approvals');
        $response->assertSee($project->project_name);
    }

    public function test_assigned_user_can_see_and_act_on_referral()
    {
        $entity = InternalEntity::create(['name' => 'Test Entity']);
        $reviewerRole = $this->createReviewerRole();

        $assignedUser = User::factory()->create(['entity_id' => $entity->id, 'role_id' => $reviewerRole->id]);
        $otherUser = User::factory()->create(['entity_id' => $entity->id, 'role_id' => $reviewerRole->id]);
        $referringUser = User::factory()->create(['entity_id' => $entity->id, 'role_id' => $reviewerRole->id]);

        $project = Project::create([
            'project_name' => 'Referral Project',
            'status' => 'pending_approval',
            'creator_entity_id' => $entity->id,
            'created_by_user_id' => $referringUser->id,
        ]);

        $referral = ProjectReferral::create([
            'project_id' => $project->id,
            'referring_entity_id' => $entity->id,
            'referring_user_id' => $referringUser->id,
            'referred_entity_id' => $entity->id,
            'referred_user_id' => $assignedUser->id,
            'referral_text' => 'Test referral',
            'status' => 'pending',
        ]);

        // Assigned user can see it in consultations
        $this->actingAs($assignedUser);
        $response = $this->get('/consultations');
        $response->assertStatus(200);

        $records = $response->original->getData()['referrals'];
        $this->assertTrue($records->contains('id', $referral->id));

        $this->assertTrue($assignedUser->can('respond', $referral));

        // Other user cannot see it
        $this->actingAs($otherUser);
        $response = $this->get('/consultations');

        $records = $response->original->getData()['referrals'];
        $this->assertFalse($records->contains('id', $referral->id));

        $this->assertFalse($otherUser->can('respond', $referral));
    }
}

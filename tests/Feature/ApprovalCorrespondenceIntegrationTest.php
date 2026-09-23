<?php

namespace Tests\Feature;

use App\Enums\ApprovalPhase;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApprovalCorrespondenceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected InternalEntity $entityA;

    protected InternalEntity $entityB;

    protected User $userA;

    protected User $userB;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('signatures/test_signature.png', 'fake_signature_binary_data');

        $this->approvalService = app(ApprovalService::class);

        PermissionResolver::clearCache();
        Cache::flush();
        $this->seed(PermissionsSeeder::class);

        $this->entityA = InternalEntity::create([
            'name' => 'Entity A',
            'is_ministry_root' => false,
            'is_active' => true,
        ]);

        $this->entityB = InternalEntity::create([
            'name' => 'Entity B',
            'is_ministry_root' => true,
            'is_active' => true,
        ]);

        $this->adminRole = Role::firstOrCreate([
            'name' => 'Admin',
            'is_active' => true,
            'full_access' => true,
        ]);

        $this->userA = User::factory()->create([
            'name' => 'User A',
            'entity_id' => $this->entityA->id,
            'role_id' => $this->adminRole->id,
            'signature_path' => 'signatures/test_signature.png',
        ]);

        $this->userB = User::factory()->create([
            'name' => 'User B',
            'entity_id' => $this->entityB->id,
            'role_id' => $this->adminRole->id,
            'signature_path' => 'signatures/test_signature.png',
        ]);
    }

    public function test_can_create_correspondence_linked_to_project_and_approval_context(): void
    {
        $project = Project::factory()->create([
            'project_name' => 'مشروع إنشاء مركز تدريب',
            'form_number' => 'PRJ-2026-99',
            'creator_entity_id' => $this->entityA->id,
            'status' => 'pending_approval',
        ]);

        $step = ProjectApproval::create([
            'project_id' => $project->id,
            'entity_id' => $this->entityA->id,
            'step_order' => 1,
            'phase' => ApprovalPhase::TechnicalReview->value,
            'status' => 'pending',
            'is_active' => true,
            'drop' => 'technical_review',
        ]);

        $response = $this->actingAs($this->userA)->post(route('correspondence.store'), [
            'subject' => 'مراسلة بخصوص المراجعة الفنية للمشروع',
            'message_body' => 'يرجى تزويدنا بالملاحظات الفنية المطلوبة للاعتماد.',
            'recipient_entity_id' => $this->entityB->id,
            'project_id' => $project->id,
            'priority' => 'high',
            'notes' => 'موافقة',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('correspondences', [
            'subject' => 'مراسلة بخصوص المراجعة الفنية للمشروع',
            'project_id' => $project->id,
            'sender_entity_id' => $this->entityA->id,
            'recipient_entity_id' => $this->entityB->id,
        ]);

        // Verify workflow is still pending at step 1 without alteration
        $this->assertEquals(1, $project->fresh()->projectApprovals()->where('is_active', true)->first()->step_order);
        $this->assertEquals('pending', $project->fresh()->projectApprovals()->where('is_active', true)->first()->status);
    }

    public function test_general_correspondence_without_project_id_works_normally(): void
    {
        $response = $this->actingAs($this->userA)->post(route('correspondence.store'), [
            'subject' => 'مراسلة إدارية عامة',
            'message_body' => 'نص المراسلة الإدارية العامة بدون أي مشروع.',
            'recipient_entity_id' => $this->entityB->id,
            'priority' => 'normal',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('correspondences', [
            'subject' => 'مراسلة إدارية عامة',
            'project_id' => null,
            'sender_entity_id' => $this->entityA->id,
            'recipient_entity_id' => $this->entityB->id,
        ]);
    }
}

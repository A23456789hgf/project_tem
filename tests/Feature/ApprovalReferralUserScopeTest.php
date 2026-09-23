<?php

namespace Tests\Feature;

use App\Enums\ApprovalPhase;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\ProjectReferral;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApprovalReferralUserScopeTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected InternalEntity $entityA;

    protected InternalEntity $entityB;

    protected User $userA;

    protected User $userB1;

    protected User $userB2;

    protected User $userBDisabled;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('signatures/test_signature.png', 'fake_signature_binary_data');

        $this->approvalService = app(ApprovalService::class);

        PermissionResolver::clearCache();
        $permissionSlugs = [
            'main_modules.projects',
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            'approvals.request-action',
            'projects.submit',
            'projects.create',
            'projects.edit',
            'projects.view',
            'projects.view-details',
            'referrals.create',
            'referrals.view',
        ];

        foreach ($permissionSlugs as $slug) {
            \App\Models\Permission::firstOrCreate(
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

        $this->entityA = InternalEntity::create([
            'name' => 'الإدارة العامة للمشاريع',
            'is_ministry_root' => false,
            'is_active' => true,
        ]);

        $this->entityB = InternalEntity::create([
            'name' => 'قطاع الدراسات والتخطيط',
            'is_ministry_root' => false,
            'is_active' => true,
        ]);

        $this->adminRole = Role::create([
            'name' => 'Admin',
            'is_active' => true,
            'full_access' => true,
        ]);

        $this->userA = User::factory()->create([
            'name' => 'مهندس الاعتماد',
            'entity_id' => $this->entityA->id,
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'is_admin' => true,
            'signature_path' => 'signatures/test_signature.png',
        ]);

        // User B1: assigned via entity_id
        $this->userB1 = User::factory()->create([
            'name' => 'أحمد المهندس الاستشاري',
            'entity_id' => $this->entityB->id,
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'phone' => '777123456',
            'password' => bcrypt('secret123'),
        ]);

        // User B2: assigned via administrative_scope_id
        $this->userB2 = User::factory()->create([
            'name' => 'محمد الخبير التخطيطي',
            'entity_id' => null,
            'administrative_scope_id' => $this->entityB->id,
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'phone' => '777654321',
            'password' => bcrypt('secret123'),
        ]);

        // User BDisabled: disabled user
        $this->userBDisabled = User::factory()->create([
            'name' => 'مستخدم معطل',
            'entity_id' => $this->entityB->id,
            'role_id' => $this->adminRole->id,
            'status' => 'Disabled',
            'phone' => '777000000',
        ]);
    }

    public function test_api_entity_users_returns_only_clean_active_user_names_without_sensitive_data(): void
    {
        $response = $this->actingAs($this->userA)->getJson(route('api.entities.users', $this->entityB->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'users' => [
                '*' => ['id', 'name'],
            ],
        ]);

        $json = $response->json();
        $this->assertTrue($json['success']);
        $users = $json['users'];

        $this->assertCount(2, $users);

        $userNames = array_column($users, 'name');
        $this->assertContains('أحمد المهندس الاستشاري', $userNames);
        $this->assertContains('محمد الخبير التخطيطي', $userNames);
        $this->assertNotContains('مستخدم معطل', $userNames);

        // Assert strictly NO sensitive keys exist in the response
        foreach ($users as $u) {
            $this->assertArrayHasKey('id', $u);
            $this->assertArrayHasKey('name', $u);
            $this->assertArrayNotHasKey('password', $u);
            $this->assertArrayNotHasKey('phone', $u);
            $this->assertArrayNotHasKey('email', $u);
            $this->assertArrayNotHasKey('username', $u);
        }
    }

    public function test_can_submit_consultation_referral_to_referred_user_in_approvals(): void
    {
        $project = Project::factory()->create([
            'project_name' => 'مشروع إنشاء شبكة ري',
            'form_number' => 'PRJ-2026-88',
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

        $response = $this->actingAs($this->userA)->post(route('approvals.referral', $project), [
            'referred_entity_id' => $this->entityB->id,
            'referred_user_id' => $this->userB1->id,
            'referral_text' => 'يرجى مراجعة المخططات الهندسية وشبكة الري وإبداء الرأي الفني.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('approvals.show', $project));

        $this->assertDatabaseHas('project_referrals', [
            'project_id' => $project->id,
            'referring_user_id' => $this->userA->id,
            'referred_entity_id' => $this->entityB->id,
            'referred_user_id' => $this->userB1->id,
            'status' => 'pending',
        ]);

        // Verify active step is still intact at step 1
        $this->assertEquals(1, $project->fresh()->projectApprovals()->where('is_active', true)->first()->step_order);
    }
}

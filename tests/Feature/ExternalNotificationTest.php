<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectAchievement;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Notifications\ProjectApprovalNotification;
use App\Services\NotificationService;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExternalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Authority $authorityA;

    protected Authority $authorityB;

    protected User $userA1;

    protected User $userAInactive;

    protected User $userB1;

    protected Project $projectA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorityA = Authority::create([
            'agency_name' => 'الهيئة الخارجية الأولى',
            'is_active' => true,
        ]);

        $this->authorityB = Authority::create([
            'agency_name' => 'الهيئة الخارجية الثانية',
            'is_active' => true,
        ]);

        $role = Role::create([
            'name' => 'external_officer',
            'label' => 'ضابط جهة خارجية',
        ]);

        $perm = Permission::firstOrCreate(['slug' => 'projects.view'], [
            'name' => 'عرض المشاريع',
            'module' => 'projects',
            'type' => 'page',
        ]);

        RolePermission::firstOrCreate([
            'role_id' => $role->id,
            'permission_id' => $perm->id,
            'role_legacy' => $role->name,
        ]);

        PermissionResolver::clearCache();

        $this->userA1 = User::create([
            'user_id' => 'USER_A1',
            'phone' => '771000001',
            'name' => 'Active User Auth A',
            'username' => 'user_a1',
            'email' => 'a1@test.com',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->userAInactive = User::create([
            'user_id' => 'USER_A_INACT',
            'phone' => '771000002',
            'name' => 'Inactive User Auth A',
            'username' => 'user_ainactive',
            'email' => 'ainactive@test.com',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'role_id' => $role->id,
            'status' => 'inactive',
        ]);

        $this->userB1 = User::create([
            'user_id' => 'USER_B1',
            'phone' => '771000003',
            'name' => 'Active User Auth B',
            'username' => 'user_b1',
            'email' => 'b1@test.com',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->projectA = Project::create([
            'project_name' => 'مشروع الهيئة أ',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'in_execution',
            'created_by_user_id' => $this->userA1->id,
            'organization_type' => 'external',
        ]);
    }

    public function test_notifications_sent_to_active_users_of_project_authority(): void
    {
        Notification::fake();

        $achievement = new ProjectAchievement;
        $achievement->new_achievement = 25;

        $service = app(NotificationService::class);
        $service->notifyOldProjectAchievementRecorded($this->projectA, $achievement);

        Notification::assertSentTo(
            $this->userA1,
            ProjectApprovalNotification::class
        );
    }

    public function test_notifications_not_sent_to_inactive_users_of_authority(): void
    {
        Notification::fake();

        $achievement = new ProjectAchievement;
        $achievement->new_achievement = 25;

        $service = app(NotificationService::class);
        $service->notifyOldProjectAchievementRecorded($this->projectA, $achievement);

        Notification::assertNotSentTo(
            $this->userAInactive,
            ProjectApprovalNotification::class
        );
    }

    public function test_notifications_never_leak_to_other_authorities(): void
    {
        Notification::fake();

        $achievement = new ProjectAchievement;
        $achievement->new_achievement = 25;

        $service = app(NotificationService::class);
        $service->notifyOldProjectAchievementRecorded($this->projectA, $achievement);

        Notification::assertNotSentTo(
            $this->userB1,
            ProjectApprovalNotification::class
        );
    }
}

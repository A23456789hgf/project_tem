<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\ExecutiveActivity;
use App\Models\ExecutiveActivityAction;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExternalExecutionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Authority $authorityA;

    protected Authority $authorityB;

    protected InternalEntity $ministryEntity;

    protected User $userAWithPerm;

    protected User $userAWithoutPerm;

    protected User $userBWithPerm;

    protected User $ministryUser;

    protected Project $projectA;

    protected ExecutiveActivityAction $actionA;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionResolver::clearCache();
        Cache::flush();

        $this->authorityA = Authority::create([
            'agency_name' => 'الهيئة التنفيذية أ',
            'is_active' => true,
        ]);

        $this->authorityB = Authority::create([
            'agency_name' => 'الهيئة التنفيذية ب',
            'is_active' => true,
        ]);

        $this->ministryEntity = InternalEntity::create([
            'name' => 'إدارة المتابعة والتنفيذ الوزارية',
            'type' => 'administration',
            'is_active' => true,
        ]);

        // Permissions
        $permissionDefs = [
            'main_modules.projects' => ['name' => 'الوحدة الرئيسية للمشاريع', 'module' => 'main_modules', 'type' => 'page'],
            'projects.view' => ['name' => 'عرض المشاريع', 'module' => 'projects', 'type' => 'page'],
            'projects.view-details' => ['name' => 'عرض تفاصيل المشروع', 'module' => 'projects', 'type' => 'page'],
            'projects.execute' => ['name' => 'تنفيذ المشاريع', 'module' => 'projects', 'type' => 'action'],
            'execution.view' => ['name' => 'عرض التنفيذ', 'module' => 'execution', 'type' => 'page'],
            'execution.add' => ['name' => 'إضافة تنفيذ', 'module' => 'execution', 'type' => 'action'],
            'execution.edit' => ['name' => 'تعديل تنفيذ', 'module' => 'execution', 'type' => 'action'],
        ];

        $permModels = [];
        foreach ($permissionDefs as $slug => $data) {
            $permModels[$slug] = Permission::firstOrCreate(['slug' => $slug], $data);
        }

        $roleExecWithPerm = Role::create(['name' => 'exec_with_perm', 'label' => 'منفذ بصلاحية', 'is_active' => true]);
        $roleExecWithoutPerm = Role::create(['name' => 'exec_without_perm', 'label' => 'منفذ بدون صلاحية تنفيذ', 'is_active' => true]);
        $roleMinistry = Role::create(['name' => 'ministry_admin', 'label' => 'موظف وزارة', 'is_active' => true, 'full_access' => true]);

        foreach ($permModels as $slug => $perm) {
            RolePermission::create(['role_id' => $roleExecWithPerm->id, 'permission_id' => $perm->id, 'role_legacy' => $roleExecWithPerm->name]);
            RolePermission::create(['role_id' => $roleMinistry->id, 'permission_id' => $perm->id, 'role_legacy' => $roleMinistry->name]);

            if (in_array($slug, ['main_modules.projects', 'projects.view', 'projects.view-details', 'projects.execute', 'execution.view'])) {
                RolePermission::create(['role_id' => $roleExecWithoutPerm->id, 'permission_id' => $perm->id, 'role_legacy' => $roleExecWithoutPerm->name]);
            }
        }

        PermissionResolver::clearCache();

        $this->userAWithPerm = User::create([
            'user_id' => 'USER_A_EXEC',
            'phone' => '771000001',
            'name' => 'User A (Execution Allowed)',
            'username' => 'user_a_exec',
            'email' => 'user_a_exec@test.com',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'role_id' => $roleExecWithPerm->id,
            'status' => 'active',
        ]);

        $this->userAWithoutPerm = User::create([
            'user_id' => 'USER_A_NO_EXEC',
            'phone' => '771000002',
            'name' => 'User A (Execution Denied)',
            'username' => 'user_a_no_exec',
            'email' => 'user_a_no_exec@test.com',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'role_id' => $roleExecWithoutPerm->id,
            'status' => 'active',
        ]);

        $this->userBWithPerm = User::create([
            'user_id' => 'USER_B_EXEC',
            'phone' => '771000003',
            'name' => 'User B (Cross Authority)',
            'username' => 'user_b_exec',
            'email' => 'user_b_exec@test.com',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'role_id' => $roleExecWithPerm->id,
            'status' => 'active',
        ]);

        $this->ministryUser = User::create([
            'user_id' => 'MIN_USER',
            'phone' => '771000004',
            'name' => 'Ministry Oversight User',
            'username' => 'ministry_user',
            'email' => 'ministry_user@test.com',
            'password' => bcrypt('password'),
            'organization_type' => 'internal',
            'entity_id' => $this->ministryEntity->id,
            'role_id' => $roleMinistry->id,
            'status' => 'active',
            'is_admin' => true,
        ]);

        $this->projectA = Project::create([
            'project_name' => 'مشروع قيد التنفيذ لهيئة أ',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'in_execution',
            'created_by_user_id' => $this->userAWithPerm->id,
            'organization_type' => 'external',
        ]);

        $activity = ExecutiveActivity::create([
            'project_id' => $this->projectA->id,
            'name' => 'نشاط تنفيذي أ',
        ]);

        $this->actionA = ExecutiveActivityAction::create([
            'executive_activity_id' => $activity->id,
            'action' => 'إجراء تنفيذي 1',
            'weight' => 10,
        ]);
    }

    public function test_external_user_a_with_permission_can_save_execution_on_own_project(): void
    {
        $response = $this->actingAs($this->userAWithPerm)->postJson(route('projects.execution.store', $this->projectA->id), [
            'executive_activity_action_id' => $this->actionA->id,
            'status' => 'in_progress',
            'completion_percentage' => 40,
            'amount_spent' => 5000,
            'notes' => 'بدء تنفيذ المرحلة الأولى',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_executions', [
            'project_id' => $this->projectA->id,
            'executive_activity_action_id' => $this->actionA->id,
            'completion_percentage' => 40,
        ]);
    }

    public function test_external_user_b_cross_authority_cannot_save_execution(): void
    {
        $response = $this->actingAs($this->userBWithPerm)->postJson(route('projects.execution.store', $this->projectA->id), [
            'executive_activity_action_id' => $this->actionA->id,
            'status' => 'in_progress',
            'completion_percentage' => 50,
        ]);

        $response->assertStatus(403);
    }

    public function test_external_user_a_without_execution_permission_is_denied(): void
    {
        $response = $this->actingAs($this->userAWithoutPerm)->postJson(route('projects.execution.store', $this->projectA->id), [
            'executive_activity_action_id' => $this->actionA->id,
            'status' => 'in_progress',
            'completion_percentage' => 30,
        ]);

        $response->assertStatus(403);
    }

    public function test_internal_authorized_ministry_user_can_access_external_project_execution(): void
    {
        $response = $this->actingAs($this->ministryUser)->get(route('projects.execution', $this->projectA->id));
        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalDashboardScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Authority $authorityA;

    protected Authority $authorityB;

    protected InternalEntity $internalEntity;

    protected User $externalUserA;

    protected User $externalUserB;

    protected User $internalUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorityA = Authority::forceCreate([
            'agency_name' => 'هيئة تطوير تهامة',
            'is_active' => true,
        ]);

        $this->authorityB = Authority::forceCreate([
            'agency_name' => 'هيئة مياه الريف',
            'is_active' => true,
        ]);

        $this->internalEntity = InternalEntity::forceCreate([
            'name' => 'الإدارة العامة للتخطيط',
            'is_active' => true,
        ]);

        $permDashboard = Permission::firstOrCreate(['slug' => 'dashboard.view'], [
            'name' => 'عرض لوحة التحكم',
            'module' => 'dashboard',
            'type' => 'page',
        ]);

        $permMainModule = Permission::firstOrCreate(['slug' => 'main_modules.dashboard'], [
            'name' => 'الوحدة الرئيسية للوحة التحكم',
            'module' => 'main_modules',
            'type' => 'page',
        ]);
        PermissionResolver::clearCache();

        $role = Role::forceCreate([
            'name' => 'external_user_role',
            'is_active' => true,
            'full_access' => false,
        ]);

        RolePermission::forceCreate([
            'role_id' => $role->id,
            'permission_id' => $permDashboard->id,
            'role_legacy' => $role->name,
        ]);

        RolePermission::forceCreate([
            'role_id' => $role->id,
            'permission_id' => $permMainModule->id,
            'role_legacy' => $role->name,
        ]);

        $this->externalUserA = User::forceCreate([
            'user_id' => 'USR_EXT_A',
            'phone' => '770001001',
            'username' => 'user_ext_a',
            'name' => 'مستخدم هيئة تهامة',
            'email' => 'user_ext_a@test.gov',
            'password' => 'password',
            'authority_id' => $this->authorityA->id,
            'organization_type' => 'external',
            'role_id' => $role->id,
            'status' => 'Active',
        ]);

        $this->externalUserB = User::forceCreate([
            'user_id' => 'USR_EXT_B',
            'phone' => '770001002',
            'username' => 'user_ext_b',
            'name' => 'مستخدم هيئة الريف',
            'email' => 'user_ext_b@test.gov',
            'password' => 'password',
            'authority_id' => $this->authorityB->id,
            'organization_type' => 'external',
            'role_id' => $role->id,
            'status' => 'Active',
        ]);

        $this->internalUser = User::forceCreate([
            'user_id' => 'USR_INT',
            'phone' => '770001003',
            'username' => 'user_int',
            'name' => 'مستخدم التخطيط',
            'email' => 'user_int@test.gov',
            'password' => 'password',
            'entity_id' => $this->internalEntity->id,
            'organization_type' => 'internal',
            'status' => 'Active',
        ]);

        $this->adminUser = User::forceCreate([
            'user_id' => 'USR_ADMIN',
            'phone' => '770001004',
            'username' => 'admin_user',
            'name' => 'مدير النظام',
            'email' => 'admin@test.gov',
            'password' => 'password',
            'status' => 'Active',
            'is_admin' => true,
        ]);
    }

    public function test_external_user_dashboard_counts_only_their_authority_projects(): void
    {
        // 3 projects for Authority A
        Project::forceCreate([
            'project_name' => 'مشروع تهامة 1',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'completed',
        ]);
        Project::forceCreate([
            'project_name' => 'مشروع تهامة 2',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'in_execution',
        ]);
        Project::forceCreate([
            'project_name' => 'مشروع تهامة 3',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'under_review',
        ]);

        // 2 projects for Authority B
        Project::forceCreate([
            'project_name' => 'مشروع ريف 1',
            'source_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'status' => 'completed',
        ]);
        Project::forceCreate([
            'project_name' => 'مشروع ريف 2',
            'source_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'status' => 'under_review',
        ]);

        $response = $this->actingAs($this->externalUserA)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('projectCount', 3);
        $response->assertViewHas('completedProjectsCount', 1);
        $response->assertViewHas('underExecutionProjectsCount', 1);
        $response->assertViewHas('underReviewProjectsCount', 1);

        $recentProjects = $response->viewData('recentProjects');
        $this->assertCount(3, $recentProjects);
        $this->assertTrue($recentProjects->pluck('authority_id')->every(fn ($id) => (int) $id === (int) $this->authorityA->id));
    }

    public function test_cross_authority_projects_never_appear_on_other_authority_dashboard(): void
    {
        Project::forceCreate([
            'project_name' => 'مشروع سري لهيئة الريف',
            'source_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'status' => 'in_execution',
        ]);

        $response = $this->actingAs($this->externalUserA)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('projectCount', 0);
        $response->assertDontSee('مشروع سري لهيئة الريف');
    }

    public function test_admin_dashboard_sees_all_projects(): void
    {
        Project::forceCreate([
            'project_name' => 'مشروع تهامة',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'completed',
        ]);
        Project::forceCreate([
            'project_name' => 'مشروع الريف',
            'source_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'status' => 'completed',
        ]);
        Project::forceCreate([
            'project_name' => 'مشروع الوزارة',
            'source_type' => 'internal',
            'creator_entity_id' => $this->internalEntity->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('projectCount', 3);
        $response->assertViewHas('completedProjectsCount', 3);
    }
}

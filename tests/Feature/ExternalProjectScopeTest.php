<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalProjectScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Authority $authorityA;

    protected Authority $authorityB;

    protected InternalEntity $ministryEntity;

    protected User $externalUserA;

    protected User $externalUserB;

    protected User $internalMinistryUser;

    protected User $adminUser;

    protected Project $projectA;

    protected Project $projectB;

    protected Project $internalProject;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Permissions setup
        $permissions = [
            'dashboard.view' => ['name' => 'عرض لوحة التحكم', 'module' => 'dashboard', 'type' => 'page'],
            'main_modules.dashboard' => ['name' => 'الوحدة الرئيسية للوحة التحكم', 'module' => 'main_modules', 'type' => 'page'],
            'main_modules.projects' => ['name' => 'الوحدة الرئيسية للمشاريع', 'module' => 'main_modules', 'type' => 'page'],
            'projects.view' => ['name' => 'عرض المشاريع', 'module' => 'projects', 'type' => 'page'],
            'projects.view-details' => ['name' => 'عرض تفاصيل المشروع', 'module' => 'projects', 'type' => 'page'],
            'projects.edit' => ['name' => 'تعديل المشروع', 'module' => 'projects', 'type' => 'action'],
            'projects.create' => ['name' => 'إنشاء مشروع', 'module' => 'projects', 'type' => 'action'],
        ];

        foreach ($permissions as $slug => $data) {
            Permission::firstOrCreate(['slug' => $slug], $data);
        }
        PermissionResolver::clearCache();

        $standardRole = Role::forceCreate([
            'name' => 'standard_project_user_role',
            'is_active' => true,
            'full_access' => false,
        ]);

        foreach (Permission::all() as $perm) {
            RolePermission::forceCreate([
                'role_id' => $standardRole->id,
                'permission_id' => $perm->id,
                'role_legacy' => $standardRole->name,
            ]);
        }

        // 2. Entities & Authorities
        $this->authorityA = Authority::forceCreate([
            'agency_name' => 'هيئة تطوير تهامة',
            'is_active' => true,
        ]);

        $this->authorityB = Authority::forceCreate([
            'agency_name' => 'هيئة مياه الريف',
            'is_active' => true,
        ]);

        $this->ministryEntity = InternalEntity::forceCreate([
            'name' => 'الإدارة العامة للتخطيط والمتابعة',
            'entity_type' => 'Department',
            'is_active' => true,
            'is_ministry_root' => true,
        ]);

        // 3. Users
        $this->externalUserA = User::forceCreate([
            'user_id' => 'USR_EXT_A',
            'phone' => '770001001',
            'username' => 'user_ext_a',
            'name' => 'مستخدم هيئة تهامة',
            'email' => 'user_ext_a@test.gov',
            'password' => 'password',
            'authority_id' => $this->authorityA->id,
            'organization_type' => 'external',
            'role_id' => $standardRole->id,
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
            'role_id' => $standardRole->id,
            'status' => 'Active',
        ]);

        $this->internalMinistryUser = User::forceCreate([
            'user_id' => 'USR_MIN_INT',
            'phone' => '770001003',
            'username' => 'user_ministry_int',
            'name' => 'موظف تخطيط الوزارة',
            'email' => 'user_int@test.gov',
            'password' => 'password',
            'entity_id' => $this->ministryEntity->id,
            'organization_type' => 'internal',
            'role_id' => $standardRole->id,
            'status' => 'Active',
            'responsibility' => 'TECHNICAL',
        ]);

        $this->adminUser = User::forceCreate([
            'user_id' => 'USR_ADMIN_FULL',
            'phone' => '770001004',
            'username' => 'admin_user_full',
            'name' => 'مدير النظام',
            'email' => 'admin@test.gov',
            'password' => 'password',
            'status' => 'Active',
            'is_admin' => true,
        ]);

        // 4. Projects
        $this->projectA = Project::forceCreate([
            'project_name' => 'مشروع تهامة الزراعي المائي',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'under_review',
        ]);

        $this->projectB = Project::forceCreate([
            'project_name' => 'مشروع حفر آبار الريف',
            'source_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'status' => 'under_review',
        ]);

        $this->internalProject = Project::forceCreate([
            'project_name' => 'مشروع التخطيط الداخلي بالوزارة',
            'source_type' => 'internal',
            'creator_entity_id' => $this->ministryEntity->id,
            'status' => 'in_execution',
        ]);
    }

    /**
     * Category A: External Authority User can view projects index and only see own authority projects.
     */
    public function test_external_user_sees_only_own_authority_projects_in_index(): void
    {
        $response = $this->actingAs($this->externalUserA)->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertSee('مشروع تهامة الزراعي المائي');
        $response->assertDontSee('مشروع حفر آبار الريف');
    }

    /**
     * Category A: External Authority User can view own project details, but is denied viewing other authority project.
     */
    public function test_external_user_cannot_view_or_edit_other_authority_project(): void
    {
        // Own project -> Allowed
        $responseOwn = $this->actingAs($this->externalUserA)->get(route('projects.show', $this->projectA->id));
        $responseOwn->assertStatus(200);

        // Other authority project -> Denied (403)
        $responseOther = $this->actingAs($this->externalUserA)->get(route('projects.show', $this->projectB->id));
        $responseOther->assertStatus(403);

        // Edit other authority project -> Denied (403)
        $responseEdit = $this->actingAs($this->externalUserA)->get(route('projects.edit', $this->projectB->id));
        $responseEdit->assertStatus(403);
    }

    /**
     * Category B: Internal Ministry User sees external project when assigned to its approval stage.
     */
    public function test_internal_ministry_user_can_view_external_project_when_assigned_in_workflow(): void
    {
        ProjectApproval::forceCreate([
            'project_id' => $this->projectA->id,
            'entity_id' => $this->ministryEntity->id,
            'status' => 'pending',
            'step_order' => 1,
        ]);

        // Ministry user should be able to view this external project
        $response = $this->actingAs($this->internalMinistryUser)->get(route('projects.show', $this->projectA->id));
        $response->assertStatus(200);
    }

    /**
     * Category C: Admin / Full Access sees all projects.
     */
    public function test_admin_user_can_access_all_projects_across_entities_and_authorities(): void
    {
        $responseIndex = $this->actingAs($this->adminUser)->get(route('projects.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('مشروع تهامة الزراعي المائي');
        $responseIndex->assertSee('مشروع حفر آبار الريف');
        $responseIndex->assertSee('مشروع التخطيط الداخلي بالوزارة');

        $this->actingAs($this->adminUser)->get(route('projects.show', $this->projectA->id))->assertStatus(200);
        $this->actingAs($this->adminUser)->get(route('projects.show', $this->projectB->id))->assertStatus(200);
        $this->actingAs($this->adminUser)->get(route('projects.show', $this->internalProject->id))->assertStatus(200);
    }
}

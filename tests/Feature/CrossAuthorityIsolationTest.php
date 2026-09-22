<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossAuthorityIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Authority $authorityA;

    protected Authority $authorityB;

    protected User $externalUserA;

    protected User $externalUserB;

    protected Project $projectA;

    protected Project $projectB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup permissions
        $permissions = [
            'dashboard.view' => ['name' => 'عرض لوحة التحكم', 'module' => 'dashboard', 'type' => 'page'],
            'main_modules.dashboard' => ['name' => 'الوحدة الرئيسية للوحة التحكم', 'module' => 'main_modules', 'type' => 'page'],
            'main_modules.projects' => ['name' => 'الوحدة الرئيسية للمشاريع', 'module' => 'main_modules', 'type' => 'page'],
            'main_modules.correspondence' => ['name' => 'الوحدة الرئيسية للمراسلات', 'module' => 'main_modules', 'type' => 'page'],
            'projects.view' => ['name' => 'عرض المشاريع', 'module' => 'projects', 'type' => 'page'],
            'projects.view-details' => ['name' => 'عرض تفاصيل المشروع', 'module' => 'projects', 'type' => 'page'],
            'projects.edit' => ['name' => 'تعديل المشروع', 'module' => 'projects', 'type' => 'action'],
            'projects.delete' => ['name' => 'حذف المشروع', 'module' => 'projects', 'type' => 'action'],
            'projects.create' => ['name' => 'إنشاء مشروع', 'module' => 'projects', 'type' => 'action'],
            'consultations.view' => ['name' => 'عرض الاستشارات', 'module' => 'consultations', 'type' => 'page'],
            'consultations.create' => ['name' => 'إنشاء استشارة', 'module' => 'consultations', 'type' => 'action'],
            'execution.view' => ['name' => 'عرض التنفيذ', 'module' => 'execution', 'type' => 'page'],
            'execution.edit' => ['name' => 'تعديل التنفيذ', 'module' => 'execution', 'type' => 'action'],
        ];

        foreach ($permissions as $slug => $data) {
            Permission::firstOrCreate(['slug' => $slug], $data);
        }
        PermissionResolver::clearCache();

        $role = Role::forceCreate([
            'name' => 'cross_auth_test_role',
            'is_active' => true,
            'full_access' => false,
        ]);

        foreach (Permission::all() as $perm) {
            RolePermission::forceCreate([
                'role_id' => $role->id,
                'permission_id' => $perm->id,
                'role_legacy' => $role->name,
            ]);
        }

        // 2. Setup Authorities
        $this->authorityA = Authority::forceCreate([
            'agency_name' => 'هيئة تطوير تهامة',
            'is_active' => true,
        ]);

        $this->authorityB = Authority::forceCreate([
            'agency_name' => 'هيئة مياه الريف',
            'is_active' => true,
        ]);

        // 3. Setup External Users
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

        // 4. Setup Projects
        $this->projectA = Project::forceCreate([
            'project_name' => 'مشروع هيئة تهامة',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'in_execution',
        ]);

        $this->projectB = Project::forceCreate([
            'project_name' => 'مشروع هيئة الريف',
            'source_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'status' => 'in_execution',
        ]);
    }

    /**
     * Negative Test 1: User A cannot view Project B details.
     */
    public function test_user_a_cannot_view_project_b_show_page(): void
    {
        $response = $this->actingAs($this->externalUserA)->get(route('projects.show', $this->projectB->id));
        $response->assertStatus(403);
    }

    /**
     * Negative Test 2: User A cannot access edit form for Project B.
     */
    public function test_user_a_cannot_access_project_b_edit_page(): void
    {
        $response = $this->actingAs($this->externalUserA)->get(route('projects.edit', $this->projectB->id));
        $response->assertStatus(403);
    }

    /**
     * Negative Test 3: User A cannot send update request for Project B.
     */
    public function test_user_a_cannot_update_project_b(): void
    {
        $response = $this->actingAs($this->externalUserA)->put(route('projects.update', $this->projectB->id), [
            'project_name' => 'محاولة اختراق وتعديل اسم المشروع',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Negative Test 4: User A cannot delete Project B.
     */
    public function test_user_a_cannot_delete_project_b(): void
    {
        $response = $this->actingAs($this->externalUserA)->delete(route('projects.destroy', $this->projectB->id));
        $response->assertStatus(403);
    }

    /**
     * Negative Test 5: User A cannot view Project B card or financial printout.
     */
    public function test_user_a_cannot_view_project_b_card_or_financial_report(): void
    {
        $this->actingAs($this->externalUserA)->get(route('projects.card.show', $this->projectB->id))->assertStatus(403);
        $this->actingAs($this->externalUserA)->get(route('projects.print-financial', $this->projectB->id))->assertStatus(403);
    }
}

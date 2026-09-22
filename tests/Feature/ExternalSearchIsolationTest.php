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

class ExternalSearchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Authority $authorityA;

    protected Authority $authorityB;

    protected User $userA;

    protected User $userB;

    protected User $adminUser;

    protected Project $projectA;

    protected Project $projectB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Permissions
        $permissions = [
            'dashboard.view' => ['name' => 'عرض لوحة التحكم', 'module' => 'dashboard', 'type' => 'page'],
            'main_modules.dashboard' => ['name' => 'الوحدة الرئيسية للوحة التحكم', 'module' => 'main_modules', 'type' => 'page'],
            'main_modules.correspondence' => ['name' => 'الوحدة الرئيسية للمراسلات', 'module' => 'main_modules', 'type' => 'page'],
            'correspondence.view' => ['name' => 'عرض المراسلات', 'module' => 'correspondence', 'type' => 'page'],
            'projects.view' => ['name' => 'عرض المشاريع', 'module' => 'projects', 'type' => 'page'],
        ];

        foreach ($permissions as $slug => $data) {
            Permission::firstOrCreate(['slug' => $slug], $data);
        }
        PermissionResolver::clearCache();

        $role = Role::forceCreate([
            'name' => 'search_test_role',
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

        // 3. Setup Users
        $this->userA = User::forceCreate([
            'user_id' => 'USR_SEARCH_A',
            'phone' => '770001001',
            'username' => 'user_search_a',
            'name' => 'مستخدم بحث تهامة',
            'email' => 'search_a@test.gov',
            'password' => 'password',
            'authority_id' => $this->authorityA->id,
            'organization_type' => 'external',
            'role_id' => $role->id,
            'status' => 'Active',
        ]);

        $this->userB = User::forceCreate([
            'user_id' => 'USR_SEARCH_B',
            'phone' => '770001002',
            'username' => 'user_search_b',
            'name' => 'مستخدم بحث الريف',
            'email' => 'search_b@test.gov',
            'password' => 'password',
            'authority_id' => $this->authorityB->id,
            'organization_type' => 'external',
            'role_id' => $role->id,
            'status' => 'Active',
        ]);

        $this->adminUser = User::forceCreate([
            'user_id' => 'USR_SEARCH_ADM',
            'phone' => '770001003',
            'username' => 'admin_search',
            'name' => 'مدير النظام',
            'email' => 'admin_search@test.gov',
            'password' => 'password',
            'status' => 'Active',
            'is_admin' => true,
        ]);

        // 4. Setup Projects
        $this->projectA = Project::forceCreate([
            'project_name' => 'مشروع تهامة المائي 2026',
            'form_number' => 'PRJ-TIH-001',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'in_execution',
        ]);

        $this->projectB = Project::forceCreate([
            'project_name' => 'مشروع الريف المائي 2026',
            'form_number' => 'PRJ-REEF-002',
            'source_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'status' => 'in_execution',
        ]);
    }

    /**
     * Test 1: User A searches for their own project keyword and receives matching results.
     */
    public function test_user_a_can_search_and_find_own_authority_projects(): void
    {
        $response = $this->actingAs($this->userA)->getJson(route('correspondence.search-projects', ['q' => 'تهامة']));

        $response->assertStatus(200);
        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertEquals($this->projectA->id, $results[0]['id']);
    }

    /**
     * Test 2: User A searches for other authority keyword — receives NO results (Strict Isolation).
     */
    public function test_user_a_searching_other_authority_keyword_returns_empty_results(): void
    {
        $response = $this->actingAs($this->userA)->getJson(route('correspondence.search-projects', ['q' => 'الريف']));

        $response->assertStatus(200);
        $results = $response->json('results');
        $this->assertEmpty($results);
    }

    /**
     * Test 3: Admin searches and receives projects from all authorities.
     */
    public function test_admin_searching_receives_projects_across_all_authorities(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('correspondence.search-projects', ['q' => 'المائي']));

        $response->assertStatus(200);
        $results = $response->json('results');
        $this->assertCount(2, $results);
    }
}

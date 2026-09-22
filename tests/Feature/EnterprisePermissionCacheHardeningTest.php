<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnterprisePermissionCacheHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionResolver::clearCache();
        Cache::flush();
    }

    /**
     * Scenario 1:
     * User has Role A (primary) + Role B (secondary).
     * Modifying a permission in Role B must take effect immediately for the user (O(1) version invalidation).
     */
    public function test_scenario_1_multi_role_user_reflects_secondary_role_permission_update_immediately(): void
    {
        $permA = Permission::firstOrCreate(
            ['slug' => 'projects.view'],
            ['name' => 'View Projects', 'module' => 'projects', 'type' => 'view']
        );
        $permB = Permission::firstOrCreate(
            ['slug' => 'reports.view'],
            ['name' => 'View Reports', 'module' => 'reports', 'type' => 'view']
        );
        $permB2 = Permission::firstOrCreate(
            ['slug' => 'planning.view'],
            ['name' => 'View Planning', 'module' => 'planning', 'type' => 'view']
        );

        $roleA = Role::create(['name' => 'PrimaryRole_A', 'is_active' => true]);
        $roleB = Role::create(['name' => 'SecondaryRole_B', 'is_active' => true]);

        RolePermission::create(['role_id' => $roleA->id, 'permission_id' => $permA->id]);
        RolePermission::create(['role_id' => $roleB->id, 'permission_id' => $permB->id]);

        $user = User::factory()->create(['role_id' => $roleA->id]);

        if (Schema::hasTable('user_roles')) {
            UserRole::create(['user_id' => $user->id, 'role_id' => $roleB->id]);
        }

        // Initially: has permA and permB
        $slugs = $user->getAllPermissionsSlugs();
        $this->assertContains('projects.view', $slugs);
        $this->assertContains('reports.view', $slugs);
        $this->assertNotContains('planning.view', $slugs);

        // Add permB2 to Role B and increment version
        RolePermission::create(['role_id' => $roleB->id, 'permission_id' => $permB2->id]);
        User::incrementRolePermissionsVersion($roleB->id);

        // Clear in-memory instance cache to simulate next request
        $user->clearPermissionCache();

        // Fresh request check: must have planning.view immediately
        $updatedSlugs = $user->getAllPermissionsSlugs();
        $this->assertContains('planning.view', $updatedSlugs);
        $this->assertContains('reports.view', $updatedSlugs);
        $this->assertContains('projects.view', $updatedSlugs);
    }

    /**
     * Scenario 2:
     * Changing a user's role from Role A to Role B (where both roles share default version 1).
     * The user must immediately receive Role B permissions and never inherit stale Role A cache.
     */
    public function test_scenario_2_role_transition_never_collides_or_inherits_stale_cache(): void
    {
        $permA = Permission::firstOrCreate(
            ['slug' => 'projects.view'],
            ['name' => 'View Projects', 'module' => 'projects', 'type' => 'view']
        );
        $permB = Permission::firstOrCreate(
            ['slug' => 'reports.view'],
            ['name' => 'View Reports', 'module' => 'reports', 'type' => 'view']
        );

        $roleA = Role::create(['name' => 'DepartmentRole_A', 'is_active' => true]);
        $roleB = Role::create(['name' => 'FinanceRole_B', 'is_active' => true]);

        RolePermission::create(['role_id' => $roleA->id, 'permission_id' => $permA->id]);
        RolePermission::create(['role_id' => $roleB->id, 'permission_id' => $permB->id]);

        $user = User::factory()->create(['role_id' => $roleA->id]);

        // Access under Role A -> caches Role A slugs
        $slugsA = $user->getAllPermissionsSlugs();
        $this->assertContains('projects.view', $slugsA);
        $this->assertNotContains('reports.view', $slugsA);

        // Switch user to Role B
        $user->role_id = $roleB->id;
        $user->save();
        $user->clearPermissionCache();

        // Must strictly have Role B permissions
        $slugsB = $user->getAllPermissionsSlugs();
        $this->assertContains('reports.view', $slugsB);
        $this->assertNotContains('projects.view', $slugsB);
    }

    /**
     * Scenario 3:
     * Removing a secondary role from user immediately revokes its permissions.
     */
    public function test_scenario_3_removing_secondary_role_immediately_revokes_permissions(): void
    {
        if (! Schema::hasTable('user_roles')) {
            $this->markTestSkipped('user_roles table not present.');
        }

        $permA = Permission::firstOrCreate(
            ['slug' => 'projects.view'],
            ['name' => 'View Projects', 'module' => 'projects', 'type' => 'view']
        );
        $permB = Permission::firstOrCreate(
            ['slug' => 'sms.view'],
            ['name' => 'View SMS', 'module' => 'sms', 'type' => 'view']
        );

        $roleA = Role::create(['name' => 'StandardRole_A', 'is_active' => true]);
        $roleB = Role::create(['name' => 'SmsManagerRole_B', 'is_active' => true]);

        RolePermission::create(['role_id' => $roleA->id, 'permission_id' => $permA->id]);
        RolePermission::create(['role_id' => $roleB->id, 'permission_id' => $permB->id]);

        $user = User::factory()->create(['role_id' => $roleA->id]);
        $userRole = UserRole::create(['user_id' => $user->id, 'role_id' => $roleB->id]);

        $slugsWithBoth = $user->getAllPermissionsSlugs();
        $this->assertContains('projects.view', $slugsWithBoth);
        $this->assertContains('sms.view', $slugsWithBoth);

        // Detach secondary role
        $userRole->delete();
        $user->unsetRelation('userRoles');
        $user->clearPermissionCache();

        $slugsAfterDetach = $user->getAllPermissionsSlugs();
        $this->assertContains('projects.view', $slugsAfterDetach);
        $this->assertNotContains('sms.view', $slugsAfterDetach);
    }

    /**
     * Scenario 4:
     * Non-regression test for Admin, Scope checks, and Deterministic Hash stability.
     */
    public function test_scenario_4_admin_bypass_and_deterministic_hash_stability(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $adminSlugs = $admin->getAllPermissionsSlugs();
        $this->assertNotEmpty($adminSlugs);
        $this->assertEquals(PermissionResolver::getMatrixSlugs(), $adminSlugs);

        $role = Role::create(['name' => 'TestHashRole', 'is_active' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $hash1 = $user->getCompositePermissionsVersionHash();
        $hash2 = $user->getCompositePermissionsVersionHash();
        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(32, strlen($hash1)); // Fixed length MD5 hash
    }
}

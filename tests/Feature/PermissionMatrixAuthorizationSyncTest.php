<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermissionMatrixAuthorizationSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        $this->seed(PermissionsTableSeeder::class);
        PermissionResolver::clearCache();
        Cache::flush();
    }

    /**
     * TEST 1:
     * Grant permission to Role -> User gets access -> HTTP 200/expected success.
     */
    public function test_1_grant_permission_gives_non_admin_user_http_200_access(): void
    {
        $perm = Permission::firstOrCreate(
            ['slug' => 'projects.view'],
            ['name' => 'View Projects', 'module' => 'projects', 'type' => 'view']
        );

        $role = Role::create(['name' => 'FieldOfficer', 'is_active' => true]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('projects.index'));
        $response->assertStatus(200);
    }

    /**
     * TEST 2:
     * Revoke same permission -> Same User loses access immediately -> HTTP 403.
     */
    public function test_2_revoke_permission_denies_access_immediately_with_403(): void
    {
        $perm = Permission::firstOrCreate(
            ['slug' => 'projects.view'],
            ['name' => 'View Projects', 'module' => 'projects', 'type' => 'view']
        );

        $role = Role::create(['name' => 'FieldOfficer', 'is_active' => true]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);

        // First: allowed (200)
        $this->actingAs($user)->get(route('projects.index'))->assertStatus(200);

        // Revoke permission from role and increment version
        RolePermission::where('role_id', $role->id)->where('permission_id', $perm->id)->delete();
        User::incrementRolePermissionsVersion($role->id);
        $user->clearPermissionCache();

        // Access immediately after revocation -> must be 403 without cache:clear
        $response = $this->actingAs($user)->get(route('projects.index'));
        $response->assertStatus(403);
    }

    /**
     * TEST 3:
     * Grant view but not create -> index/show works (200) -> create/store denied (403).
     */
    public function test_3_grant_view_only_allows_read_and_denies_create_with_403(): void
    {
        $viewPerm = Permission::firstOrCreate(
            ['slug' => 'projects.view'],
            ['name' => 'View Projects', 'module' => 'projects', 'type' => 'view']
        );

        $role = Role::create(['name' => 'ViewerRole', 'is_active' => true]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $viewPerm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);

        // View allowed
        $this->actingAs($user)->get(route('projects.index'))->assertStatus(200);

        // Create form denied (403)
        $this->actingAs($user)->get(route('projects.create'))->assertStatus(403);

        // Store request denied (403)
        $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Unauthorized Project Test',
        ])->assertStatus(403);
    }

    /**
     * TEST 4:
     * Grant create but not update/delete -> create works -> update/delete denied (403).
     */
    public function test_4_grant_create_allows_create_and_denies_delete_with_403(): void
    {
        $createPerm = Permission::firstOrCreate(
            ['slug' => 'projects.create'],
            ['name' => 'Create Projects', 'module' => 'projects', 'type' => 'create']
        );

        $role = Role::create(['name' => 'CreatorRole', 'is_active' => true]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $createPerm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);

        // Create form allowed (200)
        $this->actingAs($user)->get(route('projects.create'))->assertStatus(200);

        // Delete endpoint denied (403)
        $project = Project::factory()->create(['created_by_user_id' => $user->id]);
        $this->actingAs($user)->delete(route('projects.destroy', $project))->assertStatus(403);
    }

    /**
     * TEST 5:
     * Multi-role user -> Permission inherited from secondary Role works -> Detach secondary Role -> Access disappears immediately.
     */
    public function test_5_multi_role_inheritance_and_immediate_detachment(): void
    {
        if (! Schema::hasTable('user_roles')) {
            $this->markTestSkipped('user_roles table not present.');
        }

        $viewPerm = Permission::firstOrCreate(
            ['slug' => 'projects.view'],
            ['name' => 'View Projects', 'module' => 'projects', 'type' => 'view']
        );
        $plansPerm = Permission::firstOrCreate(
            ['slug' => 'plans.view'],
            ['name' => 'View Plans', 'module' => 'plans', 'type' => 'view']
        );

        $primaryRole = Role::create(['name' => 'OfficerPrimary', 'is_active' => true]);
        $secondaryRole = Role::create(['name' => 'PlannerSecondary', 'is_active' => true]);

        RolePermission::create(['role_id' => $primaryRole->id, 'permission_id' => $viewPerm->id]);
        RolePermission::create(['role_id' => $secondaryRole->id, 'permission_id' => $plansPerm->id]);

        $user = User::factory()->create(['role_id' => $primaryRole->id]);
        $userRole = UserRole::create(['user_id' => $user->id, 'role_id' => $secondaryRole->id]);

        // Access planning through secondary role (200)
        $this->actingAs($user)->get(route('plans.index'))->assertStatus(200);

        // Detach secondary role
        $userRole->delete();
        $user->unsetRelation('userRoles');
        $user->clearPermissionCache();

        // Access planning immediately denied (403)
        $this->actingAs($user)->get(route('plans.index'))->assertStatus(403);
    }

    /**
     * TEST 6:
     * Permission Cache -> Grant/revoke works dynamically without optimize:clear or cache:clear.
     */
    public function test_6_dynamic_grant_and_revoke_without_cache_clear(): void
    {
        $perm = Permission::where('slug', 'plans.view')->first();

        $role = Role::create(['name' => 'DynamicRole', 'is_active' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        // Initially no permission -> 403
        $this->actingAs($user)->get(route('plans.index'))->assertStatus(403);

        // Grant permission dynamically
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);
        User::incrementRolePermissionsVersion($role->id);
        $user->clearPermissionCache();

        // Next request immediately allowed (200) without cache:clear
        $this->actingAs($user)->get(route('plans.index'))->assertStatus(200);

        // Revoke permission dynamically
        RolePermission::where('role_id', $role->id)->where('permission_id', $perm->id)->delete();
        User::incrementRolePermissionsVersion($role->id);
        $user->clearPermissionCache();

        // Next request immediately denied (403) without cache:clear
        $this->actingAs($user)->get(route('plans.index'))->assertStatus(403);
    }
}

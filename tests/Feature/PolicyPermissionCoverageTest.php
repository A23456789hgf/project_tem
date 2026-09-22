<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyPermissionCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        $this->seed(PermissionsTableSeeder::class);
        PermissionResolver::clearCache();
    }

    /**
     * Test that ProjectPolicy respects granted view permissions.
     */
    public function test_project_policy_allows_authorized_user(): void
    {
        $role = Role::create(['name' => 'FieldAuditor_'.uniqid(), 'is_active' => true]);
        $perm = Permission::where('slug', 'projects.view')->firstOrFail();
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);

        $user = User::factory()->create(['role_id' => $role->id]);
        User::incrementRolePermissionsVersion($role->id);

        $policy = new ProjectPolicy;
        $this->assertTrue($policy->viewAny($user));
    }

    /**
     * Test that ProjectPolicy denies unauthorized user without projects.view.
     */
    public function test_project_policy_denies_unauthorized_user(): void
    {
        $role = Role::create(['name' => 'RestrictedAuditor_'.uniqid(), 'is_active' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);
        User::incrementRolePermissionsVersion($role->id);

        $policy = new ProjectPolicy;
        $this->assertFalse($policy->viewAny($user));
    }
}

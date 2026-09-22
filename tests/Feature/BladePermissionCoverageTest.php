<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BladePermissionCoverageTest extends TestCase
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
     * Test that unauthorized roles have dynamic CSS generated to hide permission-protected UI elements.
     */
    public function test_dynamic_permission_css_hides_unauthorized_elements(): void
    {
        $role = Role::create(['name' => 'RestrictedViewUser_'.uniqid(), 'is_active' => true, 'full_access' => false]);
        $viewPerm = Permission::where('slug', 'projects.view')->firstOrFail();
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $viewPerm->id]);

        $user = User::factory()->create(['role_id' => $role->id]);
        User::incrementRolePermissionsVersion($role->id);

        $response = $this->actingAs($user)->get(route('projects.index'));
        $response->assertStatus(200);

        // Verify that disabled permissions CSS is included in the page output
        $content = $response->getContent();
        $this->assertStringContainsString('display: none !important', $content);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExternalUserOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_external_user_can_login_and_keeps_external_organization_context(): void
    {
        $role = Role::create(['name' => 'External Manager', 'is_active' => true]);
        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'dashboard.view',
            'module' => 'dashboard',
            'type' => 'page',
        ]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $permission->id]);
        $authority = Authority::create(['agency_name' => 'External Authority', 'is_active' => true]);
        $user = User::create([
            'username' => 'external-user',
            'user_id' => 'EXT-1001',
            'name' => 'External User',
            'phone' => '777111222',
            'password' => Hash::make('SecretPass123'),
            'role_id' => $role->id,
            'status' => 'Active',
            'must_change_password' => false,
            'organization_type' => 'external',
            'entity_id' => null,
            'authority_id' => $authority->id,
        ]);

        $response = $this->post(route('auth.login'), [
            'phone' => '777111222',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('external', auth()->user()->organization()['type']);
        $this->assertSame($authority->id, auth()->user()->organization()['id']);
    }
}

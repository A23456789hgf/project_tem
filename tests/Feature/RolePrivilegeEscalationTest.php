<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'عرض الأدوار', 'slug' => 'roles-permissions.view', 'module' => 'roles-permissions', 'type' => 'page']);
        Permission::create(['name' => 'تعديل الأدوار', 'slug' => 'roles-permissions.edit', 'module' => 'roles-permissions', 'type' => 'action']);
    }

    public function test_non_admin_user_cannot_access_roles_management(): void
    {
        $role = Role::create([
            'name' => 'LimitedUserRole_'.uniqid(),
            'description' => 'Limited User',
            'is_active' => true,
            'full_access' => false,
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'is_admin' => false,
        ]);

        $this->actingAs($user);

        // RoleController is protected by middleware(['auth', 'role:Admin'])
        $this->get(route('roles.index'))->assertStatus(403);
        $this->get(route('roles.create'))->assertStatus(403);
        $this->post(route('roles.store'), ['name' => 'HackerRole'])->assertStatus(403);
        $this->post(route('roles.update-permissions'), ['role_id' => $role->id])->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $role;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->role = Role::create([
            'name' => 'Admin',
            'is_active' => true,
            'full_access' => true,
        ]);

        // create a super admin to perform the actions
        $this->admin = User::create([
            'user_id' => 'admin1',
            'username' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '111111111',
            'password' => 'password',
            'status' => 'Active',
            'role_id' => $this->role->id,
            'is_admin' => true,
            'organization_type' => 'internal',
            'responsibility' => null,
        ]);
    }

    public function test_can_create_internal_user_with_entity_id()
    {
        $entity = InternalEntity::create(['name' => 'Ministry', 'entity_type' => 'Company', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Internal User',
            'username' => 'internal_user',
            'email' => 'int@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->role->id,
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'status' => 'Active',
            'responsibility' => 'ENTITY_APPROVER',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'Internal User',
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'authority_id' => null,
        ]);
    }

    public function test_fails_internal_user_creation_without_entity_id()
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Internal User',
            'username' => 'internal_user2',
            'email' => 'int2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->role->id,
            'organization_type' => 'internal',
            'entity_id' => '', // missing
            'status' => 'Active',
            'responsibility' => 'ENTITY_APPROVER',
        ]);

        $response->assertSessionHasErrors('entity_id');
    }

    public function test_can_create_external_user_with_authority_id()
    {
        $authority = Authority::create(['name' => 'Authority X', 'agency_name' => 'Authority X Agency', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'External User',
            'username' => 'external_user',
            'email' => 'ext@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->role->id,
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'status' => 'Active',
            'responsibility' => 'ENTITY_APPROVER',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'External User',
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'entity_id' => null,
        ]);
    }

    public function test_fails_external_user_creation_without_authority_id()
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'External User',
            'username' => 'external_user2',
            'email' => 'ext2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->role->id,
            'organization_type' => 'external',
            'authority_id' => '', // missing
            'status' => 'Active',
            'responsibility' => 'ENTITY_APPROVER',
        ]);

        $response->assertSessionHasErrors('authority_id');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Directorate;
use App\Models\Governorate;
use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExternalUserTest extends TestCase
{
    use RefreshDatabase;

    protected $roleId;

    protected $governorateId;

    protected $directorateId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleId = Role::insertGetId([
            'name' => 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->governorateId = Governorate::insertGetId([
            'name' => 'Test Gov',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->directorateId = Directorate::insertGetId([
            'name' => 'Test Dir',
            'governorate_id' => $this->governorateId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_can_create_internal_user()
    {
        $entity = InternalEntity::factory()->create();

        $user = User::factory()->create([
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'authority_id' => null,
            'role_id' => $this->roleId,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'authority_id' => null,
        ]);
        $this->assertTrue($user->isInternal());
        $this->assertFalse($user->isExternal());
    }

    public function test_can_create_external_user()
    {
        $authorityId = Authority::insertGetId([
            'agency_name' => 'Test Auth',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'organization_type' => 'external',
            'authority_id' => $authorityId,
            'entity_id' => null,
            'role_id' => $this->roleId,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'organization_type' => 'external',
            'authority_id' => $authorityId,
            'entity_id' => null,
        ]);
        $this->assertTrue($user->isExternal());
        $this->assertFalse($user->isInternal());
    }

    public function test_prevents_saving_entity_id_with_authority_id()
    {
        $entity = InternalEntity::factory()->create();
        $authorityId = Authority::insertGetId([
            'agency_name' => 'Test Auth',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'organization_type' => 'external',
            'entity_id' => $entity->id,
            'authority_id' => $authorityId,
            'role_id' => $this->roleId,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'organization_type' => 'external',
            'authority_id' => $authorityId,
            'entity_id' => null,
        ]);
    }

    public function test_internal_user_can_login()
    {
        $user = User::factory()->create([
            'organization_type' => 'internal',
            'username' => 'internal_user_test',
            'password' => Hash::make('password123'),
            'status' => 'Active',
            'role_id' => $this->roleId,
        ]);

        $response = $this->post('/login', [
            'username' => 'internal_user_test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_external_user_can_login()
    {
        $user = User::factory()->create([
            'organization_type' => 'external',
            'username' => 'external_user_test',
            'password' => Hash::make('password123'),
            'status' => 'Active',
            'role_id' => $this->roleId,
        ]);

        $response = $this->post('/login', [
            'username' => 'external_user_test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_returns_correct_organization()
    {
        $entity = InternalEntity::factory()->create();
        $authorityId = Authority::insertGetId([
            'agency_name' => 'Test Auth',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $internalUser = User::factory()->create([
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'authority_id' => null,
        ]);

        $externalUser = User::factory()->create([
            'organization_type' => 'external',
            'authority_id' => $authorityId,
            'entity_id' => null,
        ]);

        $internalOrg = $internalUser->organization();
        $this->assertEquals(OrganizationResolver::INTERNAL, $internalOrg['type']);
        $this->assertEquals($entity->id, $internalOrg['id']);
        $this->assertInstanceOf(InternalEntity::class, $internalOrg['model']);

        $externalOrg = $externalUser->organization();
        $this->assertEquals(OrganizationResolver::EXTERNAL, $externalOrg['type']);
        $this->assertEquals($authorityId, $externalOrg['id']);
        $this->assertInstanceOf(Authority::class, $externalOrg['model']);
    }

    public function test_external_user_geographic_scope_applied()
    {
        $authorityId = Authority::insertGetId([
            'agency_name' => 'Test Auth',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'organization_type' => 'external',
            'authority_id' => $authorityId,
            'entity_id' => null,
            'role_id' => $this->roleId,
            'status' => 'Active',
        ]);

        $user->geographicScopes()->create([
            'governorate_id' => $this->governorateId,
            'directorate_id' => $this->directorateId,
        ]);

        $resolver = new OrganizationResolver;

        $this->assertTrue($resolver->hasGeographicScope($user));
        $this->assertContains($this->governorateId, $resolver->governorateIds($user));
        $this->assertContains($this->directorateId, $resolver->directorateIds($user));
    }

    public function test_external_user_administrative_scope_applied()
    {
        $authorityParentId = Authority::insertGetId([
            'agency_name' => 'Test Auth Parent',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $authorityChildId = Authority::insertGetId([
            'agency_name' => 'Test Auth Child',
            'parent_id' => $authorityParentId,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'organization_type' => 'external',
            'authority_id' => $authorityParentId,
            'administrative_scope_id' => $authorityParentId,
            'entity_id' => null,
            'role_id' => $this->roleId,
        ]);

        $resolver = new OrganizationResolver;
        $adminIds = $resolver->administrativeOrganizationIds($user);

        $this->assertContains($authorityParentId, $adminIds);
        $this->assertContains($authorityChildId, $adminIds);
    }
}

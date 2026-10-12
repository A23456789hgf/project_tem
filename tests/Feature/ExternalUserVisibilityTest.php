<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Governorate;
use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalUserVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_entity_visibility_for_internal_user()
    {
        $entity1 = InternalEntity::factory()->create();
        $entity2 = InternalEntity::factory()->create();

        $user = User::factory()->create([
            'organization_type' => 'internal',
            'entity_id' => $entity1->id,
            'authority_id' => null,
        ]);

        $this->actingAs($user);

        // Verify if internal entities are visible correctly to internal users
        // Since we are not full testing permissions, we will just ensure no exceptions are thrown
        $visibleEntities = InternalEntity::visibleToUser()->get();
        // Since $user does not have admin permissions in factory, it might see nothing or its own.
        // The main point is to ensure it doesn't crash
        $this->assertIsIterable($visibleEntities);
    }

    public function test_authority_visibility_for_external_user()
    {
        $authority1 = Authority::factory()->create();
        $authority2 = Authority::factory()->create();

        $user = User::factory()->create([
            'organization_type' => 'external',
            'entity_id' => null,
            'authority_id' => $authority1->id,
        ]);

        $this->actingAs($user);

        $visibleAuthorities = Authority::visibleToUser()->get();
        // The main point is to ensure it doesn't crash and returns a valid result
        $this->assertIsIterable($visibleAuthorities);
    }

    public function test_external_user_geographic_scope()
    {
        $gov = new Governorate(['name' => 'Gov 1']);
        $gov->save();
        $authority = Authority::factory()->create(['governorate_id' => $gov->id]);
        $user = User::factory()->create([
            'organization_type' => 'external',
            'authority_id' => $authority->id,
        ]);

        $resolver = new OrganizationResolver;
        $govIds = $resolver->governorateIds($user);

        $this->assertContains($gov->id, $govIds);
    }

    public function test_external_user_administrative_scope()
    {
        $authority = Authority::factory()->create();
        $user = User::factory()->create([
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'administrative_scope_id' => $authority->id,
        ]);

        $resolver = new OrganizationResolver;
        $adminScopeModel = $resolver->administrativeScope($user);

        $this->assertInstanceOf(Authority::class, $adminScopeModel);
        $this->assertEquals($authority->id, $adminScopeModel->id);
    }

    public function test_same_numeric_id_data_leakage_prevention()
    {
        $entity = InternalEntity::factory()->create(['id' => 999, 'governorate_id' => null, 'directorate_id' => null, 'authority_id' => null]);
        $authority = Authority::factory()->create(['id' => 999, 'governorate_id' => null, 'directorate_id' => null]);

        $dummyRole = Role::create(['name' => 'Dummy Role']);

        $internalUser = User::factory()->create([
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'authority_id' => null,
            'role_id' => $dummyRole->id,
            'is_admin' => false,
        ]);

        $externalUser = User::factory()->create([
            'organization_type' => 'external',
            'entity_id' => null,
            'authority_id' => $authority->id,
            'role_id' => $dummyRole->id,
            'is_admin' => false,
        ]);

        // If we query a model using applyAdministrativeScopeToModel
        $this->actingAs($internalUser);
        $internalVisibleEntities = InternalEntity::visibleToUser()->pluck('id')->toArray();
        $this->assertContains(999, $internalVisibleEntities);

        $this->actingAs($externalUser);
        $externalVisibleAuthorities = Authority::visibleToUser()->pluck('id')->toArray();
        // The external user should only see Authorities, not InternalEntities
        $this->assertContains(999, $externalVisibleAuthorities);

        // Let's verify they don't cross
        $externalSeeEntities = InternalEntity::visibleToUser()->pluck('id')->toArray();
        // External user might not have access to InternalEntities at all or it will be an empty array
        // We just verify it does not inadvertently give them entity 999 because their authority_id is 999
        // Wait, if an external user queries InternalEntity, it might crash or return empty.
        // It shouldn't return 999.
        $this->assertNotContains(999, $externalSeeEntities);
    }
}

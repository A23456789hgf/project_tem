<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Governorate;
use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGeographicScope;
use App\Services\OrganizationResolver;
use App\Services\ScopeFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_internal_and_external_organizations_without_merging_storage(): void
    {
        $entity = InternalEntity::factory()->create();
        $authority = Authority::create(['agency_name' => 'External Authority', 'is_active' => true]);

        $internalUser = User::factory()->create([
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'authority_id' => null,
        ]);
        $externalUser = User::factory()->create([
            'organization_type' => 'external',
            'entity_id' => null,
            'authority_id' => $authority->id,
        ]);

        $this->assertSame('internal', $internalUser->organization()['type']);
        $this->assertSame($entity->id, $internalUser->organization()['id']);
        $this->assertTrue($internalUser->organization()['model']->is($entity));
        $this->assertSame('external', $externalUser->organization()['type']);
        $this->assertSame($authority->id, $externalUser->organization()['id']);
        $this->assertTrue($externalUser->organization()['model']->is($authority));
    }

    public function test_user_model_enforces_mutually_exclusive_organization_foreign_keys(): void
    {
        $entity = InternalEntity::factory()->create();
        $authority = Authority::create(['agency_name' => 'External Authority', 'is_active' => true]);

        $externalUser = User::factory()->create([
            'organization_type' => 'external',
            'entity_id' => $entity->id,
            'authority_id' => $authority->id,
        ]);
        $internalUser = User::factory()->create([
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'authority_id' => $authority->id,
        ]);

        $this->assertNull($externalUser->entity_id);
        $this->assertSame($authority->id, $externalUser->authority_id);
        $this->assertSame($entity->id, $internalUser->entity_id);
        $this->assertNull($internalUser->authority_id);
    }

    public function test_external_administrative_scope_exposes_only_its_authority_tree(): void
    {
        $role = Role::create(['name' => 'Scoped External', 'is_active' => true]);
        $root = Authority::create(['agency_name' => 'Root', 'is_active' => true]);
        $child = Authority::create(['agency_name' => 'Child', 'parent_id' => $root->id, 'is_active' => true]);
        Authority::create(['agency_name' => 'Unrelated', 'is_active' => true]);
        $user = User::factory()->create([
            'organization_type' => 'external',
            'entity_id' => null,
            'authority_id' => $root->id,
            'administrative_scope_id' => $root->id,
            'role_id' => $role->id,
            'is_admin' => false,
        ]);

        $query = Authority::withoutGlobalScopes();
        app(OrganizationResolver::class)->applyVisibleAuthorities($query, $user);

        $this->assertEqualsCanonicalizing([$root->id, $child->id], $query->pluck('id')->all());
    }

    public function test_external_geographic_scope_exposes_external_and_internal_organizations_in_scope(): void
    {
        $role = Role::create(['name' => 'Geographic External', 'is_active' => true]);
        $governorate = Governorate::create(['name' => 'Scoped Governorate', 'is_active' => true]);
        $otherGovernorate = Governorate::create(['name' => 'Other Governorate', 'is_active' => true]);
        $homeAuthority = Authority::create(['agency_name' => 'Home', 'is_active' => true]);
        $visibleAuthority = Authority::create(['agency_name' => 'Visible External', 'governorate_id' => $governorate->id, 'is_active' => true]);
        Authority::create(['agency_name' => 'Hidden External', 'governorate_id' => $otherGovernorate->id, 'is_active' => true]);
        $visibleEntity = InternalEntity::factory()->create(['governorate_id' => $governorate->id]);
        InternalEntity::factory()->create(['governorate_id' => $otherGovernorate->id]);
        $user = User::factory()->create([
            'organization_type' => 'external',
            'entity_id' => null,
            'authority_id' => $homeAuthority->id,
            'role_id' => $role->id,
            'is_admin' => false,
        ]);
        UserGeographicScope::create(['user_id' => $user->id, 'governorate_id' => $governorate->id]);

        $authorityQuery = Authority::withoutGlobalScopes();
        $entityQuery = InternalEntity::withoutGlobalScopes();
        $resolver = app(OrganizationResolver::class);
        $resolver->applyVisibleAuthorities($authorityQuery, $user->fresh());
        $resolver->applyVisibleInternalEntities($entityQuery, $user->fresh());

        $this->assertSame([$visibleAuthority->id], $authorityQuery->pluck('id')->all());
        $this->assertSame([$visibleEntity->id], $entityQuery->pluck('id')->all());

        $scopedOrganizations = app(ScopeFilterService::class)->getScopedEntities($user->fresh());
        $this->assertEqualsCanonicalizing(
            [
                ['id' => $visibleAuthority->id, 'type' => 'external'],
                ['id' => $visibleEntity->id, 'type' => 'internal'],
            ],
            $scopedOrganizations->map(fn ($organization) => [
                'id' => $organization->id,
                'type' => $organization->organization_type,
            ])->all(),
        );
    }
}

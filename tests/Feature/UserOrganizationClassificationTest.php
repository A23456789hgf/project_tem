<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOrganizationClassificationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminRole;

    protected $adminUser;

    protected $internalEntity;

    protected $authority;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::forceCreate([
            'name' => 'Admin',
        ]);

        $this->internalEntity = InternalEntity::forceCreate([
            'name' => 'إدارة التخطيط والمتابعة',
            'entity_type' => 'Department',
            'is_active' => true,
        ]);

        $this->authority = Authority::forceCreate([
            'agency_name' => 'هيئة تطوير تهامة',
            'is_active' => true,
        ]);

        $this->adminUser = User::forceCreate([
            'user_id' => 'admin_test_01',
            'username' => 'admin_tester',
            'name' => 'System Admin',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'is_admin' => true,
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
        ]);
    }

    /**
     * Test all truth table states for User::isInternal() and User::isExternal()
     */
    public function test_user_organization_classification_matrix(): void
    {
        // 1. Explicit internal
        $userInternal = new User([
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
            'authority_id' => null,
        ]);
        $this->assertTrue($userInternal->isInternal());
        $this->assertFalse($userInternal->isExternal());

        // 2. Explicit external
        $userExternal = new User([
            'organization_type' => 'external',
            'entity_id' => null,
            'authority_id' => $this->authority->id,
        ]);
        $this->assertFalse($userExternal->isInternal());
        $this->assertTrue($userExternal->isExternal());

        // 3. Legacy internal (organization_type = null, entity_id set)
        $userLegacyInternal = new User([
            'organization_type' => null,
            'entity_id' => $this->internalEntity->id,
            'authority_id' => null,
        ]);
        $this->assertTrue($userLegacyInternal->isInternal());
        $this->assertFalse($userLegacyInternal->isExternal());

        // 4. Legacy external (organization_type = null, authority_id set)
        $userLegacyExternal = new User([
            'organization_type' => null,
            'entity_id' => null,
            'authority_id' => $this->authority->id,
        ]);
        $this->assertFalse($userLegacyExternal->isInternal());
        $this->assertTrue($userLegacyExternal->isExternal());

        // 5. Ambiguous dual-ID legacy user (organization_type = null, BOTH entity_id and authority_id set)
        // MUST BE FAIL-CLOSED: Neither internal nor external!
        $userAmbiguousDual = new User([
            'organization_type' => null,
            'entity_id' => 10,
            'authority_id' => 20,
        ]);
        $this->assertFalse($userAmbiguousDual->isInternal(), 'Ambiguous dual-ID legacy user must not be classified as internal');
        $this->assertFalse($userAmbiguousDual->isExternal(), 'Ambiguous dual-ID legacy user must not be classified as external');

        // 6. Explicit internal overriding foreign authority_id
        $userInternalWithAuth = new User([
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
            'authority_id' => 999,
        ]);
        $this->assertTrue($userInternalWithAuth->isInternal());
        $this->assertFalse($userInternalWithAuth->isExternal());

        // 7. Explicit external overriding foreign entity_id
        $userExternalWithEnt = new User([
            'organization_type' => 'external',
            'entity_id' => 999,
            'authority_id' => $this->authority->id,
        ]);
        $this->assertFalse($userExternalWithEnt->isInternal());
        $this->assertTrue($userExternalWithEnt->isExternal());

        // 8. Default root/system user with neither ID nor organization_type
        $userRoot = new User([
            'organization_type' => null,
            'entity_id' => null,
            'authority_id' => null,
        ]);
        $this->assertTrue($userRoot->isInternal());
        $this->assertFalse($userRoot->isExternal());
    }

    /**
     * Test creating a user with null phone succeeds and preserves database integrity.
     */
    public function test_user_creation_with_null_phone_succeeds(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'User Without Phone',
            'phone' => null,
            'email' => 'no_phone@example.com',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
        ];

        $response = $this->post(route('users.store'), $payload);
        $response->assertRedirect(route('users.index'));

        $user = User::where('name', 'User Without Phone')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->phone);
        $this->assertEquals('internal', $user->organization_type);
        $this->assertEquals($this->internalEntity->id, $user->entity_id);
        $this->assertNull($user->authority_id);
    }

    /**
     * Test that creating internal user strictly nullifies authority_id, and creating external strictly nullifies entity_id.
     */
    public function test_user_creation_cleanses_opposing_organization_scope(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Internal creation attempt with conflicting authority_id
        $internalPayload = [
            'name' => 'Internal Worker',
            'phone' => '771122334',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
            'authority_id' => $this->authority->id, // Sent maliciously/mistakenly
        ];

        $res1 = $this->post(route('users.store'), $internalPayload);
        $res1->assertRedirect(route('users.index'));

        $internalUser = User::where('name', 'Internal Worker')->first();
        $this->assertNotNull($internalUser);
        $this->assertEquals('internal', $internalUser->organization_type);
        $this->assertEquals($this->internalEntity->id, $internalUser->entity_id);
        $this->assertNull($internalUser->authority_id, 'authority_id must be nullified for internal users');

        // 2. External creation attempt with conflicting entity_id
        $externalPayload = [
            'name' => 'External Worker',
            'phone' => '771122335',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'external',
            'authority_id' => $this->authority->id,
            'entity_id' => $this->internalEntity->id, // Sent maliciously/mistakenly
        ];

        $res2 = $this->post(route('users.store'), $externalPayload);
        $res2->assertRedirect(route('users.index'));

        $externalUser = User::where('name', 'External Worker')->first();
        $this->assertNotNull($externalUser);
        $this->assertEquals('external', $externalUser->organization_type);
        $this->assertEquals($this->authority->id, $externalUser->authority_id);
        $this->assertNull($externalUser->entity_id, 'entity_id must be nullified for external users');
    }

    /**
     * Test switching organization type during update.
     */
    public function test_user_update_switches_organization_type_cleanly(): void
    {
        $this->actingAs($this->adminUser);

        $user = User::forceCreate([
            'user_id' => 'u_switch_01',
            'username' => 'u_switch',
            'name' => 'Switching User',
            'phone' => '779988776',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
            'authority_id' => null,
        ]);

        // Switch to external
        $updatePayload = [
            'name' => 'Switching User Converted',
            'phone' => '779988776',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'external',
            'authority_id' => $this->authority->id,
            'entity_id' => $this->internalEntity->id, // Should be purged
        ];

        $response = $this->put(route('users.update', $user), $updatePayload);
        $response->assertRedirect(route('users.show', $user));

        $user->refresh();
        $this->assertEquals('external', $user->organization_type);
        $this->assertEquals($this->authority->id, $user->authority_id);
        $this->assertNull($user->entity_id);
        $this->assertTrue($user->isExternal());
        $this->assertFalse($user->isInternal());
    }
}

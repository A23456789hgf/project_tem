<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEntityAndAuthorityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminRole;

    protected $adminUser;

    protected $internalEntity1;

    protected $internalEntity2;

    protected $inactiveInternalEntity;

    protected $authority1;

    protected $authority2;

    protected $inactiveAuthority;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::forceCreate([
            'name' => 'Admin',
        ]);

        $this->internalEntity1 = InternalEntity::forceCreate([
            'name' => 'ديوان عام الوزارة',
            'entity_type' => 'Company',
            'is_active' => true,
            'is_ministry_root' => true,
        ]);

        $this->internalEntity2 = InternalEntity::forceCreate([
            'name' => 'الإدارة العامة للري',
            'entity_type' => 'Department',
            'parent_id' => $this->internalEntity1->id,
            'is_active' => true,
            'is_ministry_root' => false,
        ]);

        $this->inactiveInternalEntity = InternalEntity::forceCreate([
            'name' => 'إدارة ملغاة قديمة',
            'entity_type' => 'Department',
            'is_active' => false,
            'is_ministry_root' => false,
        ]);

        $this->authority1 = Authority::forceCreate([
            'agency_name' => 'هيئة تطوير تهامة',
            'is_active' => true,
        ]);

        $this->authority2 = Authority::forceCreate([
            'agency_name' => 'هيئة مياه الريف',
            'is_active' => true,
        ]);

        $this->inactiveAuthority = Authority::forceCreate([
            'agency_name' => 'هيئة متوقفة',
            'is_active' => false,
        ]);

        $this->adminUser = User::forceCreate([
            'user_id' => 'adm_01',
            'phone' => '770000111',
            'username' => 'admin_test',
            'name' => 'Admin User',
            'email' => 'admin@system.local',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'entity_id' => $this->internalEntity1->id,
            'organization_type' => 'internal',
            'status' => 'Active',
        ]);
    }

    /**
     * 1. Check data counts and visibility for Admin.
     */
    public function test_entity_and_authority_counts_and_visibility(): void
    {
        $this->actingAs($this->adminUser);

        // Internal Entities
        $totalEntities = InternalEntity::withoutGlobalScopes()->count();
        $activeEntities = InternalEntity::withoutGlobalScopes()->where('is_active', true)->count();
        $visibleEntities = InternalEntity::active()->visibleToUser()->count();

        $this->assertEquals(3, $totalEntities);
        $this->assertEquals(2, $activeEntities);
        $this->assertEquals(2, $visibleEntities); // active + visibleToUser

        // Authorities
        $totalAuthorities = Authority::withoutGlobalScopes()->count();
        $activeAuthorities = Authority::withoutGlobalScopes()->where('is_active', true)->count();
        $queryAuthorities = Authority::active()->count();

        $this->assertEquals(3, $totalAuthorities);
        $this->assertEquals(2, $activeAuthorities);
        $this->assertEquals(2, $queryAuthorities);

        // Response on create view
        $response = $this->get(route('users.create'));
        $response->assertStatus(200);
        $response->assertViewHas('entities', function ($entities) {
            return $entities->count() === 2 && $entities->pluck('name')->contains('ديوان عام الوزارة');
        });
        $response->assertViewHas('authorities', function ($authorities) {
            return $authorities->count() === 2 && $authorities->pluck('agency_name')->contains('هيئة تطوير تهامة');
        });
    }

    /**
     * 2. Check HTML options rendered in create view.
     */
    public function test_create_view_renders_proper_html_select_options(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('users.create'));
        $response->assertStatus(200);

        // Check Internal Entity options
        $response->assertSee('value="'.$this->internalEntity1->id.'"', false);
        $response->assertSee('ديوان عام الوزارة');
        $response->assertSee('value="'.$this->internalEntity2->id.'"', false);
        $response->assertSee('الإدارة العامة للري');
        $response->assertDontSee('إدارة ملغاة قديمة');

        // Check Authority options
        $response->assertSee('value="'.$this->authority1->id.'"', false);
        $response->assertSee('هيئة تطوير تهامة');
        $response->assertSee('value="'.$this->authority2->id.'"', false);
        $response->assertSee('هيئة مياه الريف');
        $response->assertDontSee('هيئة متوقفة');
    }

    /**
     * 3. Create Internal User via UserController store.
     */
    public function test_create_internal_user_successfully_persists(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'مستخدم داخلي تجريبي',
            'phone' => '771234567',
            'password' => 'password123',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity2->id,
            'authority_id' => null,
            'responsibility' => 'TECHNICAL',
        ];

        $response = $this->post(route('users.store'), $payload);
        $response->assertRedirect(route('users.index'));

        $createdUser = User::where('name', 'مستخدم داخلي تجريبي')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('internal', $createdUser->organization_type);
        $this->assertEquals($this->internalEntity2->id, $createdUser->entity_id);
        $this->assertNull($createdUser->authority_id);
        $this->assertEquals('الإدارة العامة للري', $createdUser->entity->name);
    }

    /**
     * 4. Create External User via UserController store.
     */
    public function test_create_external_user_successfully_persists(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'مستخدم خارجي تجريبي',
            'phone' => '779876543',
            'password' => 'password123',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'external',
            'authority_id' => $this->authority1->id,
            'entity_id' => null,
            'responsibility' => 'ENTITY_APPROVER',
        ];

        $response = $this->post(route('users.store'), $payload);
        $response->assertRedirect(route('users.index'));

        $createdUser = User::where('name', 'مستخدم خارجي تجريبي')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('external', $createdUser->organization_type);
        $this->assertEquals($this->authority1->id, $createdUser->authority_id);
        $this->assertNull($createdUser->entity_id);
        $this->assertEquals('هيئة تطوير تهامة', $createdUser->authority->agency_name);
    }

    /**
     * 5. Edit Internal User.
     */
    public function test_edit_internal_user_view_and_update(): void
    {
        $this->actingAs($this->adminUser);

        $user = User::forceCreate([
            'user_id' => 'u_int_edit',
            'phone' => '773333333',
            'username' => 'int_edit',
            'name' => 'Internal User For Edit',
            'email' => 'int_edit@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'entity_id' => $this->internalEntity1->id,
            'organization_type' => 'internal',
            'status' => 'Active',
        ]);

        $response = $this->get(route('users.edit', $user));
        $response->assertStatus(200);
        $response->assertSee('value="'.$this->internalEntity1->id.'" selected', false);

        // Update to entity2
        $updatePayload = [
            'name' => 'Internal User Updated',
            'phone' => '773333333',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity2->id,
        ];

        $updateResponse = $this->put(route('users.update', $user), $updatePayload);
        $updateResponse->assertRedirect(route('users.show', $user));

        $user->refresh();
        $this->assertEquals($this->internalEntity2->id, $user->entity_id);
        $this->assertNull($user->authority_id);
    }

    /**
     * 6. Edit External User.
     */
    public function test_edit_external_user_view_and_update(): void
    {
        $this->actingAs($this->adminUser);

        $user = User::forceCreate([
            'user_id' => 'u_ext_edit',
            'phone' => '774444444',
            'username' => 'ext_edit',
            'name' => 'External User For Edit',
            'email' => 'ext_edit@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'authority_id' => $this->authority1->id,
            'organization_type' => 'external',
            'status' => 'Active',
        ]);

        $response = $this->get(route('users.edit', $user));
        $response->assertStatus(200);
        $response->assertSee('value="'.$this->authority1->id.'" selected', false);

        // Update to authority2
        $updatePayload = [
            'name' => 'External User Updated',
            'phone' => '774444444',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'external',
            'authority_id' => $this->authority2->id,
        ];

        $updateResponse = $this->put(route('users.update', $user), $updatePayload);
        $updateResponse->assertRedirect(route('users.show', $user));

        $user->refresh();
        $this->assertEquals($this->authority2->id, $user->authority_id);
        $this->assertNull($user->entity_id);
    }

    /**
     * 7. Admin / Full Access Internal User can have entity_id = NULL.
     */
    public function test_admin_internal_user_can_be_created_with_null_entity_id(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('users.store'), [
            'name' => 'مدير نظام مركزي',
            'phone' => '775555550',
            'password' => 'password123',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => null,
        ]);

        $response->assertRedirect(route('users.index'));

        $created = User::where('name', 'مدير نظام مركزي')->first();
        $this->assertNotNull($created);
        $this->assertNull($created->entity_id);
        $this->assertEquals('internal', $created->organization_type);
    }

    /**
     * 8. Scoped Internal User MUST have a valid entity_id.
     */
    public function test_scoped_internal_user_requires_valid_entity_id(): void
    {
        $this->actingAs($this->adminUser);

        $scopedRole = Role::forceCreate([
            'name' => 'مهندس مشاريع جهة',
            'full_access' => false,
        ]);

        // 1. Scoped Internal without entity_id must fail
        $res1 = $this->post(route('users.store'), [
            'name' => 'مهندس بدون جهة',
            'phone' => '775555551',
            'password' => 'password123',
            'role_id' => $scopedRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => null,
        ]);
        $res1->assertSessionHasErrors('entity_id');

        // 2. Scoped Internal with non-existent entity_id or directorates.id must fail
        $res2 = $this->post(route('users.store'), [
            'name' => 'مهندس بجهة غير موجودة',
            'phone' => '775555552',
            'password' => 'password123',
            'role_id' => $scopedRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => 312, // Non-existent internal_entity id (like directorates.id=312)
        ]);
        $res2->assertSessionHasErrors('entity_id');
    }

    /**
     * 9. External User requires authority_id and wipes entity_id.
     */
    public function test_external_user_requires_valid_authority_id(): void
    {
        $this->actingAs($this->adminUser);

        // External without authority_id must fail
        $res = $this->post(route('users.store'), [
            'name' => 'مستخدم خارجي بدون هيئة',
            'phone' => '775555553',
            'password' => 'password123',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'external',
            'authority_id' => null,
        ]);
        $res->assertSessionHasErrors('authority_id');
    }

    /**
     * 10. Switching Organization Type cleans up opposite relationship.
     */
    public function test_switching_organization_type_cleans_up_opposite_fk(): void
    {
        $this->actingAs($this->adminUser);

        // Start as Internal
        $user = User::forceCreate([
            'user_id' => 'u_switch_test',
            'phone' => '775555554',
            'username' => 'switch_user',
            'name' => 'User For Org Switch',
            'email' => 'switch@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'entity_id' => $this->internalEntity1->id,
            'organization_type' => 'internal',
            'status' => 'Active',
        ]);

        // Switch to External
        $updatePayload = [
            'name' => 'User For Org Switch',
            'phone' => '775555554',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'external',
            'authority_id' => $this->authority1->id,
        ];

        $updateResponse = $this->put(route('users.update', $user), $updatePayload);
        $updateResponse->assertRedirect(route('users.show', $user));

        $user->refresh();
        $this->assertEquals('external', $user->organization_type);
        $this->assertEquals($this->authority1->id, $user->authority_id);
        $this->assertNull($user->entity_id);

        // Switch back to Internal
        $updatePayload2 = [
            'name' => 'User For Org Switch',
            'phone' => '775555554',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity2->id,
        ];

        $updateResponse2 = $this->put(route('users.update', $user), $updatePayload2);
        $updateResponse2->assertRedirect(route('users.show', $user));

        $user->refresh();
        $this->assertEquals('internal', $user->organization_type);
        $this->assertEquals($this->internalEntity2->id, $user->entity_id);
        $this->assertNull($user->authority_id);
    }
}

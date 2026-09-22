<?php

namespace Tests\Feature;

use App\Enums\EntityResponsibilityType;
use App\Enums\UserResponsibilityType;
use App\Models\Authority;
use App\Models\AuthorityApprovalStage;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserStageSyncTest extends TestCase
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
            'name' => 'إدارة المراجعة الفنية',
            'entity_type' => 'Department',
            'is_active' => true,
        ]);

        $this->authority = Authority::forceCreate([
            'agency_name' => 'هيئة المراجعة الخارجية',
            'is_active' => true,
        ]);

        $this->adminUser = User::forceCreate([
            'user_id' => 'admin_sync_01',
            'username' => 'admin_sync',
            'name' => 'Admin Sync User',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'is_admin' => true,
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
        ]);
    }

    public function test_internal_user_creation_automatically_syncs_entity_approval_stage(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'مستشار فني جديد',
            'phone' => '770000001',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
            'responsibility' => UserResponsibilityType::Technical->value,
        ]);

        $response->assertRedirect(route('users.index'));

        $newUser = User::where('name', 'مستشار فني جديد')->first();
        $this->assertNotNull($newUser);

        $stage = EntityApprovalStage::where('entity_id', $this->internalEntity->id)
            ->where('stage', EntityResponsibilityType::TechnicalReview->value)
            ->first();

        $this->assertNotNull($stage);
        $this->assertEquals($newUser->id, $stage->responsible_user_id);
        $this->assertTrue($stage->is_active);
    }

    public function test_external_user_creation_automatically_syncs_authority_approval_stage(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'مراجع مالي خارجي',
            'phone' => '770000002',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'external',
            'authority_id' => $this->authority->id,
            'responsibility' => UserResponsibilityType::Financial->value,
        ]);

        $response->assertRedirect(route('users.index'));

        $newUser = User::where('name', 'مراجع مالي خارجي')->first();
        $this->assertNotNull($newUser);

        $stage = AuthorityApprovalStage::where('authority_id', $this->authority->id)
            ->where('stage', EntityResponsibilityType::FinancialReview->value)
            ->first();

        $this->assertNotNull($stage);
        $this->assertEquals($newUser->id, $stage->responsible_user_id);
        $this->assertTrue($stage->is_active);
    }

    public function test_updating_user_responsibility_syncs_stage(): void
    {
        $user = User::forceCreate([
            'user_id' => 'user_update_01',
            'username' => 'user_update',
            'name' => 'مسؤول جهة تحت التعديل',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('users.update', $user), [
            'name' => 'مسؤول جهة تحت التعديل',
            'phone' => '770000003',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
            'responsibility' => UserResponsibilityType::EntityApprover->value,
        ]);

        $response->assertRedirect(route('users.show', $user));

        $stage = EntityApprovalStage::where('entity_id', $this->internalEntity->id)
            ->where('stage', EntityResponsibilityType::Approval->value)
            ->first();

        $this->assertNotNull($stage);
        $this->assertEquals($user->id, $stage->responsible_user_id);
        $this->assertTrue($stage->is_active);
    }

    public function test_created_user_has_default_password_and_must_change_password_flag(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'مستخدم جديد بكلمة مرور افتراضية',
            'phone' => '770000004',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
        ]);

        $response->assertRedirect(route('users.index'));

        $newUser = User::where('name', 'مستخدم جديد بكلمة مرور افتراضية')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check('123456', $newUser->password));
        $this->assertTrue((bool) $newUser->must_change_password);
    }

    public function test_external_and_internal_user_administrative_scope_creation(): void
    {
        // 1. External User with external authority scope
        $responseExt = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'مستخدم خارجي بنطاق سلطة',
            'phone' => '770000005',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'external',
            'authority_id' => $this->authority->id,
            'administrative_scope_id' => $this->authority->id,
        ]);

        $responseExt->assertRedirect(route('users.index'));
        $extUser = User::where('name', 'مستخدم خارجي بنطاق سلطة')->first();
        $this->assertNotNull($extUser);
        $this->assertEquals($this->authority->id, $extUser->administrative_scope_id);
        $this->assertEquals($this->authority->agency_name, $extUser->administrativeAuthority?->agency_name);

        // 2. Internal User with internal entity scope
        $responseInt = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'مستخدم داخلي بنطاق كيان',
            'phone' => '770000006',
            'role_id' => $this->adminRole->id,
            'status' => 'Active',
            'organization_type' => 'internal',
            'entity_id' => $this->internalEntity->id,
            'administrative_scope_id' => $this->internalEntity->id,
        ]);

        $responseInt->assertRedirect(route('users.index'));
        $intUser = User::where('name', 'مستخدم داخلي بنطاق كيان')->first();
        $this->assertNotNull($intUser);
        $this->assertEquals($this->internalEntity->id, $intUser->administrative_scope_id);
        $this->assertEquals($this->internalEntity->name, $intUser->internalEntity?->name);
    }
}

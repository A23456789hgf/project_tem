<?php

namespace Tests\Feature;

use App\Models\FinancialItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionResolver::clearCache();
    }

    /**
     * Complete End-to-End lifecycle test:
     * Matrix Permission -> Role -> User -> UI Visibility -> Backend Authorization -> Revocation -> 403 & DB Unchanged
     */
    public function test_full_permission_matrix_e2e_lifecycle(): void
    {
        // 1. Seed base permissions via seeder
        $seeder = new PermissionsTableSeeder;
        $seeder->run();
        PermissionResolver::clearCache();

        // Verify permission is in Matrix Registry
        $matrixSlugs = PermissionResolver::getMatrixSlugs();
        $this->assertContains('financial-items.view', $matrixSlugs);
        $this->assertContains('financial-items.approve', $matrixSlugs);
        $this->assertContains('main_modules.encoding', $matrixSlugs);

        $viewPerm = Permission::where('slug', 'financial-items.view')->firstOrFail();
        $approvePerm = Permission::where('slug', 'financial-items.approve')->firstOrFail();
        $encodingPerm = Permission::where('slug', 'main_modules.encoding')->firstOrFail();

        // 2. Create a restricted custom Role
        $testRole = Role::create([
            'name' => 'E2E_Test_Role_'.uniqid(),
            'description' => 'Role for E2E Permission Testing',
            'is_active' => true,
            'full_access' => false,
            'module_scopes' => [
                'financial-items' => 'all',
            ],
        ]);

        // 3. Assign view and approve permissions to the role
        RolePermission::create(['role_id' => $testRole->id, 'permission_id' => $encodingPerm->id]);
        RolePermission::create(['role_id' => $testRole->id, 'permission_id' => $viewPerm->id]);
        RolePermission::create(['role_id' => $testRole->id, 'permission_id' => $approvePerm->id]);

        // 4. Verify Persistence
        $testRole->refresh();
        $this->assertTrue($testRole->rolePermissions()->where('permission_id', $approvePerm->id)->exists());

        // 5. Create user assigned to this role
        $testUser = User::factory()->create([
            'role_id' => $testRole->id,
            'is_admin' => false,
            'status' => 'active',
        ]);

        // 6. User inherits permissions
        $userPerms = $testUser->getAllPermissionsSlugs();
        $this->assertContains('financial-items.approve', $userPerms);
        $this->assertTrue($testUser->hasPermission('financial-items.approve'));

        // 7. Create a pending financial item
        $item = FinancialItem::create([
            'name' => 'E2E_Test_Item_'.uniqid(),
            'code' => 'CODE_'.uniqid(),
            'status' => 0,
        ]);

        // 8. User logs in -> UI Action IS VISIBLE
        $this->actingAs($testUser);
        $uiResponse = $this->get(route('financial-items.index'));
        $uiResponse->assertStatus(200);
        $uiResponse->assertSee(route('financial-items.approve', $item->id));

        // 9. Backend Authorization -> Direct request is ALLOWED
        $approveResponse = $this->post(route('financial-items.approve', $item->id));
        $approveResponse->assertRedirect();
        $this->assertDatabaseHas('financial_items', [
            'id' => $item->id,
            'status' => 1,
        ]);

        // ========================================================
        // 10. REVOCATION PHASE: Uncheck/Remove approve permission
        // ========================================================
        RolePermission::where('role_id', $testRole->id)
            ->where('permission_id', $approvePerm->id)
            ->delete();

        // Increment role version to invalidate cached user permissions
        User::incrementRolePermissionsVersion($testRole->id);
        $testUser->clearPermissionCache();

        // 11. Verify removal persisted
        $testRole->refresh();
        $this->assertFalse($testRole->rolePermissions()->where('permission_id', $approvePerm->id)->exists());

        // 12. User loses permission
        $revokedPerms = $testUser->getAllPermissionsSlugs();
        $this->assertNotContains('financial-items.approve', $revokedPerms);
        $this->assertFalse($testUser->hasPermission('financial-items.approve'));

        // 13. Create another pending item
        $secondItem = FinancialItem::create([
            'name' => 'E2E_Second_Item_'.uniqid(),
            'code' => 'CODE_'.uniqid(),
            'status' => 0,
        ]);

        // 14. UI Action IS NOW HIDDEN
        $uiRevokedResponse = $this->get(route('financial-items.index'));
        $uiRevokedResponse->assertStatus(200);
        $uiRevokedResponse->assertDontSee(route('financial-items.approve', $secondItem->id));

        // 15. Direct Backend Request is DENIED (HTTP 403)
        $deniedResponse = $this->post(route('financial-items.approve', $secondItem->id));
        $deniedResponse->assertStatus(403);

        // 16. Database remains completely UNCHANGED (status stays 0)
        $this->assertDatabaseHas('financial_items', [
            'id' => $secondItem->id,
            'status' => 0,
        ]);
    }
}

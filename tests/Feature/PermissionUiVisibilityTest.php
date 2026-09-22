<?php

namespace Tests\Feature;

use App\Models\FinancialItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PermissionUiVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        PermissionResolver::clearCache();

        // Seed permissions
        $perms = [
            ['name' => 'الترميز', 'slug' => 'main_modules.encoding', 'module' => 'main_modules'],
            ['name' => 'عرض البنود المالية', 'slug' => 'financial-items.view', 'module' => 'financial-items'],
            ['name' => 'إنشاء بند مالي', 'slug' => 'financial-items.create', 'module' => 'financial-items'],
            ['name' => 'تعديل بند مالي', 'slug' => 'financial-items.edit', 'module' => 'financial-items'],
            ['name' => 'حذف بند مالي', 'slug' => 'financial-items.delete', 'module' => 'financial-items'],
            ['name' => 'اعتماد البنود المالية', 'slug' => 'financial-items.approve', 'module' => 'financial-items'],
            ['name' => 'رفض البنود المالية', 'slug' => 'financial-items.reject', 'module' => 'financial-items'],
            ['name' => 'عرض التدخلات', 'slug' => 'interventions.view', 'module' => 'interventions'],
            ['name' => 'اعتماد التدخلات', 'slug' => 'interventions.approve', 'module' => 'interventions'],
            ['name' => 'رفض التدخلات', 'slug' => 'interventions.reject', 'module' => 'interventions'],
        ];

        foreach ($perms as $p) {
            Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        PermissionResolver::clearCache();
    }

    protected function createUserWithPermissions(array $permissionSlugs): User
    {
        $role = Role::create([
            'name' => 'Role_'.uniqid(),
            'description' => 'Test Role',
            'is_active' => true,
            'full_access' => false,
            'module_scopes' => [
                'financial-items' => 'all',
                'interventions' => 'all',
            ],
        ]);

        foreach ($permissionSlugs as $slug) {
            $perm = Permission::where('slug', $slug)->first();
            if ($perm) {
                RolePermission::create([
                    'role_id' => $role->id,
                    'permission_id' => $perm->id,
                ]);
            }
        }

        return User::factory()->create([
            'role_id' => $role->id,
            'is_admin' => false,
            'status' => 'active',
        ]);
    }

    public function test_financial_items_create_button_visible_only_with_permission(): void
    {
        $userWithPerm = $this->createUserWithPermissions(['main_modules.encoding', 'financial-items.view', 'financial-items.create']);
        $userWithoutPerm = $this->createUserWithPermissions(['main_modules.encoding', 'financial-items.view']);

        // With Permission -> create button visible
        $this->actingAs($userWithPerm);
        $response = $this->get(route('financial-items.index'));
        $response->assertStatus(200);
        $response->assertSee(route('financial-items.create'));

        // Without Permission -> create button hidden
        $this->actingAs($userWithoutPerm);
        $response = $this->get(route('financial-items.index'));
        $response->assertStatus(200);
        $response->assertDontSee(route('financial-items.create'));
    }

    public function test_financial_items_approve_button_visible_only_with_approve_permission(): void
    {
        $item = FinancialItem::create([
            'name' => 'PendingItem_'.uniqid(),
            'code' => 'CODE_'.uniqid(),
            'status' => 0,
        ]);

        $approver = $this->createUserWithPermissions(['main_modules.encoding', 'financial-items.view', 'financial-items.approve']);
        $viewer = $this->createUserWithPermissions(['main_modules.encoding', 'financial-items.view']);

        // Approver -> see approve form action
        $this->actingAs($approver);
        $response = $this->get(route('financial-items.index'));
        $response->assertStatus(200);
        $response->assertSee(route('financial-items.approve', $item->id));

        // Viewer -> don't see approve form action
        $this->actingAs($viewer);
        $response = $this->get(route('financial-items.index'));
        $response->assertStatus(200);
        $response->assertDontSee(route('financial-items.approve', $item->id));
    }
}

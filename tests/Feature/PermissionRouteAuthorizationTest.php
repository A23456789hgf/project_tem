<?php

namespace Tests\Feature;

use App\Models\BeneficiaryGroup;
use App\Models\FinancialItem;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PermissionRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        PermissionResolver::clearCache();

        // Seed necessary permissions
        $perms = [
            ['name' => 'الترميز', 'slug' => 'main_modules.encoding', 'module' => 'main_modules'],
            ['name' => 'المشاريع', 'slug' => 'main_modules.projects', 'module' => 'main_modules'],
            ['name' => 'المراسلات', 'slug' => 'main_modules.correspondence', 'module' => 'main_modules'],
            ['name' => 'عرض المشاريع', 'slug' => 'projects.view', 'module' => 'projects'],
            ['name' => 'تعديل مشروع', 'slug' => 'projects.edit', 'module' => 'projects'],
            ['name' => 'إنشاء مشروع', 'slug' => 'projects.create', 'module' => 'projects'],
            ['name' => 'حذف مشروع', 'slug' => 'projects.delete', 'module' => 'projects'],
            ['name' => 'عرض الكيانات الداخلية', 'slug' => 'internal-entities.view', 'module' => 'internal-entities'],
            ['name' => 'حذف الكيانات الداخلية', 'slug' => 'internal-entities.delete', 'module' => 'internal-entities'],
            ['name' => 'عرض الوحدات', 'slug' => 'units.view', 'module' => 'units'],
            ['name' => 'اعتماد الوحدات', 'slug' => 'units.approve', 'module' => 'units'],
            ['name' => 'رفض الوحدات', 'slug' => 'units.reject', 'module' => 'units'],
            ['name' => 'عرض مجموعات المستفيدين', 'slug' => 'beneficiary-groups.view', 'module' => 'beneficiary-groups'],
            ['name' => 'اعتماد مجموعات المستفيدين', 'slug' => 'beneficiary-groups.approve', 'module' => 'beneficiary-groups'],
            ['name' => 'رفض مجموعات المستفيدين', 'slug' => 'beneficiary-groups.reject', 'module' => 'beneficiary-groups'],
            ['name' => 'عرض البنود المالية', 'slug' => 'financial-items.view', 'module' => 'financial-items'],
            ['name' => 'اعتماد البنود المالية', 'slug' => 'financial-items.approve', 'module' => 'financial-items'],
            ['name' => 'رفض البنود المالية', 'slug' => 'financial-items.reject', 'module' => 'financial-items'],
            ['name' => 'عرض القرى', 'slug' => 'villages.view', 'module' => 'villages'],
            ['name' => 'استيراد القرى', 'slug' => 'villages.import', 'module' => 'villages'],
            ['name' => 'تصدير القرى', 'slug' => 'villages.export', 'module' => 'villages'],
            ['name' => 'عرض الإحالات', 'slug' => 'referrals.view', 'module' => 'referrals'],
            ['name' => 'إنشاء إحالة', 'slug' => 'referrals.create', 'module' => 'referrals'],
            ['name' => 'الرد على الإحالة', 'slug' => 'referrals.respond', 'module' => 'referrals'],
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
                'projects' => 'all',
                'internal-entities' => 'all',
                'units' => 'all',
                'beneficiary-groups' => 'all',
                'financial-items' => 'all',
                'villages' => 'all',
                'referrals' => 'all',
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

    public function test_user_without_permission_denied_on_update_entity(): void
    {
        $unauthorizedUser = $this->createUserWithPermissions(['main_modules.projects', 'projects.view']);
        $project = Project::factory()->create();

        $this->actingAs($unauthorizedUser);
        $response = $this->put(route('projects.update-entity', $project->id), [
            'entity_id' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_without_delete_permission_denied_on_internal_entity_bulk_destroy(): void
    {
        $viewOnlyUser = $this->createUserWithPermissions(['main_modules.encoding', 'internal-entities.view']);

        $this->actingAs($viewOnlyUser);
        $response = $this->delete(route('internal-entities.bulk-destroy'), [
            'ids' => [1, 2],
        ]);

        $response->assertStatus(403);
    }

    public function test_user_without_approve_permission_denied_on_unit_approval(): void
    {
        $viewOnlyUser = $this->createUserWithPermissions(['main_modules.encoding', 'units.view']);
        $unit = Unit::create(['unit_name' => 'TestUnit_'.uniqid()]);

        $this->actingAs($viewOnlyUser);
        $response = $this->post(route('units.approve', $unit->id));

        $response->assertStatus(403);
    }

    public function test_user_with_approve_permission_allowed_on_unit_approval(): void
    {
        $approverUser = $this->createUserWithPermissions(['main_modules.encoding', 'units.view', 'units.approve']);
        $unit = Unit::create(['unit_name' => 'TestUnit_'.uniqid()]);

        $this->actingAs($approverUser);
        $response = $this->post(route('units.approve', $unit->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'status' => 1,
        ]);
    }

    public function test_user_without_approve_permission_denied_on_beneficiary_group_approval(): void
    {
        $viewOnlyUser = $this->createUserWithPermissions(['main_modules.encoding', 'beneficiary-groups.view']);
        $bg = BeneficiaryGroup::create(['name' => 'TestGroup_'.uniqid()]);

        $this->actingAs($viewOnlyUser);
        $response = $this->post(route('beneficiary-groups.approve', $bg->id));

        $response->assertStatus(403);
    }

    public function test_user_without_approve_permission_denied_on_financial_item_approval(): void
    {
        $viewOnlyUser = $this->createUserWithPermissions(['main_modules.encoding', 'financial-items.view']);
        $item = FinancialItem::create([
            'name' => 'TestItem_'.uniqid(),
            'code' => 'CODE_'.uniqid(),
            'status' => 0,
        ]);

        $this->actingAs($viewOnlyUser);
        $response = $this->post(route('financial-items.approve', $item->id));

        $response->assertStatus(403);
    }

    public function test_user_without_import_permission_denied_on_village_import(): void
    {
        $viewOnlyUser = $this->createUserWithPermissions(['main_modules.encoding', 'villages.view']);

        $this->actingAs($viewOnlyUser);
        $response = $this->get(route('villages.import'));

        $response->assertStatus(403);
    }
}

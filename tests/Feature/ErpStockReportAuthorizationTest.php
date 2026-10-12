<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Database\Seeders\ErpStockReportsPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ErpStockReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ErpStockReportsPermissionsSeeder::class);
        PermissionResolver::clearCache();

        Http::fake([
            '*' => Http::response(['data' => []], 200),
        ]);
    }

    public function test_each_report_permission_allows_only_its_matching_page(): void
    {
        $routesByPermission = [
            'reports.material_requests.view' => 'projects.reports.material_requests',
            'reports.stock_entries.view' => 'projects.reports.stock_entries',
            'reports.stock_ledger.view' => 'projects.reports.stock_ledger',
            'reports.purchase_invoices.view' => 'projects.reports.purchase_invoices',
        ];

        foreach ($routesByPermission as $permission => $routeName) {
            $user = $this->createUserWithPermissions([$permission]);

            $this->actingAs($user)->get(route($routeName))->assertOk();

            $otherRoute = collect($routesByPermission)->except($permission)->first();
            $this->actingAs($user)->get(route($otherRoute))->assertForbidden();
        }
    }

    public function test_print_action_is_visible_only_with_its_permission(): void
    {
        $viewer = $this->createUserWithPermissions(['reports.material_requests.view']);
        $printer = $this->createUserWithPermissions([
            'reports.material_requests.view',
            'reports.material_requests.print',
        ]);

        $this->actingAs($viewer)
            ->get(route('projects.reports.material_requests'))
            ->assertOk()
            ->assertDontSee('onclick="window.print()"', false);

        $this->actingAs($printer)
            ->get(route('projects.reports.material_requests'))
            ->assertOk()
            ->assertSee('onclick="window.print()"', false);
    }

    public function test_unauthenticated_users_cannot_reach_stock_report_endpoints(): void
    {
        $this->get(route('projects.reports.material_requests'))->assertRedirectToRoute('login');
        $this->get(route('projects.reports.material_requests.api_lookup'))->assertRedirectToRoute('login');
        $this->get(route('projects.reports.purchase_invoices.items', 'PINV-1'))->assertRedirectToRoute('login');
    }

    private function createUserWithPermissions(array $permissionSlugs): User
    {
        $role = Role::create([
            'name' => 'ERP Stock Role '.uniqid(),
            'is_active' => true,
            'full_access' => false,
        ]);

        foreach (Permission::whereIn('slug', $permissionSlugs)->get() as $permission) {
            RolePermission::create([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
            ]);
        }

        $user = User::factory()->create([
            'role_id' => $role->id,
            'is_admin' => false,
            'status' => 'active',
        ]);

        User::incrementRolePermissionsVersion($role->id);

        return $user;
    }
}

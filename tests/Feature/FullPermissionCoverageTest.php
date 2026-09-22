<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRoutePermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FullPermissionCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        $this->seed(PermissionsTableSeeder::class);
        PermissionResolver::clearCache();
        Cache::flush();
    }

    /**
     * Test that all application named routes resolve to valid matrix permission slugs or are whitelisted.
     */
    public function test_all_routes_resolve_to_valid_matrix_permissions(): void
    {
        $matrixSlugs = PermissionResolver::getMatrixSlugs();
        $matrixSlugsSet = array_flip($matrixSlugs);

        $ensureRef = new \ReflectionClass(EnsureRoutePermission::class);
        $resolveMethod = $ensureRef->getMethod('resolvePermissionSlug');
        $resolveMethod->setAccessible(true);
        $ensureInstance = new EnsureRoutePermission;

        $file = file_get_contents(app_path('Http/Middleware/EnsureRoutePermission.php'));
        preg_match('/\$whitelist\s*=\s*\[(.*?)\];/s', $file, $m);
        $whitelistItems = [];
        if (! empty($m[1])) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $m[1], $wMatches);
            $whitelistItems = $wMatches[1];
        }

        $notRequiredRoutes = [
            'up', 'home', 'login', 'auth.login', 'password.request',
            'auth.send-reset-link', 'password.reset', 'password.update', 'logout',
        ];

        $routes = Route::getRoutes()->getRoutes();
        $mismatches = [];

        foreach ($routes as $route) {
            $routeName = $route->getName();
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();

            $isWeb = in_array('web', $middleware);
            if (! $isWeb && ! str_starts_with($uri, 'api/')) {
                continue;
            }

            if (str_starts_with($uri, '_ignition') || str_starts_with($uri, 'sanctum/')) {
                continue;
            }

            if (! $routeName || in_array($routeName, $notRequiredRoutes)) {
                continue;
            }

            $isWhitelisted = in_array($routeName, $whitelistItems);
            if (! $isWhitelisted) {
                foreach ($whitelistItems as $pattern) {
                    if (str_ends_with($pattern, '*') && str_starts_with($routeName, rtrim($pattern, '*'))) {
                        $isWhitelisted = true;
                        break;
                    }
                }
            }

            $resolvedSlug = $resolveMethod->invoke($ensureInstance, $routeName);
            $inMatrix = isset($matrixSlugsSet[$resolvedSlug]);

            if (! $inMatrix && ! $isWhitelisted) {
                $mismatches[] = "Route '{$routeName}' ({$uri}) generated slug '{$resolvedSlug}' which is missing in Matrix";
            }
        }

        $this->assertEmpty($mismatches, "Found route permission mismatches:\n".implode("\n", $mismatches));
    }

    /**
     * Test that Non-Admin users with granted entry permissions can access authorized modules (HTTP 200).
     */
    public function test_non_admin_with_granted_permission_can_access_module(): void
    {
        $role = Role::create([
            'name' => 'Test_Project_Auditor_'.uniqid(),
            'description' => 'Test Role for Full Permission Coverage',
            'is_active' => true,
            'full_access' => false,
        ]);

        $projectPerm = Permission::where('slug', 'projects.view')->firstOrFail();
        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $projectPerm->id,
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        User::incrementRolePermissionsVersion($role->id);

        $response = $this->actingAs($user)->get(route('projects.index'));
        $this->assertEquals(200, $response->getStatusCode(), 'Non-admin with projects.view should get 200 on projects.index');
    }

    /**
     * Test that Non-Admin users without permissions receive immediate 403 Forbidden.
     */
    public function test_non_admin_without_permission_receives_403(): void
    {
        $role = Role::create([
            'name' => 'Test_Restricted_User_'.uniqid(),
            'description' => 'Restricted role with no permissions',
            'is_active' => true,
            'full_access' => false,
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        User::incrementRolePermissionsVersion($role->id);

        $response = $this->actingAs($user)->get(route('projects.index'));
        $this->assertEquals(403, $response->getStatusCode(), 'Non-admin without permissions must receive 403 Forbidden');
    }
}

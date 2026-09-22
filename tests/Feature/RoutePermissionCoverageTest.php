<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRoutePermission;
use App\Models\Permission;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoutePermissionCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        $this->seed(PermissionsTableSeeder::class);
        PermissionResolver::clearCache();
    }

    /**
     * Test that no route has an un-matrixed or un-whitelisted permission slug.
     */
    public function test_zero_route_slug_mismatches_exist(): void
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
                $mismatches[] = "Route '{$routeName}' -> Resolved '{$resolvedSlug}'";
            }
        }

        $this->assertCount(0, $mismatches, "Zero route mismatches allowed. Found:\n".implode("\n", $mismatches));
    }
}

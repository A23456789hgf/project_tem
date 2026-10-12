<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase21SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_ignores_submitted_role_and_uses_system_default(): void
    {
        $defaultRole = Role::query()->create([
            'name' => 'viewer',
            'description' => 'Default viewer role',
            'is_active' => true,
        ]);
        $privilegedRole = Role::query()->create([
            'name' => 'admin',
            'description' => 'Administrator',
            'is_active' => true,
        ]);

        $response = $this->post(route('auth.register'), [
            'username' => 'safe-registration',
            'name' => 'Safe Registration',
            'email' => 'safe-registration@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role_id' => $privilegedRole->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'username' => 'safe-registration',
            'role_id' => $defaultRole->id,
        ]);
        $this->assertDatabaseMissing('users', [
            'username' => 'safe-registration',
            'role_id' => $privilegedRole->id,
        ]);
    }

    public function test_debug_routes_are_not_registered_outside_local_environment(): void
    {
        $this->assertFalse(app()->environment('local'));

        $this->get('/resend-sms-temp')->assertNotFound();
        $this->get('/debug-auth')->assertNotFound();
        $this->get('/debug-perf-measure')->assertNotFound();
        $this->assertFalse(Route::has('debug.scope'));
    }

    public function test_frappe_routes_are_unique_and_use_action_specific_permissions(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/frappe-project'));

        $routeKeys = $routes->flatMap(fn ($route) => collect($route->methods())
            ->reject(fn (string $method): bool => $method === 'HEAD')
            ->map(fn (string $method): string => $method.' '.$route->uri()));

        $this->assertSame($routeKeys->count(), $routeKeys->unique()->count());
        $this->assertContains('can:projects.edit', $routes->first(
            fn ($route) => $route->uri() === 'api/frappe-project/update/{projectId}'
                && in_array('PUT', $route->methods(), true)
        )->gatherMiddleware());
        $this->assertContains('can:projects.delete', $routes->first(
            fn ($route) => $route->uri() === 'api/frappe-project/delete/{projectId}'
        )->gatherMiddleware());
        $this->assertContains('can:projects.view', $routes->first(
            fn ($route) => $route->uri() === 'api/frappe-project/{projectId}'
        )->gatherMiddleware());
    }
}

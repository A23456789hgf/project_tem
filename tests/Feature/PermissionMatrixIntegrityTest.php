<?php

namespace Tests\Feature;

use App\Http\Controllers\RoleController;
use App\Models\Permission;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class PermissionMatrixIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionResolver::clearCache();
    }

    public function test_permissions_seeder_populates_valid_slugs(): void
    {
        $seeder = new PermissionsTableSeeder;
        $seeder->run();

        $count = Permission::count();
        $this->assertGreaterThan(500, $count, 'Permissions table should have over 500 permissions seeded.');
    }

    public function test_matrix_registry_resolves_seeded_permissions(): void
    {
        $seeder = new PermissionsTableSeeder;
        $seeder->run();

        PermissionResolver::clearCache();
        $matrixSlugs = PermissionResolver::getMatrixSlugs();

        $this->assertNotEmpty($matrixSlugs);
        $this->assertContains('projects.view', $matrixSlugs);
        $this->assertContains('projects.create', $matrixSlugs);
        $this->assertContains('users.view', $matrixSlugs);
        $this->assertContains('units.approve', $matrixSlugs);
    }

    public function test_role_controller_builds_matrix_data_without_errors(): void
    {
        $seeder = new PermissionsTableSeeder;
        $seeder->run();

        $roleController = new RoleController;
        $refMethod = new ReflectionMethod(RoleController::class, 'getPermissionMatrixData');
        $refMethod->setAccessible(true);
        $matrixData = $refMethod->invoke($roleController);

        $this->assertArrayHasKey('allModuleNames', $matrixData);
        $this->assertArrayHasKey('categorized', $matrixData);
        $this->assertArrayHasKey('moduleTranslations', $matrixData);
        $this->assertNotEmpty($matrixData['allModuleNames']);
    }
}

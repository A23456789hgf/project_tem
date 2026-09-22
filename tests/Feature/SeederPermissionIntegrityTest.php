<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederPermissionIntegrityTest extends TestCase
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
     * Test that all permissions in PermissionsTableSeeder are inserted into the database.
     */
    public function test_all_seeder_permissions_are_in_database(): void
    {
        $dbSlugs = Permission::pluck('slug')->toArray();
        $dbSet = array_flip($dbSlugs);

        $content = file_get_contents(database_path('seeders/PermissionsTableSeeder.php'));
        preg_match_all('/[\'"]slug[\'"]\s*=>\s*[\'"]([a-zA-Z0-9_\-\.]+)[\'"]/', $content, $m);
        $seederSlugs = array_unique($m[1]);

        $missingInDb = [];
        foreach ($seederSlugs as $slug) {
            if (! isset($dbSet[$slug])) {
                $missingInDb[] = $slug;
            }
        }

        $this->assertEmpty($missingInDb, 'Seeder permissions missing from database: '.implode(', ', $missingInDb));
    }

    /**
     * Test that all permissions in database are reflected in the Matrix registry.
     */
    public function test_all_db_permissions_exist_in_matrix_registry(): void
    {
        $dbSlugs = Permission::pluck('slug')->toArray();
        $matrixSlugs = PermissionResolver::getMatrixSlugs();
        $matrixSet = array_flip($matrixSlugs);

        $missingInMatrix = [];
        foreach ($dbSlugs as $slug) {
            if (! isset($matrixSet[$slug])) {
                $missingInMatrix[] = $slug;
            }
        }

        $this->assertEmpty($missingInMatrix, 'Database permissions missing from Matrix: '.implode(', ', $missingInMatrix));
    }
}

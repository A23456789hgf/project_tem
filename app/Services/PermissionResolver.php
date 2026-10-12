<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PermissionResolver
{
    protected static $matrixPath = 'resources/views/roles/partials/_permissions_matrix.blade.php';

    protected static $slugsCache = null;

    /**
     * Get all permissions from the matrix file.
     */
    public static function getPermissions(): array
    {
        return Cache::remember('permissions_matrix_registry_v4', now()->addHours(24), function () {
            if (Schema::hasTable('permissions')) {
                return Permission::select('id', 'name', 'slug', 'module', 'type')->get()->toArray();
            }

            return [];
        });
    }

    public static function getMatrixSlugs(): array
    {
        if (self::$slugsCache === null) {
            $permissions = self::getPermissions();
            self::$slugsCache = array_column($permissions, 'slug');
        }

        return self::$slugsCache;
    }

    /**
     * Check if a permission slug is valid (exists in the matrix).
     * Alias for isValidPermission.
     */
    public static function contains(string $slug): bool
    {
        return self::isValidPermission($slug);
    }

    /**
     * Check if a permission slug is valid (exists in the matrix).
     */
    public static function isValidPermission(string $slug): bool
    {
        if (in_array($slug, self::getMatrixSlugs(), true)) {
            return true;
        }

        return Permission::query()->where('slug', $slug)->exists();
    }

    /**
     * Get permission details by slug.
     */
    public static function getPermissionBySlug(string $slug): ?array
    {
        $permissions = self::getPermissions();
        foreach ($permissions as $permission) {
            if ($permission['slug'] === $slug) {
                return $permission;
            }
        }

        return null;
    }

    /**
     * Parse the matrix file to extract JSON registry.
     */
    protected static function parseMatrixFile(): array
    {
        $path = base_path(self::$matrixPath);

        if (! file_exists($path)) {
            Log::error("Permissions matrix file not found: {$path}");

            return [];
        }

        $content = file_get_contents($path);

        $startMarker = 'PERMISSIONS_MATRIX_REGISTRY_START';
        $endMarker = 'PERMISSIONS_MATRIX_REGISTRY_END';

        $startPos = strpos($content, $startMarker);
        $endPos = strpos($content, $endMarker);

        if ($startPos === false || $endPos === false) {
            Log::error("Permissions matrix markers not found in: {$path}");

            return [];
        }

        $jsonStart = $startPos + strlen($startMarker);
        $jsonLength = $endPos - $jsonStart;
        $jsonContent = trim(substr($content, $jsonStart, $jsonLength));

        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode permissions matrix JSON: '.json_last_error_msg());

            return [];
        }

        return $data['permissions'] ?? [];
    }

    /**
     * Clear the permissions cache.
     */
    public static function clearCache(): void
    {
        self::$slugsCache = null;
        Cache::forget('permissions_matrix_registry');
        Cache::forget('permissions_matrix_registry_v4');
    }
}

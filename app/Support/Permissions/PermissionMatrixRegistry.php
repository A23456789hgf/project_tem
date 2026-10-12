<?php

namespace App\Support\Permissions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PermissionMatrixRegistry
{
    public const REGISTRY_PATH = 'resources/views/roles/partials/_permissions_matrix.blade.php';

    public const START_MARKER = 'PERMISSIONS_MATRIX_REGISTRY_START';

    public const END_MARKER = 'PERMISSIONS_MATRIX_REGISTRY_END';

    public static function clearCache(): void
    {
        Cache::forget('permissions.matrix.registry.v1');
    }

    /**
     * @return array{version:int, permissions: array<int, array{slug:string,name?:string,module?:string,type?:string,description?:string}>}
     */
    public static function read(): array
    {
        return Cache::remember('permissions.matrix.registry.v1', now()->addMinutes(5), function () {
            $path = base_path(self::REGISTRY_PATH);
            $contents = File::get($path);
            $json = self::extractJsonBlock($contents);

            if ($json === '') {
                return ['version' => 1, 'permissions' => []];
            }

            $data = json_decode($json, true);
            if (! is_array($data)) {
                return ['version' => 1, 'permissions' => []];
            }

            $version = (int) ($data['version'] ?? 1);
            $permissions = is_array($data['permissions'] ?? null) ? $data['permissions'] : [];

            // Normalize / de-dup
            $out = [];
            $seen = [];
            foreach ($permissions as $p) {
                if (! is_array($p)) {
                    continue;
                }
                $slug = (string) ($p['slug'] ?? '');
                $slug = trim($slug);
                if ($slug === '') {
                    continue;
                }
                if (isset($seen[$slug])) {
                    continue;
                }
                $seen[$slug] = true;
                $out[] = [
                    'slug' => $slug,
                    'name' => isset($p['name']) ? (string) $p['name'] : null,
                    'module' => isset($p['module']) ? (string) $p['module'] : null,
                    'type' => isset($p['type']) ? (string) $p['type'] : null,
                    'description' => isset($p['description']) ? (string) $p['description'] : null,
                ];
            }

            return [
                'version' => $version,
                'permissions' => $out,
            ];
        });
    }

    /**
     * @return string[]
     */
    public static function slugs(): array
    {
        return array_values(array_map(
            fn ($p) => (string) ($p['slug'] ?? ''),
            self::read()['permissions'] ?? []
        ));
    }

    public static function contains(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }

        return in_array($slug, self::slugs(), true);
    }

    public static function inferModule(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return 'general';
        }

        return Str::before($slug, '.');
    }

    public static function inferType(string $slug): string
    {
        $action = Str::after($slug, '.');
        $action = strtolower($action);

        if ($action === 'sidebar') {
            return 'sidebar';
        }

        // Treat pure "view*" as page access by default.
        if ($action === 'view' || str_starts_with($action, 'view-') || $action === 'index' || $action === 'show') {
            return 'page';
        }

        if ($action === 'setting' || str_starts_with($action, 'settings') || str_contains($slug, 'configuration.')) {
            return 'setting';
        }

        if (str_starts_with($action, 'scope') || str_contains($action, 'scope')) {
            return 'scope';
        }

        // Everything else defaults to action (create/edit/delete/export/import/approve/...).
        return 'action';
    }

    public static function inferName(string $slug): string
    {
        $module = self::inferModule($slug);
        $action = Str::after($slug, '.');
        $moduleHuman = Str::of($module)->replace(['-', '_'], ' ')->title()->toString();
        $actionHuman = Str::of($action)->replace(['-', '_'], ' ')->title()->toString();

        return "{$moduleHuman} {$actionHuman}";
    }

    private static function extractJsonBlock(string $contents): string
    {
        $startPos = strpos($contents, self::START_MARKER);
        $endPos = strpos($contents, self::END_MARKER);

        if ($startPos === false || $endPos === false || $endPos <= $startPos) {
            return '';
        }

        $afterStart = substr($contents, $startPos + strlen(self::START_MARKER));
        $block = substr($afterStart, 0, $endPos - ($startPos + strlen(self::START_MARKER)));

        // Remove Blade comment wrappers and trim.
        $block = str_replace(['--}}', '{{--'], '', $block);
        $block = trim($block);

        // Try to isolate JSON object boundaries.
        $firstBrace = strpos($block, '{');
        $lastBrace = strrpos($block, '}');
        if ($firstBrace === false || $lastBrace === false || $lastBrace <= $firstBrace) {
            return '';
        }

        return substr($block, $firstBrace, $lastBrace - $firstBrace + 1);
    }
}

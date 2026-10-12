<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions\PermissionMatrixRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionsSeeder extends Seeder
{
    private function determineType($slug)
    {
        if (str_contains($slug, 'sidebar')) {
            return Permission::TYPE_SIDEBAR;
        }
        if (str_contains($slug, 'view-all') || str_contains($slug, 'view-own') || str_contains($slug, 'view-user')) {
            return Permission::TYPE_SCOPE;
        }
        if (str_contains($slug, 'view-profile') || str_contains($slug, 'edit-password')) {
            return Permission::TYPE_SETTING;
        }

        // Buttons
        if (str_contains($slug, 'print') ||
            str_contains($slug, 'export') ||
            str_contains($slug, 'download') ||
            str_contains($slug, 'import') ||
            str_contains($slug, 'sync') ||
            str_contains($slug, 'finalize') ||
            str_contains($slug, 'revert') ||
            str_contains($slug, 'approve') ||
            str_contains($slug, 'reject') ||
            str_contains($slug, 'transfer')) {
            return Permission::TYPE_BUTTON;
        }

        // Icons
        if (str_contains($slug, 'card.view') ||
            str_contains($slug, 'timeline') ||
            str_contains($slug, 'audit-log') ||
            str_contains($slug, 'history') ||
            str_contains($slug, 'list') ||
            str_contains($slug, 'tree')) {
            return Permission::TYPE_ICON;
        }

        if (str_contains($slug, 'view') || str_contains($slug, 'show')) {
            return Permission::TYPE_PAGE;
        }

        return Permission::TYPE_ACTION;
    }

    public function run()
    {
        $this->call(DisbursementWorkflowPermissionsSeeder::class);

        // 1. Migrate renamed permissions to keep existing role assignments
        $migrations = [
            'projects.show' => 'projects.view-details',
            'project-requests.show' => 'project-requests.view-details',
        ];

        foreach ($migrations as $old => $new) {
            $oldPerm = Permission::where('slug', $old)->first();
            if ($oldPerm) {
                $newPerm = Permission::where('slug', $new)->first();
                if ($newPerm) {
                    // Sync roles from old to new before deleting
                    $roleIds = DB::table('role_permission')
                        ->where('permission_id', $oldPerm->id)
                        ->pluck('role_id');

                    foreach ($roleIds as $roleId) {
                        DB::table('role_permission')->updateOrInsert(
                            ['role_id' => $roleId, 'permission_id' => $newPerm->id]
                        );
                    }
                    $oldPerm->delete();
                } else {
                    // Just rename the slug so existing role_permission links stay valid
                    $oldPerm->update(['slug' => $new]);
                }
            }
        }

        // 2. Load permissions dynamically from the matrix registry
        $registry = PermissionMatrixRegistry::read();
        $permissions = $registry['permissions'] ?? [];

        foreach ($permissions as $perm) {
            $slug = trim((string) ($perm['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $module = ! empty($perm['module']) ? $perm['module'] : PermissionMatrixRegistry::inferModule($slug);
            $type = ! empty($perm['type']) ? $perm['type'] : $this->determineType($slug);
            $name = ! empty($perm['name']) ? $perm['name'] : PermissionMatrixRegistry::inferName($slug);
            $description = ! empty($perm['description']) ? $perm['description'] : null;

            Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'module' => $module,
                    'type' => $type,
                    'description' => $description,
                ]
            );
        }

        // 3. Clear permission cache for all roles to ensure changes take effect immediately
        try {
            $roleIds = Role::pluck('id');
            foreach ($roleIds as $roleId) {
                User::incrementRolePermissionsVersion($roleId);
            }
            Log::info('Permission cache invalidated for all roles after seeding.');
        } catch (\Exception $e) {
            Log::warning('Could not invalidate permission cache: '.$e->getMessage());
        }
    }
}

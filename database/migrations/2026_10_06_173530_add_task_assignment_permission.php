<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'task.assign')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Task Assign Users',
                'slug' => 'task.assign',
                'module' => 'task',
                'type' => 'action',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sidebarPermissionId = DB::table('permissions')->where('slug', 'tasks.sidebar')->value('id');
        if (! $sidebarPermissionId) {
            return;
        }

        $roleIds = DB::table('role_permission')
            ->where('permission_id', $sidebarPermissionId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $hasAssignmentPermission = DB::table('role_permission')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $hasAssignmentPermission) {
                DB::table('role_permission')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'entity_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            Cache::increment("role_perms_version_{$roleId}");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'task.assign')->value('id');
        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('role_permission')->where('permission_id', $permissionId)->pluck('role_id');
        DB::table('role_permission')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();

        foreach ($roleIds as $roleId) {
            Cache::increment("role_perms_version_{$roleId}");
        }
    }
};

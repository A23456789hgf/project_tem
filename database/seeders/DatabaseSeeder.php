<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Permission types and permissions
            PermissionTypeSeeder::class,
            PermissionsSeeder::class,
            PermissionsTableSeeder::class,

            // Roles and role permissions
            RoleSeeder::class,
            RolePermissionsSeeder::class,
            AssignDefaultRolePermissionsSeeder::class,

            // Specific permissions
            AdminPermissionsSeeder::class,
            DashboardPermissionsSeeder::class,
            ChatPermissionSeeder::class,
            ReportsPermissionsSeeder::class,
            ErpStockReportsPermissionsSeeder::class,
            SmsPermissionsSeeder::class,
            SyncTaskPermissionsSeeder::class,
            ValueChainsPermissionsSeeder::class,
            ParallelScopePermissionsSeeder::class,
            DisbursementWorkflowPermissionsSeeder::class,

            // Super Admin
            SuperAdminSeeder::class,

            // Existing user seeders
            DefaultUserSeeder::class,
            UserSeeder::class,

            // Other existing seeder
            AuthorityTemplateSeeder::class,
        ]);
    }
}

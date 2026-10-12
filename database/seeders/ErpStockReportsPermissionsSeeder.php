<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ErpStockReportsPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            [
                'name' => 'عرض تقرير طلبات المواد',
                'slug' => 'reports.material_requests.view',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_PAGE,
                'description' => 'عرض تقرير طلبات المواد من ERPNext',
            ],
            [
                'name' => 'طباعة تقرير طلبات المواد',
                'slug' => 'reports.material_requests.print',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'طباعة تقرير طلبات المواد',
            ],
            [
                'name' => 'تصدير تقرير طلبات المواد',
                'slug' => 'reports.material_requests.export',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'تصدير تقرير طلبات المواد',
            ],
            [
                'name' => 'عرض تفاصيل طلبات المواد',
                'slug' => 'reports.material_requests.view_details',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'عرض تفاصيل طلب المواد (الاصناف)',
            ],
            [
                'name' => 'عرض تقرير صرف المواد',
                'slug' => 'reports.stock_entries.view',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_PAGE,
                'description' => 'عرض تقرير صرف المواد (Stock Entries) من ERPNext',
            ],
            [
                'name' => 'طباعة تقرير صرف المواد',
                'slug' => 'reports.stock_entries.print',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'طباعة تقرير صرف المواد',
            ],
            [
                'name' => 'تصدير تقرير صرف المواد',
                'slug' => 'reports.stock_entries.export',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'تصدير تقرير صرف المواد',
            ],
            [
                'name' => 'عرض تفاصيل صرف المواد',
                'slug' => 'reports.stock_entries.view_details',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'عرض تفاصيل حركة صرف المواد (الاصناف)',
            ],
            [
                'name' => 'عرض سجل المخزون',
                'slug' => 'reports.stock_ledger.view',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_PAGE,
                'description' => 'عرض سجل المخزون (Stock Ledger) من ERPNext',
            ],
            [
                'name' => 'طباعة سجل المخزون',
                'slug' => 'reports.stock_ledger.print',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'طباعة سجل المخزون',
            ],
            [
                'name' => 'تصدير سجل المخزون',
                'slug' => 'reports.stock_ledger.export',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'تصدير سجل المخزون',
            ],
            [
                'name' => 'عرض تقرير فواتير الشراء',
                'slug' => 'reports.purchase_invoices.view',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_PAGE,
                'description' => 'عرض تقرير فواتير الشراء (Purchase Invoices) من ERPNext',
            ],
            [
                'name' => 'طباعة تقرير فواتير الشراء',
                'slug' => 'reports.purchase_invoices.print',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'طباعة تقرير فواتير الشراء',
            ],
            [
                'name' => 'تصدير تقرير فواتير الشراء',
                'slug' => 'reports.purchase_invoices.export',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'تصدير تقرير فواتير الشراء',
            ],
            [
                'name' => 'عرض تفاصيل فواتير الشراء',
                'slug' => 'reports.purchase_invoices.view_details',
                'module' => 'reports-erp-stock',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'عرض تفاصيل فاتورة الشراء (الاصناف)',
            ],
        ];

        $superAdminRole = Role::where('name', 'Super Admin')->orWhere('id', 1)->first();
        $adminRole = Role::where('name', 'Admin')->orWhere('id', 2)->first();

        foreach ($permissions as $permData) {
            $permission = Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                [
                    'name' => $permData['name'],
                    'module' => $permData['module'],
                    'type' => $permData['type'],
                    'description' => $permData['description'],
                ]
            );

            if ($superAdminRole) {
                RolePermission::firstOrCreate([
                    'role_id' => $superAdminRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
            if ($adminRole) {
                RolePermission::firstOrCreate([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }

        try {
            $roleIds = Role::pluck('id');
            foreach ($roleIds as $roleId) {
                User::incrementRolePermissionsVersion($roleId);
            }
            Log::info('ERP Stock Reports permissions seeded successfully.');
        } catch (\Exception $e) {
            Log::warning('Could not invalidate permission cache: '.$e->getMessage());
        }
    }
}

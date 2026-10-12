<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DisbursementWorkflowPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'Unified Project Requests Sidebar',
                'slug' => 'project_requests.sidebar',
                'module' => 'project_requests',
                'type' => Permission::TYPE_SIDEBAR,
                'description' => 'Show unified project requests navigation.',
            ],
            [
                'name' => 'Unified Project Requests View',
                'slug' => 'project_requests.view',
                'module' => 'project_requests',
                'type' => Permission::TYPE_PAGE,
                'description' => 'View unified project requests.',
            ],
            [
                'name' => 'Unified Project Requests Create',
                'slug' => 'project_requests.create',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION,
                'description' => 'Create unified project requests.',
            ],
            [
                'name' => 'Unified Project Requests Edit',
                'slug' => 'project_requests.edit',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION,
                'description' => 'Edit unified project requests.',
            ],
            [
                'name' => 'Unified Project Requests Submit',
                'slug' => 'project_requests.submit',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION,
                'description' => 'Submit unified project requests for approval.',
            ],
            [
                'name' => 'Unified Project Requests Approve',
                'slug' => 'project_requests.approve',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION,
                'description' => 'Approve unified project requests.',
            ],
            [
                'name' => 'Unified Project Requests Reject',
                'slug' => 'project_requests.reject',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION,
                'description' => 'Reject unified project requests.',
            ],
            [
                'name' => 'Unified Project Requests Print',
                'slug' => 'project_requests.print',
                'module' => 'project_requests',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'Print unified project requests.',
            ],
            // Disbursement Plan & Sidebar
            [
                'name' => 'عرض خطط الصرف في القائمة الجانبية',
                'slug' => 'disbursements.sidebar',
                'module' => 'disbursements',
                'type' => Permission::TYPE_SIDEBAR,
                'description' => 'عرض رابط خطط ودفعات الصرف في القائمة الجانبية',
            ],
            [
                'name' => 'عرض خطة الصرف',
                'slug' => 'disbursements.view',
                'module' => 'disbursements',
                'type' => Permission::TYPE_PAGE,
                'description' => 'عرض خطة الصرف وجدول الدفعات للمشروع',
            ],
            [
                'name' => 'إنشاء وتعديل خطة الصرف',
                'slug' => 'disbursements.create',
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => 'إعداد وجدولة دفعات خطة الصرف للمشروع',
            ],
            [
                'name' => 'اعتماد خطة الصرف',
                'slug' => 'disbursements.approve',
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => 'اعتماد خطة الصرف وتوجيه الدفعة الأولى للصرف',
            ],
            [
                'name' => 'إعادة محاولة الصرف',
                'slug' => 'disbursements.retry',
                'module' => 'disbursements',
                'type' => Permission::TYPE_BUTTON,
                'description' => 'إعادة محاولة صرف دفعة مالية فشل قيدها في ERPNext',
            ],

            // Payment Requests
            [
                'name' => 'عرض طلبات الصرف',
                'slug' => 'payments.view',
                'module' => 'disbursements',
                'type' => Permission::TYPE_PAGE,
                'description' => 'عرض طلبات صرف الدفعات اللاحقة',
            ],
            [
                'name' => 'تقديم طلب صرف',
                'slug' => 'payments.create',
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => 'تقديم طلب صرف للدفعة التالية بعد إخلاء السابقة',
            ],
            [
                'name' => 'اعتماد طلب صرف',
                'slug' => 'payments.approve',
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => 'اعتماد طلب الصرف وإرسال القيد المالي لـ ERPNext',
            ],
            [
                'name' => 'رفض أو إلغاء طلب صرف',
                'slug' => 'payments.reject',
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => 'رفض أو إلغاء طلب صرف وإعادة الدفعة للمخطط',
            ],

            // Clearance Requests
            [
                'name' => 'عرض طلبات الإخلاء',
                'slug' => 'clearances.view',
                'module' => 'disbursements',
                'type' => Permission::TYPE_PAGE,
                'description' => 'عرض طلبات الإخلاء وسجلات الإنجاز المرفقة بها',
            ],
            [
                'name' => 'تقديم طلب إخلاء',
                'slug' => 'clearances.create',
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => 'تقديم طلب إخلاء مالي للدفعة المصروفة',
            ],
            [
                'name' => 'اعتماد طلب إخلاء',
                'slug' => 'clearances.approve',
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => 'اعتماد طلب الإخلاء وفتح إمكانية طلب الدفعة التالية',
            ],
            [
                'name' => 'رفض أو إلغاء طلب إخلاء',
                'slug' => 'clearances.reject',
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => 'رفض أو إلغاء طلب الإخلاء وتحرير سجلات التنفيذ المرتبطة به',
            ],
        ];

        foreach ([
            'financial-justifications.submit' => 'Submit financial justification',
            'execution.manager-review' => 'Review execution as entity manager',
            'execution.aggregate-clearance' => 'Aggregate installment execution for clearance',
            'disbursements.request' => 'Request next installment',
        ] as $slug => $name) {
            $permissions[] = [
                'name' => $name,
                'slug' => $slug,
                'module' => 'disbursements',
                'type' => Permission::TYPE_ACTION,
                'description' => $name,
            ];
        }

        $permissions[] = [
            'name' => 'Disbursements Reject',
            'slug' => 'disbursements.reject',
            'module' => 'disbursements',
            'type' => Permission::TYPE_ACTION,
            'description' => 'Reject and cancel payment requests.',
        ];

        $roles = Role::whereIn('name', ['Super Admin', 'Admin', 'manager'])
            ->orWhereIn('id', [1, 2])
            ->get();

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

            foreach ($roles as $role) {
                RolePermission::firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }

        try {
            foreach ($roles as $role) {
                User::incrementRolePermissionsVersion($role->id);
            }
            Log::info('Disbursement workflow permissions seeded successfully.');
        } catch (\Exception $e) {
            Log::warning('Could not invalidate permission cache: '.$e->getMessage());
        }
    }
}

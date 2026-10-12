<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'عرض إدارة الطلبات في القائمة الجانبية',
                'slug' => 'project_requests.sidebar',
                'module' => 'project_requests',
                'type' => Permission::TYPE_SIDEBAR ?? 'sidebar',
                'description' => 'عرض رابط إدارة طلبات المشاريع الموحدة في القائمة الجانبية',
            ],
            [
                'name' => 'عرض طلبات المشاريع الموحدة',
                'slug' => 'project_requests.view',
                'module' => 'project_requests',
                'type' => Permission::TYPE_PAGE ?? 'page',
                'description' => 'عرض قائمة وتفاصيل طلبات المشاريع الموحدة (إخلاء، صرف، إغلاق)',
            ],
            [
                'name' => 'إنشاء طلب مشروع موحد',
                'slug' => 'project_requests.create',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION ?? 'action',
                'description' => 'تقديم طلب جديد (إخلاء، صرف، إخلاء وصرف، إخلاء وإغلاق)',
            ],
            [
                'name' => 'تعديل طلب مشروع موحد',
                'slug' => 'project_requests.edit',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION ?? 'action',
                'description' => 'تعديل بيانات طلب المشروع الموحد في حالة المسودة أو قيد الإجراء',
            ],
            [
                'name' => 'تقديم طلب مشروع موحد',
                'slug' => 'project_requests.submit',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION ?? 'action',
                'description' => 'إرسال طلب المشروع الموحد للمراجعة والاعتماد',
            ],
            [
                'name' => 'اعتماد طلب مشروع موحد',
                'slug' => 'project_requests.approve',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION ?? 'action',
                'description' => 'الموافقة والاعتماد المالي والإداري لطلب المشروع الموحد',
            ],
            [
                'name' => 'رفض طلب مشروع موحد',
                'slug' => 'project_requests.reject',
                'module' => 'project_requests',
                'type' => Permission::TYPE_ACTION ?? 'action',
                'description' => 'رفض أو إلغاء طلب المشروع الموحد مع تسجيل السبب',
            ],
            [
                'name' => 'طباعة وثيقة طلب مشروع موحد',
                'slug' => 'project_requests.print',
                'module' => 'project_requests',
                'type' => Permission::TYPE_BUTTON ?? 'button',
                'description' => 'طباعة الوثيقة الرسمية لطلب المشروع الموحد بترويسة المنظمة',
            ],
        ];

        foreach ($permissions as $permData) {
            Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Permission records are managed by idempotent seeders so role assignments are preserved.
    }
};

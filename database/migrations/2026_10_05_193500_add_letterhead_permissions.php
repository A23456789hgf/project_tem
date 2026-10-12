<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'عرض قائمة الترويسات بالقائمة الجانبية',
                'slug' => 'letterheads.sidebar',
                'module' => 'letterheads',
                'type' => 'sidebar',
                'description' => 'إظهار عنصر إدارة الترويسات في القائمة الجانبية',
            ],
            [
                'name' => 'عرض الترويسات وإعدادات الطباعة',
                'slug' => 'letterheads.view',
                'module' => 'letterheads',
                'type' => 'page',
                'description' => 'الوصول إلى صفحة إدارة الترويسات وإعدادات الطباعة',
            ],
            [
                'name' => 'إنشاء ترويسة جديدة',
                'slug' => 'letterheads.create',
                'module' => 'letterheads',
                'type' => 'action',
                'description' => 'إضافة ترويسة جديدة لجهة معينة',
            ],
            [
                'name' => 'تعديل الترويسات وإعدادات الطباعة',
                'slug' => 'letterheads.update',
                'module' => 'letterheads',
                'type' => 'action',
                'description' => 'تحديث نصوص وشعارات وهوامش وتذييل الترويسات',
            ],
            [
                'name' => 'حذف الترويسة',
                'slug' => 'letterheads.delete',
                'module' => 'letterheads',
                'type' => 'action',
                'description' => 'حذف ترويسة جهة من النظام',
            ],
            [
                'name' => 'استخدام الترويسة في الطباعة',
                'slug' => 'letterheads.use',
                'module' => 'letterheads',
                'type' => 'action',
                'description' => 'استخدام الترويسة الرسمية في طباعة المستندات والتقارير والمذكرات',
            ],
            [
                'name' => 'اختيار ترويسة جهة أخرى ضمن النطاق',
                'slug' => 'letterheads.select-other',
                'module' => 'letterheads',
                'type' => 'action',
                'description' => 'إمكانية تغيير الترويسة واختيار ترويسة جهة أخرى ضمن النطاق الإداري والجغرافي',
            ],
            [
                'name' => 'التطبيق الجماعي لإعدادات الترويسة',
                'slug' => 'letterheads.bulk-update',
                'module' => 'letterheads',
                'type' => 'action',
                'description' => 'تطبيق إعدادات الترويسة والطباعة على عدة جهات دفعة واحدة',
            ],
            [
                'name' => 'نسخ إعدادات الترويسة بين الجهات',
                'slug' => 'letterheads.copy-settings',
                'module' => 'letterheads',
                'type' => 'action',
                'description' => 'نسخ عناصر محددة من ترويسة جهة إلى جهات أخرى',
            ],
            [
                'name' => 'تعيين الترويسة الافتراضية والوراثة الهرمية',
                'slug' => 'letterheads.set-default',
                'module' => 'letterheads',
                'type' => 'action',
                'description' => 'تعيين الترويسة الافتراضية لفرع إداري وضبط الوراثة الهرمية',
            ],
        ];

        $adminRoles = Role::where('name', 'like', '%admin%')
            ->orWhere('name', 'like', '%مدير%')
            ->orWhere('full_access', true)
            ->get();

        foreach ($permissions as $permData) {
            $perm = Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );

            foreach ($adminRoles as $role) {
                RolePermission::firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $perm->id,
                ]);
            }
        }

        // Clear permissions cache
        Cache::flush();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = [
            'letterheads.sidebar',
            'letterheads.view',
            'letterheads.create',
            'letterheads.update',
            'letterheads.delete',
            'letterheads.use',
            'letterheads.select-other',
            'letterheads.bulk-update',
            'letterheads.copy-settings',
            'letterheads.set-default',
        ];

        Permission::whereIn('slug', $slugs)->delete();
        Cache::flush();
    }
};

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
                'name' => 'عرض المراجعة المالية (API)',
                'slug' => 'financial-review.read',
                'description' => 'الاطلاع على قائمة وتفاصيل المشاريع في مرحلة المراجعة المالية عبر الـ API',
                'module' => 'financial-review',
                'type' => Permission::TYPE_ACTION,
            ],
            [
                'name' => 'تعديل البيانات المالية والملاحظات (API)',
                'slug' => 'financial-review.update',
                'description' => 'تعديل التكاليف المالية وإضافة الملاحظات أثناء المراجعة المالية عبر الـ API',
                'module' => 'financial-review',
                'type' => Permission::TYPE_ACTION,
            ],
            [
                'name' => 'اعتماد المراجعة المالية (API)',
                'slug' => 'financial-review.approve',
                'description' => 'اعتماد مرحلة المراجعة المالية ونقل المشروع للمرحلة التالية عبر الـ API',
                'module' => 'financial-review',
                'type' => Permission::TYPE_ACTION,
            ],
            [
                'name' => 'رفض المراجعة المالية (API)',
                'slug' => 'financial-review.reject',
                'description' => 'رفض المشروع أثناء مرحلة المراجعة المالية عبر الـ API',
                'module' => 'financial-review',
                'type' => Permission::TYPE_ACTION,
            ],
            [
                'name' => 'إرجاع/استكمال المراجعة المالية (API)',
                'slug' => 'financial-review.return',
                'description' => 'طلب استكمال أو إرجاع المشروع أثناء مرحلة المراجعة المالية عبر الـ API',
                'module' => 'financial-review',
                'type' => Permission::TYPE_ACTION,
            ],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                $perm
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('slug', [
            'financial-review.read',
            'financial-review.update',
            'financial-review.approve',
            'financial-review.reject',
            'financial-review.return',
        ])->delete();
    }
};

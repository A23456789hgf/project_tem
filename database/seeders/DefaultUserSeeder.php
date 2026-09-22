<?php

namespace Database\Seeders;

use App\Models\InternalEntity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    public function run()
    {
        // Ensure Admin role exists with full access
        $adminRole = Role::updateOrCreate(
            ['name' => 'Admin'],
            [
                'description' => 'مدير النظام (كامل الصلاحيات)',
                'is_active' => true,
                'full_access' => true,
            ]
        );

        // جلب الإدارة العامة لتخصيصها لمستخدم النظام
        $hq = InternalEntity::where('name', 'وزارة الزراعة والثروة السمكية والموارد المائية')->first();

        $existingRoot = User::withoutGlobalScopes()->where('username', 'root')->first();
        $rootPhone = $existingRoot?->phone ?: '777777771';

        // تجنب تكرار رقم الهاتف مع أي مستخدم آخر
        if (User::withoutGlobalScopes()->where('phone', $rootPhone)->where('username', '!=', 'root')->exists()) {
            $rootPhone = '777777771';
            if (User::withoutGlobalScopes()->where('phone', $rootPhone)->where('username', '!=', 'root')->exists()) {
                $rootPhone = '777000001';
            }
        }

        // إنشاء أو تحديث المستخدم root
        User::withoutGlobalScopes()->updateOrCreate(
            ['username' => 'root'],
            [
                'user_id' => 'root',
                'name' => 'System Administrator (Root)',
                'email' => 'root@example.com',
                'password' => Hash::make('root@2026'),
                'phone' => $rootPhone,
                'role_id' => $adminRole->id,
                'entity_id' => $hq ? $hq->id : null,
                'status' => 'Active',
            ]
        );

        $this->command->info('✅ المستخدم الافتراضي root تم إنشاؤه أو تحديثه بنجاح!');
    }
}

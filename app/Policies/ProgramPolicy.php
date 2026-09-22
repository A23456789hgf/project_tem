<?php

namespace App\Policies;

use App\Models\User;

class ProgramPolicy
{
    /**
     * عرض البرامج
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('programs.view');
    }

    /**
     * عرض برنامج واحد
     */
    public function view(User $user, $program = null): bool
    {
        return $user->hasPermission('programs.view');
    }

    /**
     * إنشاء برنامج
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('programs.create');
    }

    /**
     * تعديل برنامج
     */
    public function update(User $user, $program = null): bool
    {
        return $user->hasPermission('programs.edit');
    }

    /**
     * حذف برنامج
     */
    public function delete(User $user, $program = null): bool
    {
        return $user->hasPermission('programs.delete');
    }

    /**
     * تصدير البرامج
     */
    public function export(User $user, $program = null): bool
    {
        return $user->hasPermission('programs.export');
    }

    /**
     * استيراد البرامج
     */
    public function import(User $user, $program = null): bool
    {
        return $user->hasPermission('programs.import') || $user->hasPermission('programs.create');
    }

    /**
     * اعتماد البرنامج
     */
    public function approve(User $user, $program = null): bool
    {
        return $user->hasPermission('programs.approve');
    }

    /**
     * رفض البرنامج
     */
    public function reject(User $user, $program = null): bool
    {
        return $user->hasPermission('programs.reject');
    }
}

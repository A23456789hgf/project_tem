<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('units.view') || $user->hasPermission('units.index');
    }

    public function view(User $user, ?Unit $model = null): bool
    {
        return $user->hasPermission('units.view') || $user->hasPermission('units.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('units.create');
    }

    public function update(User $user, ?Unit $model = null): bool
    {
        return $user->hasPermission('units.update') || $user->hasPermission('units.edit');
    }

    public function delete(User $user, ?Unit $model = null): bool
    {
        return $user->hasPermission('units.delete') || $user->hasPermission('units.destroy');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('units.import') || $user->hasPermission('units.create');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('units.export') || $user->hasPermission('units.view');
    }

    public function approve(User $user, ?Unit $model = null): bool
    {
        return $user->hasPermission('units.approve');
    }

    public function reject(User $user, ?Unit $model = null): bool
    {
        return $user->hasPermission('units.reject');
    }
}

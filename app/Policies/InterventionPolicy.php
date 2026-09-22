<?php

namespace App\Policies;

use App\Models\Intervention;
use App\Models\User;

class InterventionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('interventions.view') || $user->hasPermission('interventions.index');
    }

    public function view(User $user, ?Intervention $model = null): bool
    {
        return $user->hasPermission('interventions.view') || $user->hasPermission('interventions.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('interventions.create');
    }

    public function update(User $user, ?Intervention $model = null): bool
    {
        return $user->hasPermission('interventions.update') || $user->hasPermission('interventions.edit');
    }

    public function delete(User $user, ?Intervention $model = null): bool
    {
        return $user->hasPermission('interventions.delete') || $user->hasPermission('interventions.destroy');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('interventions.import') || $user->hasPermission('interventions.create');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('interventions.export') || $user->hasPermission('interventions.view');
    }

    public function approve(User $user, ?Intervention $model = null): bool
    {
        return $user->hasPermission('interventions.approve');
    }

    public function reject(User $user, ?Intervention $model = null): bool
    {
        return $user->hasPermission('interventions.reject');
    }
}

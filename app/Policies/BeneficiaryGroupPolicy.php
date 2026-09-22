<?php

namespace App\Policies;

use App\Models\BeneficiaryGroup;
use App\Models\User;

class BeneficiaryGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('beneficiary-groups.view') || $user->hasPermission('beneficiary-groups.index');
    }

    public function view(User $user, ?BeneficiaryGroup $model = null): bool
    {
        return $user->hasPermission('beneficiary-groups.view') || $user->hasPermission('beneficiary-groups.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('beneficiary-groups.create');
    }

    public function update(User $user, ?BeneficiaryGroup $model = null): bool
    {
        return $user->hasPermission('beneficiary-groups.update') || $user->hasPermission('beneficiary-groups.edit');
    }

    public function delete(User $user, ?BeneficiaryGroup $model = null): bool
    {
        return $user->hasPermission('beneficiary-groups.delete') || $user->hasPermission('beneficiary-groups.destroy');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('beneficiary-groups.import') || $user->hasPermission('beneficiary-groups.create');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('beneficiary-groups.export') || $user->hasPermission('beneficiary-groups.view');
    }

    public function approve(User $user, ?BeneficiaryGroup $model = null): bool
    {
        return $user->hasPermission('beneficiary-groups.approve');
    }

    public function reject(User $user, ?BeneficiaryGroup $model = null): bool
    {
        return $user->hasPermission('beneficiary-groups.reject');
    }
}

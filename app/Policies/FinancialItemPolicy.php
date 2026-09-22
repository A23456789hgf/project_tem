<?php

namespace App\Policies;

use App\Models\FinancialItem;
use App\Models\User;

class FinancialItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('financial-items.view') || $user->hasPermission('financial-items.index');
    }

    public function view(User $user, ?FinancialItem $model = null): bool
    {
        return $user->hasPermission('financial-items.view') || $user->hasPermission('financial-items.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('financial-items.create');
    }

    public function update(User $user, ?FinancialItem $model = null): bool
    {
        return $user->hasPermission('financial-items.update') || $user->hasPermission('financial-items.edit');
    }

    public function delete(User $user, ?FinancialItem $model = null): bool
    {
        return $user->hasPermission('financial-items.delete') || $user->hasPermission('financial-items.destroy');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('financial-items.import') || $user->hasPermission('financial-items.create');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('financial-items.export') || $user->hasPermission('financial-items.view');
    }

    public function approve(User $user, ?FinancialItem $model = null): bool
    {
        return $user->hasPermission('financial-items.approve');
    }

    public function reject(User $user, ?FinancialItem $model = null): bool
    {
        return $user->hasPermission('financial-items.reject');
    }
}

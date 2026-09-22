<?php

namespace App\Policies;

use App\Models\Subdomain;
use App\Models\User;

class SubdomainPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('subdomains.view') || $user->hasPermission('subdomains.index');
    }

    public function view(User $user, ?Subdomain $model = null): bool
    {
        return $user->hasPermission('subdomains.view') || $user->hasPermission('subdomains.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('subdomains.create');
    }

    public function update(User $user, ?Subdomain $model = null): bool
    {
        return $user->hasPermission('subdomains.update') || $user->hasPermission('subdomains.edit');
    }

    public function delete(User $user, ?Subdomain $model = null): bool
    {
        return $user->hasPermission('subdomains.delete') || $user->hasPermission('subdomains.destroy');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('subdomains.import') || $user->hasPermission('subdomains.create');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('subdomains.export') || $user->hasPermission('subdomains.view');
    }

    public function approve(User $user, ?Subdomain $model = null): bool
    {
        return $user->hasPermission('subdomains.approve');
    }

    public function reject(User $user, ?Subdomain $model = null): bool
    {
        return $user->hasPermission('subdomains.reject');
    }
}

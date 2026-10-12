<?php

namespace App\Policies;

use App\Models\OrganizationLetterhead;
use App\Models\User;
use App\Services\LetterheadAccessService;

class OrganizationLetterheadPolicy
{
    public function __construct(
        protected LetterheadAccessService $letterheadAccessService
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || ($user->role && $user->role->full_access)
            || $user->hasPermission('letterheads.view')
            || $user->hasPermission('letterheads.index');
    }

    public function view(User $user, ?OrganizationLetterhead $letterhead = null): bool
    {
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return true;
        }

        if (! $user->hasPermission('letterheads.view')) {
            return false;
        }

        if (! $letterhead) {
            return true;
        }

        return $this->letterheadAccessService->canUserUseLetterhead($user, $letterhead);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin()
            || ($user->role && $user->role->full_access)
            || $user->hasPermission('letterheads.create');
    }

    public function update(User $user, OrganizationLetterhead $letterhead): bool
    {
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return true;
        }

        if (! $user->hasPermission('letterheads.update') && ! $user->hasPermission('letterheads.edit')) {
            return false;
        }

        if ($letterhead->is_global_default) {
            return $user->isAdmin();
        }

        return $this->letterheadAccessService->canUserManageOrganization(
            $user,
            $letterhead->organization_type,
            (int) $letterhead->organization_id
        );
    }

    public function delete(User $user, OrganizationLetterhead $letterhead): bool
    {
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return true;
        }

        if (! $user->hasPermission('letterheads.delete') && ! $user->hasPermission('letterheads.destroy')) {
            return false;
        }

        if ($letterhead->is_global_default) {
            return $user->isAdmin();
        }

        return $this->letterheadAccessService->canUserManageOrganization(
            $user,
            $letterhead->organization_type,
            (int) $letterhead->organization_id
        );
    }

    public function use(User $user, OrganizationLetterhead $letterhead): bool
    {
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return true;
        }

        return $this->letterheadAccessService->canUserUseLetterhead($user, $letterhead);
    }

    public function selectOther(User $user): bool
    {
        return $user->isAdmin()
            || ($user->role && $user->role->full_access)
            || $user->hasPermission('letterheads.select-other')
            || $user->hasPermission('letterheads.select');
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->isAdmin()
            || ($user->role && $user->role->full_access)
            || $user->hasPermission('letterheads.bulk-update');
    }

    public function copySettings(User $user): bool
    {
        return $user->isAdmin()
            || ($user->role && $user->role->full_access)
            || $user->hasPermission('letterheads.copy-settings');
    }
}

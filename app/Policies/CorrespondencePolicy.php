<?php

namespace App\Policies;

use App\Models\Correspondence;
use App\Models\User;
use App\Services\CorrespondenceOrganizationResolver;
use App\Services\OrganizationResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class CorrespondencePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('correspondence.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Correspondence $correspondence): bool
    {
        return $user->hasPermission('correspondence.view', $correspondence);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('correspondence.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Correspondence $correspondence): bool
    {
        // Only the sender can update the correspondence, and only if it can be edited
        return $user->hasPermission('correspondence.edit', $correspondence) &&
               $correspondence->sender_user_id === $user->id &&
               $correspondence->canBeEdited();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Correspondence $correspondence): bool
    {
        return $user->hasPermission('correspondence.delete', $correspondence) &&
               $correspondence->sender_user_id === $user->id &&
               $correspondence->canBeDeleted();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Correspondence $correspondence): bool
    {
        return $user->hasPermission('correspondence.restore', $correspondence);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Correspondence $correspondence): bool
    {
        return $user->hasPermission('correspondence.delete', $correspondence);
    }

    /**
     * Determine whether the user can reply to the correspondence.
     */
    public function reply(User $user, Correspondence $correspondence): bool
    {
        return $user->hasPermission('correspondence.reply', $correspondence) &&
               $correspondence->canBeRepliedBy($user);
    }

    /**
     * Determine whether the user can refer the correspondence.
     */
    public function refer(User $user, Correspondence $correspondence): bool
    {
        return $user->hasPermission('correspondence.referral', $correspondence) &&
               $correspondence->canBeReferredBy($user);
    }

    public function updateReferralStatus(User $user, Correspondence $correspondence): bool
    {
        return $user->hasPermission('correspondence.referral', $correspondence);
    }

    public function updateReplyStatus(User $user, Correspondence $correspondence): bool
    {
        return $user->hasPermission('correspondence.reply', $correspondence);
    }

    /**
     * Determine whether the user can forward the correspondence.
     */
    public function forward(User $user, Correspondence $correspondence): bool
    {
        $userOrg = app(OrganizationResolver::class)->resolve($user);
        $orgResolver = app(CorrespondenceOrganizationResolver::class);

        return $user->hasPermission('correspondence.forward', $correspondence) &&
               ($orgResolver->matchesUserOrganization($userOrg, $correspondence->recipient_type, $correspondence->recipient_entity_id, $correspondence->recipient_authority_id) ||
                $orgResolver->matchesUserOrganization($userOrg, $correspondence->sender_type, $correspondence->sender_entity_id, $correspondence->sender_authority_id));
    }

    /**
     * Determine whether the user can close the correspondence.
     */
    public function close(User $user, Correspondence $correspondence): bool
    {
        $userOrg = app(OrganizationResolver::class)->resolve($user);
        $orgResolver = app(CorrespondenceOrganizationResolver::class);

        return $user->hasPermission('correspondence.close', $correspondence) &&
               ($orgResolver->matchesUserOrganization($userOrg, $correspondence->sender_type, $correspondence->sender_entity_id, $correspondence->sender_authority_id) ||
                $orgResolver->matchesUserOrganization($userOrg, $correspondence->recipient_type, $correspondence->recipient_entity_id, $correspondence->recipient_authority_id));
    }

    /**
     * Determine whether the user can export correspondences.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('correspondence.export');
    }

    /**
     * Determine whether the user can print correspondences.
     */
    public function print(User $user): bool
    {
        return $user->hasPermission('correspondence.print');
    }
}

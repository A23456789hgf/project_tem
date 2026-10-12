<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectClearanceRequest;
use App\Models\User;

class ProjectClearanceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clearances.view');
    }

    public function view(User $user, ?ProjectClearanceRequest $clearanceRequest = null): bool
    {
        $project = $clearanceRequest?->project ?? $clearanceRequest;

        return $user->hasPermission('clearances.view', $project);
    }

    public function create(User $user, $project = null): bool
    {
        $targetProject = $project instanceof Project ? $project : ($project?->project ?? null);

        return $user->hasPermission('clearances.create', $targetProject);
    }

    public function approve(User $user, ?ProjectClearanceRequest $clearanceRequest = null): bool
    {
        $project = $clearanceRequest?->project ?? $clearanceRequest;

        return $user->hasPermission('clearances.approve', $project);
    }

    public function reject(User $user, ?ProjectClearanceRequest $clearanceRequest = null): bool
    {
        $project = $clearanceRequest?->project ?? $clearanceRequest;

        return $user->hasPermission('clearances.reject', $project);
    }
}

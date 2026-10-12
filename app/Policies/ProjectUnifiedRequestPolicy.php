<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectUnifiedRequest;
use App\Models\User;

class ProjectUnifiedRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->hasPermission('project_requests.view')
            || $user->hasPermission('payments.view')
            || $user->hasPermission('clearances.view')
            || $user->hasPermission('disbursements.view');
    }

    public function view(User $user, ?ProjectUnifiedRequest $unifiedRequest = null): bool
    {
        $project = $unifiedRequest?->project;

        return $user->isAdmin()
            || $user->hasPermission('project_requests.view', $project)
            || $user->hasPermission('payments.view', $project)
            || $user->hasPermission('clearances.view', $project)
            || $user->hasPermission('disbursements.view', $project);
    }

    public function create(User $user, $project = null): bool
    {
        $targetProject = $project instanceof Project ? $project : ($project?->project ?? null);

        return $user->isAdmin()
            || $user->hasPermission('project_requests.create', $targetProject)
            || $user->hasPermission('payments.create', $targetProject)
            || $user->hasPermission('clearances.create', $targetProject)
            || $user->hasPermission('disbursements.create', $targetProject);
    }

    public function update(User $user, ?ProjectUnifiedRequest $unifiedRequest = null): bool
    {
        $project = $unifiedRequest?->project;

        return $user->isAdmin()
            || ((int) $user->id === (int) $unifiedRequest?->created_by)
            || $user->hasPermission('project_requests.edit', $project)
            || $user->hasPermission('payments.create', $project)
            || $user->hasPermission('clearances.create', $project);
    }

    public function submit(User $user, ?ProjectUnifiedRequest $unifiedRequest = null): bool
    {
        $project = $unifiedRequest?->project;

        return $user->isAdmin()
            || ((int) $user->id === (int) $unifiedRequest?->created_by)
            || $user->hasPermission('project_requests.submit', $project)
            || $user->hasPermission('project_requests.edit', $project)
            || $user->hasPermission('payments.create', $project)
            || $user->hasPermission('clearances.create', $project);
    }

    public function approve(User $user, ?ProjectUnifiedRequest $unifiedRequest = null): bool
    {
        $project = $unifiedRequest?->project;

        return $user->isAdmin()
            || $user->hasPermission('project_requests.approve', $project)
            || $user->hasPermission('payments.approve', $project)
            || $user->hasPermission('clearances.approve', $project)
            || $user->hasPermission('disbursements.approve', $project);
    }

    public function reject(User $user, ?ProjectUnifiedRequest $unifiedRequest = null): bool
    {
        $project = $unifiedRequest?->project;

        return $user->isAdmin()
            || $user->hasPermission('project_requests.reject', $project)
            || $user->hasPermission('payments.reject', $project)
            || $user->hasPermission('clearances.reject', $project);
    }

    public function print(User $user, ?ProjectUnifiedRequest $unifiedRequest = null): bool
    {
        $project = $unifiedRequest?->project;

        return $user->isAdmin()
            || $user->hasPermission('project_requests.print', $project)
            || $user->hasPermission('payments.view', $project)
            || $user->hasPermission('clearances.view', $project);
    }
}

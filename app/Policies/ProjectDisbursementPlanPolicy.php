<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectDisbursementPlan;
use App\Models\User;

class ProjectDisbursementPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('disbursements.view');
    }

    public function view(User $user, ?ProjectDisbursementPlan $plan = null): bool
    {
        $project = $plan?->project ?? $plan;

        return $user->hasPermission('disbursements.view', $project);
    }

    public function create(User $user, $project = null): bool
    {
        $targetProject = $project instanceof Project ? $project : ($project?->project ?? null);

        return $user->hasPermission('disbursements.create', $targetProject);
    }

    public function update(User $user, ?ProjectDisbursementPlan $plan = null): bool
    {
        $project = $plan?->project ?? $plan;

        return $user->hasPermission('disbursements.create', $project);
    }

    public function approve(User $user, ?ProjectDisbursementPlan $plan = null): bool
    {
        $project = $plan?->project ?? $plan;

        return $user->hasPermission('disbursements.approve', $project);
    }

    public function retry(User $user, ?ProjectDisbursementPlan $plan = null): bool
    {
        $project = $plan?->project ?? $plan;

        return $user->hasPermission('disbursements.retry', $project);
    }

    public function requestNext(User $user, ?ProjectDisbursementPlan $plan = null): bool
    {
        $project = $plan?->project ?? $plan;

        return $user->hasPermission('disbursements.request', $project);
    }
}

<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectPaymentRequest;
use App\Models\User;

class ProjectPaymentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payments.view');
    }

    public function view(User $user, ?ProjectPaymentRequest $paymentRequest = null): bool
    {
        $project = $paymentRequest?->project ?? $paymentRequest;

        return $user->hasPermission('payments.view', $project);
    }

    public function create(User $user, $project = null): bool
    {
        $targetProject = $project instanceof Project ? $project : ($project?->project ?? null);

        return $user->hasPermission('payments.create', $targetProject);
    }

    public function approve(User $user, ?ProjectPaymentRequest $paymentRequest = null): bool
    {
        $project = $paymentRequest?->project ?? $paymentRequest;

        return $user->hasPermission('payments.approve', $project) || $user->hasPermission('disbursements.approve', $project);
    }

    public function reject(User $user, ?ProjectPaymentRequest $paymentRequest = null): bool
    {
        $project = $paymentRequest?->project ?? $paymentRequest;

        return $user->hasPermission('payments.reject', $project) || $user->hasPermission('disbursements.reject', $project);
    }
}

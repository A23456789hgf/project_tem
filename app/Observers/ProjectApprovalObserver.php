<?php

namespace App\Observers;

use App\Jobs\SendDataToErpNextJob;
use App\Models\Project;
use App\Models\ProjectApproval;

class ProjectApprovalObserver
{
    /**
     * Handle the ProjectApproval "created" event.
     */
    public function created(ProjectApproval $projectApproval): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $project = $projectApproval->project;

        // Check if this is the very first approval record and the project hasn't been synced yet
        if ($project && empty($project->erpnext_project_id) && $project->projectApprovals()->count() === 1) {
            // Lock check: Only dispatch if it hasn't been queued or synced yet
            if ($project->sync_status !== 'queued' && $project->sync_status !== 'synced') {
                // Update quietly to avoid triggering other observers
                $project->updateQuietly(['sync_status' => 'queued']);
                SendDataToErpNextJob::dispatch(Project::class, $project->id, 'sync');
            }
        }
    }

    /**
     * Handle the ProjectApproval "updated" event.
     */
    public function updated(ProjectApproval $projectApproval): void
    {
        //
    }

    /**
     * Handle the ProjectApproval "deleted" event.
     */
    public function deleted(ProjectApproval $projectApproval): void
    {
        //
    }

    /**
     * Handle the ProjectApproval "restored" event.
     */
    public function restored(ProjectApproval $projectApproval): void
    {
        //
    }

    /**
     * Handle the ProjectApproval "force deleted" event.
     */
    public function forceDeleted(ProjectApproval $projectApproval): void
    {
        //
    }
}

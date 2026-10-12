<?php

namespace App\Observers;

use App\Jobs\SendDataToErpNextJob;
use App\Models\Project;
use App\Services\FrappeAPIService;
use Illuminate\Support\Facades\Log;

class ProjectObserver
{
    /**
     * Ensure observer events run after the database transaction has committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    protected FrappeAPIService $frappeService;

    public function __construct(FrappeAPIService $frappeService)
    {
        $this->frappeService = $frappeService;
    }

    /**
     * Handle the Project "created" event.
     * This will run after the project is successfully saved to the database.
     */
    public function created(Project $project): void
    {
        Log::info('========================================');
        Log::info("ProjectObserver: Project created successfully in DB (ID: {$project->id}).");

        $entity = $project->creatorEntity;

        if (! $entity) {
            Log::info('ProjectObserver: No creator entity associated with this project. Skipping ERPNext entity sync.');

            return;
        }

        Log::info("ProjectObserver: Project creator entity is '{$entity->name}' of type '{$entity->entity_type}'.");

        try {
            // This will check if the entity (and its parents) exists in ERPNext.
            // If it doesn't, it creates them automatically.
            Log::info("ProjectObserver: Ensuring entity '{$entity->name}' and its hierarchy exist in ERPNext...");
            $this->frappeService->ensureParentHierarchyExists($entity);
            Log::info('ProjectObserver: Entity hierarchy successfully verified/created in ERPNext.');

            // Determine the relevant Company to fetch Financial Items
            $companyName = null;
            if ($entity->entity_type === 'Company') {
                $companyName = $entity->name;
                Log::info("ProjectObserver: Entity is a Company. Target Company for financial items is '{$companyName}'.");
            } else {
                Log::info('ProjectObserver: Entity is a Department. Identifying parent Company...');
                $companyName = $this->frappeService->findParentCompanyName($entity);
                if ($companyName) {
                    Log::info("ProjectObserver: Parent Company identified as '{$companyName}'.");
                } else {
                    Log::warning("ProjectObserver: Could not identify a parent Company for Department '{$entity->name}'.");
                }
            }

            // Fetch the financial items to prepare them for the costs step
            if ($companyName) {
                Log::info("ProjectObserver: Fetching Expense Claim Types (Financial Items) for Company '{$companyName}'...");
                $items = $this->frappeService->getExpenseClaimTypes($companyName);
                Log::info('ProjectObserver: Successfully fetched '.count($items)." financial items for '{$companyName}'. They are now ready for the costs step.");
            }

        } catch (\Throwable $e) {
            Log::error("ProjectObserver Exception during ERPNext entity sync for Project ID {$project->id}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Stage 1: Push project to ERPNext upon creation in Laravel (Status: Open)
        if (empty($project->erpnext_project_id)) {
            Log::info("ProjectObserver: Project created (ID: {$project->id}). Dispatching initial ERPNext project creation job.");
            try {
                SendDataToErpNextJob::dispatch(Project::class, $project->id, 'create');
            } catch (\Throwable $e) {
                Log::error("ProjectObserver Exception during project creation sync dispatch: {$e->getMessage()}");
            }
        }
    }

    public function updated(Project $project): void
    {
        // 1. Inbound Sync Loop Prevention
        if ($project->isErpnextInbound) {
            return;
        }

        // 2. Unlinked project status update or transition to execution -> Initial Push
        if (empty($project->erpnext_project_id)) {
            if ($project->wasChanged('status')) {
                Log::info("ProjectObserver: Status changed for unlinked Project ID {$project->id}. Dispatching ERPNext sync job.");
                try {
                    SendDataToErpNextJob::dispatch(Project::class, $project->id, 'sync');
                } catch (\Throwable $e) {
                    Log::error("ProjectObserver Exception during initial sync dispatch: {$e->getMessage()}");
                }
            }

            return;
        }

        // 3. Subsequent Updates for Already-Linked Projects
        if (! empty($project->erpnext_project_id)) {
            $syncedFields = [
                'project_name',
                'start_date_gregorian',
                'end_date_gregorian',
                'status',
                'priority',
                'form_number',
                'creator_entity_id',
                'internal_entity_id',
            ];
            $needsSync = false;

            foreach ($syncedFields as $field) {
                if ($project->wasChanged($field)) {
                    $needsSync = true;
                    break;
                }
            }

            if ($needsSync) {
                Log::info("ProjectObserver: Linked Project ID {$project->id} updated. Dispatching ERPNext sync update job.");
                try {
                    SendDataToErpNextJob::dispatch(Project::class, $project->id, 'sync');
                } catch (\Throwable $e) {
                    Log::error("ProjectObserver Exception during ERPNext update dispatch: {$e->getMessage()}");
                }
            }
        }
    }
}

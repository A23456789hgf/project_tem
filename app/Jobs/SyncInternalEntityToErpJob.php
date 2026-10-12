<?php

namespace App\Jobs;

use App\Models\InternalEntity;
use App\Services\InternalEntityErpSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncInternalEntityToErpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [15, 60, 180];

    /**
     * Create a new job instance.
     */
    public function __construct(public int $entityId)
    {
        // Ensure the job is dispatched only after the active database transaction commits.
        $this->afterCommit = true;

        $queueName = config('erp_entity_sync.queue', 'default');
        if ($queueName) {
            $this->onQueue($queueName);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(InternalEntityErpSyncService $syncService): void
    {
        $lockKey = "erpnext_sync_entity_{$this->entityId}";

        Cache::lock($lockKey, 30)->block(15, function () use ($syncService) {
            $entity = InternalEntity::withoutGlobalScopes()->find($this->entityId);

            if (! $entity) {
                Log::warning("SyncInternalEntityToErpJob: Internal entity #{$this->entityId} not found in database. Skipping.");

                return;
            }

            Log::info("SyncInternalEntityToErpJob: Processing sync for Entity #{$entity->id} ({$entity->name}) [Type: {$entity->entity_type}]");

            $success = $syncService->sync($entity);

            if (! $success) {
                Log::warning("SyncInternalEntityToErpJob: Synchronization returned false for entity #{$entity->id}. Status recorded in internal_entity_erp_syncs table.");
            }
        });
    }

    /**
     * Handle job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $errorMsg = $exception ? $exception->getMessage() : 'Job failed after max attempts.';
        Log::error("SyncInternalEntityToErpJob: Permanently failed for entity #{$this->entityId}: {$errorMsg}");
    }
}


<?php

namespace App\Observers;

use App\Jobs\SyncInternalEntityToErpJob;
use App\Models\InternalEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InternalEntityObserver
{
    /**
     * Ensure observer events run after the database transaction has committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the InternalEntity "created" event.
     */
    public function created(InternalEntity $entity): void
    {
        $this->dispatchSync($entity, 'created');
    }

    /**
     * Handle the InternalEntity "updated" event.
     */
    public function updated(InternalEntity $entity): void
    {
        // Only trigger if synchronization-relevant attributes were modified
        $syncRelevantFields = [
            'name',
            'entity_type',
            'entity_code',
            'parent_id',
            'is_active',
            'is_ministry_root',
        ];

        if ($entity->wasChanged($syncRelevantFields)) {
            $this->dispatchSync($entity, 'updated');
        }
    }

    /**
     * Dispatch ERPNext synchronization job.
     */
    protected function dispatchSync(InternalEntity $entity, string $trigger): void
    {
        if (! config('erp_entity_sync.enabled', true)) {
            Log::info("InternalEntityObserver: ERP entity sync disabled by config. Skipping [Trigger: {$trigger}] for entity #{$entity->id}.");

            return;
        }

        // Mark local sync state as pending
        DB::table('internal_entity_erp_syncs')->updateOrInsert(
            ['entity_id' => $entity->id],
            [
                'status' => 'pending',
                'updated_at' => now(),
            ]
        );

        Log::info("InternalEntityObserver: Dispatching SyncInternalEntityToErpJob for entity #{$entity->id} ({$entity->name}) [Trigger: {$trigger}]");

        SyncInternalEntityToErpJob::dispatch($entity->id);
    }
}


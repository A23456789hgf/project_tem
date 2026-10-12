<?php

namespace App\Console\Commands;

use App\Services\InternalEntityErpSyncService;
use Illuminate\Console\Command;

class RetryInternalEntityErpSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erpnext:retry-entity-sync {--limit=50 : Maximum number of entities to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry pending and failed internal entity synchronizations to ERPNext';

    /**
     * Execute the console command.
     */
    public function handle(InternalEntityErpSyncService $syncService): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Checking for pending or failed internal entity synchronizations (limit: {$limit})...");

        $processed = $syncService->retryPending($limit);

        $this->info("Processed {$processed} entity synchronization(s).");

        return self::SUCCESS;
    }
}


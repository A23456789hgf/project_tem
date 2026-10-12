<?php

namespace App\Console\Commands;

use App\Services\ErpNextProjectSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ErpNextSyncProjectsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erpnext:sync-projects {--full : Perform a full sync of all projects}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync projects from ERPNext to Laravel';

    /**
     * Execute the console command.
     */
    public function handle(ErpNextProjectSyncService $syncService)
    {
        $this->info('Starting ERPNext Projects Sync...');
        Log::info('ERPNext Project Sync Started');

        $isFullSync = $this->option('full');

        try {
            $stats = $syncService->sync($isFullSync);

            $this->info("Sync completed. Imported: {$stats['imported']}, Skipped: {$stats['skipped']}, Missing Year: {$stats['missing_year']}, Failed: {$stats['failed']}");

            Log::info('ERPNext Project Sync Completed', [
                'Fetched' => $stats['imported'] + $stats['skipped'] + $stats['failed'],
                'Created' => $stats['imported'],
                'Skipped Existing' => $stats['skipped'],
                'Missing Year' => $stats['missing_year'],
                'Failed' => $stats['failed'],
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Sync failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}

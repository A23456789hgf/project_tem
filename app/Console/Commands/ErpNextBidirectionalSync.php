<?php

namespace App\Console\Commands;

use App\Jobs\ProcessErpNextWebhookJob;
use App\Jobs\SendDataToErpNextJob;
use App\Models\Project;
use App\Models\SystemSetting;
use App\Services\FrappeAPIService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ErpNextBidirectionalSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erpnext:sync-bidirectional';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatic incremental pull scheduler for bidirectional Laravel ↔ ERPNext project synchronization';

    /**
     * Execute the console command.
     */
    public function handle(FrappeAPIService $frappeService)
    {
        $this->info('Starting backup bidirectional sync...');

        /*
        |--------------------------------------------------------------------------
        | 1. Recover Outbound (Laravel -> ERPNext)
        |--------------------------------------------------------------------------
        */

        $failedProjects = Project::where('sync_status', 'failed')
            ->orWhere(function ($query) {
                // Eligible but missing erpnext_project_id
                $query->whereNull('erpnext_project_id')
                    ->whereHas('projectApprovals');
            })
            ->get();

        foreach ($failedProjects as $project) {
            if ($project->sync_status !== 'queued') {
                $project->updateQuietly([
                    'sync_status' => 'queued',
                ]);

                SendDataToErpNextJob::dispatch(
                    Project::class,
                    $project->id,
                    'sync'
                );

                $this->info(
                    "Dispatched recovery outbound sync for Project ID: {$project->id}"
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Incremental Inbound Pull (ERPNext -> Laravel)
        |--------------------------------------------------------------------------
        */

        // Use a far past date for initial local sync
        $lastSync = SystemSetting::get(
            'last_successful_erpnext_sync_at',
            '2020-01-01 00:00:00'
        );

        // Safety overlap of 1 minute to avoid missing records at boundary
        $since = Carbon::parse($lastSync)
            ->subMinute()
            ->format('Y-m-d H:i:s');

        $syncStartTime = now()->format('Y-m-d H:i:s');

        $limitStart = 0;
        $limitPageLength = 100;
        $hasMore = true;

        $fetched = 0;
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        try {
            while ($hasMore) {
                $response = $frappeService->httpGet('Project', [
                    'filters' => json_encode([
                        ['modified', '>', $since],
                    ]),
                    'limit_start' => $limitStart,
                    'limit_page_length' => $limitPageLength,
                ]);

                if (
                    $response['success']
                    && isset($response['data'])
                    && count($response['data']) > 0
                ) {
                    $frappeProjects = $response['data'];

                    $fetched += count($frappeProjects);

                    foreach ($frappeProjects as $frappeProject) {
                        try {
                            $docResponse = $frappeService->httpGet(
                                'Project/'.urlencode($frappeProject['name'])
                            );

                            if (
                                $docResponse['success']
                                && isset($docResponse['data'])
                            ) {
                                $data = $docResponse['data'];

                                $existing = Project::where(
                                    'erpnext_project_id',
                                    $data['name']
                                )->first();

                                ProcessErpNextWebhookJob::dispatchSync(
                                    'Project',
                                    'on_update',
                                    $data
                                );

                                $after = Project::where(
                                    'erpnext_project_id',
                                    $data['name']
                                )->first();

                                if (! $existing && $after) {
                                    $inserted++;

                                    $this->info(
                                        "ERP_PULL_INSERTED: {$data['name']} (Local ID: {$after->id})"
                                    );

                                    Log::info(
                                        "ERP_PULL_INSERTED: {$data['name']} (Local ID: {$after->id})"
                                    );
                                } elseif (
                                    $existing
                                    && $after
                                    && $existing->updated_at != $after->updated_at
                                ) {
                                    $updated++;

                                    $this->info(
                                        "ERP_PULL_UPDATED: {$data['name']} (Local ID: {$after->id})"
                                    );

                                    Log::info(
                                        "ERP_PULL_UPDATED: {$data['name']} (Local ID: {$after->id})"
                                    );
                                } else {
                                    $skipped++;

                                    $this->warn(
                                        "ERP_PULL_SKIPPED: {$data['name']} - Mapping aborted silently or no changes."
                                    );

                                    Log::warning(
                                        "ERP_PULL_SKIPPED: {$data['name']} - Mapping aborted silently or no changes."
                                    );
                                }
                            }
                        } catch (\Exception $e) {
                            $failed++;

                            $this->error(
                                "ERP_PULL_FAILED: {$frappeProject['name']} - "
                                .$e->getMessage()
                            );

                            Log::error(
                                "ERP_PULL_FAILED: {$frappeProject['name']} - "
                                .$e->getMessage()
                            );
                        }
                    }

                    $limitStart += $limitPageLength;
                } else {
                    $hasMore = false;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Save successful synchronization timestamp
            |--------------------------------------------------------------------------
            |
            | Only advance the timestamp if the entire batch processed without
            | catastrophic failure.
            |
            */

            SystemSetting::set(
                'last_successful_erpnext_sync_at',
                $syncStartTime,
                'integration'
            );

        } catch (\Exception $e) {
            Log::error(
                'Inbound pull sync failed catastrophically: '
                .$e->getMessage()
            );

            $this->error(
                'Inbound pull sync failed catastrophically: '
                .$e->getMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Synchronization Summary
        |--------------------------------------------------------------------------
        */

        $this->info('Backup bidirectional sync completed.');

        $this->info("Fetched: $fetched");
        $this->info("Inserted: $inserted");
        $this->info("Updated: $updated");
        $this->info("Skipped: $skipped");
        $this->info("Failed: $failed");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Services;

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Priority;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProjectImplementingEntity;
use ArPHP\I18N\Arabic;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ErpNextProjectSyncService
{
    protected FrappeAPIService $frappeService;

    public function __construct(FrappeAPIService $frappeService)
    {
        $this->frappeService = $frappeService;
    }

    /**
     * Sync projects from ERPNext.
     * Returns an array with stats.
     */
    public function sync(bool $isFullSync = false): array
    {
        $stats = [
            'imported' => 0,
            'skipped' => 0,
            'missing_year' => 0,
            'failed' => 0,
        ];

        // 1. Retry Failed Projects
        $this->retryFailedProjects($stats);

        // 2. Incremental Sync for New Projects
        $this->syncNewProjects($isFullSync, $stats);

        return $stats;
    }

    /**
     * Phase 1: Retry pending failed projects
     */
    private function retryFailedProjects(array &$stats): void
    {
        $failures = DB::table('integration_sync_failures')
            ->where('integration', 'erpnext_projects')
            ->whereNull('resolved_at')
            // Add a simple backoff or just limit to retrying max 100 per cycle to avoid blocking
            ->orderBy('last_attempt_at', 'asc')
            ->limit(100)
            ->get();

        foreach ($failures as $failure) {
            // Check if it somehow got imported already
            if (Project::where('erpnext_project_id', $failure->source_reference)->exists()) {
                $this->markFailureResolved($failure->id);

                continue;
            }

            // Fetch latest data for this specific project
            $response = $this->frappeService->httpGet('Project', [
                'filters' => json_encode([['name', '=', $failure->source_reference]]),
                'limit_page_length' => 1,
            ]);

            DB::table('integration_sync_failures')
                ->where('id', $failure->id)
                ->update([
                    'retry_count' => $failure->retry_count + 1,
                    'last_attempt_at' => now(),
                    'updated_at' => now(),
                ]);

            if (! $response['success'] || empty($response['data'])) {
                continue; // Still failing or deleted in ERPNext
            }

            $erpProject = $response['data'][0];

            try {
                $this->processSingleProject($erpProject, $stats);
                // If we reach here, it succeeded!
                $this->markFailureResolved($failure->id);
            } catch (Exception $e) {
                // Keep it in failures, update message
                DB::table('integration_sync_failures')
                    ->where('id', $failure->id)
                    ->update([
                        'failure_message' => $e->getMessage(),
                    ]);
            }
        }
    }

    /**
     * Phase 2: Fetch and sync new projects
     */
    private function syncNewProjects(bool $isFullSync, array &$stats): void
    {
        $syncState = DB::table('integration_sync_states')->where('integration', 'erpnext_projects')->first();
        $lastSync = $syncState ? $syncState->last_creation_at : null;

        $filters = [];
        if (! $isFullSync && $lastSync) {
            $filters[] = ['creation', '>=', $lastSync];
        }

        $pageLength = 500;
        $start = 0;
        $maxCreationDate = $lastSync;

        while (true) {
            $params = [
                'fields' => '["*"]',
                'limit_page_length' => $pageLength,
                'limit_start' => $start,
                'order_by' => 'creation asc',
            ];

            if (! empty($filters)) {
                $params['filters'] = json_encode($filters);
            }

            $response = $this->frappeService->httpGet('Project', $params);

            if (! $response['success']) {
                Log::error('ERPNext Project Sync Failed: API Error', ['response' => $response]);
                throw new Exception('ERPNext API connection failure.');
            }

            $projects = $response['data'];

            if (empty($projects)) {
                break;
            }

            foreach ($projects as $erpProject) {
                $erpName = $erpProject['name'] ?? null;
                if (! $erpName) {
                    continue;
                }

                // Always track max creation date even if skipped or failed
                if (isset($erpProject['creation']) && $erpProject['creation'] > $maxCreationDate) {
                    $maxCreationDate = $erpProject['creation'];
                }

                // Handle existing successfully imported projects (update creating entity if company changed/unassigned)
                $existing = Project::where('erpnext_project_id', $erpName)->first();
                if ($existing) {
                    $companyName = $erpProject['company'] ?? null;
                    if ($companyName) {
                        $entity = $this->frappeService->resolveEntityFromErpNextCompany($companyName);
                        if ($entity) {
                            $needsUpdate = $existing->creator_entity_id !== $entity->id
                                || $existing->internal_entity_id !== $entity->id
                                || $existing->created_by_entity !== $entity->name;

                            if ($needsUpdate) {
                                $existing->isErpnextInbound = true;
                                $existing->creator_entity_id = $entity->id;
                                $existing->internal_entity_id = $entity->id;
                                $existing->created_by_entity = $entity->name;
                                $existing->save();

                                Log::info('Updated existing project creating entity from ERPNext company', [
                                    'project_id' => $existing->id,
                                    'erpnext_id' => $erpName,
                                    'company' => $companyName,
                                    'entity_id' => $entity->id,
                                ]);
                            }
                        }
                    }

                    $stats['skipped']++;

                    continue;
                }

                try {
                    $this->processSingleProject($erpProject, $stats);
                } catch (Exception $e) {
                    // It failed, save to failures table for future retries
                    $this->recordFailure($erpProject, $e->getMessage());
                }
            }

            $start += $pageLength;
        }

        // Update the pointer: ALWAYS advance forward, never rewind.
        if ($maxCreationDate) {
            DB::table('integration_sync_states')->updateOrInsert(
                ['integration' => 'erpnext_projects'],
                [
                    'last_creation_at' => $maxCreationDate,
                    'last_successful_sync_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * Core processing logic for a single project
     */
    private function processSingleProject(array $erpProject, array &$stats): void
    {
        $erpName = $erpProject['name'];

        DB::transaction(function () use ($erpProject, $erpName, &$stats) {
            $expectedStart = $erpProject['expected_start_date'] ?? null;
            $expectedEnd = $erpProject['expected_end_date'] ?? null;
            $actualStart = $erpProject['actual_start_date'] ?? null;
            $actualEnd = $erpProject['actual_end_date'] ?? null;
            $projectName = $erpProject['project_name'] ?? 'Unknown Project';

            $hijriYear = $this->determineHijriYear($expectedStart, $expectedEnd, $actualStart, $actualEnd);

            if ($hijriYear) {
                $yearInt = (int) $hijriYear;
                if ($yearInt < 1400 || $yearInt > 1500) {
                    // Invalid year (e.g. 851 from 1448-01-01 AD)
                    $hijriYear = null;
                }
            }

            if (! $hijriYear) {
                $stats['missing_year']++;
                Log::warning('PROJECT_WITHOUT_FUNDING_YEAR', [
                    'erpnext_id' => $erpName,
                    'project_name' => $projectName,
                ]);
            }

            $startDateHijri = $expectedStart ? $this->convertToHijri($expectedStart) : null;
            $endDateHijri = $expectedEnd ? $this->convertToHijri($expectedEnd) : null;

            $formNumber = ProjectNumberGenerator::getNextProjectNumber($hijriYear);
            $priorityId = $this->mapPriority($erpProject['priority'] ?? null);

            $project = new Project;
            $project->project_type = 'old';
            $project->project_name = $projectName;
            $project->erpnext_project_id = $erpName;
            $project->form_number = $formNumber;
            $project->status = $erpProject['status'] ?? 'Open';
            $project->priority_id = $priorityId;
            $project->start_date_gregorian = $expectedStart;
            $project->start_date_hijri = $startDateHijri;
            $project->end_date_gregorian = $expectedEnd;
            $project->end_date_hijri = $endDateHijri;
            $project->is_data_completed = 0;

            $companyName = $erpProject['company'] ?? null;
            if ($companyName) {
                $entity = $this->frappeService->resolveEntityFromErpNextCompany($companyName);
                if ($entity) {
                    $project->creator_entity_id = $entity->id;
                    $project->internal_entity_id = $entity->id;
                    $project->created_by_entity = $entity->name;
                }
            }

            $project->isErpnextInbound = true;
            $project->save();

            // We must always create ProjectCost because hijri_year is stored there, even if cost is 0 and year is null
            // Otherwise, we have nowhere to store the fact that hijri_year is null,
            // and later when cost is edited, the observer triggers.
            $estimatedCosting = $erpProject['estimated_costing'] ?? 0;
            $projectCost = new ProjectCost;
            $projectCost->project_id = $project->id;
            $projectCost->total_cost = $estimatedCosting;
            $projectCost->hijri_year = $hijriYear;
            $projectCost->save();

            $companyName = $erpProject['company'] ?? null;
            if ($companyName) {
                $this->mapCompany($project, $companyName);
            }

            Log::info('ERPNext Project Created', [
                'erpnext_id' => $erpName,
                'laravel_id' => $project->id,
                'form_number' => $formNumber,
            ]);
        });

        $stats['imported']++;
    }

    private function recordFailure(array $erpProject, string $errorMessage): void
    {
        $erpName = $erpProject['name'] ?? null;
        if (! $erpName) {
            return;
        }

        DB::table('integration_sync_failures')->updateOrInsert(
            [
                'integration' => 'erpnext_projects',
                'source_reference' => $erpName,
            ],
            [
                'failure_type' => str_contains($errorMessage, 'MISSING_PROJECT_YEAR') ? 'MISSING_PROJECT_YEAR' : 'GENERAL_ERROR',
                'failure_message' => $errorMessage,
                // Only save minimal payload to avoid huge db bloat if not necessary
                'payload' => json_encode(['project_name' => $erpProject['project_name'] ?? '']),
                'updated_at' => now(),
                // created_at is only set on insert, so it won't be overridden if we just use updateOrInsert like this
            ]
        );
    }

    private function markFailureResolved(int $failureId): void
    {
        DB::table('integration_sync_failures')
            ->where('id', $failureId)
            ->update([
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function mapPriority(?string $erpPriority): ?int
    {
        if (! $erpPriority) {
            return null;
        }

        $priorityMap = [
            'Low' => 'منخفض',
            'Medium' => 'متوسط',
            'High' => 'عالي',
        ];

        $arabicPriority = $priorityMap[$erpPriority] ?? null;
        if ($arabicPriority) {
            $priority = Priority::where('priority', 'like', "%{$arabicPriority}%")->first();

            return $priority ? $priority->id : null;
        }

        return null;
    }

    private function mapCompany(Project $project, string $companyName): void
    {
        $authority = Authority::where('agency_name', $companyName)->first();
        if ($authority) {
            ProjectImplementingEntity::create([
                'project_id' => $project->id,
                'authority_id' => (string) $authority->id,
                'authority_type' => 'external',
                'internal_entity_id' => null,
            ]);

            return;
        }

        $internalEntity = InternalEntity::where('name', $companyName)->first();
        if ($internalEntity) {
            ProjectImplementingEntity::create([
                'project_id' => $project->id,
                'internal_entity_id' => $internalEntity->id,
                'authority_type' => 'internal',
                'authority_id' => '',
            ]);

            return;
        }

        Log::warning('COMPANY_MAPPING_NOT_FOUND', [
            'erpnext_id' => $project->erpnext_project_id,
            'project_name' => $project->project_name,
            'company' => $companyName,
        ]);
    }

    private function convertToHijri(string $gregorianDate): ?string
    {
        try {
            $arabic = new Arabic;

            return $arabic->date('Y-m-d', strtotime($gregorianDate));
        } catch (Exception $e) {
            return null;
        }
    }

    private function determineHijriYear(?string $expectedStart, ?string $expectedEnd, ?string $actualStart, ?string $actualEnd): ?string
    {
        $dateToUse = $expectedStart ?: ($expectedEnd ?: ($actualStart ?: $actualEnd));

        if ($dateToUse) {
            try {
                $arabic = new Arabic;

                return $arabic->date('Y', strtotime($dateToUse));
            } catch (Exception $e) {
                // fall through
            }
        }

        return null;
    }
}

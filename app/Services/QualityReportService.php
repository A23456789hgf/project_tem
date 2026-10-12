<?php

namespace App\Services;

use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\Project;
use App\Models\ProjectLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QualityReportService
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Get unified report data (KPIs, Charts, Projects, etc.)
     * Works for a single project, multiple selected projects, or all accessible projects.
     */
    public function getReportData(Request $request): array
    {
        // 1. Build Query
        $query = $this->buildQuery($request);

        // 2. Fetch projects with eager loading
        // ✅ تمت إضافة createdBy و creatorEntity و internalEntity
        //    لمنع LazyLoadingViolationException على Accessors
        $projects = $query->with([
            // ⬇️⬇️⬇️ الإصلاح الأساسي ⬇️⬇️⬇️
            'createdBy',
            'creatorEntity',
            'internalEntity',

            // العلاقات الموجودة مسبقًا
            'preliminaryActivities.procedures.executions',
            'preliminaryActivities.procedures.costs',
            'executiveActivities.actions.executions',
            'executiveActivities.actions.costs',
            'qualityRecords',
        ])->get();

        // 3. Process calculations for each project
        $projectsData = [];
        $kpis = [
            'total_projects' => $projects->count(),
            'completed_projects' => 0,
            'in_progress_projects' => 0,
            'total_score' => 0,
            'max_score' => 0,
            'min_score' => 100,
        ];

        $locationStats = [];
        $entityStats = [];
        $statusStats = [];
        $trendStats = [];

        // Bulk fetch locations to prevent N+1
        $projectIds = $projects->pluck('id')->toArray();
        $locations = ProjectLocation::with(['governorate', 'directorate'])
            ->whereIn('project_id', $projectIds)
            ->get()
            ->keyBy('project_id');

        foreach ($projects as $project) {
            $preliminaryQuality = $this->calculatePreliminaryQuality($project);
            $executiveQuality = $this->calculateExecutiveQuality($project);
            $stats = $this->calculateStatistics($preliminaryQuality, $executiveQuality);

            $score = $stats['overall_quality_score'] ?? 0;
            $kpis['total_score'] += $score;
            if ($score > $kpis['max_score']) {
                $kpis['max_score'] = $score;
            }
            if ($score < $kpis['min_score'] && $score > 0) {
                $kpis['min_score'] = $score;
            }

            if ($project->status === 'completed') {
                $kpis['completed_projects']++;
            } else {
                $kpis['in_progress_projects']++;
            }

            // Location
            $loc = $locations->get($project->id);
            $locationName = 'غير محدد';
            if ($loc) {
                $gov = $loc->governorate ? $loc->governorate->name : '';
                $dir = $loc->directorate ? $loc->directorate->name : '';
                $locationName = trim("$gov - $dir", ' -') ?: 'غير محدد';
            }
            if (! isset($locationStats[$locationName])) {
                $locationStats[$locationName] = ['count' => 0, 'score_sum' => 0];
            }
            $locationStats[$locationName]['count']++;
            $locationStats[$locationName]['score_sum'] += $score;

            // Entity
            $entityName = $project->creator_entity_name;
            if (! isset($entityStats[$entityName])) {
                $entityStats[$entityName] = ['count' => 0, 'score_sum' => 0];
            }
            $entityStats[$entityName]['count']++;
            $entityStats[$entityName]['score_sum'] += $score;

            // Status
            $statusName = $project->current_stage_name ?? 'Unknown';
            if (! isset($statusStats[$statusName])) {
                $statusStats[$statusName] = 0;
            }
            $statusStats[$statusName]++;

            // Trend (by month/year of project creation or start)
            $date = $project->start_date_gregorian
                ? Carbon::parse($project->start_date_gregorian)->format('Y-m')
                : 'Unknown';
            if (! isset($trendStats[$date])) {
                $trendStats[$date] = ['count' => 0, 'score_sum' => 0];
            }
            $trendStats[$date]['count']++;
            $trendStats[$date]['score_sum'] += $score;

            $projectsData[] = [
                'project' => $project,
                'preliminary' => $preliminaryQuality,
                'executive' => $executiveQuality,
                'statistics' => $stats,
                'location_name' => $locationName,
                'entity_name' => $entityName,
                'status_name' => $statusName,
            ];
        }

        if ($kpis['total_projects'] > 0) {
            $kpis['average_score'] = round($kpis['total_score'] / $kpis['total_projects'], 2);
        } else {
            $kpis['average_score'] = 0;
            $kpis['min_score'] = 0;
        }

        // Prepare Charts Dataset
        $charts = [
            'quality_by_project' => [
                'labels' => collect($projectsData)->pluck('project.project_name')->toArray(),
                'data' => collect($projectsData)->pluck('statistics.overall_quality_score')->toArray(),
            ],
            'quality_by_location' => [
                'labels' => array_keys($locationStats),
                'data' => array_map(fn ($item) => round($item['score_sum'] / $item['count'], 2), array_values($locationStats)),
                'counts' => array_map(fn ($item) => $item['count'], array_values($locationStats)),
            ],
            'quality_by_entity' => [
                'labels' => array_keys($entityStats),
                'data' => array_map(fn ($item) => round($item['score_sum'] / $item['count'], 2), array_values($entityStats)),
                'counts' => array_map(fn ($item) => $item['count'], array_values($entityStats)),
            ],
            'status_distribution' => [
                'labels' => array_keys($statusStats),
                'data' => array_values($statusStats),
            ],
            'quality_trend' => [
                'labels' => array_keys($trendStats),
                'data' => array_map(fn ($item) => round($item['score_sum'] / $item['count'], 2), array_values($trendStats)),
            ],
            'quality_distribution' => $this->calculateQualityDistribution($projectsData),
        ];

        return [
            'kpis' => $kpis,
            'projects_data' => $projectsData,
            'charts' => $charts,
        ];
    }

    private function calculateQualityDistribution(array $projectsData): array
    {
        $distribution = [
            'ممتاز (90-100)' => 0,
            'جيد جداً (80-89)' => 0,
            'جيد (70-79)' => 0,
            'مقبول (60-69)' => 0,
            'ضعيف (<60)' => 0,
        ];

        foreach ($projectsData as $data) {
            $score = $data['statistics']['overall_quality_score'] ?? 0;
            if ($score >= 90) {
                $distribution['ممتاز (90-100)']++;
            } elseif ($score >= 80) {
                $distribution['جيد جداً (80-89)']++;
            } elseif ($score >= 70) {
                $distribution['جيد (70-79)']++;
            } elseif ($score >= 60) {
                $distribution['مقبول (60-69)']++;
            } else {
                $distribution['ضعيف (<60)']++;
            }
        }

        return [
            'labels' => array_keys($distribution),
            'data' => array_values($distribution),
        ];
    }

    /**
     * Build the query applying all temporal, spatial, and entity filters.
     * Respects permissions via ProjectService.
     */
    private function buildQuery(Request $request)
    {
        // Use the existing scope mechanism to fetch accessible projects
        $query = $this->projectService->getProjects($request, null, null, [], true, true);

        // 1. Report Scope (Single, Selected, All)
        $reportScope = $request->input('report_scope', 'all');
        if ($reportScope === 'single' && $request->filled('single_project_id')) {
            $query->where('id', $request->input('single_project_id'));
        } elseif ($reportScope === 'selected' && $request->filled('project_ids')) {
            $ids = is_array($request->input('project_ids'))
                ? $request->input('project_ids')
                : explode(',', $request->input('project_ids'));
            $query->whereIn('id', $ids);
        }

        // 2. Temporal Filters
        if ($request->filled('from_date')) {
            $query->whereDate('start_date_gregorian', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('start_date_gregorian', '<=', $request->input('to_date'));
        }
        if ($request->filled('hijri_year')) {
            $query->where('start_date_hijri', 'like', $request->input('hijri_year').'-%');
        }

        // 3. Entity Filters
        if ($request->filled('entity_id')) {
            $entityId = $request->input('entity_id');
            $query->where(function ($q) use ($entityId) {
                $q->where('creator_entity_id', $entityId)
                    ->orWhere('internal_entity_id', $entityId);
            });
        }

        // 4. Spatial Filters (تم تصحيحها لاستخدام applySpatialFilters)
        $query = $this->applySpatialFilters($query, $request);

        return $query;
    }

    /**
     * Build query with spatial filters corrected
     */
    private function applySpatialFilters($query, Request $request)
    {
        $hasSpatial = $request->filled('governorate_id') || $request->filled('directorate_id');
        if ($hasSpatial) {
            $locQuery = ProjectLocation::query();
            if ($request->filled('governorate_id')) {
                $locQuery->where('governorate_id', $request->input('governorate_id'));
            }
            if ($request->filled('directorate_id')) {
                $locQuery->where('directorate_id', $request->input('directorate_id'));
            }
            $projectIds = $locQuery->pluck('project_id')->toArray();
            $query->whereIn('id', $projectIds);
        }

        return $query;
    }

    // ----- ORIGINAL LOGIC FROM CONTROLLER (To maintain formulas) ----- //

    private function calculatePreliminaryQuality(Project $project): array
    {
        $qualityData = [];
        foreach ($project->preliminaryActivities as $activity) {
            foreach ($activity->procedures as $procedure) {
                $execution = $procedure->executions->first();
                if (! $execution) {
                    continue;
                }
                $qualityData[] = [
                    'type' => 'preliminary',
                    'activity' => $activity,
                    'procedure' => $procedure,
                    'execution' => $execution,
                    'time_quality' => $this->assessTimeQuality(
                        $procedure->start_date,
                        $execution->actual_start_date_gregorian,
                        $procedure->end_date,
                        $execution->actual_finish_date_gregorian
                    ),
                    'financial_quality' => $this->assessFinancialQuality(
                        $procedure->costs->sum('total'),
                        $execution->amount_spent
                    ),
                ];
            }
        }

        return $qualityData;
    }

    private function calculateExecutiveQuality(Project $project): array
    {
        $qualityData = [];
        foreach ($project->executiveActivities as $activity) {
            foreach ($activity->actions as $action) {
                $execution = $action->executions->first();
                if (! $execution) {
                    continue;
                }
                $qualityData[] = [
                    'type' => 'executive',
                    'activity' => $activity,
                    'action' => $action,
                    'execution' => $execution,
                    'time_quality' => $this->assessTimeQuality(
                        $action->start_date,
                        $execution->actual_start_date_gregorian,
                        $action->end_date,
                        $execution->actual_finish_date_gregorian
                    ),
                    'financial_quality' => $this->assessFinancialQuality(
                        $action->costs->sum('total'),
                        $execution->amount_spent
                    ),
                ];
            }
        }

        return $qualityData;
    }

    private function assessTimeQuality($plannedStart, $actualStart, $plannedEnd, $actualEnd): array
    {
        if (! $plannedStart || ! $actualStart || ! $plannedEnd || ! $actualEnd) {
            return [
                'status' => null,
                'quality' => null,
                'variance_days' => null,
                'details' => 'بيانات غير مكتملة',
            ];
        }

        $plannedStartDate = Carbon::parse($plannedStart);
        $actualStartDate = Carbon::parse($actualStart);
        $plannedEndDate = Carbon::parse($plannedEnd);
        $actualEndDate = Carbon::parse($actualEnd);

        $startVariance = $actualStartDate->diffInDays($plannedStartDate, false);
        $endVariance = $actualEndDate->diffInDays($plannedEndDate, false);

        $varianceDays = $endVariance;

        if ($varianceDays < 0) {
            $status = 'ahead';
            $quality = 'positive';
            $details = 'متقدم عن الجدول الزمني';
        } elseif ($varianceDays == 0) {
            $status = 'on_time';
            $quality = 'positive';
            $details = 'في الوقت المحدد';
        } else {
            $status = 'late';
            $quality = 'negative';
            $details = 'متأخر عن الجدول الزمني';
        }

        return [
            'status' => $status,
            'quality' => $quality,
            'variance_days' => $varianceDays,
            'planned_start' => $plannedStart,
            'actual_start' => $actualStart,
            'planned_end' => $plannedEnd,
            'actual_end' => $actualEnd,
            'details' => $details,
        ];
    }

    private function assessFinancialQuality($plannedAmount, $actualAmount): array
    {
        if ($plannedAmount === null || $actualAmount === null) {
            return [
                'status' => null,
                'quality' => null,
                'variance_amount' => null,
                'details' => 'بيانات غير مكتملة',
            ];
        }

        $varianceAmount = $actualAmount - $plannedAmount;

        if ($actualAmount > $plannedAmount) {
            $status = 'over_plan';
            $quality = 'negative';
            $details = 'تجاوز الميزانية المخططة';
        } elseif ($actualAmount == $plannedAmount) {
            $status = 'matching_plan';
            $quality = 'positive';
            $details = 'مطابق للميزانية المخططة';
        } else {
            $status = 'below_plan';
            $quality = 'positive';
            $details = 'أقل من الميزانية المخططة';
        }

        return [
            'status' => $status,
            'quality' => $quality,
            'variance_amount' => $varianceAmount,
            'planned_amount' => $plannedAmount,
            'actual_amount' => $actualAmount,
            'details' => $details,
        ];
    }

    private function calculateStatistics(array $preliminaryQuality, array $executiveQuality): array
    {
        $allQuality = array_merge($preliminaryQuality, $executiveQuality);

        $totalItems = count($allQuality);
        $timePositive = 0;
        $timeNegative = 0;
        $financialPositive = 0;
        $financialNegative = 0;

        foreach ($allQuality as $item) {
            if ($item['time_quality']['quality'] === 'positive') {
                $timePositive++;
            } elseif ($item['time_quality']['quality'] === 'negative') {
                $timeNegative++;
            }

            if ($item['financial_quality']['quality'] === 'positive') {
                $financialPositive++;
            } elseif ($item['financial_quality']['quality'] === 'negative') {
                $financialNegative++;
            }
        }

        return [
            'total_items' => $totalItems,
            'time_positive' => $timePositive,
            'time_negative' => $timeNegative,
            'time_positive_percentage' => $totalItems > 0 ? round(($timePositive / $totalItems) * 100, 2) : 0,
            'financial_positive' => $financialPositive,
            'financial_negative' => $financialNegative,
            'financial_positive_percentage' => $totalItems > 0 ? round(($financialPositive / $totalItems) * 100, 2) : 0,
            'overall_quality_score' => $totalItems > 0 ? round((($timePositive + $financialPositive) / ($totalItems * 2)) * 100, 2) : 0,
        ];
    }
}

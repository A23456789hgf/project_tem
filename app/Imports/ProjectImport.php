<?php

namespace App\Imports;

use App\Exceptions\MissingDropdownValuesException;
use App\Services\ProjectImportService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class ProjectImport implements Import, WithHeadingRow, WithMultipleSheets
{
    use Importable;

    private $trackingService;

    private $importLog;

    public function __construct($trackingService = null, $importLog = null)
    {
        HeadingRowFormatter::default('none');
        $this->trackingService = $trackingService;
        $this->importLog = $importLog;
    }

    // -----------------------------------------------------------------------
    // Report counters
    // -----------------------------------------------------------------------

    private array $report = [
        'total_rows' => 0,
        'successful_inserts' => 0,
        'successful_updates' => 0,
        'failed_rows' => 0,
        'skipped_rows' => 0,   // empty rows
        'skipped_dropdown_rows' => 0,   // skipped due to missing dropdown values
    ];

    // -----------------------------------------------------------------------
    // Row storage (populated by sub-sheet importers)
    // -----------------------------------------------------------------------

    private array $projectInfoRows = [];

    private array $originalProjectInfoRows = [];

    private array $projectInfoHeadings = [];

    private array $detailRows = [];

    private array $locationRows = [];

    private array $mainObjectiveRows = [];

    private array $specialObjectiveRows = [];

    private array $resultsOutputRows = [];

    private array $preliminaryActivityRows = [];

    private array $executiveActivityRows = [];

    private array $financingRows = [];

    private array $entityRows = [];

    // Pre-indexed
    private array $indexedDetails = [];

    private array $indexedLocations = [];

    private array $indexedMainObjectives = [];

    private array $indexedSpecialObjectives = [];

    private array $indexedResultsOutputs = [];

    private array $indexedPreliminaryActivities = [];

    private array $indexedExecutiveActivities = [];

    private array $indexedFinancings = [];

    private array $indexedEntities = [];

    // -----------------------------------------------------------------------
    // Failure / skip tracking
    // -----------------------------------------------------------------------

    private array $failures = [];

    private array $skippedRowsDetails = [];   // empty rows

    private array $updatedRowsDetails = [];   // updated (duplicate name)

    private array $failedProjectNames = [];

    /**
     * Projects skipped because of missing dropdown values.
     * Each element:
     * [
     *   'row_number'       => 3,
     *   'project_name'     => '...',
     *   'missing_values'   => [ [field_key, field_label, table_name, value], ... ],
     *   'original_row'     => [...],     // raw Excel row as-is
     * ]
     */
    private array $dropdownSkipped = [];

    // -----------------------------------------------------------------------
    // Sheets definition
    // -----------------------------------------------------------------------

    public function sheets(): array
    {
        return [
            0 => new ProjectImportSheets\ProjectInfoImport($this),
            1 => new ProjectImportSheets\GenericSheetImport($this, 'detail'),
            2 => new ProjectImportSheets\GenericSheetImport($this, 'locations'),
            3 => new ProjectImportSheets\GenericSheetImport($this, 'main_objectives'),
            4 => new ProjectImportSheets\GenericSheetImport($this, 'special_objectives'),
            5 => new ProjectImportSheets\GenericSheetImport($this, 'results_outputs'),
            6 => new ProjectImportSheets\GenericSheetImport($this, 'preliminary_activities'),
            7 => new ProjectImportSheets\GenericSheetImport($this, 'executive_activities'),
            8 => new ProjectImportSheets\GenericSheetImport($this, 'financings'),
            9 => new ProjectImportSheets\GenericSheetImport($this, 'entities'),
        ];
    }

    // -----------------------------------------------------------------------
    // Row injection (called by sub-sheet importers)
    // -----------------------------------------------------------------------

    public function addRows(string $type, array $rows, array $originalRows = []): void
    {
        $cleanedRows = array_map(function ($row) {
            if (isset($row['project_name']) && is_string($row['project_name'])) {
                $row['project_name'] = trim($row['project_name']);
            }

            return $row;
        }, $rows);

        switch ($type) {
            case 'project_info':
                $this->projectInfoRows = $cleanedRows;
                $this->originalProjectInfoRows = $originalRows;
                if (! empty($originalRows)) {
                    $this->projectInfoHeadings = array_keys($originalRows[0]);
                }
                break;
            case 'detail':                  $this->detailRows = $cleanedRows;
                break;
            case 'locations':               $this->locationRows = $cleanedRows;
                break;
            case 'main_objectives':         $this->mainObjectiveRows = $cleanedRows;
                break;
            case 'special_objectives':      $this->specialObjectiveRows = $cleanedRows;
                break;
            case 'results_outputs':         $this->resultsOutputRows = $cleanedRows;
                break;
            case 'preliminary_activities':  $this->preliminaryActivityRows = $cleanedRows;
                break;
            case 'executive_activities':    $this->executiveActivityRows = $cleanedRows;
                break;
            case 'financings':              $this->financingRows = $cleanedRows;
                break;
            case 'entities':                $this->entityRows = $cleanedRows;
                break;
        }
    }

    // -----------------------------------------------------------------------
    // Processing
    // -----------------------------------------------------------------------

    public function process(): array
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::disableQueryLog();

        $service = new ProjectImportService;
        $this->report['total_rows'] = count($this->projectInfoRows);

        // Pre-index sub-sheets for O(1) lookup
        foreach ($this->detailRows as $row) {
            $name = trim((string) ($row['project_name'] ?? ''));
            if ($name !== '') {
                $this->indexedDetails[$name] = $row;
            }
        }
        $this->indexedLocations = $this->groupByProjectName($this->locationRows);
        $this->indexedMainObjectives = $this->groupByProjectName($this->mainObjectiveRows);
        $this->indexedSpecialObjectives = $this->groupByProjectName($this->specialObjectiveRows);
        $this->indexedResultsOutputs = $this->groupByProjectName($this->resultsOutputRows);
        $this->indexedPreliminaryActivities = $this->groupByProjectName($this->preliminaryActivityRows);
        $this->indexedExecutiveActivities = $this->groupByProjectName($this->executiveActivityRows);
        $this->indexedFinancings = $this->groupByProjectName($this->financingRows);
        $this->indexedEntities = $this->groupByProjectName($this->entityRows);

        foreach ($this->projectInfoRows as $index => $projectInfo) {
            $projectName = trim((string) ($projectInfo['project_name'] ?? ''));

            // Skip completely empty rows
            if ($projectName === '') {
                $this->report['skipped_rows']++;
                $this->skippedRowsDetails[] = [
                    'row' => 'صف '.($index + 2),
                    'reason' => 'اسم المشروع فارغ أو لم يتم التعرف عليه بالملف',
                ];

                continue;
            }

            try {
                $data = $this->buildProjectData($projectName, $projectInfo);
                $this->validateImportRow($data);
                $project = $service->import($data);   // throws MissingDropdownValuesException if any missing

                if ($project->wasRecentlyCreated) {
                    $this->report['successful_inserts']++;
                    if ($this->trackingService && $this->importLog) {
                        $this->trackingService->recordSuccess($this->importLog, $project, 'created');
                    }
                } else {
                    $this->report['successful_updates']++;
                    if ($this->trackingService && $this->importLog) {
                        $this->trackingService->recordSuccess($this->importLog, $project, 'updated');
                    }
                    $this->updatedRowsDetails[] = [
                        'row' => $projectName,
                        'row_number' => 'صف '.($index + 2),
                        'reason' => 'تم تحديث مشروع موجود بنفس الاسم مسبقاً (اسم المشروع مكرر في الإكسل أو موجود في قاعدة البيانات)',
                    ];
                }

            } catch (MissingDropdownValuesException $e) {
                // Atomic skip — collect ALL missing values, no partial data
                $this->report['skipped_dropdown_rows']++;
                $this->dropdownSkipped[] = [
                    'row_number' => $index + 2,
                    'project_name' => $projectName,
                    'missing_values' => $e->getMissingValues(),
                    'full_data' => $data,
                    'original_row' => $this->originalProjectInfoRows[$index] ?? [],
                ];

            } catch (\Exception $e) {
                $this->report['failed_rows']++;
                if ($projectName !== '') {
                    $this->failedProjectNames[] = $projectName;
                }
                $this->failures[] = [
                    'row' => $projectName !== '' ? $projectName : ('صف '.($index + 2)),
                    'attribute' => 'general',
                    'errors' => [$e->getMessage()],
                    'values' => [],
                ];
            }

            if ($index > 0 && $index % 500 === 0 && function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return $this->report;
    }

    // -----------------------------------------------------------------------
    // Getters
    // -----------------------------------------------------------------------

    public function getReport(): array
    {
        return $this->report;
    }

    public function failures(): array
    {
        return $this->failures;
    }

    public function getSkippedRowsDetails(): array
    {
        return $this->skippedRowsDetails;
    }

    public function getUpdatedRowsDetails(): array
    {
        return $this->updatedRowsDetails;
    }

    public function getDropdownSkipped(): array
    {
        return $this->dropdownSkipped;
    }

    /**
     * Return missing values grouped by (field_key + original_value) — deduplicated.
     * Counts affected projects per unique missing value.
     */
    public function getGroupedMissingValues(): array
    {
        $grouped = [];

        foreach ($this->dropdownSkipped as $skipped) {
            foreach ($skipped['missing_values'] as $mv) {
                // Unique key = field_key + canonical lowercase value + parent_id
                // (constraint #7 & #15: same text under different field_key or parent = different entry)
                $parentId = $mv['parent_id'] ?? null;
                $key = $mv['field_key'].':::'.mb_strtolower(trim($mv['value'])).':::'.($parentId ?? '');

                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'field_key' => $mv['field_key'],
                        'field_label' => $mv['field_label'],
                        'table_name' => $mv['table_name'],
                        'original_value' => $mv['value'],
                        'parent_fk' => $mv['parent_fk'] ?? null,
                        'parent_id' => $parentId,
                        'affected_count' => 0,
                        'affected_projects' => [],
                        'status' => 'pending',
                    ];
                }
                $grouped[$key]['affected_count']++;
                $grouped[$key]['affected_projects'][] = $skipped['project_name'];
            }
        }

        return $grouped;
    }

    /**
     * Original Excel rows for projects skipped due to dropdown issues (for Excel report).
     */
    public function getDropdownSkippedOriginalRows(): array
    {
        $rows = [];
        foreach ($this->dropdownSkipped as $s) {
            $row = $s['original_row'];
            $row['القيم الناقصة'] = implode(' | ', array_map(
                fn ($mv) => $mv['field_label'].': '.$mv['value'],
                $s['missing_values']
            ));
            $rows[] = $row;
        }

        return $rows;
    }

    public function getOriginalHeadings(): array
    {
        return $this->projectInfoHeadings;
    }

    /**
     * For failed-projects report (existing functionality).
     */
    public function getFailedProjectData(): array
    {
        $failedRows = [];
        $failureReasons = [];
        foreach ($this->failures as $f) {
            $name = $f['row'] ?? '';
            if ($name !== '') {
                $failureReasons[$name] = implode(' | ', $f['errors'] ?? []);
            }
        }
        foreach ($this->originalProjectInfoRows as $index => $row) {
            $name = trim($row['اسم المشروع'] ?? $row['project_name'] ?? '');
            if ($name === '') {
                $rowDesc = 'صف '.($index + 2);
                if (isset($failureReasons[$rowDesc])) {
                    $row['سبب الفشل'] = $failureReasons[$rowDesc];
                    $failedRows[] = $row;
                }
            } else {
                if (in_array($name, $this->failedProjectNames)) {
                    $row['سبب الفشل'] = $failureReasons[$name] ?? 'خطأ غير معروف';
                    $failedRows[] = $row;
                }
            }
        }
        $headings = $this->projectInfoHeadings;
        if (! empty($headings) && ! in_array('سبب الفشل', $headings)) {
            $headings[] = 'سبب الفشل';
        }

        return ['headings' => $headings, 'rows' => $failedRows];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function groupByProjectName(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['project_name'] ?? ''));
            if ($name !== '') {
                $grouped[$name][] = $row;
            }
        }

        return $grouped;
    }

    private function buildProjectData(string $projectName, array $projectInfo): array
    {
        $data = $projectInfo;

        $detail = $this->indexedDetails[$projectName] ?? null;
        if ($detail) {
            unset($detail['project_name']);
            $data['detail'] = $detail;
        }

        $data['locations'] = collect($this->indexedLocations[$projectName] ?? [])->map(function ($loc) {
            unset($loc['project_name']);

            return $loc;
        })->toArray();
        $data['main_objectives'] = collect($this->indexedMainObjectives[$projectName] ?? [])->map(function ($obj) {
            unset($obj['project_name']);

            return $obj;
        })->toArray();
        $data['special_objectives'] = collect($this->indexedSpecialObjectives[$projectName] ?? [])->map(function ($obj) {
            unset($obj['project_name']);

            return $obj;
        })->toArray();

        $resultsOutputs = collect($this->indexedResultsOutputs[$projectName] ?? []);
        $data['objective_results'] = $resultsOutputs->groupBy('result_name')->map(function ($group, $resultName) {
            $first = $group->first();

            return [
                'result_name' => $resultName,
                'target_value' => $first['target_value'] ?? null,
                'indicator_type' => $first['indicator_type'] ?? null,
                'indicator_unit' => $first['indicator_unit'] ?? null,
                'outputs' => $group->map(fn ($r) => ['output' => $r['output'] ?? null])->filter(fn ($o) => ! empty($o['output']))->values()->toArray(),
            ];
        })->values()->toArray();

        $prelimActivities = collect($this->indexedPreliminaryActivities[$projectName] ?? []);
        $data['preliminary_activities'] = $prelimActivities->groupBy('activity_name')->map(function ($group, $actName) {
            $first = $group->first();

            return [
                'name' => $actName,
                'weight' => $first['activity_weight'] ?? null,
                'procedures' => $group->groupBy('procedure_name')->map(function ($procGroup, $procName) {
                    return [
                        'procedure_name' => $procName,
                        'costs' => $procGroup->map(fn ($r) => ['financial_item_id' => $r['financial_item_id'] ?? null, 'amount' => $r['cost_amount'] ?? null])->filter(fn ($c) => ! empty($c['financial_item_id']))->values()->toArray(),
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        $execActivities = collect($this->indexedExecutiveActivities[$projectName] ?? []);
        $data['executive_activities'] = $execActivities->groupBy('activity_name')->map(function ($group, $actName) {
            return [
                'name' => $actName,
                'weight' => $group->first()['activity_weight'] ?? null,
                'actions' => $group->groupBy('action')->map(function ($actionGroup, $actionName) {
                    $first = $actionGroup->first();

                    return [
                        'action' => $actionName,
                        'weight' => $first['action_weight'] ?? null,
                        'start_date' => $first['start_date'] ?? null,
                        'end_date' => $first['end_date'] ?? null,
                        'verification_means' => $first['verification_means'] ?? null,
                        'assigned_entities' => $actionGroup->pluck('assigned_entity_id')->filter()->unique()->map(fn ($id) => ['entity_id' => $id])->values()->toArray(),
                        'costs' => $actionGroup->map(fn ($r) => ['financial_item_id' => $r['financial_item_id'] ?? null, 'amount' => $r['cost_amount'] ?? null])->filter(fn ($c) => ! empty($c['financial_item_id']))->values()->toArray(),
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        $data['financings'] = collect($this->indexedFinancings[$projectName] ?? [])->map(function ($fin) {
            unset($fin['project_name']);

            return $fin;
        })->toArray();

        $entities = collect($this->indexedEntities[$projectName] ?? []);
        $isSupervising = fn ($e) => in_array($e['entity_role'] ?? '', ['supervising',   'مشرفة']);
        $isImplementing = fn ($e) => in_array($e['entity_role'] ?? '', ['implementing',  'منفذة']);
        $isParticipating = fn ($e) => in_array($e['entity_role'] ?? '', ['participating', 'مشاركة']);
        $isBeneficiary = fn ($e) => in_array($e['entity_role'] ?? '', ['beneficiary',   'مستفيدة']);
        $mapEntity = fn ($e) => ['authority_type' => $e['authority_type'] ?? null, 'authority_id' => $e['authority_id'] ?? null, 'parent_id' => $e['parent_id'] ?? null];

        $data['supervising_authorities'] = $entities->filter($isSupervising)->map($mapEntity)->values()->toArray();
        $data['implementing_entities'] = $entities->filter($isImplementing)->map($mapEntity)->values()->toArray();
        $data['participating_entities'] = $entities->filter($isParticipating)->map($mapEntity)->values()->toArray();
        $data['beneficiary_entities'] = $entities->filter($isBeneficiary)->map($mapEntity)->values()->toArray();

        return $data;
    }

    private function validateImportRow(array $data): void
    {
        $projectType = $data['project_type'] ?? 'new';
        $status = $data['status'] ?? 'draft';
        $isFinal = ! in_array(strtolower(trim($status)), ['draft', 'مسودة', '']);

        if ($projectType === 'old') {
            if (empty($data['project_name'])) {
                throw new \Exception('اسم المشروع مطلوب للمشاريع القديمة.');
            }
            if ($isFinal && (! isset($data['total_cost']) || $data['total_cost'] === '' || floatval($data['total_cost']) < 0)) {
                throw new \Exception('إجمالي تكلفة المشروع مطلوب ويجب أن يكون قيمة موجبة للاعتماد النهائي.');
            }
        } else {
            if (empty($data['project_name'])) {
                throw new \Exception('اسم المشروع مطلوب للمشاريع الجديدة.');
            }
            if ($isFinal) {
                $missingFields = [];
                if (empty($data['program_id'])) {
                    $missingFields[] = 'البرنامج';
                }
                if (empty($data['domain_id'])) {
                    $missingFields[] = 'المجال';
                }
                if (empty($data['subdomain_id'])) {
                    $missingFields[] = 'المجال الفرعي';
                }
                if (empty($data['intervention_id'])) {
                    $missingFields[] = 'نوع التدخل';
                }
                if (! empty($missingFields)) {
                    throw new \Exception('البيانات الأساسية غير مكتملة للاعتماد النهائي: يرجى تحديد '.implode('، ', $missingFields));
                }
                if (empty($data['locations'])) {
                    throw new \Exception('يجب إضافة موقع واحد على الأقل للمشروع الجديد للاعتماد النهائي.');
                }
                $specialObjectives = $data['special_objectives'] ?? [];
                if (empty($specialObjectives)) {
                    throw new \Exception('يجب إضافة هدف خاص واحد على الأقل للمشروع الجديد للاعتماد النهائي.');
                }
                $totalWeight = array_sum(array_map(fn ($o) => floatval($o['objective_weight'] ?? 0), $specialObjectives));
                if (abs($totalWeight - 100) > 0.01) {
                    throw new \Exception("يجب أن يكون مجموع أوزان الأهداف الخاصة 100% (المجموع الحالي: {$totalWeight}%).");
                }
                $results = $data['objective_results'] ?? [];
                if (empty($results)) {
                    throw new \Exception('يجب إضافة نتيجة ومخرج واحد على الأقل للمشروع الجديد للاعتماد النهائي.');
                }
                if (empty($data['supervising_authorities'])) {
                    throw new \Exception('يجب إضافة جهة مشرفة واحدة على الأقل للمشروع الجديد للاعتماد النهائي.');
                }
                if (empty($data['implementing_entities'])) {
                    throw new \Exception('يجب إضافة جهة منفذة واحدة على الأقل للمشروع الجديد للاعتماد النهائي.');
                }
                $financings = $data['financings'] ?? [];
                if (empty($financings)) {
                    throw new \Exception('يجب إضافة مصدر تمويل واحد على الأقل للمشروع الجديد للاعتماد النهائي.');
                }
                $totalAmount = array_sum(array_map(fn ($f) => floatval($f['financing_amount'] ?? 0), $financings));
                $totalPercentage = array_sum(array_map(fn ($f) => floatval($f['financing_percentage'] ?? 0), $financings));
                if ($totalPercentage > 0 && abs($totalPercentage - 100) > 0.01) {
                    throw new \Exception("يجب أن يكون مجموع نسب التمويل 100% (المجموع الحالي: {$totalPercentage}%).");
                }
                $totalCost = isset($data['total_cost']) && $data['total_cost'] !== '' ? floatval($data['total_cost']) : 0;
                if ($totalCost > 0 && abs($totalAmount - $totalCost) > 0.1) {
                    throw new \Exception("إجمالي مبالغ التمويل ({$totalAmount}) يجب أن يتطابق مع إجمالي تكلفة المشروع ({$totalCost}).");
                }
                $prelimActivities = $data['preliminary_activities'] ?? [];
                if (empty($prelimActivities)) {
                    throw new \Exception('يجب إضافة نشاط تمهيدي واحد على الأقل للمشروع الجديد للاعتماد النهائي.');
                }
                $totalActivityWeight = 0;
                foreach ($prelimActivities as $act) {
                    $totalActivityWeight += floatval($act['weight'] ?? 0);
                    if (count($act['procedures'] ?? []) < 2) {
                        throw new \Exception("النشاط التمهيدي \"{$act['name']}\" يجب أن يحتوي على إجراءين على الأقل.");
                    }
                }
                if (abs($totalActivityWeight - 100) > 0.01) {
                    throw new \Exception("يجب أن يكون مجموع أوزان الأنشطة التمهيدية 100% (المجموع الحالي: {$totalActivityWeight}%).");
                }
                $execActivities = $data['executive_activities'] ?? [];
                if (empty($execActivities)) {
                    throw new \Exception('يجب إضافة نشاط تنفيذي واحد على الأقل للمشروع الجديد للاعتماد النهائي.');
                }
                $totalExecWeight = 0;
                foreach ($execActivities as $act) {
                    $totalExecWeight += floatval($act['weight'] ?? 0);
                    if (count($act['actions'] ?? []) < 2) {
                        throw new \Exception("النشاط التنفيذي \"{$act['name']}\" يجب أن يحتوي على إجراءين (Actions) على الأقل.");
                    }
                    $totalActionWeight = array_sum(array_map(fn ($a) => floatval($a['weight'] ?? 0), $act['actions']));
                    if (abs($totalActionWeight - 100) > 0.01) {
                        throw new \Exception("النشاط التنفيذي \"{$act['name']}\": مجموع أوزان الإجراءات يجب أن يكون 100% (المجموع الحالي: {$totalActionWeight}%).");
                    }
                }
                if (abs($totalExecWeight - 100) > 0.01) {
                    throw new \Exception("يجب أن يكون مجموع أوزان الأنشطة التنفيذية 100% (المجموع الحالي: {$totalExecWeight}%).");
                }
            }
        }
    }
}

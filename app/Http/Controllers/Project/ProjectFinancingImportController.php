<?php

namespace App\Http\Controllers\Project;

use App\Exports\ProjectFinancingTemplateExport;
use App\Http\Controllers\Controller;
use App\Jobs\ImportFinancingJob;
use App\Models\Authority;
use App\Models\FinancingForm;
use App\Models\FinancingType;
use App\Models\FundingSource;
use App\Models\Project;
use App\Models\SubFinancingForm;
use App\Services\ProjectFinancingImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectFinancingImportController extends Controller
{
    /**
     * الحد الأدنى لدرجة التشابه لقبول المطابقة الضبابية تلقائيًا.
     */
    protected const FUZZY_AUTO_THRESHOLD = 0.95;

    protected $importService;

    public function __construct(ProjectFinancingImportService $importService)
    {
        $this->middleware(['auth', 'can:projects.import']);
        $this->importService = $importService;
    }

    public function showImportForm()
    {
        return view('project_financing.import.form');
    }

    public function downloadTemplate()
    {
        $fileName = 'project_financing_template_'.date('Y-m-d').'.xlsx';

        return (new ProjectFinancingTemplateExport)->download($fileName);
    }

    public function previewImport(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('projects.financings.import.form')
                ->with('warning', 'يرجى اختيار ورفع ملف الاستيراد أولاً لعرض المعاينة.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('temp_financing_imports');
        $fullPath = Storage::path($path);

        $rowsArray = $this->readSpreadsheetRows($fullPath);

        if (empty($rowsArray)) {
            return back()->with('error', 'الملف المرفق فارغ.');
        }

        $headersKeys = array_shift($rowsArray);

        $rows = collect($rowsArray)->map(function ($row) use ($headersKeys) {
            $item = [];
            foreach ($headersKeys as $col => $header) {
                $trimmedHeader = is_string($header) ? trim($header) : $header;
                if (! empty($trimmedHeader)) {
                    $item[$trimmedHeader] = $row[$col] ?? null;
                }
            }

            return collect($item);
        })->filter(function ($row) {
            return $row->filter(function ($v) {
                return $v !== null && $v !== '';
            })->isNotEmpty();
        })->values();

        if ($rows->isEmpty()) {
            return back()->with('error', 'الملف المرفق فارغ.');
        }

        $headers = array_keys($rows->first()->toArray());

        $mappings = [];
        foreach ($headers as $header) {
            $mappings[$header] = $this->importService->suggestMapping($header);
        }

        // ✅ تم حذف financing_percentage من الحقول القابلة للاستيراد
        $systemFields = [
            'project_name' => 'اسم المشروع',
            'funding_source_id' => 'مصدر التمويل',
            'authority_id' => 'جهة التمويل',
            'financing_type_id' => 'نوع التمويل',
            'financing_form_id' => 'شكل التمويل',
            'sub_financing_form_id' => 'الشكل الفرعي',
            'financing_amount' => 'مبلغ التمويل',
        ];

        $this->importService->clearCandidateCache();

        $projectHeader = null;
        foreach ($mappings as $header => $sysField) {
            if ($sysField === 'project_name') {
                $projectHeader = $header;
                break;
            }
        }
        if (! $projectHeader && count($headers) > 0) {
            $projectHeader = $headers[0];
        }

        $groupedProjects = [];
        $matchStats = [
            'total' => 0,
            'matched_exact' => 0,
            'matched_normalized' => 0,
            'matched_fuzzy' => 0,
            'needs_review' => 0,
            'ambiguous' => 0,
            'not_found' => 0,
        ];

        $groupedByProjName = $rows->groupBy(function ($row) use ($projectHeader) {
            $name = trim((string) ($row[$projectHeader] ?? 'بدون اسم مشروع'));

            return preg_replace('/\s+/u', ' ', $name) ?: 'بدون اسم مشروع';
        });

        foreach ($groupedByProjName as $excelProjectName => $projectRows) {
            $matchResult = $this->importService->resolveProject(['project_name' => $excelProjectName]);

            $status = $matchResult->status;
            if (isset($matchStats[$status])) {
                $matchStats[$status]++;
            }
            $matchStats['total']++;

            $groupedProjects[] = [
                'excel_project_name' => $excelProjectName,
                'matched_project_id' => $matchResult->project?->id,
                'matched_project_name' => $matchResult->project?->project_name,
                'status' => $status,
                'method' => $matchResult->method,
                'score' => $matchResult->score,
                'reason' => $matchResult->reason,
                'candidates' => $matchResult->candidates,
                'rows' => $projectRows,
                'rows_count' => $projectRows->count(),
            ];
        }

        $authorities = Authority::orderBy('agency_name')->get(['id', 'agency_name']);
        $fundingSources = FundingSource::orderBy('name')->get(['id', 'name']);
        $allProjects = Project::visibleToUser('projects')->select('id', 'project_name', 'form_number')->orderBy('project_name')->get();

        return view('project_financing.import.preview', compact(
            'path',
            'headers',
            'mappings',
            'systemFields',
            'rows',
            'groupedProjects',
            'matchStats',
            'projectHeader',
            'authorities',
            'fundingSources',
            'allProjects'
        ));
    }

    public function processImport(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('projects.financings.import.form');
        }

        $request->validate([
            'file_path' => 'required|string',
            'mapping' => 'required|array',
            'mode' => 'required|in:replace,fill_missing,merge,update_existing',
            'fuzzy_handling' => 'nullable|in:auto,review,skip',
        ]);

        $rawPath = $request->file_path;
        $fullPath = file_exists($rawPath) ? $rawPath : Storage::path($rawPath);
        if (! file_exists($fullPath)) {
            return redirect()->route('projects.financings.import.form')->with('error', 'الملف غير موجود، الرجاء إعادة الرفع.');
        }

        $rowsArray = $this->readSpreadsheetRows($fullPath);

        // Determine if the import should be processed asynchronously to avoid request timeouts.
        $rowCount = count($rowsArray);
        $largeThreshold = 2000; // Adjust as appropriate for your environment.
        if ($rowCount > $largeThreshold) {
            // Dispatch the import as a queued job.
            ImportFinancingJob::dispatch(
                $fullPath,
                $request->input('mapping'),
                $request->input('mode'),
                $request->input('fuzzy_handling', 'auto'),
                $request->except('file_path') // Pass additional data such as fallback IDs, selected projects, etc.
            );

            // Redirect back with a status message indicating background processing.
            return redirect()->route('projects.financings.import.form')
                ->with('status', "تم إرسال الاستيراد للمعالجة الخلفية (عدد الصفوف: $rowCount).");
        }

        if (empty($rowsArray)) {
            return back()->with('error', 'الملف المرفق فارغ.');
        }

        $headersKeys = array_shift($rowsArray);

        $rows = collect($rowsArray)->map(function ($row) use ($headersKeys) {
            $item = [];
            foreach ($headersKeys as $col => $header) {
                $trimmedHeader = is_string($header) ? trim($header) : $header;
                if (! empty($trimmedHeader)) {
                    $item[$trimmedHeader] = $row[$col] ?? null;
                }
            }

            return collect($item);
        })->filter(function ($row) {
            return $row->filter(function ($v) {
                return $v !== null && $v !== '';
            })->isNotEmpty();
        })->values();

        $mapping = $request->mapping;
        $mode = $request->mode;

        $fuzzyHandling = $request->input('fuzzy_handling', 'auto');
        $this->importService->clearCandidateCache();

        $results = [
            'processed' => 0,
            'updated' => 0,
            'failed' => 0,
            'total_amount' => 0,
            'records_imported' => 0,
            'total_rows' => $rows->count(),
            'fuzzy_handling' => $fuzzyHandling,
            'errors' => [],
            'warnings' => [],
            'stats' => [
                'total_projects' => 0,
                'matched_exact' => 0,
                'matched_normalized' => 0,
                'matched_fuzzy' => 0,
                'matched_fuzzy_imported' => 0,
                'matched_fuzzy_review' => 0,
                'matched_fuzzy_skipped' => 0,
                'needs_review' => 0,
                'ambiguous' => 0,
                'not_found' => 0,
                'unknown_entities' => 0,
                'recalculated_projects' => 0, // ✅ إحصائية جديدة
            ],
            'project_details' => [],
        ];

        $this->importService->clearCandidateCache();

        // تجميع الصفوف حسب اسم المشروع مع تطبيع المسافات
        $projectGroups = [];
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            $parsed = $this->importService->parseRow($row->toArray(), $mapping);

            $projData = $parsed['project'];

            if (! empty($projData['project_name'])) {
                $projData['project_name'] = preg_replace('/\s+/u', ' ', trim($projData['project_name']));
            }

            $key = '';
            if (! empty($projData['project_name'])) {
                $key = 'name_'.$projData['project_name'];
            } else {
                $key = 'unknown_'.$rowNum;
            }

            if (! isset($projectGroups[$key])) {
                $projectGroups[$key] = [
                    'project_data' => $projData,
                    'rows' => [],
                ];
            }
            $parsed['row_num'] = $rowNum;
            $projectGroups[$key]['rows'][] = $parsed;
        }

        // فلترة النطاق
        $importScope = $request->input('import_scope', 'all');
        $selectedProjects = (array) $request->input('selected_projects', []);
        $singleProject = $request->input('single_project_name');
        $manualProjectMap = (array) $request->input('manual_project_map', []);

        if ($importScope === 'single' && ! empty($singleProject)) {
            $projectGroups = array_filter($projectGroups, function ($grp) use ($singleProject) {
                return ($grp['project_data']['project_name'] ?? '') === $singleProject;
            });
        } elseif ($importScope === 'matched_only') {
            $projectGroups = array_filter($projectGroups, function ($grp) use ($manualProjectMap, $fuzzyHandling) {
                $pName = $grp['project_data']['project_name'] ?? '';
                $hasManual = ! empty($manualProjectMap[$pName]) || ! empty($manualProjectMap[md5($pName)]);
                if ($hasManual) {
                    return true;
                }
                $res = $this->importService->resolveProject($grp['project_data']);
                if (in_array($res->status, ['matched_exact', 'matched_normalized'], true)) {
                    return true;
                }
                if ($res->status === 'matched_fuzzy' && $fuzzyHandling !== 'skip') {
                    return true;
                }

                return false;
            });
        } elseif ($importScope === 'selected' && ! empty($selectedProjects)) {
            $projectGroups = array_filter($projectGroups, function ($grp) use ($selectedProjects, $manualProjectMap) {
                $pName = $grp['project_data']['project_name'] ?? '';
                $hasManual = ! empty($manualProjectMap[$pName]) || ! empty($manualProjectMap[md5($pName)]);

                return in_array($pName, $selectedProjects, true) || in_array('name_'.$pName, $selectedProjects, true) || $hasManual;
            });
        }

        if (empty($projectGroups)) {
            return back()->with('error', 'لم يتم تحديد أي مشاريع للاستيراد.');
        }

        $fallbackAuthorityId = $request->input('fallback_authority_id');
        $fallbackFundingSourceId = $request->input('fallback_funding_source_id');

        $results['stats']['total_projects'] = count($projectGroups);

        foreach ($projectGroups as $key => $group) {
            $projData = $group['project_data'];
            $groupRows = $group['rows'];
            $rowCount = count($groupRows);
            $pName = $projData['project_name'];

            // 1. فحص التعيين اليدوي للمشروع أولاً
            $manualId = $manualProjectMap[$pName] ?? $manualProjectMap[md5($pName)] ?? null;
            $project = null;
            $matchResult = null;

            if (! empty($manualId) && is_numeric($manualId)) {
                $manualProject = Project::find((int) $manualId);
                if ($manualProject) {
                    $project = $manualProject;
                }
            }

            if (! $project) {
                $matchResult = $this->importService->resolveProject($projData);
            }

            $detail = [
                'excel_project_name' => $projData['project_name'] ?: 'بدون اسم مشروع',
                'matched_project_id' => $project ? $project->id : $matchResult?->project?->id,
                'matched_project_name' => $project ? $project->project_name : $matchResult?->project?->project_name,
                'status' => $project ? 'matched_manual' : ($matchResult?->status ?? 'not_found'),
                'method' => $project ? 'manual' : ($matchResult?->method ?? 'none'),
                'score' => $project ? 1.0 : ($matchResult?->score ?? 0),
                'reason' => $project ? 'تم الربط والاختيار يدويًا من المستخدم.' : ($matchResult?->reason ?? ''),
                'candidates' => $matchResult?->candidates ?? [],
                'rows_count' => $rowCount,
                'financings_count' => 0,
                'total_amount' => 0,
                'import_status' => 'skipped',
                'errors' => [],
                'warnings' => [],
            ];

            if ($project) {
                $results['stats']['matched_exact']++;
            } elseif ($matchResult) {
                switch ($matchResult->status) {
                    case 'matched_exact':
                        $results['stats']['matched_exact']++;
                        $project = $matchResult->project;
                        break;

                    case 'matched_normalized':
                        $results['stats']['matched_normalized']++;
                        $project = $matchResult->project;
                        break;

                    case 'matched_fuzzy':
                        $results['stats']['matched_fuzzy']++;

                        if ($fuzzyHandling === 'auto') {
                            $project = $matchResult->project;
                            $results['stats']['matched_fuzzy_imported']++;

                            $score = round(($matchResult->score ?? 0) * 100, 1);
                            $results['warnings'][] = "المشروع '{$projData['project_name']}': تمت مطابقته ضبابيًا مع '{$matchResult->project?->project_name}' (نسبة {$score}%) وتم استيراده تلقائيًا.";
                            $detail['warnings'][] = "مطابقة ضبابية تلقائية (نسبة {$score}%).";

                            break;
                        }

                        if ($fuzzyHandling === 'skip') {
                            $results['stats']['matched_fuzzy_skipped']++;
                            $results['failed'] += $rowCount;
                            $detail['import_status'] = 'fuzzy_skipped';
                            $detail['errors'][] = 'تم تجاهل المشروع (مطابقة ضبابية - وضع "تجاهل").';
                            $results['project_details'][] = $detail;

                            continue 2;
                        }

                        $results['stats']['matched_fuzzy_review']++;
                        $results['stats']['needs_review']++;
                        $results['failed'] += $rowCount;

                        $score = round(($matchResult->score ?? 0) * 100, 1);
                        $candidateDetails = [];
                        foreach ($matchResult->candidates as $cand) {
                            $pct = round(($cand['score'] ?? 0) * 100, 1);
                            $candidateDetails[] = "{$cand['project_name']} ({$pct}%)";
                        }
                        $candidateInfo = ! empty($candidateDetails) ? ' - أقرب المطابقات: '.implode('، ', $candidateDetails) : '';

                        $results['errors'][] = "المشروع '{$projData['project_name']}': مطابقة ضبابية مع '{$matchResult->project?->project_name}' (نسبة {$score}%) تحتاج تأكيدًا يدويًا.{$candidateInfo}";
                        $detail['errors'][] = "مطابقة ضبابية ({$score}%) - تحتاج تأكيدًا.";
                        $results['project_details'][] = $detail;

                        continue 2;

                    case 'needs_review':
                        $results['stats']['needs_review']++;
                        $results['failed'] += $rowCount;
                        $candidateDetails = [];
                        foreach ($matchResult->candidates as $cand) {
                            $pct = round(($cand['score'] ?? 0) * 100, 1);
                            $candidateDetails[] = "{$cand['project_name']} ({$pct}%)";
                        }
                        $candidateInfo = ! empty($candidateDetails) ? ' - أقرب المطابقات: '.implode('، ', $candidateDetails) : '';
                        $results['errors'][] = "المشروع '{$projData['project_name']}': يحتاج مراجعة (نسبة التطابق غير كافية أو متقاربة مع مشروع آخر){$candidateInfo}.";
                        $detail['errors'][] = 'يحتاج مراجعة: نسبة التطابق غير كافية أو متقاربة مع مشروع آخر.';
                        $results['project_details'][] = $detail;

                        continue 2;

                    case 'ambiguous':
                        $results['stats']['ambiguous']++;
                        $results['failed'] += $rowCount;
                        $results['errors'][] = "المشروع '{$projData['project_name']}': يوجد أكثر من مشروع بنفس الاسم، لا يمكن تحديد المشروع بشكل آمن.";
                        $detail['errors'][] = 'مطابقة غامضة: يوجد أكثر من مشروع بنفس الاسم.';
                        $results['project_details'][] = $detail;

                        continue 2;

                    case 'not_found':
                    default:
                        $results['stats']['not_found']++;
                        $results['failed'] += $rowCount;
                        $results['errors'][] = "المشروع '{$projData['project_name']}': لم يتم العثور على المشروع.";
                        $detail['errors'][] = 'لم يتم العثور على أي مشروع مطابق.';
                        $results['project_details'][] = $detail;

                        continue 2;
                }
            }

            // ===== جمع صفوف التمويل الصالحة (بدون نسبة مئوية) =====
            $validFinancings = [];
            $projectErrors = [];

            foreach ($groupRows as $parsedRow) {
                $fin = $parsedRow['financing'];
                $rowNum = $parsedRow['row_num'];

                // ---- المبلغ ----
                $rawAmount = $fin['financing_amount'] ?? null;

                if ($rawAmount === null || $rawAmount === '' || trim((string) $rawAmount) === '') {
                    continue;
                }

                $normalizedAmount = $this->normalizeNumericValue($rawAmount);

                if ($normalizedAmount === null) {
                    $projectErrors[] = "السطر $rowNum: مبلغ التمويل غير رقمي: '{$rawAmount}'.";

                    continue;
                }

                $amount = (float) $normalizedAmount;
                if ($amount < 0) {
                    $projectErrors[] = "السطر $rowNum: مبلغ تمويل سالب غير مسموح.";

                    continue;
                }

                // ---- المراجع ----
                $fundingSourceId = $this->importService->lookupReference(FundingSource::class, $fin['funding_source_id'] ?? null);
                $authorityId = $this->importService->lookupReference(Authority::class, $fin['authority_id'] ?? null);
                $financingTypeId = $this->importService->lookupReference(FinancingType::class, $fin['financing_type_id'] ?? null);
                $financingFormId = $this->importService->lookupReference(FinancingForm::class, $fin['financing_form_id'] ?? null);
                $subFinancingFormId = $this->importService->lookupReference(SubFinancingForm::class, $fin['sub_financing_form_id'] ?? null);

                $hasUnknownEntity = false;
                foreach ([$fundingSourceId, $authorityId, $financingTypeId, $financingFormId, $subFinancingFormId] as $refId) {
                    if (is_string($refId) && str_starts_with($refId, 'unknown:')) {
                        $hasUnknownEntity = true;
                        break;
                    }
                }

                if ($hasUnknownEntity) {
                    $results['stats']['unknown_entities']++;
                }

                $resolvedAuthorityId = is_numeric($authorityId) ? (int) $authorityId : (! empty($fallbackAuthorityId) && is_numeric($fallbackAuthorityId) ? (int) $fallbackAuthorityId : null);
                $resolvedFundingSourceId = is_numeric($fundingSourceId) ? (int) $fundingSourceId : (! empty($fallbackFundingSourceId) && is_numeric($fallbackFundingSourceId) ? (int) $fallbackFundingSourceId : null);

                // ✅ لا نرسل financing_percentage إطلاقًا
                $validFinancings[] = [
                    'funding_source_id' => $resolvedFundingSourceId,
                    'authority_id' => $resolvedAuthorityId,
                    'financing_type_id' => is_numeric($financingTypeId) ? (int) $financingTypeId : null,
                    'financing_form_id' => is_numeric($financingFormId) ? (int) $financingFormId : null,
                    'sub_financing_form_id' => is_numeric($subFinancingFormId) ? (int) $subFinancingFormId : null,
                    'financing_amount' => $amount,
                ];
            }

            if (! empty($projectErrors)) {
                $results['failed'] += $rowCount;
                $results['errors'][] = "المشروع '{$projData['project_name']}': ".implode(' - ', $projectErrors);
                $detail['import_status'] = 'validation_failed';
                $detail['errors'] = $projectErrors;
                $results['project_details'][] = $detail;

                continue;
            }

            if (empty($validFinancings)) {
                $results['processed'] += $rowCount;
                $detail['import_status'] = 'empty_financings';
                $results['project_details'][] = $detail;

                continue;
            }

            try {
                // 1) استيراد التمويلات
                $this->importService->importRow($project, $validFinancings, $mode);

                // 2) ✅ إعادة حساب النسب المئوية لجميع تمويلات المشروع
                $recalcCount = $this->importService->recalculatePercentages($project);
                if ($recalcCount > 0) {
                    $results['stats']['recalculated_projects']++;
                }

                $results['updated']++;
                $results['processed'] += $rowCount;

                $projectTotalAmount = 0;
                foreach ($validFinancings as $vf) {
                    $results['records_imported']++;
                    $results['total_amount'] += $vf['financing_amount'];
                    $projectTotalAmount += $vf['financing_amount'];
                }

                $detail['import_status'] = 'imported';
                $detail['financings_count'] = count($validFinancings);
                $detail['total_amount'] = $projectTotalAmount;
                $detail['recalculated_count'] = $recalcCount;
                $results['project_details'][] = $detail;
            } catch (\Exception $e) {
                $results['failed'] += $rowCount;
                $results['errors'][] = "المشروع '{$projData['project_name']}': حدث خطأ أثناء الحفظ - ".$e->getMessage();
                $detail['import_status'] = 'save_error';
                $detail['errors'][] = $e->getMessage();
                $results['project_details'][] = $detail;
            }
        }

        Storage::delete($request->file_path);

        return view('project_financing.import.result', compact('results'));
    }

    /**
     * قراءة صفوف Excel مع الحفاظ على التنسيق (للأصفار على اليمين).
     */
    protected function readSpreadsheetRows(string $fullPath): array
    {
        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();

        $rowsArray = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $formatted = $cell->getFormattedValue();

                if (is_string($formatted)) {
                    $formatted = trim($formatted);
                }

                $rowData[$cell->getColumn()] = $formatted;
            }

            $rowsArray[] = $rowData;
        }

        return $rowsArray;
    }

    /**
     * تحويل قيمة رقمية إلى صيغة موحّدة.
     */
    protected function normalizeNumericValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);

        if ($str === '') {
            return null;
        }

        $str = str_replace([',', ' ', "\xc2\xa0"], '', $str);
        $str = preg_replace('/[^\d\.\-\+eE]/u', '', $str);

        if ($str === '' || ! is_numeric($str)) {
            return null;
        }

        return $str;
    }

    /**
     * تصدير السجلات غير المطابقة أو غير المستوردة إلى ملف Excel.
     */
    public function exportUnmatched(Request $request)
    {
        $rowsToExport = [];

        $rawPath = $request->input('file_path');
        $fullPath = $rawPath ? (file_exists($rawPath) ? $rawPath : Storage::path($rawPath)) : null;

        if ($fullPath && file_exists($fullPath)) {
            $mapping = (array) $request->input('mapping', []);
            $manualProjectMap = (array) $request->input('manual_project_map', []);
            $this->importService->clearCandidateCache();

            $rowsArray = $this->readSpreadsheetRows($fullPath);
            if (! empty($rowsArray)) {
                $headers = array_shift($rowsArray);
                $cleanHeaders = [];
                foreach ($headers as $col => $h) {
                    $trimmed = is_string($h) ? trim($h) : $h;
                    if (! empty($trimmed)) {
                        $cleanHeaders[$col] = $trimmed;
                    }
                }

                foreach ($rowsArray as $rawRow) {
                    $rowMap = [];
                    foreach ($cleanHeaders as $col => $header) {
                        $rowMap[$header] = $rawRow[$col] ?? null;
                    }

                    $parsed = $this->importService->parseRow($rowMap, $mapping);
                    $projData = $parsed['project'];
                    $pName = $projData['project_name'] ?? '';

                    $manualId = $manualProjectMap[$pName] ?? $manualProjectMap[md5($pName)] ?? null;
                    $hasManual = ! empty($manualId) && is_numeric($manualId);

                    if ($hasManual) {
                        continue;
                    }

                    $matchResult = $this->importService->resolveProject($projData);
                    $isSafe = in_array($matchResult->status, ['matched_exact', 'matched_normalized', 'matched_fuzzy'], true);

                    if (! $isSafe) {
                        $statusLabels = [
                            'needs_review' => 'يحتاج مراجعة (تطابق منخفض أو متقارب)',
                            'ambiguous' => 'مطابقة غامضة (تكرار اسم المشروع بالنظام)',
                            'not_found' => 'لم يتم العثور على أي مشروع مطابق',
                        ];

                        $candidateNames = [];
                        if (! empty($matchResult->candidates)) {
                            foreach ($matchResult->candidates as $cand) {
                                $pct = isset($cand['score']) ? round($cand['score'] * 100, 1).'% ' : '';
                                $candidateNames[] = ($cand['project_name'] ?? '').($pct ? " ($pct)" : '');
                            }
                        }

                        $exportRow = [];
                        foreach ($cleanHeaders as $col => $header) {
                            $exportRow[$header] = $rawRow[$col] ?? '';
                        }
                        $exportRow['حالة المطابقة في النظام'] = $statusLabels[$matchResult->status] ?? $matchResult->status;
                        $exportRow['سبب عدم الاستيراد'] = $matchResult->reason ?? 'لم يتم استيفاء شروط المطابقة الآمنة';
                        $exportRow['المشاريع المقترحة بالنظام'] = implode(' | ', $candidateNames);

                        $rowsToExport[] = $exportRow;
                    }
                }
            }
        } elseif ($request->has('export_json')) {
            $jsonData = json_decode($request->input('export_json'), true);
            if (is_array($jsonData)) {
                foreach ($jsonData as $item) {
                    $candidateList = [];
                    if (! empty($item['candidates'])) {
                        foreach ($item['candidates'] as $c) {
                            $candidateList[] = $c['project_name'] ?? '';
                        }
                    }

                    $rowsToExport[] = [
                        'اسم المشروع في الملف' => $item['excel_project_name'] ?? '',
                        'المشروع المقترح بالنظام' => $item['matched_project_name'] ?? 'لا يوجد',
                        'حالة المطابقة' => $item['status'] ?? '',
                        'نسبة الدقة' => isset($item['score']) ? round($item['score'] * 100, 1).'%' : '',
                        'سبب التخطي / الأخطاء' => implode(' | ', (array) ($item['errors'] ?? [$item['reason'] ?? ''])),
                        'المشاريع المرشحة' => implode(' | ', $candidateList),
                    ];
                }
            }
        }

        if (empty($rowsToExport)) {
            return back()->with('info', 'لا توجد سجلات غير مطابقة لتصديرها.');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('السجلات غير المطابقة');

        $exportHeaders = array_keys($rowsToExport[0]);
        $sheet->fromArray([$exportHeaders], null, 'A1');

        $dataRows = array_map('array_values', $rowsToExport);
        $sheet->fromArray($dataRows, null, 'A2');

        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'B91C1C'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'unmatched_project_financings_'.date('Y_m_d_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

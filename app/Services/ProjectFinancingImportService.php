<?php

namespace App\Services;

use App\DTO\ProjectMatchResult;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\Authority;
use App\Models\Project;
use App\Models\ProjectFinancing;
use App\Traits\NormalizeProjectNameTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectFinancingImportService
{
    use NormalizeProjectNameTrait;

    protected $calculationService;

    protected $candidateCache = null;

    public function __construct(ProjectFinancingCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Clear candidate cache (useful during testing or batch resets).
     */
    public function clearCandidateCache(): void
    {
        $this->candidateCache = null;
    }

    /**
     * Map a raw header to a suggested system field.
     *
     * ✅ تم حذف financing_percentage — النسبة تُحسب تلقائيًا وليس من الملف.
     */
    public function suggestMapping(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace(['_', '-'], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header);

        if (preg_match('/project\s*name|اسم\s*المشروع|project/iu', $header)) {
            return 'project_name';
        }
        if (preg_match('/funding\s*source|مصدر\s*التمويل/iu', $header)) {
            return 'funding_source_id';
        }
        if (preg_match('/authority|entity|الجهة\s*الممولة|جهة/iu', $header)) {
            return 'authority_id';
        }
        if (preg_match('/type|نوع\s*التمويل/iu', $header)) {
            return 'financing_type_id';
        }
        if (preg_match('/sub\s*form|الشكل\s*الفرعي/iu', $header)) {
            return 'sub_financing_form_id';
        }
        if (preg_match('/form|شكل\s*التمويل/iu', $header)) {
            return 'financing_form_id';
        }
        if (preg_match('/amount|المبلغ|مبلغ\s*التمويل|قيمة/iu', $header)) {
            return 'financing_amount';
        }

        // ✅ لم يعد هناك ربط لعمود النسبة المئوية
        // أي عمود يحتوي على "نسبة" أو "percentage" سيُتجاهل تلقائيًا
        // لأن النظام يحسبها بنفسه بعد الاستيراد.

        return '';
    }

    /**
     * Calculate Multibyte-Safe Unicode Levenshtein distance.
     */
    private function unicodeLevenshtein(string $a, string $b): int
    {
        $charsA = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY);
        $charsB = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY);
        $lenA = count($charsA);
        $lenB = count($charsB);

        if ($lenA === 0) {
            return $lenB;
        }
        if ($lenB === 0) {
            return $lenA;
        }

        $matrix = [];
        for ($i = 0; $i <= $lenA; $i++) {
            $matrix[$i][0] = $i;
        }
        for ($j = 0; $j <= $lenB; $j++) {
            $matrix[0][$j] = $j;
        }

        for ($i = 1; $i <= $lenA; $i++) {
            for ($j = 1; $j <= $lenB; $j++) {
                $cost = ($charsA[$i - 1] === $charsB[$j - 1]) ? 0 : 1;
                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,       // deletion
                    $matrix[$i][$j - 1] + 1,       // insertion
                    $matrix[$i - 1][$j - 1] + $cost // substitution
                );
            }
        }

        return $matrix[$lenA][$lenB];
    }

    /**
     * Resolve project using multi-layer deterministic matching pipeline.
     */
    public function resolveProject(array $data): ProjectMatchResult
    {
        $rawName = trim($data['project_name'] ?? '');
        if ($rawName === '') {
            return new ProjectMatchResult(
                project: null,
                status: 'not_found',
                score: 0.0,
                method: '',
                candidates: [],
                reason: 'Empty project name provided.'
            );
        }

        // --- Layer 1: Exact Match on project_name ---
        $exactMatches = Project::where('project_name', $rawName)->get();
        if ($exactMatches->count() === 1) {
            return new ProjectMatchResult(
                project: $exactMatches->first(),
                status: 'matched_exact',
                score: 1.0,
                method: 'exact',
                candidates: [],
                reason: 'Exact project_name match.'
            );
        } elseif ($exactMatches->count() > 1) {
            $candidates = $exactMatches->map(fn ($p) => [
                'id' => $p->id,
                'project_name' => $p->project_name,
                'score' => 1.0,
            ])->values()->toArray();

            return new ProjectMatchResult(
                project: null,
                status: 'ambiguous',
                score: 1.0,
                method: 'exact',
                candidates: $candidates,
                reason: 'Multiple exact matches found in database.'
            );
        }

        // --- Prepare Candidates for In-Memory Normalized & Fuzzy Matching ---
        if ($this->candidateCache === null) {
            $this->candidateCache = Project::all();
        }

        $normExcel = $this->normalizeProjectName($rawName);

        // --- Layer 2: Normalized Exact Match (Arabic-Aware) ---
        $normMatches = $this->candidateCache->filter(function ($proj) use ($normExcel) {
            $norm = $proj->normalized_name_cache ??= $this->normalizeProjectName($proj->project_name);

            return $norm === $normExcel;
        })->values();

        if ($normMatches->count() === 1) {
            return new ProjectMatchResult(
                project: $normMatches->first(),
                status: 'matched_normalized',
                score: 1.0,
                method: 'normalized',
                candidates: [],
                reason: 'Normalized Arabic-aware exact match.'
            );
        } elseif ($normMatches->count() > 1) {
            $candidates = $normMatches->map(fn ($p) => [
                'id' => $p->id,
                'project_name' => $p->project_name,
                'score' => 1.0,
            ])->values()->toArray();

            return new ProjectMatchResult(
                project: null,
                status: 'ambiguous',
                score: 1.0,
                method: 'normalized',
                candidates: $candidates,
                reason: 'Multiple projects match the same normalized Arabic name.'
            );
        }

        // --- Layer 3: Context-Aware Multibyte Fuzzy Matching ---
        $tokensExcel = preg_split('/\s+/u', $normExcel, -1, PREG_SPLIT_NO_EMPTY);
        $excelTokenCount = count($tokensExcel);
        $scoredCandidates = [];

        foreach ($this->candidateCache as $proj) {
            $normProj = $proj->normalized_name_cache ??= $this->normalizeProjectName($proj->project_name);

            // 1. Unicode Character Levenshtein Similarity
            $distance = $this->unicodeLevenshtein($normExcel, $normProj);
            $maxLength = max(mb_strlen($normExcel, 'UTF-8'), mb_strlen($normProj, 'UTF-8'));
            $charScore = $maxLength > 0 ? (1.0 - ($distance / $maxLength)) : 1.0;

            // 2. Token Similarity (Jaccard)
            $tokensProj = preg_split('/\s+/u', $normProj, -1, PREG_SPLIT_NO_EMPTY);
            $projTokenCount = count($tokensProj);

            $intersection = count(array_intersect($tokensExcel, $tokensProj));
            $union = count(array_unique(array_merge($tokensExcel, $tokensProj)));
            $tokenScore = $union > 0 ? ($intersection / $union) : 0.0;

            // 3. Gradual Containment Score
            $shorterCount = min($excelTokenCount, $projTokenCount);
            $containmentScore = $shorterCount > 0 ? ($intersection / $shorterCount) : 0.0;

            // 4. Combined Weighted Score: 0.5 char, 0.3 token, 0.2 containment
            $combinedScore = (0.5 * $charScore) + (0.3 * $tokenScore) + (0.2 * $containmentScore);

            $scoredCandidates[] = [
                'project' => $proj,
                'id' => $proj->id,
                'project_name' => $proj->project_name,
                'score' => $combinedScore,
            ];
        }

        // Sort candidates by combined score descending
        usort($scoredCandidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        $best = $scoredCandidates[0] ?? null;
        $second = $scoredCandidates[1] ?? null;

        $bestScore = $best ? (float) $best['score'] : 0.0;
        $secondScore = $second ? (float) $second['score'] : 0.0;

        $MATCH_THRESHOLD = 0.92;
        $GAP_THRESHOLD = 0.08;
        $REVIEW_THRESHOLD = 0.60;

        $topCandidates = array_slice(array_map(fn ($item) => [
            'id' => $item['id'],
            'project_name' => $item['project_name'],
            'score' => round($item['score'], 4),
        ], $scoredCandidates), 0, 3);

        if ($best && $bestScore >= $MATCH_THRESHOLD && ($bestScore - $secondScore) >= $GAP_THRESHOLD) {
            return new ProjectMatchResult(
                project: $best['project'],
                status: 'matched_fuzzy',
                score: round($bestScore, 4),
                method: 'fuzzy',
                candidates: $topCandidates,
                reason: 'Unique high-confidence fuzzy match.'
            );
        }

        if ($best && $bestScore >= $REVIEW_THRESHOLD) {
            return new ProjectMatchResult(
                project: null,
                status: 'needs_review',
                score: round($bestScore, 4),
                method: 'fuzzy',
                candidates: $topCandidates,
                reason: ($bestScore >= $MATCH_THRESHOLD && ($bestScore - $secondScore) < $GAP_THRESHOLD)
                    ? 'Best candidate score is high but second best candidate is too close.'
                    : 'Confidence score below automatic match threshold.'
            );
        }

        return new ProjectMatchResult(
            project: null,
            status: 'not_found',
            score: round($bestScore, 4),
            method: 'fuzzy',
            candidates: [],
            reason: 'No matching project found with sufficient similarity.'
        );
    }

    /**
     * Lookup references.
     */
    public function lookupReference($model, $value)
    {
        if (empty(trim($value ?? ''))) {
            return null;
        }

        $value = trim(preg_replace('/[\s\x{00a0}\x{200b}]+/u', ' ', (string) $value));

        if ($model === Authority::class) {
            $record = Authority::where('agency_name', $value)->first();
            if (! $record) {
                $record = Authority::whereRaw("TRIM(REPLACE(agency_name, '\xC2\xA0', ' ')) = ?", [$value])->first();
            }
        } else {
            $record = $model::where('name', $value)->first();
            if (! $record) {
                $record = $model::whereRaw("TRIM(REPLACE(name, '\xC2\xA0', ' ')) = ?", [$value])->first();
            }
        }

        return $record ? $record->id : 'unknown:'.$value;
    }

    /**
     * Clean and parse monetary amounts resiliently (handles commas, Arabic digits, spaces, currency symbols).
     */
    public function parseAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);

        // Convert Eastern Arabic numerals (٠-٩) to Western (0-9)
        $arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $westernNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $str = str_replace($arabicNumerals, $westernNumerals, $str);

        // Remove any spaces or non-breaking spaces
        $str = preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', $str);

        // Remove currency symbols, Arabic text, etc. Keep only digits, dots, commas, minus
        $str = preg_replace('/[^\d\.\,\-]/u', '', $str);

        if ($str === '' || $str === '-') {
            return 0.0;
        }

        // Handle commas (thousands separator vs decimal)
        if (str_contains($str, ',') && str_contains($str, '.')) {
            $str = str_replace(',', '', $str);
        } elseif (str_contains($str, ',') || str_contains($str, '،')) {
            $str = str_replace(['،', ','], '', $str);
        }

        return (float) $str;
    }

    /**
     * Parse raw row data using mapping.
     *
     * ✅ تم حذف financing_percentage — النسبة تُحسب تلقائيًا بعد الاستيراد.
     */
    public function parseRow(array $rawRow, array $mapping): array
    {
        $result = [
            'project' => [
                'project_name' => '',
            ],
            'financing' => [
                'funding_source_id' => null,
                'authority_id' => null,
                'financing_type_id' => null,
                'financing_form_id' => null,
                'sub_financing_form_id' => null,
                'financing_amount' => null,
                // ✅ لا يوجد financing_percentage — يُحسب تلقائيًا
            ],
            'raw_row' => $rawRow,
        ];

        foreach ($mapping as $rawHeader => $systemField) {
            if (empty($systemField)) {
                continue;
            }

            // ✅ تجاهل أي محاولة لربط عمود بالنسبة المئوية
            if ($systemField === 'financing_percentage') {
                continue;
            }

            $rawHeaderTrimmed = trim((string) $rawHeader);
            $value = $rawRow[$rawHeader] ?? $rawRow[$rawHeaderTrimmed] ?? null;
            if ($value === null) {
                foreach ($rawRow as $k => $v) {
                    if (trim((string) $k) === $rawHeaderTrimmed) {
                        $value = $v;
                        break;
                    }
                }
            }

            if ($systemField === 'project_name') {
                $result['project'][$systemField] = $value !== null ? trim((string) $value) : '';

                continue;
            }

            if (array_key_exists($systemField, $result['financing'])) {
                if ($systemField === 'financing_amount') {
                    $result['financing'][$systemField] = $value !== null ? $this->parseAmount($value) : null;
                } else {
                    $result['financing'][$systemField] = $value !== null ? trim((string) $value) : null;
                }
            }
        }

        return $result;
    }

    /**
     * Compare financings for duplicates.
     */
    private function isDuplicate(Project $project, array $finData): bool
    {
        return $project->financings()->where([
            'funding_source_id' => $finData['funding_source_id'],
            'authority_id' => $finData['authority_id'],
            'financing_type_id' => $finData['financing_type_id'],
            'financing_form_id' => $finData['financing_form_id'],
            'sub_financing_form_id' => $finData['sub_financing_form_id'],
            'financing_amount' => $finData['financing_amount'],
        ])->exists();
    }

    /**
     * Update an incomplete existing financing record if available, or create new.
     */
    private function fillMissingFinancing(Project $project, array $finData): void
    {
        // 1. If exact complete duplicate already exists, skip
        if ($this->isDuplicate($project, $finData)) {
            return;
        }

        // 2. Look for an existing financing that has missing/null fields
        $existing = $project->financings()->get();
        $target = null;

        foreach ($existing as $fin) {
            $hasMissing = is_null($fin->funding_source_id)
                || is_null($fin->authority_id)
                || is_null($fin->financing_type_id)
                || is_null($fin->financing_form_id)
                || is_null($fin->sub_financing_form_id)
                || empty($fin->financing_amount)
                || (float) $fin->financing_amount <= 0;

            if (! $hasMissing) {
                continue;
            }

            // Check compatibility with incoming data
            $isCompatible = true;
            if ($fin->funding_source_id && $finData['funding_source_id'] && $fin->funding_source_id != $finData['funding_source_id']) {
                $isCompatible = false;
            }
            if ($fin->authority_id && $finData['authority_id'] && $fin->authority_id != $finData['authority_id']) {
                $isCompatible = false;
            }
            if ($fin->financing_type_id && $finData['financing_type_id'] && $fin->financing_type_id != $finData['financing_type_id']) {
                $isCompatible = false;
            }

            if ($isCompatible) {
                $target = $fin;
                break;
            }
        }

        if ($target) {
            $fields = ['funding_source_id', 'authority_id', 'financing_type_id', 'financing_form_id', 'sub_financing_form_id'];
            foreach ($fields as $field) {
                if (is_null($target->{$field}) && ! is_null($finData[$field])) {
                    $target->{$field} = $finData[$field];
                }
            }
            if ((is_null($target->financing_amount) || (float) $target->financing_amount <= 0) && ! empty($finData['financing_amount'])) {
                $target->financing_amount = $finData['financing_amount'];
            }
            $target->save();
        } else {
            ProjectFinancing::create([
                'project_id' => $project->id,
                'funding_source_id' => $finData['funding_source_id'],
                'authority_id' => $finData['authority_id'],
                'financing_type_id' => $finData['financing_type_id'],
                'financing_form_id' => $finData['financing_form_id'],
                'sub_financing_form_id' => $finData['sub_financing_form_id'],
                'financing_amount' => $finData['financing_amount'],
                'financing_percentage' => null,
            ]);
        }
    }

    /**
     * Update existing financing matching reference fields, or create new.
     */
    private function updateExistingFinancing(Project $project, array $finData): void
    {
        $existing = $project->financings()->get();
        $target = null;

        // Try exact reference match first
        foreach ($existing as $fin) {
            if ($fin->funding_source_id == $finData['funding_source_id']
                && $fin->authority_id == $finData['authority_id']
                && $fin->financing_type_id == $finData['financing_type_id']) {
                $target = $fin;
                break;
            }
        }

        // If not found, try funding_source match
        if (! $target && ! empty($finData['funding_source_id'])) {
            foreach ($existing as $fin) {
                if ($fin->funding_source_id == $finData['funding_source_id']) {
                    $target = $fin;
                    break;
                }
            }
        }

        // If still not found and there is only 1 financing on project, update it
        if (! $target && $existing->count() === 1) {
            $target = $existing->first();
        }

        if ($target) {
            $target->update([
                'funding_source_id' => $finData['funding_source_id'] ?? $target->funding_source_id,
                'authority_id' => $finData['authority_id'] ?? $target->authority_id,
                'financing_type_id' => $finData['financing_type_id'] ?? $target->financing_type_id,
                'financing_form_id' => $finData['financing_form_id'] ?? $target->financing_form_id,
                'sub_financing_form_id' => $finData['sub_financing_form_id'] ?? $target->sub_financing_form_id,
                'financing_amount' => $finData['financing_amount'] ?? $target->financing_amount,
            ]);
        } else {
            ProjectFinancing::create([
                'project_id' => $project->id,
                'funding_source_id' => $finData['funding_source_id'],
                'authority_id' => $finData['authority_id'],
                'financing_type_id' => $finData['financing_type_id'],
                'financing_form_id' => $finData['financing_form_id'],
                'sub_financing_form_id' => $finData['sub_financing_form_id'],
                'financing_amount' => $finData['financing_amount'],
                'financing_percentage' => null,
            ]);
        }
    }

    /**
     * Process import of a single row's financings.
     *
     * Modes:
     * - 'replace': Delete existing and insert new.
     * - 'fill_missing': Complete missing fields in existing incomplete records, or create new.
     * - 'merge': Keep existing and insert new (skipping duplicates).
     * - 'update_existing': Match existing records and update their values, or create new.
     */
    public function importRow(Project $project, array $financings, string $mode = 'replace'): void
    {
        DB::transaction(function () use ($project, $financings, $mode) {
            if ($mode === 'replace') {
                $project->financings()->delete();
                foreach ($financings as $finData) {
                    ProjectFinancing::create([
                        'project_id' => $project->id,
                        'funding_source_id' => $finData['funding_source_id'],
                        'authority_id' => $finData['authority_id'],
                        'financing_type_id' => $finData['financing_type_id'],
                        'financing_form_id' => $finData['financing_form_id'],
                        'sub_financing_form_id' => $finData['sub_financing_form_id'],
                        'financing_amount' => $finData['financing_amount'],
                        'financing_percentage' => null,
                    ]);
                }
            } elseif ($mode === 'fill_missing') {
                foreach ($financings as $finData) {
                    $this->fillMissingFinancing($project, $finData);
                }
            } elseif ($mode === 'update_existing') {
                foreach ($financings as $finData) {
                    $this->updateExistingFinancing($project, $finData);
                }
            } else { // default or 'merge'
                foreach ($financings as $finData) {
                    if ($this->isDuplicate($project, $finData)) {
                        continue;
                    }

                    ProjectFinancing::create([
                        'project_id' => $project->id,
                        'funding_source_id' => $finData['funding_source_id'],
                        'authority_id' => $finData['authority_id'],
                        'financing_type_id' => $finData['financing_type_id'],
                        'financing_form_id' => $finData['financing_form_id'],
                        'sub_financing_form_id' => $finData['sub_financing_form_id'],
                        'financing_amount' => $finData['financing_amount'],
                        'financing_percentage' => null,
                    ]);
                }
            }

            // ✅ حساب النسب المئوية لجميع تمويلات المشروع (القديمة + الجديدة)
            $this->calculationService->recalculate($project);

            // ✅ مزامنة المشروع تلقائياً مع إدارة التمكين إذا تضمن تمويل قرض أو تمكين
            try {
                app(ProjectService::class)->syncProjectToEmpowermentDepartment($project);
            } catch (\Exception $e) {
                Log::error('Failed to sync empowerment in ProjectFinancingImportService', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * ✅ إعادة حساب النسب المئوية لجميع تمويلات مشروع ما يدويًا.
     * يمكن استدعاؤها من الكنترولر أو من أي مكان آخر.
     *
     * @return int عدد التمويلات التي تم تحديثها
     */
    public function recalculatePercentages(Project $project): int
    {
        if (! $project) {
            return 0;
        }

        $financings = ProjectFinancing::where('project_id', $project->id)->get();

        if ($financings->isEmpty()) {
            return 0;
        }

        $totalAmount = (float) $financings->sum('financing_amount');

        if ($totalAmount <= 0) {
            // لا يمكن حساب النسب بدون مبلغ إجمالي موجب
            return 0;
        }

        $updated = 0;

        foreach ($financings as $financing) {
            $amount = (float) $financing->financing_amount;

            // حساب النسبة
            $percentage = ($amount / $totalAmount) * 100;

            // تقييد القيمة بين 0 و 100 (لتفادي أي انحراف تقريب)
            $percentage = max(0, min(100, $percentage));

            // تقريب لخانتين عشريتين
            $percentage = round($percentage, 2);

            // ✅ استخدام update مباشر على قاعدة البيانات لتفادي Mutator
            DB::table('project_financings')
                ->where('id', $financing->id)
                ->update(['financing_percentage' => $percentage]);

            $updated++;
        }

        return $updated;
    }
}

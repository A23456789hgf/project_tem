<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\DocumentLetterheadService;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

class ProjectPdfExportController extends Controller
{
    /**
     * العلاقات الكاملة للمشاريع لتصدير PDF
     */
    protected $projectPdfRelations = [
        'program',
        'domain',
        'subdomain',
        'intervention',
        'priority',
        'detail',
        'locations.governorate',
        'locations.directorate',
        'locations.subArea',
        'locations.village',
        'mainObjectives',
        'specialObjectives.results.outputs',
        'objectiveResults',
        'resultOutputs',
        'risks',
        'cost',
        'financings.fundingSource',
        'financings.authority',
        'financings.financingType',
        'financings.financingForm',
        'financings.subFinancingForm',
        'supervisingAuthorities.authority',
        'supervisingAuthorities.parent',
        'implementingEntities.authority',
        'implementingEntities.parent',
        'participatingEntities.authority',
        'participatingEntities.parent',
        'beneficiaryEntities.authority',
        'beneficiaryEntities.parent',
        'beneficiaryGroups',
        'documents',
        'preliminaryActivities.procedures.costs.financialItem',
        'preliminaryActivities.procedures.costs.unit',
        'preliminaryFinancialSummaries',
        'executiveActivities.actions.assignedEntities',
        'executiveActivities.actions.costs.financialItem',
        'executiveFinancialSummaries',
    ];

    /**
     * إعدادات PDF
     */
    protected $pdfConfig = [
        'paper' => 'A4',
        'orientation' => 'portrait',
        'font' => 'DejaVu Sans', // للاستخدام مع DomPDF
        'arabic_font' => 'dejavusans', // للاستخدام مع mPDF - دعم أفضل للعربية
        'dpi' => 150,
    ];

    /**
     * تصدير مشروع كملف PDF باستخدام DomPDF أو mPDF
     */
    public function exportProjectPdf(Project $project, Request $request)
    {
        $pdfEngine = $request->get('engine', 'mpdf'); // mpdf افتراضياً لدعم أفضل للعربية

        try {
            // تحميل جميع العلاقات اللازمة
            $project->load($this->projectPdfRelations);

            // إنشاء QR Code لعرض المشروع
            $qrCodeUrl = route('projects.show', $project->id);
            $qrCodeImage = $this->generateQrCode($qrCodeUrl);

            // معالجة النصوص العربية للعرض الصحيح في PDF (فقط لـ DomPDF)
            if ($pdfEngine === 'dompdf') {
                $project = $this->processArabicText($project);
            }

            // حساب الإجمالي
            $totalFinancing = $this->calculateTotalFinancing($project);
            $totalProjectCost = $project->cost->total_cost ?? $totalFinancing;

            // حل الترويسة الرسمية للجهة المصدرة للمشروع
            $letterheadData = app(DocumentLetterheadService::class)->resolveFor($project);
            $letterhead = $letterheadData->letterhead;
            $logoBase64 = $letterheadData->logoBase64 ?? '';
            if (empty($logoBase64)) {
                $logoPath = public_path('images/logo.png');
                if (file_exists($logoPath)) {
                    $logoData = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/png;base64,'.base64_encode($logoData);
                }
            }

            // بيانات إضافية للعرض
            $pdfData = [
                'project' => $project,
                'letterhead' => $letterhead,
                'letterheadData' => $letterheadData,
                'qrCodeBase64' => $qrCodeImage,
                'logoBase64' => $logoBase64, // Added
                'qrCodeUrl' => $qrCodeUrl,
                'exportDate' => now()->format('Y-m-d H:i:s'),
                'printDate' => now()->format('Y-m-d'),
                'totalFinancing' => $totalFinancing,
                'totalProjectCost' => $totalProjectCost,
                'preliminaryCosts' => $this->calculatePreliminaryCosts($project),
                'executiveCosts' => $this->calculateExecutiveCosts($project),
                'engine' => $pdfEngine,
            ];

            $fileName = 'project_'.$project->form_number.'_'.date('Y-m-d').'.pdf';

            if ($pdfEngine === 'mpdf') {
                return $this->generateWithMpdf($pdfData, $fileName, 'download');
            }

            // الاستخدام مع DomPDF
            return $this->generateWithDompdf($pdfData, $fileName, 'download');

        } catch (\Exception $e) {
            Log::channel('pdf_export')->error('Failed to export PDF: '.$e->getMessage(), [
                'project_id' => $project->id,
                'engine' => $pdfEngine,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'guest',
                'ip_address' => request()->ip(),
            ]);

            return back()->with('error', 'فشل في تصدير ملف PDF: '.$e->getMessage());
        }
    }

    /**
     * عرض PDF في المتصفح دون تحميل
     */
    public function viewProjectPdf(Project $project, Request $request)
    {
        $pdfEngine = $request->get('engine', 'mpdf');

        try {
            $project->load($this->projectPdfRelations);

            $qrCodeUrl = route('projects.show', $project->id);
            $qrCodeImage = $this->generateQrCode($qrCodeUrl);

            // معالجة النصوص العربية فقط لـ DomPDF
            if ($pdfEngine === 'dompdf') {
                $project = $this->processArabicText($project);
            }

            $totalFinancing = $this->calculateTotalFinancing($project);
            $totalProjectCost = $project->cost->total_cost ?? $totalFinancing;

            // حل الترويسة الرسمية للجهة المصدرة للمشروع
            $letterheadData = app(DocumentLetterheadService::class)->resolveFor($project);
            $letterhead = $letterheadData->letterhead;
            $logoBase64 = $letterheadData->logoBase64 ?? '';
            if (empty($logoBase64)) {
                $logoPath = public_path('images/logo.png');
                if (file_exists($logoPath)) {
                    $logoData = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/png;base64,'.base64_encode($logoData);
                }
            }

            $pdfData = [
                'project' => $project,
                'letterhead' => $letterhead,
                'letterheadData' => $letterheadData,
                'qrCodeBase64' => $qrCodeImage,
                'logoBase64' => $logoBase64, // Added
                'qrCodeUrl' => $qrCodeUrl,
                'exportDate' => now()->format('Y-m-d H:i:s'),
                'printDate' => now()->format('Y-m-d'),
                'totalFinancing' => $totalFinancing,
                'totalProjectCost' => $totalProjectCost,
                'preliminaryCosts' => $this->calculatePreliminaryCosts($project),
                'executiveCosts' => $this->calculateExecutiveCosts($project),
                'engine' => $pdfEngine,
            ];

            $fileName = 'project_'.$project->form_number.'_'.date('Y-m-d').'.pdf';

            if ($pdfEngine === 'mpdf') {
                return $this->generateWithMpdf($pdfData, $fileName, 'stream');
            }

            return $this->generateWithDompdf($pdfData, $fileName, 'stream');

        } catch (\Exception $e) {
            Log::channel('pdf_export')->error('Failed to view PDF: '.$e->getMessage(), [
                'project_id' => $project->id,
                'engine' => $pdfEngine,
                'exception' => $e,
            ]);

            return back()->with('error', 'فشل في عرض ملف PDF: '.$e->getMessage());
        }
    }

    /**
     * إنشاء PDF باستخدام DomPDF
     */
    private function generateWithDompdf(array $data, string $fileName, string $outputType = 'download')
    {
        $pdf = DomPDF::loadView('projects.pdf.export', $data);

        // إعدادات DomPDF
        $pdf->setPaper($this->pdfConfig['paper'], $this->pdfConfig['orientation']);
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => $this->pdfConfig['font'],
            'dpi' => $this->pdfConfig['dpi'],
            'enable_php' => true,
            'isFontSubsettingEnabled' => true,
            'defaultMediaType' => 'screen',
            'chroot' => public_path(),
        ]);

        Log::channel('pdf_export')->info('PDF generated with DomPDF', [
            'file_name' => $fileName,
            'output_type' => $outputType,
            'engine' => 'dompdf',
        ]);

        if ($outputType === 'stream') {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    /**
     * إنشاء PDF باستخدام mPDF (دعم أفضل للعربية)
     */
    private function generateWithMpdf(array $data, string $fileName, string $outputType = 'download')
    {
        // التأكد من تثبيت mPDF عبر composer: composer require mpdf/mpdf
        if (! class_exists('\\Mpdf\\Mpdf')) {
            throw new \Exception('mPDF library is not installed. Please install it via composer: composer require mpdf/mpdf');
        }

        // تقديم الـ view
        $html = view('projects.pdf.export', $data)->render();

        // تنظيف HTML للتعامل مع النصوص العربية
        $html = $this->cleanArabicHtml($html);

        // إعداد mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $this->pdfConfig['paper'],
            'orientation' => $this->pdfConfig['orientation'],
            'default_font' => $this->pdfConfig['arabic_font'],
            'direction' => 'rtl', // مهم للنصوص العربية
            'margin_right' => 10,
            'margin_left' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 5,
            'margin_footer' => 5,
            'tempDir' => storage_path('app/tmp/mpdf'), // مجلد مؤقت
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        // إضافة خطوط عربية إضافية إذا كانت متوفرة
        $this->configureMpdfFonts($mpdf);

        $mpdf->SetTitle($fileName);
        $mpdf->SetAuthor(config('app.name'));
        $mpdf->SetCreator('Project Management System');

        // كتابة المحتوى
        $mpdf->WriteHTML($html);

        Log::channel('pdf_export')->info('PDF generated with mPDF', [
            'file_name' => $fileName,
            'output_type' => $outputType,
            'engine' => 'mpdf',
        ]);

        if ($outputType === 'stream') {
            return $mpdf->Output($fileName, 'I'); // I للعرض في المتصفح
        }

        return $mpdf->Output($fileName, 'D'); // D للتحميل
    }

    /**
     * تنظيف HTML للنصوص العربية
     */
    private function cleanArabicHtml(string $html): string
    {
        // إصلاح المشاكل الشائعة في النصوص العربية
        $replacements = [
            'ـ' => '', // إزالة التطويل
            '‌' => '', // إزالة المسافات الزائدة
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        // إضافة دعم CSS للنصوص العربية
        $arabicCss = "
        <style>
            .arabic-text {
                direction: rtl;
                text-align: right;
                font-family: '{$this->pdfConfig['arabic_font']}', 'DejaVu Sans', sans-serif;
            }
            .arabic-title {
                direction: rtl;
                text-align: center;
                font-family: '{$this->pdfConfig['arabic_font']}', 'DejaVu Sans', sans-serif;
                font-weight: bold;
            }
            table.arabic-table {
                direction: rtl;
                width: 100%;
                border-collapse: collapse;
            }
            table.arabic-table th {
                text-align: right;
                font-family: '{$this->pdfConfig['arabic_font']}', 'DejaVu Sans', sans-serif;
                background-color: #f8f9fa;
            }
        </style>
        ";

        return $arabicCss.$html;
    }

    /**
     * إعداد الخطوط لـ mPDF
     */
    private function configureMpdfFonts($mpdf)
    {
        // إضافة خطوط عربية إضافية إذا كانت متوفرة
        $fontDirs = [
            storage_path('fonts/'),
            public_path('fonts/'),
            resource_path('fonts/'),
        ];

        foreach ($fontDirs as $dir) {
            if (is_dir($dir)) {
                $mpdf->fontDir[] = $dir;
            }
        }

        // خطوط افتراضية مدعومة مع mPDF
        $defaultFonts = [
            'Amiri' => [
                'R' => 'Amiri-Regular.ttf',
                'B' => 'Amiri-Bold.ttf',
                'I' => 'Amiri-Italic.ttf',
                'BI' => 'Amiri-BoldItalic.ttf',
            ],
            'DejaVu Sans' => [
                'R' => 'DejaVuSans.ttf',
                'B' => 'DejaVuSans-Bold.ttf',
                'I' => 'DejaVuSans-Oblique.ttf',
                'BI' => 'DejaVuSans-BoldOblique.ttf',
            ],
        ];

        // يمكن إضافة المزيد من الخطوط هنا حسب الحاجة
    }

    /**
     * تصدير قائمة المشاريع كملف PDF
     */
    public function exportProjectsListPdf(Request $request)
    {
        $pdfEngine = $request->get('engine', 'mpdf');

        try {
            $query = Project::with(['program', 'domain', 'cost']);

            // Handle different export types
            $exportType = $request->get('export_type', 'all');

            if ($exportType === 'selected') {
                $selectedProjects = $request->get('selected_projects');
                if ($selectedProjects) {
                    if (is_string($selectedProjects)) {
                        $selectedProjects = json_decode($selectedProjects, true);
                    }
                    if (! empty($selectedProjects)) {
                        $query->whereIn('id', $selectedProjects);
                    }
                }
            } else {
                // تطبيق الفلترة إذا وجدت
                if ($request->has('program_id') && $request->program_id) {
                    $query->where('program_id', $request->program_id);
                }

                if ($request->has('domain_id') && $request->domain_id) {
                    $query->where('domain_id', $request->domain_id);
                }

                if ($request->has('status') && $request->status) {
                    $query->where('status', $request->status);
                }
            }

            $projects = $query->latest()->get();

            $pdfData = [
                'projects' => $projects,
                'filters' => $request->all(),
                'exportDate' => now()->format('Y-m-d H:i:s'),
                'totalProjects' => $projects->count(),
                'totalCost' => $projects->sum('cost.total_cost'),
                'engine' => $pdfEngine,
            ];

            $fileName = 'projects_list_'.date('Y-m-d').'.pdf';

            if ($pdfEngine === 'mpdf') {
                return $this->generateWithMpdf($pdfData, $fileName, 'download');
            }

            $pdf = DomPDF::loadView('projects.pdf.projects-list', $pdfData);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => $this->pdfConfig['font'],
                'dpi' => $this->pdfConfig['dpi'],
            ]);

            Log::channel('pdf_export')->info('Projects list PDF exported successfully', [
                'projects_count' => $projects->count(),
                'file_name' => $fileName,
                'engine' => $pdfEngine,
                'user_id' => auth()->id() ?? 'guest',
                'ip_address' => request()->ip(),
            ]);

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::channel('pdf_export')->error('Failed to export projects list PDF: '.$e->getMessage(), [
                'exception' => $e,
                'engine' => $pdfEngine,
                'user_id' => auth()->id() ?? 'guest',
                'ip_address' => request()->ip(),
            ]);

            return back()->with('error', 'فشل في تصدير قائمة المشاريع: '.$e->getMessage());
        }
    }

    /**
     * تصدير ملخص المشروع (تقرير مختصر)
     */
    public function exportProjectSummaryPdf(Project $project, Request $request)
    {
        $pdfEngine = $request->get('engine', 'mpdf');

        try {
            $project->load([
                'program',
                'domain',
                'subdomain',
                'cost',
                'financings.fundingSource',
                'implementingEntities.authority',
                'locations.governorate',
            ]);

            $project = $this->processArabicText($project);

            $pdfData = [
                'project' => $project,
                'exportDate' => now()->format('Y-m-d H:i:s'),
                'totalFinancing' => $this->calculateTotalFinancing($project),
                'engine' => $pdfEngine,
            ];

            $fileName = 'project_summary_'.$project->form_number.'_'.date('Y-m-d').'.pdf';

            if ($pdfEngine === 'mpdf') {
                return $this->generateWithMpdf($pdfData, $fileName, 'download');
            }

            $pdf = DomPDF::loadView('projects.pdf.summary', $pdfData);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => $this->pdfConfig['font'],
                'dpi' => $this->pdfConfig['dpi'],
            ]);

            Log::channel('pdf_export')->info('Project summary PDF exported successfully', [
                'project_id' => $project->id,
                'file_name' => $fileName,
                'engine' => $pdfEngine,
                'user_id' => auth()->id() ?? 'guest',
            ]);

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::channel('pdf_export')->error('Failed to export project summary PDF: '.$e->getMessage(), [
                'project_id' => $project->id,
                'engine' => $pdfEngine,
                'exception' => $e,
            ]);

            return back()->with('error', 'فشل في تصدير ملخص المشروع: '.$e->getMessage());
        }
    }

    // باقي الدوال كما هي بدون تغيير (generateQrCode, processArabicText, containsArabic, etc.)
    // ... [جميع الدوال الأخرى تبقى كما هي]

    /**
     * إنشاء QR Code
     */
    private function generateQrCode(string $url): string
    {
        try {
            $qrCodeImage = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl='.urlencode($url).'&choe=UTF-8';
            $qrImageData = @file_get_contents($qrCodeImage);

            if ($qrImageData !== false) {
                return 'data:image/png;base64,'.base64_encode($qrImageData);
            }

            return $qrCodeImage;
        } catch (\Exception $e) {
            Log::warning('Failed to generate QR code, using URL instead', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $qrCodeImage ?? $url;
        }
    }

    /**
     * معالجة النصوص العربية للعرض الصحيح في PDF
     */
    private function processArabicText($project)
    {
        if (! class_exists(Arabic::class)) {
            return $project;
        }

        $arabic = new Arabic;

        $processText = function ($value) use ($arabic, &$processText) {
            if (is_string($value) && $this->containsArabic($value)) {
                return $arabic->utf8Glue($arabic->utf8Strrev($value));
            }

            if (is_object($value)) {
                foreach (get_object_vars($value) as $key => $prop) {
                    // تجنب بعض الخصائص التي لا تحتاج معالجة
                    if (in_array($key, ['created_at', 'updated_at', 'deleted_at', 'id'])) {
                        continue;
                    }
                    $value->$key = $processText($prop);
                }

                return $value;
            }

            if (is_array($value)) {
                return array_map($processText, $value);
            }

            return $value;
        };

        return $processText($project);
    }

    /**
     * التحقق من وجود أحرف عربية في النص
     */
    private function containsArabic($text): bool
    {
        if (! is_string($text)) {
            return false;
        }

        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
    }

    /**
     * حساب إجمالي التمويل
     */
    private function calculateTotalFinancing(Project $project): float
    {
        return $project->financings->sum('financing_amount');
    }

    /**
     * حساب تكاليف الأنشطة التمهيدية
     */
    private function calculatePreliminaryCosts(Project $project): float
    {
        $total = 0;

        foreach ($project->preliminaryActivities as $activity) {
            foreach ($activity->procedures as $procedure) {
                foreach ($procedure->costs as $cost) {
                    $total += ($cost->amount * $cost->quantity);
                }
            }
        }

        return $total;
    }

    /**
     * حساب تكاليف الأنشطة التنفيذية
     */
    private function calculateExecutiveCosts(Project $project): float
    {
        $total = 0;

        foreach ($project->executiveActivities as $activity) {
            foreach ($activity->actions as $action) {
                foreach ($action->costs as $cost) {
                    $total += ($cost->amount * $cost->quantity);
                }
            }
        }

        return $total;
    }

    /**
     * الحصول على إحصائيات المشروع للتقرير
     */
    public function getProjectStats(Project $project)
    {
        try {
            $project->load($this->projectPdfRelations);

            $stats = [
                'basic_info' => [
                    'project_name' => $project->project_name,
                    'form_number' => $project->form_number,
                    'status' => $project->status,
                    'program' => $project->program->name ?? 'N/A',
                    'domain' => $project->domain->name ?? 'N/A',
                ],
                'financial' => [
                    'total_cost' => $project->cost->total_cost ?? 0,
                    'total_financing' => $this->calculateTotalFinancing($project),
                    'preliminary_costs' => $this->calculatePreliminaryCosts($project),
                    'executive_costs' => $this->calculateExecutiveCosts($project),
                ],
                'entities' => [
                    'supervising_count' => $project->supervisingAuthorities->count(),
                    'implementing_count' => $project->implementingEntities->count(),
                    'participating_count' => $project->participatingEntities->count(),
                    'beneficiary_count' => $project->beneficiaryEntities->count(),
                ],
                'objectives' => [
                    'main_objectives_count' => $project->mainObjectives->count(),
                    'special_objectives_count' => $project->specialObjectives->count(),
                    'results_count' => $project->objectiveResults->count(),
                    'outputs_count' => $project->resultOutputs->count(),
                ],
                'activities' => [
                    'preliminary_activities_count' => $project->preliminaryActivities->count(),
                    'executive_activities_count' => $project->executiveActivities->count(),
                ],
                'risks' => [
                    'risks_count' => $project->risks->count(),
                    'high_risks_count' => $project->risks->where('risk_rate', '>=', 7)->count(),
                    'medium_risks_count' => $project->risks->whereBetween('risk_rate', [4, 6])->count(),
                    'low_risks_count' => $project->risks->where('risk_rate', '<=', 3)->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get project stats: '.$e->getMessage(), [
                'project_id' => $project->id,
                'error' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get project statistics',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Project;

use App\Exports\QualityExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\Project;
use App\Models\ProjectQuality;
use App\Services\QualityReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QualityController extends Controller
{
    protected ProjectService $projectService;

    protected QualityReportService $qualityReportService;

    public function __construct(ProjectService $projectService, QualityReportService $qualityReportService)
    {
        $this->projectService = $projectService;
        $this->qualityReportService = $qualityReportService;
    }

    /**
     * عرض لوحة جودة جميع المشاريع (صفحة الفهرس)
     * يتم استخدامها عبر route('projects.quality.index')
     *
     * ⚠️ ملاحظة:
     * خطأ LazyLoadingViolationException الخاص بـ [createdBy] بيحصل جوه
     * QualityReportService::getReportData() عند سطر ~93.
     * الإصلاح لازم يكون هناك عن طريق إضافة ->with('createdBy')
     * للـ Query الخاص بالمشاريع قبل الرجوع.
     */
    public function index(Request $request)
    {
        $reportData = $this->qualityReportService->getReportData($request);

        $projects = $reportData['projects_data'];
        $kpis = $reportData['kpis'];
        $charts = $reportData['charts'];

        return view('projects.quality.dashboard', compact('projects', 'kpis', 'charts'));
    }

    public function print(Request $request)
    {
        $reportData = $this->qualityReportService->getReportData($request);

        return view('projects.quality.print', $reportData);
    }

    public function export(Request $request)
    {
        $reportData = $this->qualityReportService->getReportData($request);

        return QualityExport::export($reportData);
    }

    /**
     * عرض صفحة جودة مشروع واحد (تفصيلية)
     * يتم استخدامها عبر route('projects.quality.show', $project)
     */
    public function show(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        $project->load([
            // ✅ منع lazy loading على العلاقات المستخدمة في الـ blade
            'createdBy',
            'creatorEntity',

            // الأنشطة التحضيرية مع الإجراءات
            'preliminaryActivities.procedures' => function ($query) {
                $query->with([
                    'costs.financialItem',
                    'executions' => function ($q) {
                        $q->latest();
                    },
                ]);
            },

            // الأنشطة التنفيذية مع الإجراءات
            'executiveActivities.actions' => function ($query) {
                $query->with([
                    'costs.financialItem',
                    'executions' => function ($q) {
                        $q->latest();
                    },
                ]);
            },

            // سجلات الجودة الموجودة
            'qualityRecords',
        ]);

        // حساب مقاييس الجودة للإجراءات التحضيرية
        $preliminaryQuality = $this->calculatePreliminaryQuality($project);

        // حساب مقاييس الجودة للإجراءات التنفيذية
        $executiveQuality = $this->calculateExecutiveQuality($project);

        // حساب الإحصائيات الموجزة
        $statistics = $this->calculateStatistics($preliminaryQuality, $executiveQuality);

        return view('projects.quality.index', compact(
            'project',
            'preliminaryQuality',
            'executiveQuality',
            'statistics'
        ));
    }

    /**
     * حساب مقاييس الجودة للإجراءات التحضيرية
     */
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

    /**
     * حساب مقاييس الجودة للإجراءات التنفيذية
     */
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

    /**
     * تقييم جودة الوقت بمقارنة التواريخ المخططة والفعلية
     */
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

    /**
     * تقييم الجودة المالية بمقارنة المبالغ المخططة والفعلية
     */
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

    /**
     * حساب الإحصائيات الموجزة
     */
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

    /**
     * حفظ حل جودة مع المرفقات
     */
    public function store(Request $request)
    {
        if (! auth()->user()->hasPermission('quality.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالقيام بهذا الإجراء',
            ], 403);
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'record_type' => 'required|in:preliminary,executive',
            'activity_id' => 'required|integer',
            'procedure_or_action_id' => 'required|integer',
            'quality_aspect' => 'required|in:time,financial',
            'quality_status' => 'required|in:positive,negative',
            'time_status' => 'nullable|in:ahead,on_time,late',
            'financial_status' => 'nullable|in:below_plan,matching_plan,over_plan',
            'planned_start_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date',
            'planned_amount' => 'nullable|numeric',
            'actual_amount' => 'nullable|numeric',
            'variance_days' => 'nullable|integer',
            'variance_amount' => 'nullable|numeric',
            'proposed_solution' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store("quality_attachments/{$validated['project_id']}", 'public');
                    $attachmentPaths[] = $path;
                }
            }

            $validated['attachments'] = $attachmentPaths;

            $qualityRecord = ProjectQuality::updateOrCreate(
                [
                    'project_id' => $validated['project_id'],
                    'record_type' => $validated['record_type'],
                    'procedure_or_action_id' => $validated['procedure_or_action_id'],
                    'quality_aspect' => $validated['quality_aspect'],
                ],
                $validated
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الحل المقترح بنجاح',
                'data' => $qualityRecord,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ البيانات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث سجل جودة موجود
     */
    public function update(Request $request, ProjectQuality $quality)
    {
        if (! auth()->user()->hasPermission('quality.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالقيام بهذا الإجراء',
            ], 403);
        }

        $validated = $request->validate([
            'proposed_solution' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $attachmentPaths = $quality->attachments ?? [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store("quality_attachments/{$quality->project_id}", 'public');
                    $attachmentPaths[] = $path;
                }
            }

            $quality->update([
                'proposed_solution' => $validated['proposed_solution'],
                'attachments' => $attachmentPaths,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحل المقترح بنجاح',
                'data' => $quality,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف سجل جودة
     */
    public function destroy(ProjectQuality $quality)
    {
        if (! auth()->user()->hasPermission('quality.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالقيام بهذا الإجراء',
            ], 403);
        }

        DB::beginTransaction();
        try {
            if ($quality->hasAttachments()) {
                foreach ($quality->attachments as $attachment) {
                    Storage::disk('public')->delete($attachment);
                }
            }

            $quality->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السجل بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف السجل: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحميل مرفق
     */
    public function downloadAttachment(ProjectQuality $quality, $filename)
    {
        $attachments = $quality->attachments ?? [];

        foreach ($attachments as $attachment) {
            if (basename($attachment) === $filename) {
                return Storage::disk('public')->download($attachment);
            }
        }

        abort(404, 'الملف غير موجود');
    }
}

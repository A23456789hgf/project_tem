<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\Project;
use App\Models\ProjectAchievement;
use App\Models\ProjectAchievementDocument;
use App\Models\ProjectAchievementFunding;
use App\Models\ReportType;
use App\Services\DocumentLetterheadService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectAchievementController extends Controller
{
    /**
     * Display the achievements history list for a project.
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $achievements = $project->achievements()
            ->with(['reportType', 'creator', 'fundings.authority', 'documents'])
            ->get();

        $latestAchievement = $achievements->first()?->new_achievement ?? 0;

        $governorates = $project->locations->map(fn ($l) => $l->governorate?->name)
            ->filter()->unique()->implode('، ') ?: 'جميع المحافظات';

        $directorates = $project->locations->map(fn ($l) => $l->directorate?->name)
            ->filter()->unique()->implode('، ') ?: 'جميع المديريات';

        $implementingEntitiesList = $project->implementingEntities->map(function ($entity) {
            return $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
        })->filter()->unique()->implode('، ') ?: '-';

        session()->flash('info', 'تم عرض قائمة الإنجازات للمشروع بنجاح.');

        return view('projects.achievements.index', compact(
            'project',
            'achievements',
            'latestAchievement',
            'governorates',
            'directorates',
            'implementingEntitiesList'
        ));
    }

    /**
     * Print the full achievements report for a project.
     */
    public function print(Project $project)
    {
        $this->authorize('view', $project);

        $achievements = $project->achievements()
            ->with(['reportType', 'creator', 'fundings.authority', 'documents'])
            ->get();

        $latestAchievement = $achievements->first()?->new_achievement ?? 0;

        $governorates = $project->locations->map(fn ($l) => $l->governorate?->name)
            ->filter()->unique()->implode('، ') ?: 'جميع المحافظات';

        $directorates = $project->locations->map(fn ($l) => $l->directorate?->name)
            ->filter()->unique()->implode('، ') ?: 'جميع المديريات';

        $implementingEntitiesList = $project->implementingEntities->map(function ($entity) {
            return $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
        })->filter()->unique()->implode('، ') ?: '-';

        session()->flash('info', 'جاري طباعة التقرير الكامل للإنجازات.');

        $letterheadData = app(DocumentLetterheadService::class)->resolveForDocument($project);
        $letterhead = $letterheadData->letterhead;

        return view('projects.achievements.print', compact(
            'project',
            'letterhead',
            'letterheadData',
            'achievements',
            'latestAchievement',
            'governorates',
            'directorates',
            'implementingEntitiesList'
        ));
    }

    /**
     * Show a single achievement detail page.
     */
    public function show(Project $project, ProjectAchievement $achievement)
    {
        $this->authorize('view', $project);

        $achievement->load(['reportType', 'creator', 'fundings.authority', 'documents']);

        $governorates = $project->locations->map(fn ($l) => $l->governorate?->name)
            ->filter()->unique()->implode('، ') ?: 'جميع المحافظات';

        $directorates = $project->locations->map(fn ($l) => $l->directorate?->name)
            ->filter()->unique()->implode('، ') ?: 'جميع المديريات';

        $implementingEntitiesList = $project->implementingEntities->map(function ($entity) {
            return $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
        })->filter()->unique()->implode('، ') ?: '-';

        session()->flash('info', 'تم عرض تفاصيل الإنجاز بنجاح.');

        return view('projects.achievements.show', compact(
            'project',
            'achievement',
            'governorates',
            'directorates',
            'implementingEntitiesList'
        ));
    }

    /**
     * Print a single achievement as a standalone report.
     */
    public function printSingle(Project $project, ProjectAchievement $achievement)
    {
        $this->authorize('view', $project);

        $achievement->load(['reportType', 'creator', 'fundings.authority', 'documents']);

        $governorates = $project->locations->map(fn ($l) => $l->governorate?->name)
            ->filter()->unique()->implode('، ') ?: 'جميع المحافظات';

        $directorates = $project->locations->map(fn ($l) => $l->directorate?->name)
            ->filter()->unique()->implode('، ') ?: 'جميع المديريات';

        $implementingEntitiesList = $project->implementingEntities->map(function ($entity) {
            return $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
        })->filter()->unique()->implode('، ') ?: '-';

        session()->flash('info', 'جاري طباعة التقرير الفردي للإنجاز.');

        $letterheadData = app(DocumentLetterheadService::class)->resolveForDocument($project);
        $letterhead = $letterheadData->letterhead;

        return view('projects.achievements.print-single', compact(
            'project',
            'letterhead',
            'letterheadData',
            'achievement',
            'governorates',
            'directorates',
            'implementingEntitiesList'
        ));
    }

    /**
     * Show the form for creating a new achievement.
     */
    public function create(Project $project)
    {
        $this->authorize('edit', $project);

        /*
        |--------------------------------------------------------------------------
        | التحقق من الشروط الإدارية لتسجيل الإنجاز
        |--------------------------------------------------------------------------
        */
        // 1. التحقق من إضافة/تعديل الجهة المقدمة (المنفذة)
        if ($project->implementingEntities()->count() === 0) {
            session()->flash('error', 'يجب تعديل وتحديد الجهة المقدمة للمشروع أولاً قبل التمكن من إضافة الإنجازات.');

            return redirect()->route('projects.show', $project->id);
        }

        // 2. للمشاريع القديمة: يجب استكمال البيانات أولاً قبل إضافة الإنجازات
        if ($project->project_type === 'old' && ! $project->is_data_completed) {
            session()->flash('error', 'يجب استكمال بيانات المشروع أولاً من قبل الجهة قبل إضافة الإنجازات.');

            return redirect()->route('projects.show', $project->id);
        }

        // Fetch active report types
        $reportTypes = ReportType::where('is_active', true)->orderBy('name')->get();

        // Get history of achievements for this project
        $achievementsHistory = $project->achievements()
            ->with(['reportType', 'creator', 'fundings.authority', 'documents'])
            ->get();

        // Retrieve latest recorded achievement percentage
        $previousAchievementValue = $achievementsHistory->first()?->new_achievement ?? 0;

        // Compile Governorate(s)
        $governorates = $project->locations->map(function ($loc) {
            return $loc->governorate?->name;
        })->filter()->unique()->implode('، ') ?: 'جميع المحافظات';

        // Compile Directorate(s)
        $directorates = $project->locations->map(function ($loc) {
            return $loc->directorate?->name;
        })->filter()->unique()->implode('، ') ?: 'جميع المديريات';

        // Compile Implementing Entity name(s)
        $implementingEntitiesList = $project->implementingEntities->map(function ($entity) {
            return $entity->authority_type === 'internal'
                ? $entity->internalEntity?->name
                : $entity->authority?->agency_name;
        })->filter()->unique()->implode('، ') ?: '-';

        // Get financing sources from the project's own budget configuration
        $projectFinancings = $project->financings()->with(['fundingSource', 'authority'])->get();

        // ===== 1. بناء مصفوفة جهات التمويل من ميزانية المشروع الأصلية =====
        $projectFinancings = $project->financings()->with(['fundingSource', 'authority'])->get();

        $projectFundingData = [];
        foreach ($projectFinancings as $financing) {
            $authorityId = $financing->authority_id;
            if ($authorityId) {
                $amount = (float) ($financing->financing_amount ?? 0);
                if (! isset($projectFundingData[$authorityId])) {
                    $projectFundingData[$authorityId] = [
                        'total_funding' => $amount,
                        'authority_name' => $financing->authority?->agency_name ?? 'جهة غير معروفة',
                    ];
                } else {
                    $projectFundingData[$authorityId]['total_funding'] += $amount;
                }
            }
        }

        // ===== 2. حساب إجمالي الصرف التراكمي وإجمالي التمويل لكل جهة من جميع الإنجازات السابقة =====
        $achievements = $project->achievements()->with('fundings.authority')->oldest()->get();

        $totalDisbursedData = [];
        $recordedTotalFunding = [];

        foreach ($achievements as $achievement) {
            foreach ($achievement->fundings as $funding) {
                $authorityId = $funding->authority_id;
                if ($authorityId) {
                    if ((float) $funding->total_funding > 0) {
                        $recordedTotalFunding[$authorityId] = (float) $funding->total_funding;
                    }
                    // الصرف التراكمي = الصرف السابق + الصرف الجديد المسجل
                    $totalDisbursed = (float) ($funding->previous_disbursement + $funding->new_disbursement);
                    $totalDisbursedData[$authorityId] = $totalDisbursed;
                }
            }
        }

        // ===== 3. دمج بيانات التمويل والصرف لتكوين مصفوفة شاملة ودقيقة لكافة الجهات =====
        $allAuthorityIds = array_unique(array_merge(
            array_keys($projectFundingData),
            array_keys($totalDisbursedData),
            array_keys($recordedTotalFunding)
        ));

        $lastFundingData = [];
        $fundingSummary = [];

        foreach ($allAuthorityIds as $authorityId) {
            $authority = Authority::find($authorityId);
            $authorityName = $projectFundingData[$authorityId]['authority_name']
                ?? $authority?->agency_name
                ?? 'جهة غير معروفة';

            // إجمالي التمويل المعتمد للجهة (من الميزانية أو من الإنجاز السابق)
            $totalFund = (float) ($projectFundingData[$authorityId]['total_funding']
                ?? $recordedTotalFunding[$authorityId]
                ?? 0);

            // إجمالي الصرف التراكمي السابق لهذا الإنجاز الجديد
            $prevDisb = (float) ($totalDisbursedData[$authorityId] ?? 0);

            // المتبقي السابق من الصرف
            $prevRem = max(0, $totalFund - $prevDisb);

            $percentage = ($totalFund > 0) ? round(($prevDisb / $totalFund) * 100, 2) : 0;

            $lastFundingData[$authorityId] = [
                'authority_id' => $authorityId,
                'authority_name' => $authorityName,
                'total_funding' => $totalFund,
                'previous_disbursement' => $prevDisb,
                'previous_remaining_disbursement' => $prevRem,
                'disbursement_percentage' => $percentage,
            ];

            $fundingSummary[$authorityId] = [
                'authority_name' => $authorityName,
                'total_funding' => $totalFund,
                'total_disbursed' => $prevDisb,
                'remaining' => $prevRem,
            ];

            if (! isset($projectFundingData[$authorityId])) {
                $projectFundingData[$authorityId] = [
                    'total_funding' => $totalFund,
                    'authority_name' => $authorityName,
                ];
            }
        }

        // ===== 4. جلب جميع الجهات للقائمة المنسدلة =====
        $allAuthorities = Authority::orderBy('agency_name')->get();

        session()->flash('info', 'نموذج إضافة إنجاز جديد للمشروع.');

        return view('projects.achievements.create', compact(
            'project',
            'governorates',
            'directorates',
            'implementingEntitiesList',
            'reportTypes',
            'previousAchievementValue',
            'achievementsHistory',
            'projectFinancings',
            'allAuthorities',
            'projectFundingData',  // بيانات التمويل الإجمالي (للتعبئة التلقائية)
            'lastFundingData',      // بيانات الصرف من آخر إنجاز (للتعبئة التلقائية)
            'fundingSummary'        // ملخص التمويلات (للجدول العلوي)
        ));
    }

    /**
     * Store a newly created achievement in storage.
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('edit', $project);

        /*
        |--------------------------------------------------------------------------
        | التحقق من الشروط الإدارية لتسجيل الإنجاز
        |--------------------------------------------------------------------------
        */
        // 1. التحقق من إضافة/تعديل الجهة المقدمة (المنفذة)
        if ($project->implementingEntities()->count() === 0) {
            session()->flash('error', 'يجب تعديل وتحديد الجهة المقدمة للمشروع أولاً قبل التمكن من حفظ الإنجازات.');

            return redirect()->route('projects.show', $project->id);
        }

        // 2. للمشاريع القديمة: يجب استكمال البيانات أولاً قبل إضافة الإنجازات
        if ($project->project_type === 'old' && ! $project->is_data_completed) {
            session()->flash('error', 'يجب استكمال بيانات المشروع أولاً من قبل الجهة قبل إضافة الإنجازات.');

            return redirect()->route('projects.show', $project->id);
        }

        $request->validate([
            'report_type_id' => 'required|exists:report_types,id',
            'start_date_gregorian' => 'required|date',
            'start_date_hijri' => 'required|string|max:20',
            'end_date_gregorian' => 'required|date|after_or_equal:start_date_gregorian',
            'end_date_hijri' => 'required|string|max:20',
            'duration' => 'required|string|max:100',
            'new_achievement' => 'required|numeric|min:0|max:100',
            'achieved_outputs' => 'required|string',
            'achieved_indicators' => 'required|string',
            'number_of_beneficiaries' => 'required|integer|min:0',
            'notes_on_beneficiaries' => 'required|string',
            'comments' => 'nullable|string',

            // Funding rows using authority_id
            'fundings' => 'required|array|min:1',
            'fundings.*.authority_id' => 'nullable|exists:authorities,id',
            'fundings.*.new_authority_name' => 'nullable|string|max:255|required_without:fundings.*.authority_id',
            'fundings.*.total_funding' => 'required|numeric|min:0',
            'fundings.*.previous_disbursement' => 'required|numeric|min:0',
            'fundings.*.previous_remaining_disbursement' => 'required|numeric|min:0',
            'fundings.*.new_disbursement' => 'required|numeric|min:0',
            'fundings.*.disbursement_percentage' => 'required|numeric|min:0|max:100',

            // Attachments
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx,ppt,pptx|max:20480',
        ], [
            'report_type_id.required' => 'نوع التقرير مطلوب.',
            'start_date_gregorian.required' => 'تاريخ البدء الميلادي مطلوب.',
            'start_date_hijri.required' => 'تاريخ البدء الهجري مطلوب.',
            'end_date_gregorian.required' => 'تاريخ الانتهاء الميلادي مطلوب.',
            'end_date_gregorian.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون مساوياً أو بعد تاريخ البدء.',
            'end_date_hijri.required' => 'تاريخ الانتهاء الهجري مطلوب.',
            'new_achievement.required' => 'نسبة الإنجاز الجديدة مطلوبة.',
            'new_achievement.numeric' => 'نسبة الإنجاز يجب أن تكون قيمة رقمية.',
            'achieved_outputs.required' => 'المخرجات المحققة مطلوبة.',
            'achieved_indicators.required' => 'المؤشرات المحققة مطلوبة.',
            'number_of_beneficiaries.required' => 'عدد المستفيدين مطلوب.',
            'notes_on_beneficiaries.required' => 'ملاحظات المستفيدين مطلوبة.',
            'fundings.required' => 'يجب إضافة جهة تمويل واحدة على الأقل في الجدول.',
            'fundings.*.authority_id.required_without' => 'يجب اختيار جهة تمويل أو إدخال اسم جهة جديدة.',
            'fundings.*.new_authority_name.required_without' => 'يجب إدخال اسم الجهة الجديدة أو اختيار جهة موجودة.',
        ]);

        // ===== التحقق المخصص: لا يجوز أن يتجاوز الصرف الجديد المتبقي السابق =====
        foreach ($request->fundings as $index => $fundingData) {
            $newDisbursement = $fundingData['new_disbursement'] ?? 0;
            $remaining = $fundingData['previous_remaining_disbursement'] ?? 0;
            if ($newDisbursement > $remaining) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        "fundings.{$index}.new_disbursement" => 'الصرف الجديد لا يمكن أن يتجاوز المتبقي السابق من الصرف.',
                    ]);
            }
        }

        DB::beginTransaction();

        try {
            // Determine previous achievement percentage (most recent new_achievement)
            $lastAchievement = $project->achievements()->first();
            $previousAchievement = $lastAchievement ? $lastAchievement->new_achievement : 0.00;

            // Create the main achievement record
            $achievement = ProjectAchievement::create([
                'project_id' => $project->id,
                'report_type_id' => $request->report_type_id,
                'start_date_gregorian' => $request->start_date_gregorian,
                'start_date_hijri' => $request->start_date_hijri,
                'end_date_gregorian' => $request->end_date_gregorian,
                'end_date_hijri' => $request->end_date_hijri,
                'duration' => $request->duration,
                'previous_achievement' => $previousAchievement,
                'new_achievement' => $request->new_achievement,
                'achieved_outputs' => $request->achieved_outputs,
                'achieved_indicators' => $request->achieved_indicators,
                'number_of_beneficiaries' => $request->number_of_beneficiaries,
                'notes_on_beneficiaries' => $request->notes_on_beneficiaries,
                'comments' => $request->comments,
                'created_by' => auth()->id(),
            ]);

            // Save funding and disbursement rows
            foreach ($request->fundings as $fundingData) {
                $authorityId = null;

                // If new authority name provided, create it
                if (! empty($fundingData['new_authority_name'])) {
                    $authority = Authority::create([
                        'agency_name' => $fundingData['new_authority_name'],
                    ]);
                    $authorityId = $authority->id;
                } elseif (! empty($fundingData['authority_id'])) {
                    $authorityId = $fundingData['authority_id'];
                }

                ProjectAchievementFunding::create([
                    'project_achievement_id' => $achievement->id,
                    'authority_id' => $authorityId,
                    'funding_entity' => null, // for backward compatibility
                    'total_funding' => $fundingData['total_funding'],
                    'previous_disbursement' => $fundingData['previous_disbursement'],
                    'previous_remaining_disbursement' => $fundingData['previous_remaining_disbursement'],
                    'new_disbursement' => $fundingData['new_disbursement'],
                    'disbursement_percentage' => $fundingData['disbursement_percentage'],
                ]);
            }

            // Save attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store("projects/{$project->id}/achievements", 'public');

                    ProjectAchievementDocument::create([
                        'project_achievement_id' => $achievement->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            // ─── إرسال إشعار للمشاريع القديمة عند تسجيل إنجاز ───
            if ($project->project_type === 'old') {
                app(NotificationService::class)->notifyOldProjectAchievementRecorded($project, $achievement);
            }

            session()->flash('success', 'تم تسجيل إنجاز المشروع وحفظ البيانات بنجاح.');

            return redirect()->route('projects.show', $project->id);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء حفظ الإنجاز: '.$e->getMessage()]);
        }
    }

    /**
     * Display a listing of all achievements across previous projects.
     */
    public function listAll(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        // جلب الإنجازات مع العلاقات الفعليّة المستخدمة في العرض فقط
        $query = ProjectAchievement::with([
            'project:id,project_name,form_number,project_type',
            'reportType:id,name',
        ])->whereHas('project', function ($q) {
            $q->where('project_type', 'old');
        });

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('project', function ($pq) use ($search) {
                    $pq->where('project_name', 'like', "%{$search}%")
                        ->orWhere('form_number', 'like', "%{$search}%");
                })->orWhereHas('reportType', function ($rq) use ($search) {
                    $rq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Project filter
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $achievements = $query->orderBy('created_at', 'desc')->paginate(15);

        // تكييش قائمة المشاريع القديمة للقائمة المنسدلة وجلب الحقول المطلوبة فقط
        $oldProjects = Cache::remember('old_projects_dropdown_list', now()->addHours(1), function () {
            return Project::where('project_type', 'old')
                ->select(['id', 'project_name', 'form_number'])
                ->orderBy('project_name')
                ->get();
        });

        session()->flash('info', 'تم عرض جميع الإنجازات المسجلة بنجاح.');

        return view('projects.achievements.all', compact('achievements', 'oldProjects'));
    }
}

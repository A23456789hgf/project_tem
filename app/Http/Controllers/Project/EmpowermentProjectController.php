<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\EmpowermentBeneficiary; // تم إضافة الموديل لغرض التحقق والتزامن
use App\Models\EmpowermentProject;
use App\Models\FinancingType;
use App\Models\Project;
use Illuminate\Http\Request;

class EmpowermentProjectController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * عرض صفحة القروض والحسابات - إدارة التمكين
     * الرابط: /projects/empowerment
     */
    public function index(Request $request)
    {
        $scopedProjectQuery = $this->projectService->getProjects($request, null, null, [], true, true)->select('projects.id');

        // شبكة أمان تلقائية: مزامنة فورية لأي مشروع تمويله قرض/تمكين ولم يُدرج بعد في جدول التمكين
        $unsyncedProjectIds = Project::whereIn('id', (clone $scopedProjectQuery))
            ->whereHas('financings', function ($q) {
                $q->whereHas('financingType', function ($ft) {
                    $ft->where('name', 'like', '%قرض%')
                        ->orWhere('name', 'like', '%قروض%')
                        ->orWhere('name', 'like', '%تمكين%')
                        ->orWhere('name', 'like', '%محتوى%');
                })->orWhereHas('fundingSource', function ($fs) {
                    $fs->where('name', 'like', '%قرض%')
                        ->orWhere('name', 'like', '%قروض%')
                        ->orWhere('name', 'like', '%تمكين%')
                        ->orWhere('name', 'like', '%محتوى%');
                });
            })
            ->whereDoesntHave('empowermentProject')
            ->pluck('id');

        if ($unsyncedProjectIds->isNotEmpty()) {
            foreach (Project::whereIn('id', $unsyncedProjectIds)->get() as $unsyncedProj) {
                $this->projectService->syncProjectToEmpowermentDepartment($unsyncedProj);
            }
        }

        $query = EmpowermentProject::with('project', 'processor')
            ->whereIn('project_id', $scopedProjectQuery)
            ->orderByDesc('created_at');

        // فلتر بحث بالاسم أو الرقم
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('project_number', 'like', "%{$search}%");
            });
        }

        // فلتر بالحالة
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $empowermentProjects = $query->paginate(20)->withQueryString();

        $statuses = EmpowermentProject::STATUSES;

        return view('projects.empowerment.index', compact('empowermentProjects', 'statuses'));
    }

    /**
     * عرض تفاصيل مشروع تمكين واحد
     * GET /projects/empowerment/{empowermentProject}
     */
    public function show(EmpowermentProject $empowermentProject)
    {
        if (! $empowermentProject->exists) {
            abort(404);
        }

        if ($empowermentProject->project) {
            $this->projectService->authorizeProjectAccess($empowermentProject->project);
        }

        $empowermentProject->load([
            'project.program',
            'project.domain',
            'project.subdomain',
            'project.intervention',
            'project.locations.governorate',
            'project.locations.directorate',
            'project.locations.subArea',
            'project.locations.village',
            'project.financings.fundingSource',
            'project.financings.financingType',
            'project.cost',
            'project.createdBy',
            'project.mainObjectives',
            'project.specialObjectives.results.outputs',
            'project.risks',
            'project.supervisingAuthorities.authority',
            'project.supervisingAuthorities.parent',
            'project.implementingEntities.authority',
            'project.participatingEntities.authority',
            'project.preliminaryActivities',
            'project.executiveActivities',
            'project.beneficiaryGroups',
            'processor',
        ]);

        $p = $empowermentProject;
        $proj = $p->project;

        // ─── مزامنة تلقائية لمبلغ (القروض/التمكين/المحتوى) إذا وجد اختلاف ───
        if ($proj) {
            $empowermentFinancingTypeIds = FinancingType::where(function ($q) {
                $q->where('name', 'like', '%قرض%')
                    ->orWhere('name', 'like', '%قروض%')
                    ->orWhere('name', 'like', '%تمكين%')
                    ->orWhere('name', 'like', '%محتوى%');
            })->pluck('id')->toArray();

            $actualLoanAmount = $proj->financings()
                ->whereIn('financing_type_id', $empowermentFinancingTypeIds)
                ->sum('financing_amount');

            if ($actualLoanAmount > 0 && $p->total_loan_amount != $actualLoanAmount) {
                $p->total_loan_amount = $actualLoanAmount;
                if ($p->total_project_cost > 0) {
                    $p->loan_percentage = ($actualLoanAmount / $p->total_project_cost) * 100;
                }
                $p->save();
            }
        }

        $avgLoan = $p->number_of_beneficiaries > 0 ? ($p->total_loan_amount / $p->number_of_beneficiaries) : 0;
        $loanPercent = $p->total_project_cost > 0 ? ($p->total_loan_amount / $p->total_project_cost * 100) : 0;

        // عدد المستفيدين المسجلين فعلياً
        $beneficiaryCount = EmpowermentBeneficiary::where('empowerment_project_id', $p->id)->count();

        $statuses = EmpowermentProject::STATUSES;

        return view('projects.empowerment.show', compact(
            'p', 'proj', 'avgLoan', 'loanPercent', 'statuses', 'beneficiaryCount'
        ));
    }

    /**
     * تحديث حالة المعالجة لمشروع تمكين
     * POST /projects/empowerment/{empowermentProject}/status
     */
    public function updateStatus(Request $request, EmpowermentProject $empowermentProject)
    {
        if ($empowermentProject->project) {
            $this->projectService->authorizeProjectAccess($empowermentProject->project);
        }
        $validated = $request->validate([
            'status' => 'required|in:pending,under_review,processed,rejected',
            'notes' => 'nullable|string|max:1000',
            'total_loan_amount' => 'nullable|numeric|min:0',
            'number_of_beneficiaries' => 'nullable|integer|min:0',
        ]);

        $data = [
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $empowermentProject->notes,
            'processed_by' => in_array($validated['status'], ['processed', 'rejected'])
                                ? auth()->id()
                                : $empowermentProject->processed_by,
            'processed_at' => in_array($validated['status'], ['processed', 'rejected'])
                                ? now()
                                : $empowermentProject->processed_at,
        ];

        if (isset($validated['total_loan_amount'])) {
            $data['total_loan_amount'] = $validated['total_loan_amount'];

            // Recalculate percentage if total cost exists
            if ($empowermentProject->total_project_cost > 0) {
                $data['loan_percentage'] = ($validated['total_loan_amount'] / $empowermentProject->total_project_cost) * 100;
            }
        }

        if (isset($validated['number_of_beneficiaries'])) {
            $data['number_of_beneficiaries'] = $validated['number_of_beneficiaries'];
        }

        $empowermentProject->update($data);

        return back()->with('success', 'تم تحديث البيانات بنجاح');
    }

    public function apiDetails(EmpowermentProject $project)
    {
        if ($project->project) {
            $this->projectService->authorizeProjectAccess($project->project);
        }
        $beneficiaryCount = EmpowermentBeneficiary::where('empowerment_project_id', $project->id)->count();
        $avgLoan = $project->number_of_beneficiaries > 0
            ? ($project->total_loan_amount / $project->number_of_beneficiaries)
            : 0;

        return response()->json([
            'id' => $project->id,
            'project_name' => $project->project_name,
            'total_loan_amount' => $project->total_loan_amount,
            'number_of_beneficiaries' => $project->number_of_beneficiaries,
            'beneficiary_count' => $beneficiaryCount,
            'avg_loan' => $avgLoan,
            'is_full' => $beneficiaryCount >= $project->number_of_beneficiaries,
            'avg_loan_formatted' => number_format($avgLoan, 2),
        ]);
    }
}

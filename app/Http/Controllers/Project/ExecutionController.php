<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\ExecutionBudgetJustification;
use App\Models\ExecutiveActivityAction;
use App\Models\PreliminaryProcedure;
use App\Models\PreliminaryProcedureExecution;
use App\Models\ProcedureBudgetJustification;
use App\Models\ProcedureTechnicalJustification;
use App\Models\Project;
use App\Models\ProjectActivityHistory;
use App\Models\ProjectExecution;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ScopeFilterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExecutionController extends Controller
{
    protected $projectService;

    protected $scopeFilter;

    public function __construct(ProjectService $projectService, ScopeFilterService $scopeFilter)
    {
        $this->projectService = $projectService;
        $this->scopeFilter = $scopeFilter;
    }

    /**
     * Main store method
     */
    public function store(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        if (! auth()->user()->hasPermission('execution.add', $project)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بالقيام بهذا الإجراء'], 403);
            }

            return redirect()->back()->with('error', 'غير مصرح لك بالقيام بهذا الإجراء');
        }

        $validated = $request->validate([
            'executive_activity_action_id' => 'nullable|exists:executive_activity_actions,id',
            'preliminary_procedure_id' => 'nullable|exists:preliminary_procedures,id',
            'actual_start_date_gregorian' => 'nullable|date',
            'actual_start_date_hijri' => 'nullable|string',
            'actual_finish_date_gregorian' => 'nullable|date',
            'actual_finish_date_hijri' => 'nullable|string',
            'actual_amount' => 'nullable|numeric|min:0',
            'planned_amount' => 'nullable|numeric|min:0', // 👈 أضفناها
            'amount_spent' => 'nullable|numeric|min:0',
            'remaining_amount' => 'nullable|numeric',
            'status' => 'required|in:not_started,in_progress,delayed,stalled,completed',
            'completion_percentage' => 'nullable|numeric|min:0|max:100',
            'execution_rows' => 'nullable|json',
            'notes' => 'nullable|string',
        ]);

        $actionId = $validated['executive_activity_action_id'] ?? null;
        $procedureId = $validated['preliminary_procedure_id'] ?? null;

        if (! $actionId && ! $procedureId) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'يجب تحديد النشاط أو الإجراء'], 422);
            }

            return redirect()->back()->with('error', 'يجب تحديد النشاط أو الإجراء')->withInput();
        }

        if ($actionId) {
            return $this->storeExecutiveExecution($request, $project, $validated, $actionId);
        }

        return $this->storePreliminaryExecution($request, $project, $validated, $procedureId);
    }

    /**
     * Store executive execution
     */
    private function storeExecutiveExecution(Request $request, Project $project, array $validated, $actionId)
    {
        try {
            $execution = new ProjectExecution;
            $execution->project_id = $project->id;
            $execution->executive_activity_action_id = $actionId;

            $maxSequence = ProjectExecution::where('project_id', $project->id)
                ->where('executive_activity_action_id', $actionId)
                ->max('sequence') ?? 0;
            $execution->sequence = $maxSequence + 1;

            $execution->status = $validated['status'];
            $execution->completion_percentage = $validated['completion_percentage'] ?? 0;
            $execution->actual_start_date_gregorian = $validated['actual_start_date_gregorian'] ?? null;
            $execution->actual_start_date_hijri = $validated['actual_start_date_hijri'] ?? null;
            $execution->actual_finish_date_gregorian = $validated['actual_finish_date_gregorian'] ?? null;
            $execution->actual_finish_date_hijri = $validated['actual_finish_date_hijri'] ?? null;
            $execution->actual_amount = $validated['actual_amount'] ?? 0;
            $execution->amount_spent = $validated['amount_spent'] ?? 0;
            $execution->remaining_amount = $validated['remaining_amount'] ?? 0;
            $execution->notes = $validated['notes'] ?? null;
            $execution->created_by_user_id = auth()->id();

            // 1. Validation Logic
            $currentTotals = ProjectExecution::where('project_id', $project->id)
                ->where('executive_activity_action_id', $actionId)
                ->selectRaw('SUM(amount_spent) as total_spent, SUM(completion_percentage) as total_completion')
                ->first();

            $totalSpent = ($currentTotals->total_spent ?? 0) + ($validated['amount_spent'] ?? 0);
            $totalCompletion = ($currentTotals->total_completion ?? 0) + ($validated['completion_percentage'] ?? 0);

            // Validation for budget (If exceeding, handleAutoBudgetJustification will be triggered later)
            $plannedAmount = $validated['planned_amount'] ?? 0;
            // No longer blocking budget overages here, as we show a confirmation prompt on the frontend
            // and handle it via handleAutoBudgetJustification after saving.

            // Validation for completion percentage (hard limit 100%)
            if ($totalCompletion > 100.01) { // 0.01 for float precision
                return response()->json([
                    'success' => false,
                    'message' => 'إجمالي نسبة الإنجاز لا يمكن أن يتجاوز 100%. الإجمالي الحالي سيكون: '.number_format($totalCompletion, 1).'%',
                ], 422);
            }

            // Handle Technical Documents
            if ($request->hasFile('technical_documents')) {
                $techDocs = [];
                foreach ($request->file('technical_documents') as $file) {
                    $techDocs[] = $file->store("projects/{$project->id}/executions/technical", 'public');
                }
                $execution->technical_documents = $techDocs;
            }

            // Handle Financial Documents
            if ($request->hasFile('financial_documents')) {
                $finDocs = [];
                foreach ($request->file('financial_documents') as $file) {
                    $finDocs[] = $file->store("projects/{$project->id}/executions/financial", 'public');
                }
                $execution->financial_documents = $finDocs;
            }

            $execution->save();

            // ✅ إنشاء تبرير مالي تلقائي إذا زاد المبلغ الفعلي عن المخطط
            $this->handleAutoBudgetJustification(
                $project,
                $execution,
                'executive',
                $validated['planned_amount'] ?? null,
                $validated['actual_amount'] ?? null
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حفظ التنفيذ بنجاح',
                    'data' => $execution,
                ]);
            }

            return redirect()->route('projects.execution', $project)
                ->with('success', 'تم حفظ التنفيذ بنجاح');
        } catch (\Exception $e) {
            \Log::error('Error saving executive execution: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حفظ التنفيذ: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حفظ التنفيذ')->withInput();
        }
    }

    /**
     * Store preliminary execution
     */
    private function storePreliminaryExecution(Request $request, Project $project, array $validated, $procedureId)
    {
        try {
            $execution = new PreliminaryProcedureExecution;
            $execution->project_id = $project->id;
            $execution->preliminary_procedure_id = $procedureId;

            $maxSequence = PreliminaryProcedureExecution::where('project_id', $project->id)
                ->where('preliminary_procedure_id', $procedureId)
                ->max('sequence') ?? 0;
            $execution->sequence = $maxSequence + 1;

            $execution->status = $validated['status'];
            $execution->completion_percentage = $validated['completion_percentage'] ?? 0;
            $execution->actual_start_date_gregorian = $validated['actual_start_date_gregorian'] ?? null;
            $execution->actual_start_date_hijri = $validated['actual_start_date_hijri'] ?? null;
            $execution->actual_finish_date_gregorian = $validated['actual_finish_date_gregorian'] ?? null;
            $execution->actual_finish_date_hijri = $validated['actual_finish_date_hijri'] ?? null;
            $execution->actual_amount = $validated['actual_amount'] ?? 0;
            $execution->amount_spent = $validated['amount_spent'] ?? 0;
            $execution->remaining_amount = $validated['remaining_amount'] ?? 0;
            $execution->notes = $validated['notes'] ?? null;
            $execution->created_by_user_id = auth()->id();

            // 1. Validation Logic
            $currentTotals = PreliminaryProcedureExecution::where('project_id', $project->id)
                ->where('preliminary_procedure_id', $procedureId)
                ->selectRaw('SUM(amount_spent) as total_spent, SUM(completion_percentage) as total_completion')
                ->first();

            $totalSpent = ($currentTotals->total_spent ?? 0) + ($validated['amount_spent'] ?? 0);
            $totalCompletion = ($currentTotals->total_completion ?? 0) + ($validated['completion_percentage'] ?? 0);

            // Validation for budget (If exceeding, handleAutoBudgetJustification will be triggered later)
            $plannedAmount = $validated['planned_amount'] ?? 0;
            // No longer blocking budget overages here, as we show a confirmation prompt on the frontend

            // Validation for completion percentage (hard limit 100%)
            if ($totalCompletion > 100.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'إجمالي نسبة الإنجاز لا يمكن أن يتجاوز 100%. الإجمالي الحالي سيكون: '.number_format($totalCompletion, 1).'%',
                ], 422);
            }

            // Handle Technical Documents
            if ($request->hasFile('technical_documents')) {
                $techDocs = [];
                foreach ($request->file('technical_documents') as $file) {
                    $techDocs[] = $file->store("projects/{$project->id}/preliminary/technical", 'public');
                }
                $execution->technical_documents = $techDocs;
            }

            // Handle Financial Documents
            if ($request->hasFile('financial_documents')) {
                $finDocs = [];
                foreach ($request->file('financial_documents') as $file) {
                    $finDocs[] = $file->store("projects/{$project->id}/preliminary/financial", 'public');
                }
                $execution->financial_documents = $finDocs;
            }

            $execution->save();

            // ✅ تبرير مالي تلقائي
            $this->handleAutoBudgetJustification(
                $project,
                $execution,
                'preliminary',
                $validated['planned_amount'] ?? null,
                $validated['actual_amount'] ?? null
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حفظ التنفيذ بنجاح',
                    'data' => $execution,
                ]);
            }

            return redirect()->route('projects.execution', $project)
                ->with('success', 'تم حفظ التنفيذ بنجاح');
        } catch (\Exception $e) {
            \Log::error('Error saving preliminary execution: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حفظ التنفيذ: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حفظ التنفيذ')->withInput();
        }
    }

    /**
     * 🧠 إنشاء أو تحديث التبرير المالي تلقائياً
     */
    private function handleAutoBudgetJustification(Project $project, $execution, string $type, $planned, $actual)
    {
        if ($planned === null || $actual === null) {
            return;
        }

        if ($actual <= $planned) {
            return;
        }

        $overage = $actual - $planned;

        ExecutionBudgetJustification::updateOrCreate(
            [
                'execution_id' => $execution->id,
                'execution_type' => $type,
            ],
            [
                'project_id' => $project->id,
                'planned_amount' => $planned,
                'actual_amount' => $actual,
                'overage_amount' => $overage,
                'justification' => 'تم إنشاء التبرير تلقائياً بسبب تجاوز المبلغ الفعلي للمخطط.',
                'approval_status' => 'pending',
                'created_by' => auth()->id(),
            ]
        );
    }

    public function storeProcedureBudgetJustification(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        $validated = $request->validate([
            'preliminary_procedure_id' => 'required|exists:preliminary_procedures,id',
            'planned_total' => 'required|numeric',
            'actual_total' => 'required|numeric',
            'justification' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $overage = $validated['actual_total'] - $validated['planned_total'];

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("projects/{$project->id}/procedure-justifications", 'public');
                $attachments[] = $path;
            }
        }

        $justification = ProcedureBudgetJustification::updateOrCreate(
            ['preliminary_procedure_id' => $validated['preliminary_procedure_id']],
            [
                'planned_total' => $validated['planned_total'],
                'actual_total' => $validated['actual_total'],
                'overage_amount' => $overage,
                'justification' => $validated['justification'],
                'attachments' => $attachments, // Will be cast to json by model
                'approval_status' => 'pending',
                'created_by' => auth()->id(),
            ]
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التبرير المالي بنجاح',
                'data' => $justification,
            ]);
        }

        return redirect()->back()->with('success', 'تم حفظ التبرير المالي بنجاح');
    }

    /**
     * Store executive budget justification (Executive)
     */
    public function storeExecutiveBudgetJustification(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        $validated = $request->validate([
            'executive_activity_action_id' => 'required|exists:executive_activity_actions,id',
            'planned_amount' => 'required|numeric',
            'actual_amount' => 'required|numeric',
            'justification' => 'required|string|min:10',
            'executive_financial_attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $overage = $validated['actual_amount'] - $validated['planned_amount'];

        $attachments = [];
        if ($request->hasFile('executive_financial_attachments')) {
            foreach ($request->file('executive_financial_attachments') as $file) {
                $path = $file->store("projects/{$project->id}/executive-justifications", 'public');
                $attachments[] = $path;
            }
        }

        $justification = ExecutionBudgetJustification::updateOrCreate(
            [
                'execution_type_id' => $validated['executive_activity_action_id'],
                'execution_type' => ExecutiveActivityAction::class,
            ],
            [
                'planned_amount' => $validated['planned_amount'],
                'actual_amount' => $validated['actual_amount'],
                'overage_amount' => $overage,
                'justification' => $validated['justification'],
                'attachments' => $attachments,
                'approval_status' => 'pending',
                'created_by' => auth()->id(),
            ]
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التبرير المالي بنجاح',
                'data' => $justification,
            ]);
        }

        return redirect()->back()->with('success', 'تم حفظ التبرير المالي بنجاح');
    }

    /**
     * Store executive technical justification (Executive)
     */
    public function storeExecutiveTechnicalJustification(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        $validated = $request->validate([
            'executive_activity_action_id' => 'required|exists:executive_activity_actions,id',
            'planned_end_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date',
            'justification' => 'required|string|min:10',
            'executive_technical_attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $action = ExecutiveActivityAction::findOrFail($validated['executive_activity_action_id']);

        $plannedEndDate = $validated['planned_end_date'] ? Carbon::parse($validated['planned_end_date']) : $action->end_date_gregorian;
        $actualEndDate = ($validated['actual_end_date'] ?? null) ? Carbon::parse($validated['actual_end_date']) : null;

        $delayDays = 0;
        if ($plannedEndDate && $actualEndDate) {
            $delayDays = $actualEndDate->diffInDays($plannedEndDate);
        }

        $attachments = [];
        if ($request->hasFile('executive_technical_attachments')) {
            foreach ($request->file('executive_technical_attachments') as $file) {
                $path = $file->store("projects/{$project->id}/executive-technical-justifications", 'public');
                $attachments[] = $path;
            }
        }

        $technicalJustification = ExecutionDelayExplanation::create([
            'execution_type_id' => $validated['executive_activity_action_id'],
            'execution_type' => ExecutiveActivityAction::class,
            'explanation' => $validated['justification'],
            'attachments' => $attachments,
            'approval_status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التبرير التقني بنجاح',
                'data' => $technicalJustification,
            ]);
        }

        return redirect()->back()->with('success', 'تم حفظ التبرير التقني بنجاح');
    }

    /**
     * Store procedure technical justification (Preliminary)
     */
    public function storeProcedureTechnicalJustification(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        $validated = $request->validate([
            'preliminary_procedure_id' => 'required|exists:preliminary_procedures,id',
            'planned_end_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date',
            'justification' => 'required|string|min:10',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $procedure = PreliminaryProcedure::findOrFail($validated['preliminary_procedure_id']);

        $plannedEndDate = $validated['planned_end_date'] ? Carbon::parse($validated['planned_end_date']) : $procedure->end_date;
        $actualEndDate = ($validated['actual_end_date'] ?? null) ? Carbon::parse($validated['actual_end_date']) : null;

        $delayDays = 0;
        if ($plannedEndDate && $actualEndDate) {
            $delayDays = $actualEndDate->diffInDays($plannedEndDate);
        }

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("projects/{$project->id}/technical-justifications", 'public');
                $attachments[] = $path;
            }
        }

        $technicalJustification = ProcedureTechnicalJustification::create([
            'preliminary_procedure_id' => $validated['preliminary_procedure_id'],
            'planned_end_date' => $plannedEndDate,
            'actual_end_date' => $actualEndDate,
            'delay_days' => $delayDays,
            'justification' => $validated['justification'],
            'attachments' => $attachments,
            'approval_status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التبرير التقني بنجاح',
                'data' => $technicalJustification,
            ]);
        }

        return redirect()->back()->with('success', 'تم حفظ التبرير التقني بنجاح');
    }

    /**
     * اعتماد التبرير المالي
     */
    public function approveBudgetJustification(Request $request, Project $project, $justificationId)
    {
        $this->projectService->authorizeProjectAccess($project);

        if (! auth()->user()->hasRole('financial_reviewer')) {
            abort(403);
        }

        $validated = $request->validate([
            'approval_status' => 'required|in:approved,rejected',
            'reviewer_notes' => 'nullable|string',
        ]);

        $justification = ExecutionBudgetJustification::where('project_id', $project->id)
            ->findOrFail($justificationId);

        $justification->update([
            'approval_status' => $validated['approval_status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
        ]);

        return redirect()->back()->with(
            'success',
            $validated['approval_status'] === 'approved'
                ? 'تم اعتماد التبرير المالي'
                : 'تم رفض التبرير المالي'
        );
    }

    public function preliminaryIndex(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load('preliminaryActivities.procedures');

        return view('projects.implementation.execution-preliminary', compact('project'));
    }

    public function preliminaryForm(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load('preliminaryActivities.procedures');

        return view('projects.implementation.execution-preliminary-form', compact('project'));
    }

    public function preliminaryDisplay(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load('preliminaryActivities.procedures');

        return view('projects.implementation.execution-preliminary-display', compact('project'));
    }

    public function preliminaryFinancialJustification(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load('preliminaryActivities.procedures');

        return view('projects.implementation.execution-procedure-financial-justification', compact('project'));
    }

    public function preliminaryTechnicalJustification(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load('preliminaryActivities.procedures');

        return view('projects.implementation.execution-procedure-technical-justification', compact('project'));
    }

    public function executiveIndex(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load([
            'executiveActivities.actions.executions' => fn ($query) => $query->orderBy('sequence'),
        ]);

        return view('projects.implementation.execution-executive', compact('project'));
    }

    public function executiveForm(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load([
            'executiveActivities.actions.executions' => fn ($query) => $query->orderBy('sequence'),
        ]);

        return view('projects.implementation.execution-executive-form', compact('project'));
    }

    public function executiveDisplay(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load([
            'executiveActivities.actions.executions' => fn ($query) => $query->orderBy('sequence'),
        ]);

        return view('projects.implementation.execution-executive-display', compact('project'));
    }

    public function executiveFinancialJustification(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load([
            'executiveActivities.actions.executions' => fn ($query) => $query->orderBy('sequence'),
        ]);

        return view('projects.implementation.execution-executive-financial-justification', compact('project'));
    }

    public function executiveTechnicalJustification(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $project->load([
            'executiveActivities.actions.executions' => fn ($query) => $query->orderBy('sequence'),
        ]);

        return view('projects.implementation.execution-executive-technical-justification', compact('project'));
    }

    public function procedureFinancialJustification(Project $project)
    {
        $project->load('preliminaryActivities.procedures');

        return view('projects.implementation.execution-procedure-financial-justification', compact('project'));
    }

    public function procedureTechnicalJustification(Project $project)
    {
        $project->load('preliminaryActivities.procedures');

        return view('projects.implementation.execution-procedure-technical-justification', compact('project'));
    }

    public function allFinancialJustifications()
    {
        $query = $this->projectService->getProjects(request(), null, null, [], true, true)->with([
            'preliminaryActivities.procedures.procedureBudgetJustification',
            'executiveActivities.actions.budgetJustification',
        ]);

        // Project visibility is controlled automatically via the Project model's
        // global scope (department_visibility → HasEntityVisibility trait).
        // No manual filter needed here.

        $projects = $query->get();

        return view('projects.partials.implementation.financial_justifications', compact('projects'));
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'type' => 'required|in:preliminary,executive',
            'status' => 'required|in:approved,rejected',
            'reviewer_notes' => 'nullable|string',
            'rejection_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validated['type'] === 'preliminary') {
            $justification = ProcedureBudgetJustification::findOrFail($validated['id']);
        } else {
            $justification = ExecutionBudgetJustification::findOrFail($validated['id']);
        }

        $data = [
            'approval_status' => $validated['status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
        ];

        if ($request->hasFile('rejection_attachment')) {
            $path = $request->file('rejection_attachment')->store('rejection-attachments', 'public');
            $data['rejection_attachment'] = $path;
        }

        $justification->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * Store delay explanation for procedure/action
     */
    public function storeDelayExplanation(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        if (! auth()->user()->hasPermission('execution.edit', $project)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بالقيام بهذا الإجراء'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'execution_type' => 'required|in:preliminary,executive',
            'execution_type_id' => 'required|integer',
            'planned_start_date' => 'required|date',
            'planned_end_date' => 'required|date',
            'actual_start_date' => 'required|date',
            'actual_end_date' => 'required|date',
            'explanation' => 'required|string|min:10',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("projects/{$project->id}/delay-justifications", 'public');
                $attachmentPaths[] = $path;
            }
        }

        $existingExplanation = \App\Models\ExecutionDelayExplanation::where('execution_type_id', $validated['execution_type_id'])
            ->where('execution_type', $validated['execution_type'])
            ->first();

        if ($existingExplanation) {
            $existingExplanation->update([
                'explanation' => $validated['explanation'],
                'attachments' => array_merge($existingExplanation->attachments ?? [], $attachmentPaths),
                'approval_status' => 'pending',
                'created_by' => auth()->id(),
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            $explanation = $existingExplanation;
        } else {
            $explanation = \App\Models\ExecutionDelayExplanation::create([
                'execution_type_id' => $validated['execution_type_id'],
                'execution_type' => $validated['execution_type'],
                'explanation' => $validated['explanation'],
                'attachments' => $attachmentPaths,
                'approval_status' => 'pending',
                'created_by' => auth()->id(),
            ]);
        }

        try {
            app(NotificationService::class)->notifyExecutionDelay($project, $explanation, 'submitted', auth()->user());
        } catch (\Exception $e) {
            \Log::error('Notification error in storeDelayExplanation: '.$e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التبرير التقني بنجاح',
                'delay_explanation' => $explanation,
            ]);
        }

        return redirect()->back()->with('success', 'تم حفظ التبرير التقني بنجاح');
    }

    /**
     * Get delay explanation for a procedure/action
     */
    public function getDelayExplanation(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        $validated = $request->validate([
            'execution_type' => 'required|in:preliminary,executive',
            'execution_type_id' => 'required|integer',
        ]);

        $explanation = \App\Models\ExecutionDelayExplanation::where('execution_type_id', $validated['execution_type_id'])
            ->where('execution_type', $validated['execution_type'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $explanation,
        ]);
    }

    /**
     * Update delay explanation approval status
     */
    public function approveDelayExplanation(Request $request, Project $project, $explanationId)
    {
        $this->projectService->authorizeProjectAccess($project);

        if (! auth()->user()->hasPermission('execution.edit', $project)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بالقيام بهذا الإجراء'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'approval_status' => 'required|in:approved,rejected',
            'reviewer_notes' => 'nullable|string',
        ]);

        $explanation = \App\Models\ExecutionDelayExplanation::findOrFail($explanationId);

        $explanation->update([
            'approval_status' => $validated['approval_status'],
            'reviewer_notes' => $validated['reviewer_notes'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        try {
            app(NotificationService::class)->notifyExecutionDelay($project, $explanation, $validated['approval_status'], auth()->user());
        } catch (\Exception $e) {
            \Log::error('Notification error in approveDelayExplanation: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => $validated['approval_status'] === 'approved' ? 'تم قبول التبرير' : 'تم رفض التبرير',
        ]);
    }

    /**
     * Delete attachment from delay explanation
     */
    public function deleteDelayAttachment(Request $request, Project $project, $explanationId)
    {
        $this->projectService->authorizeProjectAccess($project);

        if (! auth()->user()->hasPermission('execution.edit', $project)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بالقيام بهذا الإجراء'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'attachment_path' => 'required|string',
        ]);

        $explanation = \App\Models\ExecutionDelayExplanation::findOrFail($explanationId);

        if ($explanation->attachments) {
            $attachments = array_filter(
                $explanation->attachments,
                fn ($path) => $path !== $validated['attachment_path']
            );
            $explanation->update(['attachments' => array_values($attachments)]);

            if (Storage::disk('public')->exists($validated['attachment_path'])) {
                Storage::disk('public')->delete($validated['attachment_path']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الملف بنجاح',
        ]);
    }

    /**
     * Update execution recor
     */
    public function update(Request $request, Project $project, $executionId)
    {
        $this->projectService->authorizeProjectAccess($project);

        if (! auth()->user()->hasPermission('execution.edit', $project)) {
            return redirect()->back()->with('error', 'غير مصرح لك بالقيام بهذا الإجراء');
        }

        $isPreliminary = $request->input('type') === 'preliminary';

        if ($isPreliminary) {
            $execution = PreliminaryProcedureExecution::where('project_id', $project->id)->findOrFail($executionId);
        } else {
            $execution = ProjectExecution::where('project_id', $project->id)->findOrFail($executionId);
        }

        $validated = $request->validate([
            'actual_start_date_gregorian' => 'nullable|date',
            'actual_start_date_hijri' => 'nullable|string',
            'actual_finish_date_gregorian' => 'nullable|date',
            'actual_finish_date_hijri' => 'nullable|string',
            'actual_amount' => 'nullable|numeric|min:0',
            'amount_spent' => 'nullable|numeric|min:0',
            'remaining_amount' => 'nullable|numeric',
            'status' => 'required|in:not_started,in_progress,delayed,stalled,completed',
            'completion_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $execution->update($validated);

        return redirect()->back()->with('success', 'تم تحديث التنفيذ بنجاح');
    }

    public function destroy(Project $project, $executionId)
    {
        $this->projectService->authorizeProjectAccess($project);

        if (! auth()->user()->hasPermission('execution.delete', $project)) {
            return redirect()->back()->with('error', 'غير مصرح لك بالقيام بهذا الإجراء');
        }

        $isPreliminary = request('type') === 'preliminary';

        if ($isPreliminary) {
            $execution = PreliminaryProcedureExecution::where('project_id', $project->id)->findOrFail($executionId);
        } else {
            $execution = ProjectExecution::where('project_id', $project->id)->findOrFail($executionId);
        }

        if ($execution->technical_documents) {
            foreach ($execution->technical_documents as $doc) {
                Storage::disk('public')->delete($doc);
            }
        }

        if ($execution->financial_documents) {
            foreach ($execution->financial_documents as $doc) {
                Storage::disk('public')->delete($doc);
            }
        }

        $execution->delete();

        return redirect()->back()->with('success', 'تم حذف التنفيذ بنجاح');
    }

    public function deleteTechnicalDocument(Project $project, $executionId)
    {
        return $this->deleteDocument($project, $executionId, 'technical_documents', false);
    }

    public function deleteFinancialDocument(Project $project, $executionId)
    {
        return $this->deleteDocument($project, $executionId, 'financial_documents', false);
    }

    public function deletePreliminaryTechnicalDocument(Project $project, $executionId)
    {
        return $this->deleteDocument($project, $executionId, 'technical_documents', true);
    }

    public function deletePreliminaryFinancialDocument(Project $project, $executionId)
    {
        return $this->deleteDocument($project, $executionId, 'financial_documents', true);
    }

    private function deleteDocument(Project $project, $executionId, $type, $isPreliminary)
    {
        $this->projectService->authorizeProjectAccess($project);

        if (! auth()->user()->hasPermission('execution.edit', $project)) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك'], 403);
            }
            abort(403);
        }

        if ($isPreliminary) {
            $execution = PreliminaryProcedureExecution::where('project_id', $project->id)->findOrFail($executionId);
        } else {
            $execution = ProjectExecution::where('project_id', $project->id)->findOrFail($executionId);
        }

        $docPath = request('doc_path');

        $documents = $execution->$type ?? [];

        if (($key = array_search($docPath, $documents)) !== false) {
            unset($documents[$key]);

            // Re-index array
            $documents = array_values($documents);

            $execution->$type = $documents;
            $execution->save();

            if (Storage::disk('public')->exists($docPath)) {
                Storage::disk('public')->delete($docPath);
            }

            if (request()->expectsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->back()->with('success', 'تم حذف المستند بنجاح');
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'المستند غير موجود'], 404);
        }

        return redirect()->back()->with('error', 'المستند غير موجود');
    }

    public function downloadDocument(Request $request, Project $project, $executionId)
    {
        $this->projectService->authorizeProjectAccess($project);

        $isPreliminary = $request->query('type') === 'preliminary';
        $execution = $isPreliminary
            ? PreliminaryProcedureExecution::where('project_id', $project->id)->findOrFail($executionId)
            : ProjectExecution::where('project_id', $project->id)->findOrFail($executionId);

        $docPath = $request->query('file');
        if (! $docPath || ! Storage::disk('public')->exists($docPath)) {
            abort(404);
        }

        return Storage::disk('public')->download($docPath);
    }

    public function printExecution(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        $project->load([
            'preliminaryActivities.procedures.executions',
            'preliminaryActivities.procedures.costs.financialItem',
            'preliminaryActivities.procedures.budgetJustification',
            'preliminaryActivities.procedures.technicalJustifications',
            'executiveActivities.actions.executions',
            'executiveActivities.actions.costs.financialItem',
            'executiveActivities.actions.budgetJustification',
            'executiveActivities.actions.technicalJustification',
        ]);

        $html = view('projects.partials.implementation.execution-print', compact('project'))->render();

        // Ensure the execution directory exists in public storage
        $dir = storage_path('app/public/executions');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        // Save a copy of the execution printout
        $fileName = "execution_report_{$project->id}_".date('Y-m-d_H-i-s').'.html';
        file_put_contents($dir.DIRECTORY_SEPARATOR.$fileName, $html);

        return $html;
    }

    /**
     * Approve a preliminary procedure execution record
     */
    public function approvePreliminaryExecution(Project $project, PreliminaryProcedureExecution $execution)
    {
        $this->projectService->authorizeProjectAccess($project);

        if ($execution->project_id !== $project->id) {
            abort(404);
        }

        if (! auth()->user()->hasPermission('execution.approve', $project)) {
            return redirect()->back()->with('error', 'غير مصرح لك بالقيام بهذا الإجراء');
        }

        $execution->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        ProjectActivityHistory::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'action_type' => 'execution_approved',
            'notes' => 'تمت الموافقة على سجل تنفيذ (تمهيدي): '.($execution->procedure->title ?? 'إجراء تمهيدي'),
            'action_details' => "المبلغ المصروف: {$execution->amount_spent}",
            'metadata' => ['execution_id' => $execution->id, 'type' => 'preliminary'],
        ]);

        if ($execution->created_by && $execution->created_by !== auth()->id()) {
            try {
                $creator = User::find($execution->created_by);
                if ($creator) {
                    app(NotificationService::class)->sendNotification(
                        $creator,
                        [
                            'type' => 'execution',
                            'action_type' => 'approved',
                            'title' => 'اعتماد سجل تنفيذ: '.$project->project_name,
                            'message' => 'تمت الموافقة على سجل التنفيذ (تمهيدي) للمشروع بواسطة '.(auth()->user()?->name ?? 'النظام'),
                            'page_name' => 'متابعة التنفيذ',
                            'action_url' => route('projects.execution', $project->id),
                            'icon' => 'fas fa-check-circle',
                            'causer_id' => auth()->id(),
                            'causer_name' => auth()->user()?->name,
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Notification error in approvePreliminaryExecution: '.$e->getMessage());
            }
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم الموافقة على سجل التنفيذ بنجاح',
                'status' => 'approved',
            ]);
        }

        return redirect()->back()->with('success', 'تم الموافقة على سجل التنفيذ بنجاح');
    }

    /**
     * Reject a preliminary procedure execution record
     */
    public function rejectPreliminaryExecution(Request $request, Project $project, PreliminaryProcedureExecution $execution)
    {
        $this->projectService->authorizeProjectAccess($project);

        if ($execution->project_id !== $project->id) {
            abort(404);
        }

        if (! auth()->user()->hasPermission('execution.reject', $project)) {
            return redirect()->back()->with('error', 'غير مصرح لك بالقيام بهذا الإجراء');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:1000',
        ]);

        $execution->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        ProjectActivityHistory::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'action_type' => 'execution_rejected',
            'notes' => 'تم رفض سجل تنفيذ (تمهيدي): '.($execution->procedure->title ?? 'إجراء تمهيدي'),
            'action_details' => "سبب الرفض: {$validated['rejection_reason']}",
            'metadata' => ['execution_id' => $execution->id, 'type' => 'preliminary'],
        ]);

        if ($execution->created_by && $execution->created_by !== auth()->id()) {
            try {
                $creator = User::find($execution->created_by);
                if ($creator) {
                    app(NotificationService::class)->sendNotification(
                        $creator,
                        [
                            'type' => 'execution',
                            'action_type' => 'rejected',
                            'title' => 'رفض سجل تنفيذ: '.$project->project_name,
                            'message' => 'تم رفض سجل التنفيذ (تمهيدي) للمشروع: '.$validated['rejection_reason'],
                            'page_name' => 'متابعة التنفيذ',
                            'action_url' => route('projects.execution', $project->id),
                            'icon' => 'fas fa-times-circle',
                            'causer_id' => auth()->id(),
                            'causer_name' => auth()->user()?->name,
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Notification error in rejectPreliminaryExecution: '.$e->getMessage());
            }
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم رفض سجل التنفيذ',
                'status' => 'rejected',
            ]);
        }

        return redirect()->back()->with('success', 'تم رفض سجل التنفيذ');
    }

    /**
     * Approve an executive execution record
     */
    public function approveExecutiveExecution(Project $project, ProjectExecution $execution)
    {
        $this->projectService->authorizeProjectAccess($project);

        if ($execution->project_id !== $project->id) {
            abort(404);
        }

        if (! auth()->user()->hasPermission('execution.approve', $project)) {
            return redirect()->back()->with('error', 'غير مصرح لك بالقيام بهذا الإجراء');
        }

        $execution->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        ProjectActivityHistory::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'action_type' => 'execution_approved',
            'notes' => 'تمت الموافقة على سجل تنفيذ (تنفيزي): '.($execution->action->title ?? 'نشاط تنفيذي'),
            'action_details' => "المبلغ المصروف: {$execution->amount_spent}",
            'metadata' => ['execution_id' => $execution->id, 'type' => 'executive'],
        ]);

        if ($execution->created_by && $execution->created_by !== auth()->id()) {
            try {
                $creator = User::find($execution->created_by);
                if ($creator) {
                    app(NotificationService::class)->sendNotification(
                        $creator,
                        [
                            'type' => 'execution',
                            'action_type' => 'approved',
                            'title' => 'اعتماد سجل تنفيذ: '.$project->project_name,
                            'message' => 'تمت الموافقة على سجل التنفيذ (تنفيزي) للمشروع بواسطة '.(auth()->user()?->name ?? 'النظام'),
                            'page_name' => 'متابعة التنفيذ',
                            'action_url' => route('projects.execution', $project->id),
                            'icon' => 'fas fa-check-circle',
                            'causer_id' => auth()->id(),
                            'causer_name' => auth()->user()?->name,
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Notification error in approveExecutiveExecution: '.$e->getMessage());
            }
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم الموافقة على سجل التنفيذ بنجاح',
                'status' => 'approved',
            ]);
        }

        return redirect()->back()->with('success', 'تم الموافقة على سجل التنفيذ بنجاح');
    }

    /**
     * Reject an executive execution record
     */
    public function rejectExecutiveExecution(Request $request, Project $project, ProjectExecution $execution)
    {
        $this->projectService->authorizeProjectAccess($project);

        if ($execution->project_id !== $project->id) {
            abort(404);
        }

        if (! auth()->user()->hasPermission('execution.reject', $project)) {
            return redirect()->back()->with('error', 'غير مصرح لك بالقيام بهذا الإجراء');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:1000',
        ]);

        $execution->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        ProjectActivityHistory::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'action_type' => 'execution_rejected',
            'notes' => 'تم رفض سجل تنفيذ (تنفيزي): '.($execution->action->title ?? 'نشاط تنفيذي'),
            'action_details' => "سبب الرفض: {$validated['rejection_reason']}",
            'metadata' => ['execution_id' => $execution->id, 'type' => 'executive'],
        ]);

        if ($execution->created_by && $execution->created_by !== auth()->id()) {
            try {
                $creator = User::find($execution->created_by);
                if ($creator) {
                    app(NotificationService::class)->sendNotification(
                        $creator,
                        [
                            'type' => 'execution',
                            'action_type' => 'rejected',
                            'title' => 'رفض سجل تنفيذ: '.$project->project_name,
                            'message' => 'تم رفض سجل التنفيذ (تنفيزي) للمشروع: '.$validated['rejection_reason'],
                            'page_name' => 'متابعة التنفيذ',
                            'action_url' => route('projects.execution', $project->id),
                            'icon' => 'fas fa-times-circle',
                            'causer_id' => auth()->id(),
                            'causer_name' => auth()->user()?->name,
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Notification error in rejectExecutiveExecution: '.$e->getMessage());
            }
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم رفض سجل التنفيذ',
                'status' => 'rejected',
            ]);
        }

        return redirect()->back()->with('success', 'تم رفض سجل التنفيذ');
    }

    /**
     * Display tracking page for all execution records
     */
    public function trackingIndex(Request $request)
    {
        if (! auth()->user()->hasAnyPermission(['execution.approve', 'execution.reject'])) {
            return redirect()->route('dashboard')->with('error', 'غير مصرح لك بالقيام بهذا الإجراء');
        }

        // Get filter parameters
        $status = $request->query('status', 'pending');
        $type = $request->query('type', 'all');
        $projectId = $request->query('project_id');
        $searchTerm = $request->query('search');

        // Base query for preliminary executions
        $preliminaryQuery = PreliminaryProcedureExecution::with([
            'project', 'procedure', 'createdBy', 'approvedBy', 'rejectedBy',
        ])->whereHas('project', fn ($q) => $this->projectService->applyAccessibleProjectsQuery($q, auth()->user()));

        // Base query for executive executions
        $executiveQuery = ProjectExecution::with([
            'project', 'action', 'createdBy', 'approvedBy', 'rejectedBy',
        ])->whereHas('project', fn ($q) => $this->projectService->applyAccessibleProjectsQuery($q, auth()->user()));

        // Filter by approval status
        if ($status !== 'all') {
            $preliminaryQuery->where('approval_status', $status);
            $executiveQuery->where('approval_status', $status);
        }

        // Filter by project
        if ($projectId) {
            $preliminaryQuery->where('project_id', $projectId);
            $executiveQuery->where('project_id', $projectId);
        }

        // Search by project name
        if ($searchTerm) {
            $preliminaryQuery->whereHas('project', function ($q) use ($searchTerm) {
                $q->where('project_name', 'like', "%{$searchTerm}%");
            });
            $executiveQuery->whereHas('project', function ($q) use ($searchTerm) {
                $q->where('project_name', 'like', "%{$searchTerm}%");
            });
        }

        // Get results
        $preliminaryExecutions = ($type === 'preliminary' || $type === 'all')
            ? $preliminaryQuery->orderBy('created_at', 'desc')->get()
            : collect();

        $executiveExecutions = ($type === 'executive' || $type === 'all')
            ? $executiveQuery->orderBy('created_at', 'desc')->get()
            : collect();

        // Get projects list for filter dropdown using accessible projects single source of truth
        $projects = $this->projectService->applyAccessibleProjectsQuery(
            Project::where('status', '!=', 'draft'),
            auth()->user()
        )->orderBy('project_name')->get(['id', 'project_name', 'form_number']);

        // Statistics
        $accessibleProjectIds = $this->projectService->applyAccessibleProjectsQuery(Project::query(), auth()->user())->pluck('id');
        $stats = [
            'pending' => PreliminaryProcedureExecution::whereIn('project_id', $accessibleProjectIds)->pending()->count() + ProjectExecution::whereIn('project_id', $accessibleProjectIds)->pending()->count(),
            'approved' => PreliminaryProcedureExecution::whereIn('project_id', $accessibleProjectIds)->approved()->count() + ProjectExecution::whereIn('project_id', $accessibleProjectIds)->approved()->count(),
            'rejected' => PreliminaryProcedureExecution::whereIn('project_id', $accessibleProjectIds)->rejected()->count() + ProjectExecution::whereIn('project_id', $accessibleProjectIds)->rejected()->count(),
        ];

        return view('projects.project_implementation.execution-tracking', compact(
            'preliminaryExecutions',
            'executiveExecutions',
            'projects',
            'stats',
            'status',
            'type',
            'projectId',
            'searchTerm'
        ));
    }
}

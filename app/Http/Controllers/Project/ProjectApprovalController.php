<?php

namespace App\Http\Controllers\Project;

use App\Enums\ReturnTarget;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Http\Requests\Approval\ApproveStepRequest;
use App\Http\Requests\Approval\CloseDraftRequest;
use App\Http\Requests\Approval\RecordConsultationRequest;
use App\Http\Requests\Approval\RejectStepRequest;
use App\Http\Requests\Approval\RequestCompletionRequest;
use App\Http\Requests\Approval\ResubmitProjectRequest;
use App\Models\Authority;
use App\Models\Domain;
use App\Models\ExecutiveActionCost;
use App\Models\ExecutiveActivity;
use App\Models\ExecutiveActivityAction;
use App\Models\InternalEntity;
use App\Models\Intervention;
use App\Models\PreliminaryActivity;
use App\Models\PreliminaryCost;
use App\Models\PreliminaryProcedure;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectActivityHistory;
use App\Models\ProjectApproval;
use App\Models\ProjectReferral;
use App\Models\Subdomain;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\EntityHierarchyService;
use App\Services\FrappeAPIService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProjectApprovalController extends Controller
{
    protected ApprovalService $approvalService;

    protected EntityHierarchyService $entityHierarchyService;

    protected ProjectService $projectService;

    protected FrappeAPIService $frappeService;

    public function __construct(
        ApprovalService $approvalService,
        EntityHierarchyService $entityHierarchyService,
        ProjectService $projectService,
        FrappeAPIService $frappeService
    ) {
        $this->approvalService = $approvalService;
        $this->entityHierarchyService = $entityHierarchyService;
        $this->projectService = $projectService;
        $this->frappeService = $frappeService;
    }

    /**
     * Display listing of projects in approval workflow
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Project::query()
            ->with(['createdBy', 'creatorEntity', 'currentApprovalStage', 'cost'])
            ->whereNotIn('status', ['draft', 'cancelled']);

        if (! $user->isAdmin()) {
            $userEntityId = (int) $user->entity_id;
            if ($userEntityId) {
                $allowedEntityIds = InternalEntity::getAllChildrenIds($userEntityId);
                $query->where(function ($q) use ($allowedEntityIds, $user) {
                    $q->whereIn('creator_entity_id', $allowedEntityIds)
                        ->orWhereHas('projectApprovals', function ($sq) use ($allowedEntityIds) {
                            $sq->whereIn('entity_id', $allowedEntityIds);
                        })
                        ->orWhere('created_by_user_id', $user->id);
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('form_number', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderBy('updated_at', 'desc')->paginate(15);

        return view('projects.approval.index', compact('projects'));
    }

    /**
     * Display approval show page
     */
    public function show(Project $project)
    {
        $project->load([
            'createdBy.entity',
            'creatorEntity',
            'cost',
            'activityHistory.user',
            'projectApprovals.entity',
            'projectApprovals.reviewedByUser',
            'financings.fundingSource',
        ]);

        $activeStep = $this->approvalService->getActiveStep($project);

        return view('projects.approval.show', compact('project', 'activeStep'));
    }

    /**
     * Close Draft and submit project for approval
     */
    public function submitForApproval(CloseDraftRequest $request, Project $project)
    {
        try {
            $user = auth()->user();
            $chain = $this->approvalService->closeDraftAndGenerateApprovalChain($project, $user);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إغلاق المسودة وتقديم المشروع للاعتماد بنجاح.',
                    'project' => $project->fresh(['creatorEntity']),
                    'current_stage' => $project->fresh()->current_stage,
                    'chain_count' => $chain->count(),
                ], 200);
            }

            return redirect()->route('projects.show', $project)->with('success', 'تم إغلاق المسودة وتقديم المشروع للاعتماد بنجاح.');
        } catch (\Exception $e) {
            Log::error('Close draft error', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إغلاق المسودة: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'حدث خطأ أثناء إغلاق المسودة: '.$e->getMessage());
        }
    }

    /**
     * Approve active step
     */
    public function approve(ApproveStepRequest $request, Project $project)
    {
        try {
            $user = auth()->user();
            $notes = $request->input('notes');
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'), $project);
            }

            $result = $this->approvalService->approveActiveStep($project, $user, $notes, $attachmentPath);
            $project->refresh();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'approval' => $result['approved_step'] ? $result['approved_step']->fresh(['entity', 'reviewedByUser']) : null,
                    'next_drop' => $result['next_step'] ? $result['next_step']->drop : null,
                    'project_status' => $result['project_status'],
                    'is_final' => $result['is_final'],
                ], 200);
            }

            return redirect()->route('approvals.show', $project)->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Approve step error', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء الاعتماد: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'حدث خطأ أثناء الاعتماد: '.$e->getMessage());
        }
    }

    /**
     * Reject active step
     */
    public function reject(RejectStepRequest $request, Project $project)
    {
        try {
            $user = auth()->user();
            $reason = $request->input('reason');
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'), $project);
            }

            $rejectedStep = $this->approvalService->rejectActiveStep($project, $user, $reason, $attachmentPath);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم رفض المشروع وإيقاف مسار الاعتمادات.',
                    'approval' => $rejectedStep->fresh(['entity', 'reviewedByUser']),
                    'returned_to_previous' => true,
                    'project_status' => 'rejected',
                ], 200);
            }

            return redirect()->route('approvals.show', $project)->with('success', 'تم رفض المشروع.');
        } catch (\Exception $e) {
            Log::error('Reject step error', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء الرفض: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'حدث خطأ أثناء الرفض: '.$e->getMessage());
        }
    }

    /**
     * Request completion (Roll back to creator entity or previous step)
     */
    public function requestAction(RequestCompletionRequest $request, Project $project)
    {
        try {
            $user = auth()->user();
            $reason = $request->input('reason');
            $target = ReturnTarget::tryFrom($request->input('return_target')) ?? ReturnTarget::CreatorEntity;
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'), $project);
            }

            $result = $this->approvalService->requestCompletion($project, $user, $reason, $target, $attachmentPath);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'approval' => $result['returned_step'] ? $result['returned_step']->fresh(['entity', 'reviewedByUser']) : null,
                    'target' => $result['target'],
                    'returned_to_previous' => true,
                    'project_status' => $result['project_status'],
                ], 200);
            }

            return redirect()->route('approvals.show', $project)->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Request action error', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء طلب الاستكمال: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'حدث خطأ أثناء طلب الاستكمال: '.$e->getMessage());
        }
    }

    /**
     * Resubmit project from rolled_back_for_review
     */
    public function resubmit(ResubmitProjectRequest $request, Project $project)
    {
        try {
            $user = auth()->user();
            $notes = $request->input('notes');
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'), $project);
            }

            $resubmittedStep = $this->approvalService->resubmitProject($project, $user, $notes, $attachmentPath);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تمت إعادة تقديم المشروع بنجاح بعد استكمال المطلوب.',
                    'approval' => $resubmittedStep->fresh(['entity', 'reviewedByUser']),
                    'project_status' => 'pending_approval',
                ], 200);
            }

            return redirect()->route('approvals.show', $project)->with('success', 'تمت إعادة تقديم المشروع بنجاح.');
        } catch (\Exception $e) {
            Log::error('Resubmit project error', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إعادة التقديم: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'حدث خطأ أثناء إعادة التقديم: '.$e->getMessage());
        }
    }

    /**
     * Record Consultation / Referral
     */
    public function submitReferral(RecordConsultationRequest $request, Project $project)
    {
        try {
            $user = auth()->user();
            $referredEntityId = (int) $request->input('referred_entity_id');
            $referredUserId = (int) $request->input('referred_user_id');
            $referralText = $request->input('referral_text');
            $attachments = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $attachments[] = $this->storeAttachment($file, $project);
                    }
                }
            }

            // Ensure there isn't already a pending referral to this entity
            $existing = ProjectReferral::where('project_id', $project->id)
                ->where('referred_entity_id', $referredEntityId)
                ->where('status', 'pending')
                ->exists();

            if ($existing) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'توجد استشارة قيد الانتظار لهذه الجهة.'], 422);
                }

                return back()->with('error', 'توجد استشارة قيد الانتظار لهذه الجهة.');
            }

            $referral = $this->approvalService->recordConsultation(
                $project,
                $user,
                $referredEntityId,
                $referralText,
                count($attachments) > 0 ? $attachments : null,
                $referredUserId
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الاستشارة/الإحالة بنجاح دون تغيير المرحلة النشطة.',
                    'referral' => $referral->fresh(['referringEntity', 'referredEntity', 'referringUser']),
                ], 201);
            }

            return redirect()->route('approvals.show', $project)->with('success', 'تم إرسال طلب الاستشارة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Submit referral error', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تسجيل الاستشارة: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'حدث خطأ أثناء تسجيل الاستشارة: '.$e->getMessage());
        }
    }

    /**
     * Update restricted/administrative fields during approval
     */
    public function updateRestrictedFields(Request $request, Project $project): JsonResponse
    {
        try {
            $project->update($request->only([
                'notes',
                'supervising_authority_id',
                'executing_entity_id',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح.',
                'project' => $project->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle project approval submission (Legacy Compatibility Wrapper)
     */
    public function approveProject(Request $request, Project $project): JsonResponse
    {
        $rules = [
            'drop' => 'nullable|string',
            'entity_id' => 'nullable|integer|exists:internal_entities,id',
            'status' => 'required|in:approved,need_action,rejected,resubmitted',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx,txt,csv|max:20480',
        ];

        if (in_array($request->status, ['need_action', 'rejected'])) {
            $rules['notes'] = 'required_without:reason|nullable|string|min:10';
            $rules['reason'] = 'required_without:notes|nullable|string|min:10';
        }

        $validator = Validator::make($request->all(), $rules, [
            'notes.required_without' => 'السبب إلزامي عند اختيار حالة "يتطلب إجراء" أو "مرفوض"',
            'reason.required_without' => 'السبب إلزامي عند اختيار حالة "يتطلب إجراء" أو "مرفوض"',
            'notes.min' => 'يجب أن يكون السبب 10 أحرف على الأقل',
            'reason.min' => 'يجب أن يكون السبب 10 أحرف على الأقل',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $status = $request->status;
            $notes = $request->input('notes') ?? $request->input('reason');
            $user = auth()->user();

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'), $project);
            }

            if ($status === 'approved') {
                $result = $this->approvalService->approveActiveStep($project, $user, $notes, $attachmentPath);
                $project->refresh();

                return response()->json([
                    'success' => true,
                    'message' => $result['message'] ?? 'تمت الموافقة على المشروع بنجاح والانتقال للمرحلة التالية.',
                    'approval' => $result['approved_step'] ? $result['approved_step']->fresh(['entity', 'reviewedByUser']) : null,
                    'next_drop' => $result['next_step'] ? $result['next_step']->drop : null,
                    'project_status' => $result['project_status'] ?? $project->status,
                ], 200);

            } elseif ($status === 'rejected') {
                $rejectedStep = $this->approvalService->rejectActiveStep($project, $user, $notes, $attachmentPath);

                return response()->json([
                    'success' => true,
                    'message' => 'تم رفض المشروع وإرجاعه إلى المرحلة السابقة.',
                    'approval' => $rejectedStep->fresh(['entity', 'reviewedByUser']),
                    'returned_to_previous' => true,
                ], 200);

            } elseif ($status === 'need_action') {
                $result = $this->approvalService->requestCompletion($project, $user, $notes, ReturnTarget::CreatorEntity, $attachmentPath);

                return response()->json([
                    'success' => true,
                    'message' => 'تم طلب إجراء على المشروع وإرجاعه إلى المرحلة السابقة.',
                    'approval' => $result['returned_step'] ? $result['returned_step']->fresh(['entity', 'reviewedByUser']) : null,
                    'returned_to_previous' => true,
                ], 200);

            } elseif ($status === 'resubmitted') {
                $resubmittedStep = $this->approvalService->resubmitProject($project, $user, $notes, $attachmentPath);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إعادة تقديم المشروع بنجاح.',
                    'approval' => $resubmittedStep->fresh(['entity', 'reviewedByUser']),
                ], 200);
            }

            return response()->json(['success' => false, 'message' => 'حالة غير مدعومة.'], 422);

        } catch (\Exception $e) {
            Log::error('Legacy approveProject error', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الموافقة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show financial review page
     */
    public function showFinancialReview(Project $project)
    {
        $project->load([
            'program',
            'domain',
            'subdomain',
            'mainObjectives',
            'specialObjectives',
            'supervisingAuthorities',
            'implementingEntities',
            'participatingEntities',
            'beneficiaryEntities',
            'financings.fundingSource',
            'cost',
            'preliminaryActivities.procedures.costs.financialItem',
            'executiveActivities.actions.costs.financialItem',
            'executiveActivities.actions.assignedEntities',
            'documents',
            'activityHistory.user',
            'currentApprovalStage',
        ]);

        $approval = ProjectApproval::with(['technicalReviewer', 'financialReviewer'])
            ->where('project_id', $project->id)
            ->where('status', 'financial_technical_review')
            ->latest()
            ->first() ?? ProjectApproval::with(['technicalReviewer', 'financialReviewer'])
            ->where('project_id', $project->id)
            ->latest()
            ->first();

        return view('projects.review.financial', compact('project', 'approval'));
    }

    /**
     * Show technical review page
     */
    public function showTechnicalReview(Project $project)
    {
        $project->load([
            'program',
            'domain',
            'subdomain',
            'mainObjectives',
            'specialObjectives',
            'supervisingAuthorities',
            'implementingEntities',
            'participatingEntities',
            'beneficiaryEntities',
            'financings.fundingSource',
            'cost',
            'preliminaryActivities.procedures.costs.financialItem',
            'executiveActivities.actions.costs.financialItem',
            'executiveActivities.actions.assignedEntities',
            'documents',
            'activityHistory.user',
            'currentApprovalStage',
        ]);

        $approval = ProjectApproval::with(['technicalReviewer', 'financialReviewer'])
            ->where('project_id', $project->id)
            ->where('status', 'financial_technical_review')
            ->latest()
            ->first() ?? ProjectApproval::with(['technicalReviewer', 'financialReviewer'])
            ->where('project_id', $project->id)
            ->latest()
            ->first();

        return view('projects.review.technical', compact('project', 'approval'));
    }

    /**
     * Submit financial review
     */
    public function submitFinancialReview(Request $request, Project $project)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'review_notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx,txt,csv|max:20480',
            'status' => 'nullable|in:approved,need_action,rejected',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            return back()->with('error', $validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $approval = ProjectApproval::where('project_id', $project->id)
                ->where('status', 'financial_technical_review')
                ->latest()
                ->first();

            $userId = auth()->id();
            $notes = $request->input('review_notes') ?? $request->input('notes');
            $status = $request->input('status', 'approved');
            $attachmentPath = null;

            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'), $project);
            }

            // Update preliminary costs if submitted (Financial reviewer can modify costs)
            if ($request->has('preliminary_costs') && is_array($request->preliminary_costs)) {
                foreach ($request->preliminary_costs as $costId => $data) {
                    $cost = PreliminaryCost::withoutGlobalScopes()->find($costId);
                    if ($cost) {
                        $qty = (float) ($data['quantity'] ?? $cost->quantity);
                        $price = (float) ($data['unit_price'] ?? $data['amount'] ?? $cost->amount ?? $cost->unit_price ?? 0);
                        $cost->update([
                            'quantity' => $qty,
                            'amount' => $price,
                            'total' => $qty * $price,
                        ]);
                    }
                }
            }

            // Update executive costs if submitted (Financial reviewer can modify costs)
            if ($request->has('executive_costs') && is_array($request->executive_costs)) {
                foreach ($request->executive_costs as $costId => $data) {
                    $cost = ExecutiveActionCost::withoutGlobalScopes()->find($costId);
                    if ($cost) {
                        $qty = (float) ($data['quantity'] ?? $cost->quantity);
                        $price = (float) ($data['unit_price'] ?? $data['amount'] ?? $cost->amount ?? $cost->unit_price ?? 0);
                        $cost->update([
                            'quantity' => $qty,
                            'amount' => $price,
                            'total' => $qty * $price,
                        ]);
                    }
                }
            }

            // Update weights during financial review if provided
            if ($request->has('preliminary_activities') && is_array($request->preliminary_activities)) {
                foreach ($request->preliminary_activities as $actId => $data) {
                    if (isset($data['weight'])) {
                        $activity = PreliminaryActivity::withoutGlobalScopes()->find($actId);
                        $activity?->update(['weight' => (float) $data['weight']]);
                    }
                }
            }

            if ($request->has('preliminary_procedures') && is_array($request->preliminary_procedures)) {
                foreach ($request->preliminary_procedures as $procId => $data) {
                    if (isset($data['weight'])) {
                        $procedure = PreliminaryProcedure::withoutGlobalScopes()->find($procId);
                        $procedure?->update(['weight' => (float) $data['weight']]);
                    }
                }
            }

            if ($request->has('executive_activities') && is_array($request->executive_activities)) {
                foreach ($request->executive_activities as $actId => $data) {
                    if (isset($data['weight'])) {
                        $activity = ExecutiveActivity::withoutGlobalScopes()->find($actId);
                        $activity?->update(['weight' => (float) $data['weight']]);
                    }
                }
            }

            if ($request->has('executive_actions') && is_array($request->executive_actions)) {
                foreach ($request->executive_actions as $actId => $data) {
                    if (isset($data['weight'])) {
                        $action = ExecutiveActivityAction::withoutGlobalScopes()->find($actId);
                        $action?->update(['weight' => (float) $data['weight']]);
                    }
                }
            }

            // Recalculate project total cost after financial review modifications
            $preliminaryTotal = (float) PreliminaryCost::withoutGlobalScopes()->where('project_id', $project->id)->sum('total');
            $executiveTotal = (float) ExecutiveActionCost::withoutGlobalScopes()->where('project_id', $project->id)->sum('total');
            $newTotalCost = $preliminaryTotal + $executiveTotal;
            if ($newTotalCost > 0) {
                if ($project->cost) {
                    $project->cost->update(['total_cost' => $newTotalCost]);
                } else {
                    ProjectCost::create([
                        'project_id' => $project->id,
                        'total_cost' => $newTotalCost,
                        'year_type' => 'gregorian',
                        'approval_date_hijri' => '1447-01-01',
                        'approval_year_gregorian' => 2026,
                    ]);
                }
            }

            if ($approval) {
                // Update financial review status
                $approval->update([
                    'financial_review_status' => $status,
                    'financial_reviewer_id' => $userId,
                    'financial_reviewed_at' => Carbon::now(),
                    'financial_notes' => $notes,
                    'financial_attachment' => $attachmentPath,
                ]);

                // Check if both reviews are completed
                $bothCompleted = $this->checkBothReviewsCompleted($approval);

                if ($bothCompleted) {
                    $overallStatus = $this->determineOverallReviewStatus($approval);

                    if ($overallStatus === 'approved') {
                        $this->progressToNextStage($approval);
                    } else {
                        $this->handleReviewRejection($approval, $overallStatus);
                    }
                }
            } else {
                $bothCompleted = false;
            }

            // Log activity
            $this->logActivity(
                $project->id,
                $userId,
                'financial_review',
                $status,
                $notes ?? 'طھظ…طھ ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظ…ط§ظ„ظٹط© ط¨ظ†ط¬ط§ط­',
                [
                    'approval_id' => $approval?->id,
                    'attachment' => $attachmentPath,
                ]
            );

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'طھظ… طھظ‚ط¯ظٹظ… ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظ…ط§ظ„ظٹط© ط¨ظ†ط¬ط§ط­.',
                    'both_completed' => $bothCompleted,
                    'overall_status' => ($approval && $bothCompleted) ? $this->determineOverallReviewStatus($approval) : null,
                ]);
            }

            return redirect()->route('projects.index')->with('success', 'طھظ… طھظ‚ط¯ظٹظ… ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظ…ط§ظ„ظٹط© ط¨ظ†ط¬ط§ط­.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Submit financial review error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، طھظ‚ط¯ظٹظ… ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظ…ط§ظ„ظٹط©: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، طھظ‚ط¯ظٹظ… ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظ…ط§ظ„ظٹط©: '.$e->getMessage());
        }
    }

    /**
     * Submit technical review
     */
    public function submitTechnicalReview(Request $request, Project $project)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'review_notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx,txt,csv|max:20480',
            'status' => 'nullable|in:approved,need_action,rejected',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            return back()->with('error', $validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $approval = ProjectApproval::where('project_id', $project->id)
                ->where('status', 'financial_technical_review')
                ->latest()
                ->first();

            $userId = auth()->id();
            $notes = $request->input('review_notes') ?? $request->input('notes');
            $status = $request->input('status', 'approved');
            $attachmentPath = null;

            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'), $project);
            }

            // Update preliminary activities (Technical reviewer can modify activities/procedures/weights)
            if ($request->has('preliminary_activities') && is_array($request->preliminary_activities)) {
                foreach ($request->preliminary_activities as $actId => $data) {
                    $activity = PreliminaryActivity::withoutGlobalScopes()->find($actId);
                    if ($activity) {
                        $updateData = [];
                        $name = $data['name'] ?? $data['activity'] ?? null;
                        if (! empty($name)) {
                            $updateData['name'] = $name;
                        }
                        if (isset($data['weight'])) {
                            $updateData['weight'] = (float) $data['weight'];
                        }
                        if (! empty($updateData)) {
                            $activity->update($updateData);
                        }
                    }
                }
            }

            // Update preliminary procedures (Technical reviewer can modify procedures, timelines and weights)
            if ($request->has('preliminary_procedures') && is_array($request->preliminary_procedures)) {
                foreach ($request->preliminary_procedures as $procId => $data) {
                    $procedure = PreliminaryProcedure::withoutGlobalScopes()->find($procId);
                    if ($procedure) {
                        $updateData = [];
                        $procName = $data['procedure_name'] ?? $data['procedure'] ?? null;
                        if (! empty($procName)) {
                            $updateData['procedure_name'] = $procName;
                        }
                        if (! empty($data['start_date'])) {
                            $updateData['start_date'] = $data['start_date'];
                        }
                        if (! empty($data['end_date'])) {
                            $updateData['end_date'] = $data['end_date'];
                        }
                        if (isset($data['weight'])) {
                            $updateData['weight'] = (float) $data['weight'];
                        }

                        if (! empty($updateData['start_date']) && ! empty($updateData['end_date'])) {
                            $start = Carbon::parse($updateData['start_date']);
                            $end = Carbon::parse($updateData['end_date']);
                            $updateData['duration_days'] = max(0, $end->diffInDays($start));
                        }
                        if (! empty($updateData)) {
                            $procedure->update($updateData);
                        }
                    }
                }
            }

            // Update executive activities (Technical reviewer can modify activities and weights)
            if ($request->has('executive_activities') && is_array($request->executive_activities)) {
                foreach ($request->executive_activities as $actId => $data) {
                    $activity = ExecutiveActivity::withoutGlobalScopes()->find($actId);
                    if ($activity) {
                        $updateData = [];
                        $actName = $data['name'] ?? $data['activity_name'] ?? null;
                        if (! empty($actName)) {
                            $updateData['name'] = $actName;
                        }
                        if (isset($data['weight'])) {
                            $updateData['weight'] = (float) $data['weight'];
                        }
                        if (! empty($updateData)) {
                            $activity->update($updateData);
                        }
                    }
                }
            }

            // Update executive actions (Technical reviewer can modify actions, timelines, and weights)
            if ($request->has('executive_actions') && is_array($request->executive_actions)) {
                foreach ($request->executive_actions as $actId => $data) {
                    $action = ExecutiveActivityAction::withoutGlobalScopes()->find($actId);
                    if ($action) {
                        $updateData = [];
                        $actionName = $data['action'] ?? $data['action_name'] ?? null;
                        if (! empty($actionName)) {
                            $updateData['action'] = $actionName;
                        }
                        if (! empty($data['start_date'])) {
                            $updateData['start_date'] = $data['start_date'];
                        }
                        if (! empty($data['end_date'])) {
                            $updateData['end_date'] = $data['end_date'];
                        }
                        if (isset($data['weight'])) {
                            $updateData['weight'] = (float) $data['weight'];
                        }
                        if (! empty($updateData)) {
                            $action->update($updateData);
                        }

                        if (isset($data['responsible_entity'])) {
                            $assigned = $action->assignedEntities()->first();
                            if ($assigned) {
                                $assigned->update(['entity_name' => $data['responsible_entity']]);
                            }
                        }
                    }
                }
            }
            // Note: Costs (preliminary_costs, executive_costs) are strictly NOT updated during technical review.

            if ($approval) {
                // Update technical review status
                $approval->update([
                    'technical_review_status' => $status,
                    'technical_reviewer_id' => $userId,
                    'technical_reviewed_at' => Carbon::now(),
                    'technical_notes' => $notes,
                    'technical_attachment' => $attachmentPath,
                ]);

                // Check if both reviews are completed
                $bothCompleted = $this->checkBothReviewsCompleted($approval);

                if ($bothCompleted) {
                    $overallStatus = $this->determineOverallReviewStatus($approval);

                    if ($overallStatus === 'approved') {
                        $this->progressToNextStage($approval);
                    } else {
                        $this->handleReviewRejection($approval, $overallStatus);
                    }
                }
            } else {
                $bothCompleted = false;
            }

            // Log activity
            $this->logActivity(
                $project->id,
                $userId,
                'technical_review',
                $status,
                $notes ?? 'طھظ…طھ ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظپظ†ظٹط© ط¨ظ†ط¬ط§ط­',
                [
                    'approval_id' => $approval?->id,
                    'attachment' => $attachmentPath,
                ]
            );

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'طھظ… طھظ‚ط¯ظٹظ… ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظپظ†ظٹط© ط¨ظ†ط¬ط§ط­.',
                    'both_completed' => $bothCompleted,
                    'overall_status' => ($approval && $bothCompleted) ? $this->determineOverallReviewStatus($approval) : null,
                ]);
            }

            return redirect()->route('projects.index')->with('success', 'طھظ… طھظ‚ط¯ظٹظ… ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظپظ†ظٹط© ط¨ظ†ط¬ط§ط­.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Submit technical review error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، طھظ‚ط¯ظٹظ… ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظپظ†ظٹط©: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، طھظ‚ط¯ظٹظ… ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظپظ†ظٹط©: '.$e->getMessage());
        }
    }

    /**
     * Check if both financial and technical reviews are completed
     */
    private function checkBothReviewsCompleted(ProjectApproval $approval): bool
    {
        return ! in_array($approval->financial_review_status, ['pending', null])
            && ! in_array($approval->technical_review_status, ['pending', null]);
    }

    /**
     * Determine overall status based on both reviews
     */
    private function determineOverallReviewStatus(ProjectApproval $approval): string
    {
        $financialStatus = $approval->financial_review_status;
        $technicalStatus = $approval->technical_review_status;

        // If any review is rejected, overall status is rejected
        if ($financialStatus === 'rejected' || $technicalStatus === 'rejected') {
            return 'rejected';
        }

        // If any review requires action, overall status is need_action
        if ($financialStatus === 'need_action' || $technicalStatus === 'need_action') {
            return 'need_action';
        }

        // Both are approved
        if ($financialStatus === 'approved' && $technicalStatus === 'approved') {
            return 'approved';
        }

        return 'pending';
    }

    /**
     * Handle review rejection or need_action
     */
    private function handleReviewRejection(ProjectApproval $approval, string $status): void
    {
        // Update approval status
        $approval->update([
            'status' => $status,
            'reviewed_at' => Carbon::now(),
        ]);

        // Return to previous stage if needed
        if (in_array($status, ['need_action', 'rejected'])) {
            $this->returnToPreviousStage($approval);
        }

        // Log activity
        $this->logActivity(
            $approval->project_id,
            auth()->id(),
            'review_completion',
            $status,
            'طھظ… ط§ظ„ط§ظ†طھظ‡ط§ط، ظ…ظ† ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظ…ط§ظ„ظٹط© ظˆط§ظ„ظپظ†ظٹط©',
            [
                'approval_id' => $approval->id,
                'financial_status' => $approval->financial_review_status,
                'technical_status' => $approval->technical_review_status,
            ]
        );
    }

    /**
     * Progress to next stage in the workflow
     */
    private function progressToNextStage(ProjectApproval $currentApproval): void
    {
        $projectId = $currentApproval->project_id;

        Log::info('progressToNextStage called', [
            'project_id' => $projectId,
            'current_approval_id' => $currentApproval->id,
            'current_drop' => $currentApproval->drop,
        ]);

        // Get the next stage based on the workflow configuration
        $project = Project::find($projectId);
        $nextStage = $this->getNextStage($currentApproval->drop, $project);

        Log::info('Next stage determined', [
            'next_stage' => $nextStage,
            'has_next_stage' => $nextStage !== null,
        ]);

        if ($nextStage) {
            // Update current approval as completed
            $currentApproval->update(['is_completed' => true]);

            // Get full approval chain once to retrieve correct entity_id and step_order
            $stages = $this->getProjectApprovalChain($project);
            $nextStageData = collect($stages)->firstWhere('code', $nextStage);

            $nextEntityId = $nextStageData['entity_id'] ?? null;
            $nextStepOrder = $nextStageData['order'] ?? 1;

            // If still null, try fallback extraction for legacy compatibility
            if ($nextEntityId === null && preg_match('/^entity_(\d+)/', $nextStage, $matches) === 1) {
                $nextEntityId = (int) $matches[1];
            }

            Log::info('Creating/updating next approval stage', [
                'next_stage' => $nextStage,
                'next_entity_id' => $nextEntityId,
                'next_step_order' => $nextStepOrder,
            ]);

            ProjectApproval::updateOrCreate(
                [
                    'project_id' => $projectId,
                    'drop' => $nextStage,
                ],
                [
                    'entity_id' => $nextEntityId,
                    'status' => 'pending',
                    'step_order' => $nextStepOrder,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );

            // Update project status and current stage
            Log::info('Updating project current_stage and current_stage_order', [
                'old_stage' => $project->current_stage,
                'new_stage' => $nextStage,
                'old_order' => $project->current_stage_order,
                'new_order' => $nextStepOrder,
            ]);

            Project::where('id', $projectId)->update([
                'current_stage' => $nextStage,
                'current_stage_order' => $nextStepOrder,
                'updated_at' => Carbon::now(),
            ]);

            Log::info('Stage progression completed successfully', [
                'project_id' => $projectId,
                'to_stage' => $nextStage,
                'entity_id' => $nextEntityId,
            ]);

            // Sync to Empowerment Department if needed
            try {
                $this->projectService->syncProjectToEmpowermentDepartment($project);
            } catch (\Exception $e) {
                Log::error('Failed to sync to empowerment on stage progression', ['error' => $e->getMessage()]);
            }
            // Log activity
            $this->logActivity(
                $projectId,
                auth()->id(),
                'stage_progression',
                'progressed',
                'طھظ… ط§ظ„ط§ظ†طھظ‚ط§ظ„ ط¥ظ„ظ‰ ط§ظ„ظ…ط±ط­ظ„ط© ط§ظ„طھط§ظ„ظٹط©',
                [
                    'from_stage' => $currentApproval->drop,
                    'to_stage' => $nextStage,
                    'entity_id' => $nextEntityId,
                ]
            );
        } else {
            Log::info('No next stage - all approvals done, transitioning project to in_execution');

            // Mark current approval as completed
            $currentApproval->update(['is_completed' => true]);

            // All approvals done: move project to in_execution
            $targetProject = Project::find($projectId);
            if ($targetProject) {
                $targetProject->update([
                    'status' => 'in_execution',
                    'current_stage' => null,
                    'current_stage_order' => null,
                ]);

                // =====================================================
                // ًں”¹ ظ…ط²ط§ظ…ظ†ط© ط¬ظ…ظٹط¹ ط§ظ„ط¬ظ‡ط§طھ ط§ظ„ظ…ط±طھط¨ط·ط© ط¨ط§ظ„ظ…ط´ط±ظˆط¹ ظ…ط¹ ERPNext
                // ظ‚ط¨ظ„ ط¥ط±ط³ط§ظ„ ط§ظ„ظ…ط´ط±ظˆط¹ ظ†ظپط³ظ‡
                // =====================================================
                $this->syncProjectEntities($targetProject);

                // =====================================================
                // ًں”¹ ط¥ط±ط³ط§ظ„ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط¥ظ„ظ‰ ERPNext ط¹ظ†ط¯ ط¯ط®ظˆظ„ ظ…ط±ط­ظ„ط© ط§ظ„طھظ†ظپظٹط°
                // =====================================================
                $this->createProjectInErpNext($targetProject);
            }

            // Log completion of approval cycle
            $this->logActivity(
                $projectId,
                auth()->id(),
                'project_completion',
                'in_execution',
                'ط§ظƒطھظ…ظ„طھ ط¬ظ…ظٹط¹ ظ…ط±ط§ط­ظ„ ط§ظ„ط§ط¹طھظ…ط§ط¯ â€” طھظ… طھط­ظˆظٹظ„ ط§ظ„ظ…ط´ط±ظˆط¹ ظ„ظ…ط±ط­ظ„ط© ط§ظ„طھظ†ظپظٹط°',
                [
                    'final_stage' => $currentApproval->drop,
                ]
            );

            // Sync empowerment/ERPNext on transition to execution (ط¥ظ† ظˆط¬ط¯)
            try {
                $this->projectService->syncProjectToEmpowermentDepartment($project);
            } catch (\Exception $e) {
                Log::error('Failed to sync empowerment on project in_execution transition', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * ظ…ط²ط§ظ…ظ†ط© ط¬ظ…ظٹط¹ ط§ظ„ط¬ظ‡ط§طھ ط§ظ„ظ…ط±طھط¨ط·ط© ط¨ط§ظ„ظ…ط´ط±ظˆط¹ ظ…ط¹ ERPNext
     * (ط§ظ„ط¨ط±ظ†ط§ظ…ط¬طŒ ط§ظ„ظ…ط¬ط§ظ„طŒ ط§ظ„ظ…ط¬ط§ظ„ ط§ظ„ظپط±ط¹ظٹطŒ ط§ظ„طھط¯ط®ظ„طŒ ط§ظ„ط³ظ„ط·ط§طھطŒ ط§ظ„ظƒظٹط§ظ†ط§طھ ط§ظ„ظ…ط´ط§ط±ظƒط©طŒ ط§ظ„ظ…ط³طھظپظٹط¯ط©طŒ ظ…طµط§ط¯ط± ط§ظ„طھظ…ظˆظٹظ„طŒ ...)
     */
    protected function syncProjectEntities(Project $project): void
    {
        // 1. ط§ظ„ط¨ط±ظ†ط§ظ…ط¬ (Program)
        if ($project->program_id) {
            $program = Program::find($project->program_id);
            if ($program) {
                $this->syncGenericEntity($program, 'Program', 'name');
            }
        }

        // 2. ط§ظ„ظ…ط¬ط§ظ„ (Domain)
        if ($project->domain_id) {
            $domain = Domain::find($project->domain_id);
            if ($domain) {
                $this->syncGenericEntity($domain, 'Domain', 'name');
            }
        }

        // 3. ط§ظ„ظ…ط¬ط§ظ„ ط§ظ„ظپط±ط¹ظٹ (Subdomain)
        if ($project->subdomain_id) {
            $subdomain = Subdomain::find($project->subdomain_id);
            if ($subdomain) {
                $this->syncGenericEntity($subdomain, 'Subdomain', 'name');
            }
        }

        // 4. ط§ظ„طھط¯ط®ظ„ (Intervention)
        if ($project->intervention_id) {
            $intervention = Intervention::find($project->intervention_id);
            if ($intervention) {
                $this->syncGenericEntity($intervention, 'Intervention', 'name');
            }
        }

        // 5. ط§ظ„ط³ظ„ط·ط§طھ ط§ظ„ظ…ط´ط±ظپط© (Supervising Authorities) - ط¹ظ„ط§ظ‚ط© many-to-many
        if (method_exists($project, 'supervisingAuthorities')) {
            foreach ($project->supervisingAuthorities as $authority) {
                try {
                    $this->frappeService->syncAuthority($authority);
                } catch (\Exception $e) {
                    Log::error('Error syncing supervising authority', [
                        'authority_id' => $authority->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // 6. ط§ظ„ظƒظٹط§ظ†ط§طھ ط§ظ„ظ…ظ†ظپط°ط© (Implementing Entities)
        if (method_exists($project, 'implementingEntities')) {
            foreach ($project->implementingEntities as $entity) {
                try {
                    $this->frappeService->syncParticipatingEntity($entity);
                } catch (\Exception $e) {
                    Log::error('Error syncing implementing entity', [
                        'entity_id' => $entity->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // 7. ط§ظ„ظƒظٹط§ظ†ط§طھ ط§ظ„ظ…ط´ط§ط±ظƒط© (Participating Entities)
        if (method_exists($project, 'participatingEntities')) {
            foreach ($project->participatingEntities as $entity) {
                try {
                    $this->frappeService->syncParticipatingEntity($entity);
                } catch (\Exception $e) {
                    Log::error('Error syncing participating entity', [
                        'entity_id' => $entity->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // 8. ط§ظ„ظƒظٹط§ظ†ط§طھ ط§ظ„ظ…ط³طھظپظٹط¯ط© (Beneficiary Entities)
        if (method_exists($project, 'beneficiaryEntities')) {
            foreach ($project->beneficiaryEntities as $entity) {
                try {
                    $this->frappeService->syncBeneficiaryEntity($entity);
                } catch (\Exception $e) {
                    Log::error('Error syncing beneficiary entity', [
                        'entity_id' => $entity->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // 9. ظ…طµط§ط¯ط± ط§ظ„طھظ…ظˆظٹظ„ (Funding Sources) ط¹ط¨ط± ط¹ظ„ط§ظ‚ط© ط§ظ„طھظ…ظˆظٹظ„ط§طھ
        if (method_exists($project, 'financings')) {
            foreach ($project->financings as $financing) {
                if ($financing->fundingSource) {
                    try {
                        $this->frappeService->syncFundingSource($financing->fundingSource);
                    } catch (\Exception $e) {
                        Log::error('Error syncing funding source', [
                            'source_id' => $financing->fundingSource->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // 10. ط£ظٹ ط¬ظ‡ط§طھ ط¯ط§ط®ظ„ظٹط© ط£ط®ط±ظ‰ ظ…ط±طھط¨ط·ط© ظ…ط¨ط§ط´ط±ط© (ط¥ط°ط§ ظˆط¬ط¯طھ)
        // ظ…ط«ط§ظ„: ط¥ط°ط§ ظƒط§ظ† ظ‡ظ†ط§ظƒ ط¹ظ„ط§ظ‚ط© project->internalEntity
        // ظٹظ…ظƒظ†ظƒ ط¥ط¶ط§ظپطھظ‡ط§ ظ‡ظ†ط§
    }

    /**
     * ط¯ط§ظ„ط© ط¹ط§ظ…ط© ظ„ظ…ط²ط§ظ…ظ†ط© ط£ظٹ ظ†ظ…ظˆط°ط¬ ظ…ط¹ ERPNext ظƒظƒظٹط§ظ†
     *
     * @param  mixed  $model
     */
    protected function syncGenericEntity($model, string $doctype, string $nameField): void
    {
        if (! $model) {
            return;
        }

        $name = $model->{$nameField} ?? $model->name ?? '';
        if (empty($name)) {
            Log::warning('Cannot sync entity: missing name field', ['model' => get_class($model), 'id' => $model->id]);

            return;
        }

        $data = [
            $nameField => $name,
            'entity_type' => $doctype,
            'entity_code' => (string) $model->id,
            'is_active' => 1,
        ];

        // ظٹظ…ظƒظ† ط¥ط¶ط§ظپط© ط­ظ‚ظˆظ„ ط¥ط¶ط§ظپظٹط© ط­ط³ط¨ ط§ظ„ط­ط§ط¬ط©
        if (property_exists($model, 'description') && $model->description) {
            $data['description'] = $model->description;
        }

        try {
            $result = $this->frappeService->getOrCreateEntity($doctype, $nameField, $data);
            if ($result) {
                Log::info('Entity synced to ERPNext', ['doctype' => $doctype, 'name' => $name]);
            } else {
                Log::error('Failed to sync entity to ERPNext', ['doctype' => $doctype, 'name' => $name]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while syncing entity', [
                'doctype' => $doctype,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ط¥ظ†ط´ط§ط،/ط¥ط±ط³ط§ظ„ ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط´ط±ظˆط¹ ط¥ظ„ظ‰ ERPNext ط¹ظ†ط¯ ط¯ط®ظˆظ„ظ‡ ظ…ط±ط­ظ„ط© ط§ظ„طھظ†ظپظٹط° (طھظ„ظ‚ط§ط¦ظٹط§ظ‹)
     */
    private function createProjectInErpNext(Project $project): void
    {
        // ظپظٹ ظ…ط±ط­ظ„ط© ط§ظ„طھظ†ظپظٹط° ظ†ط±ظٹط¯ ط¥ط±ط³ط§ظ„ ط§ظ„طھط­ط¯ظٹط« ط¥ظ„ظ‰ ERPNext (PUT) ط­طھظ‰ ظˆط¥ظ† طھظ… ط¥ط±ط³ط§ظ„ظ‡ ظ…ط³ط¨ظ‚ط§ظ‹ ظƒظ…ط³ظˆط¯ط©
        if (! empty($project->erpnext_project_id)) {
            Log::info('ط§ظ„ظ…ط´ط±ظˆط¹ ظٹظ…طھظ„ظƒ ظ…ط¹ط±ظپ ظپظٹ ERPNext ظ…ط³ط¨ظ‚ط§ظ‹طŒ ط³ظٹطھظ… طھط­ط¯ظٹط« ط¨ظٹط§ظ†ط§طھظ‡ ظ„ظ…ط±ط­ظ„ط© ط§ظ„طھظ†ظپظٹط°.', [
                'project_id' => $project->id,
                'erpnext_project_id' => $project->erpnext_project_id,
            ]);
        }

        // 3. ط§ظ„طھط­ظ‚ظ‚ ظ…ظ† ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط·ظ„ظˆط¨ط© ظ‚ط¨ظ„ طھظ†ظپظٹط° POST
        if (empty($project->project_name)) {
            $errorMsg = 'ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط´ط±ظˆط¹ ط؛ظٹط± ظ…ظƒطھظ…ظ„ط© (ط§ظ„ط§ط³ظ… ظ…ظپظ‚ظˆط¯). ظ„ظ… ظٹطھظ… ط§ظ„ط¥ط±ط³ط§ظ„ ط¥ظ„ظ‰ ERPNext.';
            Log::warning($errorMsg, ['project_id' => $project->id]);

            // 6. ط§ظ„ط§ط­طھظپط§ط¸ ط¨ط³ط¬ظ„ ط§ظ„ط®ط·ط£
            $project->update([
                'sync_status' => 'failed',
                'frappe_sync_status' => 'failed',
                'sync_error' => $errorMsg,
            ]);

            return;
        }

        try {
            // 1. ط§ط³طھط¯ط¹ط§ط، ط¯ط§ظ„ط© ط§ظ„ط¥ط±ط³ط§ظ„ (POST)
            $frappeResult = $this->frappeService->sendProjectOnExecution($project, true);

            $frappeProjectId = $frappeResult['data']['name'] ?? $frappeResult['name'] ?? null;

            // 5. ط­ظپط¸ ط­ط§ظ„ط© ط§ظ„ظ…ط²ط§ظ…ظ†ط© ظ„ظ„ظ†ط¬ط§ط­
            $project->update([
                'frappe_synced_at' => now(),
                'frappe_project_id' => $frappeProjectId,
                'frappe_project_name' => $frappeResult['data']['project_name'] ?? null,
                'frappe_sync_status' => 'success',
                'erpnext_project_id' => $frappeProjectId,
                'sync_status' => 'synced',
                'synced_to_erpnext_at' => now(),
                'execution_started_at' => now(),
                'sync_error' => null, // ظ…ط³ط­ ط§ظ„ط£ط®ط·ط§ط، ط§ظ„ط³ط§ط¨ظ‚ط© ط¥ظ† ظˆط¬ط¯طھ
            ]);

            // 4. طھط³ط¬ظٹظ„ ط±ط³ط§ظ„ط© ظ†ط¬ط§ط­ طھط­طھظˆظٹ ط¹ظ„ظ‰ ظ…ط¹ط±ظپ ط§ظ„ظ…ط´ط±ظˆط¹ ظˆط­ط§ظ„ط© ط§ظ„ط¥ط±ط³ط§ظ„
            Log::info('طھظ… ط¥ط±ط³ط§ظ„ ط§ظ„ظ…ط´ط±ظˆط¹ ط¥ظ„ظ‰ ERPNext ط¨ظ†ط¬ط§ط­', [
                'project_id' => $project->id,
                'frappe_project_id' => $frappeProjectId,
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            // 6. ط§ظ„ط§ط­طھظپط§ط¸ ط¨ط³ط¬ظ„ ط§ظ„ط®ط·ط£ ظ„ظ„ط³ظ…ط§ط­ ط¨ط§ظ„طھط´ط®ظٹطµ ظˆط¥ط¹ط§ط¯ط© ط§ظ„ظ…ط­ط§ظˆظ„ط© ظ„ط§ط­ظ‚ط§ظ‹
            $project->update([
                'frappe_sync_status' => 'failed',
                'sync_status' => 'failed',
                'sync_error' => substr($e->getMessage(), 0, 1000),
            ]);

            // 4. طھط³ط¬ظٹظ„ ط±ط³ط§ظ„ط© ط®ط·ط£ طھط­طھظˆظٹ ط¹ظ„ظ‰ ط³ط¨ط¨ ط§ظ„ظپط´ظ„ ظˆطھظپط§طµظٹظ„ ط§ظ„ط®ط·ط£
            Log::error('ظپط´ظ„ ط¥ط±ط³ط§ظ„ ط§ظ„ظ…ط´ط±ظˆط¹ ط¥ظ„ظ‰ ERPNext', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ]);
        }
    }

    /**
     * Return to previous stage
     */
    private function returnToPreviousStage(ProjectApproval $currentApproval): void
    {
        $projectId = $currentApproval->project_id;
        $project = Project::find($projectId);

        // Get the previous stage
        $previousStage = $this->getPreviousStage($currentApproval->drop, $project);

        if ($previousStage) {
            // Update current approval to indicate return
            $currentApproval->update([
                'returned_from_stage' => $currentApproval->drop,
                'returned_at' => Carbon::now(),
            ]);

            // Update previous approval stage to pending
            ProjectApproval::where('project_id', $projectId)
                ->where('drop', $previousStage)
                ->update([
                    'status' => 'pending',
                    'notes' => null,
                    'attachment' => null,
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                    'updated_at' => Carbon::now(),
                ]);

            // Update project status
            $prevStepOrder = $this->getStepOrder($previousStage, $project);
            Project::where('id', $projectId)->update([
                'current_stage' => $previousStage,
                'current_stage_order' => $prevStepOrder,
                'status' => 'rolled_back_for_review',
                'updated_at' => Carbon::now(),
            ]);

            // Log activity
            $this->logActivity(
                $projectId,
                auth()->id(),
                'stage_regression',
                'returned',
                'طھظ… ط¥ط±ط¬ط§ط¹ ط§ظ„ظ…ط´ط±ظˆط¹ ط¥ظ„ظ‰ ط§ظ„ظ…ط±ط­ظ„ط© ط§ظ„ط³ط§ط¨ظ‚ط©',
                [
                    'from_stage' => $currentApproval->drop,
                    'to_stage' => $previousStage,
                    'reason' => $currentApproval->notes,
                ]
            );
        }
    }

    /**
     * Store attachment file and return path
     */
    private function storeAttachment($file, Project $project): string
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueName = Str::slug($fileName).'_'.time().'.'.$extension;

        $path = $file->storeAs(
            "projects/{$project->id}/approvals",
            $uniqueName,
            'public'
        );

        return $path;
    }

    /**
     * Log activity to project_activity_history
     */
    private function logActivity(int $projectId, int $userId, string $actionType, string $status, ?string $notes = null, array $metadata = []): void
    {
        ProjectActivityHistory::create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'action_type' => $actionType,
            'status' => $status,
            'notes' => $notes,
            'metadata' => json_encode($metadata),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Get the full approval chain for a project
     */
    private function getProjectApprovalChain(Project $project): array
    {
        $projectService = app(ProjectService::class);
        $stages = $projectService->getApprovalStages($project);

        return collect($stages)->map(function ($stage) {
            return [
                'code' => $stage['drop'],
                'order' => $stage['drop_order'],
                'entity_id' => $stage['entity_id'],
                'name_ar' => $stage['stage_name'],
                'name_en' => $stage['stage_name_en'] ?? $stage['stage_name'],
            ];
        })->toArray();
    }

    /**
     * Get next stage in workflow
     */
    private function getNextStage(string $currentDrop, Project $project): ?string
    {
        Log::info('getNextStage called', [
            'currentDrop' => $currentDrop,
            'project_id' => $project->id,
            'project_current_stage' => $project->current_stage,
        ]);

        $stages = $this->getProjectApprovalChain($project);

        Log::info('Approval chain retrieved', [
            'total_stages' => count($stages),
            'stages' => $stages,
        ]);

        $currentIndex = -1;

        foreach ($stages as $index => $stage) {
            if ($stage['code'] === $currentDrop) {
                $currentIndex = $index;
                Log::info('Found current stage in chain', [
                    'index' => $index,
                    'stage_code' => $stage['code'],
                    'stage_name' => $stage['stage_name'] ?? 'N/A',
                ]);
                break;
            }
        }

        if ($currentIndex !== -1 && isset($stages[$currentIndex + 1])) {
            $nextStage = $stages[$currentIndex + 1]['code'];

            // If the next stage is implementation, it means all entity approvals are done.
            // Returning null will trigger the transition to 'in_execution'.
            if ($nextStage === 'implementation') {
                Log::info('Next stage is implementation â€” project ready for in_execution', [
                    'currentDrop' => $currentDrop,
                ]);

                return null;
            }

            Log::info('Next stage found', [
                'next_stage_index' => $currentIndex + 1,
                'next_stage_code' => $nextStage,
                'next_stage_name' => $stages[$currentIndex + 1]['name_ar'] ?? 'N/A',
            ]);

            return $nextStage;
        }

        // No next stage found â€” the project has completed all entity approvals.
        // Returning null triggers the in_execution transition in progressToNextStage.
        Log::info('No next stage in approval chain â€” project ready for in_execution', [
            'currentDrop' => $currentDrop,
            'currentIndex' => $currentIndex,
        ]);

        return null;
    }

    /**
     * Get previous stage in workflow
     */
    private function getPreviousStage(string $currentDrop, Project $project): ?string
    {
        $stages = $this->getProjectApprovalChain($project);
        $currentIndex = -1;

        foreach ($stages as $index => $stage) {
            if ($stage['code'] === $currentDrop) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex > 0) {
            return $stages[$currentIndex - 1]['code'];
        }

        // No previous stage found â€” this is already the first entity stage.
        return null;
    }

    /**
     * Get step order for a stage
     */
    private function getStepOrder(string $stageCode, Project $project): int
    {
        $stages = $this->getProjectApprovalChain($project);
        foreach ($stages as $stage) {
            if ($stage['code'] === $stageCode) {
                return $stage['order'];
            }
        }

        return 99;
    }

    /**
     * Get stage from drop value
     */
    private function getStageFromDrop(string $drop): string
    {
        if (str_starts_with($drop, 'entity_')) {
            $entityId = preg_match('/^entity_(\d+)(?:_(technical_review|financial_review|stage_approval))?$/', $drop, $matches) === 1
                ? (int) $matches[1]
                : null;
            $phase = $matches[2] ?? null;
            $entity = $entityId ? InternalEntity::find($entityId) : null;

            $phaseNames = [
                'technical_review' => 'مراجعة فنية',
                'financial_review' => 'مراجعة مالية',
                'stage_approval' => 'اعتماد للمرحلة',
            ];

            if ($entity && $phase && isset($phaseNames[$phase])) {
                return $entity->name.' - '.$phaseNames[$phase];
            }

            return $entity->name ?? $drop;
        }

        $stages = [
            'assembly' => 'ظ…ظˆط§ظپظ‚ط© ط§ظ„ط¬ظ…ط¹ظٹط©',
            'union' => 'ظ…ظˆط§ظپظ‚ط© ط§ظ„ط§طھط­ط§ط¯',
            'committee' => 'ظ…ظˆط§ظپظ‚ط© ط§ظ„ظ„ط¬ظ†ط©',
            'implementation' => 'ظ…ط±ط­ظ„ط© ط§ظ„طھظ†ظپظٹط°',
        ];

        return $stages[$drop] ?? $drop;
    }

    private function getUsersWithPermission(string $permission): Collection
    {
        // Update to correct permission slugs
        $map = [
            'review_financial' => 'approvals.financial-review',
            'review_technical' => 'approvals.technical-review',
        ];

        $slug = $map[$permission] ?? $permission;

        return User::whereHas('permissions', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->get();
    }

    /**
     * Find the stage that requested action
     */
    private function findActionRequiredStage(int $projectId): ?ProjectApproval
    {
        return ProjectApproval::where('project_id', $projectId)
            ->whereIn('status', ['need_action', 'rejected'])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get project movement log
     */
    public function getProjectMovementLog(Project $project): JsonResponse
    {
        try {
            $approvals = ProjectApproval::withTrashed()
                ->where('project_id', $project->id)
                ->with(['entity', 'reviewedByUser'])
                ->orderBy('created_at', 'desc')
                ->get();

            $log = $approvals->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'timestamp' => ($approval->reviewed_at ?? $approval->updated_at ?? $approval->created_at)->format('Y-m-d H:i:s'),
                    'stage' => $this->getStageFromDrop($approval->drop),
                    'authority' => $approval->entity->name ?? 'ط؛ظٹط± ظ…ط¹ط±ظˆظپ',
                    'reviewer' => $approval->reviewedByUser->name ?? 'ط؛ظٹط± ظ…ط¹ط±ظˆظپ',
                    'status' => $approval->status,
                    'status_arabic' => $this->getStatusArabic($approval->status),
                    'notes' => $approval->notes,
                    'attachment' => $approval->attachment ? [
                        'name' => basename($approval->attachment),
                        'url' => Storage::url($approval->attachment),
                        'size' => Storage::exists($approval->attachment) ?
                            round(Storage::size($approval->attachment) / 1024 / 1024, 2).' MB' : 'ط؛ظٹط± ظ…طھظˆظپط±',
                    ] : null,
                    'step_order' => $approval->step_order,
                    'is_return' => in_array($approval->status, ['need_action', 'needs_revision', 'rejected']),
                    'requires_return_to' => $approval->requires_return_to,
                ];
            });

            $approvals_history = [];
            $rejections_history = [];
            $required_actions_history = [];

            foreach ($log as $item) {
                if ($item['status'] === 'approved' || $item['status'] === 'pending') {
                    $approvals_history[] = $item;
                } elseif ($item['status'] === 'rejected') {
                    $rejections_history[] = $item;
                } elseif (in_array($item['status'], ['need_action', 'needs_revision', 'resubmit'])) {
                    $required_actions_history[] = $item;
                }
            }

            return response()->json([
                'success' => true,
                'project_id' => $project->id,
                'project_name' => $project->name,
                'movement_log' => $log,
                'approvals_history' => $approvals_history,
                'rejections_history' => $rejections_history,
                'required_actions_history' => $required_actions_history,
                'project_status' => $this->getCurrentProjectStatus($project),
                'total_movements' => count($log),
            ]);

        } catch (\Exception $e) {
            Log::error('Get project movement log error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، ط¬ظ„ط¨ ط³ط¬ظ„ ط­ط±ظƒط© ط§ظ„ظ…ط´ط±ظˆط¹: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current approval status for a project
     */
    public function getApprovalStatus(Project $project): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'status' => $this->getCurrentProjectStatus($project),
            ]);
        } catch (\Exception $e) {
            Log::error('Get approval status error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، ط¬ظ„ط¨ ط­ط§ظ„ط© ط§ظ„ظ…ظˆط§ظپظ‚ط©: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get financial data for the project
     */
    public function getFinancialData(Project $project): JsonResponse
    {
        try {
            $totalBudget = $project->total_budget ?? 0;
            $financings = $project->financings()->with(['fundingSource', 'authority'])->get();

            return response()->json([
                'success' => true,
                'total_budget' => $totalBudget,
                'financings' => $financings,
                'project_name' => $project->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Get financial data error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، ط¬ظ„ط¨ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط§ظ„ظٹط©: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get approval attachments for a project
     */
    public function getApprovalAttachments(Project $project): JsonResponse
    {
        try {
            $attachments = ProjectApproval::where('project_id', $project->id)
                ->whereNotNull('attachment')
                ->with(['entity', 'createdBy', 'reviewedByUser'])
                ->get()
                ->map(function ($approval) {
                    return [
                        'id' => $approval->id,
                        'name' => basename($approval->attachment),
                        'url' => Storage::url($approval->attachment),
                        'size' => Storage::exists($approval->attachment) ?
                            round(Storage::size($approval->attachment) / 1024 / 1024, 2).' MB' : 'غير متوفر',
                        'stage' => $this->getDropArabic($approval->drop),
                        'authority' => $approval->entity->name ?? 'غير معروف',
                        'status' => $approval->status,
                        'status_arabic' => $this->getStatusArabic($approval->status),
                        'reviewer_name' => $approval->reviewedByUser->name ?? $approval->createdBy->name ?? 'غير معروف',
                    ];
                });

            return response()->json([
                'success' => true,
                'attachments' => $attachments,
                'total_count' => $attachments->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Get approval attachments error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، ط¬ظ„ط¨ ط§ظ„ظ…ط±ظپظ‚ط§طھ.',
            ], 500);
        }
    }

    /**
     * Download approval attachment
     */
    public function downloadAttachment($projectId, $approvalId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $approval = ProjectApproval::where('project_id', $project->id)->findOrFail($approvalId);

            if (! $approval->attachment || ! Storage::exists($approval->attachment)) {
                abort(404, 'ط§ظ„ظ…ظ„ظپ ط؛ظٹط± ظ…ظˆط¬ظˆط¯.');
            }

            return Storage::download($approval->attachment);

        } catch (\Exception $e) {
            Log::error('Download attachment error', [
                'project_id' => $projectId,
                'approval_id' => $approvalId,
                'error' => $e->getMessage(),
            ]);

            abort(404, 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، طھط­ظ…ظٹظ„ ط§ظ„ظ…ظ„ظپ.');
        }
    }

    /**
     * View attachment directly in browser
     */
    public function viewAttachment($projectId, $approvalId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $approval = ProjectApproval::where('project_id', $project->id)->findOrFail($approvalId);

            if (! $approval->attachment || ! Storage::exists($approval->attachment)) {
                abort(404, 'ط§ظ„ظ…ظ„ظپ ط؛ظٹط± ظ…ظˆط¬ظˆط¯.');
            }

            return response()->file(Storage::path($approval->attachment));

        } catch (\Exception $e) {
            Log::error('View attachment error', [
                'project_id' => $projectId,
                'approval_id' => $approvalId,
                'error' => $e->getMessage(),
            ]);

            abort(404, 'ط§ظ„ظ…ظ„ظپ ط؛ظٹط± ظ…ظˆط¬ظˆط¯.');
        }
    }

    /**
     * Get file content for text-based files
     */
    public function getFileContent($projectId, $approvalId): JsonResponse
    {
        try {
            $project = Project::findOrFail($projectId);
            $approval = ProjectApproval::where('project_id', $project->id)->findOrFail($approvalId);

            if (! $approval->attachment || ! Storage::exists($approval->attachment)) {
                return response()->json(['success' => false, 'message' => 'ط§ظ„ظ…ظ„ظپ ط؛ظٹط± ظ…ظˆط¬ظˆط¯.'], 404);
            }

            $content = Storage::get($approval->attachment);

            return response()->json([
                'success' => true,
                'content' => $content,
                'file_name' => basename($approval->attachment),
                'file_size' => $this->formatFileSize(Storage::size($approval->attachment)),
            ]);

        } catch (\Exception $e) {
            Log::error('Get file content error', [
                'project_id' => $projectId,
                'approval_id' => $approvalId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'ط®ط·ط£ ظپظٹ ظ‚ط±ط§ط،ط© ظ…ظ„ظپ: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get detailed audit logs for project approvals
     */
    public function getApprovalAuditLog(Project $project): JsonResponse
    {
        try {
            $approvals = ProjectApproval::withTrashed()
                ->where('project_id', $project->id)
                ->with(['entity', 'createdBy', 'reviewedByUser'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($approval) {
                    return [
                        'timestamp' => ($approval->reviewed_at ?? $approval->updated_at ?? $approval->created_at)->format('Y-m-d H:i:s'),
                        'stage' => $this->getDropArabic($approval->drop),
                        'authority' => $approval->entity->name ?? 'غير معروف',
                        'reviewer' => $approval->reviewedByUser->name ?? $approval->createdBy->name ?? 'غير معروف',
                        'status' => $this->getStatusArabic($approval->status),
                        'notes' => $approval->notes,
                    ];
                });

            return response()->json([
                'success' => true,
                'audit_logs' => $approvals,
            ]);

        } catch (\Exception $e) {
            Log::error('Audit log error', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'ط­ط¯ط« ط®ط·ط£ ظپظٹ ط¬ظ„ط¨ ط³ط¬ظ„ ط§ظ„طھط¯ظ‚ظٹظ‚.'], 500);
        }
    }

    /**
     * Get pending approvals for current user's authority
     */
    public function getPendingApprovals(): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = ProjectApproval::where('status', 'pending')->with(['project', 'stage']);

            if ($user->isExternal()) {
                if (! $user->authority_id) {
                    return response()->json(['success' => true, 'pending_approvals' => [], 'count' => 0]);
                }
                $query->where('authority_id', $user->authority_id);
            } else {
                if (! $user->entity_id) {
                    return response()->json(['success' => true, 'pending_approvals' => [], 'count' => 0]);
                }
                $query->where('entity_id', $user->entity_id);
            }

            $pending = $query->get();

            return response()->json([
                'success' => true,
                'pending_approvals' => $pending,
                'count' => $pending->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Get pending approvals error', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'ط®ط·ط£ ظپظٹ ط¬ظ„ط¨ ط§ظ„ظ…ظˆط§ظپظ‚ط§طھ ط§ظ„ظ…ط¹ظ„ظ‚ط©.'], 500);
        }
    }

    /**
     * Reset approval stage to pending (admin only)
     */
    public function resetApprovalStage(Request $request, $projectId, $approvalId): JsonResponse
    {
        try {
            $approval = ProjectApproval::where('project_id', $projectId)->findOrFail($approvalId);
            $approval->update([
                'status' => 'pending',
                'reviewed_at' => null,
                'notes' => 'طھظ…طھ ط¥ط¹ط§ط¯ط© ط§ظ„ط¶ط¨ط· ط¨ظˆط§ط³ط·ط© ط§ظ„ظ…ط³ط¤ظˆظ„',
            ]);

            return response()->json(['success' => true, 'message' => 'طھظ… ط¥ط¹ط§ط¯ط© طھط¹ظٹظٹظ† ط§ظ„ظ…ط±ط­ظ„ط© ط¨ظ†ط¬ط§ط­.']);
        } catch (\Exception $e) {
            Log::error('Reset approval error', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'ظپط´ظ„طھ ط¹ظ…ظ„ظٹط© ط¥ط¹ط§ط¯ط© ط§ظ„طھط¹ظٹظٹظ†.'], 500);
        }
    }

    /**
     * Get approval timeline for a project
     */
    public function getApprovalTimeline(Project $project): JsonResponse
    {
        try {
            $approvals = ProjectApproval::withTrashed()
                ->where('project_id', $project->id)
                ->orderBy('step_order', 'asc')
                ->get()
                ->map(function ($a) {
                    return [
                        'stage' => $this->getDropArabic($a->drop),
                        'status' => $this->getStatusArabic($a->status),
                        'date' => ($a->reviewed_at ?? $a->created_at)->format('Y-m-d'),
                    ];
                });

            return response()->json(['success' => true, 'timeline' => $approvals]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ط®ط·ط£ ظپظٹ ط¬ظ„ط¨ ط§ظ„ظ…ط®ط·ط· ط§ظ„ط²ظ…ظ†ظٹ.'], 500);
        }
    }

    /**
     * Get approval statistics for the project
     */
    public function getApprovalStatistics($projectId): JsonResponse
    {
        try {
            $counts = ProjectApproval::where('project_id', $projectId)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total' => $counts->sum(),
                    'approved' => $counts['approved'] ?? 0,
                    'rejected' => $counts['rejected'] ?? 0,
                    'pending' => $counts['pending'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ط®ط·ط£ ظپظٹ ط¬ظ„ط¨ ط§ظ„ط¥ط­طµط§ط¦ظٹط§طھ.'], 500);
        }
    }

    /**
     * Get pending approvals for current project
     */
    public function getProjectPendingApprovals(Project $project): JsonResponse
    {
        try {
            $pending = ProjectApproval::where('project_id', $project->id)
                ->where('status', 'pending')
                ->with(['entity', 'createdBy', 'reviewedByUser'])
                ->get();

            return response()->json([
                'success' => true,
                'pending_approvals' => $pending,
                'count' => $pending->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'خطأ في جلب الموافقات المعلقة للمشروع.'], 500);
        }
    }

    /**
     * Get transactions requiring action for current project
     */
    public function getProjectTransactionsRequiringAction(Project $project): JsonResponse
    {
        try {
            $requiring = ProjectApproval::where('project_id', $project->id)
                ->whereIn('status', ['need_action', 'rejected'])
                ->with(['entity', 'createdBy', 'reviewedByUser'])
                ->get();

            return response()->json([
                'success' => true,
                'transactions' => $requiring,
                'count' => $requiring->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'خطأ في جلب المعاملات المطلوبة.'], 500);
        }
    }

    /**
     * Helper method: Format file size to human readable format
     */
    private function formatFileSize($bytes): string
    {
        if ($bytes == 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $base = log($bytes, 1024);
        $floor = floor($base);

        return round(pow(1024, $base - $floor), 2).' '.$units[$floor];
    }

    /**
     * Get current project status based on approvals
     */
    private function getCurrentProjectStatus(Project $project): array
    {
        $allStages = $this->getProjectApprovalChain($project);
        $totalStages = count($allStages);

        $approvals = ProjectApproval::where('project_id', $project->id)
            ->with(['entity', 'createdBy', 'reviewedByUser'])
            ->orderBy('step_order')
            ->get();

        $currentStage = $project->current_stage ?? ($allStages[0]['code'] ?? null);
        $completedStages = 0;
        $stageDetails = [];

        foreach ($approvals as $approval) {
            $stageDetails[$approval->drop] = [
                'status' => $approval->status,
                'status_arabic' => $this->getStatusArabic($approval->status),
                'reviewed_at' => $approval->reviewed_at?->format('Y-m-d H:i:s'),
                'reviewer' => $approval->reviewedByUser->name ?? $approval->createdBy->name ?? 'غير معروف',
                'financial_review_status' => $approval->financial_review_status,
                'technical_review_status' => $approval->technical_review_status,
            ];

            if ($approval->status === 'approved' || $approval->is_completed) {
                $completedStages++;
            }
        }

        $overallStatus = $project->status ?? 'pending';
        // In case currentStage is null, use the first stage
        $nextStage = $currentStage ? $this->getNextStage($currentStage, $project) : ($allStages[0]['code'] ?? null);

        return [
            'overall_status' => $overallStatus,
            'overall_status_arabic' => $this->getOverallStatusArabic($overallStatus),
            'current_stage' => $currentStage,
            'current_stage_arabic' => $currentStage ? $this->getDropArabic($currentStage) : 'ظ„ظ… ظٹط¨ط¯ط£',
            'completed_stages' => $completedStages,
            'total_stages' => $totalStages,
            'progress_percentage' => $totalStages > 0 ? round(($completedStages / $totalStages) * 100, 2) : 0,
            'stage_details' => $stageDetails,
            'next_stage' => $nextStage,
            'next_stage_arabic' => $nextStage ? $this->getDropArabic($nextStage) : 'ط§ظƒطھظ…ط§ظ„ ط§ظ„ظ…ظˆط§ظپظ‚ط©',
        ];
    }

    /**
     * Get drop in Arabic
     */
    private function getDropArabic(string $drop): string
    {
        if (str_starts_with($drop, 'entity_')) {
            $entityId = preg_match('/^entity_(\d+)(?:_(technical_review|financial_review|stage_approval))?$/', $drop, $matches) === 1
                ? (int) $matches[1]
                : null;
            $phase = $matches[2] ?? null;
            $entity = $entityId ? InternalEntity::find($entityId) : null;

            $phaseNames = [
                'technical_review' => 'مراجعة فنية',
                'financial_review' => 'مراجعة مالية',
                'stage_approval' => 'اعتماد للمرحلة',
            ];

            if ($entity && $phase && isset($phaseNames[$phase])) {
                return $entity->name.' - '.$phaseNames[$phase];
            }

            return $entity ? $entity->name : $drop;
        }

        return $drop;
    }

    /**
     * Get status in Arabic
     */
    private function getStatusArabic(string $status): string
    {
        $statuses = [
            'approved' => 'ظ…ظˆط§ظپظ‚',
            'rejected' => 'ظ…ط±ظپظˆط¶',
            'pending' => 'ظ‚ظٹط¯ ط§ظ„ط§ظ†طھط¸ط§ط±',
            'need_action' => 'ط¨ط­ط§ط¬ط© ط¥ظ„ظ‰ ط¥ط¬ط±ط§ط،',
            'resubmitted' => 'طھظ… ط¥ط¹ط§ط¯ط© طھظ‚ط¯ظٹظ…ظ‡',
            'financial_technical_review' => 'ظ…ط±ط§ط¬ط¹ط© ظ…ط§ظ„ظٹط© ظˆظپظ†ظٹط©',
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get overall status in Arabic
     */
    private function getOverallStatusArabic(string $status): string
    {
        $statuses = [
            'completed' => 'ظ…ظƒطھظ…ظ„',
            'rejected' => 'ظ…ط±ظپظˆط¶',
            'need_action' => 'ط¨ط­ط§ط¬ط© ط¥ظ„ظ‰ ط¥ط¬ط±ط§ط،',
            'rolled_back_for_review' => 'طھظ… ط¥ط±ط¬ط§ط¹ظ‡ ظ„ظ„ظ…ط±ط§ط¬ط¹ط©',
            'resubmitted' => 'طھظ… ط¥ط¹ط§ط¯ط© طھظ‚ط¯ظٹظ…ظ‡',
            'pending' => 'ظ‚ظٹط¯ ط§ظ„ط§ظ†طھط¸ط§ط±',
            'in_progress' => 'ظ‚ظٹط¯ ط§ظ„طھظ†ظپظٹط°',
            'financial_technical_review' => 'ظ‚ظٹط¯ ط§ظ„ظ…ط±ط§ط¬ط¹ط© ط§ظ„ظ…ط§ظ„ظٹط© ظˆط§ظ„ظپظ†ظٹط©',
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get approval stages for a project (for dynamic dropdown update)
     */
    public function getApprovalStages(Project $project): JsonResponse
    {
        try {
            // Get the service directly or use dependency injection
            $projectService = app(ProjectService::class);
            $approvalStages = $projectService->getApprovalStages($project);

            return response()->json([
                'success' => true,
                'approval_stages' => $approvalStages,
                'current_stage' => $project->current_stage,
            ]);
        } catch (\Exception $e) {
            Log::error('Get approval stages error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ط­ط¯ط« ط®ط·ط£ ط£ط«ظ†ط§ط، ط¬ظ„ط¨ ظ…ط±ط§ط­ظ„ ط§ظ„ظ…ظˆط§ظپظ‚ط©: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export activity history to CSV
     */
    public function exportActivityHistory(Project $project)
    {
        $activities = $project->activityHistory()->with('user')->orderBy('created_at', 'desc')->get();
        $filename = "project_{$project->id}_activity_log_".date('Y-m-d').'.csv';

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($activities) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['ط§ظ„طھط§ط±ظٹط® ظˆط§ظ„ظˆظ‚طھ', 'ط§ظ„ظ…ط³طھط®ط¯ظ…', 'ظ†ظˆط¹ ط§ظ„ط¥ط¬ط±ط§ط،', 'ط§ظ„ظ…ط±ط­ظ„ط© ط§ظ„ط³ط§ط¨ظ‚ط©', 'ط§ظ„ظ…ط±ط­ظ„ط© ط§ظ„ط­ط§ظ„ظٹط©', 'ط§ظ„ظ…ظ„ط§ط­ط¸ط§طھ']);

            foreach ($activities as $act) {
                fputcsv($file, [
                    $act->created_at ? $act->created_at->format('Y-m-d H:i:s') : '',
                    $act->user->name ?? 'ط§ظ„ظ†ط¸ط§ظ…',
                    $act->getActionDescription(),
                    $act->from_stage_name ?? 'ظ€',
                    $act->to_stage_name ?? 'ظ€',
                    $act->notes ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

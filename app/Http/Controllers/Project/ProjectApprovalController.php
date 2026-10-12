<?php

namespace App\Http\Controllers\Project;

use App\Enums\ProjectStatus;
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
use App\Models\ExecutiveActivity;
use App\Models\ExecutiveActivityAction;
use App\Models\InternalEntity;
use App\Models\Intervention;
use App\Models\PreliminaryActivity;
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
use App\Services\FinancialReview\FinancialReviewService;
use App\Services\FinancialReview\FinancialReviewWorkflowService;
use App\Services\FrappeAPIService;
use App\Services\OrganizationResolver;
use App\Traits\HandlesItemNotesAndHistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProjectApprovalController extends Controller
{
    use HandlesItemNotesAndHistory;

    protected ApprovalService $approvalService;

    protected EntityHierarchyService $entityHierarchyService;

    protected ProjectService $projectService;

    protected FrappeAPIService $frappeService;

    public function __construct(
        ApprovalService $approvalService,
        EntityHierarchyService $entityHierarchyService,
        ProjectService $projectService,
        FrappeAPIService $frappeService,
        private OrganizationResolver $organizationResolver,
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
            $organization = $this->organizationResolver->resolve($user);
            $allowedEntityIds = $user->getFilteredEntityIds();
            $authorityQuery = Authority::withoutGlobalScopes();
            $this->organizationResolver->applyVisibleAuthorities($authorityQuery, $user);
            $allowedAuthorityIds = $organization['type'] === OrganizationResolver::EXTERNAL
                ? $authorityQuery->pluck('id')->all()
                : [];

            $query->where(function ($q) use ($allowedAuthorityIds, $allowedEntityIds, $user) {
                if ($allowedEntityIds !== []) {
                    $q->whereIn('creator_entity_id', $allowedEntityIds)
                        ->orWhereHas('projectApprovals', function ($sq) use ($allowedEntityIds) {
                            $sq->whereIn('entity_id', $allowedEntityIds);
                        });
                }
                if ($allowedAuthorityIds !== []) {
                    $q->orWhereIn('authority_id', $allowedAuthorityIds)
                        ->orWhereHas('projectApprovals', function ($sq) use ($allowedAuthorityIds) {
                            $sq->whereIn('authority_id', $allowedAuthorityIds);
                        });
                }
                $q->orWhere('created_by_user_id', $user->id);
            });
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
                    'next_action_url' => $result['project_status'] === ProjectStatus::AwaitingDisbursementPlan->value
                        ? route('projects.execution', $project)
                        : null,
                ], 200);
            }

            if ($result['project_status'] === ProjectStatus::AwaitingDisbursementPlan->value) {
                return redirect()->route('projects.execution', $project)->with('success', $result['message']);
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

        $itemNotes = collect([]);
        $itemModifications = collect([]);
        if ($project->activityHistory) {
            $itemNotes = $project->activityHistory->filter(function ($history) {
                return str_contains($history->action_type ?? '', '_item_note');
            });
            $itemModifications = $project->activityHistory->filter(function ($history) {
                return str_contains($history->action_type ?? '', '_item_modification');
            });
        }

        return view('projects.review.financial', compact('project', 'approval', 'itemNotes', 'itemModifications'));
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

        $itemNotes = collect([]);
        $itemModifications = collect([]);
        if ($project->activityHistory) {
            $itemNotes = $project->activityHistory->filter(function ($history) {
                return str_contains($history->action_type ?? '', '_item_note');
            });
            $itemModifications = $project->activityHistory->filter(function ($history) {
                return str_contains($history->action_type ?? '', '_item_modification');
            });
        }

        return view('projects.review.technical', compact('project', 'approval', 'itemNotes', 'itemModifications'));
    }

    /**
     * Submit financial review
     */
    public function submitFinancialReview(
        Request $request,
        Project $project,
        FinancialReviewService $financialReviewService,
        FinancialReviewWorkflowService $financialReviewWorkflowService,
    ) {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'review_notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx,txt,csv|max:20480',
            'status' => 'nullable|in:approved,need_action,rejected',
        ]);

        $validator->after(function ($validator) use ($request): void {
            if (in_array($request->input('status'), ['need_action', 'rejected'], true)) {
                $notes = trim((string) ($request->input('review_notes') ?? $request->input('notes')));
                if (mb_strlen($notes) < 10) {
                    $validator->errors()->add('review_notes', 'ملاحظات الرفض أو الإرجاع يجب ألا تقل عن 10 أحرف.');
                }
            }
        });

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

            $approval = $financialReviewService->resolveActiveFinancialApproval($project);

            if (! $approval) {
                throw new ConflictHttpException('The project no longer has an active Financial Review assignment.');
            }

            $notes = $request->input('review_notes') ?? $request->input('notes');
            $status = $request->input('status', 'approved');
            $attachmentPath = null;

            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'), $project);
            }

            // Update preliminary and executive costs through shared FinancialReviewService (Strictly financial fields only)
            $financialReviewService->updateFinancialData(
                $project,
                $approval,
                $request->input('preliminary_costs', []),
                $request->input('executive_costs', []),
                'laravel_ui'
            );

            $this->processItemNotes($request, $project, 'financial_review', $approval);

            $workflowResult = match ($status) {
                'rejected' => $financialReviewWorkflowService->reject(
                    $project,
                    $request->user(),
                    $approval->id,
                    $notes ?? '',
                    $attachmentPath,
                    'laravel_ui',
                ),
                'need_action' => $financialReviewWorkflowService->return(
                    $project,
                    $request->user(),
                    $approval->id,
                    $notes ?? '',
                    ReturnTarget::CreatorEntity,
                    $attachmentPath,
                    'laravel_ui',
                ),
                default => $financialReviewWorkflowService->approve(
                    $project,
                    $request->user(),
                    $approval->id,
                    $notes,
                    $attachmentPath,
                    'laravel_ui',
                ),
            };

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $workflowResult['message'],
                    'data' => $workflowResult['data'],
                ]);
            }

            return redirect()->route('projects.index')->with('success', 'تم تقديم المراجعة المالية بنجاح.');

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
                    'message' => 'حدث خطأ أثناء تقديم المراجعة المالية: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'حدث خطأ أثناء تقديم المراجعة المالية: '.$e->getMessage());
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

            $approval = ProjectApproval::with(['entity', 'stage'])
                ->where('project_id', $project->id)
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
                    $activity = PreliminaryActivity::withoutGlobalScopes()
                        ->where('project_id', $project->id)
                        ->find($actId);
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
                            $activity->fill($updateData);
                            $this->logModelChangesIfDirty($activity, $project, 'technical_review', 'PreliminaryActivity', $approval);
                        }
                    }
                }
            }

            // Update preliminary procedures (Technical reviewer can modify procedures, timelines and weights)
            if ($request->has('preliminary_procedures') && is_array($request->preliminary_procedures)) {
                foreach ($request->preliminary_procedures as $procId => $data) {
                    $procedure = PreliminaryProcedure::withoutGlobalScopes()
                        ->where('project_id', $project->id)
                        ->find($procId);
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
                            $procedure->fill($updateData);
                            $this->logModelChangesIfDirty($procedure, $project, 'technical_review', 'PreliminaryProcedure', $approval);
                        }
                    }
                }
            }

            // Update executive activities (Technical reviewer can modify activities and weights)
            if ($request->has('executive_activities') && is_array($request->executive_activities)) {
                foreach ($request->executive_activities as $actId => $data) {
                    $activity = ExecutiveActivity::withoutGlobalScopes()
                        ->where('project_id', $project->id)
                        ->find($actId);
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
                            $activity->fill($updateData);
                            $this->logModelChangesIfDirty($activity, $project, 'technical_review', 'ExecutiveActivity', $approval);
                        }
                    }
                }
            }

            // Update executive actions (Technical reviewer can modify actions, timelines, and weights)
            if ($request->has('executive_actions') && is_array($request->executive_actions)) {
                foreach ($request->executive_actions as $actId => $data) {
                    $action = ExecutiveActivityAction::withoutGlobalScopes()
                        ->where(function ($query) use ($project) {
                            $query->where('project_id', $project->id)
                                ->orWhereIn('executive_activity_id', ExecutiveActivity::withoutGlobalScopes()
                                    ->where('project_id', $project->id)
                                    ->select('id'));
                        })
                        ->find($actId);
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
                            $action->fill($updateData);
                            $this->logModelChangesIfDirty($action, $project, 'technical_review', 'ExecutiveActivityAction', $approval);
                        }

                        if (isset($data['responsible_entity'])) {
                            $assigned = $action->assignedEntities()->first();
                            if ($assigned && $assigned->entity_name != $data['responsible_entity']) {
                                $oldVal = $assigned->entity_name;
                                $assigned->update(['entity_name' => $data['responsible_entity']]);

                                ProjectActivityHistory::create([
                                    'project_id' => $project->id,
                                    'user_id' => auth()->id(),
                                    'action_type' => 'technical_review_item_modification',
                                    'notes' => 'تم تعديل البيانات خلال المراجعة الفنية',
                                    'metadata' => [
                                        'related_model' => 'ProjectEntity',
                                        'related_id' => $assigned->id,
                                        'review_type' => 'technical_review',
                                        'changes' => [
                                            'entity_name' => [
                                                'old' => $oldVal,
                                                'new' => $data['responsible_entity'],
                                            ],
                                        ],
                                    ],
                                ]);
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

            $this->processItemNotes($request, $project, 'technical_review', $approval);

            // Log activity
            $this->logActivity(
                $project->id,
                $userId,
                'technical_review',
                $status,
                $notes ?? 'تمت المراجعة الفنية بنجاح',
                [
                    'approval_id' => $approval?->id,
                    'attachment' => $attachmentPath,
                ]
            );

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تقديم المراجعة الفنية بنجاح.',
                    'both_completed' => $bothCompleted,
                    'overall_status' => ($approval && $bothCompleted) ? $this->determineOverallReviewStatus($approval) : null,
                ]);
            }

            return redirect()->route('projects.index')->with('success', 'تم تقديم المراجعة الفنية بنجاح.');

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
                    'message' => 'حدث خطأ أثناء تقديم المراجعة الفنية: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'حدث خطأ أثناء تقديم المراجعة الفنية: '.$e->getMessage());
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
            'تم الانتهاء من المراجعة المالية والفنية',
            [
                'approval_id' => $approval->id,
                'financial_status' => $approval->financial_review_status,
                'technical_status' => $approval->technical_review_status,
            ]
        );
    }

    /**
     * Progress to next stage in the workflow
     *
     * ✅ يدعم الآن الحقول الجديدة is_final_stage و triggers_execution
     */
    private function progressToNextStage(ProjectApproval $currentApproval): void
    {
        $projectId = $currentApproval->project_id;

        Log::info('progressToNextStage called', [
            'project_id' => $projectId,
            'current_approval_id' => $currentApproval->id,
            'current_drop' => $currentApproval->drop,
            'is_final_stage' => $currentApproval->is_final_stage,
            'triggers_execution' => $currentApproval->triggers_execution,
        ]);

        $project = Project::find($projectId);
        if (! $project) {
            return;
        }

        // ✅ إذا كانت المرحلة الحالية نهائية → انتقل للتنفيذ مباشرة
        if ($currentApproval->is_final_stage || $currentApproval->triggers_execution) {
            Log::info('Current approval is FINAL stage — transitioning to execution');

            $currentApproval->update(['is_completed' => true]);

            $project->update([
                'status' => 'in_execution',
                'current_stage' => null,
                'current_stage_order' => null,
                'completed_at' => Carbon::now(),
            ]);

            try {
                $this->syncProjectEntities($project);
            } catch (\Exception $e) {
                Log::error('Failed to sync entities to ERPNext on stage completion', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->createProjectInErpNext($project);

            $this->logActivity(
                $projectId,
                auth()->id(),
                'project_completion',
                'in_execution',
                'اكتملت جميع مراحل الاعتماد — تم تحويل المشروع لمرحلة التنفيذ',
                ['final_stage' => $currentApproval->drop]
            );

            return;
        }

        // ❌ إذا لم تكن نهائية → ابحث عن المرحلة التالية باستخدام step_order
        $nextStep = ProjectApproval::where('project_id', $projectId)
            ->whereNull('workflow_request_id')
            ->whereNull('unified_request_id')
            ->where('step_order', '>', $currentApproval->step_order)
            ->orderBy('step_order')
            ->first();

        if ($nextStep) {
            $currentApproval->update(['is_completed' => true]);

            $nextStep->update([
                'status' => 'pending',
                'is_active' => true,
            ]);

            $project->update([
                'current_stage' => $nextStep->drop,
                'current_stage_order' => $nextStep->step_order,
                'status' => ProjectStatus::PendingApproval->value,
            ]);

            Log::info('Stage progression completed successfully', [
                'project_id' => $projectId,
                'to_stage' => $nextStep->drop,
                'next_step_order' => $nextStep->step_order,
            ]);

            try {
                $this->projectService->syncProjectToEmpowermentDepartment($project);
            } catch (\Exception $e) {
                Log::error('Failed to sync to empowerment on stage progression', ['error' => $e->getMessage()]);
            }

            $this->logActivity(
                $projectId,
                auth()->id(),
                'stage_progression',
                'progressed',
                'تم الانتقال إلى المرحلة التالية',
                [
                    'from_stage' => $currentApproval->drop,
                    'to_stage' => $nextStep->drop,
                    'entity_id' => $nextStep->entity_id,
                ]
            );

            return;
        }

        // Legacy fallback: استخدام getNextStage القديم للبيانات التي ليس لها step_order صحيح
        Log::info('No next step by step_order — falling back to legacy chain lookup');

        $nextStage = $this->getNextStage($currentApproval->drop, $project);

        if ($nextStage) {
            $currentApproval->update(['is_completed' => true]);

            $stages = $this->getProjectApprovalChain($project);
            $nextStageData = collect($stages)->firstWhere('code', $nextStage);

            $nextEntityId = $nextStageData['entity_id'] ?? null;
            $nextStepOrder = $nextStageData['order'] ?? 1;

            if ($nextEntityId === null && preg_match('/^entity_(\d+)/', $nextStage, $matches) === 1) {
                $nextEntityId = (int) $matches[1];
            }

            ProjectApproval::updateOrCreate(
                [
                    'project_id' => $projectId,
                    'drop' => $nextStage,
                ],
                [
                    'entity_id' => $nextEntityId,
                    'status' => 'pending',
                    'is_active' => true,
                    'step_order' => $nextStepOrder,
                    'updated_at' => Carbon::now(),
                ]
            );

            Project::where('id', $projectId)->update([
                'current_stage' => $nextStage,
                'current_stage_order' => $nextStepOrder,
                'updated_at' => Carbon::now(),
            ]);

            $this->logActivity(
                $projectId,
                auth()->id(),
                'stage_progression',
                'progressed',
                'تم الانتقال إلى المرحلة التالية',
                [
                    'from_stage' => $currentApproval->drop,
                    'to_stage' => $nextStage,
                    'entity_id' => $nextEntityId,
                ]
            );

            return;
        }

        // لا توجد مرحلة تالية → دخول التنفيذ
        Log::info('No next stage at all — transitioning to in_execution');

        $currentApproval->update(['is_completed' => true]);

        $project->update([
            'status' => 'in_execution',
            'current_stage' => null,
            'current_stage_order' => null,
            'completed_at' => Carbon::now(),
        ]);

        try {
            $this->syncProjectEntities($project);
        } catch (\Exception $e) {
            Log::error('Failed to sync entities to ERPNext on stage completion', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->createProjectInErpNext($project);

        $this->logActivity(
            $projectId,
            auth()->id(),
            'project_completion',
            'in_execution',
            'اكتملت جميع مراحل الاعتماد — تم تحويل المشروع لمرحلة التنفيذ',
            ['final_stage' => $currentApproval->drop]
        );
    }

    /**
     * مزامنة جميع الجهات المرتبطة بالمشروع مع ERPNext
     * (البرنامج، المجال، المجال الفرعي، التدخل، السلطات، الكيانات المشاركة، المستفيدة، مصادر التمويل، ...)
     */
    protected function syncProjectEntities(Project $project): void
    {
        // 1. البرنامج (Program)
        if ($project->program_id) {
            $program = Program::find($project->program_id);
            if ($program) {
                $this->syncGenericEntity($program, 'Program', 'name');
            }
        }

        // 2. المجال (Domain)
        if ($project->domain_id) {
            $domain = Domain::find($project->domain_id);
            if ($domain) {
                $this->syncGenericEntity($domain, 'Domain', 'name');
            }
        }

        // 3. المجال الفرعي (Subdomain)
        if ($project->subdomain_id) {
            $subdomain = Subdomain::find($project->subdomain_id);
            if ($subdomain) {
                $this->syncGenericEntity($subdomain, 'Subdomain', 'name');
            }
        }

        // 4. التدخل (Intervention)
        if ($project->intervention_id) {
            $intervention = Intervention::find($project->intervention_id);
            if ($intervention) {
                $this->syncGenericEntity($intervention, 'Intervention', 'name');
            }
        }

        // 5. السلطات المشرفة (Supervising Authorities) - علاقة many-to-many
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

        // 6. الكيانات المنفذة (Implementing Entities)
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

        // 7. الكيانات المشاركة (Participating Entities)
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

        // 8. الكيانات المستفيدة (Beneficiary Entities)
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

        // 9. مصادر التمويل (Funding Sources) عبر علاقة التمويلات
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
    }

    /**
     * دالة عامة لمزامنة أي نموذج مع ERPNext ككيان
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
     * إنشاء/إرسال بيانات المشروع إلى ERPNext عند دخوله مرحلة التنفيذ (تلقائياً)
     */
    private function createProjectInErpNext(Project $project): void
    {
        if (! empty($project->erpnext_project_id)) {
            Log::info('المشروع يمتلك معرف في ERPNext مسبقاً، سيتم تحديث بياناته لمرحلة التنفيذ.', [
                'project_id' => $project->id,
                'erpnext_project_id' => $project->erpnext_project_id,
            ]);
        }

        if (empty($project->project_name)) {
            $errorMsg = 'بيانات المشروع غير مكتملة (الاسم مفقود). لم يتم الإرسال إلى ERPNext.';
            Log::warning($errorMsg, ['project_id' => $project->id]);

            $project->update([
                'sync_status' => 'failed',
                'frappe_sync_status' => 'failed',
                'sync_error' => $errorMsg,
            ]);

            return;
        }

        try {
            $frappeResult = $this->frappeService->sendProjectOnExecution($project, true);

            $frappeProjectId = $frappeResult['data']['name'] ?? $frappeResult['name'] ?? null;

            $project->update([
                'frappe_synced_at' => now(),
                'frappe_project_id' => $frappeProjectId,
                'frappe_project_name' => $frappeResult['data']['project_name'] ?? null,
                'frappe_sync_status' => 'success',
                'erpnext_project_id' => $frappeProjectId,
                'sync_status' => 'synced',
                'synced_to_erpnext_at' => now(),
                'execution_started_at' => now(),
                'sync_error' => null,
            ]);

            Log::info('تم إرسال المشروع إلى ERPNext بنجاح', [
                'project_id' => $project->id,
                'frappe_project_id' => $frappeProjectId,
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            $project->update([
                'frappe_sync_status' => 'failed',
                'sync_status' => 'failed',
                'sync_error' => substr($e->getMessage(), 0, 1000),
            ]);

            Log::error('فشل إرسال المشروع إلى ERPNext', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ]);
        }
    }

    /**
     * Return to previous stage
     *
     * ✅ يدعم الآن step_order بدلاً من الاعتماد على drop فقط
     */
    private function returnToPreviousStage(ProjectApproval $currentApproval): void
    {
        $projectId = $currentApproval->project_id;
        $project = Project::find($projectId);

        if (! $project) {
            return;
        }

        // ابحث عن المرحلة السابقة باستخدام step_order
        $previousStep = ProjectApproval::where('project_id', $projectId)
            ->whereNull('workflow_request_id')
            ->whereNull('unified_request_id')
            ->where('step_order', '<', $currentApproval->step_order)
            ->orderByDesc('step_order')
            ->first();

        // Legacy fallback
        if (! $previousStep) {
            $previousStage = $this->getPreviousStage($currentApproval->drop, $project);
            if ($previousStage) {
                $previousStep = ProjectApproval::where('project_id', $projectId)
                    ->where('drop', $previousStage)
                    ->first();
            }
        }

        if ($previousStep) {
            $currentApproval->update([
                'returned_from_stage' => $currentApproval->drop,
                'returned_at' => Carbon::now(),
                'is_active' => false,
            ]);

            $previousStep->update([
                'status' => 'pending',
                'is_active' => true,
                'is_completed' => false,
                'notes' => null,
                'attachment' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]);

            $project->update([
                'current_stage' => $previousStep->drop,
                'current_stage_order' => $previousStep->step_order,
                'status' => 'rolled_back_for_review',
            ]);

            $this->logActivity(
                $projectId,
                auth()->id(),
                'stage_regression',
                'returned',
                'تم إرجاع المشروع إلى المرحلة السابقة',
                [
                    'from_stage' => $currentApproval->drop,
                    'to_stage' => $previousStep->drop,
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
        $stages = $this->getProjectApprovalChain($project);
        $currentIndex = -1;

        foreach ($stages as $index => $stage) {
            if ($stage['code'] === $currentDrop) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex !== -1 && isset($stages[$currentIndex + 1])) {
            $nextStage = $stages[$currentIndex + 1]['code'];

            if ($nextStage === 'implementation') {
                return null;
            }

            return $nextStage;
        }

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
            'assembly' => 'موافقة الجمعية',
            'union' => 'موافقة الاتحاد',
            'committee' => 'موافقة اللجنة',
            'implementation' => 'مرحلة التنفيذ',
        ];

        return $stages[$drop] ?? $drop;
    }

    private function getUsersWithPermission(string $permission): Collection
    {
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
                    'authority' => $approval->entity->name ?? 'غير معروف',
                    'reviewer' => $approval->reviewedByUser->name ?? 'غير معروف',
                    'status' => $approval->status,
                    'status_arabic' => $this->getStatusArabic($approval->status),
                    'notes' => $approval->notes,
                    'attachment' => $approval->attachment ? [
                        'name' => basename($approval->attachment),
                        'url' => Storage::url($approval->attachment),
                        'size' => Storage::exists($approval->attachment) ?
                            round(Storage::size($approval->attachment) / 1024 / 1024, 2).' MB' : 'غير متوفر',
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
                'message' => 'حدث خطأ أثناء جلب سجل حركة المشروع: '.$e->getMessage(),
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
                'message' => 'حدث خطأ أثناء جلب حالة الموافقة: '.$e->getMessage(),
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
                'message' => 'حدث خطأ أثناء جلب البيانات المالية: '.$e->getMessage(),
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
                'message' => 'حدث خطأ أثناء جلب المرفقات.',
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
                abort(404, 'الملف غير موجود.');
            }

            return Storage::download($approval->attachment);

        } catch (\Exception $e) {
            Log::error('Download attachment error', [
                'project_id' => $projectId,
                'approval_id' => $approvalId,
                'error' => $e->getMessage(),
            ]);

            abort(404, 'حدث خطأ أثناء تحميل الملف.');
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
                abort(404, 'الملف غير موجود.');
            }

            return response()->file(Storage::path($approval->attachment));

        } catch (\Exception $e) {
            Log::error('View attachment error', [
                'project_id' => $projectId,
                'approval_id' => $approvalId,
                'error' => $e->getMessage(),
            ]);

            abort(404, 'الملف غير موجود.');
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
                return response()->json(['success' => false, 'message' => 'الملف غير موجود.'], 404);
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

            return response()->json(['success' => false, 'message' => 'خطأ في قراءة ملف: '.$e->getMessage()], 500);
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

            return response()->json(['success' => false, 'message' => 'حدث خطأ في جلب سجل التدقيق.'], 500);
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

            return response()->json(['success' => false, 'message' => 'خطأ في جلب الموافقات المعلقة.'], 500);
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
                'notes' => 'تمت إعادة الضبط بواسطة المسؤول',
            ]);

            return response()->json(['success' => true, 'message' => 'تم إعادة تعيين المرحلة بنجاح.']);
        } catch (\Exception $e) {
            Log::error('Reset approval error', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'فشلت عملية إعادة التعيين.'], 500);
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
            return response()->json(['success' => false, 'message' => 'خطأ في جلب المخطط الزمني.'], 500);
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
            return response()->json(['success' => false, 'message' => 'خطأ في جلب الإحصائيات.'], 500);
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
        $nextStage = $currentStage ? $this->getNextStage($currentStage, $project) : ($allStages[0]['code'] ?? null);

        return [
            'overall_status' => $overallStatus,
            'overall_status_arabic' => $this->getOverallStatusArabic($overallStatus),
            'current_stage' => $currentStage,
            'current_stage_arabic' => $currentStage ? $this->getDropArabic($currentStage) : 'لم يبدأ',
            'completed_stages' => $completedStages,
            'total_stages' => $totalStages,
            'progress_percentage' => $totalStages > 0 ? round(($completedStages / $totalStages) * 100, 2) : 0,
            'stage_details' => $stageDetails,
            'next_stage' => $nextStage,
            'next_stage_arabic' => $nextStage ? $this->getDropArabic($nextStage) : 'اكتمال الموافقة',
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
            'approved' => 'موافق',
            'rejected' => 'مرفوض',
            'pending' => 'قيد الانتظار',
            'need_action' => 'بحاجة إلى إجراء',
            'resubmitted' => 'تم إعادة تقديمه',
            'financial_technical_review' => 'مراجعة مالية وفنية',
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get overall status in Arabic
     */
    private function getOverallStatusArabic(string $status): string
    {
        $statuses = [
            'completed' => 'مكتمل',
            'rejected' => 'مرفوض',
            'need_action' => 'بحاجة إلى إجراء',
            'rolled_back_for_review' => 'تم إرجاعه للمراجعة',
            'resubmitted' => 'تم إعادة تقديمه',
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'financial_technical_review' => 'قيد المراجعة المالية والفنية',
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get approval stages for a project (for dynamic dropdown update)
     */
    public function getApprovalStages(Project $project): JsonResponse
    {
        try {
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
                'message' => 'حدث خطأ أثناء جلب مراحل الموافقة: '.$e->getMessage(),
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
            fputcsv($file, ['التاريخ والوقت', 'المستخدم', 'نوع الإجراء', 'المرحلة السابقة', 'المرحلة الحالية', 'الملاحظات']);

            foreach ($activities as $act) {
                fputcsv($file, [
                    $act->created_at ? $act->created_at->format('Y-m-d H:i:s') : '',
                    $act->user->name ?? 'النظام',
                    $act->getActionDescription(),
                    $act->from_stage_name ?? '—',
                    $act->to_stage_name ?? '—',
                    $act->notes ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

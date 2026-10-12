<?php

namespace App\Services;

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\PreliminaryProcedureExecution;
use App\Models\Project;
use App\Models\ProjectActivityHistory;
use App\Models\ProjectApproval;
use App\Models\ProjectDisbursementPlanDetail;
use App\Models\ProjectExecution;
use App\Models\ProjectInstallmentPaymentObligation;
use App\Models\ProjectUnifiedRequest;
use App\Models\ProjectWorkflowRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectUnifiedRequestService
{
    public function __construct(
        protected OrganizationResolver $organizationResolver,
        protected ProjectFinancialWorkflowService $financialWorkflowService,
        protected ProjectWorkflowRequestService $workflowRequestService,
        protected ApprovalService $approvalService,
        protected ApprovalChainBuilder $approvalChainBuilder,
        protected ?FrappeAPIService $frappeAPIService = null
    ) {
        $this->frappeAPIService = $frappeAPIService ?? app(FrappeAPIService::class);
    }

    /**
     * Generate an authoritative unique request number with concurrency protection.
     * Standard Format: REYYYY#### (e.g. RE20260001)
     */
    public function generateRequestNumber(string $type, int $projectId): string
    {
        $year = date('Y');
        $prefix = "RE{$year}";
        $expectedLength = strlen($prefix) + 4; // 2 + 4 + 4 = 10 chars

        $lastRequest = ProjectUnifiedRequest::withoutGlobalScopes()
            ->where('request_number', 'like', "{$prefix}%")
            ->whereRaw('LENGTH(request_number) = ?', [$expectedLength])
            ->orderByRaw('CAST(SUBSTR(request_number, -4) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        $nextSequence = 1;
        if ($lastRequest) {
            $lastNum = (int) substr($lastRequest->request_number, -4);
            $nextSequence = $lastNum + 1;
        }

        do {
            $number = sprintf('RE%s%04d', $year, $nextSequence++);
        } while (ProjectUnifiedRequest::withoutGlobalScopes()->where('request_number', $number)->exists());

        return $number;
    }

    /**
     * Retrieve available submitting entities for the user respecting administrative and geographic scopes.
     * Implements a union-based accessibility model.
     *
     * @return Collection<int, array{id: int, type: string, name: string, label: string}>
     */
    public function getAvailableEntities(User $user): Collection
    {
        $results = collect();

        // 1. Fully privileged Administrator
        if ($user->isAdmin()) {
            $internals = InternalEntity::withoutGlobalScopes()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn ($e) => [
                    'id' => (int) $e->id,
                    'type' => 'internal',
                    'name' => $e->name,
                    'label' => $e->name.' (داخلية)',
                ]);

            $authorities = Authority::withoutGlobalScopes()
                ->where('is_active', true)
                ->whereDoesntHave('typeEntity', fn ($q) => $q->where('name', 'project'))
                ->orderBy('agency_name')
                ->get()
                ->map(fn ($a) => [
                    'id' => (int) $a->id,
                    'type' => 'external',
                    'name' => $a->agency_name,
                    'label' => $a->agency_name.' (خارجية)',
                ]);

            return $internals->concat($authorities)->values();
        }

        // 2. External Users
        if ($user->isExternal()) {
            $authQuery = Authority::withoutGlobalScopes()->where('is_active', true);
            $this->organizationResolver->applyVisibleAuthorities($authQuery, $user);
            $authorities = $authQuery->orderBy('agency_name')->get()->map(fn ($a) => [
                'id' => (int) $a->id,
                'type' => 'external',
                'name' => $a->agency_name,
                'label' => $a->agency_name.' (خارجية)',
            ]);

            $entQuery = InternalEntity::withoutGlobalScopes()->where('is_active', true);
            $this->organizationResolver->applyVisibleInternalEntities($entQuery, $user);
            $internals = $entQuery->orderBy('name')->get()->map(fn ($e) => [
                'id' => (int) $e->id,
                'type' => 'internal',
                'name' => $e->name,
                'label' => $e->name.' (داخلية)',
            ]);

            return $authorities->concat($internals)->values();
        }

        // 3. Internal Users (Union-based: Administrative Scope + Geographic Scope + Own Entity)
        $accessibleInternalIds = [];

        // A. Own Entity
        if ($user->entity_id) {
            $accessibleInternalIds[] = (int) $user->entity_id;
        }

        // B. Administrative Scope
        $adminScopeId = $user->administrative_scope_id ?: $user->entity_id;
        if ($adminScopeId) {
            $descendants = InternalEntity::getAllDescendantIds($adminScopeId);
            $accessibleInternalIds = array_merge($accessibleInternalIds, $descendants);
        }

        // C. Geographic Scope (Union: do not intersect, union with administrative!)
        $govIds = (array) $user->getAssignedGovernorateIds();
        $dirIds = (array) $user->getAssignedDirectorateIds();

        foreach ($govIds as $govId) {
            if ($govId) {
                $geoEntityIds = InternalEntity::getAllByGovernorate($govId);
                $accessibleInternalIds = array_merge($accessibleInternalIds, $geoEntityIds);
            }
        }

        foreach ($dirIds as $dirId) {
            if ($dirId) {
                $dirEntityIds = InternalEntity::getAllByDirectorate($dirId);
                $accessibleInternalIds = array_merge($accessibleInternalIds, $dirEntityIds);
            }
        }

        $accessibleInternalIds = array_values(array_unique(array_filter($accessibleInternalIds)));

        $internals = InternalEntity::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereIn('id', ! empty($accessibleInternalIds) ? $accessibleInternalIds : [-1])
            ->orderBy('name')
            ->get()
            ->map(fn ($e) => [
                'id' => (int) $e->id,
                'type' => 'internal',
                'name' => $e->name,
                'label' => $e->name.' (داخلية)',
            ]);

        return $internals->values();
    }

    /**
     * Retrieve eligible users belonging to the given entity, respecting viewer authorization.
     *
     * @return Collection<int, array{id: int, name: string, username: string, work: ?string, department: ?string}>
     */
    public function getEligibleUsersForEntity(string $entityType, int $entityId, User $viewer): Collection
    {
        // Server-side authorization check: ensure the viewer is allowed to see this entity
        if (! $viewer->isAdmin()) {
            $availableEntities = $this->getAvailableEntities($viewer);
            $hasEntity = $availableEntities->contains(
                fn ($ent) => $ent['type'] === $entityType && (int) $ent['id'] === (int) $entityId
            );

            if (! $hasEntity) {
                throw new ValidationException(validator([], []), response()->json(['error' => 'الجهة المحددة غير مصرح بها للمستخدم الحالي.'], 403));
            }
        }

        $query = User::withoutGlobalScopes();

        if ($entityType === 'external') {
            $query->where('organization_type', OrganizationResolver::EXTERNAL)
                ->where('authority_id', $entityId);
        } else {
            $query->where(function (Builder $q) {
                $q->where('organization_type', OrganizationResolver::INTERNAL)
                    ->orWhereNull('organization_type');
            })->where('entity_id', $entityId);
        }

        // Only active users
        $query->where(function ($q) {
            $q->whereNull('status')
                ->orWhereIn('status', ['active', 'Active', '1']);
        });

        // Apply visible users constraint if external viewer
        if ($viewer->isExternal()) {
            $this->organizationResolver->applyVisibleUsers($query, $viewer);
        }

        return $query->orderBy('name')
            ->get(['id', 'name', 'username', 'work', 'department'])
            ->map(fn ($u) => [
                'id' => (int) $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'work' => $u->work,
                'department' => $u->department,
                'display' => $u->name.($u->work ? " ({$u->work})" : ($u->department ? " ({$u->department})" : '')),
            ]);
    }

    /**
     * Retrieve authoritative financial information for a project.
     */
    public function getProjectFinancialData(Project $project): array
    {
        $plan = $project->disbursementPlan;
        if ($plan) {
            $plan->loadMissing([
                'details.activePaymentRequest',
                'details.clearanceRequests',
            ]);
        }
        $totalCost = (float) ($project->cost?->total_cost ?? $plan?->project_total_cost ?? 0);
        $approvedFunding = (float) ($plan?->approved_funding_amount ?: ($plan?->total_plan_amount ?: ($project->total_funding_amount ?: $totalCost)));
        $totalPlanAmount = (float) ($plan?->total_plan_amount ?? 0);

        $details = $plan?->details ?? collect();
        $scheduledInstallmentsSum = (float) $details->sum('planned_amount');

        // Balances
        $totalActuallyDisbursed = (float) $details->whereIn('status', ['disbursed', 'cleared', 'clearance_requested'])->sum('disbursed_amount');
        if ($totalActuallyDisbursed <= 0 && (float) $project->total_actually_disbursed > 0) {
            $totalActuallyDisbursed = (float) $project->total_actually_disbursed;
        }

        $totalClearedFromDetails = (float) $details->where('status', 'cleared')->sum('disbursed_amount');
        $totalClearedFromRequests = (float) $project->clearanceRequests()->where('status', 'approved')->sum('total_cleared_amount');
        $totalPreviouslyCleared = max($totalClearedFromDetails, $totalClearedFromRequests);

        $totalOutstandingAdvances = max(0.0, $totalActuallyDisbursed - $totalPreviouslyCleared);
        $remainingEligibleForDisbursement = max(0.0, $approvedFunding - $totalActuallyDisbursed);
        $remainingBudget = max(0.0, $totalCost - $totalActuallyDisbursed);
        $totalSpent = (float) $project->total_financial_execution;

        // Counts
        $totalInstallmentsCount = $details->count();
        $disbursedInstallmentsCount = $details->whereIn('status', ['disbursed', 'cleared', 'clearance_requested'])->count();
        $clearedInstallmentsCount = $details->where('status', 'cleared')->count();
        $remainingInstallmentsCount = $details->where('status', 'planned')->count();
        $cancelledInstallmentsCount = $details->where('status', 'cancelled')->count();

        // Eligible clearance installment: earliest installment currently disbursed
        $eligibleClearanceDetail = $plan?->status === 'approved'
            ? $details->whereIn('status', ['disbursed', 'clearance_requested'])->sortBy('sequence_number')->first()
            : null;

        // Eligible payment installment (pure payment request): next planned whose predecessor is cleared
        $eligiblePaymentDetail = null;
        // Eligible payment installment (combined clearance_payment request): next planned whose predecessor is either cleared OR the eligible clearance detail
        $eligiblePaymentForCombinedDetail = null;

        if ($plan?->status === 'approved') {
            $eligiblePaymentDetail = $details->first(function (ProjectDisbursementPlanDetail $detail) use ($details): bool {
                if ($detail->status !== 'planned' || (int) $detail->sequence_number <= 1) {
                    return false;
                }
                $previous = $details->firstWhere('sequence_number', $detail->sequence_number - 1);

                return $previous?->status === 'cleared' && ! $detail->activePaymentRequest;
            });

            $eligiblePaymentForCombinedDetail = $details->first(function (ProjectDisbursementPlanDetail $detail) use ($details, $eligibleClearanceDetail): bool {
                if ($detail->status !== 'planned' || (int) $detail->sequence_number <= 1) {
                    return false;
                }
                $previous = $details->firstWhere('sequence_number', $detail->sequence_number - 1);
                $prevIsCleared = $previous?->status === 'cleared';
                $prevIsEligibleClearance = $eligibleClearanceDetail && $previous?->id === $eligibleClearanceDetail->id;

                return ($prevIsCleared || $prevIsEligibleClearance) && ! $detail->activePaymentRequest;
            });
        }

        // Active / next installment references
        $activeInstallment = $eligibleClearanceDetail;
        $nextInstallment = $eligiblePaymentDetail ?: $eligiblePaymentForCombinedDetail;

        // Un-cleared executions count and sum
        $unClearedProjectExec = ProjectExecution::where('project_id', $project->id)
            ->whereNull('clearance_request_id')
            ->get();
        $unClearedPrelimExec = PreliminaryProcedureExecution::where('project_id', $project->id)
            ->whereNull('clearance_request_id')
            ->get();

        $unClearedTotal = (float) $unClearedProjectExec->sum('amount_spent') + (float) $unClearedPrelimExec->sum('amount_spent');
        $unClearedItemsCount = $unClearedProjectExec->count() + $unClearedPrelimExec->count();

        $sumPct = (float) $unClearedProjectExec->sum('completion_percentage') + (float) $unClearedPrelimExec->sum('completion_percentage');
        $avgPct = $unClearedItemsCount > 0 ? round($sumPct / $unClearedItemsCount, 2) : 0.0;

        // Closure eligibility
        $isClosureEligible = $totalOutstandingAdvances <= 0 && $unClearedItemsCount === 0;
        $closureReason = $isClosureEligible
            ? 'المشروع مؤهل للإغلاق (تمت تسوية وإخلاء كافة العهد السابقة ولا توجد مستخلصات معلقة).'
            : ($totalOutstandingAdvances > 0
                ? "لا يمكن إغلاق المشروع نظراً لوجود عهد غير مخلاة بمبلغ {$totalOutstandingAdvances} ر.ي."
                : "توجد {$unClearedItemsCount} مستخلصات/إجراءات غير مخلاة.");

        return [
            // Standard / Legacy compatibility
            'project_id' => $project->id,
            'project_name' => $project->project_name,
            'project_number' => $project->form_number ?: ('#'.$project->id),
            'total_budget' => $totalCost,
            'total_disbursed' => $totalActuallyDisbursed,
            'remaining_budget' => $remainingBudget,
            'total_spent' => $totalSpent,
            'plan_status' => $plan?->status,
            'plan_id' => $plan?->id,
            'plan_total_installments' => $totalInstallmentsCount,
            'installments' => $details->map(fn (ProjectDisbursementPlanDetail $detail): array => [
                'id' => $detail->id,
                'sequence_number' => $detail->sequence_number,
                'percentage' => $totalCost > 0 ? round(((float) $detail->planned_amount / $totalCost) * 100, 2) : 0,
                'planned_amount' => (float) $detail->planned_amount,
                'disbursed_amount' => (float) $detail->disbursed_amount,
                'disbursed_at' => $detail->disbursed_at?->format('Y-m-d'),
                'cleared_amount' => (float) ($detail->relationLoaded('clearanceRequests') ? $detail->clearanceRequests->whereIn('status', ['pending', 'approved'])->sum('total_cleared_amount') : $detail->clearanceRequests()->whereIn('status', ['pending', 'approved'])->sum('total_cleared_amount')),
                'status' => $detail->status,
                'status_label' => $detail->statusLabel(),
                'has_pending_payment' => ($detail->relationLoaded('activePaymentRequest') ? $detail->activePaymentRequest !== null : $detail->activePaymentRequest()->exists()),
            ])->values()->all(),
            'active_installment' => $activeInstallment ? [
                'id' => $activeInstallment->id,
                'sequence_number' => $activeInstallment->sequence_number,
                'disbursed_amount' => (float) $activeInstallment->disbursed_amount,
                'disbursed_at' => $activeInstallment->disbursed_at?->format('Y-m-d'),
                'status' => $activeInstallment->status,
                'status_label' => $activeInstallment->statusLabel(),
            ] : null,
            'next_installment' => $nextInstallment ? [
                'id' => $nextInstallment->id,
                'sequence_number' => $nextInstallment->sequence_number,
                'planned_amount' => (float) $nextInstallment->planned_amount,
                'status' => $nextInstallment->status,
                'status_label' => $nextInstallment->statusLabel(),
            ] : null,
            'un_cleared_items_count' => $unClearedItemsCount,
            'un_cleared_total_amount' => $unClearedTotal,
            'suggested_progress_pct' => $avgPct,

            // Section 8 Authoritative Structure
            'budget' => [
                'approved_budget' => $totalCost,
                'approved_funding' => $approvedFunding,
                'total_plan_amount' => $totalPlanAmount,
                'scheduled_installments_sum' => $scheduledInstallmentsSum,
            ],
            'balances' => [
                'total_actually_disbursed' => $totalActuallyDisbursed,
                'total_previously_cleared' => $totalPreviouslyCleared,
                'total_outstanding_advances' => $totalOutstandingAdvances,
                'remaining_eligible_for_disbursement' => $remainingEligibleForDisbursement,
            ],
            'counts' => [
                'total_installments_count' => $totalInstallmentsCount,
                'disbursed_installments_count' => $disbursedInstallmentsCount,
                'cleared_installments_count' => $clearedInstallmentsCount,
                'remaining_installments_count' => $remainingInstallmentsCount,
                'cancelled_installments_count' => $cancelledInstallmentsCount,
            ],
            'eligible_clearance' => $eligibleClearanceDetail ? [
                'id' => $eligibleClearanceDetail->id,
                'sequence_number' => $eligibleClearanceDetail->sequence_number,
                'disbursed_amount' => (float) $eligibleClearanceDetail->disbursed_amount,
                'disbursed_at' => $eligibleClearanceDetail->disbursed_at?->format('Y-m-d'),
                'status' => $eligibleClearanceDetail->status,
                'status_label' => $eligibleClearanceDetail->statusLabel(),
            ] : null,
            'eligible_payment' => $eligiblePaymentDetail ? [
                'id' => $eligiblePaymentDetail->id,
                'sequence_number' => $eligiblePaymentDetail->sequence_number,
                'planned_amount' => (float) $eligiblePaymentDetail->planned_amount,
                'status' => $eligiblePaymentDetail->status,
                'status_label' => $eligiblePaymentDetail->statusLabel(),
            ] : null,
            'eligible_payment_for_combined' => $eligiblePaymentForCombinedDetail ? [
                'id' => $eligiblePaymentForCombinedDetail->id,
                'sequence_number' => $eligiblePaymentForCombinedDetail->sequence_number,
                'planned_amount' => (float) $eligiblePaymentForCombinedDetail->planned_amount,
                'status' => $eligiblePaymentForCombinedDetail->status,
                'status_label' => $eligiblePaymentForCombinedDetail->statusLabel(),
            ] : null,
            'closure_eligibility' => [
                'is_eligible' => $isClosureEligible,
                'reason' => $closureReason,
            ],
        ];
    }

    public function assertEligibleProject(Project $project): void
    {
        $project->loadMissing('disbursementPlan');

        if (! in_array($project->status, ['in_execution', 'in_progress', 'implementation'], true)) {
            throw ValidationException::withMessages(['project_id' => 'المشروع لم يدخل مرحلة التنفيذ بعد.']);
        }

        if (! $project->uses_disbursement_workflow || ! $project->disbursementPlan || $project->disbursementPlan->status !== 'approved') {
            throw ValidationException::withMessages(['project_id' => 'لا توجد خطة صرف معتمدة لهذا المشروع.']);
        }
    }

    private function resolveInstallment(Project $project, int $detailId): ProjectDisbursementPlanDetail
    {
        $detail = ProjectDisbursementPlanDetail::query()
            ->with(['plan.details', 'activePaymentRequest', 'clearanceRequests'])
            ->whereKey($detailId)
            ->whereHas('plan', fn (Builder $query) => $query
                ->where('project_id', $project->id)
                ->where('status', 'approved'))
            ->first();

        if (! $detail) {
            throw ValidationException::withMessages(['project_id' => 'الدفعة المحددة لا تنتمي إلى خطة صرف معتمدة لهذا المشروع.']);
        }

        return $detail;
    }

    /**
     * Create and persist a new Unified Request with server-side validation.
     */
    public function createRequest(array $data, User $creator): ProjectUnifiedRequest
    {
        return DB::transaction(function () use ($data, $creator) {
            $project = Project::visibleToUser('projects')->whereKey($data['project_id'])->lockForUpdate()->firstOrFail();
            $this->assertEligibleProject($project);

            $submittingType = $data['submitting_type'] ?? ($creator->isExternal() ? 'external' : 'internal');
            $entityId = (int) ($submittingType === 'external'
                ? ($data['submitting_authority_id'] ?? ($creator->authority_id ?: 0))
                : ($data['submitting_entity_id'] ?? ($creator->entity_id ?: 0)));
            if ($entityId <= 0) {
                if ($submittingType === 'internal') {
                    $entityId = $project->implementingEntities()->first()?->internal_entity_id ?: 1;
                } else {
                    $entityId = $project->supervisingAuthorities()->first()?->authority_id ?: 1;
                }
            }
            $submittingUserId = (int) ($data['submitting_user_id'] ?? $creator->id);

            // 3. Generate Request Number
            $requestNumber = $this->generateRequestNumber($data['request_type'], $project->id);

            // 4. Financial Component Resolution
            $clearanceRequestId = null;
            $paymentRequestId = null;

            // Clearance component
            $clearanceDetailId = $data['clearance_disbursement_plan_detail_id'] ?? null;
            $clearanceDetail = $clearanceDetailId ? $this->resolveInstallment($project, (int) $clearanceDetailId) : null;
            $clearedAmount = (float) ($data['cleared_amount'] ?? 0.0);
            $originalAdvanceAmount = (float) ($clearanceDetail?->disbursed_amount ?? 0);
            $remainingAdvanceAmount = max(0.0, $originalAdvanceAmount - $clearedAmount);

            // Payment component
            $paymentDetailId = $data['payment_disbursement_plan_detail_id'] ?? null;
            $paymentDetail = $paymentDetailId ? $this->resolveInstallment($project, (int) $paymentDetailId) : null;
            $requestType = $data['request_type'];
            if (in_array($requestType, [ProjectUnifiedRequest::TYPE_PAYMENT, ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT], true)) {
                if (! $paymentDetail || (int) $paymentDetail->sequence_number <= 1 || $paymentDetail->status !== 'planned') {
                    throw ValidationException::withMessages(['payment_disbursement_plan_detail_id' => 'لا توجد دفعة تالية مؤهلة للصرف.']);
                }
                $previousDetail = $paymentDetail->plan->details
                    ? $paymentDetail->plan->details->firstWhere('sequence_number', $paymentDetail->sequence_number - 1)
                    : $paymentDetail->plan->details()->where('sequence_number', $paymentDetail->sequence_number - 1)->first();

                $isPredecessorCleared = $previousDetail && $previousDetail->status === 'cleared';
                $isPredecessorClearedInSameRequest = $requestType === ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT
                    && $clearanceDetail
                    && $previousDetail
                    && $previousDetail->id === $clearanceDetail->id
                    && in_array($clearanceDetail->status, ['disbursed', 'clearance_requested'], true)
                    && ($clearedAmount >= (float) $clearanceDetail->disbursed_amount || $remainingAdvanceAmount <= 0.001);

                if (! $previousDetail || (! $isPredecessorCleared && ! $isPredecessorClearedInSameRequest) || $paymentDetail->activePaymentRequest) {
                    throw ValidationException::withMessages(['payment_disbursement_plan_detail_id' => 'يجب إخلاء عهدة الدفعة السابقة بالكامل أولاً قبل طلب دفعة جديدة وألا يوجد طلب صرف نشط.']);
                }
            }
            if (in_array($requestType, [ProjectUnifiedRequest::TYPE_CLEARANCE, ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT, ProjectUnifiedRequest::TYPE_CLEARANCE_CLOSURE], true)
                && (! $clearanceDetail || ! in_array($clearanceDetail->status, ['disbursed', 'clearance_requested'], true))) {
                throw ValidationException::withMessages(['clearance_disbursement_plan_detail_id' => 'لا توجد دفعة مصروفة مؤهلة للإخلاء.']);
            }
            $requestedPaymentAmount = (float) ($data['requested_payment_amount'] ?? ($paymentDetail?->planned_amount ?? 0));

            // Create Unified Request record
            $unifiedRequest = ProjectUnifiedRequest::create([
                'request_number' => $requestNumber,
                'request_type' => $data['request_type'],
                'project_id' => $project->id,
                'submitting_type' => $submittingType,
                'submitting_entity_id' => $submittingType === 'internal' ? $entityId : null,
                'submitting_authority_id' => $submittingType === 'external' ? $entityId : null,
                'submitting_user_id' => $submittingUserId,
                'created_by' => $creator->id,
                'request_date' => now()->toDateString(),
                'status' => ProjectUnifiedRequest::STATUS_DRAFT,
                'description' => $data['description'] ?? null,

                // Clearance details
                'clearance_disbursement_plan_detail_id' => $clearanceDetailId,
                'advance_recipient' => $data['advance_recipient'] ?? null,
                'original_advance_amount' => $originalAdvanceAmount,
                'cleared_amount' => $clearedAmount,
                'remaining_advance_amount' => $remainingAdvanceAmount,
                'advance_receipt_date' => $data['advance_receipt_date'] ?? null,
                'technical_progress_percentage' => (float) ($data['technical_progress_percentage'] ?? 0),
                'clearance_description' => $data['clearance_description'] ?? null,
                'clearance_request_id' => $clearanceRequestId,

                // Payment details
                'payment_disbursement_plan_detail_id' => $paymentDetailId,
                'payment_number' => $paymentDetail?->sequence_number,
                'requested_payment_amount' => $requestedPaymentAmount,
                'payment_recipient' => $data['payment_recipient'] ?? null,
                'expected_clearance_date' => $data['expected_clearance_date'] ?? null,
                'payment_justification' => $data['payment_justification'] ?? null,
                'payment_request_id' => $paymentRequestId,

                // Closure details
                'closure_justification' => $data['closure_justification'] ?? null,
                'project_completion_date' => $data['project_completion_date'] ?? null,
                'final_settlement_notes' => $data['final_settlement_notes'] ?? null,

                'attachment_path' => $data['attachment_path'] ?? null,
                'additional_documents' => $data['additional_documents'] ?? null,
            ]);

            // Create draft payment obligation if payment component exists
            if ($paymentDetail) {
                $paymentRef = "DISB-P{$project->id}-D{$paymentDetail->id}-REQ{$unifiedRequest->id}";
                $unifiedRequest->update(['erpnext_payment_reference' => $paymentRef]);

                ProjectInstallmentPaymentObligation::updateOrCreate(
                    [
                        'project_disbursement_plan_detail_id' => $paymentDetail->id,
                    ],
                    [
                        'requestable_type' => $unifiedRequest->getMorphClass(),
                        'requestable_id' => $unifiedRequest->id,
                        'status' => 'draft',
                        'erpnext_payment_reference' => $paymentRef,
                        'erpnext_sync_status' => 'pending',
                    ]
                );
            }

            return $unifiedRequest;
        });
    }

    /**
     * Update an existing Unified Request in draft or pending state.
     */
    public function updateRequest(ProjectUnifiedRequest $unifiedRequest, array $data, User $user): ProjectUnifiedRequest
    {
        return DB::transaction(function () use ($unifiedRequest, $data) {
            if (! in_array($unifiedRequest->status, [ProjectUnifiedRequest::STATUS_DRAFT, ProjectUnifiedRequest::STATUS_PENDING], true)) {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن تعديل طلب تم اعتماده أو رفضه.',
                ]);
            }

            $clearedAmount = (float) ($data['cleared_amount'] ?? $unifiedRequest->cleared_amount);
            $origAdvance = (float) ($data['original_advance_amount'] ?? $unifiedRequest->original_advance_amount);
            $remAdvance = max(0.0, $origAdvance - $clearedAmount);

            $updatePayload = [
                'description' => $data['description'] ?? $unifiedRequest->description,
                'advance_recipient' => $data['advance_recipient'] ?? $unifiedRequest->advance_recipient,
                'cleared_amount' => $clearedAmount,
                'remaining_advance_amount' => $remAdvance,
                'advance_receipt_date' => $data['advance_receipt_date'] ?? $unifiedRequest->advance_receipt_date,
                'technical_progress_percentage' => (float) ($data['technical_progress_percentage'] ?? $unifiedRequest->technical_progress_percentage),
                'clearance_description' => $data['clearance_description'] ?? $unifiedRequest->clearance_description,
                'requested_payment_amount' => (float) ($data['requested_payment_amount'] ?? $unifiedRequest->requested_payment_amount),
                'payment_recipient' => $data['payment_recipient'] ?? $unifiedRequest->payment_recipient,
                'expected_clearance_date' => $data['expected_clearance_date'] ?? $unifiedRequest->expected_clearance_date,
                'payment_justification' => $data['payment_justification'] ?? $unifiedRequest->payment_justification,
                'closure_justification' => $data['closure_justification'] ?? $unifiedRequest->closure_justification,
                'project_completion_date' => $data['project_completion_date'] ?? $unifiedRequest->project_completion_date,
                'final_settlement_notes' => $data['final_settlement_notes'] ?? $unifiedRequest->final_settlement_notes,
            ];

            if (! empty($data['attachment_path'])) {
                $updatePayload['attachment_path'] = $data['attachment_path'];
            }

            $unifiedRequest->update($updatePayload);

            return $unifiedRequest->fresh();
        });
    }

    /**
     * Approve a Unified Request with financial and closure execution.
     */
    public function approveRequest(ProjectUnifiedRequest $request, User $approver, ?string $notes = null): void
    {
        // PHASE 1: DB Transaction 1 (Complete Clearance and Pre-Payment Validation)
        $paymentDetail = null;
        $erpPaymentId = null;
        $needsPaymentSync = false;

        DB::transaction(function () use ($request, $approver, $notes, &$paymentDetail, &$erpPaymentId, &$needsPaymentSync) {
            $project = $request->project;

            // 1. Process Clearance component completely FIRST
            if ($request->hasClearanceComponent()) {
                if ($request->clearanceRequest && $request->clearanceRequest->status === 'pending') {
                    $this->financialWorkflowService->approveClearanceRequest($request->clearanceRequest, $approver, $notes, false);
                } elseif ($request->clearanceInstallment && in_array($request->clearanceInstallment->status, ['disbursed', 'clearance_requested'])) {
                    $request->clearanceInstallment->update([
                        'status' => 'cleared',
                        'cleared_at' => now(),
                        'cleared_by' => $approver->id,
                    ]);
                }
            }

            // 2. Prepare Payment component
            if ($request->hasPaymentComponent()) {
                $paymentDetail = $request->paymentInstallment;
                if (! $paymentDetail) {
                    throw ValidationException::withMessages(['payment_disbursement_plan_detail_id' => 'تعذر تحديد دفعة الصرف المرتبطة بالطلب.']);
                }

                // Verify predecessor clearance
                $previousDetail = $paymentDetail->plan->details
                    ? $paymentDetail->plan->details->firstWhere('sequence_number', $paymentDetail->sequence_number - 1)
                    : $paymentDetail->plan->details()->where('sequence_number', $paymentDetail->sequence_number - 1)->first();

                if ($previousDetail && $previousDetail->status !== 'cleared') {
                    throw ValidationException::withMessages(['payment_disbursement_plan_detail_id' => 'لا يمكن اعتماد طلب الصرف نظراً لأن الدفعة السابقة لم يتم إخلاؤها بالكامل.']);
                }

                if ($request->paymentRequest && $request->paymentRequest->status === 'pending') {
                    $this->financialWorkflowService->approvePaymentRequest($request->paymentRequest, $approver, $notes);
                } elseif ($paymentDetail && in_array($paymentDetail->status, ['planned', 'payment_requested'])) {
                    $paymentReq = $this->financialWorkflowService->submitPaymentRequest(
                        $project,
                        $paymentDetail,
                        ['notes' => $request->payment_justification],
                        $request->submittingUser ?: $approver,
                        $request->id
                    );
                    $request->update(['payment_request_id' => $paymentReq->id]);
                    $this->financialWorkflowService->approvePaymentRequest($paymentReq, $approver, $notes);
                }

                $erpPaymentId = $request->erpnext_payment_id;
                $needsPaymentSync = true;
            }

            // 3. Process Closure component (if no payment pending)
            if ($request->hasClosureComponent() && ! $request->hasPaymentComponent()) {
                if ($project->uses_disbursement_workflow) {
                    $plan = $project->disbursementPlan;
                    if ($plan) {
                        $uncleared = $plan->details()->whereNotIn('status', ['cleared', 'cancelled', 'planned'])->count();
                        if ($uncleared > 0) {
                            throw ValidationException::withMessages([
                                'request' => "لا يمكن إغلاق المشروع نظراً لوجود {$uncleared} دفعة غير مخلاة بالكامل.",
                            ]);
                        }
                        $plan->details()->where('status', 'planned')->update(['status' => 'cancelled']);
                    }
                }
                $project->update([
                    'status' => 'completed',
                    'is_stopped' => true,
                ]);
                $request->update([
                    'status' => ProjectUnifiedRequest::STATUS_APPROVED,
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                ]);
            } elseif (! $request->hasPaymentComponent()) {
                // Clearance only
                $request->update([
                    'status' => ProjectUnifiedRequest::STATUS_APPROVED,
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                ]);
            }
        });

        // If no payment component, we're done safely!
        if (! $needsPaymentSync) {
            return;
        }

        // PHASE 2: Remote ERPNext Call (OUTSIDE Database Transaction)
        $writesEnabled = (bool) config('external.erpnext.payment_entry_writes_enabled', false);
        $syncSuccess = false;
        $syncError = null;

        if (! $writesEnabled) {
            $syncError = 'ERPNext payment writes are disabled in configuration.';
        } elseif (empty($erpPaymentId)) {
            $syncError = 'لا يوجد معرف مسودة قيد دفع (erpnext_payment_id) لاعتمادها في ERPNext.';
        } else {
            try {
                $submitRes = $this->frappeAPIService->submitPaymentEntry($erpPaymentId);
                $isVerified = ($submitRes['success'] ?? false) && $this->frappeAPIService->verifyPaymentEntrySubmitted($erpPaymentId);

                if ($isVerified) {
                    $syncSuccess = true;
                } else {
                    $syncError = $submitRes['error'] ?? 'تعذر تأكيد اعتماد قيد الصرف في ERPNext.';
                }
            } catch (\Throwable $e) {
                \Log::error("ERPNext final submission exception for request {$request->request_number}: ".$e->getMessage());
                $syncError = $e->getMessage();
            }
        }

        // PHASE 3: DB Transaction 2 (Finalize State Based On Remote Synchronization)
        DB::transaction(function () use ($request, $paymentDetail, $approver, $syncSuccess, $syncError, $writesEnabled) {
            $lockedRequest = ProjectUnifiedRequest::where('id', $request->id)->lockForUpdate()->first();
            $lockedDetail = ProjectDisbursementPlanDetail::where('id', $paymentDetail->id)->lockForUpdate()->first();

            if ($syncSuccess) {
                // SUCCESS: Mark as disbursed and approve
                $lockedDetail->update([
                    'status' => 'disbursed',
                    'disbursed_at' => now(),
                    'disbursed_amount' => (float) $lockedRequest->requested_payment_amount,
                    'erpnext_payment_id' => $lockedRequest->erpnext_payment_id,
                    'erpnext_payment_reference' => $lockedRequest->erpnext_payment_reference,
                ]);
                $lockedDetail->plan->refreshCachedDisbursedAmount();

                $lockedRequest->update([
                    'status' => ProjectUnifiedRequest::STATUS_APPROVED,
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                    'erpnext_sync_status' => 'submitted',
                    'erpnext_synced_at' => now(),
                    'erpnext_sync_error' => null,
                ]);

                $lockedRequest->paymentObligation?->update([
                    'status' => 'submitted',
                    'erpnext_sync_status' => 'submitted',
                    'erpnext_synced_at' => now(),
                    'erpnext_sync_error' => null,
                ]);

                // If combined closure request:
                if ($lockedRequest->hasClosureComponent()) {
                    $project = $lockedRequest->project;
                    if ($project->uses_disbursement_workflow && $project->disbursementPlan) {
                        $project->disbursementPlan->details()->where('status', 'planned')->update(['status' => 'cancelled']);
                    }
                    $project->update([
                        'status' => 'completed',
                        'is_stopped' => true,
                    ]);
                }
            } else {
                // FAILURE or WRITES DISABLED:
                // NEVER mark payment as disbursed!
                $statusFlag = $writesEnabled ? 'reconciliation_required' : 'sync_disabled';
                $lockedRequest->update([
                    'erpnext_sync_status' => $statusFlag,
                    'erpnext_sync_error' => $syncError,
                ]);
                $lockedRequest->paymentObligation?->update([
                    'erpnext_sync_status' => $statusFlag,
                    'erpnext_sync_error' => $syncError,
                ]);
            }
        });

        // Record in project approval history
        try {
            ProjectApproval::create([
                'project_id' => $request->project_id,
                'status' => 'approved',
                'notes' => $notes ?: 'اعتماد طلب موحد: '.$request->request_number,
                'reviewed_by' => $approver->id,
                'reviewed_at' => now(),
                'created_by' => $approver->id,
            ]);
        } catch (\Throwable $e) {
            \Log::info('ProjectApproval audit note: '.$e->getMessage());
        }
    }

    /**
     * Reject a Unified Request.
     */
    public function rejectRequest(ProjectUnifiedRequest $request, User $user, string $reason): void
    {
        DB::transaction(function () use ($request, $user, $reason) {
            if ($request->clearanceRequest && $request->clearanceRequest->status === 'pending') {
                $this->financialWorkflowService->rejectClearanceRequest($request->clearanceRequest, $user, $reason);
            } elseif ($request->clearanceInstallment && $request->clearanceInstallment->status === 'clearance_requested') {
                $request->clearanceInstallment->update(['status' => 'disbursed']);
            }

            if ($request->paymentRequest && $request->paymentRequest->status === 'pending') {
                $this->financialWorkflowService->rejectPaymentRequest($request->paymentRequest, $user, $reason);
            } elseif ($request->paymentInstallment && $request->paymentInstallment->status === 'payment_requested') {
                $request->paymentInstallment->update(['status' => 'planned']);
            }

            if ($request->paymentObligation) {
                $request->paymentObligation->update(['status' => 'cancelled']);
            }

            $request->update([
                'status' => ProjectUnifiedRequest::STATUS_REJECTED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => trim($reason),
            ]);

            // Record in project approval history
            try {
                ProjectApproval::create([
                    'project_id' => $request->project_id,
                    'status' => 'rejected',
                    'notes' => 'رفض طلب موحد: '.$request->request_number,
                    'rejection_reason' => trim($reason),
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'created_by' => $user->id,
                ]);
            } catch (\Throwable $e) {
                \Log::info('ProjectApproval audit note: '.$e->getMessage());
            }
        });
    }

    /**
     * Generate dynamic multi-stage approval chain for a unified project request.
     */
    public function generateApprovalChainForRequest(ProjectUnifiedRequest $request, User $submitter): ProjectWorkflowRequest
    {
        return DB::transaction(function () use ($request, $submitter) {
            $project = $request->project;

            // 1. Build database-driven approval chain for this unified request type
            $stages = $this->approvalChainBuilder->buildChainForUnifiedRequest($request);

            // 2. Create or Update ProjectWorkflowRequest
            $wfPayload = [
                'unified_request_id' => $request->id,
                'request_number' => $request->request_number,
                'submitting_user_id' => $request->submitting_user_id,
                'submitting_entity_id' => $request->submitting_entity_id,
                'submitting_authority_id' => $request->submitting_authority_id,
                'submitting_type' => $request->submitting_type,
                'requested_amount' => $request->requested_payment_amount,
                'cleared_amount' => $request->cleared_amount,
                'payment_number' => $request->payment_number,
                'notes' => $request->description ?: ($request->payment_justification ?: $request->clearance_description),
            ];

            $wfRequest = $request->workflowRequest;
            if (! $wfRequest) {
                $wfRequest = ProjectWorkflowRequest::create([
                    'project_id' => $project->id,
                    'request_type' => $request->request_type,
                    'status' => 'pending',
                    'request_number' => 'WF-'.$request->request_number,
                    'payload' => $wfPayload,
                    'requestable_type' => $request->getMorphClass(),
                    'requestable_id' => $request->id,
                    'submitted_by' => $submitter->id,
                ]);

                $request->update(['workflow_request_id' => $wfRequest->id]);
            } else {
                $wfRequest->update([
                    'status' => 'pending',
                    'submitted_by' => $submitter->id,
                    'payload' => array_merge($wfRequest->payload ?? [], $wfPayload),
                ]);
            }

            // 3. Create isolated approval steps for this request
            ProjectApproval::where('workflow_request_id', $wfRequest->id)
                ->orWhere('unified_request_id', $request->id)
                ->delete();

            foreach ($stages as $stage) {
                $isFirst = (int) $stage['order'] === 1;
                ProjectApproval::create([
                    'project_id' => $project->id,
                    'workflow_request_id' => $wfRequest->id,
                    'unified_request_id' => $request->id,
                    'entity_id' => $stage['entity_id'] ?? null,
                    'authority_id' => $stage['authority_id'] ?? null,
                    'approver_scope' => $stage['approver_scope'] ?? 'internal',
                    'drop' => $stage['code'] ?? 'stage_'.$stage['order'],
                    'phase' => $stage['phase'] ?? null,
                    'step_order' => $stage['order'],
                    'status' => $isFirst ? 'pending' : 'locked',
                    'is_active' => $isFirst,
                    'created_by' => $submitter->id,
                    'assigned_user_id' => $stage['responsible_user_id'] ?? null,
                    'stage_key' => $stage['code'] ?? null,
                    'stage_name' => $stage['stage_name'] ?? ($stage['name_ar'] ?? 'مرحلة الاعتماد'),
                    'available_actions' => $stage['available_actions'] ?? ['submit', 'approve', 'return', 'reject'],
                    'required_attachments' => $stage['required_attachments'] ?? [],
                    'uploaded_attachments' => [],
                    'action_rules' => $stage['action_rules'] ?? [],
                    'workflow_version' => $stage['workflow_version'] ?? 1,
                    'submitted_by' => $submitter->id,
                    'submitted_at' => now(),
                ]);
            }

            return $wfRequest->load('approvalSteps');
        });
    }

    /**
     * Confirm a draft unified request and mark it as confirmed draft.
     */
    public function confirmDraft(ProjectUnifiedRequest $request, User $user): ProjectUnifiedRequest
    {
        return DB::transaction(function () use ($request, $user) {
            if (! $request->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'يمكن فقط تأكيد الطلبات التي في حالة مسودة.',
                ]);
            }

            // Basic sanity checks before confirming
            if ($request->hasPaymentComponent()) {
                if ((float) $request->requested_payment_amount <= 0) {
                    throw ValidationException::withMessages([
                        'requested_payment_amount' => 'يجب تحديد مبلغ دفعة صالح لتأكيد المسودة.',
                    ]);
                }
                if (empty($request->payment_recipient)) {
                    throw ValidationException::withMessages([
                        'payment_recipient' => 'يجب تحديد المستفيد من الدفعة لتأكيد المسودة.',
                    ]);
                }
            }

            if ($request->hasClearanceComponent()) {
                if ((float) $request->cleared_amount <= 0) {
                    throw ValidationException::withMessages([
                        'cleared_amount' => 'يجب تحديد مبلغ الإخلاء الفعلي لتأكيد المسودة.',
                    ]);
                }
            }

            $request->update([
                'status' => ProjectUnifiedRequest::STATUS_CONFIRMED,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
            ]);

            try {
                ProjectActivityHistory::create([
                    'project_id' => $request->project_id,
                    'user_id' => $user->id,
                    'action_type' => 'unified_request_confirmed',
                    'notes' => 'تم تأكيد مسودة الطلب الموحد: '.$request->request_number,
                    'metadata' => [
                        'unified_request_id' => $request->id,
                        'request_number' => $request->request_number,
                    ],
                ]);
            } catch (\Throwable $e) {
                \Log::info('Activity history note: '.$e->getMessage());
            }

            return $request->fresh();
        });
    }

    /**
     * Revert a confirmed request back to draft.
     */
    public function revertToDraft(ProjectUnifiedRequest $request, User $user): ProjectUnifiedRequest
    {
        return DB::transaction(function () use ($request, $user) {
            if (! $request->isConfirmed()) {
                throw ValidationException::withMessages([
                    'status' => 'يمكن فقط إعادة الطلبات المؤكدة إلى حالة مسودة.',
                ]);
            }

            $request->update([
                'status' => ProjectUnifiedRequest::STATUS_DRAFT,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);

            try {
                ProjectActivityHistory::create([
                    'project_id' => $request->project_id,
                    'user_id' => $user->id,
                    'action_type' => 'unified_request_reverted_to_draft',
                    'notes' => 'تمت إعادة الطلب الموحد إلى مسودة: '.$request->request_number,
                    'metadata' => [
                        'unified_request_id' => $request->id,
                        'request_number' => $request->request_number,
                    ],
                ]);
            } catch (\Throwable $e) {
                \Log::info('Activity history note: '.$e->getMessage());
            }

            return $request->fresh();
        });
    }

    /**
     * Submit a confirmed/draft unified request and enter approval stages workflow.
     */
    public function submitRequest(ProjectUnifiedRequest $request, User $user): ProjectUnifiedRequest
    {
        // PHASE 1: DB Transaction 1 (Prepare Child Requests and Lock Obligation)
        $paymentDetail = null;
        $paymentRef = null;
        $requestedAmount = 0.0;
        $needsDraftSync = false;

        DB::transaction(function () use ($request, $user, &$paymentDetail, &$paymentRef, &$requestedAmount, &$needsDraftSync) {
            $lockedProject = Project::whereKey($request->project_id)->lockForUpdate()->firstOrFail();
            $this->assertEligibleProject($lockedProject);
            if (! $request->canBeSubmitted()) {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن إرسال الطلب للاعتماد في حالته الحالية.',
                ]);
            }

            // Financial child requests are created only on formal submission; draft saves are non-financial.
            if (in_array($request->request_type, [ProjectUnifiedRequest::TYPE_CLEARANCE, ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT, ProjectUnifiedRequest::TYPE_CLEARANCE_CLOSURE], true) && $request->clearance_disbursement_plan_detail_id && ! $request->clearance_request_id) {
                $clearanceDetail = $this->resolveInstallment($lockedProject, (int) $request->clearance_disbursement_plan_detail_id);
                $clrRecord = $this->financialWorkflowService->submitClearanceRequest(
                    $lockedProject,
                    $clearanceDetail,
                    [
                        'technical_progress_percentage' => $request->technical_progress_percentage ?? 0,
                        'notes' => $request->clearance_description ?? null,
                        'attachment_path' => $request->attachment_path,
                        'defer_workflow' => true,
                    ],
                    $user
                );
                $request->update([
                    'clearance_request_id' => $clrRecord->id,
                    'cleared_amount' => $clrRecord->total_cleared_amount,
                    'original_advance_amount' => $clearanceDetail->disbursed_amount,
                    'remaining_advance_amount' => max(0, (float) $clearanceDetail->disbursed_amount - (float) $clrRecord->total_cleared_amount),
                ]);
            }

            if (in_array($request->request_type, [ProjectUnifiedRequest::TYPE_PAYMENT, ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT], true) && $request->payment_disbursement_plan_detail_id && ! $request->payment_request_id) {
                $paymentDetail = $this->resolveInstallment($lockedProject, (int) $request->payment_disbursement_plan_detail_id);
                $paymentRequest = $this->financialWorkflowService->submitPaymentRequest(
                    $lockedProject,
                    $paymentDetail,
                    ['notes' => $request->payment_justification, 'attachment_path' => $request->attachment_path],
                    $user,
                    $request->id
                );
                $request->update([
                    'payment_request_id' => $paymentRequest->id,
                    'requested_payment_amount' => $paymentRequest->requested_amount,
                    'payment_number' => $paymentDetail->sequence_number,
                ]);
            }

            // Stage 2 ERPNext Synchronization (Payment Entry Draft: docstatus = 0)
            if ($request->hasPaymentComponent() && $request->payment_disbursement_plan_detail_id) {
                $paymentDetail = $this->resolveInstallment($lockedProject, (int) $request->payment_disbursement_plan_detail_id);
                $paymentRef = $request->erpnext_payment_reference ?: "DISB-P{$lockedProject->id}-D{$paymentDetail->id}-REQ{$request->id}";
                $request->update(['erpnext_payment_reference' => $paymentRef]);

                ProjectInstallmentPaymentObligation::updateOrCreate(
                    [
                        'project_disbursement_plan_detail_id' => $paymentDetail->id,
                    ],
                    [
                        'requestable_type' => $request->getMorphClass(),
                        'requestable_id' => $request->id,
                        'status' => 'locked',
                        'erpnext_payment_reference' => $paymentRef,
                        'erpnext_sync_status' => 'pending',
                    ]
                );
                $requestedAmount = (float) $request->requested_payment_amount;
                $needsDraftSync = true;
            }
        });

        // PHASE 2: Remote ERPNext Call (OUTSIDE Database Transaction)
        $writesEnabled = (bool) config('external.erpnext.payment_entry_writes_enabled', false);
        $draftSuccess = false;
        $draftPaymentId = null;
        $draftError = null;

        if ($needsDraftSync && $paymentDetail && $paymentRef) {
            if ($writesEnabled) {
                try {
                    $frappeResult = $this->frappeAPIService->createDraftPaymentEntry(
                        $paymentDetail,
                        $paymentRef,
                        $requestedAmount,
                        "صرف دفعة رقم {$paymentDetail->sequence_number} للمشروع: {$request->project->project_name} (طلب موحد: {$request->request_number})"
                    );

                    if ($frappeResult['success'] ?? false) {
                        $draftSuccess = true;
                        $draftPaymentId = $frappeResult['payment_id'];
                    } else {
                        $draftError = $frappeResult['error'] ?? 'فشل إنشاء مسودة القيد في ERPNext';
                    }
                } catch (\Throwable $e) {
                    \Log::error("ERPNext draft payment creation exception for request {$request->request_number}: ".$e->getMessage());
                    $draftError = $e->getMessage();
                }
            } else {
                $draftError = 'ERPNext payment writes are disabled in configuration.';
            }
        }

        // PHASE 3: DB Transaction 2 (Persist Draft Status and Generate Approval Chain)
        return DB::transaction(function () use ($request, $user, $needsDraftSync, $paymentRef, $writesEnabled, $draftSuccess, $draftPaymentId, $draftError) {
            $lockedRequest = ProjectUnifiedRequest::where('id', $request->id)->lockForUpdate()->first();
            $obligation = $lockedRequest->paymentObligation;

            if ($needsDraftSync) {
                if ($draftSuccess) {
                    $lockedRequest->update([
                        'erpnext_payment_reference' => $paymentRef,
                        'erpnext_payment_id' => $draftPaymentId,
                        'erpnext_sync_status' => 'draft_created',
                        'erpnext_synced_at' => now(),
                        'erpnext_sync_error' => null,
                    ]);
                    $obligation?->update([
                        'erpnext_payment_reference' => $paymentRef,
                        'erpnext_payment_id' => $draftPaymentId,
                        'erpnext_sync_status' => 'draft_created',
                        'erpnext_synced_at' => now(),
                        'erpnext_sync_error' => null,
                    ]);
                } elseif ($writesEnabled) {
                    $lockedRequest->update([
                        'erpnext_payment_reference' => $paymentRef,
                        'erpnext_sync_status' => 'reconciliation_required',
                        'erpnext_sync_error' => $draftError,
                    ]);
                    $obligation?->update([
                        'erpnext_payment_reference' => $paymentRef,
                        'erpnext_sync_status' => 'reconciliation_required',
                        'erpnext_sync_error' => $draftError,
                    ]);
                } else {
                    $lockedRequest->update([
                        'erpnext_payment_reference' => $paymentRef,
                        'erpnext_sync_status' => 'sync_disabled',
                        'erpnext_sync_error' => null,
                    ]);
                    $obligation?->update([
                        'erpnext_payment_reference' => $paymentRef,
                        'erpnext_sync_status' => 'sync_disabled',
                        'erpnext_sync_error' => null,
                    ]);
                }
            }

            $this->generateApprovalChainForRequest($lockedRequest, $user);
            $request->update(['status' => ProjectUnifiedRequest::STATUS_PENDING]);

            try {
                ProjectActivityHistory::create([
                    'project_id' => $request->project_id,
                    'user_id' => $user->id,
                    'action_type' => 'unified_request_submitted',
                    'notes' => 'تم إرسال الطلب الموحد للاعتماد وبدء مسار الموافقات: '.$request->request_number,
                    'metadata' => [
                        'unified_request_id' => $request->id,
                        'request_number' => $request->request_number,
                    ],
                ]);
            } catch (\Throwable $e) {
                \Log::info('Activity history note: '.$e->getMessage());
            }

            return $request->fresh();
        });
    }

    /**
     * Approve the active step in the request's approval chain.
     */
    public function approveStep(ProjectUnifiedRequest $request, User $user, ?string $notes = null, $attachment = null): array
    {
        return DB::transaction(function () use ($request, $user, $notes, $attachment) {
            $activeStep = $request->activeApprovalStep();
            if (! $activeStep) {
                throw ValidationException::withMessages([
                    'step' => 'لا توجد مرحلة نشطة بانتظار الاعتماد لهذا الطلب.',
                ]);
            }

            // Authoritative authorization check
            if (! $user->isAdmin() && ! $this->approvalService->canUserActOnStep($user, $activeStep, 'approve')) {
                abort(403, 'غير مصرح لك باعتماد هذه المرحلة من الطلب.');
            }

            // Enforce action permissions configured on this stage
            if (! $activeStep->isActionAllowed('approve')) {
                throw ValidationException::withMessages([
                    'action' => 'إجراء الاعتماد غير متاح في هذه المرحلة وفقاً لقواعد مسار الاعتماد.',
                ]);
            }

            $attachmentPath = null;
            if ($attachment && is_file($attachment)) {
                $attachmentPath = $attachment->store('approvals/attachments', 'public');
                $uploaded = $activeStep->uploaded_attachments ?? [];
                $uploaded['main'] = [
                    'key' => 'main',
                    'name' => method_exists($attachment, 'getClientOriginalName') ? $attachment->getClientOriginalName() : 'attachment',
                    'path' => $attachmentPath,
                    'uploaded_by' => $user->id,
                    'uploaded_at' => now()->toIso8601String(),
                ];
                $activeStep->uploaded_attachments = $uploaded;
            }

            // Verify mandatory stage attachments
            $missing = $activeStep->getMissingRequiredAttachments();
            if (! empty($missing)) {
                $missingLabels = implode('، ', array_column($missing, 'label'));
                throw ValidationException::withMessages([
                    'attachments' => "لا يمكن إتمام الاعتماد لوجود مرفقات إلزامية مطلوبة في هذه المرحلة: {$missingLabels}",
                ]);
            }

            // Mark active step as approved
            $activeStep->update([
                'status' => 'approved',
                'is_active' => false,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'notes' => $notes,
                'attachment' => $attachmentPath ?: $activeStep->attachment,
                'uploaded_attachments' => $activeStep->uploaded_attachments,
            ]);

            // Check if there is a next step
            $nextStep = $request->approvalSteps()
                ->where('step_order', '>', $activeStep->step_order)
                ->orderBy('step_order')
                ->first();

            if ($nextStep) {
                // Activate next step
                $nextStep->update([
                    'status' => 'pending',
                    'is_active' => true,
                ]);

                try {
                    ProjectActivityHistory::create([
                        'project_id' => $request->project_id,
                        'user_id' => $user->id,
                        'action_type' => 'unified_request_step_approved',
                        'notes' => "تم اعتماد الخطوة {$activeStep->step_order} ({$activeStep->stage_name}) ونقل الطلب {$request->request_number} إلى الخطوة التالية: {$nextStep->stage_name}.",
                        'metadata' => [
                            'unified_request_id' => $request->id,
                            'approved_step_id' => $activeStep->id,
                            'next_step_id' => $nextStep->id,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    \Log::info('Activity history note: '.$e->getMessage());
                }

                return [
                    'is_final' => false,
                    'approved_step' => $activeStep->fresh(),
                    'next_step' => $nextStep->fresh(),
                    'message' => 'تمت الموافقة على المرحلة بنجاح والانتقال إلى: '.($nextStep->stage_name ?: 'المرحلة التالية'),
                ];
            }

            // Final step reached!
            $this->approveRequest($request, $user, $notes);

            if ($request->workflowRequest) {
                $request->workflowRequest->update([
                    'status' => 'approved',
                    'decided_by' => $user->id,
                    'decided_at' => now(),
                    'decision_notes' => $notes,
                ]);
            }

            try {
                ProjectActivityHistory::create([
                    'project_id' => $request->project_id,
                    'user_id' => $user->id,
                    'action_type' => 'unified_request_final_approved',
                    'notes' => "تم الاعتماد النهائي للطلب الموحد {$request->request_number} وتنفيذ الإجراءات المرتبطة به.",
                    'metadata' => ['unified_request_id' => $request->id],
                ]);
            } catch (\Throwable $e) {
                \Log::info('Activity history note: '.$e->getMessage());
            }

            return [
                'is_final' => true,
                'approved_step' => $activeStep->fresh(),
                'next_step' => null,
                'message' => 'تم اعتماد الطلب نهائياً وتنفيذ كافة الإجراءات المالية والإدارية المرتبطة به.',
            ];
        });
    }

    /**
     * Reject the active step and the request entirely.
     */
    public function rejectStep(ProjectUnifiedRequest $request, User $user, string $reason): array
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            $activeStep = $request->activeApprovalStep();
            if (! $activeStep) {
                throw ValidationException::withMessages([
                    'step' => 'لا توجد مرحلة نشطة لرفضها.',
                ]);
            }

            if (! $user->isAdmin() && ! $this->approvalService->canUserActOnStep($user, $activeStep, 'reject')) {
                abort(403, 'غير مصرح لك برفض هذا الطلب.');
            }

            if (! $activeStep->isActionAllowed('reject')) {
                throw ValidationException::withMessages([
                    'action' => 'إجراء الرفض غير متاح في هذه المرحلة وفقاً لقواعد مسار الاعتماد.',
                ]);
            }

            $activeStep->update([
                'status' => 'rejected',
                'is_active' => false,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'notes' => $reason,
            ]);

            // Lock all subsequent steps
            $request->approvalSteps()->where('step_order', '>', $activeStep->step_order)->update([
                'status' => 'locked',
                'is_active' => false,
            ]);

            $this->rejectRequest($request, $user, $reason);

            if ($request->workflowRequest) {
                $request->workflowRequest->update([
                    'status' => 'rejected',
                    'decided_by' => $user->id,
                    'decided_at' => now(),
                    'decision_notes' => $reason,
                ]);
            }

            try {
                ProjectActivityHistory::create([
                    'project_id' => $request->project_id,
                    'user_id' => $user->id,
                    'action_type' => 'unified_request_rejected',
                    'notes' => "تم رفض الطلب الموحد {$request->request_number}: {$reason}",
                    'metadata' => ['unified_request_id' => $request->id, 'step_id' => $activeStep->id],
                ]);
            } catch (\Throwable $e) {
                \Log::info('Activity history note: '.$e->getMessage());
            }

            return [
                'rejected_step' => $activeStep->fresh(),
                'message' => 'تم رفض الطلب وتوثيق أسباب الرفض بنجاح.',
            ];
        });
    }

    /**
     * Return the request for corrections or additional requirements.
     */
    public function returnStep(ProjectUnifiedRequest $request, User $user, string $actionRequired): array
    {
        return DB::transaction(function () use ($request, $user, $actionRequired) {
            $activeStep = $request->activeApprovalStep();
            if (! $activeStep) {
                throw ValidationException::withMessages([
                    'step' => 'لا توجد مرحلة نشطة لإعادتها.',
                ]);
            }

            if (! $user->isAdmin() && ! $this->approvalService->canUserActOnStep($user, $activeStep, 'return')) {
                abort(403, 'غير مصرح لك بإعادة هذا الطلب.');
            }

            if (! $activeStep->isActionAllowed('return')) {
                throw ValidationException::withMessages([
                    'action' => 'إجراء الإعادة للاستكمال غير متاح في هذه المرحلة وفقاً لقواعد مسار الاعتماد.',
                ]);
            }

            $activeStep->update([
                'status' => 'requires_action',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'notes' => $actionRequired,
                'required_action' => $actionRequired,
            ]);

            if ($request->paymentObligation) {
                $request->paymentObligation->update(['status' => 'draft']);
            }

            $request->update([
                'status' => ProjectUnifiedRequest::STATUS_DRAFT,
                'description' => ($request->description ? $request->description."\n" : '')."إجراء مطلوب: {$actionRequired}",
            ]);

            try {
                ProjectActivityHistory::create([
                    'project_id' => $request->project_id,
                    'user_id' => $user->id,
                    'action_type' => 'unified_request_returned',
                    'notes' => "تمت إعادة الطلب الموحد {$request->request_number} لاستكمال النواقص: {$actionRequired}",
                    'metadata' => ['unified_request_id' => $request->id, 'step_id' => $activeStep->id],
                ]);
            } catch (\Throwable $e) {
                \Log::info('Activity history note: '.$e->getMessage());
            }

            return [
                'returned_step' => $activeStep->fresh(),
                'message' => 'تمت إعادة الطلب بنجاح وطلب استكمال الإجراءات.',
            ];
        });
    }
}

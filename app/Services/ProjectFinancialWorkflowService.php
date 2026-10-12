<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Exceptions\InvalidWorkflowTransitionException;
use App\Exceptions\WorkflowValidationException;
use App\Models\PreliminaryProcedureExecution;
use App\Models\Project;
use App\Models\ProjectClearanceRequest;
use App\Models\ProjectClearanceRequestItem;
use App\Models\ProjectDisbursementPlan;
use App\Models\ProjectDisbursementPlanDetail;
use App\Models\ProjectExecution;
use App\Models\ProjectInstallmentAggregation;
use App\Models\ProjectInstallmentPaymentObligation;
use App\Models\ProjectPaymentRequest;
use App\Models\ProjectUnifiedRequest;
use App\Models\User;
use ArPHP\I18N\Arabic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectFinancialWorkflowService
{
    public function __construct(
        protected FrappeAPIService $frappeApiService,
        protected ApprovalService $approvalService
    ) {}

    public function executionBlockReason(Project $project): ?string
    {
        if (! $project->uses_disbursement_workflow) {
            return null;
        }

        if (! in_array($project->status, [ProjectStatus::InExecution->value, 'in_progress'], true)) {
            return 'Project execution is unavailable until final approval and the first installment disbursement are complete.';
        }

        $plan = $project->disbursementPlan;
        if (! $plan || ! in_array($plan->status, ['approved', 'completed'], true)) {
            return 'Before starting project execution, you must enter the project disbursement installments.';
        }

        if (! $project->active_disbursed_installment) {
            return 'There is no currently available disbursed installment for project execution.';
        }

        return null;
    }

    // =========================================================================
    // 1. INSTALLMENT FINITE STATE MACHINE (FSM)
    // =========================================================================

    /**
     * Allowed status transitions for ProjectDisbursementPlanDetail
     */
    protected array $allowedTransitions = [
        'planned' => ['payment_requested', 'disbursing'],
        'payment_requested' => ['payment_approved', 'planned'], // planned on reject/cancel
        'payment_approved' => ['disbursing'],
        'disbursing' => ['disbursed', 'disbursing_failed'],
        'disbursing_failed' => ['disbursing'],
        'disbursed' => ['clearance_requested'],
        'clearance_requested' => ['cleared', 'disbursed'], // disbursed on reject/cancel
        'cleared' => [], // terminal for this installment
    ];

    /**
     * Transition installment status strictly via state machine
     *
     * @throws InvalidWorkflowTransitionException
     */
    public function transitionInstallmentState(ProjectDisbursementPlanDetail $detail, string $toState): void
    {
        $currentState = $detail->status;

        if ($currentState === $toState) {
            return;
        }

        $validNextStates = $this->allowedTransitions[$currentState] ?? [];

        if (! in_array($toState, $validNextStates, true)) {
            throw new InvalidWorkflowTransitionException(
                "انتقال غير صالح لحالة الدفعة: لا يمكن الانتقال من '{$currentState}' إلى '{$toState}'."
            );
        }

        $detail->update(['status' => $toState]);
    }

    // =========================================================================
    // 2. DISBURSEMENT PLAN CREATION & APPROVAL
    // =========================================================================

    /**
     * Create or update a project's disbursement plan draft
     *
     * @throws WorkflowValidationException
     */
    public function createOrUpdatePlan(Project $project, array $data, User $user): ProjectDisbursementPlan
    {
        if (! $project->uses_disbursement_workflow) {
            throw new WorkflowValidationException('هذا المشروع لا يستخدم مسار خطة الصرف.');
        }

        $existingPlan = $project->disbursementPlan;
        if ($existingPlan && in_array($existingPlan->status, ['approved', 'completed'], true)) {
            throw new WorkflowValidationException('خطة الصرف لهذا المشروع معتمدة بالفعل ولا يمكن إعادة إنشائها أو تعديلها.');
        }

        $projectTotalCost = (float) ($project->cost->total_cost ?? 0);
        if ($projectTotalCost <= 0) {
            throw new WorkflowValidationException('إجمالي تكلفة المشروع غير محدد أو صفر. يرجى إدخال التكلفة المالية للمشروع أولاً.');
        }

        $detailsData = $data['details'] ?? [];
        if (empty($detailsData) || ! is_array($detailsData)) {
            throw new WorkflowValidationException('يجب إضافة دفعة واحدة على الأقل في خطة الصرف.');
        }

        $calculatedTotal = 0.0;
        foreach ($detailsData as $index => $row) {
            $amount = (float) ($row['planned_amount'] ?? 0);
            if ($amount <= 0) {
                throw new WorkflowValidationException('مبلغ الدفعة في السطر رقم '.($index + 1).' يجب أن يكون أكبر من الصفر.');
            }
            if (empty(trim($row['disbursement_clearance_mechanism'] ?? ''))) {
                throw new WorkflowValidationException('آلية الصرف والإخلاء مطلوبة للدفعة رقم '.($index + 1).'.');
            }
            $calculatedTotal += $amount;
        }

        // Validate that sum equals project total cost strictly
        if (abs($calculatedTotal - $projectTotalCost) > 0.01) {
            $diff = abs($calculatedTotal - $projectTotalCost);
            $formattedTotal = number_format($projectTotalCost, 2);
            $formattedCalc = number_format($calculatedTotal, 2);
            $formattedDiff = number_format($diff, 2);
            throw new WorkflowValidationException(
                "إجمالي مبالغ الدفعات ({$formattedCalc}) لا يتطابق مع إجمالي تكلفة المشروع ({$formattedTotal}). الفارق: {$formattedDiff} ريال."
            );
        }

        return DB::transaction(function () use ($project, $projectTotalCost, $calculatedTotal, $detailsData, $data, $user, $existingPlan) {
            $plan = $existingPlan ?: new ProjectDisbursementPlan;
            $plan->project_id = $project->id;
            $plan->project_total_cost = $projectTotalCost;
            $plan->total_planned_amount = $calculatedTotal;
            $plan->total_disbursed_amount = 0.0;
            $plan->status = 'draft';
            $plan->notes = $data['notes'] ?? null;
            $plan->created_by = $user->id;
            $plan->save();

            // Clear old draft details if updating
            $plan->details()->delete();

            foreach ($detailsData as $idx => $row) {
                ProjectDisbursementPlanDetail::create([
                    'project_disbursement_plan_id' => $plan->id,
                    'sequence_number' => $idx + 1,
                    'funding_source_id' => $row['funding_source_id'] ?? null,
                    'planned_amount' => (float) $row['planned_amount'],
                    'disbursement_clearance_mechanism' => trim($row['disbursement_clearance_mechanism']),
                    'status' => 'planned',
                    'disbursed_amount' => 0.0,
                    'created_by' => $user->id,
                ]);
            }

            return $plan->load('details');
        });
    }

    /**
     * Approve disbursement plan and auto-trigger ERPNext disbursement for Detail #1
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function approvePlan(ProjectDisbursementPlan $plan, User $user): array
    {
        if ($plan->status !== 'draft') {
            throw new WorkflowValidationException('يمكن فقط اعتماد خطط الصرف التي في حالة مسودة.');
        }

        $project = $plan->project;
        $projectTotalCost = (float) ($project->cost->total_cost ?? 0);
        $totalPlanned = (float) $plan->details()->sum('planned_amount');

        if (abs($totalPlanned - $projectTotalCost) > 0.01) {
            throw new WorkflowValidationException('إجمالي مبالغ الدفعات لا يتطابق تماماً مع إجمالي تكلفة المشروع.');
        }

        // 1. Mark Plan Approved
        DB::transaction(function () use ($plan, $user, $projectTotalCost) {
            $plan->update([
                'status' => 'approved',
                'project_total_cost' => $projectTotalCost,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        });

        // 2. Automatically trigger disbursement for Installment #1
        $detail1 = $plan->details()->where('sequence_number', 1)->first();
        if (! $detail1) {
            throw new WorkflowValidationException('لم يتم العثور على الدفعة الأولى في خطة الصرف.');
        }

        $disburseResult = $this->executeDisbursementForInstallment($detail1);

        return [
            'success' => true,
            'plan' => $plan->fresh(['details']),
            'disbursement' => $disburseResult,
            'message' => 'تم اعتماد خطة الصرف بنجاح وبدء معالجة صرف الدفعة الأولى.',
        ];
    }

    // =========================================================================
    // 3. TWO-PHASE COMMIT PROTOCOL FOR ERPNEXT DISBURSEMENT
    // =========================================================================

    /**
     * Execute disbursement for an installment with two-phase commit decoupling HTTP call
     */
    public function executeDisbursementForInstallment(ProjectDisbursementPlanDetail $detail): array
    {
        if (! (bool) config('external.erpnext.payment_entry_writes_enabled', false)) {
            return [
                'success' => false,
                'status' => 'writes_disabled',
                'error' => 'ERPNext Payment Entry write operations are disabled in configuration.',
                'message' => 'مزامنة قيود الصرف في ERPNext معطلة في الإعدادات. لم يتم صرف الدفعة مالياً.',
            ];
        }

        $project = $detail->plan->project;

        // --- Transaction A: Lock & Set Disbursing ---
        DB::transaction(function () use ($detail, $project) {
            $lockedDetail = ProjectDisbursementPlanDetail::where('id', $detail->id)->lockForUpdate()->first();

            if (! in_array($lockedDetail->status, ['planned', 'payment_requested', 'payment_approved', 'disbursing_failed'], true)) {
                throw new InvalidWorkflowTransitionException(
                    "لا يمكن صرف الدفعة في حالتها الحالية ({$lockedDetail->status})."
                );
            }

            if (! $lockedDetail->erpnext_payment_reference) {
                $ref = "DISB-P{$project->id}-D{$lockedDetail->id}-S{$lockedDetail->sequence_number}";
                $lockedDetail->erpnext_payment_reference = $ref;
            }

            $this->transitionInstallmentState($lockedDetail, 'disbursing');
        });

        $detail->refresh();

        // --- External HTTP Call (OUTSIDE DB Transaction) ---
        $erpResult = ['success' => false, 'payment_id' => null, 'docstatus' => 0, 'error' => null];
        try {
            $erpResult = app(FrappeAPIService::class)->createDisbursementPaymentEntry($detail);
        } catch (\Throwable $e) {
            Log::error("Exception calling ERPNext for disbursement detail {$detail->id}: ".$e->getMessage());
            $erpResult = [
                'success' => false,
                'payment_id' => null,
                'docstatus' => 0,
                'error' => $e->getMessage(),
            ];
        }

        // --- Transaction B or C: Finalize State based on ERPNext Result ---
        if (($erpResult['success'] ?? false) && (int) ($erpResult['docstatus'] ?? 0) === 1) {
            // Transaction B: SUCCESS
            DB::transaction(function () use ($detail, $erpResult, $project) {
                $lockedDetail = ProjectDisbursementPlanDetail::where('id', $detail->id)->lockForUpdate()->first();

                $lockedDetail->update([
                    'erpnext_payment_id' => $erpResult['payment_id'],
                    'disbursed_amount' => $lockedDetail->planned_amount,
                    'disbursed_at' => now(),
                    'status' => 'disbursed',
                ]);

                // Update Master cached sum
                $lockedDetail->plan->refreshCachedDisbursedAmount();

                // If Detail #1, transition project to InExecution and trigger empowerment sync
                if ((int) $lockedDetail->sequence_number === 1) {
                    $project->update([
                        'status' => ProjectStatus::InExecution->value,
                        'execution_started_at' => now(),
                    ]);

                    $this->approvalService->safelySyncProjectOnExecution($project);
                }
            });

            return [
                'success' => true,
                'status' => 'disbursed',
                'payment_id' => $erpResult['payment_id'],
                'message' => 'تم صرف الدفعة بنجاح وتأكيد قيد الصرف في ERPNext.',
            ];
        }

        // Transaction C: FAILURE
        DB::transaction(function () use ($detail) {
            $lockedDetail = ProjectDisbursementPlanDetail::where('id', $detail->id)->lockForUpdate()->first();

            $this->transitionInstallmentState($lockedDetail, 'disbursing_failed');
            $lockedDetail->update([
                'disbursed_amount' => 0.0,
            ]);
        });

        return [
            'success' => false,
            'status' => 'disbursing_failed',
            'error' => $erpResult['error'] ?? 'فشل الاتصال أو اعتماد قيد الصرف في ERPNext.',
            'message' => 'تعذر إتمام صرف الدفعة في ERPNext. يمكنك إعادة المحاولة بعد التحقق من الاتصال والإعدادات.',
        ];
    }

    /**
     * Retry disbursement for a failed installment
     *
     * @throws WorkflowValidationException
     */
    public function retryDisbursement(ProjectDisbursementPlanDetail $detail, User $user): array
    {
        if ($detail->status !== 'disbursing_failed') {
            throw new WorkflowValidationException('يمكن إعادة محاولة الصرف فقط للدفعات التي فشل صرفها.');
        }

        return $this->executeDisbursementForInstallment($detail);
    }

    // =========================================================================
    // 4. PAYMENT REQUESTS (SUBSEQUENT INSTALLMENTS)
    // =========================================================================

    /**
     * Submit a payment request for the next planned installment
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function submitPaymentRequest(
        Project $project,
        ProjectDisbursementPlanDetail $detail,
        array $data,
        User $user,
        ?int $exceptUnifiedRequestId = null
    ): ProjectPaymentRequest {
        $plan = $project->disbursementPlan;
        if (! $plan || $plan->status !== 'approved') {
            throw new WorkflowValidationException('لا يمكن تقديم طلب صرف؛ خطة الصرف غير معتمدة أو غير موجودة.');
        }

        if ((int) $detail->project_disbursement_plan_id !== (int) $plan->id) {
            throw new WorkflowValidationException('الدفعة المحددة لا تنتمي لخطة صرف هذا المشروع.');
        }

        if ((int) $detail->sequence_number <= 1) {
            throw new WorkflowValidationException('الدفعة الأولى تصرف تلقائياً عند اعتماد خطة الصرف ولا تحتاج لطلب صرف يدوي.');
        }

        if ($detail->status !== 'planned') {
            throw new WorkflowValidationException("حالة الدفعة الحالية ({$detail->status}) لا تسمح بتقديم طلب صرف.");
        }

        // Preceding installment must be CLEARED or DISBURSED
        $prevDetail = $plan->details()
            ->where('sequence_number', $detail->sequence_number - 1)
            ->first();

        if (! $prevDetail || ! in_array($prevDetail->status, ['cleared', 'disbursed'], true)) {
            throw new WorkflowValidationException(
                "لا يمكن طلب صرف الدفعة رقم {$detail->sequence_number} قبل إخلاء أو صرف الدفعة السابقة رقم ".($detail->sequence_number - 1).' بالكامل.'
            );
        }

        // Mutex: No active pending payment request
        if ($detail->activePaymentRequest) {
            throw new WorkflowValidationException('يوجد طلب صرف قيد المراجعة بالفعل لهذه الدفعة.');
        }

        return DB::transaction(function () use ($project, $detail, $data, $user, $exceptUnifiedRequestId) {
            $lockedDetail = ProjectDisbursementPlanDetail::whereKey($detail->id)->lockForUpdate()->firstOrFail();
            if ($lockedDetail->status !== 'planned') {
                throw new WorkflowValidationException('تم حجز هذه الدفعة لطلب صرف آخر.');
            }

            $activeUnifiedRequest = ProjectUnifiedRequest::query()
                ->where('payment_disbursement_plan_detail_id', $lockedDetail->id)
                ->when($exceptUnifiedRequestId, fn ($q) => $q->where('id', '!=', $exceptUnifiedRequestId))
                ->whereIn('status', [
                    ProjectUnifiedRequest::STATUS_DRAFT,
                    ProjectUnifiedRequest::STATUS_CONFIRMED,
                    ProjectUnifiedRequest::STATUS_PENDING,
                ])
                ->exists();
            if ($activeUnifiedRequest) {
                throw new WorkflowValidationException('توجد مطالبة موحدة نشطة لهذه الدفعة بالفعل.');
            }

            $requestNumber = 'PAY-REQ-P'.$project->id.'-D'.$detail->sequence_number.'-'.strtoupper(Str::random(4));

            $paymentRequest = ProjectPaymentRequest::create([
                'project_id' => $project->id,
                'disbursement_plan_detail_id' => $detail->id,
                'request_number' => $requestNumber,
                'requested_amount' => $detail->planned_amount,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'attachment_path' => $data['attachment_path'] ?? null,
                'requested_by' => $user->id,
            ]);

            $reference = $lockedDetail->erpnext_payment_reference
                ?: "DISB-P{$project->id}-D{$lockedDetail->id}-S{$lockedDetail->sequence_number}";
            $lockedDetail->update(['erpnext_payment_reference' => $reference]);

            $obligation = ProjectInstallmentPaymentObligation::query()
                ->where('project_disbursement_plan_detail_id', $lockedDetail->id)
                ->lockForUpdate()
                ->first();
            if ($obligation && $obligation->erpnext_sync_status === 'submitted') {
                throw new WorkflowValidationException('تم تسجيل قيد محاسبي معتمد لهذه الدفعة بالفعل.');
            }
            if ($obligation) {
                $obligation->update([
                    'requestable_type' => $paymentRequest->getMorphClass(),
                    'requestable_id' => $paymentRequest->id,
                    'status' => 'reserved',
                ]);
            } else {
                ProjectInstallmentPaymentObligation::create([
                    'project_disbursement_plan_detail_id' => $lockedDetail->id,
                    'requestable_type' => $paymentRequest->getMorphClass(),
                    'requestable_id' => $paymentRequest->id,
                    'status' => 'reserved',
                    'erpnext_payment_reference' => $reference,
                    'erpnext_sync_status' => 'not_sent',
                ]);
            }

            $this->transitionInstallmentState($detail, 'payment_requested');

            return $paymentRequest;
        });
    }

    /**
     * Approve a pending payment request and trigger ERPNext disbursement
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function approvePaymentRequest(ProjectPaymentRequest $paymentRequest, User $user, ?string $notes = null): array
    {
        if ($paymentRequest->status !== 'pending') {
            throw new WorkflowValidationException('يمكن فقط اعتماد طلبات الصرف التي في حالة انتظار.');
        }

        $detail = $paymentRequest->installment;

        DB::transaction(function () use ($paymentRequest, $user, $detail) {
            $paymentRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->transitionInstallmentState($detail, 'payment_approved');
        });

        if (! config('external.erpnext.payment_entry_writes_enabled', false)) {
            return [
                'success' => true,
                'payment_request' => $paymentRequest->fresh(),
                'disbursement' => [
                    'success' => false,
                    'status' => 'awaiting_accounting_configuration',
                    'error' => 'ERPNext payment writes are disabled pending accounting mapping review.',
                ],
                'message' => 'تم اعتماد الطلب في Laravel. مزامنة ERPNext متوقفة حتى اعتماد إعدادات الحسابات.',
            ];
        }

        // Trigger ERPNext disbursement
        $disburseResult = $this->executeDisbursementForInstallment($detail);

        return [
            'success' => true,
            'payment_request' => $paymentRequest->fresh(),
            'disbursement' => $disburseResult,
            'message' => 'تمت الموافقة على طلب الصرف وجاري معالجة صرف الدفعة في ERPNext.',
        ];
    }

    /**
     * Reject a pending payment request and release installment back to planned
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function rejectPaymentRequest(ProjectPaymentRequest $paymentRequest, User $user, string $reason): ProjectPaymentRequest
    {
        if ($paymentRequest->status !== 'pending') {
            throw new WorkflowValidationException('يمكن فقط رفض طلبات الصرف التي في حالة انتظار.');
        }

        $trimmedReason = trim($reason);
        if (empty($trimmedReason)) {
            throw new WorkflowValidationException('سبب رفض طلب الصرف إلزامي.');
        }

        $detail = $paymentRequest->installment;

        return DB::transaction(function () use ($paymentRequest, $user, $trimmedReason, $detail) {
            $paymentRequest->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => $trimmedReason,
            ]);

            // Release installment back to planned for correction/resubmission
            $this->transitionInstallmentState($detail, 'planned');

            return $paymentRequest;
        });
    }

    /**
     * Cancel a pending payment request
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function cancelPaymentRequest(ProjectPaymentRequest $paymentRequest, User $user, ?string $reason = null): ProjectPaymentRequest
    {
        if ($paymentRequest->status !== 'pending') {
            throw new WorkflowValidationException('يمكن فقط إلغاء طلبات الصرف التي في حالة انتظار.');
        }

        $detail = $paymentRequest->installment;

        return DB::transaction(function () use ($paymentRequest, $user, $reason, $detail) {
            $paymentRequest->update([
                'status' => 'cancelled',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // Release installment back to planned
            $this->transitionInstallmentState($detail, 'planned');

            return $paymentRequest;
        });
    }

    // =========================================================================
    // 5. CLEARANCE REQUESTS & EXECUTION RELEASE
    // =========================================================================

    /**
     * Submit a clearance request for a disbursed installment
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function submitClearanceRequest(
        Project $project,
        ProjectDisbursementPlanDetail $detail,
        array $data,
        User $user
    ): ProjectClearanceRequest {
        if ($detail->status !== 'disbursed') {
            throw new WorkflowValidationException("لا يمكن تقديم طلب إخلاء لدفعة في حالة ({$detail->status}). يجب أن تكون الدفعة في حالة 'تم الصرف'.");
        }

        if ($detail->activeClearanceRequest) {
            throw new WorkflowValidationException('يوجد طلب إخلاء قيد المراجعة بالفعل لهذه الدفعة.');
        }

        $aggregation = ProjectInstallmentAggregation::where('project_disbursement_plan_detail_id', $detail->id)
            ->where('status', 'ready_for_clearance')
            ->first();
        if (! $aggregation) {
            $legacyExecutive = ProjectExecution::where('disbursement_plan_detail_id', $detail->id)->get();
            $legacyPreliminary = PreliminaryProcedureExecution::where('disbursement_plan_detail_id', $detail->id)->get();
            $legacyItems = $legacyExecutive->concat($legacyPreliminary);
            if ($legacyItems->isEmpty() || $legacyItems->contains(fn ($execution) => $execution->workflow_review_status !== null)) {
                throw new WorkflowValidationException('The installment must be manager reviewed and aggregated before clearance.');
            }
            $aggregation = ProjectInstallmentAggregation::create([
                'project_disbursement_plan_detail_id' => $detail->id,
                'status' => 'ready_for_clearance',
                'submitted_by' => $user->id,
                'payload' => [
                    'project_execution_ids' => $legacyExecutive->modelKeys(),
                    'preliminary_execution_ids' => $legacyPreliminary->modelKeys(),
                    'total_amount' => round((float) $legacyItems->sum('amount_spent'), 2),
                    'record_count' => $legacyItems->count(),
                    'legacy_compatibility' => true,
                ],
            ]);
        }

        $isLegacyAggregation = (bool) (($aggregation->payload ?? [])['legacy_compatibility'] ?? false);

        // Gather eligible execution items
        // Either explicitly passed in $data['item_ids'] or automatically select un-cleared items
        $snapshot = $aggregation->payload ?? [];
        $selectedExecIds = $snapshot['project_execution_ids'] ?? [];
        $selectedPrelimIds = $snapshot['preliminary_execution_ids'] ?? [];

        $projectExecQuery = ProjectExecution::where('project_id', $project->id)
            ->whereNull('clearance_request_id');
        if (! empty($selectedExecIds)) {
            $projectExecQuery->whereIn('id', $selectedExecIds);
        }
        $projectExecutions = $projectExecQuery->get();

        $prelimExecQuery = PreliminaryProcedureExecution::where('project_id', $project->id)
            ->whereNull('clearance_request_id');
        if (! empty($selectedPrelimIds)) {
            $prelimExecQuery->whereIn('id', $selectedPrelimIds);
        }
        $prelimExecutions = $prelimExecQuery->get();

        $allExecutions = $projectExecutions->concat($prelimExecutions);
        if ($allExecutions->isEmpty() || $allExecutions->contains(fn ($execution) => ($execution->workflow_review_status !== null && ($execution->workflow_review_status !== 'manager_approved' || $execution->approval_status !== 'approved')) || (int) $execution->disbursement_plan_detail_id !== (int) $detail->id)) {
            throw new WorkflowValidationException('Only manager-approved execution records from the aggregated installment may be cleared.');
        }

        if ($projectExecutions->isEmpty() && $prelimExecutions->isEmpty()) {
            throw new WorkflowValidationException('يجب تضمين سجل إنجاز تنفيذي أو تمهيدي واحد على الأقل لم يتم إخلاؤه مسبقاً لتقديم طلب الإخلاء.');
        }

        $totalClearedAmount = (float) $projectExecutions->sum('amount_spent') + (float) $prelimExecutions->sum('amount_spent');

        if ($totalClearedAmount <= 0) {
            throw new WorkflowValidationException('إجمالي المبلغ المراد إخلاؤه يجب أن يكون أكبر من الصفر.');
        }

        // Calculate average technical completion percentage
        $totalItems = $projectExecutions->count() + $prelimExecutions->count();
        $sumPct = (float) $projectExecutions->sum('completion_percentage') + (float) $prelimExecutions->sum('completion_percentage');
        $avgPct = $totalItems > 0 ? round($sumPct / $totalItems, 2) : 0.0;

        return DB::transaction(function () use ($project, $detail, $data, $user, $totalClearedAmount, $avgPct, $projectExecutions, $prelimExecutions, $aggregation, $isLegacyAggregation) {
            $requestNumber = 'CLR-REQ-P'.$project->id.'-D'.$detail->sequence_number.'-'.strtoupper(Str::random(4));

            $clearanceRequest = ProjectClearanceRequest::create([
                'project_id' => $project->id,
                'disbursement_plan_detail_id' => $detail->id,
                'request_number' => $requestNumber,
                'installment_disbursed_amount' => $detail->disbursed_amount,
                'total_cleared_amount' => $totalClearedAmount,
                'technical_progress_percentage' => $data['technical_progress_percentage'] ?? $avgPct,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'attachment_path' => $data['attachment_path'] ?? null,
                'submitted_by' => $user->id,
            ]);

            // Link executions and create immutable snapshots
            foreach ($projectExecutions as $pe) {
                $pe->update([
                    'clearance_request_id' => $clearanceRequest->id,
                    'disbursement_plan_detail_id' => $detail->id,
                ]);

                ProjectClearanceRequestItem::create([
                    'clearance_request_id' => $clearanceRequest->id,
                    'execution_type' => 'project_execution',
                    'execution_id' => $pe->id,
                    'amount_spent' => $pe->amount_spent,
                    'completion_percentage' => $pe->completion_percentage,
                ]);
            }

            foreach ($prelimExecutions as $ppe) {
                $ppe->update([
                    'clearance_request_id' => $clearanceRequest->id,
                    'disbursement_plan_detail_id' => $detail->id,
                ]);

                ProjectClearanceRequestItem::create([
                    'clearance_request_id' => $clearanceRequest->id,
                    'execution_type' => 'preliminary_execution',
                    'execution_id' => $ppe->id,
                    'amount_spent' => $ppe->amount_spent,
                    'completion_percentage' => $ppe->completion_percentage,
                ]);
            }

            // Transition installment to clearance_requested
            $this->transitionInstallmentState($detail, 'clearance_requested');
            $aggregation->update(['status' => 'clearance_submitted', 'clearance_request_id' => $clearanceRequest->id]);
            if (! $isLegacyAggregation && ! ($data['defer_workflow'] ?? false)) {
                app(ProjectWorkflowRequestService::class)->submit($project, 'clearance_request', [
                    'clearance_request_id' => $clearanceRequest->id,
                    'detail_id' => $detail->id,
                    'total_cleared_amount' => $totalClearedAmount,
                    'dedupe_key' => 'clearance-'.$clearanceRequest->id,
                ], $user, $clearanceRequest);
            }

            return $clearanceRequest->load('items');
        });
    }

    /**
     * Approve a clearance request: marks installment as cleared and checks plan completion
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function approveClearanceRequest(ProjectClearanceRequest $clearanceRequest, User $user, ?string $notes = null, bool $queueNextWorkflow = false): ProjectClearanceRequest
    {
        if ($clearanceRequest->status !== 'pending') {
            throw new WorkflowValidationException('يمكن فقط اعتماد طلبات الإخلاء التي في حالة انتظار.');
        }

        $detail = $clearanceRequest->installment;

        return DB::transaction(function () use ($clearanceRequest, $user, $detail, $queueNextWorkflow) {
            $clearanceRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $detail->update([
                'cleared_at' => now(),
                'cleared_by' => $user->id,
            ]);

            $this->transitionInstallmentState($detail, 'cleared');

            // Check if all plan details are now cleared -> Complete the Disbursement Plan
            $this->checkPlanCompletion($detail->plan);
            if ($queueNextWorkflow) {
                $nextDetail = $detail->plan->details()->where('sequence_number', '>', $detail->sequence_number)->orderBy('sequence_number')->first();
                if ($nextDetail) {
                    app(ProjectWorkflowRequestService::class)->submit($detail->plan->project, 'next_installment', [
                        'detail_id' => $nextDetail->id,
                        'notes' => 'Automatically submitted after clearance of installment '.$detail->sequence_number.'.',
                        'dedupe_key' => 'installment-'.$nextDetail->id,
                    ], $user);
                } elseif ($this->checkPlanCompletion($detail->plan)) {
                    app(ProjectWorkflowRequestService::class)->submit($detail->plan->project, 'project_closure', [
                        'detail_id' => $detail->id,
                        'dedupe_key' => 'project-closure-'.$detail->plan->project_id,
                    ], $user);
                }
            }

            return $clearanceRequest;
        });
    }

    /**
     * Reject a clearance request: releases execution records so they can be corrected and resubmitted
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function rejectClearanceRequest(ProjectClearanceRequest $clearanceRequest, User $user, string $reason): ProjectClearanceRequest
    {
        if ($clearanceRequest->status !== 'pending') {
            throw new WorkflowValidationException('يمكن فقط رفض طلبات الإخلاء التي في حالة انتظار.');
        }

        $trimmedReason = trim($reason);
        if (empty($trimmedReason)) {
            throw new WorkflowValidationException('سبب رفض طلب الإخلاء إلزامي.');
        }

        $detail = $clearanceRequest->installment;

        return DB::transaction(function () use ($clearanceRequest, $user, $trimmedReason, $detail) {
            $clearanceRequest->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => $trimmedReason,
            ]);

            // Release executions: clearance_request_id = NULL
            ProjectExecution::where('clearance_request_id', $clearanceRequest->id)
                ->update(['clearance_request_id' => null]);

            PreliminaryProcedureExecution::where('clearance_request_id', $clearanceRequest->id)
                ->update(['clearance_request_id' => null]);

            // Restore installment status back to disbursed (ceiling and disbursed_amount intact)
            $this->transitionInstallmentState($detail, 'disbursed');
            ProjectInstallmentAggregation::where('clearance_request_id', $clearanceRequest->id)
                ->update(['status' => 'ready_for_clearance', 'clearance_request_id' => null]);

            return $clearanceRequest;
        });
    }

    /**
     * Cancel a clearance request: releases execution records so they can be resubmitted
     *
     * @throws WorkflowValidationException
     * @throws InvalidWorkflowTransitionException
     */
    public function cancelClearanceRequest(ProjectClearanceRequest $clearanceRequest, User $user, ?string $reason = null): ProjectClearanceRequest
    {
        if ($clearanceRequest->status !== 'pending') {
            throw new WorkflowValidationException('يمكن فقط إلغاء طلبات الإخلاء التي في حالة انتظار.');
        }

        $detail = $clearanceRequest->installment;

        return DB::transaction(function () use ($clearanceRequest, $user, $reason, $detail) {
            $clearanceRequest->update([
                'status' => 'cancelled',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // Release executions
            ProjectExecution::where('clearance_request_id', $clearanceRequest->id)
                ->update(['clearance_request_id' => null]);

            PreliminaryProcedureExecution::where('clearance_request_id', $clearanceRequest->id)
                ->update(['clearance_request_id' => null]);

            // Restore installment status back to disbursed
            $this->transitionInstallmentState($detail, 'disbursed');

            return $clearanceRequest;
        });
    }

    // =========================================================================
    // 6. PLAN COMPLETION CHECK
    // =========================================================================

    /**
     * Check if all installments are cleared, completing the plan
     */
    public function checkPlanCompletion(ProjectDisbursementPlan $plan): bool
    {
        $unclearedCount = $plan->details()->where('status', '!=', 'cleared')->count();

        if ($unclearedCount === 0) {
            $plan->update(['status' => 'completed']);
            Log::info("Project disbursement plan {$plan->id} for project {$plan->project_id} successfully marked as completed.");

            return true;
        }

        return false;
    }

    // =========================================================================
    // 7. ARABIC TAFQEET HELPER
    // =========================================================================

    /**
     * Convert numeric amount to Arabic words
     */
    public function toArabicWords(float|int $amount): string
    {
        $intPart = (int) floor($amount);
        $fraction = (int) round(($amount - $intPart) * 100);

        $text = '';
        if (function_exists('numberToArabicText')) {
            $text = numberToArabicText($intPart);
        } else {
            try {
                $arabic = new Arabic;
                $text = $arabic->int2str($intPart);
            } catch (\Throwable $e) {
                $text = (string) $intPart;
            }
        }

        $result = $text.' ريال يمني';
        if ($fraction > 0) {
            $fracText = function_exists('numberToArabicText') ? numberToArabicText($fraction) : (string) $fraction;
            $result .= ' و'.$fracText.' فلساً';
        }

        return $result.' فقط لا غير.';
    }
}

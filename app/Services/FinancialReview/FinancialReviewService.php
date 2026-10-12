<?php

namespace App\Services\FinancialReview;

use App\Models\ExecutiveActionCost;
use App\Models\ExecutiveActivity;
use App\Models\ExecutiveActivityAction;
use App\Models\PreliminaryActivity;
use App\Models\PreliminaryCost;
use App\Models\PreliminaryProcedure;
use App\Models\Project;
use App\Models\ProjectActivityHistory;
use App\Models\ProjectApproval;
use App\Models\ProjectCost;
use App\Traits\HandlesItemNotesAndHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FinancialReviewService
{
    use HandlesItemNotesAndHistory;

    /**
     * Resolve the active Financial Review ProjectApproval for the project.
     */
    public function resolveActiveFinancialApproval(Project $project, ?int $expectedApprovalId = null): ?ProjectApproval
    {
        $query = ProjectApproval::with(['entity', 'authority', 'reviewedByUser'])
            ->where('project_id', $project->id)
            ->where('phase', 'financial_review')
            ->where('is_active', true)
            ->where('is_completed', false);

        if ($expectedApprovalId !== null) {
            $query->where('id', $expectedApprovalId);
        }

        return $query->first();
    }

    /**
     * Check if a ProjectApproval represents a financial review stage.
     */
    public function isFinancialReviewApproval(ProjectApproval $approval): bool
    {
        return $approval->phase === 'financial_review';
    }

    /**
     * Update financial data (PreliminaryCost and ExecutiveActionCost only).
     * Strictly disallows any technical fields or weight updates.
     */
    public function updateFinancialData(
        Project $project,
        ?ProjectApproval $approval,
        array $preliminaryCosts = [],
        array $executiveCosts = [],
        string $source = 'laravel_ui',
        ?string $externalUserId = null,
        ?string $externalUserName = null
    ): array {
        WorkflowActorContext::set($source, $externalUserId, $externalUserName);

        try {
            return DB::transaction(function () use ($project, $approval, $preliminaryCosts, $executiveCosts) {
                if ($approval) {
                    $this->lockActiveApproval($project, $approval->id);
                }

                $updatedPreliminary = [];
                $updatedExecutive = [];

                // 1. Update preliminary costs (quantity, amount ONLY)
                foreach ($preliminaryCosts as $costId => $data) {
                    $cost = PreliminaryCost::withoutGlobalScopes()
                        ->where('project_id', $project->id)
                        ->find($costId);

                    if ($cost) {
                        $qty = isset($data['quantity']) ? (float) $data['quantity'] : (float) $cost->quantity;
                        $amount = isset($data['amount']) ? (float) $data['amount'] : (float) $cost->amount;

                        $cost->fill([
                            'quantity' => $qty,
                            'amount' => $amount,
                            'total' => $qty * $amount,
                        ]);

                        $this->logModelChangesIfDirty($cost, $project, 'financial_review', 'PreliminaryCost', $approval);
                        $updatedPreliminary[] = $cost->fresh();
                    }
                }

                // 2. Update executive action costs (quantity, amount ONLY)
                foreach ($executiveCosts as $costId => $data) {
                    $cost = ExecutiveActionCost::withoutGlobalScopes()
                        ->where('project_id', $project->id)
                        ->find($costId);

                    if ($cost) {
                        $qty = isset($data['quantity']) ? (float) $data['quantity'] : (float) $cost->quantity;
                        $amount = isset($data['amount']) ? (float) $data['amount'] : (float) $cost->amount;

                        $cost->fill([
                            'quantity' => $qty,
                            'amount' => $amount,
                            'total' => $qty * $amount,
                        ]);

                        $this->logModelChangesIfDirty($cost, $project, 'financial_review', 'ExecutiveActionCost', $approval);
                        $updatedExecutive[] = $cost->fresh();
                    }
                }

                // 3. Recalculate project total cost
                $preliminaryTotal = (float) PreliminaryCost::withoutGlobalScopes()
                    ->where('project_id', $project->id)
                    ->sum('total');

                $executiveTotal = (float) ExecutiveActionCost::withoutGlobalScopes()
                    ->where('project_id', $project->id)
                    ->sum('total');

                $newTotalCost = $preliminaryTotal + $executiveTotal;

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

                return [
                    'preliminary_total' => $preliminaryTotal,
                    'executive_total' => $executiveTotal,
                    'total_cost' => $newTotalCost,
                    'updated_preliminary_count' => count($updatedPreliminary),
                    'updated_executive_count' => count($updatedExecutive),
                ];
            });
        } finally {
            WorkflowActorContext::clear();
        }
    }

    /**
     * Add a financial review note to a related project item.
     */
    public function addNote(
        Project $project,
        ?ProjectApproval $approval,
        string $relatedModel,
        int $relatedId,
        string $noteText,
        string $source = 'laravel_ui',
        ?string $externalUserId = null,
        ?string $externalUserName = null
    ): ProjectActivityHistory {
        WorkflowActorContext::set($source, $externalUserId, $externalUserName);

        try {
            $allowedModels = [
                'PreliminaryActivity' => PreliminaryActivity::class,
                'PreliminaryProcedure' => PreliminaryProcedure::class,
                'PreliminaryCost' => PreliminaryCost::class,
                'ExecutiveActivity' => ExecutiveActivity::class,
                'ExecutiveActivityAction' => ExecutiveActivityAction::class,
                'ExecutiveActionCost' => ExecutiveActionCost::class,
            ];

            if (! array_key_exists($relatedModel, $allowedModels)) {
                throw new InvalidArgumentException("Model [{$relatedModel}] is not supported for financial review notes.");
            }

            $modelClass = $allowedModels[$relatedModel];

            // Verify the related record belongs to this project
            $itemExists = false;
            if (in_array($relatedModel, ['PreliminaryActivity', 'PreliminaryProcedure', 'PreliminaryCost', 'ExecutiveActivity', 'ExecutiveActionCost'], true)) {
                $itemExists = $modelClass::withoutGlobalScopes()
                    ->where('project_id', $project->id)
                    ->where('id', $relatedId)
                    ->exists();
            } elseif ($relatedModel === 'ExecutiveActivityAction') {
                $itemExists = ExecutiveActivityAction::withoutGlobalScopes()
                    ->where('id', $relatedId)
                    ->where(function ($query) use ($project) {
                        $query->where('project_id', $project->id)
                            ->orWhereIn('executive_activity_id', ExecutiveActivity::withoutGlobalScopes()
                                ->where('project_id', $project->id)
                                ->select('id'));
                    })
                    ->exists();
            }

            if (! $itemExists) {
                throw new InvalidArgumentException("The requested [{$relatedModel}] with ID [{$relatedId}] does not belong to Project #{$project->id}.");
            }

            $metadata = [
                'related_model' => $relatedModel,
                'related_id' => $relatedId,
                'review_type' => 'financial_review',
            ];

            if ($approval) {
                $metadata['entity_id'] = $approval->entity_id ?? $approval->authority_id ?? null;
                $metadata['entity_name'] = $approval->entity?->name
                    ?? $approval->authority?->agency_name
                    ?? $approval->authority?->name
                    ?? null;
                $metadata['stage_code'] = $approval->drop
                    ?? $approval->stage_key
                    ?? $approval->stage?->code
                    ?? $approval->stage?->name
                    ?? null;
            }

            if ($actor = WorkflowActorContext::get()) {
                $metadata = array_merge($metadata, array_filter($actor));
            }

            return DB::transaction(function () use ($approval, $project, $noteText, $metadata): ProjectActivityHistory {
                if ($approval) {
                    $this->lockActiveApproval($project, $approval->id);
                }

                return ProjectActivityHistory::create([
                    'project_id' => $project->id,
                    'user_id' => auth()->id() ?? $project->created_by_user_id ?? 1,
                    'action_type' => 'financial_review_item_note',
                    'notes' => trim($noteText),
                    'metadata' => $metadata,
                ]);
            });
        } finally {
            WorkflowActorContext::clear();
        }
    }

    private function lockActiveApproval(Project $project, int $approvalId): ProjectApproval
    {
        $approval = ProjectApproval::query()
            ->whereKey($approvalId)
            ->where('project_id', $project->id)
            ->where('phase', 'financial_review')
            ->where('is_active', true)
            ->where('is_completed', false)
            ->lockForUpdate()
            ->first();

        if (! $approval) {
            throw new ConflictHttpException('The Financial Review assignment is stale or no longer active.');
        }

        return $approval;
    }
}

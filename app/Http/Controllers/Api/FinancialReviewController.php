<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReturnTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FinancialReview\AddFinancialReviewNoteRequest;
use App\Http\Requests\Api\FinancialReview\FinancialReviewActionRequest;
use App\Http\Requests\Api\FinancialReview\ReturnFinancialReviewRequest;
use App\Http\Requests\Api\FinancialReview\UpdateFinancialReviewRequest;
use App\Http\Resources\FinancialReviewResource;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\User;
use App\Services\FinancialReview\FinancialReviewService;
use App\Services\FinancialReview\FinancialReviewWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FinancialReviewController extends Controller
{
    public function __construct(
        private readonly FinancialReviewService $financialReviewService,
        private readonly FinancialReviewWorkflowService $workflowService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = ProjectApproval::query()
            ->where('phase', 'financial_review')
            ->where('is_active', true)
            ->where('is_completed', false)
            ->with($this->resourceRelations())
            ->orderBy('step_order')
            ->orderBy('id');

        if ($user) {
            $this->applyUserDataScope($query, $user);
        } else {
            $query->whereRaw('1 = 0');
        }

        $approvals = $query->paginate(50);

        return FinancialReviewResource::collection($approvals);
    }

    public function show(Request $request, Project $project): FinancialReviewResource
    {
        $approval = $this->activeApproval($project);
        $this->authorizeProjectApprovalScope($request->user(), $project, $approval, 'read');

        return new FinancialReviewResource($approval->load($this->resourceRelations()));
    }

    public function update(UpdateFinancialReviewRequest $request, Project $project): JsonResponse
    {
        $approval = $this->workflowService->ensureActiveFinancialReviewMatches(
            $project,
            $request->integer('approval_id'),
        );
        $this->authorizeProjectApprovalScope($request->user(), $project, $approval, 'update');

        $validated = $request->validated();
        $result = $this->financialReviewService->updateFinancialData(
            $project,
            $approval,
            $validated['preliminary_costs'] ?? [],
            $validated['executive_costs'] ?? [],
            'erpnext',
            (string) $request->user()->id,
            $request->user()->name,
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function addNote(AddFinancialReviewNoteRequest $request, Project $project): JsonResponse
    {
        $approval = $this->workflowService->ensureActiveFinancialReviewMatches(
            $project,
            $request->integer('approval_id'),
        );
        $this->authorizeProjectApprovalScope($request->user(), $project, $approval, 'update');

        $validated = $request->validated();

        try {
            $note = $this->financialReviewService->addNote(
                $project,
                $approval,
                $validated['related_model'],
                $validated['related_id'],
                $validated['note'],
                'erpnext',
                (string) $request->user()->id,
                $request->user()->name,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => ['note_id' => $note->id]], 201);
    }

    public function approve(FinancialReviewActionRequest $request, Project $project): JsonResponse
    {
        $approval = $this->workflowService->ensureActiveFinancialReviewMatches(
            $project,
            $request->integer('approval_id'),
        );
        $this->authorizeProjectApprovalScope($request->user(), $project, $approval, 'approve');

        return response()->json($this->workflowService->approve(
            $project,
            $request->user(),
            $request->integer('approval_id'),
            $request->validated('note'),
            source: 'erpnext',
            externalUserId: (string) $request->user()->id,
            externalUserName: $request->user()->name,
        ));
    }

    public function reject(FinancialReviewActionRequest $request, Project $project): JsonResponse
    {
        $approval = $this->workflowService->ensureActiveFinancialReviewMatches(
            $project,
            $request->integer('approval_id'),
        );
        $this->authorizeProjectApprovalScope($request->user(), $project, $approval, 'reject');

        return response()->json($this->workflowService->reject(
            $project,
            $request->user(),
            $request->integer('approval_id'),
            $request->string('note')->toString(),
            source: 'erpnext',
            externalUserId: (string) $request->user()->id,
            externalUserName: $request->user()->name,
        ));
    }

    public function return(ReturnFinancialReviewRequest $request, Project $project): JsonResponse
    {
        $approval = $this->workflowService->ensureActiveFinancialReviewMatches(
            $project,
            $request->integer('approval_id'),
        );
        $this->authorizeProjectApprovalScope($request->user(), $project, $approval, 'return');

        return response()->json($this->workflowService->return(
            $project,
            $request->user(),
            $request->integer('approval_id'),
            $request->string('note')->toString(),
            ReturnTarget::from($request->string('target')->toString()),
            source: 'erpnext',
            externalUserId: (string) $request->user()->id,
            externalUserName: $request->user()->name,
        ));
    }

    private function activeApproval(Project $project): ProjectApproval
    {
        $approval = $this->financialReviewService->resolveActiveFinancialApproval($project);

        if (! $approval) {
            throw new ConflictHttpException('The project no longer has an active Financial Review assignment.');
        }

        return $approval;
    }

    private function applyUserDataScope($query, User $user): void
    {
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return;
        }

        if ($user->isInternal()) {
            $allowedEntityIds = $user->entity_id ? InternalEntity::getAllChildrenIds((int) $user->entity_id) : [];
            if (empty($allowedEntityIds) && $user->entity_id) {
                $allowedEntityIds = [(int) $user->entity_id];
            }

            if (! empty($allowedEntityIds)) {
                $query->whereIn('entity_id', $allowedEntityIds);
            } else {
                $query->whereRaw('1 = 0');
            }

            $query->where(function ($assignQ) use ($user) {
                $assignQ->where('assigned_user_id', $user->id)
                    ->orWhereNull('assigned_user_id')
                    ->orWhere('financial_reviewer_id', $user->id);
            });

            $query->whereHas('project', function ($pq) use ($user) {
                $pq->checkVisibility($user);
            });
        } elseif ($user->isExternal()) {
            if ($user->authority_id) {
                $query->where('authority_id', (int) $user->authority_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    private function authorizeProjectApprovalScope(?User $user, Project $project, ProjectApproval $approval, string $action = 'read'): void
    {
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return;
        }

        if ($user->isInternal()) {
            $allowedEntityIds = $user->entity_id ? InternalEntity::getAllChildrenIds((int) $user->entity_id) : [];
            if (empty($allowedEntityIds) && $user->entity_id) {
                $allowedEntityIds = [(int) $user->entity_id];
            }

            if (! $approval->entity_id || ! in_array((int) $approval->entity_id, $allowedEntityIds, true)) {
                abort(403, 'غير مصرح لك بالوصول إلى بيانات هذه المراجعة المالية.');
            }

            if ($approval->assigned_user_id && (int) $approval->assigned_user_id !== (int) $user->id) {
                abort(403, 'هذه المراجعة المالية مخصصة لمستخدم آخر.');
            }

            if ($approval->financial_reviewer_id && (int) $approval->financial_reviewer_id !== (int) $user->id) {
                abort(403, 'هذه المراجعة المالية مخصصة لمستخدم آخر.');
            }

            if (! $project->checkVisibility($user)) {
                abort(403, 'غير مصرح لك بالوصول إلى هذا المشروع.');
            }
        } elseif ($user->isExternal()) {
            if (! $approval->authority_id || (int) $approval->authority_id !== (int) $user->authority_id) {
                abort(403, 'غير مصرح لك بالوصول إلى بيانات هذه المراجعة المالية.');
            }
        } else {
            abort(403, 'غير مصرح لك بالوصول إلى بيانات هذه المراجعة المالية.');
        }
    }

    private function resourceRelations(): array
    {
        return [
            'entity',
            'authority',
            'project.cost',
            'project.preliminaryCosts',
            'project.executiveActionCosts',
        ];
    }
}

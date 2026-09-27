<?php

namespace App\Services;

use App\Enums\EntityResponsibilityType;
use App\Exceptions\ConfigurationException;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\User;

/**
 * Resolves which user is responsible for a given approval stage within an entity.
 *
 * Resolution Order (single source of truth – no permission fallback):
 *
 * 1. Snapshot in ProjectApproval
 *    If the approval step already has an explicit user ID stored
 *    (technical_reviewer_id / financial_reviewer_id / assigned_user_id),
 *    that snapshot takes precedence. This covers in-flight workflows that
 *    were created before a stage configuration change.
 *
 * 2. Configured Stage Responsible User (entity_approval_stages)
 *    Look up the entity_approval_stages row for the given entity + stage.
 *    The responsible_user_id stored there is the definitive configured user.
 *    - If the user is inactive or from a different entity → NoResponsibleUser.
 *    - If the stage row exists but responsible_user_id is NULL → NoResponsibleUser.
 *
 * 3. Stage not enabled for entity → returns null (caller skips stage creation).
 *
 * There is NO permission-based fallback.
 */
class ResponsibilityResolver
{
    /**
     * Resolve the responsible user for the given entity + stage combination.
     *
     * @param  Project|null  $project  (unused currently, kept for API compatibility)
     * @param  ProjectApproval|null  $approvalStep  When provided, snapshot fields are checked first.
     */
    public function resolveResponsibleUser(
        InternalEntity|int|null $entity,
        EntityResponsibilityType|string|null $responsibilityType,
        ?Project $project = null,
        ?ProjectApproval $approvalStep = null
    ): ResponsibilityResolution {
        $resolvedType = $this->normalizeResponsibilityType($responsibilityType, $approvalStep);
        if (! $resolvedType) {
            return new ResponsibilityResolution(
                null,
                ResponsibilityResolution::NoResponsibleUser,
                'type_resolution',
                'Unable to resolve responsibility type.'
            );
        }

        $resolvedEntity = $this->normalizeEntity($entity, $approvalStep);
        if (! $resolvedEntity) {
            return new ResponsibilityResolution(
                null,
                ResponsibilityResolution::NoResponsibleUser,
                'entity_resolution',
                'Unable to resolve entity.'
            );
        }

        // 1. Snapshot in ProjectApproval (in-flight workflows)
        $snapshotUser = $this->resolveFromSnapshot($approvalStep, $resolvedType);
        if ($snapshotUser !== null) {
            return $this->buildResolution($snapshotUser, $resolvedEntity, 'approval_snapshot');
        }

        // 2. Configured Stage Responsible User
        $stageConfig = EntityApprovalStage::where('entity_id', $resolvedEntity->id)
            ->where(function($q) use ($resolvedType) {
                $q->where('stage_key', $resolvedType)
                  ->orWhere('stage', $resolvedType);
            })
            ->where('is_active', true)
            ->with('responsibleUser')
            ->first();

        if (! $stageConfig) {
            // Stage is not enabled for this entity – not a configuration error,
            // just means the stage should be skipped during workflow generation.
            return new ResponsibilityResolution(
                null,
                ResponsibilityResolution::NoResponsibleUser,
                'stage_not_configured',
                "Stage [{$resolvedType}] is not enabled for entity [{$resolvedEntity->id}]."
            );
        }

        // Stage is enabled – responsible user MUST be set and valid.
        $configuredUser = $stageConfig->responsibleUser;

        if (! $configuredUser) {
            return new ResponsibilityResolution(
                null,
                ResponsibilityResolution::NoResponsibleUser,
                'entity_stage_config',
                "Stage [{$resolvedType}] is enabled for entity [{$resolvedEntity->id}] but no responsible user is configured."
            );
        }

        return $this->buildResolution($configuredUser, $resolvedEntity, 'entity_stage_config');
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    private function normalizeResponsibilityType(
        EntityResponsibilityType|string|null $responsibilityType,
        ?ProjectApproval $approvalStep
    ): ?string {
        if ($responsibilityType instanceof EntityResponsibilityType) {
            return $responsibilityType->value;
        }

        if (is_string($responsibilityType) && $responsibilityType !== '') {
            return $responsibilityType;
        }

        return $approvalStep?->stage_key ?? $approvalStep?->phase;
    }

    private function normalizeEntity(InternalEntity|int|null $entity, ?ProjectApproval $approvalStep): ?InternalEntity
    {
        if ($entity instanceof InternalEntity) {
            return $entity;
        }

        $entityId = $entity ?? $approvalStep?->entity_id;
        if (! $entityId) {
            return null;
        }

        return InternalEntity::withoutGlobalScopes()->find((int) $entityId);
    }

    /**
     * Check whether the ProjectApproval already has a snapshotted user ID for this stage.
     * This covers in-flight workflows where a stage config may have changed since creation.
     */
    private function resolveFromSnapshot(
        ?ProjectApproval $approvalStep,
        string $type
    ): ?User {
        if (! $approvalStep) {
            return null;
        }

        $userId = $approvalStep->assigned_user_id;

        // Fallback for legacy snapshot records
        if (! $userId && $type) {
            $userId = match ($type) {
                'technical_review' => $approvalStep->technical_reviewer_id ?? $approvalStep->technical_review_user_id ?? null,
                'financial_review' => $approvalStep->financial_reviewer_id ?? $approvalStep->financial_review_user_id ?? null,
                default => null,
            };
        }

        return $userId ? User::withoutGlobalScopes()->find((int) $userId) : null;
    }

    /**
     * Validate the candidate user and build a resolution result.
     * Guards:
     *   - User must be active (status === 'Active').
     *   - User must belong to the correct entity.
     */
    private function buildResolution(
        ?User $user,
        InternalEntity $entity,
        string $source
    ): ResponsibilityResolution {
        if (! $user) {
            return new ResponsibilityResolution(
                null,
                ResponsibilityResolution::NoResponsibleUser,
                $source,
                'Configured responsible user does not exist.'
            );
        }

        if ($user->status !== 'Active') {
            return new ResponsibilityResolution(
                null,
                ResponsibilityResolution::NoResponsibleUser,
                $source,
                "Configured responsible user [{$user->id}] is inactive."
            );
        }

        if ((int) $user->entity_id !== (int) $entity->id) {
            return new ResponsibilityResolution(
                null,
                ResponsibilityResolution::NoResponsibleUser,
                $source,
                "Configured responsible user [{$user->id}] belongs to a different entity."
            );
        }

        return new ResponsibilityResolution($user, ResponsibilityResolution::Resolved, $source);
    }

    /**
     * Validate all configured stages for an entity and throw ConfigurationException
     * if any enabled stage has no valid responsible user.
     *
     * Called before workflow generation to fail fast with a clear admin message.
     *
     * @throws ConfigurationException
     */
    public function assertEntityIsConfigured(InternalEntity $entity): void
    {
        $stages = EntityApprovalStage::where('entity_id', $entity->id)
            ->where('is_active', true)
            ->with('responsibleUser')
            ->ordered()
            ->get();

        $errors = [];
        foreach ($stages as $stage) {
            $stageKey = $stage->stage_key ?? $stage->stage;
            $resolution = $this->resolveResponsibleUser($entity, $stageKey);
            if (! $resolution->isResolved()) {
                $errors[] = "الجهة: {$entity->name} | المرحلة: " . ($stage->stage_name ?? $stageKey)
                    ." | المشكلة: {$resolution->reason}";
            }
        }

        if (! empty($errors)) {
            throw new ConfigurationException(implode("\n", $errors));
        }
    }
}

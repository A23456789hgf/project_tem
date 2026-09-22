<?php

namespace App\Services;

use App\Exceptions\ConfigurationException;
use App\Models\Authority;
use App\Models\Entity;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;

class EntityHierarchyService
{
    protected static $entityCache = [];

    protected static $chainCache = [];

    /**
     * Clear static caches. Required between tests to prevent stale data
     * from leaking across RefreshDatabase transactions.
     */
    public static function clearCache(): void
    {
        static::$entityCache = [];
        static::$chainCache = [];
    }

    /**
     * Find an entity by its name (unique identifier in users and projects)
     */
    public function findEntityByName(string $entityName): ?InternalEntity
    {
        if (isset(static::$entityCache[$entityName])) {
            return static::$entityCache[$entityName];
        }

        return static::$entityCache[$entityName] = InternalEntity::where('name', $entityName)->first();
    }

    /**
     * Build approval chain from current entity to top parent
     * Returns array of entity names in order from current entity up to root
     */
    public function buildApprovalChain(string $entityName): array
    {
        $chain = [];
        $current = InternalEntity::withoutGlobalScopes()->where('name', $entityName)->first();
        $visited = []; // Prevent infinite loops

        while ($current && ! isset($visited[$current->id])) {
            $chain[] = [
                'id' => $current->id,
                'name' => $current->name,
            ];
            $visited[$current->id] = true;

            // Move up to parent without global scopes
            $current = $current->parent_id
                ? InternalEntity::withoutGlobalScopes()->find($current->parent_id)
                : null;
        }

        return $chain;
    }

    /**
     * Build approval chain in reverse order (from top parent down to current entity)
     */
    public function buildApprovalChainReverse(string $entityName): array
    {
        return array_reverse($this->buildApprovalChain($entityName));
    }

    /**
     * Get parent entity name for a given entity
     */
    public function getParentEntityName(string $entityName): ?string
    {
        $entity = $this->findEntityByName($entityName);

        return $entity && $entity->parent ? $entity->parent->name : null;
    }

    /**
     * Get all children entity names for a given parent
     */
    public function getChildrenEntities(string $parentName): array
    {
        $parent = $this->findEntityByName($parentName);
        if (! $parent) {
            return [];
        }

        return $parent->children()->pluck('name')->toArray();
    }

    /**
     * Generate dynamic approval stages from entity hierarchy chain
     * Returns array of stages starting from user's entity to top, plus implementation stage
     */
    public function generateApprovalStagesFromChain(string $userEntityId): array
    {
        $chain = $this->buildApprovalChain($userEntityId);

        return $this->expandEntityChainIntoApprovalStages($chain);
    }

    /**
     * Generate approval stages from a selected entity's parent chain
     * Only includes the entity and its parents up to the root
     * Used to filter approval chain based on selected entity_id
     */
    public function generateApprovalStagesFromSelectedEntity(int $entityId): array
    {
        if (isset(static::$chainCache[$entityId])) {
            return static::$chainCache[$entityId];
        }

        $entity = InternalEntity::withoutGlobalScopes()->find($entityId);
        if (! $entity) {
            \Log::error('generateApprovalStagesFromSelectedEntity: Entity not found in internal_entities!', ['entityId' => $entityId]);
            $auth = Authority::find($entityId);
            if ($auth) {
                \Log::error('generateApprovalStagesFromSelectedEntity: Found in authorities!', ['name' => $auth->name]);
            }
            $ext = Entity::find($entityId);
            if ($ext) {
                \Log::error('generateApprovalStagesFromSelectedEntity: Found in entities!', ['name' => $ext->name]);
            }

            return [];
        }

        // Build chain from selected entity to root.
        // IMPORTANT: use withoutGlobalScopes() on each parent lookup so that
        // DomainScope / ActiveScope do not silently cut the chain short.
        $chain = [];
        $currentId = $entity->id;
        $visited = [];

        while ($currentId && ! isset($visited[$currentId])) {
            $current = InternalEntity::withoutGlobalScopes()->find($currentId);
            if (! $current) {
                break;
            }

            $chain[] = [
                'id' => $current->id,
                'name' => $current->name,
            ];
            $visited[$currentId] = true;
            $currentId = $current->parent_id; // traverse up
        }

        return static::$chainCache[$entityId] = $this->expandEntityChainIntoApprovalStages($chain);
    }

    /**
     * Expand each internal entity into the required approval workflow phases
     * by reading entity_approval_stages for each entity.
     *
     * Only stages that are explicitly configured (row exists) are included.
     * Stages are included in stage_order ASC.
     *
     * @param  array<int, array{id:int, name:string}>  $chain
     * @return array<int, array<string, mixed>>
     *
     * @throws ConfigurationException When an entity has an enabled stage but no valid responsible user.
     */
    private function expandEntityChainIntoApprovalStages(array $chain): array
    {
        $stages = [];
        $order = 1;

        foreach ($chain as $entityData) {
            $configuredStages = EntityApprovalStage::where('entity_id', $entityData['id'])
                ->where('is_active', true)
                ->with('responsibleUser')
                ->ordered()
                ->get();

            // If the entity has no stages configured, skip it silently.
            // Entities that have never been configured will not appear in the workflow.
            foreach ($configuredStages as $stageConfig) {
                $stageEnum = $stageConfig->stageEnum();
                if (! $stageEnum) {
                    continue;
                }

                // Guard: stage is enabled but no valid responsible user
                if (! $stageConfig->hasValidResponsibleUser()) {
                    throw new ConfigurationException(
                        "الجهة: {$entityData['name']} | المرحلة: {$stageEnum->label()} | المشكلة: لم يتم تحديد مسؤول نشط لهذه المرحلة."
                    );
                }

                $phaseCode = $stageEnum->phaseCode();

                $stages[] = [
                    'order' => $order++,
                    'code' => 'entity_'.$entityData['id'].'_'.$phaseCode,
                    'name_ar' => $entityData['name'].' - '.$stageEnum->label(),
                    'name_en' => $entityData['name'].' - '.$stageEnum->value,
                    'entity_id' => $entityData['id'],
                    'entity_name' => $entityData['name'],
                    'phase' => $phaseCode,
                    'phase_name_ar' => $stageEnum->label(),
                    'phase_name_en' => $stageEnum->value,
                    'is_entity_stage' => true,
                    'is_implementation' => false,
                    // Snapshot the responsible user id so future config changes
                    // do not retroactively alter already-created ProjectApproval records.
                    'responsible_user_id' => $stageConfig->responsible_user_id,
                    'stage_type' => $stageEnum->value,
                ];
            }
        }

        return $stages;
    }

    /**
     * Get total number of stages for a user's entity
     */
    public function getTotalStagesCount(string $userEntityId): int
    {
        return count($this->generateApprovalStagesFromChain($userEntityId));
    }

    /**
     * Get the next entity in the approval chain
     * Returns null if current entity is the top-level entity
     */
    public function getNextEntityInChain(string $currentEntityName): ?array
    {
        $currentEntity = $this->findEntityByName($currentEntityName);
        if (! $currentEntity || ! $currentEntity->parent) {
            return null;
        }

        return [
            'id' => $currentEntity->parent->id,
            'name' => $currentEntity->parent->name,
        ];
    }

    /**
     * Check if a user's entity is authorized to approve at a specific entity level
     */
    public function canUserApproveAtEntity(string $userEntityName, string $targetEntityName): bool
    {
        $userEntity = $this->findEntityByName($userEntityName);
        $targetEntity = $this->findEntityByName($targetEntityName);

        if (! $userEntity || ! $targetEntity) {
            return false;
        }

        // User can approve if their entity matches the target entity
        // or if their entity is a parent/ancestor of the target entity
        if ($userEntity->id === $targetEntity->id) {
            return true;
        }

        // Check if user's entity is an ancestor of target entity
        $current = $targetEntity;
        while ($current->parent) {
            if ($current->parent->id === $userEntity->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Get all ancestor entities (from current to root)
     */
    public function getAncestors(string $entityName): array
    {
        $ancestors = [];
        $current = $this->findEntityByName($entityName);

        while ($current && $current->parent) {
            $ancestors[] = [
                'id' => $current->parent->id,
                'name' => $current->parent->name,
            ];
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Check if an entity is in the approval path for a given starting entity
     */
    public function isEntityInApprovalPath(string $startEntityName, string $checkEntityName): bool
    {
        $chain = $this->buildApprovalChain($startEntityName);

        foreach ($chain as $entity) {
            if ($entity['name'] === $checkEntityName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the previous entity in the approval chain (for returns/rejections)
     */
    public function getPreviousEntityInChain(string $currentEntityName, string $projectStartEntityName): ?array
    {
        $chain = $this->buildApprovalChain($projectStartEntityName);

        for ($i = 0; $i < count($chain); $i++) {
            if ($chain[$i]['name'] === $currentEntityName && $i > 0) {
                return $chain[$i - 1];
            }
        }

        return null;
    }

    /**
     * Get configured responsible user for a specific entity stage.
     * Used by approval workflow to show the current task owner.
     */
    public function getStageResponsibleUser(int $entityId, string $phaseCode): ?User
    {
        $stage = EntityApprovalStage::where('entity_id', $entityId)
            ->where('is_active', true)
            ->whereHas('stage', function ($q) use ($phaseCode) {
                $q->where('code', $phaseCode)
                    ->orWhere('value', $phaseCode);
            })
            ->with('responsibleUser')
            ->first();

        return $stage?->responsibleUser;
    }

    /**
     * Validate that all configured stages have valid responsible users.
     */
    public function validateApprovalChainConfiguration(array $chain): bool
    {
        foreach ($chain as $entityData) {
            $stages = EntityApprovalStage::where('entity_id', $entityData['id'])
                ->where('is_active', true)
                ->get();

            foreach ($stages as $stage) {
                if (! $stage->hasValidResponsibleUser()) {
                    throw new ConfigurationException(
                        "الجهة: {$entityData['name']} تحتوي مرحلة بدون مسؤول نشط."
                    );
                }
            }
        }

        return true;
    }

    /**
     * Return only active configured stages for displaying current workflow state.
     */
    public function getActiveWorkflowStages(int $entityId): array
    {
        return $this->generateApprovalStagesFromSelectedEntity($entityId);
    }
}

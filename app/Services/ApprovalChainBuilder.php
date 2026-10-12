<?php

namespace App\Services;

use App\Enums\EntityResponsibilityType;
use App\Exceptions\ConfigurationException;
use App\Models\Authority;
use App\Models\AuthorityApprovalRoute;
use App\Models\AuthorityApprovalStage;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectUnifiedRequest;
use App\Models\User;

class ApprovalChainBuilder
{
    private OrganizationResolver $organizationResolver;

    public function __construct(?OrganizationResolver $organizationResolver = null)
    {
        $this->organizationResolver = $organizationResolver ?? new OrganizationResolver;
    }

    /**
     * Build the full dynamic approval chain stages for a project.
     *
     * @throws ConfigurationException
     */
    public function buildChainForProject(Project $project, string $workflowType = 'project_approval'): array
    {
        $organization = $this->organizationResolver->resolveProject($project);

        if ($organization['type'] === OrganizationResolver::EXTERNAL) {
            $startAuthorityId = $organization['id'];
            if (! $startAuthorityId) {
                throw new ConfigurationException('External project missing authority_id.');
            }

            $chain = $this->buildExternalChain($startAuthorityId, $workflowType);
        } else {
            $startEntityId = $organization['id'];
            if (! $startEntityId) {
                throw new ConfigurationException('Internal project missing internal_entity_id/creator_entity_id.');
            }

            $chain = $this->buildInternalChain((int) $startEntityId, $workflowType);
        }

        // ✅ الترتيب الصحيح: نُعلّم المرحلة النهائية أولاً، ثم نتحقق من صحة السلسلة
        $chain = $this->markFinalStages($chain);
        $this->validateChainStages($chain);

        return $chain;
    }

    /**
     * Build dynamic approval chain for a ProjectUnifiedRequest.
     *
     * @throws ConfigurationException
     */
    public function buildChainForUnifiedRequest(ProjectUnifiedRequest $request): array
    {
        $workflowType = match ($request->request_type) {
            ProjectUnifiedRequest::TYPE_PAYMENT => 'payment',
            ProjectUnifiedRequest::TYPE_CLEARANCE => 'clearance',
            ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT => 'clearance_payment',
            ProjectUnifiedRequest::TYPE_CLEARANCE_CLOSURE => 'clearance_closure',
            default => $request->request_type,
        };

        if ($request->submitting_type === OrganizationResolver::EXTERNAL && $request->submitting_authority_id) {
            $chain = $this->buildExternalChain((int) $request->submitting_authority_id, $workflowType);
        } elseif ($request->submitting_entity_id) {
            $chain = $this->buildInternalChain((int) $request->submitting_entity_id, $workflowType);
        } else {
            $organization = $this->organizationResolver->resolveProject($request->project);
            if ($organization['type'] === OrganizationResolver::EXTERNAL) {
                $chain = $this->buildExternalChain((int) $organization['id'], $workflowType);
            } else {
                $chain = $this->buildInternalChain((int) $organization['id'], $workflowType);
            }
        }

        // ✅ الترتيب الصحيح: نُعلّم المرحلة النهائية أولاً، ثم نتحقق من صحة السلسلة
        $chain = $this->markFinalStages($chain);
        $this->validateChainStages($chain);

        return $chain;
    }

    /**
     * Builds the chain for internal entities (Entity -> Parent -> Ministry Root)
     */
    public function buildInternalChain(int $startEntityId, string $workflowType = 'project_approval'): array
    {
        $chain = [];
        $currentId = $startEntityId;
        $visited = [];

        while ($currentId && ! isset($visited[$currentId])) {
            $current = InternalEntity::withoutGlobalScopes()->find($currentId);
            if (! $current) {
                break;
            }

            $chain[] = [
                'type' => 'internal',
                'id' => $current->id,
                'name' => $current->name,
                'is_ministry_root' => (bool) $current->is_ministry_root,
            ];
            $visited[$currentId] = true;
            $currentId = $current->parent_id;
        }

        return $this->expandChainIntoStages($chain, $workflowType);
    }

    /**
     * Builds the chain for external authorities (Authority -> Authority -> Ministry Root)
     */
    public function buildExternalChain(int $startAuthorityId, string $workflowType = 'project_approval'): array
    {
        $validation = $this->validateAuthorityRoute($startAuthorityId);

        if (! $validation['is_valid']) {
            throw new ConfigurationException('مسار اعتماد الجهة الخارجية غير صالح: '.$validation['error']);
        }

        $chain = [];
        foreach ($validation['path'] as $node) {
            $chain[] = $node;
        }

        return $this->expandChainIntoStages($chain, $workflowType);
    }

    /**
     * Validates if an authority route is valid, doesn't contain circular references,
     * and correctly reaches the Ministry Root.
     */
    public function validateAuthorityRoute(int $startAuthorityId): array
    {
        $currentAuthorityId = $startAuthorityId;
        $visitedAuthorities = [];
        $path = [];

        while (true) {
            if (isset($visitedAuthorities[$currentAuthorityId])) {
                return [
                    'is_valid' => false,
                    'error' => 'تم اكتشاف حلقة دائرية (Circular Route) في مسار الاعتمادات.',
                    'path' => $path,
                ];
            }

            $visitedAuthorities[$currentAuthorityId] = true;
            $authority = Authority::withoutGlobalScopes()->find($currentAuthorityId);

            if (! $authority) {
                return [
                    'is_valid' => false,
                    'error' => "الجهة الخارجية ذات المعرف {$currentAuthorityId} غير موجودة.",
                    'path' => $path,
                ];
            }

            $path[] = [
                'type' => 'external',
                'id' => $authority->id,
                'name' => $authority->name,
                'is_ministry_root' => false,
            ];

            $route = AuthorityApprovalRoute::where('authority_id', $currentAuthorityId)
                ->where('is_active', true)
                ->first();

            if (! $route) {
                return [
                    'is_valid' => false,
                    'error' => "لا يوجد مسار فعال (Active Route) للجهة الخارجية: {$authority->name}.",
                    'path' => $path,
                ];
            }

            if ($route->destination_type === 'ministry') {
                $ministryRoot = InternalEntity::getMinistryRoot();
                if (! $ministryRoot) {
                    return [
                        'is_valid' => false,
                        'error' => 'لم يتم إعداد وزارة كجذر رئيسي (Ministry Root) في النظام.',
                        'path' => $path,
                    ];
                }

                $path[] = [
                    'type' => 'internal',
                    'id' => $ministryRoot->id,
                    'name' => $ministryRoot->name,
                    'is_ministry_root' => true,
                ];

                return [
                    'is_valid' => true,
                    'error' => null,
                    'path' => $path,
                ];
            } elseif ($route->destination_type === 'authority') {
                if (! $route->destination_authority_id) {
                    return [
                        'is_valid' => false,
                        'error' => "مسار الجهة {$authority->name} يوجه إلى جهة خارجية أخرى، لكن لم يتم تحديد الوجهة.",
                        'path' => $path,
                    ];
                }
                $currentAuthorityId = $route->destination_authority_id;
            } else {
                return [
                    'is_valid' => false,
                    'error' => "نوع الوجهة غير صالح (Invalid destination_type) في مسار الجهة {$authority->name}.",
                    'path' => $path,
                ];
            }
        }
    }

    /**
     * Expand the resolved chain into individual approval stages.
     */
    private function expandChainIntoStages(array $chain, string $workflowType = 'project_approval'): array
    {
        $stages = [];
        $order = 1;

        foreach ($chain as $node) {
            if ($node['type'] === 'external') {
                $configuredStages = AuthorityApprovalStage::where('authority_id', $node['id'])
                    ->where('workflow_type', $workflowType)
                    ->where('is_active', true)
                    ->orderBy('stage_order')
                    ->get();

                // Explicit inheritance rule: If no stages configured for specific request type, inherit from base project_approval stages
                if ($configuredStages->isEmpty() && $workflowType !== 'project_approval') {
                    $configuredStages = AuthorityApprovalStage::where('authority_id', $node['id'])
                        ->where('workflow_type', 'project_approval')
                        ->where('is_active', true)
                        ->orderBy('stage_order')
                        ->get();
                }

                foreach ($configuredStages as $stageConfig) {
                    if (! $stageConfig->responsible_user_id) {
                        throw new ConfigurationException(
                            "الجهة الخارجية: {$node['name']} | المشكلة: لم يتم تحديد مسؤول نشط لمرحلة الاعتماد."
                        );
                    }

                    $stageEnum = EntityResponsibilityType::tryFrom($stageConfig->stage);
                    $phaseCode = $stageConfig->stage_key ?? ($stageEnum ? $stageEnum->phaseCode() : str_replace(' ', '_', strtolower($stageConfig->stage_name_en ?? $stageConfig->stage)));
                    $stageLabel = $stageConfig->stage_name_ar ?? ($stageEnum ? $stageEnum->label() : $stageConfig->stage);

                    $stages[] = [
                        'order' => $order++,
                        'code' => 'authority_'.$node['id'].'_'.$phaseCode,
                        'name_ar' => $node['name'].' - '.$stageLabel,
                        'name_en' => $node['name'].' - '.($stageConfig->stage_name_en ?? ($stageEnum ? $stageEnum->value : $stageConfig->stage)),
                        'entity_id' => null,
                        'authority_id' => $node['id'],
                        'entity_name' => $node['name'],
                        'phase' => $phaseCode,
                        'is_entity_stage' => false,
                        'is_implementation' => $this->isImplementationStage($phaseCode, $stageConfig),
                        'responsible_user_id' => $stageConfig->responsible_user_id,
                        'stage_type' => $stageEnum ? $stageEnum->value : $stageConfig->stage,
                        'approver_scope' => 'external',
                        'is_ministry_root' => false,
                        'stage_key' => $phaseCode,
                        'stage_name' => $stageLabel,
                        'available_actions' => $stageConfig->available_actions,
                        'required_attachments' => $stageConfig->required_attachments ?? [],
                        'action_rules' => $stageConfig->action_rules ?? [],
                        'completion_requirements' => $stageConfig->completion_requirements ?? [],
                        // حقول المرحلة النهائية والتنفيذ
                        'is_final' => (bool) ($stageConfig->is_final ?? false),
                        'triggers_execution' => (bool) ($stageConfig->triggers_execution ?? false),
                    ];
                }
            } elseif ($node['type'] === 'internal') {
                $configuredStages = EntityApprovalStage::where('entity_id', $node['id'])
                    ->where('workflow_type', $workflowType)
                    ->where('is_active', true)
                    ->with('responsibleUser')
                    ->ordered()
                    ->get();

                // Explicit inheritance rule: If no stages configured for specific request type, inherit from base project_approval stages
                if ($configuredStages->isEmpty() && $workflowType !== 'project_approval') {
                    $configuredStages = EntityApprovalStage::where('entity_id', $node['id'])
                        ->where('workflow_type', 'project_approval')
                        ->where('is_active', true)
                        ->with('responsibleUser')
                        ->ordered()
                        ->get();
                }

                foreach ($configuredStages as $stageConfig) {
                    $stageEnum = $stageConfig->stageEnum();
                    $phaseCode = $stageConfig->stage_key ?? ($stageEnum ? $stageEnum->phaseCode() : str_replace(' ', '_', strtolower($stageConfig->stage_name_en ?? $stageConfig->stage)));
                    $stageLabel = $stageConfig->stage_name_ar ?? ($stageEnum ? $stageEnum->label() : $stageConfig->stage);

                    if (! $stageConfig->hasValidResponsibleUser()) {
                        throw new ConfigurationException(
                            "الجهة الداخلية: {$node['name']} | المرحلة: {$stageLabel} | المشكلة: لم يتم تحديد مسؤول نشط لهذه المرحلة."
                        );
                    }

                    $stages[] = [
                        'order' => $order++,
                        'code' => 'entity_'.$node['id'].'_'.$phaseCode,
                        'name_ar' => $node['name'].' - '.$stageLabel,
                        'name_en' => $node['name'].' - '.($stageConfig->stage_name_en ?? ($stageEnum ? $stageEnum->value : $stageConfig->stage)),
                        'entity_id' => $node['id'],
                        'authority_id' => null,
                        'entity_name' => $node['name'],
                        'phase' => $phaseCode,
                        'is_entity_stage' => true,
                        'is_implementation' => $this->isImplementationStage($phaseCode, $stageConfig),
                        'responsible_user_id' => $stageConfig->responsible_user_id,
                        'stage_type' => $stageEnum ? $stageEnum->value : $stageConfig->stage,
                        'approver_scope' => 'internal',
                        'is_ministry_root' => $node['is_ministry_root'],
                        'stage_key' => $phaseCode,
                        'stage_name' => $stageLabel,
                        'available_actions' => $stageConfig->available_actions,
                        'required_attachments' => $stageConfig->required_attachments ?? [],
                        'action_rules' => $stageConfig->action_rules ?? [],
                        'completion_requirements' => $stageConfig->completion_requirements ?? [],
                        // حقول المرحلة النهائية والتنفيذ
                        'is_final' => (bool) ($stageConfig->is_final ?? false),
                        'triggers_execution' => (bool) ($stageConfig->triggers_execution ?? false),
                    ];
                }
            }
        }

        if (empty($stages)) {
            $workflowLabel = EntityApprovalStage::WORKFLOW_TYPES[$workflowType] ?? $workflowType;
            $startNodeName = $chain[0]['name'] ?? 'الجهة المعنية';
            throw new ConfigurationException(
                "لم يتم ضبط مسار اعتماد مفعل لنوع الطلب [{$workflowLabel}] للجهة [{$startNodeName}]. يرجى من مدير النظام إعداد مراحل مسار الاعتماد من مركز الاعتمادات."
            );
        }

        return $stages;
    }

    /**
     * Determine whether the given stage is an execution/implementation stage.
     * Falls back to matching known phase codes if no explicit flag is set.
     */
    private function isImplementationStage(string $phaseCode, $stageConfig): bool
    {
        if (isset($stageConfig->is_implementation)) {
            return (bool) $stageConfig->is_implementation;
        }

        $implementationPhases = [
            'EXECUTION',
            'IMPLEMENTATION',
            'IN_EXECUTION',
            'PROJECT_EXECUTION',
            'PAYMENT_EXECUTION',
            'CLOSURE',
            'PROJECT_CLOSURE',
        ];

        return in_array(strtoupper($phaseCode), $implementationPhases, true);
    }

    /**
     * Mark the final stage(s) in the chain.
     *
     * Priority:
     *   1. Any stage explicitly flagged with is_final = true or triggers_execution = true.
     *   2. Otherwise, the last stage in the ordered chain is treated as final.
     *
     * Side effect: ensures that at most ONE stage is flagged as final.
     * If multiple stages are flagged, the last one in the chain wins.
     */
    private function markFinalStages(array $chain): array
    {
        if (empty($chain)) {
            return $chain;
        }

        $explicitFinalIndexes = [];
        foreach ($chain as $index => $stage) {
            if (! empty($stage['is_final']) || ! empty($stage['triggers_execution'])) {
                $explicitFinalIndexes[] = $index;
            }
        }

        // Case A: No explicit final stage configured → mark the last one.
        if (empty($explicitFinalIndexes)) {
            $lastIndex = count($chain) - 1;
            $chain[$lastIndex]['is_final'] = true;
            $chain[$lastIndex]['triggers_execution'] = true;

            return $chain;
        }

        // Case B: Multiple explicit final stages → keep only the last one.
        if (count($explicitFinalIndexes) > 1) {
            $lastExplicitIndex = end($explicitFinalIndexes);

            foreach ($explicitFinalIndexes as $index) {
                if ($index !== $lastExplicitIndex) {
                    $chain[$index]['is_final'] = false;
                    $chain[$index]['triggers_execution'] = false;
                }
            }

            // Ensure the kept one has both flags.
            $chain[$lastExplicitIndex]['is_final'] = true;
            $chain[$lastExplicitIndex]['triggers_execution'] = true;

            return $chain;
        }

        // Case C: Exactly one explicit final stage → normalize its flags.
        $index = $explicitFinalIndexes[0];
        $chain[$index]['is_final'] = true;
        $chain[$index]['triggers_execution'] = true;

        return $chain;
    }

    /**
     * Validate that every configured stage has a responsible user,
     * and that the chain contains exactly one final stage.
     *
     * @throws ConfigurationException
     */
    public function validateChainStages(array $stages): bool
    {
        if (empty($stages)) {
            throw new ConfigurationException('سلسلة الاعتماد فارغة.');
        }

        foreach ($stages as $stage) {
            if (empty($stage['responsible_user_id'])) {
                throw new ConfigurationException(
                    'مرحلة الاعتماد '.($stage['name_ar'] ?? 'غير معروفة').' لا تحتوي على مسؤول محدد.'
                );
            }
        }

        // Verify exactly one final stage exists in the chain
        $finalCount = 0;
        foreach ($stages as $stage) {
            if (! empty($stage['is_final'])) {
                $finalCount++;
            }
        }

        if ($finalCount === 0) {
            throw new ConfigurationException(
                'سلسلة الاعتماد لا تحتوي على مرحلة نهائية (Final Stage) محددة. يرجى ضبط المرحلة النهائية من إعدادات الاعتمادات.'
            );
        }

        if ($finalCount > 1) {
            throw new ConfigurationException(
                'سلسلة الاعتماد تحتوي على أكثر من مرحلة نهائية. يجب تحديد مرحلة نهائية واحدة فقط.'
            );
        }

        return true;
    }

    /**
     * Get the assigned user for the current workflow stage.
     * Used to display the task owner.
     */
    public function getResponsibleUserFromStage(array $stage): ?User
    {
        if (empty($stage['responsible_user_id'])) {
            return null;
        }

        return User::find($stage['responsible_user_id']);
    }

    /**
     * Build chain prepared for workflow execution.
     * Adds normalized fields used by ProjectApproval creation.
     */
    public function buildWorkflowChain(Project $project, string $workflowType = 'project_approval'): array
    {
        // buildChainForProject now handles markFinalStages + validation internally.
        return $this->buildChainForProject($project, $workflowType);
    }

    /**
     * Get only the final stage from a chain (helper).
     */
    public function getFinalStage(array $chain): ?array
    {
        foreach ($chain as $stage) {
            if (! empty($stage['is_final'])) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * Check if the given stage is the final one (helper).
     */
    public function isFinalStage(array $stage): bool
    {
        return ! empty($stage['is_final']);
    }

    /**
     * Get the index (position) of the final stage in the chain (helper).
     * Returns null if no final stage is found.
     */
    public function getFinalStageIndex(array $chain): ?int
    {
        foreach ($chain as $index => $stage) {
            if (! empty($stage['is_final'])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Check whether a given stage (by index) is the last one before execution.
     */
    public function isLastBeforeExecution(array $chain, int $index): bool
    {
        $finalIndex = $this->getFinalStageIndex($chain);

        return $finalIndex !== null && $index === ($finalIndex - 1);
    }
}

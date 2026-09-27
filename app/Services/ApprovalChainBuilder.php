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
use App\Models\User;

class ApprovalChainBuilder
{
    /**
     * Build the full dynamic approval chain stages for a project.
     *
     * @throws ConfigurationException
     */
    public function buildChainForProject(Project $project): array
    {
        $isExternal = $project->source_type === 'external';

        if ($isExternal) {
            $startAuthorityId = $project->authority_id;
            if (! $startAuthorityId) {
                throw new ConfigurationException('External project missing authority_id.');
            }

            return $this->buildExternalChain($startAuthorityId);
        } else {
            $startEntityId = $project->internal_entity_id ?? $project->creator_entity_id;
            if (! $startEntityId) {
                throw new ConfigurationException('Internal project missing internal_entity_id/creator_entity_id.');
            }

            return $this->buildInternalChain((int) $startEntityId);
        }
    }

    /**
     * Builds the chain for internal entities (Entity -> Parent -> Ministry Root)
     */
    public function buildInternalChain(int $startEntityId): array
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

        return $this->expandChainIntoStages($chain);
    }

    /**
     * Builds the chain for external authorities (Authority -> Authority -> Ministry Root)
     */
    public function buildExternalChain(int $startAuthorityId): array
    {
        $validation = $this->validateAuthorityRoute($startAuthorityId);

        if (! $validation['is_valid']) {
            throw new ConfigurationException('مسار اعتماد الجهة الخارجية غير صالح: '.$validation['error']);
        }

        $chain = [];
        foreach ($validation['path'] as $node) {
            $chain[] = $node;
        }

        return $this->expandChainIntoStages($chain);
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
    private function expandChainIntoStages(array $chain): array
    {
        $stages = [];
        $order = 1;

        foreach ($chain as $node) {
            if ($node['type'] === 'external') {
                $configuredStages = AuthorityApprovalStage::where('authority_id', $node['id'])
                    ->where('is_active', true)
                    ->orderBy('stage_order')
                    ->get();

                foreach ($configuredStages as $stageConfig) {
                    if (! $stageConfig->responsible_user_id) {
                        throw new ConfigurationException(
                            "الجهة الخارجية: {$node['name']} | المشكلة: لم يتم تحديد مسؤول نشط لمرحلة الاعتماد."
                        );
                    }

                    $stageEnum = EntityResponsibilityType::tryFrom($stageConfig->stage);
                    $phaseCode = $stageEnum ? $stageEnum->phaseCode() : str_replace(' ', '_', strtolower($stageConfig->stage_name_en ?? $stageConfig->stage));
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
                        'is_implementation' => false,
                        'responsible_user_id' => $stageConfig->responsible_user_id,
                        'stage_type' => $stageEnum ? $stageEnum->value : $stageConfig->stage,
                        'approver_scope' => 'external',
                        'is_ministry_root' => false,
                        'stage_key' => $phaseCode,
                        'stage_name' => $stageLabel,
                        'available_actions' => $stageConfig->available_actions,
                    ];
                }
            } elseif ($node['type'] === 'internal') {
                $configuredStages = EntityApprovalStage::where('entity_id', $node['id'])
                    ->where('is_active', true)
                    ->with('responsibleUser')
                    ->ordered()
                    ->get();

                foreach ($configuredStages as $stageConfig) {
                    $stageEnum = $stageConfig->stageEnum();
                    $phaseCode = $stageEnum ? $stageEnum->phaseCode() : str_replace(' ', '_', strtolower($stageConfig->stage_name_en ?? $stageConfig->stage));
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
                        'is_implementation' => false,
                        'responsible_user_id' => $stageConfig->responsible_user_id,
                        'stage_type' => $stageEnum ? $stageEnum->value : $stageConfig->stage,
                        'approver_scope' => 'internal',
                        'is_ministry_root' => $node['is_ministry_root'],
                        'stage_key' => $phaseCode,
                        'stage_name' => $stageLabel,
                        'available_actions' => $stageConfig->available_actions,
                    ];
                }
            }
        }

        return $stages;
    }

    /**
     * Validate that every configured stage has a responsible user.
     * Prevents creating approval tasks that nobody can execute.
     */
    public function validateChainStages(array $stages): bool
    {
        foreach ($stages as $stage) {
            if (empty($stage['responsible_user_id'])) {
                throw new ConfigurationException(
                    'مرحلة الاعتماد '.$stage['name_ar'].' لا تحتوي على مسؤول محدد.'
                );
            }
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
    public function buildWorkflowChain(Project $project): array
    {
        $chain = $this->buildChainForProject($project);

        $this->validateChainStages($chain);

        return $chain;
    }
}

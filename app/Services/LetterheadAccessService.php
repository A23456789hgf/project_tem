<?php

namespace App\Services;

use App\Data\LetterheadResolutionResult;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\OrganizationLetterhead;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LetterheadAccessService
{
    public function __construct(
        protected OrganizationResolver $organizationResolver,
    ) {}

    /**
     * Resolves the effective Letterhead for a user or target organization following strict fallback hierarchy.
     */
    public function resolveEffectiveLetterhead(
        ?User $user = null,
        ?string $targetOrgType = null,
        ?int $targetOrgId = null,
        ?int $requestedLetterheadId = null
    ): LetterheadResolutionResult {
        // Normalize when requestedLetterheadId is passed as 2nd parameter
        if (is_numeric($targetOrgType) && $targetOrgId === null && $requestedLetterheadId === null) {
            $requestedLetterheadId = (int) $targetOrgType;
            $targetOrgType = null;
        }

        // 1. Explicit Letterhead Request
        if ($requestedLetterheadId) {
            $requested = OrganizationLetterhead::withoutGlobalScopes()
                ->where('is_active', true)
                ->find($requestedLetterheadId);

            if ($requested) {
                $userOrg = $user ? $this->organizationResolver->resolve($user) : null;
                $isTargetOrg = $targetOrgType && (int) $targetOrgId && $requested->organization_type === $targetOrgType && (int) $requested->organization_id === (int) $targetOrgId;
                $isOwnUserOrg = $userOrg && $requested->organization_type === $userOrg['type'] && (int) $requested->organization_id === (int) $userOrg['id'];
                $canSelectOther = $user && ($user->isAdmin() || ($user->role && $user->role->full_access) || $user->hasPermission('letterheads.select-other'));

                if (($isTargetOrg || $isOwnUserOrg || $canSelectOther || ! $user) && (! $user || $this->canUserUseLetterhead($user, $requested))) {
                    $orgName = $requested->organization_name;

                    return new LetterheadResolutionResult(
                        letterhead: $requested,
                        sourceType: 'explicit',
                        sourceOrganizationType: $requested->organization_type,
                        sourceOrganizationId: $requested->organization_id,
                        sourceOrganizationName: $orgName,
                        isInherited: false,
                        fallbackLevel: 0,
                        explanation: "تم استخدام الترويسة المحددة يدوياً: {$requested->name}",
                    );
                }

                if ($user) {
                    Log::warning("User {$user->id} attempted to use unauthorized letterhead {$requestedLetterheadId}. Falling back to default.");
                }
            }
        }

        // Determine target organization from user if not specified
        $userOrg = null;
        if (! $targetOrgType || ! $targetOrgId) {
            if ($user) {
                $userOrg = $this->organizationResolver->resolve($user);
                $targetOrgType = $userOrg['type'];
                $targetOrgId = $userOrg['id'];
            }
        }

        // 2. Organization's Own Active Default Letterhead
        if ($targetOrgType && $targetOrgId) {
            $ownLetterhead = OrganizationLetterhead::withoutGlobalScopes()
                ->where('organization_type', $targetOrgType)
                ->where('organization_id', $targetOrgId)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if (! $ownLetterhead) {
                $ownLetterhead = OrganizationLetterhead::withoutGlobalScopes()
                    ->where('organization_type', $targetOrgType)
                    ->where('organization_id', $targetOrgId)
                    ->where('is_active', true)
                    ->first();
            }

            if ($ownLetterhead && ($ownLetterhead->inheritance_mode === 'own' || empty($ownLetterhead->inheritance_mode))) {
                return new LetterheadResolutionResult(
                    letterhead: $ownLetterhead,
                    sourceType: 'own',
                    sourceOrganizationType: $targetOrgType,
                    sourceOrganizationId: $targetOrgId,
                    sourceOrganizationName: $ownLetterhead->organization_name,
                    isInherited: false,
                    fallbackLevel: 0,
                    explanation: "تم استخدام الترويسة الافتراضية الخاصة بالجهة: {$ownLetterhead->organization_name}",
                );
            }
        }

        // 3. Parent / Ancestor Inheritance in Organizational Hierarchy
        if ($targetOrgType && $targetOrgId) {
            $ancestorResolution = $this->resolveFromAncestors($targetOrgType, $targetOrgId);
            if ($ancestorResolution) {
                return $ancestorResolution;
            }
        }

        // 4. User's Administrative Scope Root Fallback (only if user belongs to same target org)
        if ($user && $user->administrative_scope_id && (! $targetOrgId || ($userOrg && $targetOrgId === $userOrg['id']))) {
            $adminOrgType = $this->organizationResolver->type($user);
            $adminLetterhead = OrganizationLetterhead::withoutGlobalScopes()
                ->where('organization_type', $adminOrgType)
                ->where('organization_id', $user->administrative_scope_id)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if ($adminLetterhead) {
                return new LetterheadResolutionResult(
                    letterhead: $adminLetterhead,
                    sourceType: 'admin_scope',
                    sourceOrganizationType: $adminOrgType,
                    sourceOrganizationId: $user->administrative_scope_id,
                    sourceOrganizationName: $adminLetterhead->organization_name,
                    isInherited: true,
                    fallbackLevel: 3,
                    explanation: "تم وراثة الترويسة من النطاق الإداري الأعلى: {$adminLetterhead->organization_name}",
                );
            }
        }

        // 5. Ministry Root (for internal) or Root Authority (for external)
        if ($targetOrgType === OrganizationResolver::INTERNAL) {
            $ministryRoot = InternalEntity::withoutGlobalScopes()->where('is_ministry_root', true)->first();
            if (! $ministryRoot) {
                $ministryRoot = InternalEntity::withoutGlobalScopes()->whereNull('parent_id')->orderBy('id')->first();
            }

            if ($ministryRoot) {
                $ministryLetterhead = OrganizationLetterhead::withoutGlobalScopes()
                    ->where('organization_type', OrganizationResolver::INTERNAL)
                    ->where('organization_id', $ministryRoot->id)
                    ->where('is_active', true)
                    ->first();

                if ($ministryLetterhead) {
                    return new LetterheadResolutionResult(
                        letterhead: $ministryLetterhead,
                        sourceType: 'ministry_root',
                        sourceOrganizationType: OrganizationResolver::INTERNAL,
                        sourceOrganizationId: $ministryRoot->id,
                        sourceOrganizationName: $ministryRoot->name,
                        isInherited: true,
                        fallbackLevel: 4,
                        explanation: 'تم استخدام الترويسة الرسمية لديوان عام الوزارة',
                    );
                }
            }
        } else {
            $rootAuthority = Authority::withoutGlobalScopes()->whereNull('parent_id')->where('is_active', true)->first();
            if ($rootAuthority) {
                $authorityLetterhead = OrganizationLetterhead::withoutGlobalScopes()
                    ->where('organization_type', OrganizationResolver::EXTERNAL)
                    ->where('organization_id', $rootAuthority->id)
                    ->where('is_active', true)
                    ->first();

                if ($authorityLetterhead) {
                    return new LetterheadResolutionResult(
                        letterhead: $authorityLetterhead,
                        sourceType: 'root_authority',
                        sourceOrganizationType: OrganizationResolver::EXTERNAL,
                        sourceOrganizationId: $rootAuthority->id,
                        sourceOrganizationName: $rootAuthority->agency_name ?? $rootAuthority->name,
                        isInherited: true,
                        fallbackLevel: 4,
                        explanation: 'تم استخدام ترويسة الهيئة/الجهة المركزية',
                    );
                }
            }
        }

        // 6. System Global Default Letterhead
        $globalDefault = OrganizationLetterhead::withoutGlobalScopes()
            ->where('is_global_default', true)
            ->where('is_active', true)
            ->first();

        if ($globalDefault) {
            return new LetterheadResolutionResult(
                letterhead: $globalDefault,
                sourceType: 'system_global',
                sourceOrganizationType: 'system_global',
                sourceOrganizationId: null,
                sourceOrganizationName: 'الترويسة العامة للنظام',
                isInherited: true,
                fallbackLevel: 5,
                explanation: 'تم استخدام الترويسة العامة الافتراضية للنظام',
            );
        }

        // 7. Ephemeral Fallback (Safe In-Memory Letterhead to prevent any crashes)
        $ephemeral = new OrganizationLetterhead([
            'name' => 'الترويسة الرسمية الافتراضية',
            'organization_type' => $targetOrgType ?? 'internal',
            'organization_id' => $targetOrgId,
            'is_default' => true,
            'is_active' => true,
        ]);

        return new LetterheadResolutionResult(
            letterhead: $ephemeral,
            sourceType: 'system_global',
            sourceOrganizationType: 'system_global',
            sourceOrganizationId: null,
            sourceOrganizationName: 'الترويسة القياسية',
            isInherited: true,
            fallbackLevel: 6,
            explanation: 'تم استخدام الترويسة القياسية للنظام',
        );
    }

    /**
     * Resolves letterhead upward through parent hierarchy.
     */
    protected function resolveFromAncestors(string $orgType, int $orgId): ?LetterheadResolutionResult
    {
        $currentId = $orgId;
        $distance = 1;
        $visited = [$orgId => true];

        while ($currentId) {
            $parentId = null;

            if ($orgType === OrganizationResolver::INTERNAL) {
                $parentId = DB::table('internal_entities')->where('id', $currentId)->value('parent_id');
            } else {
                $parentId = DB::table('authorities')->where('id', $currentId)->value('parent_id');
            }

            if (! $parentId || isset($visited[$parentId])) {
                break;
            }

            $visited[$parentId] = true;
            $currentId = (int) $parentId;

            $parentLetterhead = OrganizationLetterhead::withoutGlobalScopes()
                ->where('organization_type', $orgType)
                ->where('organization_id', $currentId)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if ($parentLetterhead) {
                $parentName = $parentLetterhead->organization_name;

                return new LetterheadResolutionResult(
                    letterhead: $parentLetterhead,
                    sourceType: 'parent',
                    sourceOrganizationType: $orgType,
                    sourceOrganizationId: $currentId,
                    sourceOrganizationName: $parentName,
                    isInherited: true,
                    fallbackLevel: $distance,
                    explanation: "تم وراثة الترويسة من الجهة الأب ({$parentName})",
                );
            }

            $distance++;
        }

        return null;
    }

    /**
     * Get all allowed letterheads for a user based on Admin Scope ∩ Geo Scope ∩ Permissions.
     *
     * @return Collection<int, OrganizationLetterhead>
     */
    public function getAllowedLetterheadsForUser(?User $user, ?string $search = null): Collection
    {
        if (! $user) {
            return collect();
        }

        $query = OrganizationLetterhead::withoutGlobalScopes()
            ->where('is_active', true)
            ->with(['internalEntity', 'authority']);

        // Admin and Full Access users have complete visibility
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }

            return $query->orderBy('is_global_default', 'desc')->orderBy('name')->get();
        }

        $userOrgType = $this->organizationResolver->type($user);
        $allowedOrgIds = [];

        if ($userOrgType === OrganizationResolver::INTERNAL) {
            // Get user's own entity + children within admin scope
            $adminRootId = $user->administrative_scope_id ?: $user->entity_id;
            $adminEntityIds = $adminRootId ? InternalEntity::getAllDescendantIds($adminRootId) : [];

            // If user has geographic restrictions, filter by governorates/directorates
            if ($user->isGeographicUser() && $this->organizationResolver->hasGeographicScope($user)) {
                $govIds = $this->organizationResolver->governorateIds($user);
                $dirIds = $this->organizationResolver->directorateIds($user);

                $geoEntityIds = [];
                if (! empty($govIds)) {
                    foreach ($govIds as $gId) {
                        $geoEntityIds = array_merge($geoEntityIds, InternalEntity::getAllByGovernorate($gId));
                    }
                }
                if (! empty($dirIds)) {
                    foreach ($dirIds as $dId) {
                        $geoEntityIds = array_merge($geoEntityIds, InternalEntity::getAllByDirectorate($dId));
                    }
                }

                $geoEntityIds = array_unique($geoEntityIds);
                $allowedOrgIds = ! empty($adminEntityIds)
                    ? array_values(array_intersect($adminEntityIds, $geoEntityIds))
                    : $geoEntityIds;
            } else {
                $allowedOrgIds = $adminEntityIds;
            }

            // Always ensure user's own entity is included
            if ($user->entity_id && ! in_array($user->entity_id, $allowedOrgIds)) {
                $allowedOrgIds[] = (int) $user->entity_id;
            }

            $query->where(function (Builder $q) use ($allowedOrgIds) {
                $q->where(function (Builder $sub) use ($allowedOrgIds) {
                    $sub->where('organization_type', OrganizationResolver::INTERNAL)
                        ->whereIn('organization_id', ! empty($allowedOrgIds) ? $allowedOrgIds : [-1]);
                })->orWhere('is_global_default', true);
            });
        } else {
            // External Authority User
            $adminAuthorityIds = $this->organizationResolver->administrativeOrganizationIds($user);
            if ($user->authority_id && ! in_array($user->authority_id, $adminAuthorityIds)) {
                $adminAuthorityIds[] = (int) $user->authority_id;
            }

            $query->where(function (Builder $q) use ($adminAuthorityIds) {
                $q->where(function (Builder $sub) use ($adminAuthorityIds) {
                    $sub->where('organization_type', OrganizationResolver::EXTERNAL)
                        ->whereIn('organization_id', ! empty($adminAuthorityIds) ? $adminAuthorityIds : [-1]);
                })->orWhere('is_global_default', true);
            });
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('is_global_default', 'desc')->orderBy('name')->get();
    }

    /**
     * Verify if a user is authorized to use a specific letterhead.
     */
    public function canUserUseLetterhead(User $user, OrganizationLetterhead|int $letterhead): bool
    {
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return true;
        }

        $letterheadModel = is_numeric($letterhead)
            ? OrganizationLetterhead::withoutGlobalScopes()->find($letterhead)
            : $letterhead;

        if (! $letterheadModel || ! $letterheadModel->is_active) {
            return false;
        }

        if ($letterheadModel->is_global_default) {
            return true;
        }

        $allowedLetterheadIds = $this->getAllowedLetterheadsForUser($user)->pluck('id')->all();

        return in_array($letterheadModel->id, $allowedLetterheadIds);
    }

    /**
     * Check if user can manage letterheads for a specific organization.
     */
    public function canUserManageOrganization(User $user, string $orgType, int $orgId): bool
    {
        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return true;
        }

        if ($orgType === 'system_global') {
            return $user->isAdmin();
        }

        $userOrgType = $this->organizationResolver->type($user);
        if ($userOrgType !== $orgType) {
            return false;
        }

        if ($orgType === OrganizationResolver::INTERNAL) {
            $adminRootId = $user->administrative_scope_id ?: $user->entity_id;
            $allowedIds = $adminRootId ? InternalEntity::getAllDescendantIds($adminRootId) : [];

            if (! in_array($orgId, $allowedIds) && $user->entity_id !== $orgId) {
                return false;
            }

            if ($user->isGeographicUser() && $this->organizationResolver->hasGeographicScope($user)) {
                $govIds = $this->organizationResolver->governorateIds($user);
                $orgGovId = DB::table('internal_entities')->where('id', $orgId)->value('governorate_id');

                if ($orgGovId && ! in_array((int) $orgGovId, $govIds)) {
                    return false;
                }
            }

            return true;
        }

        if ($orgType === OrganizationResolver::EXTERNAL) {
            $allowedIds = $this->organizationResolver->administrativeOrganizationIds($user);
            if ($user->authority_id && ! in_array($user->authority_id, $allowedIds)) {
                $allowedIds[] = (int) $user->authority_id;
            }

            return in_array($orgId, $allowedIds);
        }

        return false;
    }

    /**
     * Bulk apply letterhead settings across multiple organizations within scope.
     *
     * @param  array<string, mixed>  $sourceSettings
     * @param  array<int, array{type: string, id: int}>  $targetOrgs
     * @param  array<int, string>  $groupsToCopy  ['header', 'logo', 'typography', 'footer', 'page_settings']
     */
    public function bulkApplySettings(User $user, array $sourceSettings, array $targetOrgs, array $groupsToCopy): int
    {
        $updatedCount = 0;

        DB::transaction(function () use ($user, $sourceSettings, $targetOrgs, $groupsToCopy, &$updatedCount) {
            foreach ($targetOrgs as $org) {
                $type = $org['type'];
                $id = (int) $org['id'];

                if (! $this->canUserManageOrganization($user, $type, $id)) {
                    Log::warning("User {$user->id} attempted to bulk configure unauthorized organization {$type}:{$id}");

                    continue;
                }

                $letterhead = OrganizationLetterhead::withoutGlobalScopes()
                    ->where('organization_type', $type)
                    ->where('organization_id', $id)
                    ->first();

                $isNew = false;
                if (! $letterhead) {
                    $isNew = true;
                    $orgName = $type === 'internal'
                        ? DB::table('internal_entities')->where('id', $id)->value('name')
                        : DB::table('authorities')->where('id', $id)->value('agency_name');

                    $letterhead = new OrganizationLetterhead([
                        'name' => "ترويسة {$orgName}",
                        'organization_type' => $type,
                        'organization_id' => $id,
                        'is_default' => true,
                        'is_active' => true,
                        'inheritance_mode' => 'own',
                        'created_by' => $user->id,
                    ]);
                }

                $oldValues = $letterhead->toArray();

                if (in_array('header', $groupsToCopy)) {
                    if (isset($sourceSettings['header_right'])) {
                        $letterhead->header_right = $sourceSettings['header_right'];
                    }
                    if (isset($sourceSettings['header_center'])) {
                        $letterhead->header_center = $sourceSettings['header_center'];
                    }
                    if (isset($sourceSettings['header_left'])) {
                        $letterhead->header_left = $sourceSettings['header_left'];
                    }
                }

                if (in_array('logo', $groupsToCopy)) {
                    if (isset($sourceSettings['logo_path'])) {
                        $letterhead->logo_path = $sourceSettings['logo_path'];
                    }
                    if (isset($sourceSettings['second_logo_path'])) {
                        $letterhead->second_logo_path = $sourceSettings['second_logo_path'];
                    }
                    if (isset($sourceSettings['watermark_path'])) {
                        $letterhead->watermark_path = $sourceSettings['watermark_path'];
                    }
                }

                if (in_array('typography', $groupsToCopy) && isset($sourceSettings['typography_settings'])) {
                    $letterhead->typography_settings = $sourceSettings['typography_settings'];
                }

                if (in_array('footer', $groupsToCopy) && isset($sourceSettings['footer_settings'])) {
                    $letterhead->footer_settings = $sourceSettings['footer_settings'];
                }

                if (in_array('page_settings', $groupsToCopy) && isset($sourceSettings['page_settings'])) {
                    $letterhead->page_settings = $sourceSettings['page_settings'];
                }

                $letterhead->updated_by = $user->id;
                $letterhead->save();

                // Audit Log
                AuditLog::create([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'entity_name' => $letterhead->organization_name,
                    'action' => $isNew ? 'bulk_create_letterhead' : 'bulk_update_letterhead',
                    'module' => 'letterheads',
                    'model_type' => OrganizationLetterhead::class,
                    'model_id' => $letterhead->id,
                    'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
                    'new_values' => json_encode($letterhead->toArray(), JSON_UNESCAPED_UNICODE),
                    'description' => "تطبيق جماعي لإعدادات الترويسة للجهة: {$letterhead->organization_name}",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    /**
     * Copy settings from one source letterhead to target organizations.
     */
    public function copySettings(User $user, int $sourceLetterheadId, array $targetOrgKeys, array $groupsToCopy): int
    {
        $source = OrganizationLetterhead::withoutGlobalScopes()->findOrFail($sourceLetterheadId);

        $targetOrgs = [];
        foreach ($targetOrgKeys as $key) {
            // key format: 'internal:5' or 'external:12'
            $parts = explode(':', (string) $key);
            if (count($parts) === 2) {
                $targetOrgs[] = [
                    'type' => $parts[0],
                    'id' => (int) $parts[1],
                ];
            }
        }

        $sourceSettings = [
            'header_right' => $source->header_right,
            'header_center' => $source->header_center,
            'header_left' => $source->header_left,
            'logo_path' => $source->logo_path,
            'second_logo_path' => $source->second_logo_path,
            'watermark_path' => $source->watermark_path,
            'typography_settings' => $source->typography_settings,
            'footer_settings' => $source->footer_settings,
            'page_settings' => $source->page_settings,
        ];

        return $this->bulkApplySettings($user, $sourceSettings, $targetOrgs, $groupsToCopy);
    }

    /**
     * Configure hierarchical default behavior for an organization branch.
     *
     * @param  string  $mode  'this_only', 'this_and_children_without_custom', 'this_and_all_children'
     */
    public function setHierarchicalDefault(User $user, string $orgType, int $orgId, int $letterheadId, string $mode): int
    {
        if (! $this->canUserManageOrganization($user, $orgType, $orgId)) {
            throw new AuthorizationException('غير مصرح لك بإدارة هذا الفرع الإداري.');
        }

        $count = 0;

        DB::transaction(function () use ($user, $orgType, $orgId, $letterheadId, $mode, &$count) {
            // Set for current organization
            $this->setLetterheadAsDefault($user, $letterheadId, $orgType, $orgId);
            $count++;

            if ($mode === 'this_only') {
                return;
            }

            // Resolve children IDs
            $childrenIds = $orgType === OrganizationResolver::INTERNAL
                ? InternalEntity::getAllChildrenIds($orgId)
                : (new Authority)->where('parent_id', $orgId)->pluck('id')->all();

            foreach ($childrenIds as $childId) {
                $childId = (int) $childId;
                if ($childId === $orgId) {
                    continue;
                }

                if (! $this->canUserManageOrganization($user, $orgType, $childId)) {
                    continue;
                }

                $existing = OrganizationLetterhead::withoutGlobalScopes()
                    ->where('organization_type', $orgType)
                    ->where('organization_id', $childId)
                    ->where('inheritance_mode', 'own')
                    ->exists();

                if ($mode === 'this_and_children_without_custom' && $existing) {
                    continue; // Preserve custom configuration
                }

                // Set inheritance mode to parent
                OrganizationLetterhead::withoutGlobalScopes()
                    ->where('organization_type', $orgType)
                    ->where('organization_id', $childId)
                    ->update([
                        'inheritance_mode' => 'inherit_parent',
                        'is_default' => false,
                        'updated_by' => $user->id,
                    ]);

                $count++;
            }
        });

        return $count;
    }

    /**
     * Unset other defaults and set specific letterhead as default for an organization.
     */
    public function setLetterheadAsDefault(User $user, int $letterheadId, string $orgType, ?int $orgId): void
    {
        DB::transaction(function () use ($user, $letterheadId, $orgType, $orgId) {
            // Unset previous defaults
            OrganizationLetterhead::withoutGlobalScopes()
                ->where('organization_type', $orgType)
                ->where('organization_id', $orgId)
                ->where('id', '!=', $letterheadId)
                ->update(['is_default' => false]);

            // Set new default
            $letterhead = OrganizationLetterhead::withoutGlobalScopes()->findOrFail($letterheadId);
            $letterhead->is_default = true;
            $letterhead->inheritance_mode = 'own';
            $letterhead->updated_by = $user->id;
            $letterhead->save();

            AuditLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'entity_name' => $letterhead->organization_name,
                'action' => 'set_default_letterhead',
                'module' => 'letterheads',
                'model_type' => OrganizationLetterhead::class,
                'model_id' => $letterhead->id,
                'new_values' => json_encode(['is_default' => true, 'inheritance_mode' => 'own'], JSON_UNESCAPED_UNICODE),
                'description' => "تعيين الترويسة '{$letterhead->name}' كافتراضية للجهة: {$letterhead->organization_name}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Build nested organization hierarchy tree with letterhead & inheritance status.
     *
     * @return array<int, mixed>
     */
    public function getHierarchyTreeForUser(User $user): array
    {
        $tree = [];

        // 1. Internal Entities Hierarchy Tree
        if ($user->isAdmin() || $user->isInternal() || ($user->role && $user->role->full_access)) {
            $rootEntitiesQuery = InternalEntity::withoutGlobalScopes()->whereNull('parent_id')->orderBy('name');
            if (! $user->isAdmin() && ! ($user->role && $user->role->full_access)) {
                $adminRootId = $user->administrative_scope_id ?: $user->entity_id;
                if ($adminRootId) {
                    $rootEntitiesQuery = InternalEntity::withoutGlobalScopes()->where('id', $adminRootId);
                }
            }

            $rootEntities = $rootEntitiesQuery->get();
            foreach ($rootEntities as $entity) {
                $tree[] = $this->buildEntityTreeNode($entity, $user);
            }
        }

        // 2. External Authorities Hierarchy Tree
        if ($user->isAdmin() || $user->isExternal() || ($user->role && $user->role->full_access)) {
            $rootAuthoritiesQuery = Authority::withoutGlobalScopes()->whereNull('parent_id')->orderBy('agency_name');
            if (! $user->isAdmin() && ! ($user->role && $user->role->full_access)) {
                $adminRootId = $user->administrative_scope_id ?: $user->authority_id;
                if ($adminRootId) {
                    $rootAuthoritiesQuery = Authority::withoutGlobalScopes()->where('id', $adminRootId);
                }
            }

            $rootAuthorities = $rootAuthoritiesQuery->get();
            foreach ($rootAuthorities as $auth) {
                $tree[] = $this->buildAuthorityTreeNode($auth, $user);
            }
        }

        return $tree;
    }

    protected function buildEntityTreeNode(InternalEntity $entity, User $user): array
    {
        $effective = $this->resolveEffectiveLetterhead($user, OrganizationResolver::INTERNAL, $entity->id);
        $ownLetterhead = OrganizationLetterhead::withoutGlobalScopes()
            ->where('organization_type', OrganizationResolver::INTERNAL)
            ->where('organization_id', $entity->id)
            ->where('is_active', true)
            ->first();

        $children = [];
        $childEntities = InternalEntity::withoutGlobalScopes()->where('parent_id', $entity->id)->orderBy('name')->get();
        foreach ($childEntities as $child) {
            $children[] = $this->buildEntityTreeNode($child, $user);
        }

        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'type' => 'internal',
            'type_label' => 'جهة داخلية',
            'code' => $entity->entity_code,
            'has_own_letterhead' => $ownLetterhead && $ownLetterhead->inheritance_mode === 'own',
            'own_letterhead_id' => $ownLetterhead?->id,
            'effective_letterhead_name' => $effective->letterhead->name,
            'effective_letterhead_id' => $effective->letterhead->id,
            'is_inherited' => $effective->isInherited,
            'source_type' => $effective->sourceType,
            'source_org_name' => $effective->sourceOrganizationName,
            'explanation' => $effective->explanation,
            'can_manage' => $this->canUserManageOrganization($user, 'internal', $entity->id),
            'children' => $children,
        ];
    }

    protected function buildAuthorityTreeNode(Authority $authority, User $user): array
    {
        $effective = $this->resolveEffectiveLetterhead($user, OrganizationResolver::EXTERNAL, $authority->id);
        $ownLetterhead = OrganizationLetterhead::withoutGlobalScopes()
            ->where('organization_type', OrganizationResolver::EXTERNAL)
            ->where('organization_id', $authority->id)
            ->where('is_active', true)
            ->first();

        $children = [];
        $childAuthorities = Authority::withoutGlobalScopes()->where('parent_id', $authority->id)->orderBy('agency_name')->get();
        foreach ($childAuthorities as $child) {
            $children[] = $this->buildAuthorityTreeNode($child, $user);
        }

        $authName = $authority->agency_name ?? $authority->name ?? '---';

        return [
            'id' => $authority->id,
            'name' => $authName,
            'type' => 'external',
            'type_label' => 'جهة خارجية',
            'code' => null,
            'has_own_letterhead' => $ownLetterhead && $ownLetterhead->inheritance_mode === 'own',
            'own_letterhead_id' => $ownLetterhead?->id,
            'effective_letterhead_name' => $effective->letterhead->name,
            'effective_letterhead_id' => $effective->letterhead->id,
            'is_inherited' => $effective->isInherited,
            'source_type' => $effective->sourceType,
            'source_org_name' => $effective->sourceOrganizationName,
            'explanation' => $effective->explanation,
            'can_manage' => $this->canUserManageOrganization($user, 'external', $authority->id),
            'children' => $children,
        ];
    }
}

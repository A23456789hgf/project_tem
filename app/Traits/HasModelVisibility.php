<?php

namespace App\Traits;

use App\Models\SystemSetting;
use App\Scopes\DomainScope;
use App\Services\SchemaCache;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Trait HasModelVisibility
 *
 * Provides a 'visibleToUser' scope for models that leverages the
 * administrative and geographic visibility logic.
 */
trait HasModelVisibility
{
    /**
     * Scope a query to only include records visible to the current user.
     * This implements the same logic as HandlesDataVisibility but as a model scope.
     *
     * @param  string|null  $module  The module identifier (defaults to table name)
     */
    public function scopeVisibleToUser(Builder $query, ?string $module = null): Builder
    {
        // 0. Global System Visibility Controls (Toggleable by Admin)
        $activeSource = SystemSetting::get('active_entity_source', 'both');
        if ($this->getTable() === 'internal_entities') {
            if ($activeSource === 'external') {
                return $query->whereRaw('0=1');
            }
            if (SystemSetting::get('visibility_internal', '1') === '0') {
                return $query->whereRaw('0=1');
            }
        } elseif ($this->getTable() === 'authorities') {
            if ($activeSource === 'internal') {
                return $query->whereRaw('0=1');
            }
            if (SystemSetting::get('visibility_external', '1') === '0') {
                return $query->whereRaw('0=1');
            }
        }

        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('0=1');
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        $module = $module ?? $this->getTable();

        return app(UserDataScopeService::class)->applyScope($query, $user, $module);
    }

    /**
     * Scope a query to only include records the user is authorized to "add to"
     * or select in parent-child relationships.
     */
    public function scopeAddableToUser(Builder $query, ?string $module = null): Builder
    {
        $user = Auth::user();
        if (! $user) {
            return $query->whereRaw('0=1');
        }

        // Resolve module name
        $module = $module ?? $this->getTable();
        $scopeService = new DomainScope;
        $module = $scopeService->resolveModule($this);

        // Use getEntityAddScope for entity-type modules
        $scope = $user->getEntityAddScope($module);

        if ($scope === 'all') {
            return $query;
        }

        if ($scope === 'none') {
            return $query->whereRaw('0=1');
        }

        $userGovId = $user->getAssignedGovernorateId();
        $userDirId = $user->getAssignedDirectorateId();

        if ($scope === 'same_governorate') {
            if ($userGovId) {
                return $query->where('governorate_id', $userGovId);
            }

            return $query;
        }

        if ($scope === 'same_directorate') {
            if ($userDirId) {
                return $query->where('directorate_id', $userDirId);
            }

            return $query;
        }

        return $query->whereRaw('0=1');
    }

    /**
     * Apply visibility filters to a model query.
     */
    protected function applyModelVisibilityFilters(Builder $query, $user, $internalEntity, string $adminScope, string $geoScope, string $module): Builder
    {
        // Get user authority for geographic filtering
        $userAuthority = $internalEntity->authority;

        // Apply geographic filter
        $this->applyGeographicScopeToModel($query, $userAuthority, $geoScope, $module);

        // Apply administrative filter
        $this->applyAdministrativeScopeToModel($query, $user, $adminScope, $module);

        return $query;
    }

    /**
     * Apply geographic scope filter to a model query.
     * Handles both direct columns and relationships.
     */
    protected function applyGeographicScopeToModel(Builder $query, $userAuthority, string $geoScope, string $module): void
    {
        if ($geoScope === 'all') {
            return;
        }

        $model = $query->getModel();
        $table = $model->getTable();
        $hasDirectGeoCols = SchemaCache::hasColumn($table, 'governorate_id') && SchemaCache::hasColumn($table, 'directorate_id');

        if (! $userAuthority) {
            // Non-geo user: only records with NULL geographic fields
            if ($hasDirectGeoCols) {
                $query->where(function ($q) {
                    $q->whereNull('governorate_id')->whereNull('directorate_id');
                });
            }

            return;
        }

        // Build geographic condition
        if ($hasDirectGeoCols) {
            $query->where(function ($q) use ($userAuthority, $geoScope) {
                // Allow NULL locations
                $q->where(function ($nullQ) {
                    $nullQ->whereNull('governorate_id')->whereNull('directorate_id');
                })
                // Or match user's geographic scope
                    ->orWhere(function ($matchQ) use ($userAuthority, $geoScope) {
                        if ($geoScope === 'same_governorate') {
                            $matchQ->where('governorate_id', $userAuthority->governorate_id);
                        } elseif ($geoScope === 'same_directorate') {
                            $matchQ->where('directorate_id', $userAuthority->directorate_id);
                        }
                    });
            });
        } else {
            // For models without direct geo columns, check relationships
            // Projects: check through locations relationship
            if (method_exists($model, 'locations')) {
                $query->whereHas('locations', function ($locQ) use ($userAuthority, $geoScope) {
                    if ($geoScope === 'same_governorate') {
                        $locQ->where('governorate_id', $userAuthority->governorate_id);
                    } elseif ($geoScope === 'same_directorate') {
                        $locQ->where('directorate_id', $userAuthority->directorate_id);
                    }
                });
            }
            // For models with authority relationship
            elseif (SchemaCache::hasColumn($table, 'authority_id')) {
                $query->whereHas('authority', function ($authQ) use ($userAuthority, $geoScope) {
                    if ($geoScope === 'same_governorate') {
                        $authQ->where('governorate_id', $userAuthority->governorate_id);
                    } elseif ($geoScope === 'same_directorate') {
                        $authQ->where('directorate_id', $userAuthority->directorate_id);
                    }
                });
            }
            // For models with entity relationship
            elseif (SchemaCache::hasColumn($table, 'entity_id') || SchemaCache::hasColumn($table, 'internal_entity_id')) {
                $entityCol = SchemaCache::hasColumn($table, 'entity_id') ? 'entity_id' : 'internal_entity_id';
                $query->whereHas('entity', function ($entityQ) use ($userAuthority, $geoScope) {
                    if ($geoScope === 'same_governorate') {
                        $entityQ->where('governorate_id', $userAuthority->governorate_id);
                    } elseif ($geoScope === 'same_directorate') {
                        $entityQ->where('directorate_id', $userAuthority->directorate_id);
                    }
                });
            }
        }
    }

    /**
     * Apply administrative scope filter to a model query.
     */
    protected function applyAdministrativeScopeToModel(Builder $query, $user, string $adminScope, string $module): void
    {
        if ($adminScope === 'all') {
            return;
        }

        $model = $query->getModel();
        $table = $model->getTable();

        // Determine column names
        $userCol = $this->detectUserColumn($table);
        $entityCol = $this->detectEntityColumn($table, $module);

        switch ($adminScope) {
            case 'user':
                if ($userCol) {
                    $query->where($userCol, $user->id);
                } else {
                    $query->whereRaw('0=1');
                }
                break;

            case 'own':
            case 'own_entity':
                if ($module === 'correspondence') {
                    $query->where(function ($sub) use ($user) {
                        if ($user->isExternal()) {
                            $sub->where('sender_authority_id', $user->authority_id)
                                ->orWhere('recipient_authority_id', $user->authority_id);
                        } else {
                            $sub->where('sender_entity_id', $user->entity_id)
                                ->orWhere('recipient_entity_id', $user->entity_id);
                        }
                    });
                } elseif ($entityCol) {
                    $val = $user->isExternal() ? $user->authority_id : $user->entity_id;
                    $query->where($entityCol, $val);
                } else {
                    $query->whereRaw('0=1');
                }
                break;

            case 'parent':
                if ($user->isExternal()) {
                    if ($module === 'correspondence') {
                        $query->where(function ($sub) use ($user) {
                            $sub->where('sender_authority_id', $user->authority_id)
                                ->orWhere('recipient_authority_id', $user->authority_id);
                        });
                    } elseif ($entityCol) {
                        $query->where($entityCol, $user->authority_id);
                    } else {
                        $query->whereRaw('0=1');
                    }
                } else {
                    $accessibleEntityIds = $this->getChildEntityIds($user->entity_id);
                    if ($module === 'correspondence') {
                        $query->where(function ($sub) use ($accessibleEntityIds) {
                            $sub->whereIn('sender_entity_id', $accessibleEntityIds)
                                ->orWhereIn('recipient_entity_id', $accessibleEntityIds);
                        });
                    } elseif ($entityCol) {
                        $query->whereIn($entityCol, $accessibleEntityIds);
                    } else {
                        $query->whereRaw('0=1');
                    }
                }
                break;

            default:
                $query->whereRaw('0=1');
                break;
        }
    }

    /**
     * Detect user-related column in a table.
     */
    protected function detectUserColumn(string $table): ?string
    {
        $candidates = ['created_by', 'created_by_user_id', 'user_id', 'processed_by'];
        foreach ($candidates as $col) {
            if (SchemaCache::hasColumn($table, $col)) {
                return $col;
            }
        }

        return null;
    }

    /**
     * Detect entity-related column in a table.
     */
    protected function detectEntityColumn(string $table, string $module): ?string
    {
        $user = auth()->user();
        $isExternal = $user && $user->isExternal();

        if ($module === 'correspondence') {
            return $isExternal ? 'sender_authority_id' : 'sender_entity_id';
        }
        if ($module === 'planning') {
            return $isExternal ? 'submitting_authority_id' : 'submitting_entity_id';
        }
        if ($module === 'internal_entities' || $module === 'authorities') {
            return 'id';
        }

        $candidates = $isExternal
            ? ['authority_id', 'submitting_authority_id', 'sender_authority_id', 'creator_authority_id', 'assigned_authority_id']
            : ['internal_entity_id', 'entity_id', 'submitting_entity_id', 'sender_entity_id', 'created_by_entity', 'assigned_entity_id'];

        foreach ($candidates as $col) {
            if (SchemaCache::hasColumn($table, $col)) {
                return $col;
            }
        }

        return null;
    }

    /**
     * Resolve administrative scope for a module.
     * Uses caching for performance.
     */
    protected function resolveAdminScope($user, string $module): string
    {
        $cacheKey = "admin_scope_{$user->id}_{$module}_".($user->role_id ?? 'null');

        if (! $scope = Cache::get($cacheKey)) {
            $scope = 'none';

            // Check permission slugs first
            $permissionMap = [
                "{$module}.view-all" => 'all',
                "{$module}.view-parent" => 'parent',
                "{$module}.view-own" => 'own',
                "{$module}.view-user" => 'user',
            ];

            foreach ($permissionMap as $permSlug => $permScope) {
                if ($user->hasPermission($permSlug)) {
                    $scope = $permScope;
                    break;
                }
            }

            // Fallback to role meta
            if ($scope === 'none' && $user->role && isset($user->role->module_scopes[$module])) {
                $scope = $user->role->module_scopes[$module];
            }

            Cache::put($cacheKey, $scope, now()->addMinutes(5));
        }

        return $scope;
    }

    /**
     * Resolve geographic scope for a module.
     * Uses caching for performance.
     */
    protected function resolveGeoScope($user, string $module): string
    {
        $cacheKey = "geo_scope_{$user->id}_{$module}_".($user->role_id ?? 'null');

        if (! $scope = Cache::get($cacheKey)) {
            $scope = 'none';

            // Check permission slugs
            $geoPermissionMap = [
                "{$module}.scope-all" => 'all',
                "{$module}.scope-governorate" => 'same_governorate',
                "{$module}.scope-directorate" => 'same_directorate',
            ];

            foreach ($geoPermissionMap as $permSlug => $permScope) {
                if ($user->hasPermission($permSlug)) {
                    $scope = $permScope;
                    break;
                }
            }

            // Fallback to role meta
            if ($scope === 'none' && $user->role && isset($user->role->module_geo_scopes[$module])) {
                $scope = $user->role->module_geo_scopes[$module];
            }

            Cache::put($cacheKey, $scope, now()->addMinutes(5));
        }

        return $scope;
    }

    /**
     * Get all child entity IDs for hierarchical entity scope.
     * Uses caching for performance.
     */
    protected function getChildEntityIds(int $entityId): array
    {
        $cacheKey = "child_entity_ids_{$entityId}";
        if (! $ids = Cache::get($cacheKey)) {
            $allIds = [$entityId];
            $toProcess = [$entityId];

            while (! empty($toProcess)) {
                $currentBatch = DB::table('internal_entities')
                    ->whereIn('parent_id', $toProcess)
                    ->pluck('id')
                    ->toArray();

                if (empty($currentBatch)) {
                    break;
                }

                $allIds = array_merge($allIds, $currentBatch);
                $toProcess = $currentBatch;
            }

            $ids = array_unique($allIds);
            Cache::put($cacheKey, $ids, now()->addMinutes(10));
        }

        return $ids;
    }
}

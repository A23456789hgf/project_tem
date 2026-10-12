<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trait HasEntityVisibility
 *
 * Provides entity-based data filtering based on user permissions and geographic scope.
 *
 * RULE PRIORITY:
 *   1. If user has geographic data (govId / dirId) → Apply GEO filter AND THEN ADMIN filter (combined AND).
 *   2. If user has no geographic data             → Apply ADMINISTRATIVE SCOPE ONLY (recursive).
 *
 * NON-GEOGRAPHIC ENTITIES RULE:
 *   Entities with NULL governorate/directorate are included in geo-filtered results
 *   (via OR NULL), then further restricted by admin scope.
 *   This ensures central/ministry-level records are never hidden outright.
 */
trait HasEntityVisibility
{
    protected static $schemaColumnsCache = [];

    // -----------------------------------------------------------------------
    // Schema Helper
    // -----------------------------------------------------------------------

    protected function hasColumnCached(string $table, string $column): bool
    {
        if (! isset(static::$schemaColumnsCache[$table])) {
            static::$schemaColumnsCache[$table] = Schema::getColumnListing($table);
        }

        return in_array($column, static::$schemaColumnsCache[$table]);
    }

    // -----------------------------------------------------------------------
    // PRIMARY ENTRY POINT – Geo + Admin combined
    // -----------------------------------------------------------------------

    /**
     * Primary scope for all business modules.
     *
     * Applies GEO filter first (if user has geographic assignment),
     * then applies ADMIN filter on top (always).
     * Both filters are ANDed together.
     */
    public function scopeVisibleToUserForModule(Builder $query, string $module): Builder
    {
        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // GEO + ADMIN combined

        $userGovId = $user->getAssignedGovernorateId();
        $userDistId = $user->getAssignedDirectorateId();

        $geoScope = $user->getModuleGeoScope($module);
        $adminScope = $user->role ? ($user->role->module_scopes[$module] ?? 'own') : 'own';

        // ── User HAS geographic assignment ──────────────────────────────────
        if ($userGovId || $userDistId) {
            switch ($geoScope) {

                case 'none':
                    return $query->whereRaw('0=1');

                case 'all':
                    // Critical: Even with 'all' geo scope, we MUST apply admin filter.
                    return $this->applyAdminScopeFilter($query, $user, $adminScope);

                case 'same_governorate':
                case 'same_directorate':
                    // 1. Apply geographic filter first (includes OR NULL for non-geo entities).
                    $query = $this->applyMandatoryGeoFilter($query, $user, $geoScope);

                    // 2. Apply admin scope on top (ANDed).
                    return $this->applyAdminScopeFilter($query, $user, $adminScope);

                case 'custom':
                    // Custom → use administrative scope only.
                    return $this->applyAdminScopeFilter($query, $user, $adminScope);

                default:
                    return $query->whereRaw('0=1');
            }
        }

        // ── User has NO geographic assignment → admin scope only ────────────
        return $this->applyAdminScopeFilter($query, $user, $adminScope);
    }

    // -----------------------------------------------------------------------
    // ENTITY TABLE SCOPE
    // (InternalEntity & Authority use entity_display_scope, not module_geo_scopes)
    // -----------------------------------------------------------------------

    /**
     * Scope for entity tables (internal_entities / authorities).
     * Reads geo scope from entity_display_scope (set in the permissions matrix),
     * then ANDs it with the administrative scope.
     *
     * @param  string  $entityType  'internal_entities' or 'authorities'
     */
    public function scopeVisibleToUserForEntityModule(Builder $query, string $entityType): Builder
    {
        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Read geo scope from entity_display_scope (saved by the permissions matrix).
        $geoScope = $user->getEntityDisplayScope($entityType);
        $adminScope = $user->role ? ($user->role->module_scopes[$entityType] ?? 'own') : 'own';

        $userGovId = $user->getAssignedGovernorateId();
        $userDistId = $user->getAssignedDirectorateId();

        if ($userGovId || $userDistId) {
            switch ($geoScope) {

                case 'none':
                    return $query->whereRaw('0=1');

                case 'all':
                    // No geographic restriction, admin scope still applies.
                    return $this->applyAdminScopeFilter($query, $user, $adminScope);

                case 'same_governorate':
                case 'same_directorate':
                    // Geo filter first, then admin scope on top (AND).
                    $query = $this->applyMandatoryGeoFilter($query, $user, $geoScope);

                    return $this->applyAdminScopeFilter($query, $user, $adminScope);

                default:
                    // Any unknown/custom value → fall back to admin scope.
                    return $this->applyAdminScopeFilter($query, $user, $adminScope);
            }
        }

        // No geographic data → admin scope only.
        return $this->applyAdminScopeFilter($query, $user, $adminScope);
    }

    // -----------------------------------------------------------------------
    // BACKWARD COMPATIBILITY SCOPE
    // -----------------------------------------------------------------------

    /**
     * Legacy entry point kept for backward compatibility.
     * Prefer scopeVisibleToUserForModule() for new code.
     */
    public function scopeVisibleToUser(Builder $query, ?string $permissionPrefix = null): Builder
    {
        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($permissionPrefix) {
            return $this->scopeVisibleToUserForModule($query, $permissionPrefix);
        }

        if ($user->entity_id) {
            return $this->applyAdminScopeFilter($query, $user, 'own');
        }

        return $query;
    }

    // -----------------------------------------------------------------------
    // ENTITY OWNERSHIP SCOPE
    // -----------------------------------------------------------------------

    /**
     * Scope a query to filter by the entity that owns records.
     */
    public function scopeForUserEntity(Builder $query, $entityId = null): Builder
    {
        $user = auth()->user();
        $entityId = $entityId ?: $user?->entity_id;

        $hasFullAccess = method_exists($user, 'isAdmin') && $user->isAdmin();
        if (! $entityId && ! $hasFullAccess) {
            return $query->whereRaw('1 = 0');
        }
        if ($hasFullAccess) {
            return $query;
        }

        $table = $this->getTable();

        if ($this->hasColumnCached($table, 'referring_entity_id') && $this->hasColumnCached($table, 'referred_entity_id')) {
            return $query->where(function ($q) use ($entityId) {
                $q->where('referring_entity_id', $entityId)->orWhere('referred_entity_id', $entityId);
            });
        }

        if ($this->hasColumnCached($table, 'entity_id')) {
            return $query->where('entity_id', $entityId);
        }

        if ($this->hasColumnCached($table, 'created_by_entity')) {
            return $query->where(function ($q) use ($entityId) {
                $q->where('created_by_entity', $entityId)
                    ->orWhere('created_by_entity', (string) $entityId);
            });
        }

        return $query;
    }

    // -----------------------------------------------------------------------
    // GEO FILTER
    // -----------------------------------------------------------------------

    /**
     * Applies geographic filter based on same_governorate / same_directorate scope.
     *
     * KEY RULE: Entities with NULL governorate/directorate are INCLUDED via OR NULL.
     * This ensures central/ministry-level records are visible and then further
     * controlled by the admin scope layer.
     */
    private function applyMandatoryGeoFilter(Builder $query, $user, string $geoScope): Builder
    {
        $table = $query->getModel()->getTable();

        if ($geoScope === 'same_governorate') {
            $govId = $user->getAssignedGovernorateId();
            if (! $govId) {
                return $query->whereRaw('0=1');
            }

            // ── Internal Entities / Authorities ─────────────────────────────
            if (in_array($table, ['internal_entities', 'authorities'])) {
                return $query->where(function ($q) use ($govId) {
                    $q->where('governorate_id', $govId)
                        ->orWhereNull('governorate_id');
                });
            }

            // ── Projects / Empowerment / Plans ──────────────────────────────
            if (in_array($table, ['projects', 'empowerment_projects'])) {
                return $this->applyEntityGeoFilter($query, 'governorate_id', $govId, 'creator_entity_id');
            }
            if ($table === 'plans') {
                return $this->applyEntityGeoFilter($query, 'governorate_id', $govId, 'submitting_entity_id');
            }

            // ── Correspondences ─────────────────────────────────────────────
            if ($table === 'correspondences') {
                return $query->where(function ($q) use ($govId) {
                    $this->applyEntityGeoFilter($q, 'governorate_id', $govId, 'sender_entity_id');
                    $q->orWhere(function ($subQ) use ($govId) {
                        $this->applyEntityGeoFilter($subQ, 'governorate_id', $govId, 'recipient_entity_id');
                    });
                    $q->orWhereHas('referrals', function ($refQ) use ($govId) {
                        $this->applyEntityGeoFilter($refQ, 'governorate_id', $govId, 'referred_to_entity_id');
                    });
                });
            }

            // ── Fallback ─────────────────────────────────────────────────────
            return $this->applyEntityGeoFilter($query, 'governorate_id', $govId, 'creator_entity_id');
        }

        if ($geoScope === 'same_directorate') {
            $dirId = $user->getAssignedDirectorateId();
            if (! $dirId) {
                return $query->whereRaw('0=1');
            }

            // ── Internal Entities / Authorities ─────────────────────────────
            if (in_array($table, ['internal_entities', 'authorities'])) {
                return $query->where(function ($q) use ($dirId) {
                    $q->where('directorate_id', $dirId)
                        ->orWhereNull('directorate_id');
                });
            }

            // ── Projects / Empowerment / Plans ──────────────────────────────
            if (in_array($table, ['projects', 'empowerment_projects'])) {
                return $this->applyEntityGeoFilter($query, 'directorate_id', $dirId, 'creator_entity_id');
            }
            if ($table === 'plans') {
                return $this->applyEntityGeoFilter($query, 'directorate_id', $dirId, 'submitting_entity_id');
            }

            // ── Correspondences ─────────────────────────────────────────────
            if ($table === 'correspondences') {
                return $query->where(function ($q) use ($dirId) {
                    $this->applyEntityGeoFilter($q, 'directorate_id', $dirId, 'sender_entity_id');
                    $q->orWhere(function ($subQ) use ($dirId) {
                        $this->applyEntityGeoFilter($subQ, 'directorate_id', $dirId, 'recipient_entity_id');
                    });
                    $q->orWhereHas('referrals', function ($refQ) use ($dirId) {
                        $this->applyEntityGeoFilter($refQ, 'directorate_id', $dirId, 'referred_to_entity_id');
                    });
                });
            }

            // ── Fallback ─────────────────────────────────────────────────────
            return $this->applyEntityGeoFilter($query, 'directorate_id', $dirId, 'creator_entity_id');
        }

        return $query->whereRaw('0=1');
    }

    /**
     * Subquery filter: matches the geo column via an entity reference column.
     *
     * Handles both:
     *  - String-based entity column (created_by_entity stores entity name)
     *  - Numeric-based entity column (created_by_entity stores entity ID cast as string)
     *
     * Includes entities with NULL geo column (non-geographic/central entities).
     */
    private function applyEntityGeoFilter(
        Builder $query,
        string $column,
        int $targetId,
        string $entityColumn = 'creator_entity_id'
    ): Builder {
        // If the column is an integer FK (creator_entity_id), use a simple subquery on the id.
        if ($entityColumn === 'creator_entity_id') {
            return $query->whereIn($entityColumn, function ($sub) use ($column, $targetId) {
                $sub->select('id')
                    ->from('internal_entities')
                    ->where(function ($entQ) use ($column, $targetId) {
                        $entQ->where($column, $targetId)
                            ->orWhereNull($column)      // Include non-geographic entities
                            ->orWhereExists(function ($ex) use ($column, $targetId) {
                                $ex->from('authorities')
                                    ->whereColumn('authorities.id', 'internal_entities.authority_id')
                                    ->where($column, $targetId);
                            });
                    });
            });
        }

        // Legacy path: entityColumn is a string (name or CAST(id AS CHAR))
        return $query->where(function ($q) use ($column, $targetId, $entityColumn) {

            // Match by entity name
            $q->whereIn($entityColumn, function ($sub) use ($column, $targetId) {
                $sub->select('name')
                    ->from('internal_entities')
                    ->where(function ($entQ) use ($column, $targetId) {
                        $entQ->where($column, $targetId)
                            ->orWhereNull($column)       // Include non-geographic entities
                            ->orWhereExists(function ($ex) use ($column, $targetId) {
                                $ex->from('authorities')
                                    ->whereColumn('authorities.id', 'internal_entities.authority_id')
                                    ->where($column, $targetId);
                            });
                    });
            })
            // Match by entity ID (stored as string)
                ->orWhereIn($entityColumn, function ($sub) use ($column, $targetId) {
                    $sub->select(DB::raw('CAST(id AS CHAR)'))
                        ->from('internal_entities')
                        ->where(function ($entQ) use ($column, $targetId) {
                            $entQ->where($column, $targetId)
                                ->orWhereNull($column)       // Include non-geographic entities
                                ->orWhereExists(function ($ex) use ($column, $targetId) {
                                    $ex->from('authorities')
                                        ->whereColumn('authorities.id', 'internal_entities.authority_id')
                                        ->where($column, $targetId);
                                });
                        });
                });
        });
    }

    // -----------------------------------------------------------------------
    // ADMIN SCOPE FILTER
    // -----------------------------------------------------------------------

    /**
     * Applies administrative scope filtering.
     *
     * Supported values:
     *   none   – No access (0=1)
     *   user   – Only records created by this user
     *   own    – Only records belonging to the user's entity
     *   parent – User's entity + all descendant entities recursively (unlimited depth)
     *   all    – All records (no restriction)
     *
     * The 'parent' scope uses the user's OWN entity as the root,
     * then includes all child/descendant entities recursively.
     */
    protected function applyAdminScopeFilter(Builder $query, $user, string $adminScope): Builder
    {
        $table = $query->getModel()->getTable();

        // Detect user column
        $userCols = ['created_by_user_id', 'created_by', 'user_id', 'processed_by'];
        $foundUserCol = null;
        foreach ($userCols as $col) {
            if ($this->hasColumnCached($table, $col)) {
                $foundUserCol = $col;
                break;
            }
        }

        // Detect entity columns
        $isExternal = $user && $user->isExternal();
        $entityColCandidates = $isExternal
            ? ['authority_id', 'submitting_authority_id', 'sender_authority_id', 'creator_authority_id', 'assigned_authority_id']
            : [
                'entity_id', 'submitting_entity_id', 'submitting_entity',
                'created_by_entity', 'sender_entity_id', 'recipient_entity_id',
            ];

        $foundEntityCols = [];
        foreach ($entityColCandidates as $col) {
            if ($this->hasColumnCached($table, $col)) {
                $foundEntityCols[] = $col;
            }
        }

        switch ($adminScope) {

            // ── None: block all ─────────────────────────────────────────────
            case 'none':
                return $query->whereRaw('0=1');

                // ── User: only records created by this user ──────────────────────
            case 'user':
                if ($foundUserCol) {
                    return $query->where($foundUserCol, $user->id);
                }

                return $query->whereRaw('0=1');

                // ── Own: only records belonging to the user's entity ─────────────
            case 'own':
                if ($user->isExternal()) {
                    $entityId = $user->authority_id;
                    $entityName = $user->department ?: (
                        $entityId
                            ? DB::table('authorities')->where('id', $entityId)->value('agency_name')
                            : null
                    );
                } else {
                    $entityId = $user->entity_id;
                    $entityName = $user->department ?: (
                        $entityId
                            ? DB::table('internal_entities')->where('id', $entityId)->value('name')
                            : null
                    );
                }

                return $query->where(function ($q) use (
                    $user, $foundUserCol, $foundEntityCols, $entityId, $entityName, $table
                ) {
                    if ($foundUserCol) {
                        $q->where($foundUserCol, $user->id);
                    }
                    foreach ($foundEntityCols as $col) {
                        if ($entityId) {
                            $q->orWhere($col, $entityId);
                        }
                        if ($entityName) {
                            $q->orWhere($col, $entityName);
                        }
                        if ($entityId) {
                            $q->orWhere($col, (string) $entityId);
                        }
                    }
                    if ($table === 'correspondences' && $entityId) {
                        $q->orWhereHas('referrals', function ($refQ) use ($entityId) {
                            $refQ->where('referred_to_entity_id', $entityId);
                        });
                    }
                });

                // ── Parent: user's entity + all descendants (unlimited depth) ────
                // FIX: Use user's OWN entity as root (not its parent).
            case 'parent':
            case 'dept_in_gen_dir':
            case 'gen_dir_in_sector':
                if ($user->isExternal()) {
                    return $this->applyAdminScopeFilter($query, $user, 'own');
                }

                $userEntity = $user->entity_id
                    ? DB::table('internal_entities')
                        ->where('id', $user->entity_id)
                        ->first()
                    : null;
                if (! $userEntity) {
                    // Fall back to 'own' scope if no entity is assigned
                    return $this->applyAdminScopeFilter($query, $user, 'own');
                }

                // Root = user's own entity; get all descendants recursively
                $descendantIds = $this->getDescendantEntityIds($userEntity->id);
                $allAccessibleIds = array_merge([$userEntity->id], $descendantIds);

                $allAccessibleNames = DB::table('internal_entities')
                    ->whereIn('id', $allAccessibleIds)
                    ->pluck('name')
                    ->toArray();

                return $query->where(function ($q) use (
                    $foundEntityCols, $allAccessibleIds, $allAccessibleNames, $table
                ) {
                    $first = true;
                    foreach ($foundEntityCols as $col) {
                        if ($first) {
                            $q->whereIn($col, $allAccessibleIds)
                                ->orWhereIn($col, $allAccessibleNames);
                            $first = false;
                        } else {
                            $q->orWhereIn($col, $allAccessibleIds)
                                ->orWhereIn($col, $allAccessibleNames);
                        }
                    }

                    if ($table === 'correspondences') {
                        $closure = fn ($refQ) => $refQ->whereIn('referred_to_entity_id', $allAccessibleIds);
                        if ($first) {
                            $q->whereHas('referrals', $closure);
                            $first = false;
                        } else {
                            $q->orWhereHas('referrals', $closure);
                        }
                    }

                    if ($first) {
                        $q->whereRaw('0=1');
                    }
                });

                // ── All: no restriction ──────────────────────────────────────────
            case 'all':
                return $query;

                // ── Default: block (safe) ────────────────────────────────────────
            default:
                return $query->whereRaw('0=1');
        }
    }

    // -----------------------------------------------------------------------
    // RECURSIVE HIERARCHY HELPER
    // -----------------------------------------------------------------------

    /**
     * Recursively collect all descendant entity IDs (unlimited depth).
     * Uses iterative BFS to avoid PHP stack overflow on deep hierarchies.
     */
    private function getDescendantEntityIds(int $parentId): array
    {
        $allIds = [];
        $toProcess = [$parentId];
        $visited = [$parentId];

        while (! empty($toProcess)) {
            $currentBatch = DB::table('internal_entities')
                ->whereIn('parent_id', $toProcess)
                ->pluck('id')
                ->toArray();

            $currentBatch = array_diff($currentBatch, $visited);
            if (empty($currentBatch)) {
                break;
            }

            $visited = array_merge($visited, $currentBatch);
            $allIds = array_merge($allIds, $currentBatch);
            $toProcess = $currentBatch;
        }

        return array_unique($allIds);
    }
}

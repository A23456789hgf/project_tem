<?php

namespace App\Services;

use App\Models\Authority;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Class UserDataScopeService
 *
 * Centralized, dynamic, system-wide record visibility service based on
 * each user's assigned administrative or geographic scope.
 */
class UserDataScopeService
{
    /**
     * In-memory cache for resolved user scopes within a single request lifecycle.
     */
    protected static array $resolvedScopeCache = [];

    protected static array $govEntityCache = [];

    protected static array $govAuthCache = [];

    protected static array $dirEntityCache = [];

    protected static array $dirAuthCache = [];

    /**
     * Clear all static request-level scope caches.
     */
    public static function clearCache(): void
    {
        self::$resolvedScopeCache = [];
        self::$govEntityCache = [];
        self::$govAuthCache = [];
        self::$dirEntityCache = [];
        self::$dirAuthCache = [];
    }

    /**
     * Get the resolved internal entity IDs authorized for the given user.
     *
     * @return int[]
     */
    public function getUserEntityIds(?User $user = null): array
    {
        return $this->getEffectiveScope($user)['entity_ids'];
    }

    /**
     * Get the resolved external authority IDs authorized for the given user.
     *
     * @return int[]
     */
    public function getUserAuthorityIds(?User $user = null): array
    {
        return $this->getEffectiveScope($user)['authority_ids'];
    }

    /**
     * Check if a specific internal entity ID is within the user's scope.
     */
    public function isEntityAllowed(int $entityId, ?User $user = null): bool
    {
        $scope = $this->getEffectiveScope($user);
        if ($scope['is_admin'] || $scope['is_all']) {
            return true;
        }

        return in_array($entityId, $scope['entity_ids'], true);
    }

    /**
     * Check if a specific authority ID is within the user's scope.
     */
    public function isAuthorityAllowed(int $authorityId, ?User $user = null): bool
    {
        $scope = $this->getEffectiveScope($user);
        if ($scope['is_admin'] || $scope['is_all']) {
            return true;
        }

        return in_array($authorityId, $scope['authority_ids'], true);
    }

    /**
     * Resolve the complete effective scope structure for a user.
     *
     * @return array{
     *     is_admin: bool,
     *     is_all: bool,
     *     user_id: int|null,
     *     is_external: bool,
     *     admin_entity_ids: int[],
     *     geo_entity_ids: int[],
     *     entity_ids: int[],
     *     authority_ids: int[],
     *     entity_names: string[],
     *     authority_names: string[]
     * }
     */
    public function getEffectiveScope(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return [
                'is_admin' => false,
                'is_all' => false,
                'user_id' => null,
                'is_external' => false,
                'admin_entity_ids' => [],
                'geo_entity_ids' => [],
                'entity_ids' => [],
                'authority_ids' => [],
                'entity_names' => [],
                'authority_names' => [],
            ];
        }

        $userId = $user->id;
        $activeAdminScope = Session::get('selected_administrative_scope_id', $user->administrative_scope_id);
        $activeEntityId = Session::get('selected_entity_id', $user->entity_id);
        $activeGeoScopes = Session::get('selected_geographic_scopes');

        $cacheKey = "user_scope_{$userId}_"
            .(is_array($activeAdminScope) ? implode(',', $activeAdminScope) : (string) ($activeAdminScope ?? '0')).'_'
            .(string) ($activeEntityId ?? '0').'_'
            .md5(json_encode($activeGeoScopes ?? ($user->relationLoaded('geographicScopes') ? $user->geographicScopes->toArray() : 'default')));

        if (isset(self::$resolvedScopeCache[$cacheKey])) {
            return self::$resolvedScopeCache[$cacheKey];
        }

        $isAdmin = (bool) ($user->isAdmin() || ($user->role && $user->role->full_access));
        if ($isAdmin) {
            $allInternalIds = DB::table('internal_entities')->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            $allAuthorityIds = DB::table('authorities')->pluck('id')->map(fn ($id) => (int) $id)->toArray();

            return self::$resolvedScopeCache[$cacheKey] = [
                'is_admin' => true,
                'is_all' => true,
                'user_id' => $userId,
                'is_external' => false,
                'admin_entity_ids' => $allInternalIds,
                'geo_entity_ids' => $allInternalIds,
                'entity_ids' => $allInternalIds,
                'authority_ids' => $allAuthorityIds,
                'entity_names' => [],
                'authority_names' => [],
            ];
        }

        $isExternal = (bool) ($user->isExternal() || ($user->organization_type === 'external' && $user->authority_id));

        // 1. Resolve Geographic Scope
        $geoResolved = $this->resolveGeographicScope($user, $activeGeoScopes);
        $isAllGeo = $geoResolved['is_all'];
        $geoEntityIds = $geoResolved['entity_ids'];
        $geoAuthorityIds = $geoResolved['authority_ids'];

        // 2. Resolve Administrative Scope
        $adminResolved = $this->resolveAdministrativeScope($user, $activeAdminScope, $isExternal);
        $adminEntityIds = $adminResolved['entity_ids'];
        $adminAuthorityIds = $adminResolved['authority_ids'];

        // 3. Fallback when NEITHER scope is configured
        $hasAdminScope = ! empty($adminEntityIds) || ! empty($adminAuthorityIds);
        $hasGeoScope = ! empty($geoEntityIds) || ! empty($geoAuthorityIds) || $isAllGeo;

        $fallbackEntityIds = [];
        $fallbackAuthorityIds = [];

        if (! $hasAdminScope && ! $hasGeoScope) {
            if ($isExternal) {
                $targetAuthId = (int) ($user->authority_id ?? 0);
                if ($targetAuthId > 0) {
                    $fallbackAuthorityIds = $this->getAuthorityDescendantIds([$targetAuthId]);
                }
            } else {
                $targetEntityId = (int) ($activeEntityId ?? $user->entity_id ?? 0);
                if ($targetEntityId > 0) {
                    $fallbackEntityIds = $this->getInternalEntityDescendantIds([$targetEntityId]);
                }
            }
        }

        // 4. Combine Scopes using Inclusive OR logic
        if ($isAllGeo) {
            $allInternalIds = DB::table('internal_entities')->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            $allAuthorityIds = DB::table('authorities')->pluck('id')->map(fn ($id) => (int) $id)->toArray();

            $finalEntityIds = $allInternalIds;
            $finalAuthorityIds = $allAuthorityIds;
            $isAll = true;
        } else {
            $finalEntityIds = array_values(array_unique(array_filter(
                array_merge($adminEntityIds, $geoEntityIds, $fallbackEntityIds),
                fn ($id) => is_numeric($id) && $id > 0
            )));

            $finalAuthorityIds = array_values(array_unique(array_filter(
                array_merge($adminAuthorityIds, $geoAuthorityIds, $fallbackAuthorityIds),
                fn ($id) => is_numeric($id) && $id > 0
            )));

            $isAll = false;
        }

        // 5. Fetch names for text matching
        $entityNames = [];
        if (! empty($finalEntityIds) && count($finalEntityIds) < 500) {
            $entityNames = DB::table('internal_entities')
                ->whereIn('id', $finalEntityIds)
                ->pluck('name')
                ->filter()
                ->map(fn ($n) => trim(preg_replace('/\s+/u', ' ', $n)))
                ->values()
                ->toArray();
        }

        $authorityNames = [];
        if (! empty($finalAuthorityIds) && count($finalAuthorityIds) < 500) {
            $authorityNames = DB::table('authorities')
                ->whereIn('id', $finalAuthorityIds)
                ->pluck('agency_name')
                ->filter()
                ->map(fn ($n) => trim(preg_replace('/\s+/u', ' ', $n)))
                ->values()
                ->toArray();
        }

        return self::$resolvedScopeCache[$cacheKey] = [
            'is_admin' => false,
            'is_all' => $isAll,
            'user_id' => $userId,
            'is_external' => $isExternal,
            'admin_entity_ids' => $adminEntityIds,
            'geo_entity_ids' => $geoEntityIds,
            'entity_ids' => $finalEntityIds,
            'authority_ids' => $finalAuthorityIds,
            'entity_names' => $entityNames,
            'authority_names' => $authorityNames,
        ];
    }

    /**
     * Resolve entities matching geographic scope rules.
     */
    protected function resolveGeographicScope(User $user, mixed $activeGeoScopes): array
    {
        if ($activeGeoScopes !== null) {
            $scopes = collect($activeGeoScopes);
        } else {
            $user->loadMissing('geographicScopes');
            $scopes = $user->geographicScopes ?? collect();
        }

        $entityIds = [];
        $authorityIds = [];
        $isAll = false;

        $hasExplicitScopes = $scopes->isNotEmpty();

        if ($hasExplicitScopes) {
            foreach ($scopes as $scope) {
                $govInput = is_array($scope) ? ($scope['governorate_id'] ?? null) : ($scope->governorate_id ?? null);
                $dirInput = is_array($scope) ? ($scope['directorate_id'] ?? null) : ($scope->directorate_id ?? null);

                // Rule 6: "All Governorates"
                if ($govInput === 'all' || $govInput === 0 || $govInput === '0' || ($govInput === null && empty($dirInput))) {
                    $isAll = true;
                    break;
                }

                $dirs = [];
                if (! empty($dirInput) && $dirInput !== 'all') {
                    if (is_array($dirInput)) {
                        $dirs = array_map('intval', array_filter($dirInput, fn ($d) => ! empty($d) && $d !== 'all'));
                    } elseif (is_string($dirInput) && str_contains($dirInput, ',')) {
                        $dirs = array_map('intval', array_filter(explode(',', $dirInput), fn ($d) => ! empty($d) && $d !== 'all'));
                    } else {
                        $dirs = [(int) $dirInput];
                    }
                }

                $govs = [];
                if (! empty($govInput) && $govInput !== 'all') {
                    if (is_array($govInput)) {
                        $govs = array_map('intval', array_filter($govInput, fn ($g) => ! empty($g) && $g !== 'all'));
                    } elseif (is_string($govInput) && str_contains($govInput, ',')) {
                        $govs = array_map('intval', array_filter(explode(',', $govInput), fn ($g) => ! empty($g) && $g !== 'all'));
                    } else {
                        $govs = [(int) $govInput];
                    }
                }

                if (! empty($dirs)) {
                    // Rules 2 & 3: Selected directorate(s) (single or multiple)
                    foreach ($dirs as $dirId) {
                        $entityIds = array_merge($entityIds, $this->getEntitiesByDirectorate($dirId));
                        $authorityIds = array_merge($authorityIds, $this->getAuthoritiesByDirectorate($dirId));
                    }
                } elseif (! empty($govs)) {
                    // Rules 1, 4 & 5: Governorate(s) selected (includes all its directorates dynamically)
                    foreach ($govs as $govId) {
                        $entityIds = array_merge($entityIds, $this->getEntitiesByGovernorate($govId));
                        $authorityIds = array_merge($authorityIds, $this->getAuthoritiesByGovernorate($govId));
                    }
                }
            }
        } elseif (! empty($user->governorate_id) || ! empty($user->directorate_id)) {
            // Direct columns on the users table
            if (! empty($user->directorate_id)) {
                $entityIds = array_merge($entityIds, $this->getEntitiesByDirectorate((int) $user->directorate_id));
                $authorityIds = array_merge($authorityIds, $this->getAuthoritiesByDirectorate((int) $user->directorate_id));
            } elseif (! empty($user->governorate_id)) {
                $entityIds = array_merge($entityIds, $this->getEntitiesByGovernorate((int) $user->governorate_id));
                $authorityIds = array_merge($authorityIds, $this->getAuthoritiesByGovernorate((int) $user->governorate_id));
            }
        }

        return [
            'is_all' => $isAll,
            'entity_ids' => array_values(array_unique($entityIds)),
            'authority_ids' => array_values(array_unique($authorityIds)),
        ];
    }

    /**
     * Resolve entities matching administrative scope rules (unlimited depth, multiple roots).
     */
    protected function resolveAdministrativeScope(User $user, mixed $activeAdminScope, bool $isExternal): array
    {
        $rootIds = [];

        if (! empty($activeAdminScope)) {
            if (is_array($activeAdminScope)) {
                $rootIds = array_map('intval', array_filter($activeAdminScope));
            } elseif (is_string($activeAdminScope) && str_contains($activeAdminScope, ',')) {
                $rootIds = array_map('intval', array_filter(explode(',', $activeAdminScope)));
            } else {
                $rootIds = [(int) $activeAdminScope];
            }
        }

        $rootIds = array_values(array_unique(array_filter($rootIds, fn ($id) => $id > 0)));

        if (empty($rootIds)) {
            return [
                'entity_ids' => [],
                'authority_ids' => [],
            ];
        }

        if ($isExternal) {
            $descendants = $this->getAuthorityDescendantIds($rootIds);

            return [
                'entity_ids' => [],
                'authority_ids' => $descendants,
            ];
        }

        $descendants = $this->getInternalEntityDescendantIds($rootIds);

        return [
            'entity_ids' => $descendants,
            'authority_ids' => [],
        ];
    }

    /**
     * Get all entities geographically located within a governorate (Rule 1, 4, 7).
     * Geographic membership takes precedence over administrative parent.
     */
    public function getEntitiesByGovernorate(int $governorateId): array
    {
        if (isset(self::$govEntityCache[$governorateId])) {
            return self::$govEntityCache[$governorateId];
        }

        // Dynamically resolve all directorates belonging to this governorate (Rule 4)
        $dirIds = DB::table('directorates')
            ->where('governorate_id', $governorateId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        return self::$govEntityCache[$governorateId] = DB::table('internal_entities')
            ->leftJoin('authorities', 'internal_entities.authority_id', '=', 'authorities.id')
            ->where(function ($q) use ($governorateId, $dirIds) {
                // Direct on internal entity
                $q->where('internal_entities.governorate_id', $governorateId)
                    ->orWhereIn('internal_entities.directorate_id', $dirIds ?: [-1])
                    // Or via linked authority
                    ->orWhere('authorities.governorate_id', $governorateId)
                    ->orWhereIn('authorities.directorate_id', $dirIds ?: [-1]);
            })
            ->pluck('internal_entities.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * Get all authorities geographically located within a governorate.
     */
    public function getAuthoritiesByGovernorate(int $governorateId): array
    {
        if (isset(self::$govAuthCache[$governorateId])) {
            return self::$govAuthCache[$governorateId];
        }

        $dirIds = DB::table('directorates')
            ->where('governorate_id', $governorateId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        return self::$govAuthCache[$governorateId] = DB::table('authorities')
            ->where(function ($q) use ($governorateId, $dirIds) {
                $q->where('authorities.governorate_id', $governorateId)
                    ->orWhereIn('authorities.directorate_id', $dirIds ?: [-1]);
            })
            ->pluck('authorities.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * Get all entities geographically located within a directorate (Rule 2 & 3).
     */
    public function getEntitiesByDirectorate(int $directorateId): array
    {
        if (isset(self::$dirEntityCache[$directorateId])) {
            return self::$dirEntityCache[$directorateId];
        }

        return self::$dirEntityCache[$directorateId] = DB::table('internal_entities')
            ->leftJoin('authorities', 'internal_entities.authority_id', '=', 'authorities.id')
            ->where(function ($q) use ($directorateId) {
                $q->where('internal_entities.directorate_id', $directorateId)
                    ->orWhere('authorities.directorate_id', $directorateId);
            })
            ->pluck('internal_entities.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * Get all authorities geographically located within a directorate.
     */
    public function getAuthoritiesByDirectorate(int $directorateId): array
    {
        if (isset(self::$dirAuthCache[$directorateId])) {
            return self::$dirAuthCache[$directorateId];
        }

        return self::$dirAuthCache[$directorateId] = DB::table('authorities')
            ->where('authorities.directorate_id', $directorateId)
            ->pluck('authorities.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * Recursive descendant resolution for internal entities supporting unlimited depth (Admin Rules 1 & 2).
     */
    public function getInternalEntityDescendantIds(array $parentIds): array
    {
        if (empty($parentIds)) {
            return [];
        }

        $toProcess = array_values(array_unique(array_map('intval', $parentIds)));
        $allDescendants = $toProcess;
        $visited = array_fill_keys($toProcess, true);

        while (! empty($toProcess)) {
            $children = DB::table('internal_entities')
                ->whereIn('parent_id', $toProcess)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            if (empty($children)) {
                break;
            }

            $newToProcess = [];
            foreach ($children as $child) {
                if (! isset($visited[$child])) {
                    $visited[$child] = true;
                    $allDescendants[] = $child;
                    $newToProcess[] = $child;
                }
            }

            $toProcess = $newToProcess;
        }

        return array_values(array_unique($allDescendants));
    }

    /**
     * Recursive descendant resolution for authorities supporting unlimited depth (Admin Rules 1 & 2).
     */
    public function getAuthorityDescendantIds(array $parentIds): array
    {
        if (empty($parentIds)) {
            return [];
        }

        $toProcess = array_values(array_unique(array_map('intval', $parentIds)));
        $allDescendants = $toProcess;
        $visited = array_fill_keys($toProcess, true);

        while (! empty($toProcess)) {
            $children = DB::table('authorities')
                ->whereIn('parent_id', $toProcess)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            if (empty($children)) {
                break;
            }

            $newToProcess = [];
            foreach ($children as $child) {
                if (! isset($visited[$child])) {
                    $visited[$child] = true;
                    $allDescendants[] = $child;
                    $newToProcess[] = $child;
                }
            }

            $toProcess = $newToProcess;
        }

        return array_values(array_unique($allDescendants));
    }

    /**
     * Apply centralized, server-side data scoping to any Eloquent query builder.
     */
    public function applyScope(Builder $query, ?User $user = null, ?string $context = null): Builder
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        $scope = $this->getEffectiveScope($user);

        if ($scope['is_admin'] || $scope['is_all']) {
            return $query;
        }

        $table = $query->getModel()->getTable();
        $entityIds = $scope['entity_ids'];
        $authorityIds = $scope['authority_ids'];
        $cleanEntityNames = $scope['entity_names'];
        $cleanAuthorityNames = $scope['authority_names'];
        $userId = $scope['user_id'];
        $isExternal = $scope['is_external'];

        switch ($table) {
            case 'projects':
                return $this->scopeProjects($query, $user, $entityIds, $authorityIds, $cleanEntityNames, $cleanAuthorityNames, $isExternal);

            case 'plans':
                return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $userId) {
                    $hasCondition = false;
                    if (! empty($entityIds)) {
                        $q->whereIn('plans.submitting_entity_id', $entityIds)
                            ->orWhereIn('plans.creator_entity_id', $entityIds);
                        $hasCondition = true;
                    }
                    if (! empty($authorityIds)) {
                        $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                        $q->{$method}('plans.submitting_authority_id', $authorityIds);
                        $hasCondition = true;
                    }
                    if ($userId) {
                        $method = $hasCondition ? 'orWhere' : 'where';
                        $q->{$method}('plans.created_by', $userId);
                    }
                });

            case 'correspondences':
                return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $userId) {
                    $hasCondition = false;
                    if (! empty($entityIds)) {
                        $q->whereIn('correspondences.sender_entity_id', $entityIds)
                            ->orWhereIn('correspondences.recipient_entity_id', $entityIds)
                            ->orWhereIn('correspondences.creator_entity_id', $entityIds)
                            ->orWhereHas('referrals', fn ($rq) => $rq->whereIn('referred_to_entity_id', $entityIds));
                        $hasCondition = true;
                    }
                    if (! empty($authorityIds)) {
                        $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                        $q->{$method}('correspondences.sender_authority_id', $authorityIds)
                            ->orWhereIn('correspondences.recipient_authority_id', $authorityIds)
                            ->orWhereIn('correspondences.creator_authority_id', $authorityIds);
                        $hasCondition = true;
                    }
                    if ($userId) {
                        $method = $hasCondition ? 'orWhere' : 'where';
                        $q->{$method}('correspondences.sender_user_id', $userId);
                    }
                });

            case 'tasks':
                return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $userId) {
                    $hasCondition = false;
                    if (! empty($entityIds)) {
                        $q->whereIn('tasks.assigned_entity_id', $entityIds);
                        $hasCondition = true;
                    }
                    if (! empty($authorityIds)) {
                        $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                        $q->{$method}('tasks.assigned_authority_id', $authorityIds);
                        $hasCondition = true;
                    }
                    if ($userId) {
                        $method = $hasCondition ? 'orWhere' : 'where';
                        $q->{$method}('tasks.assigned_user_id', $userId)
                            ->orWhere('tasks.created_by', $userId);
                        $hasCondition = true;
                    }
                    // Tasks also visible if their underlying project is accessible
                    $method = $hasCondition ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('project', function (Builder $pq) use ($entityIds, $authorityIds, $userId) {
                        $pq->where(function (Builder $sub) use ($entityIds, $authorityIds, $userId) {
                            if (! empty($entityIds)) {
                                $sub->whereIn('projects.creator_entity_id', $entityIds)
                                    ->orWhereIn('projects.internal_entity_id', $entityIds);
                            }
                            if (! empty($authorityIds)) {
                                $sub->orWhereIn('projects.authority_id', $authorityIds);
                            }
                            if ($userId) {
                                $sub->orWhere('projects.created_by_user_id', $userId);
                            }
                        });
                    });
                });

            case 'project_workflow_requests':
                return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $userId) {
                    $q->whereHas('project', function (Builder $pq) use ($entityIds, $authorityIds, $userId) {
                        $pq->where(function (Builder $sub) use ($entityIds, $authorityIds, $userId) {
                            if (! empty($entityIds)) {
                                $sub->whereIn('projects.creator_entity_id', $entityIds)
                                    ->orWhereIn('projects.internal_entity_id', $entityIds);
                            }
                            if (! empty($authorityIds)) {
                                $sub->orWhereIn('projects.authority_id', $authorityIds);
                            }
                            if ($userId) {
                                $sub->orWhere('projects.created_by_user_id', $userId);
                            }
                        });
                    })->orWhereHas('approvalSteps', function (Builder $appQ) use ($entityIds, $authorityIds, $userId) {
                        $appQ->where(function (Builder $sub) use ($entityIds, $authorityIds, $userId) {
                            if (! empty($entityIds)) {
                                $sub->whereIn('project_approvals.entity_id', $entityIds);
                            }
                            if (! empty($authorityIds)) {
                                $sub->orWhereIn('project_approvals.authority_id', $authorityIds);
                            }
                            if ($userId) {
                                $sub->orWhere('project_approvals.assigned_user_id', $userId);
                            }
                        });
                    });

                    if ($userId) {
                        $q->orWhere('project_workflow_requests.submitted_by', $userId);
                    }
                });

            case 'project_approvals':
                return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $userId) {
                    if (! empty($entityIds)) {
                        $q->whereIn('project_approvals.entity_id', $entityIds);
                    }
                    if (! empty($authorityIds)) {
                        $q->orWhereIn('project_approvals.authority_id', $authorityIds);
                    }
                    if ($userId) {
                        $q->orWhere('project_approvals.assigned_user_id', $userId);
                    }
                    $q->orWhereHas('project', function (Builder $pq) use ($entityIds, $authorityIds, $userId) {
                        $pq->where(function (Builder $sub) use ($entityIds, $authorityIds, $userId) {
                            if (! empty($entityIds)) {
                                $sub->whereIn('projects.creator_entity_id', $entityIds)
                                    ->orWhereIn('projects.internal_entity_id', $entityIds);
                            }
                            if (! empty($authorityIds)) {
                                $sub->orWhereIn('projects.authority_id', $authorityIds);
                            }
                            if ($userId) {
                                $sub->orWhere('projects.created_by_user_id', $userId);
                            }
                        });
                    });
                });

            case 'project_disbursement_plans':
            case 'project_clearance_requests':
            case 'project_payment_requests':
            case 'project_unified_requests':
                return $query->whereHas('project', function (Builder $pq) use ($entityIds, $authorityIds, $userId) {
                    $pq->where(function (Builder $sub) use ($entityIds, $authorityIds, $userId) {
                        if (! empty($entityIds)) {
                            $sub->whereIn('projects.creator_entity_id', $entityIds)
                                ->orWhereIn('projects.internal_entity_id', $entityIds);
                        }
                        if (! empty($authorityIds)) {
                            $sub->orWhereIn('projects.authority_id', $authorityIds);
                        }
                        if ($userId) {
                            $sub->orWhere('projects.created_by_user_id', $userId);
                        }
                    });
                });

            case 'task_achievement_reports':
                return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $userId) {
                    if ($userId) {
                        $q->where('task_achievement_reports.created_by_user_id', $userId);
                    }
                    $q->orWhereHas('task', function (Builder $tq) use ($entityIds, $authorityIds, $userId) {
                        $tq->where(function (Builder $sub) use ($entityIds, $authorityIds, $userId) {
                            if (! empty($entityIds)) {
                                $sub->whereIn('tasks.assigned_entity_id', $entityIds);
                            }
                            if (! empty($authorityIds)) {
                                $sub->orWhereIn('tasks.assigned_authority_id', $authorityIds);
                            }
                            if ($userId) {
                                $sub->orWhere('tasks.assigned_user_id', $userId)
                                    ->orWhere('tasks.created_by', $userId);
                            }
                        });
                    });
                });

            case 'empowerment_projects':
                return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $userId) {
                    if (! empty($entityIds)) {
                        $q->whereIn('empowerment_projects.internal_entity_id', $entityIds);
                    }
                    if (! empty($authorityIds)) {
                        $q->orWhereIn('empowerment_projects.authority_id', $authorityIds);
                    }
                    if ($userId) {
                        $q->orWhere('empowerment_projects.created_by_user_id', $userId);
                    }
                });

            case 'empowerment_beneficiaries':
                return $query->whereHas('empowermentProject', function (Builder $epq) use ($entityIds, $authorityIds, $userId) {
                    $epq->where(function (Builder $sub) use ($entityIds, $authorityIds, $userId) {
                        if (! empty($entityIds)) {
                            $sub->whereIn('empowerment_projects.internal_entity_id', $entityIds);
                        }
                        if (! empty($authorityIds)) {
                            $sub->orWhereIn('empowerment_projects.authority_id', $authorityIds);
                        }
                        if ($userId) {
                            $sub->orWhere('empowerment_projects.created_by_user_id', $userId);
                        }
                    });
                });

            case 'memoirs':
                return $query->where(function (Builder $q) use ($entityIds, $userId) {
                    if (! empty($entityIds)) {
                        $q->whereIn('memoirs.entity_id', $entityIds);
                    }
                    if ($userId) {
                        $q->orWhere('memoirs.created_by_user_id', $userId);
                    }
                });

            case 'suggestions':
                return $query->where(function (Builder $q) use ($entityIds, $userId) {
                    if (! empty($entityIds)) {
                        $q->whereIn('suggestions.entity_id', $entityIds);
                    }
                    if ($userId) {
                        $q->orWhere('suggestions.created_by_user_id', $userId);
                    }
                });

            case 'users':
                return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $userId) {
                    if ($userId) {
                        $q->where('users.id', $userId);
                    }
                    if (! empty($entityIds)) {
                        $q->orWhereIn('users.entity_id', $entityIds);
                    }
                    if (! empty($authorityIds)) {
                        $q->orWhereIn('users.authority_id', $authorityIds);
                    }
                });

            case 'internal_entities':
                return ! empty($entityIds)
                    ? $query->whereIn('internal_entities.id', $entityIds)
                    : $query->whereRaw('0 = 1');

            case 'authorities':
                return ! empty($authorityIds)
                    ? $query->whereIn('authorities.id', $authorityIds)
                    : $query->whereRaw('0 = 1');

            case 'programs':
                return $query->whereHas('projects', fn (Builder $pq) => $this->scopeProjects($pq, $user, $entityIds, $authorityIds, $cleanEntityNames, $cleanAuthorityNames, $isExternal));

            default:
                return $this->scopeGenericTable($query, $user, $table, $entityIds, $authorityIds, $userId);
        }
    }

    /**
     * Dedicated project query scoping adhering to strict authority and ministry isolation.
     */
    protected function scopeProjects(
        Builder $query,
        User $user,
        array $entityIds,
        array $authorityIds,
        array $cleanEntityNames,
        array $cleanAuthorityNames,
        bool $isExternal
    ): Builder {
        if ($isExternal) {
            $userAuthorityId = (int) ($user->authority_id ?? 0);
            $targetAuthIds = ! empty($authorityIds) ? $authorityIds : ($userAuthorityId > 0 ? [$userAuthorityId] : []);
            $hasWorkflowResponsibility = ! empty($user->responsibility);

            return $query->where(function (Builder $q) use ($user, $targetAuthIds, $cleanAuthorityNames) {
                $q->where('projects.created_by_user_id', $user->id);

                if (! empty($targetAuthIds)) {
                    $q->orWhereIn('projects.authority_id', $targetAuthIds);

                    $stakeholderRelations = ['implementingEntities', 'supervisingAuthorities', 'participatingEntities', 'beneficiaryEntities'];
                    foreach ($stakeholderRelations as $relation) {
                        if (method_exists(Project::class, $relation)) {
                            $q->orWhereHas($relation, fn ($relQ) => $relQ->whereIn('authority_id', $targetAuthIds));
                        }
                    }
                }

                if (! empty($cleanAuthorityNames)) {
                    $q->orWhereIn('projects.created_by_entity', $cleanAuthorityNames);
                }
            });
        }

        return $query->where(function (Builder $q) use ($entityIds, $authorityIds, $cleanEntityNames, $cleanAuthorityNames, $user) {
            $hasCondition = false;

            if (! empty($entityIds)) {
                $q->whereIn('projects.creator_entity_id', $entityIds)
                    ->orWhereIn('projects.internal_entity_id', $entityIds)
                    ->orWhereHas('projectApprovals', fn ($appQ) => $appQ->whereIn('project_approvals.entity_id', $entityIds));
                $hasCondition = true;
            }

            if (! empty($authorityIds)) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $q->{$method}('projects.authority_id', $authorityIds);
                $hasCondition = true;
            }

            $stakeholderRelations = ['implementingEntities', 'supervisingAuthorities', 'participatingEntities', 'beneficiaryEntities'];
            foreach ($stakeholderRelations as $relation) {
                if (method_exists(Project::class, $relation)) {
                    $q->orWhereHas($relation, function ($relQ) use ($entityIds, $authorityIds) {
                        $relQ->where(function ($sub) use ($entityIds, $authorityIds) {
                            $hasRel = false;
                            if (! empty($entityIds)) {
                                $sub->whereIn('internal_entity_id', $entityIds);
                                $hasRel = true;
                            }
                            if (! empty($authorityIds)) {
                                $method = $hasRel ? 'orWhereIn' : 'whereIn';
                                $sub->{$method}('authority_id', $authorityIds);
                            }
                        });
                    });
                }
            }

            if (! empty($cleanEntityNames)) {
                $q->orWhereIn('projects.created_by_entity', $cleanEntityNames);
            }

            if (! empty($cleanAuthorityNames)) {
                $q->orWhereIn('projects.created_by_entity', $cleanAuthorityNames);
            }

            $q->orWhere('projects.created_by_user_id', $user->id);
        });
    }

    /**
     * Generic table scoping inspecting common candidate column names.
     */
    protected function scopeGenericTable(
        Builder $query,
        User $user,
        string $table,
        array $entityIds,
        array $authorityIds,
        ?int $userId
    ): Builder {
        return $query->where(function (Builder $q) use ($table, $entityIds, $authorityIds, $userId) {
            $hasCondition = false;

            // Candidate entity columns
            $entityCols = ['internal_entity_id', 'entity_id', 'creator_entity_id', 'submitting_entity_id', 'sender_entity_id', 'recipient_entity_id'];
            foreach ($entityCols as $col) {
                if (SchemaCache::hasColumn($table, $col) && ! empty($entityIds)) {
                    $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                    $q->{$method}("{$table}.{$col}", $entityIds);
                    $hasCondition = true;
                }
            }

            // Candidate authority columns
            $authCols = ['authority_id', 'submitting_authority_id', 'sender_authority_id', 'recipient_authority_id', 'creator_authority_id'];
            foreach ($authCols as $col) {
                if (SchemaCache::hasColumn($table, $col) && ! empty($authorityIds)) {
                    $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                    $q->{$method}("{$table}.{$col}", $authorityIds);
                    $hasCondition = true;
                }
            }

            // Candidate user columns
            $userCols = ['created_by_user_id', 'user_id', 'created_by', 'sender_user_id', 'submitted_by'];
            foreach ($userCols as $col) {
                if (SchemaCache::hasColumn($table, $col) && $userId) {
                    $method = $hasCondition ? 'orWhere' : 'where';
                    $q->{$method}("{$table}.{$col}", $userId);
                    $hasCondition = true;
                }
            }

            if (! $hasCondition) {
                // If the table cannot be mapped to any entity/authority/user column, keep it accessible
                $q->whereRaw('1 = 1');
            }
        });
    }

    /**
     * Authorize access to a single record or throw 403 HTTP exception.
     */
    public function authorizeRecord(?object $record, ?User $user = null): void
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            abort(401, 'يجب تسجيل الدخول أولاً.');
        }

        if ($record === null) {
            abort(404, 'السجل المطلوب غير موجود.');
        }

        if (! $this->canAccessRecord($record, $user)) {
            abort(403, 'ليس لديك الصلاحية لعرض أو تعديل هذا السجل بموجب نطاقك المحدد.');
        }
    }

    /**
     * Check whether a user is allowed to access a specific record instance.
     */
    public function canAccessRecord(?object $record, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user || ! $record) {
            return false;
        }

        $scope = $this->getEffectiveScope($user);

        if ($scope['is_admin'] || $scope['is_all']) {
            return true;
        }

        $userId = $scope['user_id'];
        $entityIds = $scope['entity_ids'];
        $authorityIds = $scope['authority_ids'];

        // 1. Direct creator check
        $creatorCols = ['created_by_user_id', 'user_id', 'created_by', 'sender_user_id', 'submitted_by'];
        foreach ($creatorCols as $col) {
            if (isset($record->{$col}) && (int) $record->{$col} === $userId) {
                return true;
            }
        }

        // 2. Direct entity match
        $entityCols = ['creator_entity_id', 'internal_entity_id', 'entity_id', 'submitting_entity_id', 'sender_entity_id', 'recipient_entity_id', 'assigned_entity_id'];
        foreach ($entityCols as $col) {
            if (isset($record->{$col}) && in_array((int) $record->{$col}, $entityIds, true)) {
                return true;
            }
        }

        // 3. Direct authority match
        $authCols = ['authority_id', 'submitting_authority_id', 'sender_authority_id', 'recipient_authority_id', 'creator_authority_id', 'assigned_authority_id'];
        foreach ($authCols as $col) {
            if (isset($record->{$col}) && in_array((int) $record->{$col}, $authorityIds, true)) {
                return true;
            }
        }

        // 4. Model-specific deep checks if available
        if ($record instanceof Model) {
            $table = $record->getTable();
            $query = $record->newQueryWithoutScopes()->where("{$table}.id", $record->id);

            return $this->applyScope($query, $user)->exists();
        }

        return false;
    }
}

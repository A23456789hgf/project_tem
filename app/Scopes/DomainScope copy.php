<?php

namespace App\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DomainScope implements Scope
{
    protected static $applying = false;

    public function apply(Builder $builder, Model $model)
    {
        if (self::$applying) {
            return;
        }

        self::$applying = true;

        try {
            $this->doApply($builder, $model);
        } finally {
            self::$applying = false;
        }
    }

    protected function doApply(Builder $builder, Model $model)
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // 1. Bypass check: Administrators, Full Access roles, and Root Entity users see everything.
        if ($user->isAdmin() || optional($user->role)->full_access || ($user->entity && method_exists($user->entity, 'isRootEntity') && $user->entity->isRootEntity())) {
            return;
        }

        $module = $this->resolveModule($model);

        // 2. CORE RULE: Final Data = Permission Allowed ∩ (Administrative Scope AND Geographic Scope)
        $this->applyUnifiedFiltering($builder, $user, $module);
    }

    // =========================================================
    // 🔥 UNIFIED FILTER ENGINE
    // =========================================================

    protected function applyUnifiedFiltering(Builder $builder, User $user, string $module): void
    {
        $geoScope = $user->getModuleGeoScope($module);
        $adminScope = $user->getModuleAdminScope($module);

        // If either scope is 'none', user sees nothing.
        if ($geoScope === 'none' || $adminScope === 'none') {
            $builder->whereRaw('0=1');

            return;
        }

        // Apply both filters on the primary builder (Laravel implicitly ANDs them)
        // Layer A: Geographic Visibility
        if ($geoScope !== 'all') {
            $this->applyGeoFilter($builder, $user, $module, $geoScope);
        }

        // Layer B: Administrative visibility
        if ($adminScope !== 'all') {
            $this->applyAdminFilter($builder, $user, $module, $adminScope);
        }
    }

    // =========================================================
    // 🔥 PROJECTS SPECIAL HANDLING
    // =========================================================

    protected function applyProjectScope(Builder $builder, User $user, string $module): void
    {
        $table = $builder->getModel()->getTable();

        if (! $this->hasJoin($builder, 'internal_entities')) {
            $builder->leftJoin(
                'internal_entities',
                "{$table}.internal_entity_id",
                '=',
                'internal_entities.id'
            );
        }

        $builder->select("{$table}.*");

        $this->applyUnifiedFiltering($builder, $user, $module);
    }

    // =========================================================
    // 🔥 GEO FILTER (FIXED + MATRIX BASED)
    // =========================================================

    protected function applyGeoFilter(Builder $query, User $user, string $module, string $geoScope): void
    {
        $table = $query->getModel()->getTable();
        $userGov = $user->getAssignedGovernorateId();
        $userDir = $user->getAssignedDirectorateId();
        $geoData = $user->getModuleGeoData($module); // Custom governorates array

        // If the table doesn't have direct geo columns, we join internal_entities to filter by entity location
        $hasDirectGeo = Schema::hasColumn($table, 'governorate_id') || Schema::hasColumn($table, 'geographic_scope_id');

        $joinTable = $table;
        if (! $hasDirectGeo) {
            $adminCol = $user->detectAdminColumn($table);
            if ($adminCol) {
                if (! $this->hasJoin($query, 'internal_entities')) {
                    $query->leftJoin('internal_entities', "{$table}.{$adminCol}", '=', 'internal_entities.id');
                    $query->select("{$table}.*");
                }
                $joinTable = 'internal_entities';
            } else {
                return;
            }
        }

        $govCol = Schema::hasColumn($joinTable, 'geographic_scope_id') ? "{$joinTable}.geographic_scope_id" : "{$joinTable}.governorate_id";
        $dirCol = "{$joinTable}.directorate_id";

        switch ($geoScope) {
            case 'same_governorate':
                $query->where($govCol, $userGov ?: 0);
                break;
            case 'same_directorate':
                if ($userDir) {
                    $query->where($dirCol, $userDir);
                } else {
                    $query->where($govCol, $userGov ?: 0);
                }
                break;
            case 'custom':
                // Pass-through: Administrative scope handles the logic in custom mode.
                break;
        }
    }

    protected function applyAdminFilter(Builder $query, User $user, string $module, string $adminScope): void
    {
        $table = $query->getModel()->getTable();
        $adminCol = $user->detectAdminColumn($table);

        switch ($adminScope) {
            case 'user':
                $userCol = $user->detectUserColumn($table);
                if ($userCol) {
                    $query->where("{$table}.{$userCol}", $user->id);
                } else {
                    $query->whereRaw('0=1');
                }
                break;
            case 'own':
                if ($adminCol) {
                    $query->where(function ($q) use ($adminCol, $user, $table) {
                        $cols = (array) $adminCol;
                        foreach ($cols as $col) {
                            if ($col === 'referrals') {
                                $q->orWhereHas('referrals', function ($r) use ($user) {
                                    $r->where('referred_to_entity_id', $user->entity_id);
                                });
                            } else {
                                $q->orWhere("{$table}.{$col}", $user->entity_id);
                            }
                        }
                    });
                } else {
                    $query->whereRaw('0=1');
                }
                break;
            case 'parent':
                $parentId = $user->getParentEntityId();
                if ($adminCol && $parentId) {
                    $query->where(function ($q) use ($adminCol, $parentId, $table) {
                        $cols = (array) $adminCol;
                        foreach ($cols as $col) {
                            if ($col === 'referrals') {
                                $q->orWhereHas('referrals', function ($r) use ($parentId) {
                                    $r->where('referred_to_entity_id', $parentId);
                                });
                            } else {
                                $q->orWhere("{$table}.{$col}", $parentId);
                            }
                        }
                    });
                } else {
                    $query->whereRaw('0=1');
                }
                break;
            case 'dept_in_gen_dir':
                $ids = $user->getAllowedAdministrativeIds($module);
                if ($adminCol && ! empty($ids) && is_array($ids)) {
                    $query->where(function ($q) use ($adminCol, $ids, $table) {
                        $cols = (array) $adminCol;
                        foreach ($cols as $col) {
                            if ($col === 'referrals') {
                                $q->orWhereHas('referrals', function ($r) use ($ids) {
                                    $r->whereIn('referred_to_entity_id', $ids);
                                });
                            } else {
                                $q->orWhereIn("{$table}.{$col}", $ids);
                            }
                        }
                    });
                } else {
                    $query->whereRaw('0=1');
                }
                break;
        }
    }

    public function resolveModule(Model $model): string
    {
        $table = $model->getTable();

        return match ($table) {
            'projects' => 'projects',
            'project_requests' => 'project-requests',
            'internal_entities' => 'internal-entities',
            'authorities' => 'authorities',
            'correspondences' => 'correspondence',
            'plans' => 'planning',
            'empowerment_projects' => 'empowerment',
            'memoirs' => 'memoirs',
            'suggestions' => 'suggestions',
            'reports' => 'reports',
            default => $table,
        };
    }

    // =========================================================
    // 🔥 JOIN CHECK
    // =========================================================

    protected function hasJoin(Builder $builder, string $table): bool
    {
        $joins = $builder->getQuery()->joins ?? [];

        foreach ($joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }
}

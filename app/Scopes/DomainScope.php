<?php

namespace App\Scopes;

use App\Models\User;
use App\Services\SchemaCache;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DomainScope implements Scope
{
    protected static $applying = false;

    /**
     * Cache للمساعدة في تقليل استعلامات قاعدة البيانات
     */
    protected static array $geoCache = [];

    // =========================================================
    // الدالة الأساسية لتطبيق الـ Scope
    // =========================================================

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

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        // =========================================================
        // 🆕 التحقق من وجود فلترة يدوية على creator_entity_id
        //    إذا وُجدت، نتجاوز تطبيق DomainScope بالكامل
        //    (لتفادي التضارب مع دوال مثل getProjects)
        // =========================================================
        if ($this->hasWhereConditionOnColumn($builder, 'creator_entity_id') ||
            $this->hasWhereConditionOnColumn($builder, 'projects.creator_entity_id')) {
            return;
        }

        $module = $this->resolveModule($model);

        // تفويض الفلترة الموحدة المركزية إلى UserDataScopeService
        app(UserDataScopeService::class)->applyScope($builder, $user, $module);
    }

    // =========================================================
    // 🆕 دالة الكشف عن وجود شرط WHERE على عمود معين
    // (تدعم wheres العادية، whereIn، والشروط المتداخلة)
    // =========================================================

    protected function hasWhereConditionOnColumn(Builder $builder, string $column): bool
    {
        $query = $builder->getQuery();
        $wheres = $query->wheres;

        foreach ($wheres as $where) {
            // where عادية
            if (isset($where['column']) && $where['column'] === $column) {
                return true;
            }

            // whereIn
            if (isset($where['type']) && $where['type'] === 'In' &&
                isset($where['column']) && $where['column'] === $column) {
                return true;
            }

            // whereColumn
            if (isset($where['type']) && $where['type'] === 'Column' &&
                isset($where['first']) && $where['first'] === $column) {
                return true;
            }

            // شرط متداخل (Nested)
            if (isset($where['type']) && $where['type'] === 'Nested' &&
                isset($where['query']) && $where['query'] instanceof Builder) {
                if ($this->hasWhereConditionOnColumn($where['query'], $column)) {
                    return true;
                }
            }

            // whereRaw قد تحتوي على النص ولكن لا نفحصها حالياً (نادر)
        }

        return false;
    }

    // =========================================================
    // 🔥 UNIFIED FILTER ENGINE
    // =========================================================

    protected function applyUnifiedFiltering(Builder $builder, User $user, string $module): void
    {
        $isCentral = $user->isCentralUser();

        if (! $isCentral) {
            $geoScope = $user->getModuleGeoScope($module);
            $adminScope = $user->getModuleAdminScope($module);

            if ($geoScope === 'none' && $adminScope === 'none') {
                if ($builder->getModel()->getTable() === 'users') {
                    $builder->where('users.id', $user->id);

                    return;
                }
                $builder->whereRaw('0=1');

                return;
            }

            /**
             * 🟢 INCLUSIVE LOGIC for Regional Users:
             * Match projects within Geographic boundary
             * OR projects allowed by Administrative hierarchy.
             */
            $builder->where(function ($q) use ($user, $module, $geoScope, $adminScope) {
                // 1. Geographic Layer
                $q->where(function ($geoQ) use ($user, $module, $geoScope) {
                    if ($geoScope === 'all') {
                        $geoQ->whereRaw('1=1');
                    } elseif ($geoScope === 'none' || empty($geoScope)) {
                        $geoQ->whereRaw('0=1');
                    } else {
                        $this->applyGeoFilterUnified($geoQ, $user, $module, $geoScope);
                    }
                });

                // 2. Administrative Layer (Inclusive OR)
                $q->orWhere(function ($adminQ) use ($user, $module, $adminScope) {
                    if ($adminScope === 'all') {
                        $adminQ->whereRaw('1=1');
                    } elseif ($adminScope === 'none' || empty($adminScope)) {
                        $adminQ->whereRaw('0=1');
                    } else {
                        $this->applyAdminFilterUnified($adminQ, $user, $module, $adminScope);
                    }
                });
            });
        } else {
            // CENTRAL USERS (Administrative scope only)
            $adminScope = $user->getModuleAdminScope($module);

            if ($adminScope === 'none' || empty($adminScope)) {
                if ($builder->getModel()->getTable() === 'users') {
                    $builder->where('users.id', $user->id);

                    return;
                }
                $builder->whereRaw('0=1');

                return;
            }

            if ($adminScope !== 'all') {
                $this->applyAdminFilterUnified($builder, $user, $module, $adminScope);
            }

            /**
             * Special Case for Central users to see projects in their assigned office's governorate
             */
            if ($module === 'projects' && method_exists($user, 'getAssignedGovernorateId')) {
                $govId = $user->getAssignedGovernorateId();
                if ($govId) {
                    $builder->orWhere(function ($q) use ($govId) {
                        $this->addGovernorateCondition($q, [$govId]);
                    });
                }
            }
        }
    }

    // =========================================================
    // 🔥 PROJECTS SPECIAL HANDLING
    // =========================================================

    protected function applyProjectScope(Builder $builder, User $user, string $module): void
    {
        $table = $builder->getModel()->getTable();

        // نطبق الفلترة الموحدة مباشرة (بدون إضافة join تلقائي لأن المشاريع لها أعمدة متعددة)
        $this->applyUnifiedFiltering($builder, $user, $module);
    }

    // =========================================================
    // 🔥 GEO FILTER (محسّن وشامل للمشاريع والكيانات الأخرى)
    // =========================================================

    protected function applyGeoFilterUnified(Builder $query, User $user, string $module, string $geoScope): void
    {
        $table = $query->getModel()->getTable();

        // معالجة خاصة للمشاريع لأنها تحتوي على أعمدة متعددة وعلاقات
        if ($table === 'projects') {
            $this->applyGeoFilterForProjects($query, $user, $geoScope);

            return;
        }

        // للجداول الأخرى (internal_entities, authorities, etc.) نستخدم المنطق البسيط
        $this->applyGeoFilterForGenericTable($query, $user, $geoScope, $table);
    }

    /**
     * تطبيق النطاق الجغرافي على جدول المشاريع (يدعم كل السيناريوهات)
     */
    protected function applyGeoFilterForProjects(Builder $query, User $user, string $geoScope): void
    {
        $govIds = (array) $user->getAssignedGovernorateIds();
        $dirIds = (array) $user->getAssignedDirectorateIds();

        if ($geoScope === 'same_governorate') {
            if (empty($govIds)) {
                $query->whereRaw('0=1');

                return;
            }
            $query->where(function ($q) use ($govIds) {
                $this->addGovernorateCondition($q, $govIds);
            });
        } elseif ($geoScope === 'same_directorate') {
            if (empty($dirIds)) {
                $query->whereRaw('0=1');

                return;
            }
            $query->where(function ($q) use ($dirIds) {
                $this->addDirectorateCondition($q, $dirIds);
            });
        } elseif ($geoScope === 'custom') {
            $customGovIds = $user->getCustomGovernorateIds('projects');
            if (empty($customGovIds)) {
                $query->whereRaw('0=1');

                return;
            }
            $query->where(function ($q) use ($customGovIds) {
                $this->addGovernorateCondition($q, $customGovIds);
            });
        }
        // 'all' لا نضيف شيئاً
    }

    /**
     * إضافة شروط المحافظة (تشمل كل الكيانات التابعة للمحافظة)
     */
    protected function addGovernorateCondition(Builder $query, array $govIds): void
    {
        static $hasGovernorateColumn = null;
        static $hasDirectorateColumn = null;

        if ($hasGovernorateColumn === null) {
            $hasGovernorateColumn = SchemaCache::hasColumn('projects', 'governorate_id');
            $hasDirectorateColumn = SchemaCache::hasColumn('projects', 'directorate_id');
        }

        $relatedDirIds = $this->getCachedDirectorateIds($govIds);

        // 1. الأعمدة المباشرة
        if ($hasGovernorateColumn) {
            $query->orWhereIn('projects.governorate_id', $govIds);
        }
        if ($hasDirectorateColumn && ! empty($relatedDirIds)) {
            $query->orWhereIn('projects.directorate_id', $relatedDirIds);
        }

        // 2. الكيانات الداخلية والخارجية المرتبطة
        $internalIds = $this->getCachedInternalEntityIds($govIds, $relatedDirIds);
        $authorityIds = $this->getCachedAuthorityIds($govIds, $relatedDirIds);

        if (! empty($internalIds)) {
            $query->orWhereIn('projects.creator_entity_id', $internalIds)
                ->orWhereIn('projects.internal_entity_id', $internalIds);
        }
        if (! empty($authorityIds)) {
            $query->orWhereIn('projects.authority_id', $authorityIds);
        }

        // 3. الأطراف (علاقات many-to-many)
        $this->addStakeholderConditions($query, $internalIds, $authorityIds);
    }

    /**
     * إضافة شروط المديرية (تشمل كل الكيانات التابعة للمديرية)
     */
    protected function addDirectorateCondition(Builder $query, array $dirIds): void
    {
        static $hasDirectorateColumn = null;
        if ($hasDirectorateColumn === null) {
            $hasDirectorateColumn = SchemaCache::hasColumn('projects', 'directorate_id');
        }

        if ($hasDirectorateColumn) {
            $query->orWhereIn('projects.directorate_id', $dirIds);
        }

        $internalIds = $this->getCachedInternalEntityIdsByDirectorates($dirIds);
        $authorityIds = $this->getCachedAuthorityIdsByDirectorates($dirIds);

        if (! empty($internalIds)) {
            $query->orWhereIn('projects.creator_entity_id', $internalIds)
                ->orWhereIn('projects.internal_entity_id', $internalIds);
        }
        if (! empty($authorityIds)) {
            $query->orWhereIn('projects.authority_id', $authorityIds);
        }

        $this->addStakeholderConditions($query, $internalIds, $authorityIds);
    }

    /**
     * إضافة شروط علاقات الأطراف (جهات منفذة، مشرفة، مشاركة، مستفيدة)
     */
    protected function addStakeholderConditions(Builder $query, array $internalIds, array $authorityIds): void
    {
        if (empty($internalIds) && empty($authorityIds)) {
            return;
        }

        $relations = ['implementingEntities', 'supervisingAuthorities', 'participatingEntities', 'beneficiaryEntities'];

        foreach ($relations as $relation) {
            $query->orWhereHas($relation, function ($q) use ($internalIds, $authorityIds) {
                $q->where(function ($sub) use ($internalIds, $authorityIds) {
                    if (! empty($internalIds)) {
                        $sub->whereIn('internal_entity_id', $internalIds);
                    }
                    if (! empty($authorityIds)) {
                        $sub->orWhereIn('authority_id', $authorityIds);
                    }
                });
            });
        }
    }

    /**
     * تطبيق النطاق الجغرافي على الجداول العامة (غير المشاريع)
     */
    protected function applyGeoFilterForGenericTable(Builder $query, User $user, string $geoScope, string $table): void
    {
        $govIds = (array) $user->getAssignedGovernorateIds();
        $dirIds = (array) $user->getAssignedDirectorateIds();

        // نحاول استخدام الأعمدة المباشرة إن وجدت
        $hasGovCol = SchemaCache::hasColumn($table, 'governorate_id');
        $hasDirCol = SchemaCache::hasColumn($table, 'directorate_id');

        if ($geoScope === 'same_governorate') {
            if (empty($govIds)) {
                $query->whereRaw('0=1');

                return;
            }
            if ($hasGovCol) {
                $query->whereIn("{$table}.governorate_id", $govIds);
            } else {
                $query->whereRaw('0=1');
            }
        } elseif ($geoScope === 'same_directorate') {
            if (empty($dirIds)) {
                $query->whereRaw('0=1');

                return;
            }
            if ($hasDirCol) {
                $query->whereIn("{$table}.directorate_id", $dirIds);
            } else {
                $query->whereRaw('0=1');
            }
        }
        // 'custom' و 'all' لا نطبق شيئاً إضافياً هنا
    }

    // =========================================================
    // 🔥 ADMIN FILTER (للمستخدمين المركزيين) - يدعم جميع الخيارات
    // =========================================================

    protected function applyAdminFilterUnified(Builder $query, User $user, string $module, string $adminScope): void
    {
        $table = $query->getModel()->getTable();

        // معالجة خاصة للمشاريع لأنها تحتوي على عدة أعمدة وعلاقات
        if ($table === 'projects') {
            $this->applyAdminFilterForProjects($query, $user, $adminScope);

            return;
        }

        // للجداول الأخرى نستخدم المنطق العام
        $this->applyAdminFilterForGenericTable($query, $user, $adminScope, $table);
    }

    /**
     * تطبيق النطاق الإداري على جدول المشاريع
     */
    protected function applyAdminFilterForProjects(Builder $query, User $user, string $adminScope): void
    {
        switch ($adminScope) {
            case 'user':
                $query->where('projects.created_by_user_id', $user->id);
                break;

            case 'own':
            case 'own_entity':
                $entityId = $user->getUserEntityId();
                if (! $entityId) {
                    $query->whereRaw('0=1');
                } else {
                    $this->applyEntityFilter($query, $entityId, $user->isExternal());
                }
                break;

            case 'sub_entities':
                $entityIds = $user->getSubEntityIds();
                if (empty($entityIds)) {
                    $query->whereRaw('0=1');
                } else {
                    $this->applyEntityFilter($query, $entityIds, $user->isExternal());
                }
                break;

            case 'all':
                // لا نضيف شيئاً
                break;

            default:
                $query->whereRaw('0=1');
                break;
        }
    }

    /**
     * فلترة المشاريع بناءً على معرفات الجهات (داخلية أو خارجية)
     */
    protected function applyEntityFilter(Builder $query, $entityIds, $isExternal = false): void
    {
        $idsArray = is_array($entityIds) ? $entityIds : [$entityIds];

        $query->where(function ($q) use ($idsArray, $isExternal) {
            if ($isExternal) {
                $q->whereIn('projects.authority_id', $idsArray)
                    ->orWhereHas('implementingEntities', fn ($r) => $r->whereIn('authority_id', $idsArray))
                    ->orWhereHas('supervisingAuthorities', fn ($r) => $r->whereIn('authority_id', $idsArray))
                    ->orWhereHas('participatingEntities', fn ($r) => $r->whereIn('authority_id', $idsArray))
                    ->orWhereHas('beneficiaryEntities', fn ($r) => $r->whereIn('authority_id', $idsArray));
            } else {
                $q->whereIn('projects.creator_entity_id', $idsArray)
                    ->orWhereIn('projects.internal_entity_id', $idsArray)
                    ->orWhereHas('implementingEntities', fn ($r) => $r->whereIn('internal_entity_id', $idsArray))
                    ->orWhereHas('supervisingAuthorities', fn ($r) => $r->whereIn('internal_entity_id', $idsArray))
                    ->orWhereHas('participatingEntities', fn ($r) => $r->whereIn('internal_entity_id', $idsArray))
                    ->orWhereHas('beneficiaryEntities', fn ($r) => $r->whereIn('internal_entity_id', $idsArray));
            }
        });
    }

    /**
     * تطبيق النطاق الإداري على الجداول العامة
     */
    protected function applyAdminFilterForGenericTable(Builder $query, User $user, string $adminScope, string $table): void
    {
        // نكتشف عمود الجهة الإدارية في الجدول (مثل entity_id, internal_entity_id, authority_id)
        $adminCol = $user->detectAdminColumn($table);

        if (! $adminCol) {
            $query->whereRaw('0=1');

            return;
        }

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
            case 'own_entity':
                $entityId = $user->getUserEntityId();
                if ($entityId) {
                    if (is_array($adminCol)) {
                        $query->where(function ($q) use ($table, $adminCol, $entityId) {
                            foreach ($adminCol as $col) {
                                if ($col === 'referrals') {
                                    $q->orWhereHas('referrals', function ($r) use ($entityId) {
                                        $r->where('referred_to_entity_id', $entityId);
                                    });
                                } else {
                                    $q->orWhere("{$table}.{$col}", $entityId);
                                }
                            }
                        });
                    } else {
                        $query->where("{$table}.{$adminCol}", $entityId);
                    }
                } else {
                    $query->whereRaw('0=1');
                }
                break;

            case 'sub_entities':
                $entityIds = $user->getSubEntityIds();
                if (! empty($entityIds)) {
                    if (is_array($adminCol)) {
                        $query->where(function ($q) use ($table, $adminCol, $entityIds) {
                            foreach ($adminCol as $col) {
                                if ($col === 'referrals') {
                                    $q->orWhereHas('referrals', function ($r) use ($entityIds) {
                                        $r->whereIn('referred_to_entity_id', $entityIds);
                                    });
                                } else {
                                    $q->orWhereIn("{$table}.{$col}", $entityIds);
                                }
                            }
                        });
                    } else {
                        $query->whereIn("{$table}.{$adminCol}", $entityIds);
                    }
                } else {
                    $query->whereRaw('0=1');
                }
                break;

            case 'all':
                // لا نضيف شيئاً
                break;

            default:
                $query->whereRaw('0=1');
                break;
        }
    }

    // =========================================================
    // 🔥 دوال مساعدة مع Cache
    // =========================================================

    protected function getCachedDirectorateIds(array $govIds): array
    {
        $cacheKey = 'directorates_for_govs_'.implode('_', $govIds);
        if (! isset(static::$geoCache[$cacheKey])) {
            static::$geoCache[$cacheKey] = DB::table('directorates')
                ->whereIn('governorate_id', $govIds)
                ->pluck('id')
                ->toArray();
        }

        return static::$geoCache[$cacheKey];
    }

    protected function getCachedInternalEntityIds(array $govIds, array $dirIds): array
    {
        $cacheKey = 'internal_entities_govs_'.implode('_', $govIds).'_dirs_'.implode('_', $dirIds);
        if (! isset(static::$geoCache[$cacheKey])) {
            $query = DB::table('internal_entities');
            if (! empty($govIds)) {
                $query->whereIn('governorate_id', $govIds);
            }
            if (! empty($dirIds)) {
                $query->orWhereIn('directorate_id', $dirIds);
            }
            static::$geoCache[$cacheKey] = $query->pluck('id')->toArray();
        }

        return static::$geoCache[$cacheKey];
    }

    protected function getCachedAuthorityIds(array $govIds, array $dirIds): array
    {
        $cacheKey = 'authorities_govs_'.implode('_', $govIds).'_dirs_'.implode('_', $dirIds);
        if (! isset(static::$geoCache[$cacheKey])) {
            $query = DB::table('authorities');
            if (! empty($govIds)) {
                $query->whereIn('governorate_id', $govIds);
            }
            if (! empty($dirIds)) {
                $query->orWhereIn('directorate_id', $dirIds);
            }
            static::$geoCache[$cacheKey] = $query->pluck('id')->toArray();
        }

        return static::$geoCache[$cacheKey];
    }

    protected function getCachedInternalEntityIdsByDirectorates(array $dirIds): array
    {
        $cacheKey = 'internal_entities_dirs_'.implode('_', $dirIds);
        if (! isset(static::$geoCache[$cacheKey])) {
            static::$geoCache[$cacheKey] = DB::table('internal_entities')
                ->whereIn('directorate_id', $dirIds)
                ->pluck('id')
                ->toArray();
        }

        return static::$geoCache[$cacheKey];
    }

    protected function getCachedAuthorityIdsByDirectorates(array $dirIds): array
    {
        $cacheKey = 'authorities_dirs_'.implode('_', $dirIds);
        if (! isset(static::$geoCache[$cacheKey])) {
            static::$geoCache[$cacheKey] = DB::table('authorities')
                ->whereIn('directorate_id', $dirIds)
                ->pluck('id')
                ->toArray();
        }

        return static::$geoCache[$cacheKey];
    }

    // =========================================================
    // 🔥 دوال عامة
    // =========================================================

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

<?php

namespace App\Services;

use App\Models\Authority;
use App\Models\Domain;
use App\Models\Governorate;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ScopeFilterService
{
    protected static array $userScopeCache = [];

    /**
     * استخراج وتكييش قائمة الكيانات والنطاقات المسموح بها للمستخدم الحالي للطلب الواحد.
     */
    public function getUserEntityIds($user = null): array
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return [];
        }

        $adminScopeId = session('selected_administrative_scope_id', $user->administrative_scope_id);
        $entityId = session('selected_entity_id', $user->entity_id);
        $geoScopes = session('selected_geographic_scopes');

        $cacheKey = $user->id.'_'.($adminScopeId ?? 0).'_'.($entityId ?? 0).'_'.md5(json_encode($geoScopes));

        if (isset(self::$userScopeCache[$cacheKey])) {
            return self::$userScopeCache[$cacheKey];
        }

        // ── أ) النطاق الإداري ──────────────────────────────
        $entityIdsByEnt = $adminScopeId ? InternalEntity::getAllChildrenIds($adminScopeId) : [];

        // ── ب) النطاق بحسب الكيان (entity_id) ──────────────
        $entityIdsByMyEnt = $entityId ? InternalEntity::getAllChildrenIds($entityId) : [];

        // ── ج) النطاقات الجغرافية ──────────────────────────
        if ($geoScopes !== null) {
            $entityIdsByGovAndDist = collect($geoScopes)->map(fn ($s) => (object) $s);
        } else {
            $entityIdsByGovAndDist = $user->geographicScopes ?? collect();
        }

        $entityIdsByGeo = [];
        foreach ($entityIdsByGovAndDist as $scope) {
            $govId = is_array($scope) ? ($scope['governorate_id'] ?? null) : ($scope->governorate_id ?? null);
            $dirId = is_array($scope) ? ($scope['directorate_id'] ?? null) : ($scope->directorate_id ?? null);

            if (! empty($dirId)) {
                $ids = InternalEntity::getAllByDirectorate($dirId);
                $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
            } elseif (! empty($govId)) {
                if ($govId === 'all' || $govId === 0 || $govId === '0') {
                    $ids = DB::table('internal_entities')->pluck('id')->map(fn ($id) => (int) $id)->toArray();
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                } else {
                    $ids = InternalEntity::getAllByGovernorate($govId);
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                }
            }
        }

        $entityIds = array_merge($entityIdsByEnt, $entityIdsByMyEnt, $entityIdsByGeo);
        $entityIds = array_unique($entityIds);
        $entityIds = array_filter($entityIds, fn ($id) => is_numeric($id) && $id > 0);

        return self::$userScopeCache[$cacheKey] = array_values($entityIds);
    }

    /**
     * جلب الجهات المتاحة للمستخدم ضمن نطاقه (للقوائم المنسدلة).
     */
    public function getScopedEntities($user = null)
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return collect();
        }

        if ($user->isExternal() || ($user->organization_type === 'external' && $user->authority_id)) {
            return Authority::where('id', $user->authority_id)
                ->select('id', 'agency_name as name')
                ->get();
        }

        $entitiesQuery = InternalEntity::where('is_active', true)->orderBy('name')->select('id', 'name');

        if (! $user->isAdmin()) {
            $entityIds = $this->getUserEntityIds($user);
            if (! empty($entityIds)) {
                $entitiesQuery->whereIn('id', $entityIds);
            } elseif ($user->entity_id) {
                $entitiesQuery->where('id', $user->entity_id);
            }
        }

        return $entitiesQuery->get();
    }

    /**
     * جلب الجهة الافتراضية للمستخدم.
     */
    public function getDefaultEntityId($user = null): ?int
    {
        $user = $user ?? auth()->user();

        if ($user?->isExternal()) {
            return $user->authority_id ? (int) $user->authority_id : null;
        }

        return $user?->entity_id ? (int) $user->entity_id : null;
    }

    /**
     * تطبيق فلترة النطاق الجغرافي والإداري على الاستعلام الأساسي.
     */
    public function applyEntityFilter(Builder $query, string $column = 'creator_entity_id'): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('0=1');
        }

        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return $query;
        }

        // 1. المستخدم الخارجي
        if ($user->isExternal() || ($user->organization_type === 'external' && $user->authority_id)) {
            $userAuthorityId = (int) $user->authority_id;
            $hasWorkflowResponsibility = ! empty($user->responsibility);

            return $query->where(function ($q) use ($user, $userAuthorityId, $hasWorkflowResponsibility) {
                $table = $q->getModel()->getTable();
                $q->where("$table.created_by_user_id", $user->id);

                if ($hasWorkflowResponsibility) {
                    $q->orWhere("$table.authority_id", $userAuthorityId);
                } else {
                    $authorityName = DB::table('authorities')->where('id', $userAuthorityId)->value('agency_name');
                    if ($authorityName) {
                        $q->orWhere("$table.created_by_entity", trim($authorityName));
                    }
                    $q->orWhere("$table.authority_id", $userAuthorityId);
                }
            });
        }

        // 2. مستخدم الوزارة الداخلي
        $entityIds = $this->getUserEntityIds($user);

        if (! empty($entityIds)) {
            $entityNames = DB::table('internal_entities')->whereIn('id', $entityIds)->pluck('name')->filter()->toArray();

            return $query->where(function ($q) use ($entityIds, $entityNames, $user, $column) {
                $table = $q->getModel()->getTable();
                $q->whereIn("$table.$column", $entityIds)
                    ->orWhereIn("$table.internal_entity_id", $entityIds)
                    ->orWhere("$table.created_by_user_id", $user->id);

                $cleanEntityNames = array_filter(array_map(function ($name) {
                    return trim(preg_replace('/\s+/u', ' ', $name));
                }, $entityNames));

                if (! empty($cleanEntityNames)) {
                    $q->orWhereIn("$table.created_by_entity", $cleanEntityNames);
                }

                if ($table === 'projects') {
                    $q->orWhereHas('projectApprovals', fn ($appQ) => $appQ->whereIn('project_approvals.entity_id', $entityIds));
                }
            });
        } else {
            $userEntityId = $user->entity_id;
            $userEntityName = $user->entity?->name;

            return $query->where(function ($q) use ($user, $userEntityId, $userEntityName, $column) {
                $table = $q->getModel()->getTable();
                $q->where("$table.created_by_user_id", $user->id);
                if ($userEntityId) {
                    $q->orWhere("$table.$column", $userEntityId)
                        ->orWhere("$table.internal_entity_id", $userEntityId);

                    if ($table === 'projects') {
                        $q->orWhereHas('projectApprovals', fn ($appQ) => $appQ->where('project_approvals.entity_id', $userEntityId));
                    }
                }
                if ($userEntityName) {
                    $cleanEntName = trim(preg_replace('/\s+/u', ' ', $userEntityName));
                    $q->orWhere("$table.created_by_entity", $cleanEntName);
                }
            });
        }
    }

    /**
     * تطبيق الفلاتر الشاملة (النطاق الجغرافي والإداري + فلاتر الطلب: الجهة، المحافظة، العام، المجال، إلخ)
     * مع تعيين جهة المستخدم كقيمة افتراضية عند عدم تحديد جهة أخرى.
     *
     * @param  bool  $enforceDefaultEntity  ما إذا كان يجب اعتماد جهة المستخدم كافتراضي عند فتح الصفحة لأول مرة
     */
    public function applyProjectFilters(Builder $query, ?Request $request = null, bool $enforceDefaultEntity = true): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('0=1');
        }

        // 1. تطبيق النطاق الجغرافي والإداري الأساسي
        $this->applyEntityFilter($query, 'creator_entity_id');

        if (! $request) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        // 2. فلتر الجهة (مع الافتراضي لجهة المستخدم)
        $orgInput = null;
        if ($request->has('organization') || $request->has('entity')) {
            $orgInput = $request->input('organization', $request->input('entity'));
        } elseif ($enforceDefaultEntity && ($user->entity_id || $user->authority_id)) {
            $orgInput = $user->entity_id ?? $user->authority_id;
        }

        if (! empty($orgInput) && $orgInput !== 'all' && $orgInput !== '') {
            $normalizedId = is_numeric($orgInput) ? (int) $orgInput : null;
            $organizationName = ! is_numeric($orgInput) ? $orgInput : null;

            if ($normalizedId && ! $organizationName) {
                $organizationName = DB::table('internal_entities')
                    ->where('id', $normalizedId)
                    ->value('name')
                    ?? DB::table('authorities')
                        ->where('id', $normalizedId)
                        ->value('agency_name');
            }

            $includeChildren = $request->boolean('include_children', false);

            if ($normalizedId !== null && $includeChildren) {
                $childIds = InternalEntity::getAllChildrenIds($normalizedId);
                $targetIds = array_values(array_unique(array_merge([$normalizedId], $childIds)));
                $targetNames = DB::table('internal_entities')->whereIn('id', $targetIds)->pluck('name')->filter()->toArray();

                $query->where(function ($q) use ($targetIds, $targetNames, $table) {
                    $q->whereIn("$table.creator_entity_id", $targetIds)
                        ->orWhereIn("$table.internal_entity_id", $targetIds)
                        ->orWhereIn("$table.authority_id", $targetIds);

                    if (! empty($targetNames)) {
                        $cleanNames = array_map(fn ($n) => trim(preg_replace('/\s+/u', ' ', $n)), $targetNames);
                        $q->orWhereIn("$table.created_by_entity", $cleanNames);
                    }
                });
            } else {
                // الافتراضي: الجهة المحددة فقط دون الأبناء
                $query->where(function ($q) use ($normalizedId, $organizationName, $orgInput, $table) {
                    if ($normalizedId !== null) {
                        $q->where("$table.creator_entity_id", $normalizedId)
                            ->orWhere("$table.internal_entity_id", $normalizedId)
                            ->orWhere("$table.authority_id", $normalizedId);
                    }

                    $q->orWhere("$table.created_by_entity", (string) $orgInput);

                    if (! empty($organizationName)) {
                        $cleanName = trim(preg_replace('/\s+/u', ' ', $organizationName));

                        $q->orWhere("$table.created_by_entity", $cleanName)
                            ->orWhereHas('creatorEntity', function ($entityQ) use ($cleanName) {
                                $entityQ->where('name', $cleanName);
                            })
                            ->orWhereHas('internalEntity', function ($entityQ) use ($cleanName) {
                                $entityQ->where('name', $cleanName);
                            })
                            ->orWhereHas('authority', function ($authQ) use ($cleanName) {
                                $authQ->where('agency_name', $cleanName);
                            });
                    }
                });
            }
        }

        // 3. فلتر المحافظة
        if ($request->filled('governorate')) {
            $governorate = $request->input('governorate');
            $query->whereHas('locations', function ($locQ) use ($governorate) {
                $locQ->where('governorate_id', $governorate);
            });
        }

        // 4. فلتر العام الهجري
        if ($request->filled('hijri_year')) {
            $hijriYear = $request->input('hijri_year');
            $query->where("$table.form_number", 'like', "PRO{$hijriYear}%");
        }

        // 5. فلتر المجال والمجال الفرعي والتدخل
        if ($request->filled('domain')) {
            $query->where("$table.domain_id", $request->input('domain'));
        }
        if ($request->filled('subdomain')) {
            $query->where("$table.subdomain_id", $request->input('subdomain'));
        }
        if ($request->filled('intervention')) {
            $query->where("$table.intervention_id", $request->input('intervention'));
        }

        // 6. فلتر نوع المشروع (جديد / قديم)
        if ($request->filled('project_type')) {
            $query->where("$table.project_type", $request->input('project_type'));
        }

        // 7. فلتر الحالة
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where("$table.status", $request->input('status'));
        }

        // 8. فلتر مشروع محدد
        if ($request->filled('project_id')) {
            $query->where("$table.id", $request->input('project_id'));
        }

        // 9. البحث بالنص
        if ($request->filled('search')) {
            $searchString = trim($request->input('search'));
            $words = array_filter(explode(' ', $searchString));

            $query->where(function ($q) use ($searchString, $words, $table) {
                $q->where("$table.form_number", 'like', "%{$searchString}%");
                $q->orWhere(function ($subQ) use ($words, $table) {
                    foreach ($words as $word) {
                        $subQ->where("$table.project_name", 'like', "%{$word}%");
                    }
                });
            });
        }

        return $query;
    }

    /**
     * جلب كافة بيانات الفلاتر للتقارير (الجهات، المحافظات، الأعوام، المجالات، الحالات).
     */
    public function getFilterDropdownData(?Request $request = null): array
    {
        $user = auth()->user();
        $cacheTtl = now()->addMinutes(15);

        $entities = $this->getScopedEntities($user);
        $defaultEntityId = $this->getDefaultEntityId($user);

        $selectedEntity = null;
        if ($request) {
            if ($request->has('organization') || $request->has('entity')) {
                $selectedEntity = $request->input('organization', $request->input('entity'));
            } else {
                $selectedEntity = $defaultEntityId;
            }
        } else {
            $selectedEntity = $defaultEntityId;
        }

        $governorates = Cache::remember('filter_dropdown_governorates', $cacheTtl, function () {
            return Governorate::whereIn(
                'id',
                ProjectLocation::whereNotNull('governorate_id')
                    ->distinct()
                    ->pluck('governorate_id')
            )->select('id', 'name')->orderBy('name')->get();
        });

        $domains = Cache::remember('filter_dropdown_domains', $cacheTtl, function () {
            return Domain::select('id', 'name')->orderBy('name')->get();
        });

        $hijriYears = Cache::remember('filter_dropdown_hijri_years', $cacheTtl, function () {
            return Project::withoutGlobalScopes()
                ->where('form_number', 'like', 'PRO%')
                ->selectRaw('SUBSTRING(form_number, 4, 4) as year')
                ->distinct()
                ->pluck('year')
                ->filter()
                ->sortDesc()
                ->values();
        });

        $allStatuses = [
            'draft' => 'مسودة',
            'pending' => 'قيد المراجعة',
            'under_review' => 'تحت الدراسة',
            'approved' => 'معتمد',
            'internally_approved' => 'معتمد داخلياً',
            'in_progress' => 'جارٍ التنفيذ',
            'in_execution' => 'قيد التنفيذ',
            'implementation' => 'مرحلة التنفيذ',
            'completed' => 'مكتمل',
            'suspended' => 'موقوف',
            'cancelled' => 'ملغي',
            'rejected' => 'مرفوض',
        ];

        return [
            'entities' => $entities,
            'defaultEntityId' => $defaultEntityId,
            'selectedEntity' => $selectedEntity,
            'governorates' => $governorates,
            'domains' => $domains,
            'hijriYears' => $hijriYears,
            'statuses' => $allStatuses,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Authority;
use App\Models\BeneficiaryGroup;
use App\Models\Directorate;
use App\Models\Domain;
use App\Models\FinancialItem;
use App\Models\FinancingForm;
use App\Models\FinancingType;
use App\Models\FundingSource;
use App\Models\Governorate;
use App\Models\InternalEntity;
use App\Models\Intervention;
use App\Models\MainRouter;
use App\Models\Priority;
use App\Models\Program;
use App\Models\SubArea;
use App\Models\Subdomain;
use App\Models\SubFinancingForm;
use App\Models\SubRouter;
use App\Models\TargetCategory;
use App\Models\Unit;
use App\Models\Village;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * General search method for various entities
     */
    public function search(Request $request)
    {
        $type = $request->get('type');
        $search = $request->get('q');
        $limit = $request->get('limit', 20);
        $user = auth()->user();
        $geographicScope = $this->getUserGeographicScope(); // ['governorate_id', 'directorate_id']

        // 1. بناء الاستعلام الأساسي وإعدادات الحقول حسب type
        $query = null;
        $idField = 'id';
        $textField = 'name';

        switch ($type) {
            case 'authority':

                $user = auth()->user();

                // =========================
                // Administrative Scope
                // =========================

                $entityIdsByEnt = [];

                if ($user->administrative_scope_id) {

                    // الأبناء
                    $childrenIds = InternalEntity::getAllChildrenIds(
                        $user->administrative_scope_id
                    );

                    // الآباء
                    $parentIds = InternalEntity::getAllParentIds(
                        $user->administrative_scope_id
                    );

                    // الجهة الحالية
                    $selfId = [$user->administrative_scope_id];

                    $entityIdsByEnt = array_merge(
                        $childrenIds ?? [],
                        $parentIds ?? [],
                        $selfId
                    );
                }

                // =========================
                // Geographic Scope
                // =========================

                $entityIdsByGeo = [];

                foreach ($user->geographicScopes as $scope) {

                    if (
                        ! empty($scope->governorate_id)
                        && empty($scope->directorate_id)
                    ) {

                        $ids = InternalEntity::getAllByGovernorate(
                            $scope->governorate_id
                        );

                        $entityIdsByGeo = array_merge(
                            $entityIdsByGeo,
                            $ids
                        );

                    } elseif (! empty($scope->directorate_id)) {

                        $ids = InternalEntity::getAllByDirectorate(
                            $scope->directorate_id
                        );

                        $entityIdsByGeo = array_merge(
                            $entityIdsByGeo,
                            $ids
                        );
                    }
                }

                // =========================
                // Merge IDs
                // =========================

                $entityIds = array_unique(array_merge(
                    $entityIdsByEnt ?? [],
                    $entityIdsByGeo ?? []
                ));

                $query = Authority::withoutGlobalScope(
                    'entity_display_filtering'
                )
                    ->with('parent', 'entity');

                $textField = 'agency_name';

                // =========================
                // Filtering
                // =========================

                $query->where(function ($q) use ($entityIds) {
                    if (empty($entityIds)) {
                        $q->whereRaw('0=1');

                        return;
                    }
                    $q->whereIn('creator_entity_id', $entityIds);
                });

                break;

            case 'internal_entity':

                $user = auth()->user();

                $query = InternalEntity::withoutGlobalScope('entity_display_filtering')
                    ->with('parent');

                $entityIds = [];

                foreach ($user->geographicScopes as $scope) {

                    if ($scope->governorate_id) {

                        $entityIds = array_merge(
                            $entityIds,
                            InternalEntity::getAllByGovernorate(
                                $scope->governorate_id
                            )
                        );
                    }
                }

                $entityIds = array_unique($entityIds);

                if (empty($entityIds)) {
                    $query->whereRaw('0 = 1');
                } else {
                    $query->whereIn('id', $entityIds);
                }

                break;
            case 'program':
                $query = Program::query();
                break;

            case 'domain':
                $query = Domain::query();
                break;

            case 'subdomain':
                $query = Subdomain::query();
                if ($request->has('domain_id')) {
                    $query->where('domain_id', $request->get('domain_id'));
                }
                break;

            case 'intervention':
                $query = Intervention::query();
                if ($request->has('subdomain_id')) {
                    $query->where('subdomain_id', $request->get('subdomain_id'));
                }
                break;

            case 'financial_item':
                $query = FinancialItem::query();
                break;

            case 'unit':
                $query = Unit::query();
                $textField = 'unit_name';
                break;

            case 'funding_source':
                $query = FundingSource::query();
                break;

            case 'financing_type':
                $query = FinancingType::query();
                break;

            case 'financing_form':
                $query = FinancingForm::query();
                break;

            case 'sub_financing_form':
                $query = SubFinancingForm::query();
                if ($request->has('financing_form_id')) {
                    $query->where('financing_form_id', $request->get('financing_form_id'));
                }
                break;

            case 'priority':
                $query = Priority::query();
                $textField = 'priority';
                break;

            case 'main_router':
                $query = MainRouter::query();
                $textField = 'main_router';
                break;

            case 'sub_router':
                $query = SubRouter::query();
                $textField = 'sub_router';
                if ($request->has('main_router_id')) {
                    $query->where('main_router_id', $request->get('main_router_id'));
                }
                break;

            case 'governorate':

                $query = Governorate::query();

                /*
                |--------------------------------------------------------------------------
                | إذا كان المستخدم إداري فقط
                | وغير مرتبط بأي نطاق جغرافي
                | يتم عرض جميع المحافظات
                |--------------------------------------------------------------------------
                */

                if (
                    ! (
                        $user->administrative_scope_id
                        && empty($geographicScope['governorate_id'])
                        && empty($geographicScope['directorate_id'])
                    )
                ) {

                    $this->applyGeographicScope(
                        $query,
                        $type,
                        $geographicScope
                    );
                }

                break;

            case 'directorate':

                $query = Directorate::query();

                /*
                |--------------------------------------------------------------------------
                | إذا كان المستخدم إداري فقط
                | وغير مرتبط بأي نطاق جغرافي
                | يتم عرض جميع المديريات
                |--------------------------------------------------------------------------
                */

                if (
                    ! (
                        $user->administrative_scope_id
                        && empty($geographicScope['governorate_id'])
                        && empty($geographicScope['directorate_id'])
                    )
                ) {

                    $this->applyGeographicScope(
                        $query,
                        $type,
                        $geographicScope
                    );

                    // إضافة فلترة إضافية خاصة بالمديرية

                    if ($directorateId = $geographicScope['directorate_id']) {

                        $query->where(function ($q) use ($directorateId, $geographicScope) {

                            $q->where('id', $directorateId)
                                ->orWhere(
                                    'governorate_id',
                                    $geographicScope['governorate_id']
                                );
                        });

                    } elseif ($governorateId = $geographicScope['governorate_id']) {

                        $query->where(
                            'governorate_id',
                            $governorateId
                        );
                    }
                }

                break;

            case 'sub_area':
                $query = SubArea::query();
                $this->applyGeographicScope($query, $type, $geographicScope);
                // فلترة إضافية بناءً على request
                if ($request->filled('directorate_id') && $request->get('directorate_id') != '0') {
                    if (! $geographicScope['directorate_id']) {
                        $query->where('directorate_id', $request->get('directorate_id'));
                    }
                }
                break;

            case 'village':
                $query = Village::query();
                $this->applyGeographicScope($query, $type, $geographicScope);
                if ($request->filled('sub_area_id') && $request->get('sub_area_id') != '0') {
                    if (! $geographicScope['directorate_id']) {
                        $query->where('sub_area_id', $request->get('sub_area_id'));
                    }
                }
                break;

            case 'target_category':
                $query = TargetCategory::query();
                break;

            case 'beneficiary_group':
                $query = BeneficiaryGroup::query();
                break;

            default:
                return response()->json(['error' => 'Invalid lookup type'], 400);
        }

        // 2. تصفية active إذا كان العمود موجودًا
        if ($query && \Schema::hasColumn($query->getModel()->getTable(), 'is_active')) {
            $query->where('is_active', true);
        }

        // 3. البحث النصي
        if (! empty($search)) {
            $query->where($textField, 'like', "%{$search}%");
        }

        // 4. تحديد الأعمدة المطلوبة
        $columns = [$idField, $textField];
        if (in_array($type, ['authority', 'internal_entity'])) {
            $columns[] = 'parent_id';
        }

        // 5. تنفيذ الاستعلام
        $results = $query->limit($limit)->get($columns);

        // 6. تنسيق النتائج لـ Select2
        $formattedResults = $results->map(function ($item) use ($idField, $textField, $type) {
            $data = [
                'id' => $item->$idField,
                'text' => $item->$textField,
            ];
            if ($type === 'authority') {
                $data['parent_id'] = $item->parent_id;
                $data['parent_name'] = $item->parent?->agency_name ?? 'لا توجد جهة أب';
            }
            if ($type === 'internal_entity') {
                $data['parent_id'] = $item->parent_id;
                $data['parent_name'] = $item->parent?->name ?? 'لا توجد جهة أب';
            }

            return $data;
        });

        // 7. إضافة خيار "الكل" للأنواع الجغرافية (إلا إذا كان هناك نتيجة واحدة فقط)
        if (in_array($type, ['governorate', 'directorate', 'sub_area', 'village']) && $formattedResults->count() !== 1) {
            $allText = match ($type) {
                'governorate' => 'جميع المحافظات',
                'directorate' => 'جميع المديريات',
                'sub_area' => 'جميع المناطق الفرعية',
                'village' => 'جميع القرى والحارات',
            };
            $formattedResults->prepend(['id' => '0', 'text' => $allText]);
        }

        return response()->json(['results' => $formattedResults]);
    }

    // ===============================
    // 🔹 دوال مساعدة لإزالة التكرار
    // ===============================

    /**
     * تطبيق فلترة الصلاحيات على جهات Authority
     */
    private function applyAuthorityFilter(Builder $query, $user): void
    {
        if (! $user) {
            return;
        }

        $entityIdsByEnt = InternalEntity::getAllChildrenIds($user->administrative_scope_id) ?? [];
        $entityIdsByGeo = [];

        foreach ($user->geographicScopes as $scope) {
            if (! empty($scope->governorate_id) && empty($scope->directorate_id)) {
                $ids = InternalEntity::getAllByGovernorate($scope->governorate_id);
            } elseif (! empty($scope->directorate_id)) {
                $ids = InternalEntity::getAllByDirectorate($scope->directorate_id);
            } else {
                $ids = [];
            }
            if (! empty($ids)) {
                $entityIdsByGeo[] = $ids;
            }
        }

        if (! empty($entityIdsByGeo)) {
            $entityIdsByGeo = array_merge(...$entityIdsByGeo);
        }
        $entityIds = array_unique(array_merge($entityIdsByEnt, $entityIdsByGeo));

        if (! empty($entityIds)) {
            $query->whereIn('creator_entity_id', $entityIds);
        }
    }

    /**
     * تطبيق فلترة الصلاحيات على الكيانات الداخلية InternalEntity
     */
    private function applyInternalEntityFilter(Builder $query, $user): void
    {
        if (! $user || $user->geographicScopes->isEmpty()) {
            return; // مركزي - لا فلتر
        }

        $entityIds = [];
        foreach ($user->geographicScopes as $scope) {
            if (! empty($scope->governorate_id) && empty($scope->directorate_id)) {
                $ids = InternalEntity::getAllByGovernorate($scope->governorate_id);
            } elseif (! empty($scope->directorate_id)) {
                $ids = InternalEntity::getAllByDirectorate($scope->directorate_id);
            } else {
                $ids = [];
            }
            if (! empty($ids)) {
                $entityIds[] = $ids;
            }
        }

        if (! empty($entityIds)) {
            $entityIds = array_unique(array_merge(...$entityIds));
            $query->whereIn('id', $entityIds);
        }
    }

    /**
     * تطبيق النطاق الجغرافي على استعلامات المحافظات/المديريات/المناطق/القرى
     */
    private function applyGeographicScope(Builder $query, string $type, array $scope): void
    {
        $govId = $scope['governorate_id'];
        $dirId = $scope['directorate_id'];

        switch ($type) {
            case 'governorate':
                if ($govId && ! $dirId) {
                    $query->where('id', $govId);
                }
                break;

            case 'directorate':
                if ($dirId) {
                    $query->where('id', $dirId);
                } elseif ($govId) {
                    $query->where('governorate_id', $govId);
                }
                break;

            case 'sub_area':
                if ($dirId) {
                    $query->where('directorate_id', $dirId);
                } elseif ($govId) {
                    $query->whereHas('directorate', fn ($q) => $q->where('governorate_id', $govId));
                }
                break;

            case 'village':
                if ($dirId) {
                    $query->whereHas('subArea', fn ($q) => $q->where('directorate_id', $dirId));
                } elseif ($govId) {
                    $query->whereHas('subArea.directorate', fn ($q) => $q->where('governorate_id', $govId));
                }
                break;
        }
    }

    /**
     * الحصول على النطاق الجغرافي للمستخدم الحالي
     */
    private function getUserGeographicScope(): array
    {
        $user = auth()->user();
        if (! $user) {
            return ['governorate_id' => null, 'directorate_id' => null];
        }

        return [
            'governorate_id' => $user->getAssignedGovernorateId(),
            'directorate_id' => $user->getAssignedDirectorateId(),
        ];
    }
}

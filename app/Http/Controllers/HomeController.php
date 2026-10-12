<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\InternalEntity;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    public function __construct(protected ProjectService $projectService) {}

    public function index()
    {
        $user = Auth::user();

        // ── فلترة المشاريع الموحدة (Single Source of Truth) ─────────────
        $projectQuery = Project::withoutGlobalScopes();
        $this->projectService->applyAccessibleProjectsQuery($projectQuery, $user);

        // ── فلترة البرامج ──────────────────────────────────────────────
        $programQuery = Program::query();
        if (! $user->isAdmin() && ! ($user->role && $user->role->full_access)) {
            $programQuery->whereHas('projects', function ($q) use ($user) {
                $this->projectService->applyAccessibleProjectsQuery($q, $user);
            });
        }

        // ── فلترة المستخدمين ──────────────────────────────────────────
        $userQuery = User::query();
        app(UserDataScopeService::class)->applyScope($userQuery, $user);

        // ================================================================
        // الإحصائيات والمشاريع الأخيرة
        // ================================================================
        $programCount = $programQuery->count();
        $userCount = $userQuery->count();
        $projectCount = (clone $projectQuery)->count();

        $completedProjectsCount = (clone $projectQuery)->where('status', 'completed')->count();
        $underReviewProjectsCount = (clone $projectQuery)->where(function ($q) {
            $q->where('status', 'under_review')
                ->orWhereHas('currentApprovalStage', function ($sq) {
                    $sq->whereIn('type', ['financial_review', 'technical_review', 'review']);
                });
        })->count();
        $underExecutionProjectsCount = (clone $projectQuery)->whereIn('status', ['in_progress', 'implementation', 'in_execution'])->count();

        $recentProjects = (clone $projectQuery)
            ->with(['program', 'domain', 'currentApprovalStage', 'authority', 'creatorEntity'])
            ->latest('projects.created_at')
            ->take(5)
            ->get();

        return view('home', compact(
            'user',
            'programCount',
            'projectCount',
            'userCount',
            'completedProjectsCount',
            'underReviewProjectsCount',
            'underExecutionProjectsCount',
            'recentProjects'
        ));
    }

    /**
     * بناء قائمة المعرفات من النطاقات الثلاثة.
     * مطابق لمنطق ProjectService (يدعم 'all' و null و 0 و '0').
     */
    private function getEntityIdsFromScope($adminScope, $entity, $geoScopes): array
    {
        // ── أ) النطاق الإداري ──
        $entityIdsByEnt = $adminScope ? InternalEntity::getAllChildrenIds($adminScope) : [];

        // ── ج) النطاق الجغرافي ──
        $entityIdsByGeo = [];

        if ($geoScopes) {
            foreach ($geoScopes as $scope) {
                $govId = is_array($scope) ? ($scope['governorate_id'] ?? null) : ($scope->governorate_id ?? null);
                $dirId = is_array($scope) ? ($scope['directorate_id'] ?? null) : ($scope->directorate_id ?? null);

                if (! empty($dirId)) {
                    // نطاق مديرية محددة
                    $ids = InternalEntity::getAllByDirectorate($dirId);
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                } elseif ($govId === 'all' || $govId === 0 || $govId === '0' || $govId === null) {
                    // نطاق «كل المحافظات»: جلب جميع الكيانات الداخلية
                    $ids = DB::table('internal_entities')
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->toArray();
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                } elseif (! empty($govId)) {
                    // نطاق محافظة محددة
                    $ids = InternalEntity::getAllByGovernorate($govId);
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                }
            }
        }

        // ── ب) نطاق الكيان ──
        $entityIdsByMyEnt = [];
        if (empty($entityIdsByEnt) && empty($entityIdsByGeo)) {
            $entityIdsByMyEnt = $entity ? InternalEntity::getAllChildrenIds($entity) : [];
        }

        $entityIds = array_unique(array_merge($entityIdsByEnt, $entityIdsByMyEnt, $entityIdsByGeo));

        return array_values(array_filter($entityIds, fn ($id) => is_numeric($id) && $id > 0));
    }

    /**
     * (اختياري) دالة فلترة المستخدمين القديمة، معدّلة لاستخدام الجلسة.
     */
    protected function applyUserScopeFilter(Builder $query): Builder
    {
        $user = Auth::user();
        $selectedAdminScope = Session::get('selected_administrative_scope_id', $user->administrative_scope_id);
        $selectedEntity = Session::get('selected_entity_id', $user->entity_id);
        $selectedGeographicScopes = Session::get('selected_geographic_scopes', $user->geographicScopes);

        $entityIds = $this->getEntityIdsFromScope($selectedAdminScope, $selectedEntity, $selectedGeographicScopes);
        if (! empty($entityIds)) {
            $query->whereIn('entity_id', $entityIds);
        }

        return $query;
    }

    /**
     * (اختياري) دالة النطاق الجغرافي للموديولات، يمكن تعديلها لاستخدام الجلسة لاحقاً.
     */
    protected function applyGeographicScope(Builder $query, string $module): void
    {
        $user = Auth::user();

        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return;
        }

        $scope = $user->getModuleGeoScope($module);

        if ($scope === 'all' || $scope === '') {
            return;
        }

        if ($scope === 'none') {
            $query->whereRaw('1 = 0');

            return;
        }

        $govId = $user->governorate_id ?? $user->getAssignedGovernorateId();
        $dirId = $user->directorate_id ?? $user->getAssignedDirectorateId();

        $model = $query->getModel();

        if ($scope === 'same_governorate' && $govId) {

            if ($model instanceof User) {
                $query->where('governorate_id', $govId);
            } else {
                $query->whereHas('createdBy', function ($q) use ($govId) {
                    $q->where('governorate_id', $govId);
                });
            }

        } elseif ($scope === 'same_directorate' && $dirId) {

            if ($model instanceof User) {
                $query->where('directorate_id', $dirId);
            } else {
                $query->whereHas('createdBy', function ($q) use ($dirId) {
                    $q->where('directorate_id', $dirId);
                });
            }
        }
    }
}

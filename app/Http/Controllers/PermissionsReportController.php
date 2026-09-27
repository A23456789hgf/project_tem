<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Entity;
use App\Services\PermissionAuditService;
use Illuminate\Support\Facades\Log;

class PermissionsReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:permissions.report']);
    }

    /**
     * Generate and display the comprehensive report.
     */
    public function index(PermissionAuditService $auditService)
    {
        if (! auth()->user()->can('permissions.report') && ! auth()->user()->can('reports.permissions.view')) {
            abort(403, 'غير مصرح لك بالوصول لتقرير الصلاحيات.');
        }
        $gaps = $auditService->auditControllerMethods();
        $roles = Role::withCount('rolePermissions as permissions_count')->get();
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();

        $moduleStats = Permission::selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->orderBy('module')
            ->get();

        // ---- Validation: Check that all entities are fetched ----
        $this->validateEntitiesFetched();

        if (request()->has('print')) {
            return view('reports.print_permissions_report', compact(
                'gaps',
                'roles',
                'totalPermissions',
                'totalRoles',
                'moduleStats'
            ));
        }

        return view('reports.permissions_report', compact(
            'gaps',
            'roles',
            'totalPermissions',
            'totalRoles',
            'moduleStats'
        ));
    }
/**
 * Validate that all expected entities are fetched.
 * Logs a warning if the fetched count is lower than expected.
 */
private function validateEntitiesFetched(): void
{
    $expected = config('app.expected_entities') ?? 0;
    if ($expected === 0 && class_exists(Entity::class)) {
        $expected = Entity::count();
    }

    // TODO: replace with actual source if entities are fetched from external service
    $fetched = Entity::count();

    if ($expected > 0 && $fetched < $expected) {
        Log::warning('Ubuntu – عدم جلب جميع الجهات', [
            'expected' => $expected,
            'fetched'  => $fetched,
            'route'    => request()->fullUrl(),
            'user_id'  => auth()->id(),
            'controller'=> self::class,
            'possible_cause' => [
                'case_sensitive_paths',
                'env_mismatch',
                'network_firewall',
            ],
        ]);
    }
}


    public function autoRegister(PermissionAuditService $auditService)
    {
        $this->authorize('permissions.report');
        $gaps = $auditService->auditControllerMethods();
        $count = 0;

        foreach ($gaps as $gap) {
            $module = explode('.', $gap['suggested_slug'])[0];
            $perm = Permission::firstOrNew(['slug' => $gap['suggested_slug']]);
            if (! $perm->exists) {
                $perm->name = $gap['suggested_name'];
                $perm->module = $module;
            }
            $perm->controller_class = $gap['controller'];
            $perm->controller_method = $gap['method'];
            $perm->save();
            $count++;
        }

        return redirect()->back()->with('success', "تم تسجيل {$count} صلاحية مفقودة آلياً بنجاح.");
    }
}

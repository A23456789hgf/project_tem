<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckMainModulePermission
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        // Super Admin / Full Access bypass
        if (
            (method_exists($user, 'isAdmin') && $user->isAdmin())
            || ($user->role && $user->role->full_access)
            || $user->hasRole('super-admin')
            || $user->hasRole('مدير النظام')
            || $user->hasRole('Admin')
        ) {
            return $next($request);
        }

        $routeName = $request->route() ? $request->route()->getName() : '';
        if (! $routeName) {
            return $next($request);
        }

        // Explicit Approved Entry Permissions per Route Prefix (No wildcards)
        $routeEntryMap = [
            'dashboard' => ['dashboard.view', 'main_modules.dashboard'],
            'projects.reports.' => [
                'reports.view', 'reports.implementation.view', 'reports.quality.view',
                'reports.financial.view', 'reports.official_summary.view', 'main_modules.reports',
                'reports.material_requests.view', 'reports.stock_entries.view',
                'reports.stock_ledger.view', 'reports.purchase_invoices.view',
            ],
            'projects.empowerment' => ['empowerment.view', 'empowerment.create', 'empowerment.edit', 'main_modules.empowerment'],
            'projects.api.tasks.users-by-scope' => ['task.assign'],
            'projects.api.entities.users' => ['task.assign'],
            'projects.' => [
                'projects.view', 'projects.view-details', 'projects.create', 'projects.edit',
                'execution.view', 'quality.view', 'approvals.view', 'main_modules.projects',
            ],
            'execution.' => ['execution.view', 'execution.edit', 'execution.approve', 'projects.view', 'main_modules.projects'],
            'tasks.' => ['task.view', 'task.create', 'task.edit', 'tasks.sidebar', 'tasks.print', 'main_modules.tasks', 'task_reports.view', 'task_reports.create'],
            'requests_descend.' => [
                'project-requests.view', 'project-requests.create', 'project-requests.edit',
                'project-requests.approve', 'main_modules.requests-descend',
            ],
            'project-referrals.' => ['referrals.view', 'correspondence.view', 'main_modules.correspondence'],
            'referrals.' => ['referrals.view', 'correspondence.view', 'main_modules.correspondence'],
            'correspondence.' => ['correspondence.view', 'referrals.view', 'memoirs.view', 'main_modules.correspondence'],
            'memoirs.' => ['memoirs.view', 'correspondence.view', 'main_modules.correspondence'],
            'plans.' => ['plans.view', 'plans.create', 'plans.update', 'main_modules.planning'],
            'value-chains.' => ['value_chains.view', 'value_chains.create', 'value_chains.edit', 'main_modules.value-chains'],
            'value-chain-members.' => ['value_chain_members.view', 'value_chain_members.create', 'value_chains.view', 'main_modules.value-chains'],
            'global-financings.' => ['value_chains.view', 'value_chains.create', 'value_chains.edit', 'main_modules.value-chains'],
            'chain_plans.' => ['value_chains.view', 'value_chains.create', 'value_chains.edit', 'main_modules.value-chains'],
            'programs.' => ['programs.view', 'programs.create', 'programs.edit', 'programs.approve', 'main_modules.encoding'],
            'domains.' => ['domains.view', 'domains.create', 'domains.edit', 'main_modules.encoding'],
            'subdomains.' => ['subdomains.view', 'subdomains.create', 'subdomains.edit', 'subdomains.approve', 'main_modules.encoding'],
            'interventions.' => ['interventions.view', 'interventions.create', 'interventions.edit', 'main_modules.encoding'],
            'governorates.' => ['governorates.view', 'governorates.create', 'governorates.edit', 'main_modules.encoding'],
            'directorates.' => ['directorates.view', 'directorates.create', 'directorates.edit', 'main_modules.encoding'],
            'sub-areas.' => ['sub-areas.view', 'sub-areas.create', 'sub-areas.edit', 'main_modules.encoding'],
            'villages.' => ['villages.view', 'villages.create', 'villages.edit', 'main_modules.encoding'],
            'financial-items.' => ['financial-items.view', 'financial-items.create', 'financial-items.edit', 'main_modules.encoding'],
            'funding-sources.' => ['funding-sources.view', 'funding-sources.create', 'funding-sources.edit', 'main_modules.encoding'],
            'financing-types.' => ['financing-types.view', 'financing-types.create', 'financing-types.edit', 'main_modules.encoding'],
            'value-chain-financing-types.' => ['value-chain-financing-types.view', 'value-chain-financing-types.edit', 'main_modules.encoding'],
            'formfinancing.' => ['form-financings.view', 'main_modules.encoding'],
            'subfinancing-forms.' => ['sub-financing-forms.view', 'main_modules.encoding'],
            'authorities.' => ['authorities.view', 'authorities.create', 'authorities.edit', 'authorities.bulk-edit', 'main_modules.encoding'],
            'main-routers.' => ['main-routers.view', 'main-routers.create', 'main-routers.edit', 'main_modules.encoding'],
            'sub-routers.' => ['sub-routers.view', 'sub-routers.create', 'sub-routers.edit', 'main_modules.encoding'],
            'priorities.' => ['priorities.view', 'priorities.create', 'priorities.edit', 'main_modules.encoding'],
            'units.' => ['units.view', 'units.create', 'units.edit', 'main_modules.encoding'],
            'beneficiary-groups.' => ['beneficiary-groups.view', 'beneficiary-groups.create', 'beneficiary-groups.edit', 'main_modules.encoding'],
            'configuration.sms.' => ['config.view', 'main_modules.encoding'],
            'signatures.' => ['signatures.view', 'signatures.create', 'signatures.edit', 'main_modules.encoding'],
            'internal-entities.' => ['internal-entities.view', 'internal-entities.create', 'internal-entities.edit', 'main_modules.encoding'],
            'entity-officers.' => ['entity-officers.view', 'entity-officers.create', 'entity-officers.edit', 'main_modules.encoding'],
            'entity-authorities.' => ['entity-authorities.view', 'entity-authorities.edit', 'main_modules.encoding'],
            'users.' => ['users.view', 'users.create', 'users.edit', 'main_modules.users'],
            'roles.' => ['roles.view', 'roles.create', 'roles.edit', 'main_modules.users'],
            'roles-permissions.' => ['roles-permissions.view', 'roles-permissions.edit', 'main_modules.users'],
            'admin.audit-logs.' => ['audit-logs.view', 'audit-logs.export', 'main_modules.users'],
        ];

        foreach ($routeEntryMap as $prefix => $approvedPerms) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                $hasEntryPermission = false;
                foreach ($approvedPerms as $perm) {
                    if ($user->can($perm)) {
                        $hasEntryPermission = true;
                        break;
                    }
                }

                if (! $hasEntryPermission) {
                    abort(403, 'ليس لديك الصلاحية للوصول إلى هذه الوحدة الرئيسية.');
                }
                break;
            }
        }

        return $next($request);
    }
}

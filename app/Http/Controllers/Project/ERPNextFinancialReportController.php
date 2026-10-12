<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Services\FrappeAPIService;
use App\Services\OrganizationResolver;
use App\Services\ScopeFilterService;
use Illuminate\Http\Request;

class ERPNextFinancialReportController extends Controller
{
    protected FrappeAPIService $frappe;

    public function __construct(
        FrappeAPIService $frappe,
        private OrganizationResolver $organizationResolver,
        private ScopeFilterService $scopeFilterService,
    ) {
        $this->frappe = $frappe;
        $this->middleware('permission:reports.view');
    }

    /**
     * عرض صفحة التقرير المالي الموحد للمشاريع (ERPNext)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $user->loadMissing(['entity', 'authority', 'role']);

        $isAdmin = $user->isAdmin() || ($user->role && $user->role->full_access);

        $userEntityName = null;
        $userEntityErpId = null;
        $allowedCompanies = null;

        if ($isAdmin) {
            $companies = InternalEntity::where('entity_type', 'Company')
                ->active()
                ->get();
        } else {
            $organization = $this->organizationResolver->resolve($user);
            $companyQuery = InternalEntity::withoutGlobalScopes()
                ->where('entity_type', 'Company')
                ->active();

            if ($organization['type'] === OrganizationResolver::INTERNAL) {
                $companyQuery->whereKey($organization['id']);
            } else {
                $this->organizationResolver->applyVisibleInternalEntities($companyQuery, $user);
            }

            $companies = $companyQuery->get();
            $allowedCompanies = $companies
                ->map(fn (InternalEntity $entity) => $entity->erpnext_id ?: $entity->name)
                ->values()
                ->all();
            $userEntityName = $organization['model']?->name ?? $organization['model']?->agency_name;
            $userEntityErpId = count($allowedCompanies) === 1 ? $allowedCompanies[0] : null;
        }

        // جلب المشاريع النشطة المرتبطة بـ ERPNext للفلاتر
        $projectQuery = Project::whereNotNull('erpnext_project_id');
        $this->scopeFilterService->applyEntityFilter($projectQuery);
        $projects = $projectQuery
            ->select('id', 'erpnext_project_id', 'project_name', 'form_number')
            ->orderBy('project_name')
            ->get();

        if ($request->has('print') || $request->input('export') === 'print') {
            $dataResponse = $this->getFinancialData($request)->getData(true);
            $plSummary = $dataResponse['pl_summary'] ?? [];
            $plStatement = $dataResponse['pl_statement'] ?? [];
            $plExpenses = $dataResponse['pl_expenses'] ?? [];
            $glReport = $dataResponse['gl_report'] ?? [];
            $company = $request->query('company') ?: ($userEntityName ?? 'كافة الجهات');

            return view('projects.reports.print_erpnext_financial', compact(
                'isAdmin',
                'company',
                'plSummary',
                'plStatement',
                'plExpenses',
                'glReport'
            ));
        }

        return view('projects.reports.erpnext_financial', compact(
            'isAdmin',
            'userEntityName',
            'userEntityErpId',
            'allowedCompanies',
            'companies',
            'projects'
        ));
    }

    /**
     * جلب بيانات التقرير المالي (P&L Summary, PL Expense Type Summary, GL Report)
     * GET /api/projects/reports/financial-data
     */
    public function getFinancialData(Request $request)
    {
        $user = auth()->user();
        $allowedCompanies = $this->getAllowedCompanies($user);

        // التحقق من صلاحية الجهة المطلوبة
        $company = $request->query('company');
        if ($company && ! $this->isCompanyAllowed($company, $allowedCompanies)) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لبيانات هذه الجهة.'], 403);
        }

        // إذا لم يحدد شركة وكان مستخدم عادي، نفرض شركته
        if (! $company && $allowedCompanies !== null && count($allowedCompanies) === 1) {
            $company = $allowedCompanies[0];
        }

        // بناء فلاتر التقارير
        $filterBasedOn = $request->query('filter_based_on', 'Fiscal Year');
        $fromFiscalYear = $request->query('from_fiscal_year', date('Y'));
        $toFiscalYear = $request->query('to_fiscal_year', date('Y'));
        $periodStartDate = $request->query('period_start_date', $fromFiscalYear.'-01-01');
        $periodEndDate = $request->query('period_end_date', $toFiscalYear.'-12-31');
        $periodicity = $request->query('periodicity', 'Yearly');

        $projectFilter = [];
        if ($request->query('project')) {
            $projectFilter = [$request->query('project')];
        }

        $filters = [
            'company' => $company ?: '',
            'filter_based_on' => $filterBasedOn,
            'period_start_date' => $periodStartDate,
            'period_end_date' => $periodEndDate,
            'from_fiscal_year' => $fromFiscalYear,
            'to_fiscal_year' => $toFiscalYear,
            'periodicity' => $periodicity,
            'show_account_details' => 'Summary',
            'selected_view' => 'Report',
            'include_default_book_entries' => 1,
            'accumulated_values' => 1,
        ];

        if (! empty($projectFilter)) {
            $filters['project'] = $projectFilter;
        }

        // 1. جلب خلاصة الأرباح والخسائر (Profit & Loss Summary)
        $plSummary = $this->frappe->httpGetMethod('get_pl_summary', [
            'filters' => json_encode($filters),
        ]);

        // 2. جلب النفقات حسب البنود (PL Expense Type Summary)
        $plExpenseSummary = $this->frappe->httpGetMethod('get_pl_expense_type_summary', [
            'filters' => json_encode($filters),
        ]);

        // 3. جلب دفتر الأستاذ العام (General Ledger Report)
        $glFilters = [
            'company' => $company ?: '',
            'from_date' => $periodStartDate,
            'to_date' => $periodEndDate,
        ];
        if ($request->query('project')) {
            $glFilters['project'] = $request->query('project');
        }

        $glReport = $this->frappe->httpGetMethod('get_gl_report', [
            'filters' => json_encode($glFilters),
        ]);

        // 4. جلب التقرير المالي للمشاريع الرئيسي (Profit and Loss Statement)
        $plStatement = $this->frappe->httpGetMethod('frappe.desk.query_report.run', [
            'report_name' => 'Profit and Loss Statement',
            'filters' => json_encode($filters),
        ]);

        return response()->json([
            'success' => true,
            'pl_summary' => $plSummary['success'] ? ($plSummary['data']['report_summary'] ?? $plSummary['data'] ?? []) : [],
            'pl_expenses' => $plExpenseSummary['success'] ? ($plExpenseSummary['data'] ?? []) : [],
            'gl_report' => $glReport['success'] ? ($glReport['data'] ?? []) : [],
            'pl_statement' => $plStatement['success'] ? ($plStatement['data'] ?? []) : [],
        ]);
    }

    protected function getAllowedCompanies($user): ?array
    {
        $isAdmin = $user->isAdmin() || ($user->role && $user->role->full_access);
        if ($isAdmin) {
            return null;
        }
        $organization = $this->organizationResolver->resolve($user);
        $query = InternalEntity::withoutGlobalScopes()->where('entity_type', 'Company')->active();

        if ($organization['type'] === OrganizationResolver::INTERNAL) {
            $query->whereKey($organization['id']);
        } else {
            $this->organizationResolver->applyVisibleInternalEntities($query, $user);
        }

        return $query->get()
            ->map(fn (InternalEntity $entity) => $entity->erpnext_id ?: $entity->name)
            ->values()
            ->all();
    }

    protected function isCompanyAllowed(string $company, ?array $allowedCompanies): bool
    {
        if ($allowedCompanies === null) {
            return true;
        }
        foreach ($allowedCompanies as $allowed) {
            if (str_contains(strtolower($company), strtolower($allowed))
                || str_contains(strtolower($allowed), strtolower($company))) {
                return true;
            }
        }

        return false;
    }
}

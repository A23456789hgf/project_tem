<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ErpNextService;
use App\Services\ErpNextReportService;

class ErpStockReportController extends Controller
{
    protected $erpNextService;
    protected $erpNextReportService;

    public function __construct(ErpNextService $erpNextService, ErpNextReportService $erpNextReportService)
    {
        $this->erpNextService = $erpNextService;
        $this->erpNextReportService = $erpNextReportService;
    }

    public function materialRequests(Request $request)
    {
        $this->authorize('reports.material_requests.view');

        $filters = $this->buildResourceFilters($request, [
            'docstatus' => 1,
            'status' => 'Pending'
        ]);

        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $limit_start = ($page - 1) * $limit;

        $params = [
            'fields' => '["name", "transaction_date", "company", "status", "schedule_date", "material_request_type"]',
            'limit_page_length' => $limit,
            'limit_start' => $limit_start,
            'order_by' => 'transaction_date desc',
        ];

        if (!empty($filters)) {
            $params['filters'] = json_encode($filters);
        }

        try {
            $response = $this->erpNextService->get('/api/resource/Material Request', $params);
            $data = $response['data'] ?? [];
            // ERPNext doesn't return total count in standard list API by default unless using frappe.client.get_list with certain flags.
            // We'll assume if count == limit, there might be more.
        } catch (\Exception $e) {
            $data = [];
            session()->flash('error', 'Error fetching Material Requests from ERPNext: ' . $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json([
                'data' => $data,
                'hasMore' => count($data) == $limit
            ]);
        }

        return view('projects.reports.stock.material_requests', compact('data', 'request'));
    }

    public function materialRequestItems(Request $request, $name)
    {
        $this->authorize('reports.material_requests.view');
        
        try {
            $response = $this->erpNextService->get('/api/resource/Material Request/' . urlencode($name));
            $data = $response['data'] ?? [];
            return response()->json($data['items'] ?? []);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function apiLookup(Request $request)
    {
        if (! auth()->user()->canAny([
            'reports.material_requests.view',
            'reports.stock_entries.view',
            'reports.stock_ledger.view',
            'reports.purchase_invoices.view'
        ])) {
            abort(403, 'Unauthorized action.');
        }
        
        $type = $request->input('type'); // Company, Warehouse, Item, Project, Cost Center, Supplier, Currency
        $search = $request->input('q', '');
        $page = $request->input('page', 1);
        $limit = 20;
        $limit_start = ($page - 1) * $limit;

        $allowedTypes = ['Company', 'Warehouse', 'Item', 'Project', 'Cost Center', 'Supplier', 'Currency'];
        if (!in_array($type, $allowedTypes)) {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        $filters = [];
        if (!empty($search)) {
            $filters[] = ['name', 'like', "%{$search}%"];
        }

        // Dependent filters
        if ($type === 'Warehouse' && $request->filled('company')) {
            $filters[] = ['company', '=', $request->company];
        }
        if ($type === 'Cost Center' && $request->filled('company')) {
            $filters[] = ['company', '=', $request->company];
        }

        $params = [
            'fields' => '["name"]',
            'limit_page_length' => $limit,
            'limit_start' => $limit_start
        ];

        if (!empty($filters)) {
            $params['filters'] = json_encode($filters);
        }

        try {
            $response = $this->erpNextService->get('/api/resource/' . urlencode($type), $params);
            $items = $response['data'] ?? [];
            
            $results = array_map(function($item) {
                return [
                    'id' => $item['name'],
                    'text' => $item['name']
                ];
            }, $items);

            return response()->json([
                'results' => $results,
                'pagination' => [
                    'more' => count($items) == $limit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function stockEntries(Request $request)
    {
        $this->authorize('reports.stock_entries.view');

        $filters = $this->buildResourceFilters($request, [
            'docstatus' => 1,
            'stock_entry_type' => 'Material Issue'
        ]);

        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $limit_start = ($page - 1) * $limit;

        $params = [
            'fields' => '["name", "posting_date", "company", "stock_entry_type", "purpose", "project"]',
            'limit_page_length' => $limit,
            'limit_start' => $limit_start,
            'order_by' => 'posting_date desc',
        ];

        if (!empty($filters)) {
            $params['filters'] = json_encode($filters);
        }

        try {
            $response = $this->erpNextService->get('/api/resource/Stock Entry', $params);
            $data = $response['data'] ?? [];
            if (isset($response['exc_type']) && $response['exc_type'] === 'PermissionError') {
                throw new \Exception('تعذر جلب بيانات صرف المواد من ERPNext بسبب عدم توفر صلاحية القراءة لحساب التكامل. يرجى مراجعة صلاحيات Stock Entry في ERPNext.');
            }
        } catch (\Exception $e) {
            $data = [];
            \Log::error('ERPNext Stock Entry Error: ' . $e->getMessage());
            session()->flash('error', $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json([
                'data' => $data,
                'hasMore' => count($data) == $limit
            ]);
        }

        return view('projects.reports.stock.stock_entries', compact('data', 'request'));
    }

    public function stockEntryItems(Request $request, $name)
    {
        $this->authorize('reports.stock_entries.view');
        
        try {
            $response = $this->erpNextService->get('/api/resource/Stock Entry/' . urlencode($name));
            if (isset($response['exc_type']) && $response['exc_type'] === 'PermissionError') {
                throw new \Exception('تعذر عرض التفاصيل بسبب عدم توفر صلاحية القراءة لحساب التكامل في ERPNext.');
            }
            $data = $response['data'] ?? [];
            return response()->json($data['items'] ?? []);
        } catch (\Exception $e) {
            \Log::error('ERPNext Stock Entry Items Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function stockLedger(Request $request)
    {
        $this->authorize('reports.stock_ledger.view');

        $data = collect();
        $columns = [];
        $total = 0;
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);

        if (empty($request->input('from_date'))) {
            $request->merge(['from_date' => now()->startOfMonth()->toDateString()]);
        }
        if (empty($request->input('to_date'))) {
            $request->merge(['to_date' => now()->toDateString()]);
        }

        if ($request->from_date > $request->to_date) {
            session()->flash('error', 'تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية.');
            return view('projects.reports.stock.stock_ledger', compact('data', 'columns', 'total', 'request'));
        }

        $filters = [];
        $allowedFilters = ['company', 'warehouse', 'item_code', 'project', 'batch_no', 'voucher_no', 'from_date', 'to_date', 'include_uom'];
        
        foreach ($allowedFilters as $field) {
            if ($request->filled($field)) {
                $val = $request->input($field);
                if ($field === 'include_uom') {
                    $filters[$field] = $val ? 1 : 0;
                } else {
                    $filters[$field] = $val;
                }
            }
        }

        try {
            $response = $this->erpNextService->post('/api/method/frappe.desk.query_report.run', [
                'report_name' => 'Stock Ledger',
                'filters' => json_encode($filters),
                'ignore_prepared_report' => true,
            ])->json();

            if (isset($response['exc_type'])) {
                if ($response['exc_type'] === 'PermissionError') {
                    throw new \Exception('تعذر تشغيل تقرير سجل المخزون بسبب عدم توفر الصلاحية المطلوبة في ERPNext.');
                }
                $errorMessage = $response['_server_messages'] ?? $response['exc_type'];
                throw new \Exception('خطأ في جلب بيانات سجل المخزون من ERPNext: ' . $errorMessage);
            }

            if (isset($response['message'])) {
                $columns = $response['message']['columns'] ?? [];
                $result = $response['message']['result'] ?? [];
                
                $collection = collect($result)->filter(function ($row) {
                    return !empty($row) && (isset($row['item_code']) || isset($row['posting_date']) || isset($row['voucher_no']));
                });

                $total = $collection->count();
                $data = $collection->forPage($page, $limit)->values();
            }

        } catch (\Exception $e) {
            \Log::error('ERPNext Stock Ledger Error: ' . $e->getMessage());
            session()->flash('error', $e->getMessage());
        }

        return view('projects.reports.stock.stock_ledger', compact('data', 'columns', 'total', 'request'));
    }


    private function buildResourceFilters(Request $request, array $defaultFilters = [])
    {
        $filters = [];
        
        // DocStatus
        if ($request->has('docstatus') && $request->docstatus !== '') {
            $filters[] = ['docstatus', '=', (int)$request->docstatus];
        } elseif (isset($defaultFilters['docstatus'])) {
            $filters[] = ['docstatus', '=', $defaultFilters['docstatus']];
        }

        // Status
        if ($request->has('status') && $request->status !== '') {
            $filters[] = ['status', '=', $request->status];
        } elseif (isset($defaultFilters['status'])) {
            $filters[] = ['status', '=', $defaultFilters['status']];
        }

        if ($request->filled('material_request_type')) {
            $filters[] = ['material_request_type', '=', $request->material_request_type];
        }
        
        if ($request->filled('stock_entry_type')) {
            $filters[] = ['stock_entry_type', '=', $request->stock_entry_type];
        }

        if ($request->filled('company')) {
            $filters[] = ['company', '=', $request->company];
        }

        $dateField = request()->routeIs('*.material_requests') ? 'transaction_date' : 'posting_date';
        
        if ($request->filled('from_date')) {
            $filters[] = [$dateField, '>=', $request->from_date];
        }
        if ($request->filled('to_date')) {
            $filters[] = [$dateField, '<=', $request->to_date];
        }

        // ERPNext doesn't natively support filtering parent by child table fields in REST API easily.
        // If these are passed, we have to either rely on custom endpoints or assume they exist on parent.
        // In Frappe, sometimes `Material Request` doesn't have `project` or `warehouse` on parent level but only in child `items`.
        // We will pass them as filters; if Frappe throws 403 or 500, they need to be removed or implemented via custom script.
        // For now, we will add them if they exist as standard filters since the user requested them.
        if ($request->filled('project')) {
            $filters[] = ['project', '=', $request->project];
        }
        if ($request->filled('cost_center')) {
            $filters[] = ['cost_center', '=', $request->cost_center];
        }
        if ($request->filled('source_warehouse')) {
            $filters[] = ['from_warehouse', '=', $request->source_warehouse]; // Frappe standard parent field is from_warehouse
        }
        if ($request->filled('target_warehouse')) {
            $filters[] = ['to_warehouse', '=', $request->target_warehouse]; // Frappe standard parent field is to_warehouse
        }
        if ($request->filled('item_code')) {
            $filters[] = ['item_code', '=', $request->item_code]; // If parent doesn't have it, Frappe might fail unless custom linked
        }

        return $filters;
    }
}

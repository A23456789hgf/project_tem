<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ErpNextService;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceController extends Controller
{
    protected $erpNextService;

    public function __construct(ErpNextService $erpNextService)
    {
        $this->erpNextService = $erpNextService;
    }

    public function purchaseInvoices(Request $request)
    {
        $this->authorize('reports.purchase_invoices.view');

        // Build server-side filters
        $filters = [];
        
        // DocStatus: 0 (Draft), 1 (Submitted), 2 (Cancelled)
        if ($request->has('docstatus') && $request->docstatus !== '') {
            $filters[] = ['docstatus', '=', (int)$request->docstatus];
        }

        if ($request->filled('status')) {
            $filters[] = ['status', '=', $request->status];
        }

        if ($request->filled('company')) {
            $filters[] = ['company', '=', $request->company];
        }

        if ($request->filled('project')) {
            $filters[] = ['project', '=', $request->project];
        }

        if ($request->filled('supplier')) {
            $filters[] = ['supplier', '=', $request->supplier];
        }

        if ($request->filled('from_date')) {
            $filters[] = ['posting_date', '>=', $request->from_date];
        }
        
        if ($request->filled('to_date')) {
            $filters[] = ['posting_date', '<=', $request->to_date];
        }

        $outstanding = $request->input('outstanding_status');
        if ($outstanding === 'unpaid') {
            $filters[] = ['outstanding_amount', '>', 0];
        } elseif ($outstanding === 'paid') {
            $filters[] = ['outstanding_amount', '=', 0];
        }
        
        if ($request->filled('bill_no')) {
            $filters[] = ['bill_no', 'like', "%{$request->bill_no}%"];
        }

        if ($request->filled('name')) {
            $filters[] = ['name', 'like', "%{$request->name}%"];
        }
        
        if ($request->filled('currency')) {
            $filters[] = ['currency', '=', $request->currency];
        }

        // Pagination
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $limit_start = ($page - 1) * $limit;

        $params = [
            'fields' => '["name", "posting_date", "supplier", "supplier_name", "company", "project", "bill_no", "grand_total", "outstanding_amount", "currency", "status"]',
            'limit_page_length' => $limit,
            'limit_start' => $limit_start,
            'order_by' => 'posting_date desc',
        ];

        if (!empty($filters)) {
            $params['filters'] = json_encode($filters);
        }

        try {
            $response = $this->erpNextService->get('/api/resource/Purchase Invoice', $params);
            $data = $response['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('ERPNext Purchase Invoices Error: ' . $e->getMessage());
            $data = [];
            session()->flash('error', $e->getMessage());
        }

        return view('projects.reports.stock.purchase_invoices', compact('data', 'request'));
    }

    public function purchaseInvoiceItems(Request $request, $name)
    {
        $this->authorize('reports.purchase_invoices.view');
        
        try {
            $response = $this->erpNextService->get('/api/resource/Purchase Invoice/' . urlencode($name));
            $data = $response['data'] ?? [];
            return response()->json($data['items'] ?? []);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

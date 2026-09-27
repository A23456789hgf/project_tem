@extends('layouts.app')

@section('title', 'لوحة تحكم فواتير الشراء')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    :root {
        --report-primary: #2c5f2d;
        --report-secondary: #97bc62;
        --report-accent: #f2e3c6;
        --report-text: #1f2937;
        --report-bg: #f8fafc;
        --report-border: #e2e8f0;
    }
    
    body {
        background-color: var(--report-bg);
    }
    
    .report-container {
        padding: 2rem;
        max-width: 1600px;
        margin: 0 auto;
    }
    
    .report-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--report-border);
    }
    
    .report-title {
        color: var(--report-primary);
        font-weight: 700;
        margin: 0;
    }
    
    /* Dashboard Cards */
    .kpi-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .kpi-card .card-body {
        position: relative;
        z-index: 2;
    }
    .kpi-card-icon {
        position: absolute;
        top: -10px;
        right: -10px;
        font-size: 6rem;
        opacity: 0.15;
        z-index: 1;
        transform: rotate(15deg);
    }
    .bg-gradient-primary { background: linear-gradient(135deg, #2c5f2d 0%, #1a3a1b 100%); }
    .bg-gradient-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .bg-gradient-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .bg-gradient-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    .bg-gradient-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .bg-gradient-dark { background: linear-gradient(135deg, #4b5563 0%, #374151 100%); }

    /* Filters */
    .filters-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        border: 1px solid var(--report-border);
    }
    .filters-header {
        background: #f8fafc;
        border-bottom: 1px solid var(--report-border);
        border-radius: 12px 12px 0 0;
        padding: 1rem 1.5rem;
    }
    
    .table-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        border: 1px solid var(--report-border);
        overflow: hidden;
    }
    
    .table thead th {
        background-color: var(--report-primary);
        color: #fff;
        border: none;
        padding: 1rem;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--report-border);
    }

    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 50rem;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .print-header {
        display: none;
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 1cm;
        }
        
        body {
            background-color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .d-print-none,
        .navbar,
        .sidebar,
        .filters-card,
        .report-header,
        .kpi-row,
        .pagination-container {
            display: none !important;
        }
        
        .report-container {
            padding: 0;
            margin: 0;
            max-width: 100%;
        }
        
        .table-container {
            box-shadow: none;
            border: none;
        }
        
        .table thead th {
            background-color: #f1f5f9 !important;
            color: #1f2937 !important;
            border: 1px solid #cbd5e1 !important;
        }
        
        .table tbody td {
            border: 1px solid #cbd5e1 !important;
        }
        
        .print-header {
            display: block;
            margin-bottom: 20px;
        }
    }
</style>
@endpush

@section('content')
<div class="print-header">
    <table width="100%" style="margin-bottom: 20px; border-bottom: 2px solid #2c5f2d; padding-bottom: 10px;">
        <tr>
            <td width="33%" style="text-align: right; vertical-align: top;">
                <div style="font-size: 16px; font-weight: bold; color: #2c5f2d;">الجمهورية اليمنية</div>
                <div style="font-size: 14px; color: #1f2937;">وزارة الزراعة والثروة السمكية والموارد المائية</div>
            </td>
            <td width="33%" style="text-align: center; vertical-align: middle;">
                <h3 style="color: #2c5f2d; margin: 0;">فواتير الشراء - ERPNext</h3>
            </td>
            <td width="33%" style="text-align: left; vertical-align: top;">
                <div style="font-size: 12px; color: #64748b;">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</div>
            </td>
        </tr>
    </table>
    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; font-size: 13px;">
        @if(request('company')) <span class="badge bg-light text-dark border">الشركة: {{ request('company') }}</span> @endif
        @if(request('supplier')) <span class="badge bg-light text-dark border">المورد: {{ request('supplier') }}</span> @endif
        @if(request('project')) <span class="badge bg-light text-dark border">المشروع: {{ request('project') }}</span> @endif
        @if(request('status')) <span class="badge bg-light text-dark border">الحالة: {{ request('status') }}</span> @endif
    </div>
</div>

<div class="report-container">
    <div class="report-header d-flex justify-content-between align-items-center d-print-none">
        <div>
            <h2 class="report-title"><i class="fas fa-file-invoice-dollar me-2"></i> لوحة تحكم فواتير الشراء</h2>
            <p class="text-muted mb-0">عرض فواتير الشراء المباشرة من ERPNext</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.reports.purchase_invoices') }}" class="btn btn-outline-secondary rounded-pill">
                <i class="fas fa-sync-alt me-1"></i> تحديث
            </a>
            @can('reports.purchase_invoices.print')
            <button type="button" onclick="window.print()" class="btn btn-outline-primary rounded-pill">
                <i class="fas fa-print me-1"></i> طباعة
            </button>
            @endcan
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger d-print-none">{{ session('error') }}</div>
    @endif

    <!-- KPI Cards Row -->
    <div class="row kpi-row d-print-none mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card kpi-card bg-gradient-primary h-100">
                <i class="fas fa-file-invoice kpi-card-icon"></i>
                <div class="card-body">
                    <h6 class="text-uppercase mb-1 opacity-75">إجمالي الفواتير</h6>
                    <h3 class="font-weight-bold mb-0" style="font-size: 1.2rem;">Dashboard metric unavailable efficiently</h3>
                    <small class="opacity-75">بحسب الفلاتر المحددة</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card kpi-card bg-gradient-success h-100">
                <i class="fas fa-check-circle kpi-card-icon"></i>
                <div class="card-body">
                    <h6 class="text-uppercase mb-1 opacity-75">الفواتير المسددة</h6>
                    <h3 class="font-weight-bold mb-0" style="font-size: 1.2rem;">Dashboard metric unavailable efficiently</h3>
                    <small class="opacity-75">إجمالي المبالغ المدفوعة</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card kpi-card bg-gradient-danger h-100">
                <i class="fas fa-exclamation-circle kpi-card-icon"></i>
                <div class="card-body">
                    <h6 class="text-uppercase mb-1 opacity-75">المبالغ المستحقة</h6>
                    <h3 class="font-weight-bold mb-0" style="font-size: 1.2rem;">Dashboard metric unavailable efficiently</h3>
                    <small class="opacity-75">غير المسددة للآن</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-card mb-4 d-print-none">
        <div class="filters-header">
            <h6 class="m-0 font-weight-bold text-report-primary"><i class="fas fa-filter me-2"></i> تصفية البيانات</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('projects.reports.purchase_invoices') }}" class="row g-3" id="filterForm">
                <input type="hidden" name="page" id="pageInput" value="1">
                
                <div class="col-md-3">
                    <label class="form-label">الشركة (Company)</label>
                    <select name="company" class="form-control select2-dynamic" data-type="Company" id="companyFilter">
                        @if(request('company'))
                            <option value="{{ request('company') }}" selected>{{ request('company') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المورد (Supplier)</label>
                    <select name="supplier" class="form-control select2-dynamic" data-type="Supplier">
                        @if(request('supplier'))
                            <option value="{{ request('supplier') }}" selected>{{ request('supplier') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المشروع (Project)</label>
                    <select name="project" class="form-control select2-dynamic" data-type="Project">
                        @if(request('project'))
                            <option value="{{ request('project') }}" selected>{{ request('project') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">العملة (Currency)</label>
                    <select name="currency" class="form-control select2-dynamic" data-type="Currency">
                        @if(request('currency'))
                            <option value="{{ request('currency') }}" selected>{{ request('currency') }}</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">رقم الفاتورة (Invoice No)</label>
                    <input type="text" name="name" class="form-control" placeholder="رقم الفاتورة..." value="{{ request('name') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">رقم فاتورة المورد (Bill No)</label>
                    <input type="text" name="bill_no" class="form-control" placeholder="رقم الفاتورة..." value="{{ request('bill_no') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">الحالة (Status)</label>
                    <select name="status" class="form-control">
                        <option value="">الكل</option>
                        <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>مسودة (Draft)</option>
                        <option value="Return" {{ request('status') == 'Return' ? 'selected' : '' }}>مرتجع (Return)</option>
                        <option value="Submitted" {{ request('status') == 'Submitted' ? 'selected' : '' }}>معتمد (Submitted)</option>
                        <option value="Paid" {{ request('status') == 'Paid' ? 'selected' : '' }}>مدفوع (Paid)</option>
                        <option value="Partly Paid" {{ request('status') == 'Partly Paid' ? 'selected' : '' }}>مدفوع جزئياً (Partly Paid)</option>
                        <option value="Unpaid" {{ request('status') == 'Unpaid' ? 'selected' : '' }}>غير مدفوع (Unpaid)</option>
                        <option value="Overdue" {{ request('status') == 'Overdue' ? 'selected' : '' }}>متأخر (Overdue)</option>
                        <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>ملغى (Cancelled)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">حالة المستند (DocStatus)</label>
                    <select name="docstatus" class="form-control">
                        <option value="">الكل</option>
                        <option value="0" {{ request('docstatus') === '0' ? 'selected' : '' }}>مسودة (0)</option>
                        <option value="1" {{ request('docstatus') === '1' ? 'selected' : '' }}>معتمد (1)</option>
                        <option value="2" {{ request('docstatus') === '2' ? 'selected' : '' }}>ملغى (2)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">حالة السداد</label>
                    <select name="outstanding_status" class="form-control">
                        <option value="">الكل</option>
                        <option value="unpaid" {{ request('outstanding_status') == 'unpaid' ? 'selected' : '' }}>يوجد مستحق</option>
                        <option value="paid" {{ request('outstanding_status') == 'paid' ? 'selected' : '' }}>مسدد بالكامل</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">من تاريخ (اختياري)</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">إلى تاريخ (اختياري)</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('projects.reports.purchase_invoices') }}" class="btn btn-light px-4">إعادة تعيين</a>
                    <button type="submit" class="btn btn-primary bg-report-primary border-0 px-4">
                        <i class="fas fa-search me-1"></i> تطبيق الفلاتر
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>التاريخ</th>
                        <th>الشركة / المشروع</th>
                        <th>المورد</th>
                        <th class="text-end">الإجمالي</th>
                        <th class="text-end">المستحق</th>
                        <th class="text-center">الحالة</th>
                        <th class="text-center d-print-none">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['name'] }}</strong>
                                @if(!empty($row['bill_no']))
                                    <br><small class="text-muted">مورد: {{ $row['bill_no'] }}</small>
                                @endif
                            </td>
                            <td>{{ $row['posting_date'] ?? '-' }}</td>
                            <td>
                                <span class="d-block">{{ $row['company'] ?? '-' }}</span>
                                <small class="text-primary">{{ $row['project'] ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="d-block">{{ $row['supplier_name'] ?? $row['supplier'] ?? '-' }}</span>
                                @if(!empty($row['supplier_name']) && $row['supplier_name'] !== $row['supplier'])
                                    <small class="text-muted">{{ $row['supplier'] }}</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($row['grand_total'] ?? 0, 2) }}</strong>
                                <br><small class="text-muted">{{ $row['currency'] ?? '' }}</small>
                            </td>
                            <td class="text-end">
                                <strong class="{{ ($row['outstanding_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($row['outstanding_amount'] ?? 0, 2) }}
                                </strong>
                            </td>
                            <td class="text-center">
                                @php
                                    $status = $row['status'] ?? '';
                                    $badgeClass = 'bg-secondary';
                                    if ($status === 'Paid') $badgeClass = 'bg-success';
                                    elseif ($status === 'Unpaid') $badgeClass = 'bg-danger';
                                    elseif ($status === 'Partly Paid') $badgeClass = 'bg-warning text-dark';
                                    elseif ($status === 'Draft') $badgeClass = 'bg-secondary';
                                    elseif ($status === 'Overdue') $badgeClass = 'bg-danger';
                                    elseif ($status === 'Return') $badgeClass = 'bg-info';
                                @endphp
                                <span class="status-badge {{ $badgeClass }}">{{ $status }}</span>
                            </td>
                            <td class="text-center d-print-none">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="showDetails('{{ $row['name'] }}', '{{ $row['posting_date'] ?? '' }}', '{{ $row['supplier'] ?? '' }}', '{{ $row['company'] ?? '' }}', '{{ $row['project'] ?? '' }}', '{{ $row['grand_total'] ?? 0 }}', '{{ $row['currency'] ?? '' }}', '{{ $row['status'] ?? '' }}')">
                                    التفاصيل
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-muted">لا توجد فواتير شراء مطابقة للفلاتر المحددة في ERPNext.</h5>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if(count($data) > 0 || request('page', 1) > 1)
    <div class="d-flex justify-content-between align-items-center mt-4 pagination-container d-print-none">
        <div class="d-flex align-items-center gap-2">
            <label>عرض:</label>
            <select class="form-select form-select-sm w-auto" onchange="changeLimit(this.value)">
                <option value="20" {{ request('limit', 20) == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" onclick="changePage(-1)" {{ request('page', 1) <= 1 ? 'disabled' : '' }}>
                السابق
            </button>
            <button type="button" class="btn btn-primary" disabled>
                صفحة {{ request('page', 1) }}
            </button>
            <button type="button" class="btn btn-outline-primary" onclick="changePage(1)" {{ count($data) < request('limit', 20) ? 'disabled' : '' }}>
                التالي
            </button>
        </div>
    </div>
    @endif
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-report-primary">
                    <i class="fas fa-file-invoice me-2"></i> فاتورة الشراء: <span id="modalInvoiceNo"></span>
                </h5>
                <div class="d-flex gap-2 ms-auto me-3 d-print-none">
                    @can('reports.purchase_invoices.print')
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> طباعة
                    </button>
                    @endcan
                </div>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalPrintArea">
                <div class="text-center d-none d-print-block mb-4">
                    <h4 style="color: #2c5f2d;">تفاصيل فاتورة الشراء: <span id="printInvoiceNo"></span></h4>
                    <hr>
                </div>
                
                <div id="detailsLoading" class="text-center py-5 d-print-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2 text-muted">جاري جلب التفاصيل من ERPNext...</p>
                </div>

                <div id="detailsContent" style="display: none;">
                    
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <small class="text-muted d-block">التاريخ</small>
                                    <strong id="mDate"></strong>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <small class="text-muted d-block">الشركة</small>
                                    <strong id="mCompany"></strong>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <small class="text-muted d-block">المورد</small>
                                    <strong id="mSupplier"></strong>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <small class="text-muted d-block">المشروع</small>
                                    <strong id="mProject"></strong>
                                </div>
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <small class="text-muted d-block">الإجمالي</small>
                                    <strong id="mTotal" class="text-report-primary" style="font-size: 1.1rem;"></strong>
                                    <small id="mCurrency" class="text-muted"></small>
                                </div>
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <small class="text-muted d-block">الحالة</small>
                                    <strong id="mStatus"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="font-weight-bold text-report-primary mb-3">الأصناف (Items)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>رمز الصنف</th>
                                    <th>اسم الصنف / الوصف</th>
                                    <th>المشروع</th>
                                    <th>المخزن</th>
                                    <th>مركز التكلفة</th>
                                    <th class="text-center">الكمية</th>
                                    <th class="text-center">الوحدة</th>
                                    <th class="text-end">السعر</th>
                                    <th class="text-end">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Items will be loaded here via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function changeLimit(limit) {
        document.getElementById('pageInput').value = 1;
        let form = document.getElementById('filterForm');
        let limitInput = document.createElement('input');
        limitInput.type = 'hidden';
        limitInput.name = 'limit';
        limitInput.value = limit;
        form.appendChild(limitInput);
        form.submit();
    }

    function changePage(delta) {
        let currentPage = parseInt(document.getElementById('pageInput').value) || 1;
        document.getElementById('pageInput').value = currentPage + delta;
        
        let urlParams = new URLSearchParams(window.location.search);
        let limit = urlParams.get('limit');
        
        let form = document.getElementById('filterForm');
        if (limit) {
            let limitInput = document.createElement('input');
            limitInput.type = 'hidden';
            limitInput.name = 'limit';
            limitInput.value = limit;
            form.appendChild(limitInput);
        }
        form.submit();
    }

    function showDetails(name, date, supplier, company, project, total, currency, status) {
        document.getElementById('modalInvoiceNo').textContent = name;
        document.getElementById('printInvoiceNo').textContent = name;
        
        // Fill Parent Data
        document.getElementById('mDate').textContent = date || '-';
        document.getElementById('mCompany').textContent = company || '-';
        document.getElementById('mSupplier').textContent = supplier || '-';
        document.getElementById('mProject').textContent = project || '-';
        document.getElementById('mTotal').textContent = Number(total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('mCurrency').textContent = currency || '';
        document.getElementById('mStatus').textContent = status || '-';

        document.getElementById('detailsLoading').style.display = 'block';
        document.getElementById('detailsContent').style.display = 'none';
        
        let detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
        detailsModal.show();

        let url = "{{ route('projects.reports.purchase_invoices.items', '__NAME__') }}".replace('__NAME__', encodeURIComponent(name));
        fetch(url)
            .then(res => res.json())
            .then(data => {
                document.getElementById('detailsLoading').style.display = 'none';
                document.getElementById('detailsContent').style.display = 'block';
                
                let tbody = document.getElementById('itemsTableBody');
                tbody.innerHTML = '';
                
                if (data.error) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">${data.error}</td></tr>`;
                    return;
                }
                
                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center">لا توجد أصناف</td></tr>`;
                    return;
                }
                
                data.forEach(item => {
                    let tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${item.item_code || '-'}</td>
                        <td>
                            <strong>${item.item_name || '-'}</strong>
                            ${item.description && item.description !== item.item_name ? `<br><small class="text-muted">${item.description}</small>` : ''}
                        </td>
                        <td><small class="text-primary">${item.project || '-'}</small></td>
                        <td><small>${item.warehouse || '-'}</small></td>
                        <td><small>${item.cost_center || '-'}</small></td>
                        <td class="text-center fw-bold">${Number(item.qty || 0).toFixed(2)}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${item.uom || '-'}</span></td>
                        <td class="text-end">${Number(item.rate || 0).toFixed(2)}</td>
                        <td class="text-end fw-bold text-success">${Number(item.amount || 0).toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(err => {
                document.getElementById('detailsLoading').style.display = 'none';
                document.getElementById('detailsContent').style.display = 'block';
                document.getElementById('itemsTableBody').innerHTML = `<tr><td colspan="9" class="text-center text-danger">حدث خطأ أثناء جلب البيانات</td></tr>`;
                console.error(err);
            });
    }

    $(document).ready(function() {
        // Initialize Select2 for ERPNext Generic Lookup
        $('.select2-dynamic').each(function() {
            var type = $(this).data('type');
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'اختر ' + type + '...',
                allowClear: true,
                ajax: {
                    url: '{{ route("projects.reports.material_requests.api_lookup") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            type: type,
                            page: params.page || 1,
                            company: $('#companyFilter').val()
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: (data.pagination && data.pagination.more)
                            }
                        };
                    },
                    cache: true
                }
            });
        });
    });
</script>
@endpush

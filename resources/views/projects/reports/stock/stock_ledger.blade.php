@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/modern-reports.css') }}" rel="stylesheet">
<link href="{{ asset('css/design-system/master.css') }}" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .text-report-primary { color: var(--report-primary) !important; }
    .bg-report-primary { background-color: var(--report-primary) !important; }
    .select2-container--default .select2-selection--single {
        height: 38px;
        padding: 5px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .table-container {
        overflow-x: auto;
    }
    
    @media print {
        @page { size: A4 landscape; margin: 1cm; }
        .d-print-none { display: none !important; }
        .card { border: none !important; box-shadow: none !important; margin: 0 !important; }
        .card-body { padding: 0 !important; }
        .table-container { overflow-x: visible !important; }
        .table { border: 1px solid #000; width: 100% !important; font-size: 10px; }
        .table th, .table td { border: 1px solid #000 !important; padding: 4px !important; }
        .print-header { display: block !important; margin-bottom: 20px; }
        .report-container { padding: 0 !important; }
    }
    
    .print-header { display: none; }
    
    .num-col { text-align: left; /* Or right in RTL if preferable, let's keep it consistent */ }
</style>
@endpush

@section('content')
<div class="report-container">
    <div class="report-header d-flex justify-content-between align-items-center mb-4 d-print-none">
        <div class="report-header-content">
            <h2 class="report-title text-report-primary"><i class="fas fa-book me-2"></i> سجل المخزون (Stock Ledger)</h2>
            <p class="text-muted mb-0">تقرير تفصيلي يعرض حركات وأرصدة المخزون من ERPNext</p>
        </div>
        <div class="d-flex gap-2">
            <x-ui.button href="{{ route('projects.reports.index') }}" variant="outline-primary" size="sm" icon="fas fa-arrow-right">العودة للتقارير</x-ui.button>
            @can('reports.stock_ledger.print')
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i> طباعة الصفحة الحالية</button>
            @endcan
            @can('reports.stock_ledger.export')
            <button type="button" class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i> تصدير (قريباً)</button>
            @endcan
        </div>
    </div>
    
    <!-- Print Only Header -->
    <div class="print-header text-center">
        <h2>تقرير سجل المخزون (Stock Ledger)</h2>
        <p>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 15px;">
            @if(request('company')) <span>الشركة: {{ request('company') }}</span> @endif
            @if(request('warehouse')) <span>المخزن: {{ request('warehouse') }}</span> @endif
            @if(request('item_code')) <span>الصنف: {{ request('item_code') }}</span> @endif
            @if(request('project')) <span>المشروع: {{ request('project') }}</span> @endif
            @if(request('batch_no')) <span>رقم التشغيلة: {{ request('batch_no') }}</span> @endif
            @if(request('voucher_no')) <span>رقم المستند: {{ request('voucher_no') }}</span> @endif
            <span>الفترة: {{ request('from_date') }} إلى {{ request('to_date') }}</span>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger d-print-none">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="alert alert-info d-print-none">{{ session('info') }}</div>
    @endif

    <div class="card mb-4 d-print-none">
        <div class="card-body">
            <form method="GET" action="{{ route('projects.reports.stock_ledger') }}" class="row g-3" id="filterForm">
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
                    <label class="form-label">المخزن (Warehouse)</label>
                    <select name="warehouse" class="form-control select2-dynamic-warehouse" data-type="Warehouse" id="warehouseFilter">
                        @if(request('warehouse'))
                            <option value="{{ request('warehouse') }}" selected>{{ request('warehouse') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الصنف (Item)</label>
                    <select name="item_code" class="form-control select2-dynamic" data-type="Item">
                        @if(request('item_code'))
                            <option value="{{ request('item_code') }}" selected>{{ request('item_code') }}</option>
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
                <div class="col-md-2">
                    <label class="form-label">رقم التشغيلة (Batch)</label>
                    <select name="batch_no" class="form-control select2-dynamic" data-type="Batch">
                        @if(request('batch_no'))
                            <option value="{{ request('batch_no') }}" selected>{{ request('batch_no') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">رقم المستند (Voucher)</label>
                    <input type="text" name="voucher_no" class="form-control" placeholder="بحث بالنص..." value="{{ request('voucher_no') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label text-danger">من تاريخ (إلزامي)*</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-danger">إلى تاريخ (إلزامي)*</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date', now()->endOfMonth()->format('Y-m-d')) }}" required>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">العدد بالصفحة</label>
                    <select name="limit" class="form-control" id="limitSelect">
                        <option value="20" {{ request('limit', 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="include_uom" value="1" id="includeUom" {{ request('include_uom') ? 'checked' : '' }}>
                        <label class="form-check-label" for="includeUom">
                            تضمين الوحدات (UOM)
                        </label>
                    </div>
                </div>

                <div class="col-md-12 d-flex align-items-end gap-2 justify-content-end mt-3">
                    <a href="{{ route('projects.reports.stock_ledger') }}" class="btn btn-secondary"><i class="fas fa-undo me-2"></i> إعادة تعيين</a>
                    <button type="button" class="btn btn-info text-white" onclick="document.getElementById('pageInput').value=1; document.getElementById('filterForm').submit();"><i class="fas fa-sync-alt me-2"></i> تحديث</button>
                    <button type="submit" class="btn btn-primary" onclick="document.getElementById('pageInput').value=1"><i class="fas fa-filter me-2"></i> تطبيق الفلاتر</button>
                </div>
            </form>
        </div>
    </div>

    @if(!session('error') && !session('info'))
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover table-striped mb-0 table-sm">
                    <thead class="bg-light sticky-top">
                        <tr>
                            @if(!empty($columns))
                                @foreach($columns as $col)
                                    @php
                                        $label = is_array($col) ? ($col['label'] ?? $col['fieldname'] ?? '') : $col;
                                        $width = is_array($col) && isset($col['width']) ? $col['width'] . 'px' : 'auto';
                                    @endphp
                                    <th style="min-width: {{ $width }}">{{ $label }}</th>
                                @endforeach
                            @else
                                <th>البيانات</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($data) > 0)
                            @foreach($data as $row)
                                <tr>
                                    @foreach($columns as $col)
                                        @php
                                            $fieldName = is_array($col) ? ($col['fieldname'] ?? '') : $col;
                                            $fieldType = is_array($col) ? ($col['fieldtype'] ?? 'Data') : 'Data';
                                            $val = is_array($row) ? ($row[$fieldName] ?? '') : '';
                                            
                                            // Formatting
                                            $formattedVal = $val;
                                            $isNumeric = in_array($fieldType, ['Currency', 'Float', 'Int']);
                                            
                                            if ($isNumeric && is_numeric($val)) {
                                                $formattedVal = number_format((float)$val, $fieldType === 'Int' ? 0 : 3);
                                            } elseif ($fieldType === 'Date' && !empty($val)) {
                                                $formattedVal = date('Y-m-d', strtotime($val));
                                            }
                                        @endphp
                                        <td class="{{ $isNumeric ? 'text-start fw-medium' : '' }}" dir="{{ $isNumeric ? 'ltr' : 'auto' }}">
                                            @if($fieldType === 'Link' && !empty($formattedVal))
                                                <span class="text-primary">{{ $formattedVal }}</span>
                                            @else
                                                {{ $formattedVal }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ count($columns) ?: 1 }}" class="text-center py-4 text-muted">
                                    لا توجد حركات مخزون خلال الفترة المحددة.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            @if(count($data) > 0)
            <div class="d-flex justify-content-between align-items-center p-3 border-top d-print-none">
                @php
                    $currentPage = (int)request('page', 1);
                    $limit = (int)request('limit', 20);
                    $totalPages = ceil($total / $limit);
                    $hasMore = $currentPage < $totalPages;
                @endphp
                <div>
                    عرض {{ count($data) }} سجل من إجمالي {{ $total }} سجل (الصفحة {{ $currentPage }} من {{ $totalPages ?: 1 }})
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" onclick="changePage({{ $currentPage - 1 }})" {{ $currentPage <= 1 ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-right"></i> السابق
                    </button>
                    <button class="btn btn-outline-secondary" onclick="changePage({{ $currentPage + 1 }})" {{ !$hasMore ? 'disabled' : '' }}>
                        التالي <i class="fas fa-chevron-left"></i>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function changePage(newPage) {
        document.getElementById('pageInput').value = newPage;
        document.getElementById('filterForm').submit();
    }

    $(document).ready(function() {
        var lookupUrl = '{{ route("projects.reports.material_requests.api_lookup") }}';

        // General dynamic Select2
        $('.select2-dynamic').each(function() {
            var el = $(this);
            var type = el.data('type');
            el.select2({
                placeholder: 'بحث...',
                allowClear: true,
                ajax: {
                    url: lookupUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            type: type,
                            company: $('#companyFilter').val()
                        };
                    },
                    cache: true
                }
            });
        });

        // Warehouse Select2 linked to Company
        $('.select2-dynamic-warehouse').select2({
            placeholder: 'بحث...',
            allowClear: true,
            ajax: {
                url: lookupUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page || 1,
                        type: 'Warehouse',
                        company: $('#companyFilter').val()
                    };
                },
                cache: true
            }
        });

        $('#companyFilter').on('change', function() {
            $('#warehouseFilter').val(null).trigger('change');
        });
    });
</script>
@endpush

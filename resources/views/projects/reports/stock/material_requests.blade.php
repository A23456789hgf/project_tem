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
    .details-row { background-color: #f8f9fa; }
</style>
@endpush

@section('content')
<div class="report-container">
    <div class="report-header d-flex justify-content-between align-items-center mb-4">
        <div class="report-header-content">
            <h2 class="report-title text-report-primary"><i class="fas fa-boxes me-2"></i> تقرير طلبات المواد (Material Requests)</h2>
            <p class="text-muted mb-0">تقرير يعرض طلبات المواد المعلقة من ERPNext</p>
        </div>
        <div class="d-flex gap-2">
            <x-ui.button href="{{ route('projects.reports.index') }}" variant="outline-primary" size="sm" icon="fas fa-arrow-right">العودة للتقارير</x-ui.button>
            @can('reports.material_requests.print')
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i> طباعة</button>
            @endcan
            @can('reports.material_requests.export')
            <button type="button" class="btn btn-success btn-sm" disabled><i class="fas fa-file-excel me-1"></i> تصدير (قريباً)</button>
            @endcan
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4 d-print-none">
        <div class="card-body">
            <form method="GET" action="{{ route('projects.reports.material_requests') }}" class="row g-3" id="filterForm">
                <input type="hidden" name="page" id="pageInput" value="{{ request('page', 1) }}">
                
                <div class="col-md-3">
                    <label class="form-label">الشركة (Company)</label>
                    <select name="company" class="form-control select2-dynamic" data-type="Company">
                        @if(request('company'))
                            <option value="{{ request('company') }}" selected>{{ request('company') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المخزن (Warehouse)</label>
                    <select name="warehouse" class="form-control select2-dynamic" data-type="Warehouse">
                        @if(request('warehouse'))
                            <option value="{{ request('warehouse') }}" selected>{{ request('warehouse') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الصنف (Item)</label>
                    <select name="item" class="form-control select2-dynamic" data-type="Item">
                        @if(request('item'))
                            <option value="{{ request('item') }}" selected>{{ request('item') }}</option>
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
                    <label class="form-label">مركز التكلفة (Cost Center)</label>
                    <select name="cost_center" class="form-control select2-dynamic" data-type="Cost Center">
                        @if(request('cost_center'))
                            <option value="{{ request('cost_center') }}" selected>{{ request('cost_center') }}</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">الحالة (Status)</label>
                    <select name="status" class="form-control">
                        <option value="">الكل</option>
                        <option value="Pending" {{ request('status', 'Pending') == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Stopped" {{ request('status') == 'Stopped' ? 'selected' : '' }}>Stopped</option>
                        <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">حالة المستند (DocStatus)</label>
                    <select name="docstatus" class="form-control">
                        <option value="">الكل</option>
                        <option value="0" {{ request('docstatus') == '0' ? 'selected' : '' }}>Draft (0)</option>
                        <option value="1" {{ request('docstatus', '1') == '1' ? 'selected' : '' }}>Submitted (1)</option>
                        <option value="2" {{ request('docstatus') == '2' ? 'selected' : '' }}>Cancelled (2)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">نوع الطلب (Type)</label>
                    <select name="material_request_type" class="form-control">
                        <option value="">الكل</option>
                        <option value="Purchase" {{ request('material_request_type') == 'Purchase' ? 'selected' : '' }}>Purchase</option>
                        <option value="Material Transfer" {{ request('material_request_type') == 'Material Transfer' ? 'selected' : '' }}>Material Transfer</option>
                        <option value="Material Issue" {{ request('material_request_type') == 'Material Issue' ? 'selected' : '' }}>Material Issue</option>
                        <option value="Manufacture" {{ request('material_request_type') == 'Manufacture' ? 'selected' : '' }}>Manufacture</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">العدد بالصفحة</label>
                    <select name="limit" class="form-control" id="limitSelect">
                        <option value="20" {{ request('limit', 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <div class="col-md-12 d-flex align-items-end gap-2 justify-content-end mt-3">
                    <a href="{{ route('projects.reports.material_requests') }}" class="btn btn-secondary"><i class="fas fa-undo me-2"></i> إعادة تعيين</a>
                    <button type="submit" class="btn btn-primary" onclick="document.getElementById('pageInput').value=1"><i class="fas fa-filter me-2"></i> تصفية</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="bg-light">
                        <tr>
                            <th>رقم الطلب</th>
                            <th>تاريخ الطلب</th>
                            <th>تاريخ الجدولة</th>
                            <th>الشركة</th>
                            <th>النوع</th>
                            <th>الحالة</th>
                            <th class="d-print-none">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                <td>{{ $row['name'] ?? '-' }}</td>
                                <td>{{ $row['transaction_date'] ?? '-' }}</td>
                                <td>{{ $row['schedule_date'] ?? '-' }}</td>
                                <td>{{ $row['company'] ?? '-' }}</td>
                                <td>{{ $row['material_request_type'] ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-warning text-dark">{{ $row['status'] ?? 'Pending' }}</span>
                                </td>
                                <td class="d-print-none">
                                    @can('reports.material_requests.view_details')
                                    <button class="btn btn-sm btn-info text-white btn-view-details" data-id="{{ $row['name'] }}">
                                        <i class="fas fa-eye me-1"></i> التفاصيل
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">لا توجد بيانات (No data found)</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center p-3 border-top d-print-none">
                @php
                    $currentPage = (int)request('page', 1);
                    $limit = (int)request('limit', 20);
                    $hasMore = count($data) == $limit;
                @endphp
                <div>
                    الصفحة {{ $currentPage }}
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
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">تفاصيل الطلب: <span id="modalRequestName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalLoader" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
        <div id="modalContent" style="display: none;">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center">
                    <thead class="table-light">
                        <tr>
                            <th>الصنف</th>
                            <th>الكمية</th>
                            <th>وحدة القياس</th>
                            <th>المخزن</th>
                            <th>المشروع</th>
                            <th>مركز التكلفة</th>
                            <th>السعر</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsBody">
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
    function changePage(newPage) {
        document.getElementById('pageInput').value = newPage;
        document.getElementById('filterForm').submit();
    }

    $(document).ready(function() {
        // Initialize Select2 dynamically
        $('.select2-dynamic').each(function() {
            var el = $(this);
            var type = el.data('type');
            el.select2({
                placeholder: 'بحث...',
                allowClear: true,
                ajax: {
                    url: '{{ route("projects.reports.material_requests.api_lookup") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1,
                            type: type,
                            company: $('select[name="company"]').val() // Pass company filter if available
                        };
                    },
                    cache: true
                }
            });
        });

        // View Details Modal
        $('.btn-view-details').click(function() {
            var name = $(this).data('id');
            $('#modalRequestName').text(name);
            $('#modalLoader').show();
            $('#modalContent').hide();
            $('#modalItemsBody').empty();
            
            var modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();

            var url = '{{ route("projects.reports.material_requests.items", ["name" => ":name"]) }}'.replace(':name', encodeURIComponent(name));
            
            $.ajax({
                url: url,
                type: 'GET',
                success: function(items) {
                    $('#modalLoader').hide();
                    $('#modalContent').show();
                    
                    if (items.length === 0) {
                        $('#modalItemsBody').html('<tr><td colspan="8">لا توجد أصناف</td></tr>');
                        return;
                    }

                    var html = '';
                    items.forEach(function(item) {
                        html += '<tr>';
                        html += '<td><strong>' + (item.item_code || '-') + '</strong><br><small class="text-muted">' + (item.item_name || '') + '</small></td>';
                        html += '<td>' + (item.qty || 0) + '</td>';
                        html += '<td>' + (item.uom || '-') + '</td>';
                        html += '<td><small>' + (item.warehouse || '-') + '</small></td>';
                        html += '<td><small>' + (item.project || '-') + '</small></td>';
                        html += '<td><small>' + (item.cost_center || '-') + '</small></td>';
                        html += '<td>' + (item.rate || 0) + '</td>';
                        html += '<td>' + (item.amount || 0) + '</td>';
                        html += '</tr>';
                    });
                    $('#modalItemsBody').html(html);
                },
                error: function() {
                    $('#modalLoader').hide();
                    $('#modalContent').show();
                    $('#modalItemsBody').html('<tr><td colspan="8" class="text-danger">حدث خطأ أثناء جلب التفاصيل</td></tr>');
                }
            });
        });
    });
</script>
@endpush

@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4">
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <x-icon name="tasks" class="me-2" />متابعة سجلات التنفيذ
            </h2>
            <p class="text-muted small mb-0 mt-1">استعراض وموافقة على جميع سجلات التنفيذ للمشاريع المعتمدة</p>
        </div>
    </div>

    {{-- Statistics Section --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-start border-4 border-warning shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">قيد المراجعة</div>
                        <div class="h3 mb-0 fw-bold mt-2">{{ $stats['pending'] }}</div>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <x-icon name="hourglass-half" class="text-warning" size="24" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-start border-4 border-success shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">موافق عليها</div>
                        <div class="h3 mb-0 fw-bold mt-2">{{ $stats['approved'] }}</div>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <x-icon name="check-circle" class="text-success" size="24" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-start border-4 border-danger shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">مرفوضة</div>
                        <div class="h3 mb-0 fw-bold mt-2">{{ $stats['rejected'] }}</div>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <x-icon name="times-circle" class="text-danger" size="24" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><x-icon name="filter" class="me-2" />خيارات التصفية والبحث</h6>
        </div>
        <div class="card-body py-3">
            <form method="GET" action="{{ route('execution.tracking') }}" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="status" class="form-label small fw-bold">حالة الموافقة</label>
                        <select name="status" id="status" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>موافق عليه</option>
                            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>الكل</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="type" class="form-label small fw-bold">نوع السجل</label>
                        <select name="type" id="type" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ $type === 'all' ? 'selected' : '' }}>الكل</option>
                            <option value="preliminary" {{ $type === 'preliminary' ? 'selected' : '' }}>الأنشطة التحضيرية</option>
                            <option value="executive" {{ $type === 'executive' ? 'selected' : '' }}>الأنشطة التنفيذية</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="project_id" class="form-label small fw-bold">المشروع</label>
                        <select name="project_id" id="project_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                            <option value="">اختر مشروعاً</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ $projectId == $project->id ? 'selected' : '' }}>
                                    {{ $project->project_name }} ({{ $project->form_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="search" class="form-label small fw-bold">بحث</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="ابحث باسم المشروع..." value="{{ $searchTerm }}">
                            <button type="submit" class="btn btn-primary">
                                <x-icon name="search" />
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Preliminary Executions --}}
    @if($type === 'preliminary' || $type === 'all')
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 h6 fw-bold">
                <x-icon name="clipboard-list" class="me-2 text-primary" />سجلات الأنشطة التحضيرية
                <span class="badge bg-info ms-2 rounded-pill">{{ $preliminaryExecutions->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                @if($preliminaryExecutions->count() > 0)
                    <table class="table table-bordered table-hover align-middle table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">اسم المشروع</th>
                                <th width="20%">الإجراء</th>
                                <th width="10%">الحالة</th>
                                <th width="10%">المبلغ الفعلي</th>
                                <th width="10%">البداية</th>
                                <th width="10%">النهاية</th>
                                <th width="10%">المنشئ</th>
                                <th width="10%" class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preliminaryExecutions as $execution)
                                <tr>
                                    <td>
                                        <div class="fw-bold small">{{ $execution->project->project_name }}</div>
                                        <div class="text-muted x-small">{{ $execution->project->form_number }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold small">{{ $execution->procedure->procedure_name }}</div>
                                    </td>
                                    <td>
                                        @if($execution->approval_status === 'pending')
                                            <span class="badge bg-warning text-dark">قيد المراجعة</span>
                                        @elseif($execution->approval_status === 'approved')
                                            <span class="badge bg-success">موافق عليه</span>
                                        @else
                                            <span class="badge bg-danger">مرفوض</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ number_format($execution->actual_amount, 2) }} ﷼</td>
                                    <td class="small">{{ $execution->actual_start_date_gregorian?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="small">{{ $execution->actual_finish_date_gregorian?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="small">{{ $execution->createdBy->name ?? 'غير متوفر' }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            @can('execution.view')
                                            <a href="{{ route('projects.execution', $execution->project) }}" 
                                               class="btn btn-outline-primary"
                                               title="عرض التفاصيل">
                                                <x-icon name="eye" size="14" />
                                            </a>
                                            @endcan
                                            @if($execution->approval_status === 'pending' && auth()->user()->hasPermission('execution.approve'))
                                                <button type="button" class="btn btn-outline-success approve-execution-btn"
                                                        data-execution-id="{{ $execution->id }}"
                                                        data-execution-type="preliminary"
                                                        data-project-id="{{ $execution->project->id }}"
                                                        title="الموافقة">
                                                    <x-icon name="check" size="14" />
                                                </button>
                                            @endif
                                            @if($execution->approval_status === 'pending' && auth()->user()->hasPermission('execution.reject'))
                                                <button type="button" class="btn btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal{{ $execution->id }}"
                                                        title="الرفض">
                                                    <x-icon name="times" size="14" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Rejection Modal --}}
                                @if($execution->approval_status === 'pending')
                                    <div class="modal fade" id="rejectModal{{ $execution->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">رفض سجل التنفيذ</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                @can('execution.reject')
                                                <form action="{{ route('projects.execution.preliminary.reject', [$execution->project, $execution]) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">سبب الرفض </label>
                                                            <textarea class="form-control" name="rejection_reason" rows="4" 
                                                                      placeholder="أدخل السبب التفصيلي..." minlength="10"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-danger btn-sm">رفض</button>
                                                    </div>
                                                </form>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-5 text-muted">
                                <x-icon name="inbox" class="mb-3 opacity-50" size="48" />
                        <p class="mb-0">لا توجد سجلات تنفيذ تحضيرية تطابق معايير البحث</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Executive Executions --}}
    @if($type === 'executive' || $type === 'all')
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 h6 fw-bold">
                <x-icon name="cogs" class="me-2 text-primary" />سجلات الأنشطة التنفيذية
                <span class="badge bg-info ms-2 rounded-pill">{{ $executiveExecutions->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                @if($executiveExecutions->count() > 0)
                    <table class="table table-bordered table-hover align-middle table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">اسم المشروع</th>
                                <th width="20%">الإجراء</th>
                                <th width="10%">الحالة</th>
                                <th width="10%">المبلغ الفعلي</th>
                                <th width="10%">البداية</th>
                                <th width="10%">النهاية</th>
                                <th width="10%">النسبة المئوية</th>
                                <th width="10%" class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($executiveExecutions as $execution)
                                <tr>
                                    <td>
                                        <div class="fw-bold small">{{ $execution->project->project_name }}</div>
                                        <div class="text-muted x-small">{{ $execution->project->form_number }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold small">{{ $execution->action->action ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        @if($execution->approval_status === 'pending')
                                            <span class="badge bg-warning text-dark">قيد المراجعة</span>
                                        @elseif($execution->approval_status === 'approved')
                                            <span class="badge bg-success">موافق عليه</span>
                                        @else
                                            <span class="badge bg-danger">مرفوض</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ number_format($execution->actual_amount, 2) }} ﷼</td>
                                    <td class="small">{{ $execution->actual_start_date_gregorian?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="small">{{ $execution->actual_finish_date_gregorian?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ number_format($execution->completion_percentage, 1) }}%</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            @can('execution.view')
                                            <a href="{{ route('projects.execution', $execution->project) }}" 
                                               class="btn btn-outline-primary"
                                               title="عرض التفاصيل">
                                                <x-icon name="eye" size="14" />
                                            </a>
                                            @endcan
                                            @if($execution->approval_status === 'pending' && auth()->user()->hasPermission('execution.approve'))
                                                <button type="button" class="btn btn-outline-success approve-execution-btn"
                                                        data-execution-id="{{ $execution->id }}"
                                                        data-execution-type="executive"
                                                        data-project-id="{{ $execution->project->id }}"
                                                        title="الموافقة">
                                                    <x-icon name="check" size="14" />
                                                </button>
                                            @endif
                                            @if($execution->approval_status === 'pending' && auth()->user()->hasPermission('execution.reject'))
                                                <button type="button" class="btn btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal{{ $execution->id }}"
                                                        title="الرفض">
                                                    <x-icon name="times" size="14" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Rejection Modal --}}
                                @if($execution->approval_status === 'pending')
                                    <div class="modal fade" id="rejectModal{{ $execution->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">رفض سجل التنفيذ</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                @can('execution.reject')
                                                <form action="{{ route('projects.execution.executive.reject', [$execution->project, $execution]) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">سبب الرفض </label>
                                                            <textarea class="form-control" name="rejection_reason" rows="4" 
                                                                      placeholder="أدخل السبب التفصيلي..." minlength="10"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-danger btn-sm">رفض</button>
                                                    </div>
                                                </form>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-5 text-muted">
                                <x-icon name="inbox" class="mb-3 opacity-50" size="48" />
                        <p class="mb-0">لا توجد سجلات تنفيذ تنفيذية تطابق معايير البحث</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Approve button functionality
    document.querySelectorAll('.approve-execution-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            Swal.fire({
                title: 'تأكيد العملية',
                text: 'هل تريد بالفعل الموافقة على هذا السجل؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم',
                cancelButtonText: 'لا',
                reverseButtons: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    const executionId = this.dataset.executionId;
                    const executionType = this.dataset.executionType;
                    const projectId = this.dataset.projectId;
                    
                    const url = `/projects/${projectId}/execution/${executionType}/${executionId}/approve`;
                    
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            flasher.success(data.message || 'تمت الموافقة بنجاح');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            flasher.error(data.message || 'حدث خطأ أثناء الموافقة');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        flasher.error('حدث خطأ أثناء معالجة الطلب');
                    });
                }
            });
        });
    });
});
</script>
@endsection

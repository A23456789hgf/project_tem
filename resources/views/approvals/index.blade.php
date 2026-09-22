@extends('layouts.app')

@section('title', 'مركز المراجعة والاعتمادات')

@section('styles')
<style>
.approval-center-header{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);border-radius:1rem;color:#fff;padding:1.75rem 2rem;margin-bottom:1.75rem;box-shadow:0 4px 20px -2px rgba(15,23,42,.15)}

@media print {
    .btn, .pagination, form, nav, .breadcrumb, .no-print {
        display: none !important;
    }
    .approval-center-header {
        background: none !important;
        color: #000 !important;
        box-shadow: none !important;
        padding: 0 0 1rem 0 !important;
        border-bottom: 2px solid #0f172a !important;
        margin-bottom: 1rem !important;
    }
    .approval-center-header h3, .approval-center-header p {
        color: #000 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
    table {
        width: 100% !important;
        font-size: 0.78rem !important;
        border: 1px solid #cbd5e1 !important;
    }
    th, td {
        border: 1px solid #cbd5e1 !important;
        padding: 6px 8px !important;
    }
    th:last-child, td:last-child {
        display: none !important;
    }
    .badge {
        border: 1px solid #94a3b8 !important;
        color: #000 !important;
        background: #fff !important;
    }
}
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
<div class="approval-center-header">
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
<div>
<h3 class="fw-bold mb-1"><i class="fas fa-clipboard-check text-info"></i> مركز المراجعة والاعتمادات</h3>
<p class="text-white-50 mb-0 small">عرض آخر حالة للمشروع ومتابعة إجراءات الاعتماد</p>
</div>
<div class="d-flex gap-2 flex-wrap">
    <button type="button" onclick="window.print()" class="btn btn-outline-light rounded-pill px-3 btn-sm fw-semibold shadow-sm">
        <i class="fas fa-print me-1"></i> طباعة القائمة
    </button>
    <a href="{{ route('projects.index') }}" class="btn btn-outline-light rounded-pill px-4 btn-sm fw-semibold"><i class="fas fa-list me-1"></i> قائمة المشاريع</a>
</div>
</div>
</div>

@if($isAdmin)
<div class="card border-0 shadow-sm rounded-4 mb-4">
<div class="card-body">
<form method="GET" action="{{ route('approvals.index') }}" class="row g-3 align-items-end">
<div class="col-md-8">
<input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="بحث عن مشروع...">
</div>
<div class="col-md-4">
<button class="btn btn-primary w-100">بحث</button>
</div>
</form>
</div>
</div>
@endif

<div class="card border-0 shadow-sm rounded-4">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
<th class="px-3">رقم المشروع</th>
<th>اسم المشروع</th>
<th>الجهة المقدمة</th>
<th>المرحلة</th>
<th>الجهة الحالية (التي وصل إليها)</th>
<th>المسؤول المعين</th>
<th>الحالة</th>
<th>آخر تحديث</th>
<th class="text-end px-3">إجراءات</th>
</tr>
</thead>
<tbody>
@forelse($approvalRecords as $record)
<tr>
<td class="px-3 font-monospace small text-muted">
    {{ $record->project?->form_number ?: ('PRJ-'.$record->project_id) }}
</td>
<td>
    <a href="{{ route('approvals.show',$record->project_id) }}" class="fw-bold text-dark text-decoration-none">
        {{ $record->project?->project_name ?? 'مشروع #'.$record->project_id }}
    </a>
</td>
<td>
    <div class="fw-semibold text-dark">
        <i class="fas fa-building text-secondary me-1 small"></i>
        {{ $record->project?->creator_entity_name ?? 'غير محدد' }}
    </div>
</td>
<td>
    <span class="badge bg-light text-dark border px-2 py-1">
        <i class="fas fa-layer-group text-muted me-1 small"></i>
        {{ $record->getPhaseLabel() }}
    </span>
</td>
<td>
    <div class="fw-bold text-primary">
        <i class="fas fa-map-marker-alt text-primary me-1 small"></i>
        {{ $record->entity?->name ?? $record->authority?->agency_name ?? 'المركز الرئيسي' }}
    </div>
</td>
<td>
    <small class="text-muted">
    @if($record->status === 'pending')
        @if($record->assignedUser)
            <span class="text-dark fw-semibold"><i class="fas fa-user-check text-success me-1"></i>{{ $record->assignedUser->name }}</span>
        @else
            <span class="text-muted"><i class="fas fa-user-clock me-1"></i>بانتظار التعيين</span>
        @endif
    @else
        <span class="text-muted"><i class="fas fa-check me-1"></i>تم إنهاء الإجراء</span>
    @endif
    </small>
</td>
<td>
@if($record->status === 'pending')
<span class="badge bg-warning text-dark">قيد المراجعة</span>
@elseif($record->status === 'approved')
<span class="badge bg-success">معتمد</span>
@elseif($record->status === 'rejected')
<span class="badge bg-danger">مرفوض</span>
@else
<span class="badge bg-secondary">{{ $record->status }}</span>
@endif
</td>
<td class="text-muted small">{{ $record->updated_at?->format('Y-m-d H:i') }}</td>
<td class="text-end px-3">
<a href="{{ route('approvals.show',$record->project_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
<i class="fas fa-eye"></i> التفاصيل
</a>
</td>
</tr>
@empty
<tr><td colspan="9" class="text-center py-5 text-muted">لا توجد مهام حالياً</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
</div>

<div class="mt-4 d-flex justify-content-center">
@if(isset($approvalRecords) && $approvalRecords instanceof \Illuminate\Pagination\LengthAwarePaginator)
{{ $approvalRecords->links() }}
@endif
</div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'مركز المراجعة والاعتمادات الموحد')

@section('styles')
<style>
.approval-center-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 1rem;
    color: #fff;
    padding: 1.75rem 2rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.15);
}

.filter-card {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

@media print {
    .btn, .pagination, form, nav, .breadcrumb, .no-print, .filter-card {
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
<div class="container-fluid px-3 px-md-4 py-3" dir="rtl">
    {{-- Header --}}
    <div class="approval-center-header">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="fas fa-clipboard-check text-info me-2"></i> مركز المراجعة والاعتمادات الموحد</h3>
                <p class="text-white-50 mb-0 small">متابعة كافة طلبات اعتماد المشاريع وطلبات الصرف والإخلاء والمبررات المالية في جدول موحد</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" onclick="window.print()" class="btn btn-outline-light rounded-pill px-3 btn-sm fw-semibold shadow-sm">
                    <i class="fas fa-print me-1"></i> طباعة القائمة
                </button>
                <a href="{{ route('projects.index') }}" class="btn btn-outline-light rounded-pill px-4 btn-sm fw-semibold">
                    <i class="fas fa-list me-1"></i> قائمة المشاريع
                </a>
            </div>
        </div>
    </div>

    {{-- Top Navigation Pills --}}
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm border">
        <li class="nav-item">
            <a class="nav-link active rounded-pill fw-bold bg-primary text-white" href="{{ route('approvals.index') }}">
                <i class="fas fa-tasks me-1"></i> المعاملات والطلبات النشطة
            </a>
        </li>
        @can('stages.create')
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-bold text-secondary" href="{{ route('approvals.configurations.index') }}">
                <i class="fas fa-cogs me-1"></i> إعداد ومسارات الاعتماد (Workflow Engine)
            </a>
        </li>
        @endcan
    </ul>

    {{-- Unified Search & Filter Section --}}
    <div class="card border-0 filter-card mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('approvals.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-bold text-secondary">البحث برقم/اسم المشروع أو الطلب</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="ابحث برقم أو اسم المشروع...">
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-bold text-secondary">نوع الطلب</label>
                    <select name="request_type" class="form-select form-select-sm">
                        <option value="">جميع أنواع الطلبات</option>
                        <option value="project_approval" {{ request('request_type') === 'project_approval' ? 'selected' : '' }}>اعتماد مشروع</option>
                        <option value="clearance_request" {{ request('request_type') === 'clearance_request' ? 'selected' : '' }}>طلب إخلاء</option>
                        <option value="payment_request" {{ request('request_type') === 'payment_request' ? 'selected' : '' }}>طلب دفعة</option>
                        <option value="clearance_payment" {{ request('request_type') === 'clearance_payment' ? 'selected' : '' }}>طلب إخلاء ودفعة</option>
                        <option value="clearance_closure" {{ request('request_type') === 'clearance_closure' ? 'selected' : '' }}>طلب إخلاء وإغلاق</option>
                        <option value="project_closure" {{ request('request_type') === 'project_closure' ? 'selected' : '' }}>طلب إغلاق مشروع</option>
                        <option value="financial_justification" {{ request('request_type') === 'financial_justification' ? 'selected' : '' }}>مبرر مالي</option>
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-bold text-secondary">الجهة المقدمة</label>
                    <select name="submitting_entity_id" class="form-select form-select-sm">
                        <option value="">جميع الجهات</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ request('submitting_entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-bold text-secondary">الجهة المسؤولة</label>
                    <select name="responsible_entity_id" class="form-select form-select-sm">
                        <option value="">جميع الجهات المسؤولة</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ request('responsible_entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-bold text-secondary">ترتيب حسب</label>
                    <select name="sort_by" class="form-select form-select-sm">
                        <option value="newest" {{ request('sort_by') === 'newest' || !request('sort_by') ? 'selected' : '' }}>الأحدث أولاً</option>
                        <option value="oldest" {{ request('sort_by') === 'oldest' ? 'selected' : '' }}>الأقدم أولاً</option>
                        <option value="project_number" {{ request('sort_by') === 'project_number' ? 'selected' : '' }}>رقم المشروع</option>
                        <option value="request_type" {{ request('sort_by') === 'request_type' ? 'selected' : '' }}>نوع الطلب</option>
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-bold text-secondary">تاريخ التقديم من</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-bold text-secondary">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 px-4 w-100 fw-semibold">
                        <i class="fas fa-filter me-1"></i> بحث وتصفية
                    </button>
                    <a href="{{ route('approvals.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 px-3 w-100 fw-semibold">
                        <i class="fas fa-undo me-1"></i> إعادة ضبط
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Unified Single Approval Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-tasks text-primary me-2"></i> قائمة طلبات الاعتماد وسير العمل الموحدة
            </h5>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                إجمالي الطلبات النشطة: {{ $approvalRecords->total() }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">رقم المشروع</th>
                            <th>اسم المشروع</th>
                            <th>نوع الطلب</th>
                            <th>الجهة المقدمة</th>
                            <th>المرحلة</th>
                            <th>الجهة المسؤولة</th>
                            <th>المسؤول المعين</th>
                            <th>الحالة</th>
                            <th>آخر تحديث</th>
                            <th class="text-end px-3">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approvalRecords as $item)
                            <tr>
                                <td class="px-3 font-monospace small text-muted">
                                    {{ $item->project_number }}
                                </td>
                                <td>
                                    <a href="{{ $item->action_url }}" class="fw-bold text-dark text-decoration-none">
                                        {{ $item->project_name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge {{ $item->request_type_badge_class }} px-2 py-1">
                                        {{ $item->request_type_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <i class="fas fa-building text-secondary me-1 small"></i>
                                        {{ $item->submitting_entity_name }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="fas fa-layer-group text-muted me-1 small"></i>
                                        {{ $item->current_stage_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">
                                        <i class="fas fa-map-marker-alt text-primary me-1 small"></i>
                                        {{ $item->responsible_entity_name }}
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        @if($item->responsible_user_name)
                                            <span class="text-dark fw-semibold">
                                                <i class="fas fa-user-check text-success me-1"></i>{{ $item->responsible_user_name }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-user-clock me-1"></i>بانتظار التعيين
                                            </span>
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    @if($item->status === 'pending')
                                        <span class="badge bg-warning text-dark">قيد المراجعة</span>
                                    @elseif($item->status === 'approved')
                                        <span class="badge bg-success">معتمد</span>
                                    @elseif($item->status === 'rejected')
                                        <span class="badge bg-danger">مرفوض</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('Y-m-d H:i') : '—' }}
                                </td>
                                <td class="text-end px-3">
                                    <a href="{{ $item->action_url }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                        <i class="fas fa-eye me-1"></i> المراجعة والتفاصيل
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                    لا توجد طلبات تنتظر مراجعتك حالياً
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Unified Server-Side Pagination Links --}}
    <div class="mt-4 d-flex justify-content-center">
        @if(isset($approvalRecords) && $approvalRecords instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $approvalRecords->links() }}
        @endif
    </div>
</div>
@endsection

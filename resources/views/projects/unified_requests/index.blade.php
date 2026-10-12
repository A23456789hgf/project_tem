@extends('layouts.app')

@section('title', 'إدارة الطلبات')

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">

    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-decoration-none text-muted">المشاريع</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">إدارة الطلبات</li>
                </ol>
            </nav>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="fas fa-file-invoice-dollar text-primary me-2"></i> إدارة الطلبات الموحدة للمشاريع
            </h2>
        </div>
        <div>
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('project_requests.create') || auth()->user()->hasPermission('payments.create') || auth()->user()->hasPermission('clearances.create') || auth()->user()->hasPermission('disbursements.create'))
                <a href="{{ route('projects.requests.create') }}" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm fw-bold">
                    <i class="fas fa-plus-circle me-1"></i> إنشاء طلب جديد
                </a>
            @endif
        </div>
    </div>

    {{-- Summary Statistics (KPI Cards) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold">إجمالي الطلبات</div>
                    <div class="h4 fw-bold text-dark mb-0 mt-1">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-secondary border-4">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold">المسودات</div>
                    <div class="h4 fw-bold text-secondary mb-0 mt-1">{{ number_format($stats['draft'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-info border-4">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold">مسودات مؤكدة</div>
                    <div class="h4 fw-bold text-info mb-0 mt-1">{{ number_format($stats['confirmed'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold">قيد المراجعة</div>
                    <div class="h4 fw-bold text-warning mb-0 mt-1">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold">معتمدة</div>
                    <div class="h4 fw-bold text-success mb-0 mt-1">{{ number_format($stats['approved']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold">مرفوضة</div>
                    <div class="h4 fw-bold text-danger mb-0 mt-1">{{ number_format($stats['rejected']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('projects.requests.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">بحث برقم الطلب أو المشروع</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm rounded-3"
                           placeholder="رقم الطلب أو اسم / رمز المشروع...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">نوع الطلب</label>
                    <select name="type" class="form-select form-select-sm rounded-3">
                        <option value="">جميع الأنواع</option>
                        <option value="clearance_payment" {{ request('type') === 'clearance_payment' ? 'selected' : '' }}>إخلاء وطلب دفعة</option>
                        <option value="clearance_closure" {{ request('type') === 'clearance_closure' ? 'selected' : '' }}>إخلاء وإغلاق المشروع</option>
                        <option value="clearance" {{ request('type') === 'clearance' ? 'selected' : '' }}>إخلاء</option>
                        <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>طلب دفعة</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">حالة الطلب</label>
                    <select name="status" class="form-select form-select-sm rounded-3">
                        <option value="">جميع الحالات</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>مسودة مؤكدة</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد المراجعة والاعتماد</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>معتمد</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">الجهة المقدمة</label>
                    <select name="submitting_entity_id" id="filterEntitySelect" class="form-select form-select-sm rounded-3">
                        <option value="">كافة الجهات</option>
                        @foreach($availableEntities as $ent)
                            @php $val = $ent['type'] . ':' . $ent['id']; @endphp
                            <option value="{{ $val }}" {{ request('submitting_entity_id') == $val ? 'selected' : '' }}>
                                {{ $ent['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 flex-fill">
                        <i class="fas fa-search me-1"></i> تصفية
                    </button>
                    @if(request()->hasAny(['search', 'type', 'status', 'submitting_entity_id', 'submitting_user_id', 'project_id', 'date_from', 'date_to']))
                        <a href="{{ route('projects.requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-3" title="إعادة تعيين الفلاتر">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Main Requests Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fas fa-list text-primary me-2"></i> سجل الطلبات الموحدة
            </h5>
            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                عدد النتائج: {{ number_format($unifiedRequests->total()) }}
            </span>
        </div>
        <div class="card-body p-0">
            @if($unifiedRequests->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-file-invoice fs-1 d-block mb-3 opacity-50"></i>
                    <h6 class="fw-bold">لا توجد طلبات مطابقة لمعايير البحث.</h6>
                    <p class="small text-muted mb-0">يمكنك إنشاء طلب جديد من خلال زر "إنشاء طلب جديد" بالأعلى.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>رقم الطلب</th>
                                <th>نوع الطلب</th>
                                <th>المشروع</th>
                                <th>رقم المشروع</th>
                                <th class="bg-primary-subtle text-primary-emphasis">الجهة المقدمة</th>
                                <th class="bg-primary-subtle text-primary-emphasis">المستخدم مقدم الطلب</th>
                                <th>تاريخ الطلب</th>
                                <th>المبلغ المطلوب</th>
                                <th>حالة الطلب</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unifiedRequests as $req)
                                <tr>
                                    <td>
                                        <a href="{{ route('projects.requests.show', $req) }}" class="fw-bold text-decoration-none text-primary">
                                            {{ $req->request_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge {{ $req->typeBadgeClass() }} px-2 py-1 rounded-pill small">
                                            {{ $req->typeLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(auth()->user()->hasPermission('disbursements.view', $req->project))
                                            <a href="{{ route('projects.disbursement-plan.show', $req->project) }}"
                                               class="text-decoration-none fw-semibold text-dark small" title="{{ $req->project?->project_name }}">
                                                {{ Str::limit($req->project?->project_name, 35) }}
                                            </a>
                                        @else
                                            {{ Str::limit($req->project?->project_name, 35) }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border rounded-pill">
                                            {{ $req->project?->form_number ?: ('#'.$req->project_id) }}
                                        </span>
                                    </td>
                                    {{-- Submitting Entity (Mandatory Column) --}}
                                    <td class="fw-semibold text-dark">
                                        <i class="fas fa-building text-secondary me-1 small opacity-75"></i>
                                        {{ $req->submitting_organization_name }}
                                    </td>
                                    {{-- Submitting User (Mandatory Column) --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center me-2"
                                                 style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                <i class="fas fa-user text-muted"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark small">{{ $req->submitting_user_name }}</div>
                                                @if($req->created_by && $req->created_by !== $req->submitting_user_id)
                                                    <div class="text-muted" style="font-size: 0.7rem;">بواسطة: {{ $req->creator?->name }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small text-muted">{{ $req->request_date?->format('Y-m-d') }}</td>
                                    {{-- Requested Amount (Placeholder for pure clearance) --}}
                                    <td>
                                        @if($req->hasPaymentComponent())
                                            <span class="fw-bold text-success">
                                                {{ number_format($req->requested_payment_amount, 2) }} ريال
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border">
                                                — (إخلاء عهدة)
                                            </span>
                                        @endif
                                    </td>
                                    {{-- Status & Approval Stage --}}
                                    <td>
                                        <div>
                                            <span class="badge {{ $req->statusBadgeClass() }} px-3 py-1 rounded-pill">
                                                {{ $req->statusLabel() }}
                                            </span>
                                        </div>
                                        @if($req->isPending())
                                            <div class="text-muted mt-1 small" style="font-size: 0.72rem;">
                                                <i class="fas fa-layer-group text-primary me-1"></i>
                                                {{ $req->currentStageName() }}
                                            </div>
                                        @endif
                                    </td>
                                    {{-- Actions --}}
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('project_requests.view', $req->project) || auth()->user()->hasPermission('payments.view', $req->project) || auth()->user()->hasPermission('clearances.view', $req->project) || auth()->user()->hasPermission('disbursements.view', $req->project))
                                                <a href="{{ route('projects.requests.show', $req) }}" class="btn btn-outline-primary" title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endif
                                            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('project_requests.print', $req->project) || auth()->user()->hasPermission('payments.view', $req->project) || auth()->user()->hasPermission('clearances.view', $req->project))
                                                <a href="{{ route('projects.requests.print', $req) }}"
                                                   class="btn btn-outline-secondary" target="_blank" title="طباعة الوثيقة">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            @endif
                                            @if(($req->isDraft() || $req->isPending() || $req->status === 'requires_action') && (auth()->user()->isAdmin() || auth()->id() === $req->created_by || auth()->user()->hasPermission('project_requests.edit', $req->project) || auth()->user()->hasPermission('payments.create', $req->project) || auth()->user()->hasPermission('clearances.create', $req->project)))
                                                <a href="{{ route('projects.requests.edit', $req) }}"
                                                   class="btn btn-outline-warning" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            @if($req->isDraft() && (auth()->user()->isAdmin() || auth()->id() === $req->created_by || auth()->user()->hasPermission('project_requests.edit', $req->project) || auth()->user()->hasPermission('payments.create', $req->project) || auth()->user()->hasPermission('clearances.create', $req->project)))
                                                <form action="{{ route('projects.requests.confirm-draft', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من تأكيد هذه المسودة؟')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-warning" title="تأكيد المسودة">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if($req->isConfirmed() && (auth()->user()->isAdmin() || auth()->id() === $req->created_by || auth()->user()->hasPermission('project_requests.submit', $req->project) || auth()->user()->hasPermission('project_requests.edit', $req->project) || auth()->user()->hasPermission('payments.create', $req->project) || auth()->user()->hasPermission('clearances.create', $req->project)))
                                                <form action="{{ route('projects.requests.submit', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إرسال هذا الطلب للاعتماد؟')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" title="إرسال للاعتماد">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($unifiedRequests->hasPages())
                    <div class="p-3 border-top d-flex justify-content-center">
                        {{ $unifiedRequests->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

</div>
@endsection

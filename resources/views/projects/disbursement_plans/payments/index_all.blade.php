@extends('layouts.app')

@section('title', 'إدارة طلبات الصرف')

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">

    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-decoration-none text-muted">المشاريع</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">طلبات الصرف</li>
                </ol>
            </nav>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="fas fa-hand-holding-usd text-primary me-2"></i> إدارة طلبات الصرف
            </h2>
        </div>
    </div>

    {{-- Quick Action: Select Execution Project to Request Payment --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-primary border-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-7 col-md-6">
                    <label for="selectPaymentProject" class="form-label fw-bold text-dark mb-1">
                        <i class="fas fa-hand-holding-usd text-primary me-2"></i> اختيار مشروع دخل التنفيذ لتقديم طلب صرف:
                    </label>
                    <div class="text-muted small mb-2">اختر المشروع المنفذ للبدء في تقديم طلب صرف دفعة جديدة أو متابعة دفعاته.</div>
                    <select id="selectPaymentProject" class="form-select" data-placeholder="-- ابحث عن المشروع بالاسم أو الرمز للبدء --">
                        <option value="">-- ابحث عن المشروع بالاسم أو الرمز للبدء --</option>
                        @foreach($executionProjects as $p)
                            @php
                                $plan = $p->disbursementPlan;
                                $eligibleDetail = null;
                                if ($plan && $plan->relationLoaded('details')) {
                                    foreach ($plan->details as $idx => $dt) {
                                        $prevDt = $idx > 0 ? $plan->details[$idx - 1] : null;
                                        if ($dt->isPlanned() && $dt->sequence_number > 1 && $prevDt && $prevDt->isCleared()) {
                                            $eligibleDetail = $dt;
                                            break;
                                        }
                                    }
                                }
                                $createUrl = $eligibleDetail 
                                    ? route('projects.disbursement-plan.payments.create', [$p, $eligibleDetail]) 
                                    : route('projects.disbursement-plan.show', $p);
                                $pCode = $p->form_number ?: ('#'.$p->id);
                                $eligibleLabel = $eligibleDetail ? ('متاح طلب الدفعة ' . $eligibleDetail->sequence_number) : 'عرض جدول الخطة والدفعات';
                                $canCreatePayment = auth()->user()->hasPermission('payments.create', $p) || auth()->user()->hasPermission('disbursements.request', $p);
                                $canViewPlan = auth()->user()->hasPermission('disbursements.view', $p);
                            @endphp
                            @if(($eligibleDetail && $canCreatePayment) || (!$eligibleDetail && $canViewPlan))
                            <option value="{{ $p->id }}" 
                                    data-url="{{ $createUrl }}"
                                    data-has-eligible="{{ $eligibleDetail ? '1' : '0' }}"
                                    data-installment-num="{{ $eligibleDetail?->sequence_number }}"
                                    data-name="{{ $p->project_name }}"
                                    data-code="{{ $pCode }}"
                                    data-label="{{ $eligibleLabel }}">
                                {{ $p->project_name }} ({{ $pCode }}) - [{{ $eligibleLabel }}]
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-5 col-md-6 d-flex align-items-end justify-content-md-end pt-md-3">
                    <div id="paymentActionBox" class="w-100 text-md-end" style="display: none;">
                        <a id="btnPaymentAction" href="#" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm fw-bold">
                            <i class="fas fa-plus-circle me-1"></i> <span>تقديم طلب صرف</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">إجمالي الطلبات</div>
                        <div class="h3 fw-bold text-dark mb-0 mt-1">{{ number_format($stats['total']) }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;background:rgba(79,70,229,.1);color:#4f46e5">
                        <i class="fas fa-file-invoice-dollar fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">قيد المراجعة</div>
                        <div class="h3 fw-bold text-warning mb-0 mt-1">{{ number_format($stats['pending']) }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;background:rgba(245,158,11,.1);color:#d97706">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">مُعتمدة</div>
                        <div class="h3 fw-bold text-success mb-0 mt-1">{{ number_format($stats['approved']) }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;background:rgba(16,185,129,.1);color:#059669">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">مرفوضة</div>
                        <div class="h3 fw-bold text-danger mb-0 mt-1">{{ number_format($stats['rejected']) }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;background:rgba(239,68,68,.1);color:#dc2626">
                        <i class="fas fa-times-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">بحث بالمشروع</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm rounded-3"
                           placeholder="اسم المشروع أو رقم النموذج...">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">الحالة</label>
                    <select name="status" class="form-select form-select-sm rounded-3">
                        <option value="">الكل</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>مُعتمد</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 flex-fill">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('projects.payment-requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-3">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fas fa-list text-primary me-2"></i> قائمة طلبات الصرف
            </h5>
        </div>
        <div class="card-body p-0">
            @if($paymentRequests->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-inbox fs-1 d-block mb-3 opacity-50"></i>
                    <h6>لا توجد طلبات صرف مطابقة للبحث.</h6>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>رقم الطلب</th>
                                <th>المشروع</th>
                                <th>الدفعة</th>
                                <th>المبلغ المطلوب</th>
                                <th>الحالة</th>
                                <th>مقدم الطلب</th>
                                <th>تاريخ التقديم</th>
                                <th class="text-center">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentRequests as $req)
                                <tr>
                                    <td><strong>{{ $req->request_number }}</strong></td>
                                    <td>
                                        <a href="{{ route('projects.disbursement-plan.show', $req->project) }}"
                                           class="text-decoration-none fw-semibold text-dark small">
                                            {{ $req->project?->project_name }}
                                        </a>
                                        @if($req->project?->form_number)
                                            <div class="text-muted" style="font-size:0.75rem">{{ $req->project->form_number }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            دفعة {{ $req->installment?->sequence_number }}
                                        </span>
                                    </td>
                                    <td class="fw-bold text-primary">{{ number_format($req->requested_amount, 2) }} ريال</td>
                                    <td>
                                        <span class="badge {{ $req->statusBadgeClass() }} px-3 py-1 rounded-pill">
                                            {{ $req->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $req->requestedBy?->name ?? '—' }}</td>
                                    <td class="small text-muted">{{ $req->created_at->format('Y-m-d') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('projects.disbursement-plan.payments.show', [$req->project, $req]) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="fas fa-eye me-1"></i> تفاصيل
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($paymentRequests->hasPages())
                    <div class="p-3 border-top d-flex justify-content-center">
                        {{ $paymentRequests->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('selectPaymentProject');
    const box = document.getElementById('paymentActionBox');
    const btn = document.getElementById('btnPaymentAction');
    if (!select || !box || !btn) return;

    const btnSpan = btn.querySelector('span');

    function updateAction() {
        const selected = select.options[select.selectedIndex];
        if (!selected || !selected.value) {
            $(box).slideUp(200);
            return;
        }

        const url = selected.getAttribute('data-url');
        const hasEligible = selected.getAttribute('data-has-eligible') === '1';
        const installmentNum = selected.getAttribute('data-installment-num');

        btn.setAttribute('href', url);
        if (hasEligible && installmentNum) {
            btn.className = 'btn btn-primary btn-lg rounded-pill px-4 shadow-sm fw-bold';
            btnSpan.textContent = 'تقديم طلب صرف للدفعة ' + installmentNum;
        } else {
            btn.className = 'btn btn-outline-primary btn-lg rounded-pill px-4 shadow-sm fw-bold';
            btnSpan.textContent = 'عرض جدول الدفعات والطلب';
        }
        $(box).slideDown(200);
    }

    function formatPaymentProjectOption(item) {
        if (!item.id) return item.text;
        const el = $(item.element);
        const name = el.data('name') || item.text;
        const code = el.data('code');
        const label = el.data('label');
        const hasEligible = el.data('has-eligible') === '1' || el.data('has-eligible') === 1;

        const badgeClass = hasEligible ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border';

        return $(`
            <div class="d-flex align-items-center justify-content-between py-1 text-wrap" style="text-align: right; width: 100%;">
                <div>
                    <i class="fas fa-folder-open text-primary opacity-50 me-1 small"></i>
                    <strong class="text-dark">${name}</strong>
                    ${code ? `<span class="badge bg-light text-secondary border rounded-pill ms-2 small">${code}</span>` : ''}
                </div>
                ${label ? `<span class="badge ${badgeClass} rounded-pill ms-2 flex-shrink-0 small">${label}</span>` : ''}
            </div>
        `);
    }

    function formatPaymentProjectSelection(item) {
        if (!item.id) return item.text;
        const el = $(item.element);
        const name = el.data('name') || item.text;
        const code = el.data('code');
        const label = el.data('label');
        return `${name} ${code ? '(' + code + ')' : ''} ${label ? '[' + label + ']' : ''}`;
    }

    if (window.jQuery && typeof $(select).select2 === 'function') {
        $(select).select2({
            theme: 'bootstrap-5',
            placeholder: $(select).data('placeholder') || '-- ابحث عن المشروع بالاسم أو الرمز للبدء --',
            allowClear: true,
            dir: 'rtl',
            width: '100%',
            templateResult: formatPaymentProjectOption,
            templateSelection: formatPaymentProjectSelection
        }).on('change', updateAction);
    } else {
        select.addEventListener('change', updateAction);
    }
});
</script>
@endpush


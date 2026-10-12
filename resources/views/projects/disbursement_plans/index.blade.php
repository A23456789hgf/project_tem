@extends('layouts.app')

@section('title', 'خطط ودفعات الصرف والإخلاء')

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    {{-- Breadcrumbs & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-decoration-none text-muted">المشاريع</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">خطط ودفعات الصرف</li>
                </ol>
            </nav>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="fas fa-list-ol text-primary me-2"></i> إدارة خطط ودفعات الصرف والإخلاء
            </h2>
        </div>
    </div>

    {{-- Quick Action: Select Execution Project --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-primary border-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-7 col-md-6">
                    <label for="selectDisbursementProject" class="form-label fw-bold text-dark mb-1">
                        <i class="fas fa-project-diagram text-primary me-2"></i> اختيار مشروع دخل مرحلة التنفيذ أو الصرف:
                    </label>
                    <div class="text-muted small mb-2">اختر المشروع لإعداد أو متابعة خطة الصرف والدفعات المجدولة له.</div>
                        <select id="selectDisbursementProject" class="form-select" data-placeholder="-- ابحث عن المشروع بالاسم أو الرمز للبدء --">
                        <option value="">-- ابحث عن المشروع بالاسم أو الرمز للبدء --</option>
                        @foreach($executionProjects as $p)
                            @php
                                $hasPlan = $p->disbursementPlan !== null;
                                $targetUrl = $hasPlan ? route('projects.disbursement-plan.show', $p) : route('projects.disbursement-plan.create', $p);
                                $canOpenPlan = auth()->user()->hasPermission($hasPlan ? 'disbursements.view' : 'disbursements.create', $p);
                                $planStatus = $hasPlan ? ($p->disbursementPlan->statusLabel() ?? 'مسجلة') : 'بانتظار إعداد الخطة';
                                $pCode = $p->form_number ?: ('#'.$p->id);
                            @endphp
                            @if($canOpenPlan)
                            <option value="{{ $p->id }}" 
                                    data-url="{{ $targetUrl }}" 
                                    data-has-plan="{{ $hasPlan ? '1' : '0' }}"
                                    data-plan-status="{{ $planStatus }}"
                                    data-name="{{ $p->project_name }}"
                                    data-code="{{ $pCode }}">
                                {{ $p->project_name }} ({{ $pCode }}) - [{{ $planStatus }}]
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-5 col-md-6 d-flex align-items-end justify-content-md-end pt-md-3">
                    <div id="disbursementActionBox" class="w-100 text-md-end" style="display: none;">
                        <a id="btnDisbursementAction" href="#" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm fw-bold">
                            <i class="fas fa-plus-circle me-1"></i> <span>إعداد خطة الصرف</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards Grid --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">مشاريع مرحلة الصرف</div>
                        <div class="h3 fw-bold text-dark mb-0 mt-1">{{ number_format($stats['total_projects']) }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(30, 58, 95, 0.1); color: #1e3a5f;">
                        <i class="fas fa-project-diagram fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">بانتظار إعداد الخطة</div>
                        <div class="h3 fw-bold text-warning mb-0 mt-1">{{ number_format($stats['awaiting_plan_count']) }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.1); color: #d97706;">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">خطط صرف معتمدة</div>
                        <div class="h3 fw-bold text-success mb-0 mt-1">{{ number_format($stats['approved_plans_count']) }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); color: #059669;">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">إجمالي المنصرف الفعلي</div>
                        <div class="h4 fw-bold text-primary mb-0 mt-1">{{ number_format($stats['total_disbursed'], 2) }} <small class="fs-6 text-muted">ريال</small></div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(201, 169, 97, 0.15); color: #b89850;">
                        <i class="fas fa-coins fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">طلبات معلقة للتدقيق</div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-info text-dark" title="طلبات صرف قيد الانتظار">
                                <i class="fas fa-hand-holding-usd me-1"></i> {{ $stats['pending_payments_count'] }} صرف
                            </span>
                            <span class="badge bg-secondary text-white" title="طلبات إخلاء قيد الانتظار">
                                <i class="fas fa-file-invoice-dollar me-1"></i> {{ $stats['pending_clearances_count'] }} إخلاء
                            </span>
                        </div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(59, 130, 246, 0.1); color: #2563eb;">
                        <i class="fas fa-tasks fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter and Search Bar --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('projects.disbursement-plans.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" 
                               placeholder="بحث باسم المشروع أو رمزه..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <select name="plan_status" class="form-select bg-light">
                        <option value="">-- كل حالات خطط الصرف --</option>
                        <option value="awaiting_plan" {{ request('plan_status') == 'awaiting_plan' ? 'selected' : '' }}>بانتظار إعداد الخطة</option>
                        <option value="draft" {{ request('plan_status') == 'draft' ? 'selected' : '' }}>مسودة خطة الصرف</option>
                        <option value="approved" {{ request('plan_status') == 'approved' ? 'selected' : '' }}>خطة معتمدة (جاري الصرف)</option>
                        <option value="completed" {{ request('plan_status') == 'completed' ? 'selected' : '' }}>خطة مكتملة (تم إخلاء كامل الدفعات)</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if(request()->hasAny(['search', 'plan_status']))
                        <a href="{{ route('projects.disbursement-plans.index') }}" class="btn btn-light border text-muted" title="إلغاء التصفية">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Projects Table Card --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-list-ul text-primary me-2"></i> قائمة مشاريع دورة الصرف
            </h5>
            <span class="badge bg-light text-dark border">
                إجمالي النتائج: {{ $projects->total() }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th class="text-center" style="width: 70px;">#</th>
                        <th>اسم المشروع</th>
                        <th>الجهة المقدمة</th>
                        <th class="text-end">التكلفة الكلية</th>
                        <th class="text-center">حالة الخطة</th>
                        <th>المنصرف الفعلي</th>
                        <th class="text-center">الدفعة النشطة</th>
                        <th class="text-center" style="width: 220px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        @php
                            $plan = $project->disbursementPlan;
                            $totalCost = (float) ($project->cost->total_cost ?? 0);
                            $disbursed = (float) ($plan->total_disbursed_amount ?? 0);
                            $pctDisbursed = $totalCost > 0 ? min(100, round(($disbursed / $totalCost) * 100, 1)) : 0;
                            $activeInstallment = $project->active_disbursed_installment;
                        @endphp
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $project->id }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $project->project_name }}</div>
                                <div class="text-muted small">
                                    <span class="badge bg-light text-secondary border">
                                        {{ $project->status_label ?? $project->status }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark">{{ $project->creatorEntity->name ?? 'غير محدد' }}</div>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                {{ number_format($totalCost, 2) }} <small class="text-muted">ريال</small>
                            </td>
                            <td class="text-center">
                                @if(!$plan)
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                        <i class="fas fa-exclamation-circle me-1"></i> بانتظار إعداد الخطة
                                    </span>
                                @elseif($plan->status === 'draft')
                                    <span class="badge bg-info text-dark px-3 py-2 rounded-pill">
                                        <i class="fas fa-edit me-1"></i> مسودة خطة
                                    </span>
                                @elseif($plan->status === 'approved')
                                    <span class="badge bg-success text-white px-3 py-2 rounded-pill">
                                        <i class="fas fa-check-circle me-1"></i> معتمدة (جاري الصرف)
                                    </span>
                                @elseif($plan->status === 'completed')
                                    <span class="badge bg-primary text-white px-3 py-2 rounded-pill">
                                        <i class="fas fa-flag-checkered me-1"></i> مكتملة الإخلاء
                                    </span>
                                @else
                                    <span class="badge bg-secondary text-white px-3 py-2 rounded-pill">
                                        {{ $plan->status }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-bold text-dark">{{ number_format($disbursed, 2) }}</span>
                                    <span class="text-muted">{{ $pctDisbursed }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctDisbursed }}%;" 
                                         aria-valuenow="{{ $pctDisbursed }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($activeInstallment)
                                    <span class="badge bg-light text-dark border">
                                        دفعة #{{ $activeInstallment->sequence_number }}
                                        <span class="badge bg-primary ms-1">{{ $activeInstallment->status }}</span>
                                    </span>
                                @elseif($plan && $plan->status === 'completed')
                                    <span class="text-success small fw-bold">
                                        <i class="fas fa-check-double me-1"></i> تم إخلاء كافة الدفعات
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    @if(!$plan || $plan->status === 'draft')
                                        @if(auth()->user()->hasPermission('disbursements.create', $project))
                                            <a href="{{ route('projects.disbursement-plan.create', $project) }}" 
                                               class="btn btn-warning text-dark fw-bold" title="إعداد / تعديل خطة الصرف">
                                                <i class="fas fa-pencil-alt me-1"></i> إعداد الخطة
                                            </a>
                                        @endif
                                    @else
                                        @if(auth()->user()->hasPermission('disbursements.view', $project))
                                            <a href="{{ route('projects.disbursement-plan.show', $project) }}" 
                                               class="btn btn-outline-primary" title="عرض خطة الصرف وجدول الدفعات">
                                                <i class="fas fa-eye me-1"></i> الخطة
                                            </a>
                                        @endif
                                    @endif

                                    @if($plan && in_array($plan->status, ['approved', 'completed']))
                                        @if(auth()->user()->hasPermission('payments.view', $project))
                                            <a href="{{ route('projects.disbursement-plan.payments.index', $project) }}" 
                                               class="btn btn-outline-info" title="طلبات الصرف">
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </a>
                                        @endif

                                        @if(auth()->user()->hasPermission('clearances.view', $project))
                                            <a href="{{ route('projects.disbursement-plan.clearances.index', $project) }}" 
                                               class="btn btn-outline-secondary" title="طلبات الإخلاء">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                <div class="h6 fw-semibold">لا توجد مشاريع في مرحلة الصرف حالياً</div>
                                <p class="small text-muted mb-0">المشاريع التي تجتاز الاعتماد النهائي ستظهر تلقائياً هنا لإعداد واعتماد خطط الصرف.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-center">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('selectDisbursementProject');
    const box = document.getElementById('disbursementActionBox');
    const btn = document.getElementById('btnDisbursementAction');
    if (!select || !box || !btn) return;

    const btnSpan = btn.querySelector('span');
    const btnIcon = btn.querySelector('i');

    function updateAction() {
        const selected = select.options[select.selectedIndex];
        if (!selected || !selected.value) {
            $(box).slideUp(200);
            return;
        }

        const url = selected.getAttribute('data-url');
        const hasPlan = selected.getAttribute('data-has-plan') === '1';

        btn.setAttribute('href', url);
        if (hasPlan) {
            btn.className = 'btn btn-outline-primary btn-lg rounded-pill px-4 shadow-sm fw-bold';
            btnSpan.textContent = 'عرض ومتابعة خطة الصرف';
            btnIcon.className = 'fas fa-eye me-1';
        } else {
            btn.className = 'btn btn-success btn-lg rounded-pill px-4 shadow-sm fw-bold';
            btnSpan.textContent = 'إعداد خطة صرف جديدة';
            btnIcon.className = 'fas fa-plus-circle me-1';
        }
        $(box).slideDown(200);
    }

    function formatProjectSelectOption(item) {
        if (!item.id) return item.text;
        const el = $(item.element);
        const name = el.data('name') || item.text;
        const code = el.data('code');
        const planStatus = el.data('plan-status');
        const hasPlan = el.data('has-plan') === '1' || el.data('has-plan') === 1;

        const badgeClass = hasPlan ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';

        return $(`
            <div class="d-flex align-items-center justify-content-between py-1 text-wrap" style="text-align: right; width: 100%;">
                <div>
                    <i class="fas fa-folder-open text-primary opacity-50 me-1 small"></i>
                    <strong class="text-dark">${name}</strong>
                    ${code ? `<span class="badge bg-light text-secondary border rounded-pill ms-2 small">${code}</span>` : ''}
                </div>
                ${planStatus ? `<span class="badge ${badgeClass} rounded-pill ms-2 flex-shrink-0 small">${planStatus}</span>` : ''}
            </div>
        `);
    }

    function formatProjectSelectSelection(item) {
        if (!item.id) return item.text;
        const el = $(item.element);
        const name = el.data('name') || item.text;
        const code = el.data('code');
        const planStatus = el.data('plan-status');
        return `${name} ${code ? '(' + code + ')' : ''} ${planStatus ? '[' + planStatus + ']' : ''}`;
    }

    if (window.jQuery && typeof $(select).select2 === 'function') {
        $(select).select2({
            theme: 'bootstrap-5',
            placeholder: $(select).data('placeholder') || '-- ابحث عن المشروع للبدء --',
            allowClear: true,
            dir: 'rtl',
            width: '100%',
            templateResult: formatProjectSelectOption,
            templateSelection: formatProjectSelectSelection
        }).on('change', updateAction);
    } else {
        select.addEventListener('change', updateAction);
    }
});
</script>
@endpush

@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted text-decoration-none">المشاريع</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}" class="text-muted text-decoration-none">{{ $project->project_name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.disbursement-plan.show', $project) }}" class="text-muted text-decoration-none">خطة الصرف</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">تقديم طلب إخلاء مالي للدفعة {{ $detail->sequence_number }}</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-warning text-dark py-3 px-4 rounded-top-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-clipboard-check me-2"></i> إعداد وتقديم طلب إخلاء مالي للدفعة رقم {{ $detail->sequence_number }}
                    </h5>
                    <small class="text-dark-50">حدد سجلات الإنجاز الفعلي المنفذة المراد إخلاؤها مقابل هذه الدفعة</small>
                </div>

                <form action="{{ route('projects.disbursement-plan.clearances.store', [$project, $detail]) }}" method="POST" enctype="multipart/form-data" id="clearanceForm">
                    @csrf
                    <div class="card-body p-4">

                        {{-- Installment Info Card --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-4 border">
                                    <div class="small text-muted mb-1">مبلغ الدفعة المصروفة</div>
                                    <div class="fs-3 fw-bold text-dark">{{ number_format($detail->disbursed_amount, 2) }} <small class="fs-6">ريال</small></div>
                                    <div class="small text-muted mt-1">{{ $arabicDisbursed }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-4 border">
                                    <div class="small text-muted mb-1">إجمالي المبلغ المحدد للإخلاء في هذا الطلب</div>
                                    <div class="fs-3 fw-bold text-success" id="selectedClearTotal">0.00 <small class="fs-6">ريال</small></div>
                                    <div class="small text-muted mt-1">يُحسب تلقائياً بناءً على السجلات المختارة أدناه</div>
                                </div>
                            </div>
                        </div>

                        {{-- Executions Checklist Table --}}
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="fas fa-tasks text-primary me-1"></i> سجلات الإنجاز المالي والفني المتاحة للإخلاء:
                        </h6>

                        @if($availableProjectExecutions->isEmpty() && $availablePreliminaryExecutions->isEmpty())
                            <div class="alert alert-warning rounded-4 p-4 text-center">
                                <i class="fas fa-exclamation-triangle fs-3 d-block mb-2 text-warning"></i>
                                <strong>لا توجد سجلات إنجاز جديدة غير مخلاة لهذا المشروع.</strong>
                                <p class="small text-muted mb-0 mt-1">يرجى تسجيل إنجازات الأنشطة التنفيذية أو التمهيدية في شاشة التنفيذ أولاً قبل تقديم طلب الإخلاء.</p>
                            </div>
                        @else
                            <div class="table-responsive mb-4 border rounded-3">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">
                                                <input type="checkbox" class="form-check-input" id="selectAllExecutions" checked>
                                            </th>
                                            <th>نوع النشاط</th>
                                            <th>اسم النشاط / الإجراء</th>
                                            <th class="text-center">نسبة الإنجاز</th>
                                            <th class="text-end">المبلغ المنصرف</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Executive Executions --}}
                                        @foreach($availableProjectExecutions as $pe)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" name="project_execution_ids[]"
                                                           value="{{ $pe->id }}"
                                                           class="form-check-input exec-checkbox"
                                                           data-amount="{{ $pe->amount_spent }}"
                                                           data-pct="{{ $pe->completion_percentage }}"
                                                           checked>
                                                </td>
                                                <td><span class="badge bg-primary-subtle text-primary border">تنفيذي</span></td>
                                                <td>
                                                    <strong>{{ $pe->action?->action_name ?? 'إجراء تنفيذي #' . $pe->id }}</strong>
                                                    @if($pe->notes)<small class="d-block text-muted">{{ Str::limit($pe->notes, 60) }}</small>@endif
                                                </td>
                                                <td class="text-center fw-bold">{{ $pe->completion_percentage }}%</td>
                                                <td class="text-end fw-bold text-success">{{ number_format($pe->amount_spent, 2) }} ريال</td>
                                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $pe->status }}</span></td>
                                            </tr>
                                        @endforeach

                                        {{-- Preliminary Executions --}}
                                        @foreach($availablePreliminaryExecutions as $ppe)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" name="preliminary_execution_ids[]"
                                                           value="{{ $ppe->id }}"
                                                           class="form-check-input exec-checkbox"
                                                           data-amount="{{ $ppe->amount_spent }}"
                                                           data-pct="{{ $ppe->completion_percentage }}"
                                                           checked>
                                                </td>
                                                <td><span class="badge bg-info-subtle text-info border">تمهيدي</span></td>
                                                <td>
                                                    <strong>{{ $ppe->procedure?->procedure_name ?? 'إجراء تمهيدي #' . $ppe->id }}</strong>
                                                    @if($ppe->notes)<small class="d-block text-muted">{{ Str::limit($ppe->notes, 60) }}</small>@endif
                                                </td>
                                                <td class="text-center fw-bold">{{ $ppe->completion_percentage }}%</td>
                                                <td class="text-end fw-bold text-success">{{ number_format($ppe->amount_spent, 2) }} ريال</td>
                                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $ppe->status }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Notes & Attachments --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small">ملاحظات طلب الإخلاء (اختياري):</label>
                                <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="أدخل أي ملاحظات توضيحية حول الإنجاز والوثائق المرفقة..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small">المرفقات ووثائق الإخلاء (اختياري):</label>
                                <input type="file" name="attachment" class="form-control rounded-3">
                                <div class="form-text small">سندات، فواتير، تقارير إنجاز بصيغة PDF أو Word أو صور (الحد الأقصى 10 ميجابايت).</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('projects.disbursement-plan.show', $project) }}" class="btn btn-outline-secondary rounded-pill px-4">
                                إلغاء والعودة
                            </a>
                            <button type="submit" class="btn btn-warning rounded-pill px-5 fw-bold shadow-sm" id="submitClearanceBtn">
                                <i class="fas fa-paper-plane me-1"></i> تقديم طلب الإخلاء
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.exec-checkbox');
    const selectAll = document.getElementById('selectAllExecutions');
    const totalEl = document.getElementById('selectedClearTotal');
    const submitBtn = document.getElementById('submitClearanceBtn');

    function calculateTotal() {
        let sum = 0.0;
        let count = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                sum += parseFloat(cb.getAttribute('data-amount')) || 0.0;
                count++;
            }
        });

        totalEl.innerHTML = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(sum) + ' <small class="fs-6">ريال</small>';
        if (submitBtn) {
            submitBtn.disabled = (count === 0 || sum <= 0);
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            calculateTotal();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', calculateTotal);
    });

    calculateTotal();
});
</script>
@endsection


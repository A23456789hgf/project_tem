@extends('layouts.app')

@section('title', 'تعديل الطلب | ' . $unifiedRequest->request_number)

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">

    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.requests.index') }}" class="text-decoration-none text-muted">إدارة الطلبات</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">تعديل الطلب</li>
                </ol>
            </nav>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="fas fa-edit text-warning me-2"></i> تعديل بيانات الطلب: {{ $unifiedRequest->request_number }}
            </h2>
        </div>
        <div>
            <a href="{{ route('projects.requests.show', $unifiedRequest) }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-right me-1"></i> العودة لتفاصيل الطلب
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-4 shadow-sm border-0 mb-4 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-exclamation-triangle fs-5 me-2"></i>
                <strong class="fs-6">يرجى تصحيح الأخطاء التالية:</strong>
            </div>
            <ul class="mb-0 pe-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('projects.requests.update', $unifiedRequest) }}" method="POST" enctype="multipart/form-data" id="editUnifiedRequestForm">
        @csrf
        @method('PUT')

        {{-- SECTION A: General Request Information (Immutable Identity) --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-info-circle me-2"></i> البيانات العامة للطلب
                </h5>
                <span class="badge bg-light text-muted border rounded-pill px-3 py-1">
                    الحالة: {{ $unifiedRequest->statusLabel() }}
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small">المشروع</label>
                        <input type="text" class="form-control bg-light text-dark fw-bold"
                               value="{{ $unifiedRequest->project?->project_name }} ({{ $unifiedRequest->project?->form_number }})" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small">نوع الطلب</label>
                        <input type="text" class="form-control bg-light text-primary fw-bold"
                               value="{{ $unifiedRequest->typeLabel() }}" readonly>
                    </div>

                    <div class="col-12">
                        <label for="descriptionInput" class="form-label fw-bold text-dark small">بيان الطلب / الموضوع</label>
                        <textarea name="description" id="descriptionInput" rows="2" class="form-control"
                                  placeholder="موضوع أو ملخص بيان الطلب...">{{ old('description', $unifiedRequest->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION B: Financial Overview Summary Card --}}
        @if(isset($financialData))
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-light py-3 px-4 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-coins text-warning me-2"></i> الموازنة والأرصدة المالية الحالية للمشروع
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded-4 border bg-light h-100">
                                <div class="text-muted small fw-bold mb-1">الموازنة المعتمدة</div>
                                <div class="fs-6 fw-bold text-dark">
                                    {{ number_format($financialData['budget']['approved_budget'] ?? $financialData['total_budget'] ?? 0, 2) }} ر.ي
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded-4 border bg-info-subtle border-info-subtle h-100">
                                <div class="text-info-emphasis small fw-bold mb-1">المنصرف فعلياً</div>
                                <div class="fs-6 fw-bold text-info-emphasis">
                                    {{ number_format($financialData['balances']['total_actually_disbursed'] ?? $financialData['total_disbursed'] ?? 0, 2) }} ر.ي
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded-4 border bg-warning-subtle border-warning-subtle h-100">
                                <div class="text-warning-emphasis small fw-bold mb-1">العهد القائمة</div>
                                <div class="fs-6 fw-bold text-warning-emphasis">
                                    {{ number_format($financialData['balances']['total_outstanding_advances'] ?? 0, 2) }} ر.ي
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded-4 border bg-success-subtle border-success-subtle h-100">
                                <div class="text-success-emphasis small fw-bold mb-1">المتبقي القابل للصرف</div>
                                <div class="fs-6 fw-bold text-success-emphasis">
                                    {{ number_format($financialData['balances']['remaining_eligible_for_disbursement'] ?? $financialData['remaining_budget'] ?? 0, 2) }} ر.ي
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECTION C: Clearance Details Section --}}
        @if($unifiedRequest->hasClearanceComponent())
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-warning border-4">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0 text-warning">
                        <i class="fas fa-clipboard-check me-2"></i> بيانات إخلاء العهدة المالية
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark small">رقم الدفعة المراد إخلاؤها</label>
                            <input type="text" class="form-control bg-light fw-bold"
                                   value="الدفعة {{ $unifiedRequest->clearanceInstallment?->sequence_number ?: '—' }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark small">مبلغ العهدة المستلم</label>
                            <input type="number" step="0.01" name="original_advance_amount" id="originalAdvanceAmount"
                                   class="form-control bg-light fw-bold" value="{{ old('original_advance_amount', $unifiedRequest->original_advance_amount) }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="advanceRecipient" class="form-label fw-bold text-dark small">مستلم العهدة</label>
                            <input type="text" name="advance_recipient" id="advanceRecipient" class="form-control"
                                   value="{{ old('advance_recipient', $unifiedRequest->advance_recipient) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="advanceReceiptDate" class="form-label fw-bold text-dark small">تاريخ استلام العهدة</label>
                            <input type="date" name="advance_receipt_date" id="advanceReceiptDate" class="form-control gregorian-date-input"
                                   value="{{ old('advance_receipt_date', $unifiedRequest->advance_receipt_date?->format('Y-m-d')) }}"
                                   data-hijri-target="advanceReceiptDateHijriBadge">
                            <div class="mt-1 small" id="advanceReceiptDateHijriBadge"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="clearedAmountInput" class="form-label fw-bold text-dark small required">
                                المبلغ المقدم للإخلاء <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="cleared_amount" id="clearedAmountInput"
                                       class="form-control fw-bold border-warning" value="{{ old('cleared_amount', $unifiedRequest->cleared_amount) }}">
                                <span class="input-group-text bg-warning-subtle fw-bold">ر.ي</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="remainingAdvanceAmount" class="form-label fw-bold text-dark small">المبلغ المتبقي من العهدة</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="remaining_advance_amount" id="remainingAdvanceAmount"
                                       class="form-control bg-light text-muted fw-bold" value="{{ old('remaining_advance_amount', $unifiedRequest->remaining_advance_amount) }}" readonly>
                                <span class="input-group-text">ر.ي</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="technicalProgressPct" class="form-label fw-bold text-dark small">نسبة الإنجاز الفني (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="technical_progress_percentage" id="technicalProgressPct"
                                       class="form-control" value="{{ old('technical_progress_percentage', $unifiedRequest->technical_progress_percentage) }}">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="clearanceDescription" class="form-label fw-bold text-dark small">بيان الإخلاء وتفاصيل المصروفات</label>
                            <textarea name="clearance_description" id="clearanceDescription" rows="2" class="form-control">{{ old('clearance_description', $unifiedRequest->clearance_description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECTION D: Payment Details Section --}}
        @if($unifiedRequest->hasPaymentComponent())
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-success border-4">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0 text-success">
                        <i class="fas fa-hand-holding-usd me-2"></i> بيانات طلب صرف الدفعة
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark small">رقم الدفعة المطلوبة</label>
                            <input type="number" class="form-control bg-light fw-bold"
                                   value="{{ $unifiedRequest->payment_number }}" readonly>
                        </div>

                        <div class="col-md-3">
                            <label for="requestedPaymentAmount" class="form-label fw-bold text-dark small required">
                                المبلغ المطلوب صرفه <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="requested_payment_amount" id="requestedPaymentAmount"
                                       class="form-control fw-bold border-success" value="{{ old('requested_payment_amount', $unifiedRequest->requested_payment_amount) }}">
                                <span class="input-group-text bg-success-subtle fw-bold">ر.ي</span>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="paymentRecipient" class="form-label fw-bold text-dark small">مستلم الدفعة</label>
                            <input type="text" name="payment_recipient" id="paymentRecipient" class="form-control"
                                   value="{{ old('payment_recipient', $unifiedRequest->payment_recipient) }}">
                        </div>

                        <div class="col-md-3">
                            <label for="expectedClearanceDate" class="form-label fw-bold text-dark small">تاريخ الإخلاء المتوقع</label>
                            <input type="date" name="expected_clearance_date" id="expectedClearanceDate" class="form-control gregorian-date-input"
                                   value="{{ old('expected_clearance_date', $unifiedRequest->expected_clearance_date?->format('Y-m-d')) }}"
                                   data-hijri-target="expectedClearanceDateHijriBadge">
                            <div class="mt-1 small" id="expectedClearanceDateHijriBadge"></div>
                        </div>

                        <div class="col-12">
                            <label for="paymentJustification" class="form-label fw-bold text-dark small">مبررات طلب الدفعة واحتياجات التنفيذ</label>
                            <textarea name="payment_justification" id="paymentJustification" rows="2" class="form-control">{{ old('payment_justification', $unifiedRequest->payment_justification) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECTION E: Closure Section --}}
        @if($unifiedRequest->hasClosureComponent())
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-dark border-4">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-lock me-2"></i> بيانات الإغلاق المالي والإداري للمشروع
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="projectCompletionDate" class="form-label fw-bold text-dark small">تاريخ اكتمال أنشطة المشروع</label>
                            <input type="date" name="project_completion_date" id="projectCompletionDate" class="form-control gregorian-date-input"
                                   value="{{ old('project_completion_date', $unifiedRequest->project_completion_date?->format('Y-m-d')) }}"
                                   data-hijri-target="projectCompletionDateHijriBadge">
                            <div class="mt-1 small" id="projectCompletionDateHijriBadge"></div>
                        </div>
                        <div class="col-md-8">
                            <label for="finalSettlementNotes" class="form-label fw-bold text-dark small">ملاحظات التسوية المالية الختامية</label>
                            <input type="text" name="final_settlement_notes" id="finalSettlementNotes" class="form-control"
                                   value="{{ old('final_settlement_notes', $unifiedRequest->final_settlement_notes) }}">
                        </div>
                        <div class="col-12">
                            <label for="closureJustification" class="form-label fw-bold text-dark small required">
                                مبررات وتفاصيل طلب إغلاق المشروع <span class="text-danger">*</span>
                            </label>
                            <textarea name="closure_justification" id="closureJustification" rows="3" class="form-control">{{ old('closure_justification', $unifiedRequest->closure_justification) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECTION F: Documents --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-paperclip me-2"></i> المستندات المؤيدة والمرفقات
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        @if($unifiedRequest->attachment_path)
                            <div class="mb-2">
                                <span class="text-muted small">المرفق الحالي:</span>
                                <a href="{{ asset('storage/' . $unifiedRequest->attachment_path) }}" target="_blank" class="fw-bold text-primary ms-1">
                                    <i class="fas fa-file-download me-1"></i> تحميل المستند الحالي
                                </a>
                            </div>
                        @endif
                        <label for="attachmentInput" class="form-label fw-bold text-dark small">استبدال المرفق (اختياري)</label>
                        <input type="file" name="attachment" id="attachmentInput" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex justify-content-end gap-3 mb-5">
            <a href="{{ route('projects.requests.show', $unifiedRequest) }}" class="btn btn-outline-secondary rounded-pill px-4">
                إلغاء
            </a>
            <button type="submit" class="btn btn-warning btn-lg rounded-pill px-5 shadow-sm fw-bold">
                <i class="fas fa-save me-2"></i> حفظ التعديلات
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const originalAdvanceAmount = document.getElementById('originalAdvanceAmount');
    const clearedAmountInput = document.getElementById('clearedAmountInput');
    const remainingAdvanceAmount = document.getElementById('remainingAdvanceAmount');

    function calculateRemainingAdvance() {
        if (!originalAdvanceAmount || !clearedAmountInput || !remainingAdvanceAmount) return;
        const orig = parseFloat(originalAdvanceAmount.value) || 0;
        const cleared = parseFloat(clearedAmountInput.value) || 0;
        const rem = Math.max(0, orig - cleared);
        remainingAdvanceAmount.value = rem.toFixed(2);
    }

    if (clearedAmountInput) {
        clearedAmountInput.addEventListener('input', calculateRemainingAdvance);
    }

    // Live Gregorian-to-Hijri Synchronization
    function updateHijriDisplay(input) {
        const val = input.value;
        const targetId = input.getAttribute('data-hijri-target');
        if (!targetId) return;
        const badge = document.getElementById(targetId);
        if (!badge) return;

        if (!val) {
            badge.innerHTML = '';
            return;
        }

        try {
            const d = new Date(val);
            if (!isNaN(d.getTime())) {
                const intlHijri = new Intl.DateTimeFormat('ar-SA-u-ca-islamic-umalqura', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                }).format(d);
                badge.innerHTML = `<span class="badge bg-light text-primary border"><i class="fas fa-calendar-alt me-1"></i> الموافق هجرياً: ${intlHijri}</span>`;
                return;
            }
        } catch (e) {}

        badge.innerHTML = '';
    }

    document.querySelectorAll('.gregorian-date-input').forEach(input => {
        updateHijriDisplay(input);
        input.addEventListener('change', () => updateHijriDisplay(input));
        input.addEventListener('input', () => updateHijriDisplay(input));
    });
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'إنشاء طلب جديد')

@section('content')

<div class="container-fluid px-4 py-4" dir="rtl">

    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.requests.index') }}" class="text-decoration-none text-muted">إدارة الطلبات</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">إنشاء طلب جديد</li>
                </ol>
            </nav>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="fas fa-plus-circle text-primary me-2"></i> إنشاء طلب جديد للمشروع
            </h2>
        </div>
        <div>
            <a href="{{ route('projects.requests.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-4 shadow-sm border-0 mb-4 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-exclamation-triangle fs-5 me-2"></i>
                <strong class="fs-6">يرجى تصحيح الأخطاء التالية قبل المتابعة:</strong>
            </div>
            <ul class="mb-0 pe-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-3 py-3">
        <div class="fs-3 text-info"><i class="fas fa-file-signature"></i></div>
        <div>
            <div class="fw-bold mb-1">دورة حياة الطلب: مسودة &larr; تأكيد المسودة &larr; الإرسال للاعتماد الرسمي</div>
            <div class="small text-muted">سيتم حفظ طلبك كمسودة أولاً دون إنشاء قيود مالية، ويمكنك مراجعتها وتأكيدها ثم إرسالها لمسار الموافقات بمركز الاعتمادات لإنشاء قيد الصرف في ERPNext آلياً.</div>
        </div>
    </div>

    <form action="{{ route('projects.requests.store') }}" method="POST" enctype="multipart/form-data" id="unifiedRequestForm">
        @csrf

        {{-- SECTION 1 & 2: Primary Request Information --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-layer-group me-2"></i> بيانات الطلب الأساسية
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    {{-- FIELD 1 (STRICTLY FIRST): Project Selection --}}
                    <div class="col-md-6">
                        <label for="projectSelect" class="form-label fw-bold text-dark fs-6 required">
                            1. المشروع المستهدف <span class="text-danger">*</span>
                        </label>
                        <select name="project_id" id="projectSelect" class="form-select form-select-lg fw-semibold border-primary-subtle shadow-none" required>
                            <option value="">-- اختر المشروع للبدء وتحديث البيانات المالية --</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}"
                                        data-code="{{ $p->form_number ?: ('#'.$p->id) }}"
                                        data-name="{{ $p->project_name }}"
                                        {{ (string)old('project_id', $preselectedProjectId) === (string)$p->id ? 'selected' : '' }}>
                                    {{ $p->project_name }} ({{ $p->form_number ?: ('#'.$p->id) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">
                            المشاريع المتاحة هي تلك التي دخلت مرحلة التنفيذ ولديها خطة صرف معتمدة ضمن نطاقك المصرح به.
                        </div>
                    </div>

                    {{-- FIELD 2 (STRICTLY SECOND): Request Type Selection --}}
                    <div class="col-md-6">
                        <label for="requestTypeSelect" class="form-label fw-bold text-dark fs-6 required">
                            2. نوع الطلب <span class="text-danger">*</span>
                        </label>
                        <select name="request_type" id="requestTypeSelect" class="form-select form-select-lg fw-semibold border-primary-subtle shadow-none" required>
                            <option value="clearance_payment" {{ old('request_type', $preselectedType) === 'clearance_payment' ? 'selected' : '' }}>
                                إخلاء عهدة سابقة وطلب دفعة جديدة (Clearance & Payment)
                            </option>
                            <option value="clearance" {{ old('request_type', $preselectedType) === 'clearance' ? 'selected' : '' }}>
                                إخلاء عهدة مالية فقط (Clearance Only)
                            </option>
                            <option value="payment" {{ old('request_type', $preselectedType) === 'payment' ? 'selected' : '' }}>
                                طلب صرف دفعة فقط (Payment Request Only)
                            </option>
                            <option value="clearance_closure" {{ old('request_type', $preselectedType) === 'clearance_closure' ? 'selected' : '' }}>
                                إخلاء الدفعة الأخيرة وإغلاق المشروع (Clearance & Closure)
                            </option>
                        </select>
                        <div class="form-text small text-muted">
                            اختر نوع العملية المطلوبة؛ سيتم ضبط الحقول المؤهلة آلياً بناءً على حالة الخطة.
                        </div>
                    </div>

                    {{-- General Description / Subject --}}
                    <div class="col-12">
                        <label for="descriptionInput" class="form-label fw-bold text-dark small">بيان الطلب / الموضوع العام</label>
                        <textarea name="description" id="descriptionInput" rows="2" class="form-control"
                                  placeholder="موضوع أو ملخص بيان الطلب الموحد...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 3: Authoritative Financial Balances & Disbursement Plan Overview --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white" id="financialOverviewCard">
            <div class="card-header bg-light py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary p-2 rounded-3 text-white"><i class="fas fa-chart-pie"></i></span>
                    <h5 class="fw-bold mb-0 text-dark">
                        3. الموازنة والأرصدة المالية وحالة خطة الصرف
                    </h5>
                </div>
                <div id="planBadgesContainer" class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary rounded-pill px-3 py-2" id="badgePlanStatus">يرجى اختيار المشروع</span>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2" id="badgeClosureStatus">حالة الإغلاق: —</span>
                </div>
            </div>
            <div class="card-body p-4">
                {{-- Balances KPI Grid --}}
                <div class="row g-3 text-center mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-4 border bg-light h-100">
                            <div class="text-muted small fw-bold mb-1">موازنة المشروع المعتمدة</div>
                            <div class="fs-5 fw-bolder text-dark" id="cardBudget">0.00 ر.ي</div>
                            <div class="text-muted smaller" id="cardFundingSub">التمويل: 0.00 ر.ي</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-4 border bg-info-subtle border-info-subtle h-100">
                            <div class="text-info-emphasis small fw-bold mb-1">إجمالي المنصرف فعلياً</div>
                            <div class="fs-5 fw-bolder text-info-emphasis" id="cardDisbursed">0.00 ر.ي</div>
                            <div class="text-muted smaller" id="cardDisbursedCount">0 دفعات مصروفة</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-4 border bg-warning-subtle border-warning-subtle h-100">
                            <div class="text-warning-emphasis small fw-bold mb-1">العهد القائمة (غير مخلاة)</div>
                            <div class="fs-5 fw-bolder text-warning-emphasis" id="cardOutstandingAdvances">0.00 ر.ي</div>
                            <div class="text-muted smaller" id="cardClearedSub">المخلى سابقاً: 0.00 ر.ي</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-4 border bg-success-subtle border-success-subtle h-100">
                            <div class="text-success-emphasis small fw-bold mb-1">المتبقي القابل للصرف</div>
                            <div class="fs-5 fw-bolder text-success-emphasis" id="cardRemainingEligible">0.00 ر.ي</div>
                            <div class="text-muted smaller" id="cardRemainingCount">0 دفعات مجدولة</div>
                        </div>
                    </div>
                </div>

                {{-- Installment Counts Bar --}}
                <div class="p-2 px-3 rounded-3 bg-light border d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
                    <div>
                        <i class="fas fa-list-ol me-1 text-primary"></i>
                        <span>إجمالي دفعات الخطة: <strong class="text-dark" id="countTotal">0</strong></span>
                    </div>
                    <div>
                        <i class="fas fa-check-circle me-1 text-success"></i>
                        <span>مخلاة بالكامل: <strong class="text-success" id="countCleared">0</strong></span>
                    </div>
                    <div>
                        <i class="fas fa-hand-holding-usd me-1 text-info"></i>
                        <span>مصروفة (عهدة نشطة): <strong class="text-info" id="countDisbursed">0</strong></span>
                    </div>
                    <div>
                        <i class="fas fa-clock me-1 text-warning"></i>
                        <span>مخططة متبقية: <strong class="text-warning" id="countRemaining">0</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION C: Clearance Details Section --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-warning border-4" id="clearanceSection">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-warning">
                    <i class="fas fa-clipboard-check me-2"></i> بيانات إخلاء العهدة المالية
                </h5>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 rounded-pill">
                    قسم الإخلاء المالي
                </span>
            </div>
            <div class="card-body p-4">
                <input type="hidden" name="clearance_disbursement_plan_detail_id" id="clearanceDetailIdInput" value="{{ old('clearance_disbursement_plan_detail_id') }}">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark small">رقم الدفعة المراد إخلاؤها</label>
                        <input type="text" id="clearanceSequenceDisplay" class="form-control bg-light fw-bold"
                               value="{{ old('clearance_sequence_display', '—') }}" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="originalAdvanceAmount" class="form-label fw-bold text-dark small">مبلغ العهدة المستلم</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="original_advance_amount" id="originalAdvanceAmount"
                                   class="form-control bg-light fw-bold" value="{{ old('original_advance_amount', 0) }}" readonly>
                            <span class="input-group-text">ر.ي</span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="advanceRecipient" class="form-label fw-bold text-dark small">مستلم العهدة</label>
                        <input type="text" name="advance_recipient" id="advanceRecipient" class="form-control"
                               placeholder="اسم مستلم العهدة..." value="{{ old('advance_recipient') }}">
                    </div>

                    <div class="col-md-3">
                        <label for="advanceReceiptDate" class="form-label fw-bold text-dark small">تاريخ استلام العهدة</label>
                        <input type="date" name="advance_receipt_date" id="advanceReceiptDate"
                               class="form-control gregorian-date-input" value="{{ old('advance_receipt_date') }}"
                               data-hijri-target="advanceReceiptDateHijriBadge">
                        <div class="mt-1 small" id="advanceReceiptDateHijriBadge"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="clearedAmountInput" class="form-label fw-bold text-dark small required">
                            المبلغ المقدم للإخلاء <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" name="cleared_amount" id="clearedAmountInput"
                                   class="form-control fw-bold border-warning" value="{{ old('cleared_amount', 0) }}">
                            <span class="input-group-text bg-warning-subtle fw-bold">ر.ي</span>
                        </div>
                        <div class="form-text small text-muted">إجمالي المصروفات الفعلية المؤيدة بالمستندات.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="remainingAdvanceAmount" class="form-label fw-bold text-dark small">المبلغ المتبقي من العهدة</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="remaining_advance_amount" id="remainingAdvanceAmount"
                                   class="form-control bg-light text-muted fw-bold" value="{{ old('remaining_advance_amount', 0) }}" readonly>
                            <span class="input-group-text">ر.ي</span>
                        </div>
                        <div class="form-text small text-muted">الرصيد المتبقي = مبلغ العهدة - المبلغ المقدم للإخلاء.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="technicalProgressPct" class="form-label fw-bold text-dark small">نسبة الإنجاز الفني (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="technical_progress_percentage" id="technicalProgressPct"
                                   class="form-control" value="{{ old('technical_progress_percentage', 0) }}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="clearanceDescription" class="form-label fw-bold text-dark small">بيان الإخلاء وتفاصيل المصروفات</label>
                        <textarea name="clearance_description" id="clearanceDescription" rows="2" class="form-control"
                                  placeholder="بيان تفصيلي بالمصروفات وأنشطة الإنجاز المنجزة...">{{ old('clearance_description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION D: Payment Details Section --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-success border-4" id="paymentSection">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-success">
                    <i class="fas fa-hand-holding-usd me-2"></i> بيانات طلب صرف الدفعة
                </h5>
                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-1 rounded-pill">
                    قسم صرف الدفعة
                </span>
            </div>
            <div class="card-body p-4">
                <input type="hidden" name="payment_disbursement_plan_detail_id" id="paymentDetailIdInput" value="{{ old('payment_disbursement_plan_detail_id') }}">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="paymentNumberInput" class="form-label fw-bold text-dark small">رقم الدفعة المطلوبة</label>
                        <input type="number" name="payment_number" id="paymentNumberInput"
                               class="form-control bg-light fw-bold" value="{{ old('payment_number') }}" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="requestedPaymentAmount" class="form-label fw-bold text-dark small required">
                            المبلغ المطلوب صرفه <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" name="requested_payment_amount" id="requestedPaymentAmount"
                                   class="form-control fw-bold border-success" value="{{ old('requested_payment_amount', 0) }}">
                            <span class="input-group-text bg-success-subtle fw-bold">ر.ي</span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="paymentRecipient" class="form-label fw-bold text-dark small">مستلم الدفعة</label>
                        <input type="text" name="payment_recipient" id="paymentRecipient" class="form-control"
                               placeholder="اسم مستلم الدفعة أو الجهة..." value="{{ old('payment_recipient') }}">
                    </div>

                    <div class="col-md-3">
                        <label for="expectedClearanceDate" class="form-label fw-bold text-dark small">تاريخ الإخلاء المتوقع</label>
                        <input type="date" name="expected_clearance_date" id="expectedClearanceDate"
                               class="form-control gregorian-date-input" value="{{ old('expected_clearance_date') }}"
                               data-hijri-target="expectedClearanceDateHijriBadge">
                        <div class="mt-1 small" id="expectedClearanceDateHijriBadge"></div>
                    </div>

                    <div class="col-12">
                        <label for="paymentJustification" class="form-label fw-bold text-dark small">مبررات طلب الدفعة واحتياجات التنفيذ</label>
                        <textarea name="payment_justification" id="paymentJustification" rows="2" class="form-control"
                                  placeholder="مبررات طلب صرف الدفعة والأنشطة المخطط تنفيذها...">{{ old('payment_justification') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION E: Project Closure Details Section --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-dark border-4" id="closureSection" style="display: none;">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-lock me-2"></i> بيانات الإغلاق المالي والإداري للمشروع
                </h5>
                <span class="badge bg-dark text-white px-3 py-1 rounded-pill">
                    قسم إغلاق المشروع
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="projectCompletionDate" class="form-label fw-bold text-dark small">تاريخ اكتمال أنشطة المشروع</label>
                        <input type="date" name="project_completion_date" id="projectCompletionDate"
                               class="form-control gregorian-date-input" value="{{ old('project_completion_date', now()->format('Y-m-d')) }}"
                               data-hijri-target="projectCompletionDateHijriBadge">
                        <div class="mt-1 small" id="projectCompletionDateHijriBadge"></div>
                    </div>

                    <div class="col-md-8">
                        <label for="finalSettlementNotes" class="form-label fw-bold text-dark small">ملاحظات التسوية المالية الختامية</label>
                        <input type="text" name="final_settlement_notes" id="finalSettlementNotes" class="form-control"
                               placeholder="ملاحظات التسوية المحاسبية والالتزامات المتبقية..." value="{{ old('final_settlement_notes') }}">
                    </div>

                    <div class="col-12">
                        <label for="closureJustification" class="form-label fw-bold text-dark small required">
                            مبررات وتفاصيل طلب إغلاق المشروع <span class="text-danger">*</span>
                        </label>
                        <textarea name="closure_justification" id="closureJustification" rows="3" class="form-control"
                                  placeholder="بيان اكتمال المشروع وتصفية كافة العهد والالتزامات ومبررات الإغلاق...">{{ old('closure_justification') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION F: Documents & Attachments --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-paperclip me-2"></i> المستندات المؤيدة والمرفقات
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="attachmentInput" class="form-label fw-bold text-dark small">ملف الوثائق والمستندات المؤيدة للطلب</label>
                        <input type="file" name="attachment" id="attachmentInput" class="form-control">
                        <div class="form-text small text-muted">
                            ملفات مسموحة: PDF, صور, Word, أو ملف مضغوط ZIP (الحجم الأقصى 20 ميغابايت).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex justify-content-end gap-3 mb-5">
            <a href="{{ route('projects.requests.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                إلغاء
            </a>
            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm fw-bold">
                <i class="fas fa-save me-2"></i> حفظ الطلب كمسودة
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const requestTypeSelect = document.getElementById('requestTypeSelect');
    const clearanceSection = document.getElementById('clearanceSection');
    const paymentSection = document.getElementById('paymentSection');
    const closureSection = document.getElementById('closureSection');
    const projectSelect = document.getElementById('projectSelect');

    const cardBudget = document.getElementById('cardBudget');
    const cardFundingSub = document.getElementById('cardFundingSub');
    const cardDisbursed = document.getElementById('cardDisbursed');
    const cardDisbursedCount = document.getElementById('cardDisbursedCount');
    const cardOutstandingAdvances = document.getElementById('cardOutstandingAdvances');
    const cardClearedSub = document.getElementById('cardClearedSub');
    const cardRemainingEligible = document.getElementById('cardRemainingEligible');
    const cardRemainingCount = document.getElementById('cardRemainingCount');

    const countTotal = document.getElementById('countTotal');
    const countCleared = document.getElementById('countCleared');
    const countDisbursed = document.getElementById('countDisbursed');
    const countRemaining = document.getElementById('countRemaining');

    const badgePlanStatus = document.getElementById('badgePlanStatus');
    const badgeClosureStatus = document.getElementById('badgeClosureStatus');

    const originalAdvanceAmount = document.getElementById('originalAdvanceAmount');
    const clearedAmountInput = document.getElementById('clearedAmountInput');
    const remainingAdvanceAmount = document.getElementById('remainingAdvanceAmount');

    let currentFinancialData = null;

    // 1. Toggle Sections based on Request Type
    function updateSectionsVisibility() {
        const type = requestTypeSelect.value;
        const hasClearance = (type === 'clearance' || type === 'clearance_payment' || type === 'clearance_closure');
        const hasPayment = (type === 'payment' || type === 'clearance_payment');
        const hasClosure = (type === 'clearance_closure');

        clearanceSection.style.display = hasClearance ? 'block' : 'none';
        paymentSection.style.display = hasPayment ? 'block' : 'none';
        closureSection.style.display = hasClosure ? 'block' : 'none';

        if (currentFinancialData) {
            applyFinancialDataToInstallments(currentFinancialData);
        }
    }

    requestTypeSelect.addEventListener('change', updateSectionsVisibility);
    updateSectionsVisibility();

    // 2. Real-time Remaining Advance Balance Calculation
    function calculateRemainingAdvance() {
        const orig = parseFloat(originalAdvanceAmount.value) || 0;
        const cleared = parseFloat(clearedAmountInput.value) || 0;
        const rem = Math.max(0, orig - cleared);
        remainingAdvanceAmount.value = rem.toFixed(2);
    }

    if (clearedAmountInput) {
        clearedAmountInput.addEventListener('input', calculateRemainingAdvance);
    }

    // 3. Helper to populate installment sections from financial data
    function applyFinancialDataToInstallments(d) {
        const type = requestTypeSelect.value;

        // Populate Clearance Section
        const clrInst = d.eligible_clearance || d.active_installment;
        if (clrInst) {
            document.getElementById('clearanceDetailIdInput').value = clrInst.id;
            document.getElementById('clearanceSequenceDisplay').value = 'الدفعة رقم ' + clrInst.sequence_number;
            originalAdvanceAmount.value = Number(clrInst.disbursed_amount || 0).toFixed(2);

            if (d.un_cleared_total_amount && d.un_cleared_total_amount > 0 && (!clearedAmountInput.value || clearedAmountInput.value == '0')) {
                clearedAmountInput.value = Number(d.un_cleared_total_amount).toFixed(2);
            }

            if (d.suggested_progress_pct) {
                document.getElementById('technicalProgressPct').value = d.suggested_progress_pct;
            }

            calculateRemainingAdvance();
        } else {
            document.getElementById('clearanceDetailIdInput').value = '';
            document.getElementById('clearanceSequenceDisplay').value = 'لا توجد دفعات مصروفة حالياً للإخلاء';
            originalAdvanceAmount.value = '0.00';
            remainingAdvanceAmount.value = '0.00';
        }

        // Populate Payment Section based on request type
        let payInst = null;
        if (type === 'clearance_payment') {
            payInst = d.eligible_payment_for_combined || d.eligible_payment || d.next_installment;
        } else {
            payInst = d.eligible_payment || d.next_installment;
        }

        if (payInst) {
            document.getElementById('paymentDetailIdInput').value = payInst.id;
            document.getElementById('paymentNumberInput').value = payInst.sequence_number;
            document.getElementById('requestedPaymentAmount').value = Number(payInst.planned_amount || 0).toFixed(2);
        } else {
            document.getElementById('paymentDetailIdInput').value = '';
            document.getElementById('paymentNumberInput').value = '';
            document.getElementById('requestedPaymentAmount').value = '0.00';
        }
    }

    // 4. Dynamic Project Selection -> Retrieve Financial Balances & Installments
    projectSelect.addEventListener('change', function () {
        const projectId = this.value;
        if (!projectId) {
            resetFinancialCards();
            currentFinancialData = null;
            return;
        }

        fetch(`{{ url('projects/requests/project-financials') }}/${projectId}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                currentFinancialData = d;

                // Format Balances & Budget
                const budgetVal = d.budget ? d.budget.approved_budget : d.total_budget;
                const fundingVal = d.budget ? d.budget.approved_funding : budgetVal;
                const disbursedVal = d.balances ? d.balances.total_actually_disbursed : d.total_disbursed;
                const advancesVal = d.balances ? d.balances.total_outstanding_advances : 0;
                const clearedVal = d.balances ? d.balances.total_previously_cleared : 0;
                const eligibleDisbVal = d.balances ? d.balances.remaining_eligible_for_disbursement : d.remaining_budget;

                cardBudget.textContent = Number(budgetVal).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ر.ي';
                cardFundingSub.textContent = 'التمويل المعتمد: ' + Number(fundingVal).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ر.ي';
                cardDisbursed.textContent = Number(disbursedVal).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ر.ي';
                cardOutstandingAdvances.textContent = Number(advancesVal).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ر.ي';
                cardClearedSub.textContent = 'المخلى سابقاً: ' + Number(clearedVal).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ر.ي';
                cardRemainingEligible.textContent = Number(eligibleDisbVal).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ر.ي';

                // Counts
                if (d.counts) {
                    countTotal.textContent = d.counts.total_installments_count;
                    countCleared.textContent = d.counts.cleared_installments_count;
                    countDisbursed.textContent = d.counts.disbursed_installments_count;
                    countRemaining.textContent = d.counts.remaining_installments_count;
                    cardDisbursedCount.textContent = d.counts.disbursed_installments_count + ' دفعات مصروفة';
                    cardRemainingCount.textContent = d.counts.remaining_installments_count + ' دفعات متبقية';
                }

                // Plan Status Badge
                badgePlanStatus.className = 'badge rounded-pill px-3 py-2 ' + (d.plan_status === 'approved' ? 'bg-success' : 'bg-warning');
                badgePlanStatus.textContent = 'حالة الخطة: ' + (d.plan_status === 'approved' ? 'معتمدة' : (d.plan_status || 'غير محدد'));

                // Closure Status Badge
                if (d.closure_eligibility) {
                    badgeClosureStatus.className = 'badge rounded-pill px-3 py-2 ' + (d.closure_eligibility.is_eligible ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger');
                    badgeClosureStatus.textContent = d.closure_eligibility.is_eligible ? 'مؤهل للإغلاق' : 'غير مؤهل للإغلاق';
                    badgeClosureStatus.title = d.closure_eligibility.reason;
                }

                // Populate installment cards
                applyFinancialDataToInstallments(d);
            }
        })
        .catch(err => console.error(err));
    });

    function resetFinancialCards() {
        cardBudget.textContent = '0.00 ر.ي';
        cardFundingSub.textContent = 'التمويل: 0.00 ر.ي';
        cardDisbursed.textContent = '0.00 ر.ي';
        cardOutstandingAdvances.textContent = '0.00 ر.ي';
        cardClearedSub.textContent = 'المخلى سابقاً: 0.00 ر.ي';
        cardRemainingEligible.textContent = '0.00 ر.ي';
        badgePlanStatus.textContent = 'يرجى اختيار المشروع';
        badgePlanStatus.className = 'badge bg-secondary rounded-pill px-3 py-2';
        badgeClosureStatus.textContent = 'حالة الإغلاق: —';
        badgeClosureStatus.className = 'badge bg-light text-dark border rounded-pill px-3 py-2';
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

    // Trigger initial project loading if preselected
    if (projectSelect.value) {
        projectSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush

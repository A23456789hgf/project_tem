@extends('layouts.app')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-color: #4f46e5;
        --primary-light: #eef2ff;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --bg-body: #f8fafc;
        --card-bg: #ffffff;
        --success-soft: #dcfce7;
        --success-text: #166534;
        --danger-soft: #fee2e2;
        --danger-text: #991b1b;
        --warning-soft: #fef3c7;
        --warning-text: #92400e;
        --radius-lg: 16px;
        --radius-md: 12px;
        --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Cairo', sans-serif;
        color: var(--text-dark);
    }

    .plan-header-card {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
        color: #ffffff;
        border-radius: var(--radius-lg);
        padding: 1.75rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 1.5rem;
    }

    .kpi-stat-box {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border-radius: var(--radius-md);
        padding: 1rem 1.25rem;
    }

    .table-plan {
        background: #ffffff;
        border-radius: var(--radius-md);
        overflow: hidden;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-plan thead th {
        background: #f1f5f9;
        color: #334155;
        font-weight: 700;
        font-size: 0.88rem;
        padding: 0.85rem 1rem;
        border-bottom: 2px solid #cbd5e1;
    }

    .table-plan tbody td {
        padding: 0.75rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    .table-plan tfoot td {
        background: #f8fafc;
        font-weight: 700;
        padding: 1rem;
        border-top: 2px solid #cbd5e1;
    }

    .seq-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #e0e7ff;
        color: #4338ca;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .diff-badge-match {
        background-color: var(--success-soft);
        color: var(--success-text);
        border: 1px solid #86efac;
    }

    .diff-badge-mismatch {
        background-color: var(--danger-soft);
        color: var(--danger-text);
        border: 1px solid #fca5a5;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Alert Messages --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted text-decoration-none">المشاريع</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}" class="text-muted text-decoration-none">{{ $project->project_name }}</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">إعداد خطة الصرف</li>
        </ol>
    </nav>

    {{-- Plan Header Banner --}}
    <div class="plan-header-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">
                    <i class="fas fa-coins me-1"></i> مرحلة خطة الصرف
                </span>
                <h3 class="fw-bold mb-1">{{ $project->project_name }}</h3>
                <div class="small opacity-75">
                    <span><i class="fas fa-hashtag me-1"></i> رقم المشروع: <strong>{{ $project->form_number ?: $project->id }}</strong></span>
                    <span class="mx-2">•</span>
                    <span><i class="fas fa-building me-1"></i> الجهة المنشئة: {{ $project->creatorEntity?->name ?? 'غير محدد' }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-light rounded-pill px-3 py-2 btn-sm">
                    <i class="fas fa-arrow-right me-1"></i> العودة للمشروع
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="kpi-stat-box">
                    <div class="small text-white-50 mb-1">إجمالي التكلفة التقديرية المعتمدة للمشروع</div>
                    <div class="fs-4 fw-bold text-white" id="displayProjectCost">
                        {{ number_format($projectTotalCost, 2) }} <small class="fs-6">ريال</small>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="kpi-stat-box">
                    <div class="small text-white-50 mb-1">المبلغ كتابةً (بالتفقيد)</div>
                    <div class="fs-6 fw-semibold text-warning">
                        {{ $arabicCostWords }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Container --}}
    <form action="{{ route('projects.disbursement-plan.store', $project) }}" method="POST" id="disbursementPlanForm">
        @csrf

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-list-ol text-primary me-2"></i> جدول خطة الصرف والدفعات المخططة
                    </h5>
                    <small class="text-muted">أدخل مبالغ الدفعات وآلية الصرف والإخلاء لكل دفعة. يجب أن يتطابق إجمالي الدفعات تماماً مع إجمالي تكلفة المشروع.</small>
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-3 py-1 btn-sm shadow-sm" id="addRowBtn">
                    <i class="fas fa-plus me-1"></i> إضافة دفعة جديدة
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-plan mb-0" id="planDetailsTable">
                        <thead>
                            <tr>
                                <th style="width: 70px;" class="text-center">م.</th>
                                <th style="width: 250px;">المبلغ (ريال) <span class="text-danger">*</span></th>
                                <th>آلية الصرف والإخلاء <span class="text-danger">*</span></th>
                                <th style="width: 80px;" class="text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody id="planDetailsBody">
                            @php
                                $existingDetails = old('details', ($plan && $plan->details->count() > 0) ? $plan->details->toArray() : [
                                    ['sequence_number' => 1, 'planned_amount' => $projectTotalCost, 'disbursement_clearance_mechanism' => 'صرف الدفعة الأولى كعهدة / تمويل لتنفيذ المرحلة الأولى، والإخلاء بموجب وثائق الإنجاز الفعلي.']
                                ]);
                            @endphp

                            @foreach($existingDetails as $index => $row)
                                <tr class="installment-row" data-index="{{ $index }}">
                                    <td class="text-center">
                                        <span class="seq-badge row-seq">{{ $index + 1 }}</span>
                                        <input type="hidden" name="details[{{ $index }}][sequence_number]" value="{{ $index + 1 }}" class="input-seq">
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0.01"
                                                   name="details[{{ $index }}][planned_amount]"
                                                   class="form-control fw-bold text-end amount-input"
                                                   value="{{ $row['planned_amount'] ?? '' }}"
                                                   placeholder="0.00" required>
                                            <span class="input-group-text bg-light text-muted small">ريال</span>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="details[{{ $index }}][funding_source_id]" class="form-select mb-2">
                                            <option value="">Funding source (optional)</option>
                                            @foreach($fundingSources as $fundingSource)
                                                <option value="{{ $fundingSource->id }}" @selected(($row['funding_source_id'] ?? null) == $fundingSource->id)>{{ $fundingSource->name }}</option>
                                            @endforeach
                                        </select>
                                        <textarea name="details[{{ $index }}][disbursement_clearance_mechanism]"
                                                  rows="2" class="form-control mechanism-input"
                                                  placeholder="اذكر آلية الصرف والإخلاء والشروط المرتبطة بهذه الدفعة..." required>{{ $row['disbursement_clearance_mechanism'] ?? '' }}</textarea>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle delete-row-btn" title="حذف الدفعة">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-start">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span>إجمالي مبالغ الدفعات في الخطة:</span>
                                        <span class="fs-5 fw-bold text-primary" id="tableTotalAmount">0.00 ريال</span>
                                    </div>
                                </td>
                                <td colspan="2">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span>حالة المطابقة مع إجمالي تكلفة المشروع:</span>
                                        <span id="diffStatusBadge" class="badge px-3 py-2 rounded-pill fs-6 diff-badge-mismatch">
                                            جاري الحساب...
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Notes & Submission --}}
            <div class="card-footer bg-white p-4 border-top">
                <div class="row align-items-center g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold small text-dark mb-1">ملاحظات عامة حول خطة الصرف (اختياري):</label>
                        <input type="text" name="notes" class="form-control" placeholder="أي شروط أو ملاحظات تنفيذية عامة..." value="{{ old('notes', $plan->notes ?? '') }}">
                    </div>
                    <div class="col-md-4 text-end d-flex justify-content-end gap-2 pt-md-3">
                        <button type="submit" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold" id="saveDraftBtn">
                            <i class="fas fa-save me-1"></i> حفظ كمسودة
                        </button>
                    </div>
                </div>

                {{-- Difference Warning Alert --}}
                <div class="alert alert-danger rounded-4 mt-3 mb-0 d-none" id="mismatchAlert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="mismatchAlertText">لا يمكن حفظ أو اعتماد الخطة: إجمالي مبالغ الدفعات لا يتطابق مع إجمالي تكلفة المشروع المعتمدة.</span>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const projectTotalCost = {{ $projectTotalCost }};
    const tbody = document.getElementById('planDetailsBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const tableTotalAmountEl = document.getElementById('tableTotalAmount');
    const diffStatusBadge = document.getElementById('diffStatusBadge');
    const mismatchAlert = document.getElementById('mismatchAlert');
    const mismatchAlertText = document.getElementById('mismatchAlertText');
    const saveDraftBtn = document.getElementById('saveDraftBtn');

    function renumberRows() {
        const rows = tbody.querySelectorAll('.installment-row');
        rows.forEach((row, idx) => {
            row.setAttribute('data-index', idx);
            const seqBadge = row.querySelector('.row-seq');
            if (seqBadge) seqBadge.textContent = idx + 1;
            const seqInput = row.querySelector('.input-seq');
            if (seqInput) {
                seqInput.name = `details[${idx}][sequence_number]`;
                seqInput.value = idx + 1;
            }
            const amountInput = row.querySelector('.amount-input');
            if (amountInput) amountInput.name = `details[${idx}][planned_amount]`;
            const mechInput = row.querySelector('.mechanism-input');
            if (mechInput) mechInput.name = `details[${idx}][disbursement_clearance_mechanism]`;
        });
    }

    function calculateTotals() {
        let sum = 0.0;
        const amountInputs = tbody.querySelectorAll('.amount-input');
        amountInputs.forEach(input => {
            const val = parseFloat(input.value) || 0.0;
            sum += val;
        });

        tableTotalAmountEl.textContent = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(sum) + ' ريال';

        const diff = Math.abs(sum - projectTotalCost);
        const isMatched = diff < 0.01;

        if (isMatched) {
            diffStatusBadge.className = 'badge px-3 py-2 rounded-pill fs-6 diff-badge-match';
            diffStatusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i> مطابق تماماً لتكلفة المشروع';
            mismatchAlert.classList.add('d-none');
            saveDraftBtn.disabled = false;
        } else {
            diffStatusBadge.className = 'badge px-3 py-2 rounded-pill fs-6 diff-badge-mismatch';
            const diffFormatted = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(diff);
            if (sum < projectTotalCost) {
                diffStatusBadge.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i> متبقي لم يوزع: ${diffFormatted} ريال`;
            } else {
                diffStatusBadge.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> زيادة عن التكلفة: ${diffFormatted} ريال`;
            }
            mismatchAlertText.textContent = `الفارق الحالي بين مجموع الدفعات وتكلفة المشروع: ${diffFormatted} ريال. يجب أن يتطابق المجموع تماماً.`;
            mismatchAlert.classList.remove('d-none');
        }
    }

    // Add Row
    addRowBtn.addEventListener('click', function () {
        const rowsCount = tbody.querySelectorAll('.installment-row').length;
        const newIndex = rowsCount;
        const newRow = document.createElement('tr');
        newRow.className = 'installment-row';
        newRow.setAttribute('data-index', newIndex);

        newRow.innerHTML = `
            <td class="text-center">
                <span class="seq-badge row-seq">${newIndex + 1}</span>
                <input type="hidden" name="details[${newIndex}][sequence_number]" value="${newIndex + 1}" class="input-seq">
            </td>
            <td>
                <div class="input-group">
                    <input type="number" step="0.01" min="0.01"
                           name="details[${newIndex}][planned_amount]"
                           class="form-control fw-bold text-end amount-input"
                           placeholder="0.00" required>
                    <span class="input-group-text bg-light text-muted small">ريال</span>
                </div>
            </td>
            <td>
                <select name="details[${newIndex}][funding_source_id]" class="form-select mb-2">
                    <option value="">Funding source (optional)</option>
                    @foreach($fundingSources as $fundingSource)
                        <option value="{{ $fundingSource->id }}">{{ $fundingSource->name }}</option>
                    @endforeach
                </select>
                <textarea name="details[${newIndex}][disbursement_clearance_mechanism]"
                          rows="2" class="form-control mechanism-input"
                          placeholder="آلية الصرف والإخلاء لهذه الدفعة..." required></textarea>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle delete-row-btn" title="حذف الدفعة">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(newRow);
        renumberRows();
        calculateTotals();
    });

    // Delete Row
    tbody.addEventListener('click', function (e) {
        if (e.target.closest('.delete-row-btn')) {
            const rows = tbody.querySelectorAll('.installment-row');
            if (rows.length <= 1) {
                alert('يجب أن تحتوي خطة الصرف على دفعة واحدة على الأقل.');
                return;
            }
            e.target.closest('.installment-row').remove();
            renumberRows();
            calculateTotals();
        }
    });

    // Recalculate on input change
    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('amount-input')) {
            calculateTotals();
        }
    });

    // Initial calculation
    calculateTotals();
});
</script>
@endsection


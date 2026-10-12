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

    .kpi-stat-card {
        background: #ffffff;
        border-radius: var(--radius-md);
        padding: 1.25rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid #e2e8f0;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .kpi-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .table-plan {
        background: #ffffff;
        border-radius: var(--radius-md);
        overflow: hidden;
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
        padding: 0.85rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
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
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Alerts --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4 shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('warning') }}
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
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">خطة الصرف والدفعات</li>
        </ol>
    </nav>

    {{-- Plan Header Banner --}}
    <div class="plan-header-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge {{ $plan->statusBadgeClass() }} px-3 py-1 rounded-pill fw-bold">
                        <i class="fas fa-shield-alt me-1"></i> {{ $plan->statusLabel() }}
                    </span>
                    <span class="badge bg-light text-dark px-2 py-1 rounded-pill small">
                        <i class="fas fa-hashtag me-1"></i> كود الخطة: #{{ $plan->id }}
                    </span>
                </div>
                <h3 class="fw-bold mb-1">{{ $project->project_name }}</h3>
                <div class="small opacity-75">
                    <span><i class="fas fa-hashtag me-1"></i> رقم المشروع: <strong>{{ $project->form_number ?: $project->id }}</strong></span>
                    <span class="mx-2">•</span>
                    <span><i class="fas fa-building me-1"></i> الجهة المنشئة: {{ $project->creatorEntity?->name ?? 'غير محدد' }}</span>
                    @if($plan->approved_at)
                        <span class="mx-2">•</span>
                        <span><i class="fas fa-calendar-check me-1"></i> تاريخ الاعتماد: {{ $plan->approved_at->format('Y-m-d H:i') }}</span>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @if($plan->isDraft())
                    @if(auth()->user()->hasPermission('disbursements.create', $project))
                        <a href="{{ route('projects.disbursement-plan.create', $project) }}" class="btn btn-warning rounded-pill px-3 py-2 fw-bold shadow-sm">
                            <i class="fas fa-edit me-1"></i> تعديل الخطة
                        </a>
                    @endif
                    @if(auth()->user()->hasPermission('disbursements.approve', $project))
                        <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#approvePlanModal">
                            <i class="fas fa-check-circle me-1"></i> اعتماد الخطة وبدء الصرف
                        </button>
                    @endif
                @endif
                @if(auth()->user()->hasPermission('projects.execute', $project))
                    <a href="{{ route('projects.execution', $project) }}" class="btn btn-outline-light rounded-pill px-3 py-2 fw-bold">
                        <i class="fas fa-chart-line me-1"></i> شاشة التنفيذ
                    </a>
                @endif
            </div>
        </div>

        <div class="small text-white-50">
            <span>التكلفة التقديرية كتابةً: </span>
            <strong class="text-warning">{{ $arabicCostWords }}</strong>
        </div>
    </div>

    {{-- Financial KPI Cards Grid --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kpi-stat-card border-start border-primary border-4">
                <div class="small text-muted mb-1"><i class="fas fa-calculator text-primary me-1"></i> إجمالي تكلفة المشروع</div>
                <div class="fs-4 fw-bold text-dark">{{ number_format($projectTotalCost, 2) }} <small class="fs-6">ريال</small></div>
                <div class="small text-muted mt-1">المبلغ الإجمالي المعتمد للمشروع</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-stat-card border-start border-success border-4">
                <div class="small text-muted mb-1"><i class="fas fa-money-bill-wave text-success me-1"></i> إجمالي المصروف فعلياً</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($project->total_actually_disbursed, 2) }} <small class="fs-6">ريال</small></div>
                <div class="small text-muted mt-1">السقف المالي المتاح للصرف حالياً</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-stat-card border-start border-info border-4">
                <div class="small text-muted mb-1"><i class="fas fa-wallet text-info me-1"></i> المتاح للتنفيذ المالي</div>
                <div class="fs-4 fw-bold text-info">{{ number_format($project->available_for_execution, 2) }} <small class="fs-6">ريال</small></div>
                <div class="small text-muted mt-1">المتبقي من الدفعات المصروفة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-stat-card border-start border-warning border-4">
                <div class="small text-muted mb-1"><i class="fas fa-hourglass-half text-warning me-1"></i> المتبقي للصرف من المشروع</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($project->remaining_project_cost, 2) }} <small class="fs-6">ريال</small></div>
                <div class="small text-muted mt-1">الدفعات اللاحقة غير المصروفة</div>
            </div>
        </div>
    </div>

    {{-- Installments Master/Details Table Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-list-ol text-primary me-2"></i> جدول دفعات خطة الصرف (Installments Lifecycle)
                </h5>
                <small class="text-muted">مراحل الصرف المجدولة، حالة الدفعات، وأرقام قيود الصرف في ERPNext</small>
            </div>
            <div class="d-flex gap-2">
                @if(auth()->user()->hasPermission('payments.view', $project))
                <a href="{{ route('projects.disbursement-plan.payments.index', $project) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="fas fa-paper-plane me-1"></i> سجل طلبات الصرف
                </a>
                @endif
                @if(auth()->user()->hasPermission('clearances.view', $project))
                <a href="{{ route('projects.disbursement-plan.clearances.index', $project) }}" class="btn btn-outline-info btn-sm rounded-pill px-3">
                    <i class="fas fa-clipboard-check me-1"></i> سجل طلبات الإخلاء
                </a>
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-plan mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 70px;">م.</th>
                            <th style="width: 180px;">المبلغ المخطط (ريال)</th>
                            <th>آلية الصرف والإخلاء</th>
                            <th style="width: 150px;">حالة الدفعة</th>
                            <th style="width: 180px;">المصروف فعلياً</th>
                            <th style="width: 200px;">معلومات ERPNext</th>
                            <th style="width: 180px;" class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plan->details as $detail)
                            @php
                                $prevDetail = $loop->first ? null : $plan->details[$loop->index - 1];
                                $canRequestPayment = $plan->isApproved() && $detail->isPlanned() && $detail->sequence_number > 1 && $prevDetail && $prevDetail->isCleared();
                                $canRequestClearance = $plan->isApproved() && $detail->isDisbursed() && ! $detail->activeClearanceRequest;
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <span class="seq-badge">{{ $detail->sequence_number }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold fs-6 text-dark">{{ number_format($detail->planned_amount, 2) }}</span>
                                    <small class="text-muted d-block">ريال</small>
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $detail->disbursement_clearance_mechanism }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $detail->statusBadgeClass() }} px-3 py-1 rounded-pill">
                                        {{ $detail->statusLabel() }}
                                    </span>
                                    @if($detail->disbursed_at)
                                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                            <i class="fas fa-check me-1 text-success"></i> {{ $detail->disbursed_at->format('Y-m-d') }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if($detail->disbursed_amount > 0)
                                        <span class="fw-bold fs-6 text-success">{{ number_format($detail->disbursed_amount, 2) }}</span>
                                        <small class="text-muted d-block">ريال</small>
                                    @else
                                        <span class="text-muted small">لم تصرف بعد</span>
                                    @endif
                                </td>
                                <td>
                                    @if($detail->erpnext_payment_id)
                                        <div class="small">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                <i class="fas fa-check-circle me-1"></i> قيد: {{ $detail->erpnext_payment_id }}
                                            </span>
                                        </div>
                                    @elseif($detail->erpnext_payment_reference)
                                        <div class="small text-muted">
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">
                                                {{ $detail->erpnext_payment_reference }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{-- Retry Button for failed disbursement --}}
                                    @if($detail->isDisbursingFailed() && auth()->user()->hasPermission('disbursements.retry', $project))
                                        <form action="{{ route('projects.disbursement-plan.retry-disbursement', [$project, $detail]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" title="إعادة محاولة الصرف في ERPNext">
                                                <i class="fas fa-redo-alt me-1"></i> إعادة المحاولة
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Clearance Request CTA --}}
                                    @if($canRequestClearance && auth()->user()->hasPermission('clearances.create', $project))
                                        <a href="{{ route('projects.disbursement-plan.clearances.create', [$project, $detail]) }}" class="btn btn-warning btn-sm rounded-pill px-3 shadow-sm text-dark fw-bold">
                                            <i class="fas fa-receipt me-1"></i> طلب إخلاء
                                        </a>
                                    @elseif($detail->isClearanceRequested() && $detail->activeClearanceRequest && auth()->user()->hasPermission('clearances.view', $project))
                                        <a href="{{ route('projects.disbursement-plan.clearances.show', [$project, $detail->activeClearanceRequest]) }}" class="btn btn-outline-warning btn-sm rounded-pill px-3">
                                            <i class="fas fa-eye me-1"></i> عرض الإخلاء
                                        </a>
                                    @endif

                                    {{-- Subsequent Payment Request CTA --}}
                                    @if($canRequestPayment && (auth()->user()->hasPermission('payments.create', $project) || auth()->user()->hasPermission('disbursements.request', $project)))
                                        <a href="{{ route('projects.disbursement-plan.payments.create', [$project, $detail]) }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-bold">
                                            <i class="fas fa-hand-holding-usd me-1"></i> طلب صرف
                                        </a>
                                    @elseif($detail->isPaymentRequested() && $detail->activePaymentRequest && auth()->user()->hasPermission('payments.view', $project))
                                        <a href="{{ route('projects.disbursement-plan.payments.show', [$project, $detail->activePaymentRequest]) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <i class="fas fa-eye me-1"></i> عرض الطلب
                                        </a>
                                    @endif

                                    @if($detail->isCleared())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                                            <i class="fas fa-check-double me-1"></i> مخلاة بالكامل
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td class="text-center fw-bold">الإجمالي</td>
                            <td class="fw-bold fs-6 text-primary">{{ number_format($plan->total_planned_amount, 2) }} ريال</td>
                            <td colspan="2" class="text-muted small">
                                إجمالي المبالغ المخططة عبر جميع الدفعات
                            </td>
                            <td class="fw-bold fs-6 text-success">{{ number_format($project->total_actually_disbursed, 2) }} ريال</td>
                            <td colspan="2" class="text-muted small">
                                إجمالي المصروف الفعلي من خلال قيود ERPNext
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Approve Plan & Trigger Detail #1 --}}
@if($plan->isDraft() && auth()->user()->hasPermission('disbursements.approve', $project))
<div class="modal fade" id="approvePlanModal" tabindex="-1" aria-labelledby="approvePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('projects.disbursement-plan.approve', $project) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white rounded-top-4 py-3">
                    <h5 class="modal-title fw-bold" id="approvePlanModalLabel">
                        <i class="fas fa-check-double me-1"></i> تأكيد اعتماد خطة الصرف وبدء الصرف
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3 text-success">
                        <i class="fas fa-file-invoice-dollar fs-1"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">هل أنت متأكد من رغبتك في اعتماد خطة الصرف نهائياً؟</h6>
                    <p class="small text-muted mb-3">
                        عند الاعتماد، سيتم تجميد التكلفة الإجمالية للمشروع بشكل دائم، وسيتم إنشاء قيد صرف في نظام ERPNext تلقائياً للدفعة الأولى بمبلغ:
                    </p>
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <span class="fs-4 fw-bold text-primary">
                            {{ number_format($plan->details->first()?->planned_amount ?? 0, 2) }} ريال
                        </span>
                        <div class="small text-muted mt-1">الدفعة رقم 1</div>
                    </div>
                    <p class="small text-danger fw-semibold mb-0">
                        <i class="fas fa-info-circle me-1"></i> لن ينتقل المشروع لمرحلة التنفيذ الفعلي إلا بعد نجاح قيد الصرف وتأكيد اعتماده في ERPNext.
                    </p>
                </div>
                <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-check me-1"></i> تأكيد الاعتماد والصرف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection


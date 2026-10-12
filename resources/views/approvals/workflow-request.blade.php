@extends('layouts.app')

@section('title', 'تفاصيل طلب سير العمل المالي')

@section('content')
<div class="container py-4" dir="rtl">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-dark text-white p-4 rounded-top-4 d-flex justify-content-between align-items-center">
            <div>
                @php
                    $typeLabels = [
                        'clearance_request' => 'طلب إخلاء مالي',
                        'clearance' => 'طلب إخلاء مالي',
                        'payment_request' => 'طلب دفعة مالية',
                        'payment' => 'طلب دفعة مالية',
                        'next_installment' => 'طلب دفعة مالية',
                        'clearance_payment' => 'طلب إخلاء ودفعة مالية',
                        'clearance_closure' => 'طلب إخلاء وإغلاق مشروع',
                        'project_closure' => 'طلب إغلاق مشروع نهائي',
                        'financial_justification' => 'مبرر مالي لزيادة المصروفات',
                    ];
                    $label = $typeLabels[$workflowRequest->request_type] ?? str($workflowRequest->request_type)->replace('_', ' ')->title();
                    $unifiedRequestId = $workflowRequest->payload['unified_request_id'] ?? ($workflowRequest->requestable_type === 'App\\Models\\ProjectUnifiedRequest' ? $workflowRequest->requestable_id : null);
                @endphp
                <h3 class="fw-bold mb-1"><i class="fas fa-file-invoice-dollar text-info me-2"></i> {{ $label }}</h3>
                <div class="text-white-50">
                    رقم الطلب: <span class="font-monospace fw-bold text-light">{{ $workflowRequest->request_number }}</span>
                    · المشروع: <span class="fw-bold text-light">{{ $workflowRequest->project?->project_name }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($unifiedRequestId)
                    <a href="{{ route('projects.requests.show', $unifiedRequestId) }}" class="btn btn-outline-info rounded-pill btn-sm px-3 shadow-sm">
                        <i class="fas fa-external-link-alt me-1"></i> وثيقة الطلب الموحد
                    </a>
                @endif
                @if($workflowRequest->status === 'pending')
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill">قيد المراجعة</span>
                @elseif($workflowRequest->status === 'approved')
                    <span class="badge bg-success px-3 py-2 fs-6 rounded-pill">معتمد</span>
                @elseif($workflowRequest->status === 'rejected')
                    <span class="badge bg-danger px-3 py-2 fs-6 rounded-pill">مرفوض</span>
                @else
                    <span class="badge bg-secondary px-3 py-2 fs-6 rounded-pill">{{ $workflowRequest->status }}</span>
                @endif
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small">المقدم بواسطة</div>
                        <div class="fw-bold text-dark"><i class="fas fa-user me-1 text-primary"></i> {{ $workflowRequest->submitter?->name ?? 'غير محدد' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small">المراجِع الحالي</div>
                        <div class="fw-bold text-dark">
                            <i class="fas fa-user-shield me-1 text-success"></i>
                            {{ $activeStep?->assignedUser?->name ?? $activeStep?->entity?->name ?? $activeStep?->authority?->agency_name ?? 'تم استكمال الإجراء' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small">تاريخ التقديم</div>
                        <div class="fw-bold text-dark"><i class="fas fa-calendar-alt me-1 text-info"></i> {{ $workflowRequest->created_at?->format('Y-m-d H:i') }}</div>
                    </div>
                </div>
            </div>

            {{-- Structured Type-Specific Details --}}
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
                <i class="fas fa-list-alt text-primary me-2"></i> تفاصيل الطلب المالية
            </h5>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <tbody class="table-group-divider">
                        @foreach(($workflowRequest->payload ?? []) as $key => $value)
                            @if($key !== 'dedupe_key')
                                <tr>
                                    <th class="bg-light w-25 text-secondary">
                                        @php
                                            $translatedKeys = [
                                                'clearance_request_id' => 'رقم طلب الإخلاء',
                                                'payment_request_id' => 'رقم طلب الدفعة',
                                                'installment_number' => 'رقم الدفعة',
                                                'installment_amount' => 'مبلغ الدفعة',
                                                'amount' => 'المبلغ المطلوبة',
                                                'requested_amount' => 'المبلغ المطلوب',
                                                'available_balance' => 'الرصيد المتاح',
                                                'excess_amount' => 'المبلغ الزائد عن الميزانية',
                                                'aggregated_count' => 'عدد عمليات التنفيذ المجمعة',
                                                'aggregated_amount' => 'إجمالي الإنجاز المصروف',
                                                'notes' => 'ملاحظات وتبريرات',
                                                'justification' => 'المبرر المالي',
                                                'final_clearance_status' => 'حالة الإخلاء النهائي',
                                            ];
                                        @endphp
                                        {{ $translatedKeys[$key] ?? str($key)->replace('_', ' ')->title() }}
                                    </th>
                                    <td class="fw-semibold text-dark">
                                        @if(is_numeric($value) && (str_contains($key, 'amount') || str_contains($key, 'balance')))
                                            {{ number_format($value, 2) }} ر.س
                                        @elseif(is_scalar($value))
                                            {{ $value }}
                                        @else
                                            <code>{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</code>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Approval Chain History --}}
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
                <i class="fas fa-history text-secondary me-2"></i> سجل الاعتمادات والمراحل
            </h5>
            <ol class="list-group list-group-numbered mb-4">
                @foreach($workflowRequest->approvalSteps as $step)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <strong>{{ $step->stage_name ?? $step->getResolvedStageName() }}</strong>
                            @if($step->assignedUser)
                                — <span class="text-primary">{{ $step->assignedUser->name }}</span>
                            @endif
                        </span>
                        @if($step->status === 'approved')
                            <span class="badge bg-success">مقبول</span>
                        @elseif($step->status === 'rejected')
                            <span class="badge bg-danger">مرفوض</span>
                        @else
                            <span class="badge bg-warning text-dark">قيد الانتظار</span>
                        @endif
                    </li>
                @endforeach
            </ol>

            {{-- Approval Actions --}}
            @if($canApprove)
                <div class="card bg-light border-success mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold text-success mb-2"><i class="fas fa-check-circle me-1"></i> اعتماد الطلب</h6>
                        <form method="POST" action="{{ route('approvals.workflow-requests.approve', $workflowRequest) }}">
                            @csrf
                            <div class="mb-3">
                                <textarea class="form-control" name="notes" rows="2" placeholder="أدخل ملاحظات الاعتماد (اختياري)..."></textarea>
                            </div>
                            <button class="btn btn-success px-4 rounded-pill"><i class="fas fa-check me-1"></i> موافقة واعتماد</button>
                        </form>
                    </div>
                </div>
            @endif

            @if($canReject)
                <div class="card bg-light border-danger">
                    <div class="card-body">
                        <h6 class="fw-bold text-danger mb-2"><i class="fas fa-times-circle me-1"></i> رفض الطلب</h6>
                        <form method="POST" action="{{ route('approvals.workflow-requests.reject', $workflowRequest) }}">
                            @csrf
                            <div class="mb-3">
                                <textarea class="form-control" name="notes" rows="2" required placeholder="أدخل سبب الرفض (مطلوب)..."></textarea>
                            </div>
                            <button class="btn btn-danger px-4 rounded-pill"><i class="fas fa-times me-1"></i> رفض الطلب</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

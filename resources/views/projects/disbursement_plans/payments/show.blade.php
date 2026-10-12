@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted text-decoration-none">المشاريع</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}" class="text-muted text-decoration-none">{{ $project->project_name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.disbursement-plan.show', $project) }}" class="text-muted text-decoration-none">خطة الصرف</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.disbursement-plan.payments.index', $project) }}" class="text-muted text-decoration-none">طلبات الصرف</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">{{ $paymentRequest->request_number }}</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <span class="badge {{ $paymentRequest->statusBadgeClass() }} px-3 py-1 rounded-pill mb-1">
                            {{ $paymentRequest->statusLabel() }}
                        </span>
                        <h5 class="fw-bold mb-0 text-dark">
                            طلب صرف الدفعة رقم {{ $paymentRequest->installment?->sequence_number }}
                        </h5>
                    </div>
                    <span class="text-muted small">كود: <strong>{{ $paymentRequest->request_number }}</strong></span>
                </div>

                <div class="card-body p-4">
                    <div class="p-3 bg-light rounded-4 mb-4 border text-center">
                        <div class="small text-muted mb-1">المبلغ المطلوب صرفه</div>
                        <div class="fs-2 fw-bold text-primary mb-1">
                            {{ number_format($paymentRequest->requested_amount, 2) }} <small class="fs-5">ريال</small>
                        </div>
                        <div class="small text-muted fw-semibold">
                            {{ $arabicAmount }}
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="small text-muted d-block">مقدم الطلب</label>
                            <strong>{{ $paymentRequest->requestedBy?->name ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block">تاريخ التقديم</label>
                            <strong>{{ $paymentRequest->created_at->format('Y-m-d H:i') }}</strong>
                        </div>
                    </div>

                    @if($paymentRequest->notes)
                        <div class="mb-3">
                            <label class="small text-muted d-block">ملاحظات الطلب</label>
                            <div class="p-3 bg-white rounded-3 border small">{{ $paymentRequest->notes }}</div>
                        </div>
                    @endif

                    @if($paymentRequest->attachment_path)
                        <div class="mb-4">
                            <label class="small text-muted d-block">المرفقات</label>
                            <a href="{{ Storage::url($paymentRequest->attachment_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 mt-1">
                                <i class="fas fa-paperclip me-1"></i> تحميل / عرض المرفق
                            </a>
                        </div>
                    @endif

                    @if($paymentRequest->rejection_reason)
                        <div class="alert alert-danger rounded-4 mb-3">
                            <strong class="d-block mb-1"><i class="fas fa-times-circle me-1"></i> سبب الرفض:</strong>
                            <p class="mb-0 small">{{ $paymentRequest->rejection_reason }}</p>
                        </div>
                    @endif

                    {{-- Actions for Pending Request --}}
                    @if($paymentRequest->isPending())
                        <div class="pt-4 border-top d-flex flex-wrap gap-2 justify-content-between">
                            @if(auth()->user()->hasPermission('payments.reject', $project) || auth()->user()->hasPermission('disbursements.reject', $project))
                                <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="fas fa-times me-1"></i> رفض الطلب
                                </button>
                            @endif
                            <div class="d-flex gap-2">
                                @if(auth()->user()->hasPermission('payments.reject', $project) || auth()->user()->hasPermission('disbursements.reject', $project))
                                    <form action="{{ route('projects.disbursement-plan.payments.cancel', [$project, $paymentRequest]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary rounded-pill px-3" onclick="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟');">
                                            إلغاء الطلب
                                        </button>
                                    </form>
                                @endif
                                @if(auth()->user()->hasPermission('payments.approve', $project) || auth()->user()->hasPermission('disbursements.approve', $project))
                                    <form action="{{ route('projects.disbursement-plan.payments.approve', [$project, $paymentRequest]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                                            <i class="fas fa-check me-1"></i> اعتماد طلب الصرف وبدء التحويل (ERPNext)
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
@if($paymentRequest->isPending() && (auth()->user()->hasPermission('payments.reject', $project) || auth()->user()->hasPermission('disbursements.reject', $project)))
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('projects.disbursement-plan.payments.reject', [$project, $paymentRequest]) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white rounded-top-4 py-3">
                    <h5 class="modal-title fw-bold" id="rejectModalLabel">
                        <i class="fas fa-ban me-1"></i> رفض طلب الصرف
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        عند رفض طلب الصرف، ستعود الدفعة تلقائياً لحالة "مخطط" لإتاحة تقديم طلب مصحح لاحقاً، مع حفظ هذا السجل في الأرشيف للتدقيق.
                    </p>
                    <label class="form-label fw-bold small text-dark">سبب الرفض (إلزامي): <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control rounded-3" rows="3" required placeholder="اذكر سبب رفض طلب الصرف بالتفصيل..."></textarea>
                </div>
                <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">تراجع</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">تأكيد الرفض</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection


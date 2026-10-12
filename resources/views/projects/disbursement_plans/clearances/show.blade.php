@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted text-decoration-none">المشاريع</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}" class="text-muted text-decoration-none">{{ $project->project_name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.disbursement-plan.show', $project) }}" class="text-muted text-decoration-none">خطة الصرف</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.disbursement-plan.clearances.index', $project) }}" class="text-muted text-decoration-none">طلبات الإخلاء</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">{{ $clearanceRequest->request_number }}</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <span class="badge {{ $clearanceRequest->statusBadgeClass() }} px-3 py-1 rounded-pill mb-1">
                            {{ $clearanceRequest->statusLabel() }}
                        </span>
                        <h5 class="fw-bold mb-0 text-dark">
                            طلب إخلاء مالي للدفعة رقم {{ $clearanceRequest->installment?->sequence_number }}
                        </h5>
                    </div>
                    <span class="text-muted small">كود: <strong>{{ $clearanceRequest->request_number }}</strong></span>
                </div>

                <div class="card-body p-4">
                    {{-- Summary Box --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-4 border text-center">
                                <div class="small text-muted mb-1">مبلغ الدفعة المصروفة</div>
                                <div class="fs-4 fw-bold text-dark">{{ number_format($clearanceRequest->installment_disbursed_amount, 2) }} <small class="fs-6">ريال</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-4 border text-center">
                                <div class="small text-muted mb-1">إجمالي المبلغ المخلى في هذا الطلب</div>
                                <div class="fs-4 fw-bold text-success">{{ number_format($clearanceRequest->total_cleared_amount, 2) }} <small class="fs-6">ريال</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-4 border text-center">
                                <div class="small text-muted mb-1">نسبة الإنجاز الفني المحققة</div>
                                <div class="fs-4 fw-bold text-primary">{{ $clearanceRequest->technical_progress_percentage }}%</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3 border text-center small text-muted">
                        المبلغ كتابةً: <strong class="text-dark">{{ $arabicAmount }}</strong>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="small text-muted d-block">مقدم الطلب</label>
                            <strong>{{ $clearanceRequest->submittedBy?->name ?? '—' }}</strong>
                            </div>
                            <div class="col-md-6">
                            <label class="small text-muted d-block">تاريخ التقديم</label>
                            <strong>{{ $clearanceRequest->created_at->format('Y-m-d H:i') }}</strong>
                        </div>
                    </div>

                    @if($clearanceRequest->notes)
                        <div class="mb-3">
                            <label class="small text-muted d-block">ملاحظات الطلب</label>
                            <div class="p-3 bg-white rounded-3 border small">{{ $clearanceRequest->notes }}</div>
                        </div>
                    @endif

                    @if($clearanceRequest->attachment_path)
                        <div class="mb-4">
                            <label class="small text-muted d-block">المرفقات والوثائق</label>
                            <a href="{{ Storage::url($clearanceRequest->attachment_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 mt-1">
                                <i class="fas fa-paperclip me-1"></i> تحميل / عرض المرفق
                            </a>
                        </div>
                    @endif

                    @if($clearanceRequest->rejection_reason)
                        <div class="alert alert-danger rounded-4 mb-4">
                            <strong class="d-block mb-1"><i class="fas fa-times-circle me-1"></i> سبب الرفض:</strong>
                            <p class="mb-0 small">{{ $clearanceRequest->rejection_reason }}</p>
                        </div>
                    @endif

                    {{-- Immutable Snapshot Items Table --}}
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="fas fa-layer-group text-primary me-1"></i> بنود الإنجاز المدرجة في هذا الإخلاء (Immutable Snapshots):
                    </h6>
                    <div class="table-responsive border rounded-3 mb-4">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>نوع السجل</th>
                                    <th>معرف السجل</th>
                                    <th class="text-center">نسبة الإنجاز المجمدة</th>
                                    <th class="text-end">المبلغ المنصرف المجمد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clearanceRequest->items as $idx => $item)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            @if($item->execution_type === 'project_execution')
                                                <span class="badge bg-primary-subtle text-primary border">إنجاز تنفيذي</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info border">إنجاز تمهيدي</span>
                                            @endif
                                        </td>
                                        <td>#{{ $item->execution_id }}</td>
                                        <td class="text-center fw-bold">{{ $item->completion_percentage }}%</td>
                                        <td class="text-end fw-bold text-success">{{ number_format($item->amount_spent, 2) }} ريال</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Actions for Pending Request --}}
                    @if($clearanceRequest->isPending())
                        <div class="pt-4 border-top d-flex flex-wrap gap-2 justify-content-between">
                            @if(auth()->user()->hasPermission('clearances.reject', $project))
                                <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#rejectClearanceModal">
                                    <i class="fas fa-times me-1"></i> رفض طلب الإخلاء
                                </button>
                            @endif
                            <div class="d-flex gap-2">
                                @if(auth()->user()->hasPermission('clearances.reject', $project))
                                    <form action="{{ route('projects.disbursement-plan.clearances.cancel', [$project, $clearanceRequest]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary rounded-pill px-3" onclick="return confirm('هل أنت متأكد من إلغاء هذا الطلب وتحرير سجلات التنفيذ؟');">
                                            إلغاء الطلب
                                        </button>
                                    </form>
                                @endif
                                @if(auth()->user()->hasPermission('clearances.approve', $project))
                                    <form action="{{ route('projects.disbursement-plan.clearances.approve', [$project, $clearanceRequest]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                                            <i class="fas fa-check me-1"></i> اعتماد طلب الإخلاء وإخلاء الدفعة
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

{{-- Reject Clearance Modal --}}
@if($clearanceRequest->isPending() && auth()->user()->hasPermission('clearances.reject', $project))
<div class="modal fade" id="rejectClearanceModal" tabindex="-1" aria-labelledby="rejectClearanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('projects.disbursement-plan.clearances.reject', [$project, $clearanceRequest]) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white rounded-top-4 py-3">
                    <h5 class="modal-title fw-bold" id="rejectClearanceModalLabel">
                        <i class="fas fa-ban me-1"></i> رفض طلب الإخلاء المالي
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>ملاحظة هامة:</strong> رفض طلب الإخلاء سيقوم فوراً بتحرير جميع سجلات الإنجاز المالي والفني المرتبطة به لتمكين مقدم الطلب من تعديلها وتصحيحها وإعادة تقديم طلب إخلاء جديد، ولن يتم المساس بسقف الصرف أو مبلغ الدفعة.
                    </div>
                    <label class="form-label fw-bold small text-dark">سبب الرفض (إلزامي): <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control rounded-3" rows="3" required placeholder="اذكر أسباب عدم مطابقة الإنجاز أو نواقص الوثائق بالتفصيل..."></textarea>
                </div>
                <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">تراجع</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">تأكيد الرفض وتحرير السجلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

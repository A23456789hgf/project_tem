@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted text-decoration-none">المشاريع</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}" class="text-muted text-decoration-none">{{ $project->project_name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.disbursement-plan.show', $project) }}" class="text-muted text-decoration-none">خطة الصرف</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">سجل طلبات الإخلاء</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-clipboard-check text-info me-2"></i> سجل طلبات الإخلاء المالي (Clearance Requests)
                </h5>
                <small class="text-muted">متابعة إخلاء الدفعات المصروفة مقابل وثائق وسجلات الإنجاز المالي والفني</small>
            </div>
            <a href="{{ route('projects.disbursement-plan.show', $project) }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                <i class="fas fa-arrow-right me-1"></i> العودة لخطة الصرف
            </a>
        </div>

        <div class="card-body p-0">
            @if($clearanceRequests->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-receipt fs-1 d-block mb-3 opacity-50"></i>
                    <h6>لا توجد أي طلبات إخلاء مسجلة لهذا المشروع حتى الآن.</h6>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>رقم الطلب</th>
                                <th>الدفعة المراد إخلاؤها</th>
                                <th>مبلغ الدفعة</th>
                                <th>إجمالي المبلغ المخلى</th>
                                <th>نسبة الإنجاز الفني</th>
                                <th>الحالة</th>
                                <th>مقدم الطلب</th>
                                <th>تاريخ التقديم</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clearanceRequests as $req)
                                <tr>
                                    <td><strong>{{ $req->request_number }}</strong></td>
                                    <td>الدفعة رقم {{ $req->installment?->sequence_number }}</td>
                                    <td>{{ number_format($req->installment_disbursed_amount, 2) }} ريال</td>
                                    <td class="fw-bold text-success">{{ number_format($req->total_cleared_amount, 2) }} ريال</td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border px-2 py-1 rounded-pill">
                                            {{ $req->technical_progress_percentage }}%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $req->statusBadgeClass() }} px-3 py-1 rounded-pill">
                                            {{ $req->statusLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $req->submittedBy?->name ?? '—' }}</td>
                                    <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('projects.disbursement-plan.clearances.show', [$project, $req]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="fas fa-eye me-1"></i> عرض التفاصيل
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


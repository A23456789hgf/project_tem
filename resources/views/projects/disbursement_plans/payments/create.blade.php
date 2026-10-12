@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted text-decoration-none">المشاريع</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}" class="text-muted text-decoration-none">{{ $project->project_name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.disbursement-plan.show', $project) }}" class="text-muted text-decoration-none">خطة الصرف</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">تقديم طلب صرف الدفعة {{ $detail->sequence_number }}</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-primary text-white py-3 px-4 rounded-top-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-hand-holding-usd me-2"></i> تقديم طلب صرف للدفعة رقم {{ $detail->sequence_number }}
                    </h5>
                    <small class="text-white-50">تم إخلاء الدفعة السابقة بنجاح وأصبحت هذه الدفعة مؤهلة لطلب الصرف</small>
                </div>

                <form action="{{ route('projects.disbursement-plan.payments.store', [$project, $detail]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body p-4">
                        {{-- Amount Card --}}
                        <div class="p-3 bg-light rounded-4 mb-4 border text-center">
                            <div class="small text-muted mb-1">المبلغ المجدول المطلوب صرفه</div>
                            <div class="fs-2 fw-bold text-primary mb-1">
                                {{ number_format($detail->planned_amount, 2) }} <small class="fs-5">ريال</small>
                            </div>
                            <div class="small text-muted fw-semibold">
                                {{ $arabicAmount }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small">آلية الصرف والإخلاء المقررة في الخطة:</label>
                            <div class="p-3 bg-white rounded-3 border small text-secondary">
                                {{ $detail->disbursement_clearance_mechanism }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small">ملاحظات أو مبررات طلب الصرف (اختياري):</label>
                            <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="أدخل أي ملاحظات إضافية بخصوص طلب الصرف..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small">المرفقات والوثائق الداعمة (اختياري):</label>
                            <input type="file" name="attachment" class="form-control rounded-3">
                            <div class="form-text small">ملفات PDF، Word، أو صور (الحد الأقصى 10 ميجابايت).</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('projects.disbursement-plan.show', $project) }}" class="btn btn-outline-secondary rounded-pill px-4">
                                إلغاء والعودة
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                <i class="fas fa-paper-plane me-1"></i> إرسال طلب الصرف
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


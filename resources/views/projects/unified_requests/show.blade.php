@extends('layouts.app')

@section('title', 'تفاصيل الطلب | ' . $unifiedRequest->request_number)

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">

    {{-- Breadcrumb & Top Bar --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.requests.index') }}" class="text-decoration-none text-muted">إدارة الطلبات</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">{{ $unifiedRequest->request_number }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <h2 class="h4 fw-bold mb-0 text-dark">
                    طلب رقم: <span class="text-primary">{{ $unifiedRequest->request_number }}</span>
                </h2>
                <span class="badge {{ $unifiedRequest->typeBadgeClass() }} px-3 py-2 rounded-pill fs-6">
                    {{ $unifiedRequest->typeLabel() }}
                </span>
                <span class="badge {{ $unifiedRequest->statusBadgeClass() }} px-3 py-2 rounded-pill fs-6">
                    {{ $unifiedRequest->statusLabel() }}
                </span>
            </div>
        </div>

        {{-- Top Action Buttons --}}
        <div class="d-flex flex-wrap gap-2">
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('project_requests.print', $unifiedRequest->project) || auth()->user()->hasPermission('payments.view', $unifiedRequest->project) || auth()->user()->hasPermission('clearances.view', $unifiedRequest->project))
                <a href="{{ route('projects.requests.print', $unifiedRequest) }}" target="_blank"
                   class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fas fa-print me-1"></i> طباعة الوثيقة الرسمية
                </a>
            @endif

            {{-- 1. Actions for Draft Status --}}
            @if($unifiedRequest->isDraft() && (auth()->user()->isAdmin() || auth()->id() === $unifiedRequest->created_by || auth()->user()->hasPermission('project_requests.edit', $unifiedRequest->project) || auth()->user()->hasPermission('payments.create', $unifiedRequest->project) || auth()->user()->hasPermission('clearances.create', $unifiedRequest->project)))
                <a href="{{ route('projects.requests.edit', $unifiedRequest) }}" class="btn btn-outline-warning rounded-pill px-3 shadow-sm">
                    <i class="fas fa-edit me-1"></i> تعديل المسودة
                </a>
                <button type="button" class="btn btn-warning rounded-pill px-3 shadow-sm fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#confirmDraftModal">
                    <i class="fas fa-check-circle me-1"></i> تأكيد المسودة
                </button>
            @endif

            {{-- 2. Actions for Confirmed Draft Status --}}
            @if($unifiedRequest->isConfirmed() && (auth()->user()->isAdmin() || auth()->id() === $unifiedRequest->created_by || auth()->user()->hasPermission('project_requests.edit', $unifiedRequest->project) || auth()->user()->hasPermission('project_requests.submit', $unifiedRequest->project) || auth()->user()->hasPermission('payments.create', $unifiedRequest->project) || auth()->user()->hasPermission('clearances.create', $unifiedRequest->project)))
                <form action="{{ route('projects.requests.revert-draft', $unifiedRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('هل تريد إعادة الطلب إلى مسودة للتعديل عليها؟')">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                        <i class="fas fa-undo me-1"></i> إعادة للمسودة
                    </button>
                </form>
                <form action="{{ route('projects.requests.submit', $unifiedRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إرسال الطلب المؤكد للاعتماد وبدء مسار الموافقات؟')">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fas fa-paper-plane me-1"></i> إرسال للاعتماد
                    </button>
                </form>
            @endif

            {{-- 3. Actions for Requires Action Status --}}
            @if($unifiedRequest->status === 'requires_action' && (auth()->user()->isAdmin() || auth()->id() === $unifiedRequest->created_by || auth()->user()->hasPermission('project_requests.submit', $unifiedRequest->project) || auth()->user()->hasPermission('project_requests.edit', $unifiedRequest->project) || auth()->user()->hasPermission('payments.create', $unifiedRequest->project) || auth()->user()->hasPermission('clearances.create', $unifiedRequest->project)))
                <a href="{{ route('projects.requests.edit', $unifiedRequest) }}" class="btn btn-outline-warning rounded-pill px-3 shadow-sm">
                    <i class="fas fa-edit me-1"></i> تعديل واستكمال
                </a>
                <form action="{{ route('projects.requests.submit', $unifiedRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إعادة إرسال الطلب للاعتماد؟')">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-pill px-3 shadow-sm fw-bold">
                        <i class="fas fa-paper-plane me-1"></i> إعادة الإرسال للاعتماد
                    </button>
                </form>
            @endif

            {{-- 4. Actions for Pending Approver --}}
            @if($unifiedRequest->isPending() && $canActOnStep && $activeStep)
                @if($activeStep->isActionAllowed('approve'))
                    <button type="button" class="btn btn-success rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#approveStepModal">
                        <i class="fas fa-check-circle me-1"></i> اعتماد المرحلة الحالية
                    </button>
                @endif
                @if($activeStep->isActionAllowed('return'))
                    <button type="button" class="btn btn-warning rounded-pill px-3 shadow-sm fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#returnStepModal">
                        <i class="fas fa-undo me-1"></i> طلب إجراء / استكمال
                    </button>
                @endif
                @if($activeStep->isActionAllowed('reject'))
                    <button type="button" class="btn btn-danger rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#rejectStepModal">
                        <i class="fas fa-times-circle me-1"></i> رفض الطلب
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Unified Request Lifecycle Tracker --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 text-center">
                {{-- Step 1: Draft --}}
                <div class="flex-fill p-2 rounded-3 {{ $unifiedRequest->isDraft() ? 'bg-secondary-subtle border border-secondary text-dark fw-bold' : 'bg-success-subtle text-success fw-semibold' }}">
                    <i class="fas {{ $unifiedRequest->isDraft() ? 'fa-pencil-alt' : 'fa-check-circle' }} me-1"></i>
                    1. مسودة الطلب
                    @if(! $unifiedRequest->isDraft())
                        <div class="small text-muted" style="font-size: 0.72rem;">تم الإنشاء بنجاح</div>
                    @else
                        <div class="small text-muted" style="font-size: 0.72rem;">بانتظار التأكيد</div>
                    @endif
                </div>
                <i class="fas fa-chevron-left text-muted d-none d-md-block"></i>

                {{-- Step 2: Confirmed --}}
                <div class="flex-fill p-2 rounded-3 {{ $unifiedRequest->isConfirmed() ? 'bg-primary-subtle border border-primary text-primary fw-bold' : ($unifiedRequest->isDraft() ? 'bg-light text-muted' : 'bg-success-subtle text-success fw-semibold') }}">
                    <i class="fas {{ $unifiedRequest->isConfirmed() ? 'fa-clipboard-check' : ($unifiedRequest->isDraft() ? 'fa-clock' : 'fa-check-circle') }} me-1"></i>
                    2. تأكيد المسودة
                    @if($unifiedRequest->isConfirmed())
                        <div class="small text-primary" style="font-size: 0.72rem;">مؤكدة (جاهزة للإرسال)</div>
                    @elseif($unifiedRequest->isDraft())
                        <div class="small text-muted" style="font-size: 0.72rem;">لم يتم التأكيد بعد</div>
                    @else
                        <div class="small text-muted" style="font-size: 0.72rem;">تم التأكيد</div>
                    @endif
                </div>
                <i class="fas fa-chevron-left text-muted d-none d-md-block"></i>

                {{-- Step 3: Approval Stages --}}
                <div class="flex-fill p-2 rounded-3 {{ $unifiedRequest->isPending() ? 'bg-warning-subtle border border-warning text-dark fw-bold' : ($unifiedRequest->isApproved() ? 'bg-success-subtle text-success fw-semibold' : ($unifiedRequest->isRejected() ? 'bg-danger-subtle text-danger fw-semibold' : 'bg-light text-muted')) }}">
                    <i class="fas {{ $unifiedRequest->isPending() ? 'fa-spinner fa-spin text-warning' : ($unifiedRequest->isApproved() ? 'fa-check-circle' : ($unifiedRequest->isRejected() ? 'fa-times-circle' : 'fa-paper-plane')) }} me-1"></i>
                    3. مركز الاعتمادات
                    @if($unifiedRequest->isPending())
                        <div class="small text-warning-emphasis" style="font-size: 0.72rem;">قيد المراجعة الرسمية</div>
                    @elseif($unifiedRequest->isApproved() || $unifiedRequest->isRejected())
                        <div class="small text-muted" style="font-size: 0.72rem;">اكتمل المسار</div>
                    @else
                        <div class="small text-muted" style="font-size: 0.72rem;">بانتظار الإرسال</div>
                    @endif
                </div>
                <i class="fas fa-chevron-left text-muted d-none d-md-block"></i>

                {{-- Step 4: Final Decision --}}
                <div class="flex-fill p-2 rounded-3 {{ $unifiedRequest->isApproved() ? 'bg-success text-white fw-bold' : ($unifiedRequest->isRejected() ? 'bg-danger text-white fw-bold' : 'bg-light text-muted') }}">
                    <i class="fas {{ $unifiedRequest->isApproved() ? 'fa-stamp' : ($unifiedRequest->isRejected() ? 'fa-ban' : 'fa-flag-checkered') }} me-1"></i>
                    4. {{ $unifiedRequest->isApproved() ? 'معتمد نهائياً' : ($unifiedRequest->isRejected() ? 'مرفوض' : 'الاعتماد النهائي') }}
                    @if($unifiedRequest->isApproved())
                        <div class="small text-white-50" style="font-size: 0.72rem;">تم التنفيذ المالي والإداري</div>
                    @elseif($unifiedRequest->isRejected())
                        <div class="small text-white-50" style="font-size: 0.72rem;">تم رفض الطلب</div>
                    @else
                        <div class="small text-muted" style="font-size: 0.72rem;">المرحلة الختامية</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Right / Main Column --}}
        <div class="col-lg-8">

            {{-- Mandatory Submitting Information Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-primary border-4">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="fas fa-id-card me-2"></i> بيانات الجهة والمستخدم مقدم الطلب (معلومات إلزامية)
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="text-muted small fw-semibold">الجهة المقدمة للطلب (Submitting Entity)</div>
                                <div class="h5 fw-bold text-dark mt-1 mb-0">
                                    <i class="fas fa-building text-primary me-2"></i>
                                    {{ $unifiedRequest->submitting_organization_name }}
                                </div>
                                <div class="text-muted small mt-1">
                                    نوع الجهة: {{ $unifiedRequest->submitting_type === 'external' ? 'جهة خارجية معتمدة' : 'جهة داخلية' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="text-muted small fw-semibold">المستخدم مقدم الطلب (Submitting User)</div>
                                <div class="h5 fw-bold text-dark mt-1 mb-0">
                                    <i class="fas fa-user-check text-success me-2"></i>
                                    {{ $unifiedRequest->submitting_user_name }}
                                </div>
                                @if($unifiedRequest->submittingUser?->work)
                                    <div class="text-muted small mt-1">الصفة / الوظيفة: {{ $unifiedRequest->submittingUser->work }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <span class="text-muted small d-block">مدخل الطلب في النظام:</span>
                            <strong class="text-dark">{{ $unifiedRequest->creator?->name ?: '—' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">تاريخ تقديم الطلب:</span>
                            <strong class="text-dark">{{ $unifiedRequest->request_date?->format('Y-m-d') }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">تاريخ الإنشاء الفعلي:</span>
                            <span class="text-muted">{{ $unifiedRequest->created_at?->format('Y-m-d H:i') }}</span>
                        </div>

                        @if($unifiedRequest->description)
                            <div class="col-12 mt-3 pt-3 border-top">
                                <span class="text-muted small d-block mb-1">بيان الطلب / الموضوع:</span>
                                <div class="p-3 bg-light rounded-3 text-dark">{{ $unifiedRequest->description }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Clearance Financial Component (If Applicable) --}}
            @if($unifiedRequest->hasClearanceComponent())
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-warning border-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-warning">
                            <i class="fas fa-clipboard-check me-2"></i> تفاصيل إخلاء العهدة المالية
                        </h5>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 rounded-pill">
                            الدفعة {{ $unifiedRequest->clearanceInstallment?->sequence_number ?: 'الحالية' }}
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 text-center border">
                                    <div class="text-muted small fw-semibold">مبلغ العهدة الأصلي</div>
                                    <div class="h5 fw-bold text-dark mt-1 mb-0">{{ number_format($unifiedRequest->original_advance_amount, 2) }} ريال</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-warning-subtle rounded-3 text-center border border-warning-subtle">
                                    <div class="text-warning-emphasis small fw-semibold">المبلغ المقدم للإخلاء</div>
                                    <div class="h5 fw-bold text-warning-emphasis mt-1 mb-0">{{ number_format($unifiedRequest->cleared_amount, 2) }} ريال</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 text-center border">
                                    <div class="text-muted small fw-semibold">المبلغ المتبقي من العهدة</div>
                                    <div class="h5 fw-bold text-dark mt-1 mb-0">{{ number_format($unifiedRequest->remaining_advance_amount, 2) }} ريال</div>
                                </div>
                            </div>

                            @if($arabicClearedAmount)
                                <div class="col-12">
                                    <div class="p-2 bg-light rounded-3 small text-muted">
                                        <i class="fas fa-pen-fancy me-1"></i> تفقيط المبلغ المخلى: <strong>{{ $arabicClearedAmount }}</strong>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-4">
                                <span class="text-muted small d-block">مستلم العهدة:</span>
                                <strong class="text-dark">{{ $unifiedRequest->advance_recipient ?: '—' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">تاريخ استلام العهدة:</span>
                                <strong class="text-dark">{{ $unifiedRequest->advance_receipt_date?->format('Y-m-d') ?: '—' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">نسبة الإنجاز الفني:</span>
                                <span class="badge bg-primary-subtle text-primary border px-2 py-1 rounded-pill fw-bold">
                                    {{ $unifiedRequest->technical_progress_percentage }}%
                                </span>
                            </div>

                            @if($unifiedRequest->clearance_description)
                                <div class="col-12 mt-3 pt-3 border-top">
                                    <span class="text-muted small d-block mb-1">بيان الإخلاء والمصروفات:</span>
                                    <div class="p-3 bg-light rounded-3 text-dark">{{ $unifiedRequest->clearance_description }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Payment Financial Component (If Applicable) --}}
            @if($unifiedRequest->hasPaymentComponent())
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-success border-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-success">
                            <i class="fas fa-hand-holding-usd me-2"></i> تفاصيل طلب صرف الدفعة
                        </h5>
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-1 rounded-pill">
                            دفعة رقم {{ $unifiedRequest->payment_number ?: ($unifiedRequest->paymentInstallment?->sequence_number ?: '—') }}
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-success-subtle rounded-3 text-center border border-success-subtle">
                                    <div class="text-success-emphasis small fw-semibold">المبلغ المطلوب صرفه</div>
                                    <div class="h4 fw-bold text-success mt-1 mb-0">{{ number_format($unifiedRequest->requested_payment_amount, 2) }} ريال</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 text-center border">
                                    <div class="text-muted small fw-semibold">مستلم الدفعة</div>
                                    <div class="h5 fw-bold text-dark mt-1 mb-0">{{ $unifiedRequest->payment_recipient ?: '—' }}</div>
                                </div>
                            </div>

                            @if($arabicPaymentAmount)
                                <div class="col-12">
                                    <div class="p-2 bg-light rounded-3 small text-muted">
                                        <i class="fas fa-pen-fancy me-1"></i> تفقيط المبلغ المطلوب: <strong>{{ $arabicPaymentAmount }}</strong>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-6">
                                <span class="text-muted small d-block">تاريخ الإخلاء المتوقع:</span>
                                <strong class="text-dark">{{ $unifiedRequest->expected_clearance_date?->format('Y-m-d') ?: '—' }}</strong>
                            </div>

                            @if($unifiedRequest->payment_justification)
                                <div class="col-12 mt-3 pt-3 border-top">
                                    <span class="text-muted small d-block mb-1">مبررات واحتياجات طلب الدفعة:</span>
                                    <div class="p-3 bg-light rounded-3 text-dark">{{ $unifiedRequest->payment_justification }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Closure Component (If Applicable) --}}
            @if($unifiedRequest->hasClosureComponent())
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-dark border-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-lock me-2"></i> بيانات الإغلاق المالي والإداري للمشروع
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">تاريخ اكتمال أنشطة المشروع:</span>
                                <strong class="text-dark">{{ $unifiedRequest->project_completion_date?->format('Y-m-d') ?: '—' }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">ملاحظات التسوية الختامية:</span>
                                <strong class="text-dark">{{ $unifiedRequest->final_settlement_notes ?: 'تمت التسوية' }}</strong>
                            </div>
                            <div class="col-12 mt-3 pt-3 border-top">
                                <span class="text-muted small d-block mb-1">مبررات وتفاصيل طلب الإغلاق:</span>
                                <div class="p-3 bg-light rounded-3 text-dark">{{ $unifiedRequest->closure_justification }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Attachments & Documents --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-paperclip me-2"></i> المستندات والوثائق المؤيدة
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($unifiedRequest->attachment_path)
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-pdf fs-3 text-danger me-3"></i>
                                <div>
                                    <div class="fw-bold text-dark">ملف الوثائق المؤيدة للطلب</div>
                                    <div class="text-muted small">تم إرفاقه أثناء تقديم الطلب</div>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $unifiedRequest->attachment_path) }}" target="_blank"
                               class="btn btn-primary btn-sm rounded-pill px-4">
                                <i class="fas fa-download me-1"></i> تحميل المستند
                            </a>
                        </div>
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-file-excel fs-4 d-block mb-2 opacity-50"></i>
                            لا توجد مستندات مرفقة مع هذا الطلب.
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Left / Sidebar Column --}}
        <div class="col-lg-4">

            {{-- Project Information Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-project-diagram text-primary me-2"></i> بيانات المشروع
                    </h5>
                </div>
                <div class="card-body p-4">
                    <h6 class="fw-bold text-primary mb-1">
                        @if(auth()->user()->hasPermission('disbursements.view', $unifiedRequest->project))
                            <a href="{{ route('projects.disbursement-plan.show', $unifiedRequest->project) }}" class="text-decoration-none text-primary">
                                {{ $unifiedRequest->project?->project_name }}
                            </a>
                        @else
                            <a href="{{ route('projects.show', $unifiedRequest->project) }}" class="text-decoration-none text-primary">
                                {{ $unifiedRequest->project?->project_name }}
                            </a>
                        @endif
                    </h6>
                    <div class="text-muted small mb-3">
                        رمز النموذج: <span class="badge bg-light text-dark border">{{ $unifiedRequest->project?->form_number ?: ('#'.$unifiedRequest->project_id) }}</span>
                    </div>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">موازنة المشروع:</span>
                            <strong>{{ number_format($unifiedRequest->project?->total_cost ?: 0, 2) }} ريال</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">المنصرف حتى الآن:</span>
                            <strong class="text-info">{{ number_format($unifiedRequest->project?->total_actually_disbursed ?: 0, 2) }} ريال</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">المنفذ مالياً:</span>
                            <strong class="text-success">{{ number_format($unifiedRequest->project?->total_financial_execution ?: 0, 2) }} ريال</strong>
                        </li>
                    </ul>

                    @if(auth()->user()->hasPermission('disbursements.view', $unifiedRequest->project))
                    <div class="mt-3 text-center">
                        <a href="{{ route('projects.disbursement-plan.show', $unifiedRequest->project) }}"
                           class="btn btn-outline-primary btn-sm rounded-pill w-100">
                            <i class="fas fa-list-ol me-1"></i> عرض جدول خطة الصرف والدفعات
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ERPNext Payment Entry Synchronization Status Card --}}
            @if($unifiedRequest->hasPaymentComponent())
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-top border-info border-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-sync-alt text-info me-2"></i> ربط ERPNext (Payment Entry)
                        </h6>
                        @php
                            $syncStatus = $unifiedRequest->erpnext_sync_status ?: 'pending';
                            $syncBadge = match($syncStatus) {
                                'submitted' => 'bg-success text-white',
                                'draft_created' => 'bg-info text-dark',
                                'awaiting_accounting_configuration' => 'bg-secondary text-white',
                                'reconciliation_required' => 'bg-warning text-dark',
                                default => 'bg-light text-muted border'
                            };
                            $syncLabel = match($syncStatus) {
                                'submitted' => 'معمد ومرحل (docstatus=1)',
                                'draft_created' => 'مسودة منشأة (docstatus=0)',
                                'awaiting_accounting_configuration' => 'بانتظار تهيئة الحسابات',
                                'reconciliation_required' => 'مطلوب المطابقة',
                                default => 'لم يُنشأ بعد'
                            };
                        @endphp
                        <span class="badge {{ $syncBadge }} rounded-pill px-3 py-1 small">
                            {{ $syncLabel }}
                        </span>
                    </div>
                    <div class="card-body p-4 small">
                        <ul class="list-group list-group-flush mb-0">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">معرّف القيد (ERPNext ID):</span>
                                <strong class="text-dark">{{ $unifiedRequest->erpnext_payment_id ?: '—' }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">الرقم المرجعي (Reference):</span>
                                <code class="text-primary">{{ $unifiedRequest->erpnext_payment_reference ?: '—' }}</code>
                            </li>
                            @if($unifiedRequest->erpnext_synced_at)
                                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                    <span class="text-muted">تاريخ آخر مزامنة:</span>
                                    <span>{{ $unifiedRequest->erpnext_synced_at->format('Y-m-d H:i') }}</span>
                                </li>
                            @endif
                        </ul>

                        @if($unifiedRequest->erpnext_sync_error)
                            <div class="alert alert-warning p-2 mt-3 rounded-3 mb-0 small">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <strong>تنبيه المطابقة:</strong> {{ $unifiedRequest->erpnext_sync_error }}
                            </div>
                        @endif

                        <div class="text-muted smaller mt-3 pt-2 border-top">
                            <i class="fas fa-info-circle me-1 text-info"></i>
                            يتم إنشاء القيد في ERPNext كمسودة <code>docstatus=0</code> عند إرسال الطلب، ولا يتم تعميده <code>docstatus=1</code> إلا بعد اكتمال كافة مراحل مسار الاعتماد النهائي.
                        </div>
                    </div>
                </div>
            @endif

            {{-- Multi-Stage Approval Workflow Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-top border-primary border-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-tasks text-primary me-2"></i> مسار ومراحل الاعتماد
                    </h5>
                    <span class="badge {{ $unifiedRequest->statusBadgeClass() }} px-3 py-1 rounded-pill">
                        {{ $unifiedRequest->currentStageName() }}
                    </span>
                </div>
                <div class="card-body p-4">
                    @if($unifiedRequest->isApproved())
                        <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle small mb-4">
                            <div class="fw-bold text-success fs-6"><i class="fas fa-check-double me-1"></i> تم اعتماد الطلب نهائياً بالكامل</div>
                            <div class="text-muted mt-1">المعتمد النهائي: <strong>{{ $unifiedRequest->approvedBy?->name ?: 'الإدارة المالية' }}</strong></div>
                            <div class="text-muted">تاريخ الاعتماد: <strong>{{ $unifiedRequest->approved_at?->format('Y-m-d H:i') }}</strong></div>
                        </div>
                    @elseif($unifiedRequest->isRejected())
                        <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle small mb-4">
                            <div class="fw-bold text-danger fs-6"><i class="fas fa-times-circle me-1"></i> تم رفض الطلب في مسار الاعتمادات</div>
                            <div class="text-muted mt-1">المراجع: <strong>{{ $unifiedRequest->approvedBy?->name ?: 'الإدارة' }}</strong></div>
                            @if($unifiedRequest->rejection_reason)
                                <div class="text-danger mt-1 fw-semibold">سبب الرفض: {{ $unifiedRequest->rejection_reason }}</div>
                            @endif
                        </div>
                    @endif

                    @if($approvalSteps && $approvalSteps->isNotEmpty())
                        <div class="approval-stepper">
                            @foreach($approvalSteps as $step)
                                @php
                                    $stepState = match($step->status) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'requires_action' => 'warning',
                                        'pending' => 'primary',
                                        default => 'secondary'
                                    };
                                    $stepLabel = match($step->status) {
                                        'approved' => 'معتمد',
                                        'rejected' => 'مرفوض',
                                        'requires_action' => 'يتطلب إجراء',
                                        'pending' => 'قيد المراجعة',
                                        default => 'مغلق'
                                    };
                                @endphp
                                <div class="approval-step-item mb-3 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <div class="badge bg-{{ $stepState }} rounded-circle shadow-sm d-flex align-items-center justify-content-center text-white"
                                                     style="width: 36px; height: 36px; font-size: 0.95rem; font-weight: bold;">
                                                    @if($step->status === 'approved')
                                                        <i class="fas fa-check"></i>
                                                    @elseif($step->status === 'rejected')
                                                        <i class="fas fa-times"></i>
                                                    @elseif($step->status === 'requires_action')
                                                        <i class="fas fa-exclamation"></i>
                                                    @else
                                                        {{ $step->step_order }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-1">
                                                    {{ $step->stage_name ?: ('المرحلة ' . $step->step_order) }}
                                                </h6>
                                                <div class="text-muted small">
                                                    الجهة:
                                                    <span class="text-dark fw-semibold">
                                                        {{ $step->entity?->name ?: ($step->authority?->name ?: 'جهة التقديم / الشؤون المالية') }}
                                                    </span>
                                                    @if($step->assignedUser)
                                                        <span class="ms-1 text-secondary">({{ $step->assignedUser->name }})</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge bg-{{ $stepState }}-subtle text-{{ $stepState }} border border-{{ $stepState }}-subtle px-2 py-1 rounded-pill small">
                                                {{ $stepLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Step Execution Details --}}
                                    @if($step->reviewed_at || $step->notes || $step->required_action || $step->rejection_reason)
                                        <div class="mt-2 pe-5 ps-2">
                                            <div class="p-2 bg-light rounded-3 border small">
                                                @if($step->reviewedByUser)
                                                    <div class="text-muted mb-1">
                                                        <i class="fas fa-user-check text-primary me-1"></i>
                                                        المراجع: <strong>{{ $step->reviewedByUser->name }}</strong>
                                                        في <strong>{{ $step->reviewed_at?->format('Y-m-d H:i') }}</strong>
                                                    </div>
                                                @endif
                                                @if($step->required_action)
                                                    <div class="text-warning-emphasis fw-semibold mb-1">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> إجراء مطلوب: {{ $step->required_action }}
                                                    </div>
                                                @endif
                                                @if($step->rejection_reason)
                                                    <div class="text-danger fw-semibold mb-1">
                                                        <i class="fas fa-times me-1"></i> سبب الرفض: {{ $step->rejection_reason }}
                                                    </div>
                                                @endif
                                                @if($step->notes)
                                                    <div class="text-muted">
                                                        <i class="fas fa-comment-dots me-1"></i> ملاحظات: {{ $step->notes }}
                                                    </div>
                                                @endif
                                                @if($step->attachment)
                                                    <div class="mt-1">
                                                        <a href="{{ Storage::url($step->attachment) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" style="font-size: 0.75rem;">
                                                            <i class="fas fa-paperclip me-1"></i> المرفق
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted small text-center py-3">
                            <i class="fas fa-info-circle me-1"></i> لم يتم توليد مراحل الاعتماد بعد، سيتم تفعيلها تلقائياً عند تقديم الطلب.
                        </div>
                    @endif

                    {{-- Decision Panel for Current Active Step --}}
                    @if($unifiedRequest->isPending() && $canActOnStep && $activeStep)
                        <div class="card bg-primary-subtle border-primary mt-3 rounded-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-gavel text-primary fs-5 me-2"></i>
                                    <div class="fw-bold text-primary">صلاحية اتخاذ القرار على المرحلة الحالية</div>
                                </div>
                                <p class="small text-muted mb-3">
                                    المرحلة الحالية: <strong>{{ $activeStep?->stage_name }}</strong>. يرجى اختيار الإجراء المناسب:
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    @if($activeStep->isActionAllowed('approve'))
                                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold flex-fill"
                                                data-bs-toggle="modal" data-bs-target="#approveStepModal">
                                            <i class="fas fa-check-circle me-1"></i> اعتماد المرحلة
                                        </button>
                                    @endif
                                    @if($activeStep->isActionAllowed('return'))
                                        <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark flex-fill"
                                                data-bs-toggle="modal" data-bs-target="#returnStepModal">
                                            <i class="fas fa-undo me-1"></i> طلب إجراء / استكمال
                                        </button>
                                    @endif
                                    @if($activeStep->isActionAllowed('reject'))
                                        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold flex-fill"
                                                data-bs-toggle="modal" data-bs-target="#rejectStepModal">
                                            <i class="fas fa-times-circle me-1"></i> رفض الطلب
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>

{{-- Step Decision Modals --}}
@if($unifiedRequest->isPending() && $canActOnStep && $activeStep && $activeStep->isActionAllowed('approve'))
    {{-- Modal: Approve Current Step --}}
    <div class="modal fade" id="approveStepModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('projects.requests.steps.approve', $unifiedRequest) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="fas fa-check-circle me-2"></i> اعتماد: {{ $activeStep?->stage_name ?: 'المرحلة الحالية' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted">
                            أنت على وشك الموافقة على الخطوة رقم <strong class="text-dark">{{ $activeStep?->step_order }}</strong>
                            من طلب المشروع رقم <strong class="text-dark">{{ $unifiedRequest->request_number }}</strong>.
                        </p>
                        @php
                            $isLastStep = $approvalSteps && $activeStep && $activeStep->step_order === $approvalSteps->max('step_order');
                        @endphp
                        @if($isLastStep)
                            <div class="alert alert-warning small py-2 mb-3">
                                <i class="fas fa-exclamation-triangle me-1"></i> <strong>تنبيه:</strong> هذه هي المرحلة النهائية! الموافقة عليها ستعتمد الطلب نهائياً وتنفذ العمليات المالية في النظام.
                            </div>
                        @else
                            <div class="alert alert-info small py-2 mb-3">
                                <i class="fas fa-info-circle me-1"></i> الموافقة ستنقل الطلب تلقائياً إلى المرحلة التالية في مسار الاعتمادات.
                            </div>
                        @endif

                        @if($activeStep && !empty($activeStep->required_attachments))
                            <div class="mb-3 p-3 bg-light rounded-3 border">
                                <label class="form-label small fw-bold text-dark mb-2">
                                    <i class="fas fa-paperclip text-primary me-1"></i> المرفقات المطلوبة في هذه المرحلة:
                                </label>
                                <ul class="list-unstyled mb-0">
                                    @foreach($activeStep->required_attachments as $reqAtt)
                                        @php
                                            $isUploaded = isset($activeStep->uploaded_attachments[$reqAtt['key']]);
                                        @endphp
                                        <li class="d-flex align-items-center justify-content-between py-1 border-bottom small">
                                            <span>
                                                <i class="fas {{ $isUploaded ? 'fa-check-circle text-success' : ($reqAtt['required'] ? 'fa-exclamation-circle text-danger' : 'fa-circle text-muted') }} me-1"></i>
                                                {{ $reqAtt['label'] }}
                                                @if(!empty($reqAtt['required'])) <span class="badge bg-danger-subtle text-danger px-1">إلزامي</span> @endif
                                            </span>
                                            @if($isUploaded)
                                                <span class="badge bg-success-subtle text-success">تم الرفع</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">بانتظار الرفع</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="approveStepNotes" class="form-label small fw-semibold">ملاحظات الاعتماد (اختياري):</label>
                            <textarea name="notes" id="approveStepNotes" rows="2" class="form-control" placeholder="أي ملاحظات حول اعتماد هذه المرحلة..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="approveStepAttachment" class="form-label small fw-semibold">مرفق توثيقي (اختياري):</label>
                            <input type="file" name="attachment" id="approveStepAttachment" class="form-control form-control-sm" accept=".pdf,.png,.jpg,.jpeg">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                            <i class="fas fa-check me-1"></i> تأكيد اعتماد المرحلة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Return Current Step --}}
    <div class="modal fade" id="returnStepModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('projects.requests.steps.return', $unifiedRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-warning">
                            <i class="fas fa-undo me-2"></i> طلب إجراء / استكمال متطلبات
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted">
                            سيتم تحويل حالة الطلب إلى مسودة لإتاحة تعديل البيانات أو استكمال النواقص من قبل مقدم الطلب.
                        </p>
                        <div class="mb-3">
                            <label for="actionRequired" class="form-label small fw-semibold required">
                                الإجراء المطلوب أو أسباب الإعادة <span class="text-danger">*</span>
                            </label>
                            <textarea name="action_required" id="actionRequired" rows="3" class="form-control" required
                                      placeholder="وضح بالتفصيل الإجراء المطلوب أو النواقص التي تتطلب الاستكمال..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">
                            <i class="fas fa-paper-plane me-1"></i> تأكيد الإعادة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Reject Current Step --}}
    <div class="modal fade" id="rejectStepModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('projects.requests.steps.reject', $unifiedRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="fas fa-times-circle me-2"></i> تأكيد رفض الطلب
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted">
                            أنت على وشك رفض الطلب نهائياً وإيقاف مسار الاعتمادات. يرجى توثيق سبب الرفض.
                        </p>
                        <div class="mb-3">
                            <label for="stepRejectionReason" class="form-label small fw-semibold required">
                                سبب الرفض <span class="text-danger">*</span>
                            </label>
                            <textarea name="rejection_reason" id="stepRejectionReason" rows="3" class="form-control" required
                                      placeholder="أدخل سبب الرفض بالتفصيل..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                            <i class="fas fa-times me-1"></i> تأكيد رفض الطلب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Direct Approval Modals (for Admin override or requests without multi-step chains) --}}
@if($unifiedRequest->isPending() && auth()->user()->isAdmin())
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('projects.requests.approve', $unifiedRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="fas fa-check-circle me-2"></i> اعتماد الطلب المباشر (صلاحية المشرف)
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted">
                            أنت على وشك اعتماد طلب رقم <strong class="text-dark">{{ $unifiedRequest->request_number }}</strong>
                            بشكل مباشر وتجاوز المراحل المتبقية.
                        </p>
                        <div class="mb-3">
                            <label for="approveNotes" class="form-label small fw-semibold">ملاحظات الاعتماد (اختياري):</label>
                            <textarea name="notes" id="approveNotes" rows="2" class="form-control" placeholder="أي ملاحظات حول الاعتماد..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                            <i class="fas fa-check me-1"></i> تأكيد الاعتماد النهائي
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('projects.requests.reject', $unifiedRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="fas fa-times-circle me-2"></i> تأكيد رفض الطلب المباشر
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="rejectionReason" class="form-label small fw-semibold required">
                                سبب الرفض <span class="text-danger">*</span>
                            </label>
                            <textarea name="rejection_reason" id="rejectionReason" rows="3" class="form-control" required
                                      placeholder="أدخل سبب الرفض بالتفصيل..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                            <i class="fas fa-times me-1"></i> تأكيد الرفض
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Modal: Confirm Draft --}}
@if($unifiedRequest->isDraft() && (auth()->user()->isAdmin() || auth()->id() === $unifiedRequest->created_by || auth()->user()->hasPermission('project_requests.edit', $unifiedRequest->project) || auth()->user()->hasPermission('payments.create', $unifiedRequest->project) || auth()->user()->hasPermission('clearances.create', $unifiedRequest->project)))
    <div class="modal fade" id="confirmDraftModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('projects.requests.confirm-draft', $unifiedRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-primary">
                            <i class="fas fa-check-circle me-2"></i> تأكيد مسودة الطلب
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-secondary mb-3">
                            هل تأكدت من مراجعة كافة بيانات الطلب والمبالغ والمرفقات المرتبطة؟
                        </p>
                        <div class="p-3 bg-light rounded-3 border mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">رقم الطلب:</span>
                                <strong class="text-dark">{{ $unifiedRequest->request_number }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">نوع الطلب:</span>
                                <strong>{{ $unifiedRequest->typeLabel() }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">المشروع:</span>
                                <strong>{{ $unifiedRequest->project?->project_name }}</strong>
                            </div>
                            @if($unifiedRequest->hasPaymentComponent())
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">مبلغ الدفعة المطلوب:</span>
                                    <strong class="text-success">{{ number_format($unifiedRequest->requested_payment_amount, 2) }} ر.س</strong>
                                </div>
                            @endif
                            @if($unifiedRequest->hasClearanceComponent())
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">مبلغ الإخلاء:</span>
                                    <strong class="text-primary">{{ number_format($unifiedRequest->cleared_amount, 2) }} ر.س</strong>
                                </div>
                            @endif
                        </div>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            عند تأكيد المسودة، ستتحول إلى <strong>«مسودة مؤكدة»</strong> مما يتيح لك إرسالها رسمياً للاعتماد.
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">
                            <i class="fas fa-check me-1"></i> نعم، تأكيد المسودة الآن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

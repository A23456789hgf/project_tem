@extends('layouts.app')

@section('title', 'تفاصيل الاستشارة / الإحالة: ' . $project->project_name)

@section('styles')
<style>
    .approval-show-header {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 1rem;
        color: #fff;
        padding: 1.75rem 2rem;
        margin-bottom: 1.75rem;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.15);
    }

    .tracker-card {
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .referral-timeline {
        position: relative;
        padding: 15px 0;
    }
    
    .timeline-item {
        position: relative;
        padding-right: 42px;
        margin-bottom: 30px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        right: 17px;
        top: 34px;
        bottom: -30px;
        width: 2px;
        background-color: #e2e8f0;
    }
    
    .timeline-item:last-child::before {
        display: none;
    }
    
    .timeline-icon {
        position: absolute;
        right: 0;
        top: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #fff;
        border: 2px solid #e2e8f0;
        z-index: 1;
        font-size: 0.95rem;
    }
    
    .timeline-item.primary .timeline-icon {
        border-color: #3b82f6;
        color: #3b82f6;
        background-color: #eff6ff;
    }
    
    .timeline-item.success .timeline-icon {
        border-color: #10b981;
        color: #10b981;
        background-color: #ecfdf5;
    }

    .timeline-item.warning .timeline-icon {
        border-color: #f59e0b;
        color: #f59e0b;
        background-color: #fffbeb;
    }

    .info-label {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
    }

    .attachment-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        text-decoration: none;
        font-size: 0.825rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .attachment-badge:hover {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #2563eb;
        transform: translateY(-1px);
    }

    @media print {
        .btn, .response-panel, nav, .breadcrumb, .no-print, form, .modal {
            display: none !important;
        }
        .approval-show-header {
            background: none !important;
            color: #000 !important;
            box-shadow: none !important;
            padding: 0 0 1rem 0 !important;
            border-bottom: 2px solid #0f172a !important;
            margin-bottom: 1rem !important;
        }
        .approval-show-header h3, .approval-show-header span, .approval-show-header div, .approval-show-header strong {
            color: #000 !important;
        }
        .tracker-card {
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
            page-break-inside: avoid !important;
            margin-bottom: 1rem !important;
        }
        .col-12.col-xl-8 {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        .badge {
            border: 1px solid #94a3b8 !important;
            color: #000 !important;
            background: #fff !important;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
        @php
            $lastActivity = $project->activityHistory->first();
            $referredUser = $referral->referredUser ?? null;
        @endphp


    {{-- Top Banner Header --}}
    <div class="approval-show-header">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2 text-white-50 small">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none"><i class="fas fa-home me-1"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('approvals.index') }}" class="text-white-50 text-decoration-none">مركز المراجعة والاعتمادات</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('approvals.index', ['tab' => 'referrals_incoming']) }}" class="text-white-50 text-decoration-none">الاستشارات والإحالات</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">تفاصيل الاستشارة</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h3 class="fw-bold mb-0 text-white">
                        {{ $project->project_name }}
                    </h3>
                    <span class="badge bg-light text-dark px-3 py-1 rounded-pill small fw-semibold">
                        <i class="fas fa-hashtag me-1"></i>{{ $project->form_number ?: 'PRJ-'.$project->id }}
                    </span>
                    @if($referral->status === 'pending')
                        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold">
                            <i class="fas fa-clock me-1"></i> بانتظار الرد
                        </span>
                    @elseif($referral->status === 'responded')
                        <span class="badge bg-success text-white px-3 py-1 rounded-pill fw-bold">
                            <i class="fas fa-check-circle me-1"></i> تم الرد
                        </span>
                    @elseif($referral->status === 'returned')
                        <span class="badge bg-secondary text-white px-3 py-1 rounded-pill fw-bold">
                            <i class="fas fa-undo me-1"></i> تم الإرجاع
                        </span>
                    @endif

                    @if($lastActivity)
                        <span class="badge bg-info text-white px-3 py-1 rounded-pill fw-bold">
                            <i class="fas fa-history me-1"></i>
                            آخر إجراء: {{ $lastActivity->action_type }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" onclick="window.print()" class="btn btn-outline-light rounded-pill px-3 btn-sm fw-semibold shadow-sm">
                    <i class="fas fa-print me-1"></i> طباعة الاستشارة
                </button>
                <a href="{{ route('approvals.show', $project->id) }}" class="btn btn-primary rounded-pill px-4 btn-sm fw-semibold shadow-sm">
                    <i class="fas fa-clipboard-check me-1"></i> صفحة المشروع بالاعتمادات
                </a>
                <a href="{{ route('approvals.index', ['tab' => 'referrals_incoming']) }}" class="btn btn-outline-light rounded-pill px-4 btn-sm fw-semibold">
                    <i class="fas fa-arrow-right me-1"></i> العودة لقائمة الاستشارات
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Right Column (Project Info & Consultation Details & Timeline) --}}
        <div class="col-12 col-xl-8">
            
            {{-- 1. Project Basic Overview Card --}}
            <div class="card tracker-card mb-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-project-diagram text-primary me-2"></i> بيانات المشروع الأساسية
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="info-label">اسم المشروع</div>
                            <div class="info-value">{{ $project->project_name }}</div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="info-label">رقم الاستمارة</div>
                            <div class="info-value font-monospace">{{ $project->form_number ?: 'PRJ-'.$project->id }}</div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="info-label">التكلفة الإجمالية</div>
                            <div class="info-value text-success">
                                {{ number_format((float)($project->cost->total_cost ?? 0), 2) }} {{ $project->cost->currency ?? 'USD' }}
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="info-label">الجهة المقدمة للمشروع</div>
                            <div class="info-value">{{ $project->creator_entity_name }}</div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="info-label">المرحلة الحالية بالمسار</div>
                            <div class="info-value text-primary">
                                {{ $project->current_stage_order ? 'الخطوة #' . $project->current_stage_order : 'مسار الاعتماد' }}
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="info-label">حالة المشروع الرئيسية</div>
                            <div>
                                <span class="badge bg-primary px-3 py-1 rounded-pill small fw-semibold">
                                    {{ $project->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Consultation Subject & Requirements Card --}}
            <div class="card tracker-card mb-4">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-comments text-primary me-2"></i> موضوع الاستشارة والمطلوب
                    </h5>
                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-1 rounded-pill small fw-bold">
                        إجراء مستقل (Out-of-band)
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <div class="info-label">الجهة التي قامت بالإحالة (من)</div>
                            <div class="info-value text-dark d-flex align-items-center gap-2">
                                <i class="fas fa-building text-secondary"></i>
                                <span>{{ $referral->referringEntity->name ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="info-label">الجهة المُحال إليها (إلى / التي وصل إليها الطلب)</div>
                            <div class="info-value text-primary d-flex align-items-center gap-2">
                                <i class="fas fa-building text-primary"></i>
                                <span>{{ $referral->referredEntity->name ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="info-label">المستخدم المُرسل</div>
                            <div class="info-value d-flex align-items-center gap-2">
                                <i class="fas fa-user text-muted"></i>
                                <span>{{ $referral->referringUser->name }}</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="info-label">تاريخ وتوقيت الإحالة</div>
                            <div class="info-value" dir="ltr">
                                <i class="far fa-calendar-alt text-muted me-1"></i>
                                <span>{{ $referral->created_at->format('Y-m-d h:i A') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="info-label">نص الإحالة / المطلوب من الجهة المستشارة:</label>
                        <div class="p-3 bg-light rounded-3 border">
                            <p class="mb-0 fw-semibold text-dark" style="white-space: pre-wrap; line-height: 1.7;">{{ $referral->referral_text }}</p>
                        </div>
                    </div>

                    {{-- Referral Attachments --}}
                    @php
                        $refAttachments = $referral->getAllReferralAttachments();
                    @endphp
                    @if(count($refAttachments) > 0)
                        <div>
                            <label class="info-label mb-2"><i class="fas fa-paperclip me-1"></i> المرفقات مع طلب الاستشارة:</label>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach($refAttachments as $idx => $path)
                                    <a href="{{ Storage::url($path) }}" target="_blank" class="attachment-badge">
                                        <i class="fas fa-file-download text-primary"></i>
                                        <span>مرفق #{{ $idx + 1 }} ({{ basename($path) }})</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 3. Consultation Response & Timeline Card --}}
            <div class="card tracker-card">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-history text-primary me-2"></i> سجل وتاريخ الاستشارة
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="referral-timeline">
                        {{-- Step 1: Referral Sent --}}
                        <div class="timeline-item primary">
                            <div class="timeline-icon"><i class="fas fa-paper-plane"></i></div>
                            <div class="card border-0 shadow-sm bg-light">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 text-primary">إرسال طلب الاستشارة / الإحالة</h6>
                                        <small class="text-muted" dir="ltr">{{ $referral->created_at->format('Y-m-d h:i A') }}</small>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        تمت الإحالة من قبل <strong>{{ $referral->referringUser->name }}</strong> ({{ $referral->referringEntity->name }}) إلى <strong>{{ $referral->referredEntity->name }}</strong>.
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Response (if responded/returned) --}}
                        @if($referral->status !== 'pending')
                            <div class="timeline-item {{ $referral->status === 'returned' ? 'warning' : 'success' }}">
                                <div class="timeline-icon">
                                    <i class="fas {{ $referral->status === 'returned' ? 'fa-undo' : 'fa-reply' }}"></i>
                                </div>
                                <div class="card border-0 shadow-sm {{ $referral->status === 'returned' ? 'bg-warning bg-opacity-10 border-warning' : 'bg-success bg-opacity-10 border-success' }}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-bold mb-0 {{ $referral->status === 'returned' ? 'text-warning' : 'text-success' }}">
                                                {{ $referral->status === 'returned' ? 'تم الإرجاع للاستكمال / عدم الاختصاص' : 'رد وإفادة الجهة المستشارة' }}
                                            </h6>
                                            <small class="text-muted" dir="ltr">
                                                {{ $referral->responded_at ? $referral->responded_at->format('Y-m-d h:i A') : '' }}
                                            </small>
                                        </div>
                                        <div class="small text-muted mb-2">
                                            المستشار: <strong>{{ $referral->respondingUser?->name ?? 'مستشار الجهة' }}</strong> ({{ $referral->referredEntity->name }})
                                        </div>
                                        <div class="p-3 bg-white rounded border mb-2">
                                            <p class="mb-0 fw-semibold text-dark" style="white-space: pre-wrap; line-height: 1.6;">{{ $referral->response_text }}</p>
                                        </div>

                                        @php
                                            $respAttachments = $referral->getAllResponseAttachments();
                                        @endphp
                                        @if(count($respAttachments) > 0)
                                            <div class="mt-2">
                                                <label class="info-label mb-1"><i class="fas fa-paperclip me-1"></i> مرفقات الرد:</label>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    @foreach($respAttachments as $idx => $path)
                                                        <a href="{{ Storage::url($path) }}" target="_blank" class="attachment-badge">
                                                            <i class="fas fa-file-download text-success"></i>
                                                            <span>مرفق الرد #{{ $idx + 1 }} ({{ basename($path) }})</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Step 2 Pending Notice --}}
                            <div class="timeline-item">
                                <div class="timeline-icon bg-light text-muted"><i class="fas fa-clock"></i></div>
                                <div class="card border-0 bg-light p-3">
                                    <div class="text-muted small">
                                        <i class="fas fa-hourglass-half me-1"></i> بانتظار استلام الرد والإفادة من الجهة المُستشارَة ({{ $referral->referredEntity->name }})...
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Activity & Audit History --}}
        <div class="card tracker-card mt-4 mb-4">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-history text-secondary me-2"></i>
                    سجل الإجراءات والتدقيق (Activity & Audit History)
                </h5>
            </div>

            <div class="card-body p-4">
                @if($project->activityHistory->isEmpty())
                    <div class="text-center text-muted p-4">
                        لا توجد إجراءات مسجلة على المشروع.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>المستخدم</th>
                                    <th>الإجراء</th>
                                    <th>التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->activityHistory as $history)
                                    <tr>
                                        <td>{{ $history->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ $history->user?->name ?? 'النظام' }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $history->action_type }}
                                            </span>
                                        </td>
                                        <td>{{ $history->notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Left Column (Action Board) --}}
        <div class="col-12 col-xl-4">
            <div class="card tracker-card position-sticky" style="top: 85px;">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-reply text-primary me-2"></i> الرد على طلب الاستشارة
                    </h5>
                </div>
                <div class="card-body p-4">
                    @php
                        $user = Auth::user();
                        $canRespond = $user && $user->can('respond', $referral);
                    @endphp

                    @if($referral->status === 'pending' && $canRespond)
                            <div class="alert alert-primary border-0 rounded-3 mb-4 p-3">
                                <h6 class="fw-bold mb-1"><i class="fas fa-info-circle me-1"></i> مطلوب إفادتكم</h6>
                                <p class="small mb-0">يرجى دراسة تفاصيل الاستشارة وكتابة الرأي والملاحظات الفنية أدناه.</p>
                            </div>

                            <form id="referralResponseForm" action="{{ route('project-referrals.respond', $referral) }}" method="POST" enctype="multipart/form-data" onsubmit="submitReferralResponse(event)">
                                @csrf
                                <input type="hidden" id="referral_id" value="{{ $referral->id }}">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark small">الإجراء المتخذ <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" id="response_status" required>
                                        <option value="responded" selected>تمت الإفادة والرد (Responded)</option>
                                        <option value="returned">إرجاع للاستكمال / عدم الاختصاص (Returned)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-dark small">الرأي / الإفادة والملاحظات <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="response_text" id="response_text" rows="6" required minlength="10" placeholder="اكتب الرأي الفني أو الإفادة بالتفصيل هنا (10 أحرف كحد أدنى)..."></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark small">المرفقات (اختياري)</label>
                                    <input type="file" class="form-control" name="attachments[]" id="response_attachments" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx,.txt">
                                    <div class="form-text small text-muted">يمكن إرفاق أكثر من ملف. الحد الأقصى 20 ميجابايت للملف.</div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm" id="submitResponseBtn">
                                    <i class="fas fa-paper-plane me-2"></i> إرسال الرد والإفادة
                                </button>
                            </form>
                        @else
                            <div class="text-center py-4">
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width: 64px; height: 64px;">
                                    <i class="fas fa-lock text-muted fs-3"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">الاستشارة قيد الانتظار</h6>
                                <p class="text-muted small mb-0">
                                    هذه الاستشارة موجهة إلى 
@if($referredUser)
<strong>{{ $referredUser->name }}</strong>
@else
<strong>{{ $referral->referredEntity->name }}</strong>
@endif
. فقط المستخدمون المخولون يحق لهم الرد.
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width: 64px; height: 64px;">
                                <i class="fas fa-check-circle text-success fs-2"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">الاستشارة منجزة</h6>
                            <p class="text-muted small mb-3">
                                تم تقديم الإفادة المطلوبة بنجاح وإغلاق طلب الاستشارة.
                            </p>
                            <a href="{{ route('approvals.show', $project->id) }}" class="btn btn-outline-primary rounded-pill btn-sm px-4 fw-bold">
                                <i class="fas fa-external-link-alt me-1"></i> عرض المشروع في مركز الاعتمادات
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function submitReferralResponse(e) {
        e.preventDefault();
        
        const form = document.getElementById('referralResponseForm');
        const submitBtn = document.getElementById('submitResponseBtn');
        const referralId = document.getElementById('referral_id').value;
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري إرسال الرد...';

        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action') || `/project-referrals/${referralId}/respond`;

        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errData => {
                    throw new Error(errData.message || 'حدث خطأ أثناء معالجة الرد');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: data.message,
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    alert(data.message);
                    window.location.reload();
                }
            } else {
                throw new Error(data.message || 'حدث خطأ غير متوقع');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: error.message,
                    confirmButtonText: 'حسناً'
                });
            } else {
                alert('خطأ: ' + error.message);
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> إرسال الرد والإفادة';
        });
    }
</script>
@endsection

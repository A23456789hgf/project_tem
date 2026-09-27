{{-- Unified Decision Panel Component (لوحة اتخاذ القرار الموحدة) --}}
<div class="decision-panel p-4 shadow-sm">
    
    @if($activeStep)
        @php
            $phaseColor = match($activeStep->phase) {
                'technical_review' => ['border' => '#6f42c1', 'bg' => '#f3f0ff', 'text' => '#6f42c1', 'icon' => 'fa-tools'],
                'financial_review'  => ['border' => '#0dcaf0', 'bg' => '#e8f9fd', 'text' => '#087990', 'icon' => 'fa-dollar-sign'],
                default             => ['border' => '#198754', 'bg' => '#d1e7dd', 'text' => '#146c43', 'icon' => 'fa-stamp'],
            };
        @endphp
        
        {{-- 1. رأس اعتماد المشروع --}}
        <div class="p-3 rounded-4 mb-4 border shadow-sm"
             style="border-color: {{ $phaseColor['border'] }} !important; background-color: {{ $phaseColor['bg'] }};">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge px-3 py-1 rounded-pill fw-bold" style="background-color: {{ $phaseColor['text'] }}; color: #fff;">
                    <i class="fas {{ $phaseColor['icon'] }} me-1"></i> اعتماد المشروع
                </span>
                <span class="badge bg-white text-dark border px-2 py-1 rounded-pill small fw-semibold">
                    <i class="fas fa-sort-numeric-up text-primary me-1"></i> الخطوة {{ $activeStep->step_order }} من إجمالي {{ $approvalChain->count() }}
                </span>
            </div>
            <div class="mb-2">
                <small class="text-muted d-block" style="font-size: 0.78rem;">المرحلة الحالية:</small>
                <h5 class="fw-bold mb-0" style="color: {{ $phaseColor['text'] }};">
                    {{ $activeStep->getPhaseArabicName() }}
                </h5>
            </div>
            <div class="pt-2 border-top border-white d-flex align-items-center gap-2">
                <i class="fas fa-building text-secondary"></i>
                <span class="small fw-bold text-dark">
                    الجهة الحالية: {{ $activeStep->entity?->name ?? $activeStep->authority?->agency_name ?? $activeStep->authority?->name ?? 'المركز الرئيسي' }}
                </span>
            </div>
        </div>

        {{-- 2. بطاقة عمل وإجراءات المرحلة الحالية (Review & Edit Dedicated Actions) --}}
        <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header py-2 px-3 fw-bold small text-dark d-flex align-items-center justify-content-between" style="background-color: #f8f9fa;">
                <span><i class="fas fa-tasks me-1 text-primary"></i> إجراءات مراجعة المرحلة الحالية</span>
                <span class="badge bg-secondary-subtle text-secondary small">خطوة المراجعة</span>
            </div>
            <div class="card-body p-3 bg-white">
                @if($activeStep->phase === 'technical_review')
                    <div class="p-2 rounded-3 mb-2" style="background-color: #f8f4ff; border: 1px dashed #6f42c1;">
                        <h6 class="fw-bold mb-1" style="color: #6f42c1; font-size: 0.9rem;">
                            <i class="fas fa-tools me-1"></i> مراجعة وتعديل البيانات الفنية
                        </h6>
                        <p class="text-muted small mb-2" style="font-size: 0.78rem;">
                            يُتاح للمراجع الفني تعديل مسميات الأنشطة والإجراءات، والمواعيد الزمنية، وتعديل الأوزان النسبية قبل اتخاذ قرار الاعتماد.
                        </p>
                        <a href="{{ route('projects.review.technical', $project) }}" class="btn btn-sm w-100 text-white fw-bold shadow-sm rounded-pill" style="background-color: #6f42c1;">
                            <i class="fas fa-external-link-alt me-1"></i> فتح شاشة المراجعة الفنية وتعديل الأوزان
                        </a>
                    </div>
                @elseif($activeStep->phase === 'financial_review')
                    <div class="p-2 rounded-3 mb-2" style="background-color: #f0fbfd; border: 1px dashed #0dcaf0;">
                        <h6 class="fw-bold mb-1 text-info" style="font-size: 0.9rem;">
                            <i class="fas fa-dollar-sign me-1"></i> مراجعة وتعديل البيانات المالية
                        </h6>
                        <p class="text-muted small mb-2" style="font-size: 0.78rem;">
                            يُتاح للمراجع المالي تعديل تكاليف الأنشطة والإجراءات (الكميات وأسعار الوحدات) والأوزان قبل اتخاذ قرار الاعتماد.
                        </p>
                        <a href="{{ route('projects.review.financial', $project) }}" class="btn btn-sm w-100 btn-info text-white fw-bold shadow-sm rounded-pill">
                            <i class="fas fa-external-link-alt me-1"></i> فتح شاشة المراجعة المالية وتعديل التكاليف
                        </a>
                    </div>
                @else
                    <div class="p-2 rounded-3 mb-2" style="background-color: #f0f9f4; border: 1px dashed #198754;">
                        <h6 class="fw-bold mb-1 text-success" style="font-size: 0.9rem;">
                            <i class="fas fa-stamp me-1"></i> اعتماد المرحلة لدى الجهة الحالية
                        </h6>
                        <p class="text-muted small mb-2" style="font-size: 0.78rem;">
                            تمت المراجعة الفنية والمالية بنجاح لدى هذه الجهة. يرجى الاطلاع على التفاصيل واتخاذ قرار الاعتماد للانتقال للجهة التالية في السلسلة.
                        </p>
                        <a href="{{ route('projects.show', $project) }}" target="_blank" class="btn btn-sm w-100 btn-outline-success fw-bold shadow-sm rounded-pill">
                            <i class="fas fa-file-alt me-1"></i> مراجعة بيانات المشروع الكاملة
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @elseif($project->status === 'rolled_back_for_review')
        <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning mb-4">
            <h6 class="fw-bold text-dark mb-1">
                <i class="fas fa-undo me-1 text-warning"></i> استكمال نواقص المشروع
            </h6>
            <small class="text-muted">المشروع قيد المراجعة وإعادة التقديم لدى الجهة المنشئة: <strong>{{ $project->creatorEntity?->name }}</strong></small>
        </div>
    @elseif(in_array($project->status, ['in_execution', 'in_progress', 'completed'], true))
        <div class="p-3 rounded-4 bg-success bg-opacity-10 border border-success mb-4 text-center">
            <h6 class="fw-bold text-success mb-0">
                <i class="fas fa-check-circle me-1"></i> المشروع معتمد بالكامل (قيد التنفيذ)
            </h6>
        </div>
    @elseif($project->status === 'rejected')
        <div class="p-3 rounded-4 bg-danger bg-opacity-10 border border-danger mb-4 text-center">
            <h6 class="fw-bold text-danger mb-0">
                <i class="fas fa-ban me-1"></i> المشروع مرفوض
            </h6>
        </div>
    @endif

    {{-- 3. لوحة اتخاذ القرار والنماذج --}}
    @if($project->status === 'rolled_back_for_review')
        {{-- Resubmit Form --}}
        @if($canActOnActiveStep || Auth::user()?->isAdmin() || Auth::id() === $project->created_by_user_id)
            <div class="mb-3">
                <h5 class="fw-bold text-dark mb-2">إعادة تقديم المشروع</h5>
                <p class="text-muted small mb-3">تم إرجاع المشروع لاستكمال الملاحظات. يرجى تدوين ما تم استكماله وإعادة إرساله لسلسلة الاعتماد.</p>
                
                <form action="{{ route('approvals.resubmit', $project) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">ملاحظات الاستكمال / المعالجة: <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="اكتب ما تم تعديله أو إرفاقه بناءً على طلب الاستكمال..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-dark">مرفقات داعمة (اختياري):</label>
                        <input type="file" name="attachment" class="form-control form-control-sm">
                    </div>

                    <button type="submit" class="btn btn-success rounded-pill w-100 fw-bold py-2 shadow-sm">
                        <i class="fas fa-paper-plane me-1"></i> إعادة تقديم المشروع للاعتماد
                    </button>
                </form>
            </div>
        @else
            <div class="alert alert-secondary border-0 rounded-3 text-center mb-0">
                <i class="fas fa-lock me-1"></i> إعادة التقديم متاحة فقط للمستخدمين المخولين من الجهة المنشئة للمشروع أو مدير النظام.
            </div>
        @endif

    @elseif($project->status === 'pending_approval' && $activeStep)

        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-dark mb-0">اتخاذ القرار</h5>
                @if(!$canActOnActiveStep)
                    <span class="badge bg-secondary-subtle text-secondary small border">
                        <i class="fas fa-eye me-1"></i> وضع المشاهدة (معاينة)
                    </span>
                @endif
            </div>

            @if(!$canActOnActiveStep)
                <div class="alert alert-info border-0 rounded-3 py-2 px-3 small mb-3">
                    <i class="fas fa-info-circle me-1"></i> هذه المرحلة مسندة حالياً إلى: <strong>{{ $activeStep->entity?->name ?? 'الجهة المختصة' }}</strong>. تنفيذ القرارات متاح لمسؤول المرحلة ولمدير النظام.
                </div>
            @endif
            
            {{-- Action Choice Selector --}}
            <div class="d-flex flex-column gap-2 mb-4" id="actionChoiceList">
                @if($activeStep->hasAction('approve'))
                <div class="action-choice-btn active-approve" onclick="selectApprovalAction('approve')">
                    <i class="fas fa-check-circle fs-5 text-success"></i>
                    <div>
                        <strong class="d-block">اعتماد وموافقة</strong>
                        <small class="text-muted">الموافقة على المرحلة ونقل المشروع للخطوة التالية</small>
                    </div>
                </div>
                @endif

                @if($activeStep->hasAction('return'))
                <div class="action-choice-btn" onclick="selectApprovalAction('completion')">
                    <i class="fas fa-undo fs-5 text-warning"></i>
                    <div>
                        <strong class="d-block">طلب استكمال نواقص</strong>
                        <small class="text-muted">إرجاع المشروع للخطوة السابقة أو للجهة المنشئة</small>
                    </div>
                </div>
                @endif

                @if($activeStep->hasAction('consultation'))
                <div class="action-choice-btn" onclick="selectApprovalAction('consultation')">
                    <i class="fas fa-comments fs-5 text-primary"></i>
                    <div>
                        <strong class="d-block">إحالة واستشارة فنية</strong>
                        <small class="text-muted">إرسال استفسار فني/إداري لجهة أخرى دون تغيير الدور</small>
                    </div>
                </div>
                @endif

                @if($activeStep->hasAction('reject'))
                <div class="action-choice-btn" onclick="selectApprovalAction('reject')">
                    <i class="fas fa-times-circle fs-5 text-danger"></i>
                    <div>
                        <strong class="d-block">رفض المشروع</strong>
                        <small class="text-muted">رفض المشروع نهائياً وإيقاف مسار الاعتماد</small>
                    </div>
                </div>
                @endif
            </div>

            {{-- Form 1: Approve --}}
            <form id="formApprove" action="{{ route('approvals.approve', $project) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-dark">ملاحظات الاعتماد (اختياري):</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="أدخل أي ملاحظات فنية أو توصيات..." {{ !$canActOnActiveStep ? 'disabled' : '' }}></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-dark">مرفق الاعتماد (اختياري):</label>
                    <input type="file" name="attachment" class="form-control form-control-sm" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                </div>
                <button type="submit" class="btn btn-success rounded-pill w-100 fw-bold py-2 shadow-sm mb-2" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                    <i class="fas fa-check-circle me-1"></i> تأكيد الاعتماد والموافقة
                </button>
                <div class="text-center">
                    <a href="{{ route('correspondence.create', [
                        'project_id' => $project->id,
                        'decision_type' => 'موافقة',
                        'entity_id' => $activeStep->entity_id,
                        'entity_name' => $activeStep->entity?->name ?? 'المركز الرئيسي',
                        'phase' => $activeStep->phase,
                        'phase_name' => $activeStep->getPhaseArabicName(),
                        'step_order' => "الخطوة {$activeStep->step_order} من إجمالي {$approvalChain->count()}",
                        'subject' => 'مراسلة (موافقة واعتماد) بخصوص مشروع: ' . ($project->project_name ?? $project->name) . ' (' . $project->form_number . ')'
                    ]) }}" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 shadow-sm w-100" target="_blank">
                        <i class="fas fa-envelope-open-text me-1"></i> إنشاء مراسلة مرتبطة بالموافقة (اختياري)
                    </a>
                </div>
            </form>

            {{-- Form 2: Request Completion --}}
            <form id="formCompletion" action="{{ route('approvals.requestAction', $project) }}" method="POST" enctype="multipart/form-data" style="display: none;">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">إرجاع المشروع إلى: <span class="text-danger">*</span></label>
                    <div class="form-check p-2 bg-light rounded-2 border mb-2">
                        <input class="form-check-input ms-2" type="radio" name="return_target" id="retTargetPrev" value="previous_step" checked {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                        <label class="form-check-label small fw-bold text-dark" for="retTargetPrev">
                            الخطوة السابقة مباشرة (Previous Step) - افتراضي
                        </label>
                    </div>
                    <div class="form-check p-2 bg-light rounded-2 border">
                        <input class="form-check-input ms-2" type="radio" name="return_target" id="retTargetCreator" value="creator_entity" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                        <label class="form-check-label small fw-bold text-dark" for="retTargetCreator">
                            الجهة المنشئة (Creator Entity)
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">الإجراء المطلوب / الملاحظات: <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="وضّح بالتفصيل الإجراء المطلوب والنواقص (10 أحرف كحد أدنى)..." required minlength="10" {{ !$canActOnActiveStep ? 'disabled' : '' }}></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-dark">مرفق توضيحي (اختياري):</label>
                    <input type="file" name="attachment" class="form-control form-control-sm" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                </div>

                <button type="submit" class="btn btn-warning rounded-pill w-100 fw-bold py-2 shadow-sm text-dark mb-2" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                    <i class="fas fa-undo me-1"></i> إرسال طلب استكمال الإجراءات
                </button>
                <div class="text-center">
                    <a href="{{ route('correspondence.create', [
                        'project_id' => $project->id,
                        'decision_type' => 'استكمال الإجراءات',
                        'entity_id' => $activeStep->entity_id,
                        'entity_name' => $activeStep->entity?->name ?? 'المركز الرئيسي',
                        'phase' => $activeStep->phase,
                        'phase_name' => $activeStep->getPhaseArabicName(),
                        'step_order' => "الخطوة {$activeStep->step_order} من إجمالي {$approvalChain->count()}",
                        'subject' => 'مراسلة (طلب استكمال) بخصوص مشروع: ' . ($project->project_name ?? $project->name) . ' (' . $project->form_number . ')'
                    ]) }}" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 py-1 shadow-sm w-100" target="_blank">
                        <i class="fas fa-envelope-open-text me-1"></i> إنشاء مراسلة مرتبطة بطلب الاستكمال (اختياري)
                    </a>
                </div>
            </form>

            {{-- Form 3: Consultation / Referral --}}
            <form id="formConsultation" action="{{ route('approvals.referral', $project) }}" method="POST" enctype="multipart/form-data" style="display: none;">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">الجهة المطلوب الاستشارة منها: <span class="text-danger">*</span></label>
                    <select name="referred_entity_id" id="consultation_referred_entity_id" class="form-select form-select-sm" required {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                        <option value="">-- اختر الجهة المستشارة --</option>
                        @foreach($entitiesList as $ent)
                            <option value="{{ $ent->id }}">{{ $ent->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">المستخدم المستلم: <span class="text-danger">*</span></label>
                    <select name="referred_user_id" id="consultation_referred_user_id" class="form-select form-select-sm" required {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                        <option value="">-- اختر المستخدم المستلم --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">نص الاستشارة / الاستفسار: <span class="text-danger">*</span></label>
                    <textarea name="referral_text" class="form-control" rows="3" placeholder="اكتب نص الاستشارة أو النقطة المطلوب إبداء الرأي الفني فيها..." required minlength="10" {{ !$canActOnActiveStep ? 'disabled' : '' }}></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-dark">المرفقات الداعمة (اختياري):</label>
                    <input type="file" name="attachments[]" multiple class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx,.txt" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                    <div class="form-text small text-muted">يمكن إرفاق أكثر من ملف.</div>
                </div>

                <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold py-2 shadow-sm mb-2" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                    <i class="fas fa-paper-plane me-1"></i> إرسال طلب الاستشارة
                </button>
                <div class="text-center">
                    <a href="{{ route('correspondence.create', [
                        'project_id' => $project->id,
                        'decision_type' => 'طلب استشارة',
                        'entity_id' => $activeStep->entity_id,
                        'entity_name' => $activeStep->entity?->name ?? 'المركز الرئيسي',
                        'phase' => $activeStep->phase,
                        'phase_name' => $activeStep->getPhaseArabicName(),
                        'step_order' => "الخطوة {$activeStep->step_order} من إجمالي {$approvalChain->count()}",
                        'subject' => 'مراسلة (طلب استشارة فنية) بخصوص مشروع: ' . ($project->project_name ?? $project->name) . ' (' . $project->form_number . ')'
                    ]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-sm w-100" target="_blank">
                        <i class="fas fa-envelope-open-text me-1"></i> إنشاء مراسلة مرتبطة بطلب الاستشارة (اختياري)
                    </a>
                </div>
            </form>

            {{-- Form 4: Reject --}}
            <form id="formReject" action="{{ route('approvals.reject', $project) }}" method="POST" enctype="multipart/form-data" style="display: none;">
                @csrf
                <div class="alert alert-danger border-0 rounded-3 small p-2 mb-3">
                    <i class="fas fa-exclamation-triangle me-1"></i> تنبيه: رفض المشروع سيؤدي إلى إيقاف المسار وإعادته للمسودة.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">سبب الرفض: <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="اذكر سبب الرفض بالتفصيل (إلزامي — 10 أحرف على الأقل)..." required minlength="10" {{ !$canActOnActiveStep ? 'disabled' : '' }}></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-dark">مرفق قرار الرفض (اختياري):</label>
                    <input type="file" name="attachment" class="form-control form-control-sm" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                </div>

                <button type="submit" class="btn btn-danger rounded-pill w-100 fw-bold py-2 shadow-sm mb-2" {{ !$canActOnActiveStep ? 'disabled' : '' }}>
                    <i class="fas fa-times-circle me-1"></i> تأكيد رفض المشروع
                </button>
                <div class="text-center">
                    <a href="{{ route('correspondence.create', [
                        'project_id' => $project->id,
                        'decision_type' => 'رفض',
                        'entity_id' => $activeStep->entity_id,
                        'entity_name' => $activeStep->entity?->name ?? 'المركز الرئيسي',
                        'phase' => $activeStep->phase,
                        'phase_name' => $activeStep->getPhaseArabicName(),
                        'step_order' => "الخطوة {$activeStep->step_order} من إجمالي {$approvalChain->count()}",
                        'subject' => 'مراسلة (قرار رفض) بخصوص مشروع: ' . ($project->project_name ?? $project->name) . ' (' . $project->form_number . ')'
                    ]) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 shadow-sm w-100" target="_blank">
                        <i class="fas fa-envelope-open-text me-1"></i> إنشاء مراسلة مرتبطة بالرفض (اختياري)
                    </a>
                </div>
            </form>
        </div>

    @elseif(in_array($project->status, ['in_execution', 'in_progress', 'completed'], true))
        <div class="alert alert-success border-0 rounded-4 p-4 text-center mb-0">
            <i class="fas fa-check-double fs-3 text-success mb-2 d-block"></i>
            <strong class="d-block text-dark mb-1">المشروع معتمد بالكامل</strong>
            <p class="small text-muted mb-0">تم اعتماد كافة خطوات المسار الهرمي للمشروع، وهو الآن في طور التنفيذ.</p>
        </div>
    @elseif($project->status === 'rejected')
        <div class="alert alert-danger border-0 rounded-4 p-4 text-center mb-0">
            <i class="fas fa-ban fs-3 text-danger mb-2 d-block"></i>
            <strong class="d-block text-dark mb-1">المشروع مرفوض</strong>
            <p class="small text-muted mb-0">تم إنهاء مسار الاعتماد بالرفض.</p>
        </div>
    @endif

</div>

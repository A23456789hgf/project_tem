<div>
    {{-- ===== رأس القسم ===== --}}
    <div class="memos-section-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="section-icon-box blue">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">القرارات والمذكرات الرسمية</h5>
                    <p class="text-muted mb-0 small">إدارة المذكرات والقرارات المرتبطة بهذه المهمة</p>
                </div>
            </div>
            @can('task.memo.create', $task)
                <button class="btn-add-memo" type="button" data-bs-toggle="collapse" data-bs-target="#createMemoCollapse">
                    <i class="fas fa-plus-circle"></i>
                    إضافة مذكرة/قرار
                </button>
            @endcan
        </div>
    </div>

    {{-- ===== نموذج إضافة مذكرة ===== --}}
    @can('task.memo.create', $task)
        <div class="collapse" id="createMemoCollapse">
            <div class="memo-form-card">
                <div class="memo-form-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-pen-to-square text-primary"></i>
                        <span class="fw-bold">إنشاء مذكرة / قرار جديد</span>
                    </div>
                    <button type="button" class="btn-close-form" data-bs-toggle="collapse"
                        data-bs-target="#createMemoCollapse">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ $task->project_id ? route('projects.tasks.memos.store', [$task->project_id, $task->id]) : route('tasks.memos.store', $task) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="memo-form-body">
                        {{-- الصف الأول: الموضوع والجهة --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-field">
                                    <label class="form-field-label">
                                        <span class="field-icon blue"><i class="fas fa-heading"></i></span>
                                        موضوع المذكرة / عنوان القرار
                                        
                                    </label>
                                    <input type="text" name="subject" class="form-field-input"
                                        placeholder="مثال: مذكرة طلب شراء مواد للمشروع...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-field">
                                    <label class="form-field-label">
                                        <span class="field-icon purple"><i class="fas fa-building"></i></span>
                                        الجهة المستلمة
                                        
                                    </label>
                                    <select name="recipient_entity_id" id="recipient_entity_id" class="form-field-input">
                                        <option value="">— اختر الجهة المستلمة —</option>
                                        @if(isset($entities))
                                            @foreach($entities as $entity)
                                                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- الصف الثاني: الأولوية والسرية --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-field">
                                    <label class="form-field-label">
                                        <span class="field-icon red"><i class="fas fa-flag"></i></span>
                                        الأولوية
                                    </label>
                                    <select name="priority" id="priority" class="form-field-input">
                                        <option value="normal">📋 عادية</option>
                                        <option value="high">🟠 عالية</option>
                                        <option value="urgent">🔴 عاجلة</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-field">
                                    <label class="form-field-label">
                                        <span class="field-icon orange"><i class="fas fa-shield-halved"></i></span>
                                        مستوى السرية
                                    </label>
                                    <div class="confidential-toggle">
                                        <input class="form-check-input confidential-switch" type="checkbox"
                                            name="confidential" id="confidential" value="1" role="switch">
                                        <label class="confidential-label" for="confidential">
                                            <span class="confidential-text">مراسلة عادية</span>
                                            <span class="confidential-badge">غير سرية</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- نص المذكرة --}}
                        <div class="form-field">
                            <label class="form-field-label">
                                <span class="field-icon green"><i class="fas fa-align-right"></i></span>
                                نص المذكرة بالكامل
                                
                            </label>
                            <textarea name="content" class="form-field-input" rows="5"
                                placeholder="اكتب التفاصيل الكاملة للقرار أو المذكرة الإدارية هنا..."></textarea>
                        </div>

                        {{-- ملاحظات --}}
                        <div class="form-field">
                            <label class="form-field-label">
                                <span class="field-icon teal"><i class="fas fa-note-sticky"></i></span>
                                ملاحظات
                                <span class="optional-mark">(اختياري)</span>
                            </label>
                            <textarea name="notes" id="notes" rows="2" class="form-field-input"
                                placeholder="أية ملاحظات إضافية..."></textarea>
                        </div>

                        {{-- المرفقات --}}
                        <div class="form-field">
                            <label class="form-field-label">
                                <span class="field-icon indigo"><i class="fas fa-paperclip"></i></span>
                                المرفقات
                                <span class="optional-mark">(اختياري)</span>
                            </label>
                            <div class="file-upload-area">
                                <input type="file" name="attachments[]" id="attachments" class="file-upload-input" multiple>
                                <div class="file-upload-placeholder">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                    <span>اسحب الملفات هنا أو <strong>اضغط للاختيار</strong></span>
                                    <small>PDF, Word, Excel, الصور — الحد الأقصى 10 ميجابايت لكل ملف</small>
                                </div>
                            </div>
                        </div>

                        {{-- قسم التوقيع (عند الإنشاء) --}}
                        <div class="form-field mt-4 border-top pt-3">
                            <label class="form-field-label mb-3">
                                <span class="field-icon green"><i class="fas fa-signature"></i></span>
                                توقيع المذكرة واعتمادها
                            </label>
                            
                            <div class="form-check form-switch mb-3 ps-0 d-flex align-items-center gap-2">
                                <input class="form-check-input ms-0 mt-0" type="checkbox" id="sign_now_toggle" name="sign_now" value="1" style="width: 2.5em; height: 1.4em; cursor: pointer;">
                                <label class="form-check-label fw-bold m-0" for="sign_now_toggle" style="cursor: pointer;">توقيع المذكرة الآن</label>
                            </div>

                            <div id="create_signature_section" style="display: none;">
                                <input type="hidden" name="create_signature" id="createSignatureDataInput">
                                
                                @if(auth()->user()->hasSignature())
                                    <div class="alert alert-info mb-3 d-flex align-items-center gap-3">
                                        <div class="fs-4 text-info"><i class="fas fa-info-circle"></i></div>
                                        <div>
                                            <strong>لديك توقيع محفوظ مسبقاً!</strong>
                                            <p class="mb-0 small">سيتم اعتماد توقيعك المحفوظ تلقائياً. إذا أردت رسم توقيع جديد، يمكنك القيام بذلك في المربع أدناه ليتم استخدامه بدلاً منه وحفظه للمستقبل.</p>
                                        </div>
                                    </div>
                                @else
                                    <p class="signature-instruction text-muted small mb-2">
                                        <i class="fas fa-info-circle"></i> ارسم توقيعك بوضوح لاعتماده (سيتم حفظه بملفك الشخصي لاستخدامه لاحقاً).
                                    </p>
                                @endif

                                <div class="signature-canvas-wrapper create-canvas-wrapper" style="border: 2px dashed #cbd5e1; border-radius: 12px; position: relative; background: #f8fafc; overflow: hidden; width: fit-content;">
                                    <canvas id="createSignatureCanvas" width="450" height="200"></canvas>
                                    <div class="signature-canvas-placeholder" id="createSignaturePlaceholder" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; transition: opacity 0.2s;">
                                        <i class="fas fa-pen-fancy fs-3"></i>
                                        <span class="fw-bold">ارسم توقيعك هنا</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="clearCreateSignature()">
                                    <i class="fas fa-eraser"></i> مسح التوقيع
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="memo-form-footer">
                        <button type="button" class="btn-form-cancel" data-bs-toggle="collapse"
                            data-bs-target="#createMemoCollapse">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn-form-submit">
                            <i class="fas fa-check-circle"></i> حفظ المذكرة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- ===== قائمة المذكرات ===== --}}
    <div class="memos-list">
        @forelse($task->memos as $memo)
            <div class="memo-card">
                {{-- رأس المذكرة --}}
                <div class="memo-card-header" data-bs-toggle="collapse" data-bs-target="#collapseMemo{{ $memo->id }}">
                    <div class="memo-header-right">
                        <div class="memo-icon-box {{ $memo->signed_by ? 'signed' : 'unsigned' }}">
                            <i class="fas {{ $memo->signed_by ? 'fa-file-signature' : 'fa-file-contract' }}"></i>
                        </div>
                        <div class="memo-header-info">
                            <h6 class="memo-subject">{{ $memo->subject }}</h6>
                            <div class="memo-meta">
                                <span class="meta-item">
                                    <i class="fas fa-user"></i>
                                    {{ $memo->senderUser->name ?? 'مستخدم' }}
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    {{ $memo->created_at->format('Y-m-d H:i') }}
                                </span>
                                @if($memo->priority === 'urgent')
                                    <span class="priority-badge urgent">🔴 عاجلة</span>
                                @elseif($memo->priority === 'high')
                                    <span class="priority-badge high">🟠 عالية</span>
                                @endif
                                @if($memo->confidential)
                                    <span class="priority-badge confidential">🔒 سرية</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="memo-header-left" onclick="event.stopPropagation();">
                        @if(!$memo->signed_by)
                            @can('task.memo.sign', $task)
                                <button type="button" class="btn-sign-memo" onclick="openSignatureModal({{ $memo->id }})">
                                    <i class="fas fa-signature"></i>
                                    <span>توقيع</span>
                                </button>
                            @endcan
                        @else
                            <span class="signed-badge">
                                <i class="fas fa-check-double"></i>
                                موقع
                            </span>
                        @endif

                        @can('task.memo.delete', $task)
                            <form action="{{ $task->project_id ? route('projects.tasks.memos.destroy', [$task->project_id, $task->id, $memo->id]) : route('tasks.memos.destroy', [$task, $memo]) }}"
                                method="POST" onsubmit="return confirmAction(this, 'هل أنت متأكد من حذف هذه المذكرة؟')"
                                class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete-memo" title="حذف المذكرة">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        @endcan

                        <span class="expand-icon">
                            <i class="fas fa-chevron-down"></i>
                        </span>
                    </div>
                </div>

                {{-- محتوى المذكرة --}}
                <div id="collapseMemo{{ $memo->id }}" class="accordion-collapse collapse" data-bs-parent="#memosAccordion">
                    <div class="memo-card-body">
                        {{-- شارة التوقيع --}}
                        @if($memo->signed_by)
                            <div class="signature-status signed">
                                <div class="signature-status-icon">
                                    <i class="fas fa-stamp"></i>
                                </div>
                                <div class="signature-status-info">
                                    <div class="signature-status-title">موقع ومعتمد رسمياً</div>
                                    <div class="signature-status-detail">
                                        تم التوقيع بواسطة <strong>{{ $memo->signer->name ?? 'مستخدم' }}</strong>
                                        في {{ \Carbon\Carbon::parse($memo->signed_at)->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                                @if($memo->signature_path)
                                    <div class="signature-image">
                                        <img src="{{ asset('storage/' . $memo->signature_path) }}" alt="التوقيع">
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- تفاصيل المذكرة --}}
                        @include('correspondence.partials.details-content', ['correspondence' => $memo, 'is_embedded' => true])
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-memos">
                <div class="empty-memos-icon">
                    <i class="fas fa-file-circle-xmark"></i>
                </div>
                <h6>لا توجد مذكرات أو قرارات</h6>
                <p>لم يتم تسجيل أي مذكرات أو قرارات لهذه المهمة حتى الآن.</p>
                @can('task.memo.create', $task)
                    <button class="btn-add-memo" type="button" data-bs-toggle="collapse" data-bs-target="#createMemoCollapse">
                        <i class="fas fa-plus-circle"></i>
                        إضافة مذكرة جديدة
                    </button>
                @endcan
            </div>
        @endforelse
    </div>
</div>

{{-- ===== Modal: التوقيع الرقمي ===== --}}
<div class="modal fade" id="signatureModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="signatureForm" method="POST" class="modal-content signature-modal-content">
            @csrf
            <input type="hidden" name="signature" id="signatureDataInput">

            <div class="signature-modal-header">
                <div class="signature-modal-icon">
                    <i class="fas fa-signature"></i>
                </div>
                <div>
                    <h5>التوقيع الرقمي للقرار</h5>
                    <small>قم برسم توقيعك في المربع أدناه</small>
                </div>
                <button type="button" class="btn-close-modal" data-bs-dismiss="modal" onclick="clearSignature()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="signature-modal-body">
                @if(auth()->user()->hasSignature())
                    <div class="alert alert-info mb-3 d-flex align-items-center gap-3">
                        <div class="fs-4 text-info"><i class="fas fa-info-circle"></i></div>
                        <div>
                            <strong>لديك توقيع محفوظ مسبقاً!</strong>
                            <p class="mb-0 small">يمكنك النقر على "تأكيد واعتماد التوقيع" مباشرة لاستخدامه، أو رسم توقيع جديد في المربع أدناه ليتم استخدامه بدلاً منه.</p>
                        </div>
                    </div>
                @endif
                <p class="signature-instruction">
                    <i class="fas fa-info-circle"></i>
                    الرجاء رسم توقيعك بوضوح باستخدام الماوس أو الشاشة التي تعمل باللمس
                </p>

                <div class="signature-canvas-wrapper">
                    <canvas id="signatureCanvas" width="450" height="200"></canvas>
                    <div class="signature-canvas-placeholder" id="signaturePlaceholder">
                        <i class="fas fa-pen-fancy"></i>
                        <span>ارسم توقيعك هنا</span>
                    </div>
                </div>

                <div class="signature-actions">
                    <button type="button" class="btn-clear-signature" onclick="clearSignature()">
                        <i class="fas fa-eraser"></i>
                        مسح التوقيع
                    </button>
                    <span class="signature-legal">
                        <i class="fas fa-shield-halved"></i>
                        توقيع رسمي ملزم
                    </span>
                </div>
            </div>

            <div class="signature-modal-footer">
                <button type="button" class="btn-sig-cancel" data-bs-dismiss="modal" onclick="clearSignature()">
                    <i class="fas fa-times"></i> إلغاء
                </button>
                <button type="button" class="btn-sig-confirm" onclick="submitSignature()">
                    <i class="fas fa-check-circle"></i> تأكيد واعتماد التوقيع
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== الأنماط ===== --}}
<style>
    /* ===============================
       رأس القسم
       =============================== */
    .memos-section-header {
        background: #fff;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1.25rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    }

    .section-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .section-icon-box.blue {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #3b82f6;
    }

    /* ===============================
       زر الإضافة
       =============================== */
    .btn-add-memo {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        border: none;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    }

    .btn-add-memo:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(59, 130, 246, 0.3);
        color: #fff;
    }

    /* ===============================
       نموذج الإضافة
       =============================== */
    .memo-form-card {
        background: #fff;
        border-radius: 16px;
        border: 1.5px solid #bfdbfe;
        margin-bottom: 1.5rem;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.06);
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .memo-form-header {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #bfdbfe;
    }

    .memo-form-header i {
        color: #3b82f6;
    }

    .btn-close-form {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: none;
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-close-form:hover {
        background: #3b82f6;
        color: #fff;
    }

    .memo-form-body {
        padding: 1.5rem;
    }

    .memo-form-footer {
        padding: 1rem 1.5rem;
        background: #fafbfc;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        gap: 0.65rem;
    }

    .btn-form-cancel {
        background: #fff;
        border: 1.5px solid #e2e8f0;
        color: #64748b;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .btn-form-cancel:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #475569;
    }

    .btn-form-submit {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: none;
        color: #fff;
        padding: 0.6rem 1.5rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        cursor: pointer;
    }

    .btn-form-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
    }

    /* ===============================
       حقول النموذج
       =============================== */
    .form-field {
        margin-bottom: 0;
    }

    .form-field-label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.5rem;
    }

    .field-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        flex-shrink: 0;
    }

    .field-icon.blue {
        background: #eff6ff;
        color: #3b82f6;
    }

    .field-icon.red {
        background: #fef2f2;
        color: #ef4444;
    }

    .field-icon.green {
        background: #f0fdf4;
        color: #22c55e;
    }

    .field-icon.purple {
        background: #faf5ff;
        color: #a855f7;
    }

    .field-icon.orange {
        background: #fff7ed;
        color: #f97316;
    }

    .field-icon.teal {
        background: #f0fdfa;
        color: #14b8a6;
    }

    .field-icon.indigo {
        background: #eef2ff;
        color: #6366f1;
    }

    .required-mark {
        color: #ef4444;
        font-size: 0.75rem;
        margin-right: 2px;
    }

    .optional-mark {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 500;
        margin-right: 4px;
    }

    .form-field-input {
        width: 100%;
        padding: 0.6rem 0.9rem;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        font-size: 0.88rem;
        color: #334155;
        transition: all 0.25s ease;
    }

    .form-field-input:focus {
        background: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
        outline: none;
    }

    .form-field-input:hover {
        border-color: #cbd5e1;
    }

    textarea.form-field-input {
        resize: vertical;
        min-height: 80px;
    }

    /* ===============================
       مفتاح السرية
       =============================== */
    .confidential-toggle {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 1rem;
        background: #f8fafc;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        transition: all 0.25s ease;
    }

    .confidential-toggle:has(.confidential-switch:checked) {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border-color: #fca5a5;
    }

    .confidential-switch {
        width: 2.5em;
        height: 1.4em;
        cursor: pointer;
    }

    .confidential-switch:checked {
        background-color: #ef4444;
        border-color: #ef4444;
    }

    .confidential-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        margin: 0;
        flex: 1;
    }

    .confidential-text {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
    }

    .confidential-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        background: #f0fdf4;
        color: #16a34a;
        font-weight: 600;
    }

    .confidential-switch:checked~.confidential-label .confidential-badge,
    .confidential-toggle:has(.confidential-switch:checked) .confidential-badge {
        background: #fef2f2;
        color: #dc2626;
    }

    /* ===============================
       منطقة رفع الملفات
       =============================== */
    .file-upload-area {
        position: relative;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        transition: all 0.25s ease;
        overflow: hidden;
    }

    .file-upload-area:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .file-upload-input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .file-upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        text-align: center;
        color: #64748b;
        gap: 0.4rem;
    }

    .file-upload-placeholder i {
        font-size: 1.5rem;
        color: #94a3b8;
        margin-bottom: 0.25rem;
    }

    .file-upload-placeholder span {
        font-size: 0.85rem;
        font-weight: 600;
    }

    .file-upload-placeholder small {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    /* ===============================
       قائمة المذكرات
       =============================== */
    .memos-list {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    /* ===============================
       بطاقة المذكرة
       =============================== */
    .memo-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.25s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    }

    .memo-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }

    .memo-card-header {
        padding: 1.15rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        cursor: pointer;
        transition: background 0.2s ease;
        flex-wrap: wrap;
    }

    .memo-card-header:hover {
        background: #fafbfc;
    }

    .memo-header-right {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex: 1;
        min-width: 0;
    }

    .memo-icon-box {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        transition: all 0.25s ease;
    }

    .memo-icon-box.unsigned {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #3b82f6;
    }

    .memo-icon-box.signed {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #16a34a;
    }

    .memo-header-info {
        flex: 1;
        min-width: 0;
    }

    .memo-subject {
        font-size: 0.92rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 0.35rem 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .memo-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .meta-item {
        font-size: 0.75rem;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .meta-item i {
        font-size: 0.65rem;
    }

    .priority-badge {
        font-size: 0.68rem;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        font-weight: 600;
        white-space: nowrap;
    }

    .priority-badge.urgent {
        background: #fee2e2;
        color: #991b1b;
    }

    .priority-badge.high {
        background: #ffedd5;
        color: #9a3412;
    }

    .priority-badge.confidential {
        background: #faf5ff;
        color: #7c3aed;
    }

    .memo-header-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .btn-sign-memo {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #fff;
        border: none;
        padding: 0.45rem 0.9rem;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.25s ease;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2);
    }

    .btn-sign-memo:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .signed-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #166534;
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .btn-delete-memo {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid #fecaca;
        background: #fef2f2;
        color: #dc2626;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        transition: all 0.25s ease;
        cursor: pointer;
    }

    .btn-delete-memo:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #fff;
        transform: translateY(-1px);
    }

    .expand-icon {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 0.7rem;
        transition: all 0.3s ease;
    }

    .memo-card-header[aria-expanded="true"] .expand-icon,
    .memo-card-header:not([aria-expanded]) .expand-icon {
        /* Rotate when expanded - handled by BS */
    }

    /* ===============================
       محتوى المذكرة
       =============================== */
    .memo-card-body {
        padding: 1.5rem;
        border-top: 1px solid #f1f5f9;
        background: #fafbfc;
    }

    /* ===============================
       شارة التوقيع
       =============================== */
    .signature-status {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .signature-status.signed {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border: 1.5px solid #bbf7d0;
    }

    .signature-status-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #22c55e;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .signature-status-info {
        flex: 1;
    }

    .signature-status-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #166534;
        margin-bottom: 0.15rem;
    }

    .signature-status-detail {
        font-size: 0.8rem;
        color: #4ade80;
        color: #15803d;
    }

    .signature-image {
        flex-shrink: 0;
        background: #fff;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        border: 1px solid #bbf7d0;
    }

    .signature-image img {
        max-height: 45px;
        mix-blend-mode: multiply;
    }

    /* ===============================
       حالة عدم وجود مذكرات
       =============================== */
    .empty-memos {
        text-align: center;
        padding: 3.5rem 2rem;
        background: #fff;
        border-radius: 16px;
        border: 2px dashed #e2e8f0;
    }

    .empty-memos-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 1.25rem;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #94a3b8;
    }

    .empty-memos h6 {
        font-size: 1rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.4rem;
    }

    .empty-memos p {
        font-size: 0.85rem;
        color: #94a3b8;
        margin-bottom: 1.25rem;
    }

    /* ===============================
       Modal التوقيع
       =============================== */
    .signature-modal-content {
        border: none;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.25);
    }

    .signature-modal-header {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        padding: 1.35rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        color: #fff;
        position: relative;
    }

    .signature-modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -20%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 50%;
    }

    .signature-modal-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        backdrop-filter: blur(10px);
        position: relative;
        z-index: 1;
    }

    .signature-modal-header h5 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .signature-modal-header small {
        font-size: 0.78rem;
        opacity: 0.85;
        position: relative;
        z-index: 1;
    }

    .btn-close-modal {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-right: auto;
        position: relative;
        z-index: 1;
    }

    .btn-close-modal:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    .signature-modal-body {
        padding: 1.5rem;
    }

    .signature-instruction {
        font-size: 0.82rem;
        color: #64748b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.65rem 0.9rem;
        background: #f0fdf4;
        border-radius: 8px;
        border: 1px solid #bbf7d0;
    }

    .signature-instruction i {
        color: #22c55e;
    }

    /* ===============================
       Canvas التوقيع
       =============================== */
    .signature-canvas-wrapper {
        position: relative;
        border: 2px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
        overflow: hidden;
        transition: border-color 0.3s ease;
        margin-bottom: 1rem;
    }

    .signature-canvas-wrapper:hover,
    .signature-canvas-wrapper:focus-within {
        border-color: #22c55e;
    }

    .signature-canvas-wrapper canvas {
        width: 100%;
        height: 200px;
        display: block;
        cursor: crosshair;
        position: relative;
        z-index: 1;
    }

    .signature-canvas-placeholder {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
        color: #cbd5e1;
        pointer-events: none;
        z-index: 0;
        transition: opacity 0.3s ease;
    }

    .signature-canvas-placeholder i {
        font-size: 2rem;
    }

    .signature-canvas-placeholder span {
        font-size: 0.85rem;
        font-weight: 600;
    }

    .signature-canvas-wrapper.has-signature .signature-canvas-placeholder {
        opacity: 0;
    }

    .signature-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .btn-clear-signature {
        background: #fef2f2;
        border: 1.5px solid #fecaca;
        color: #dc2626;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-clear-signature:hover {
        background: #fee2e2;
        border-color: #fca5a5;
    }

    .signature-legal {
        font-size: 0.78rem;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .signature-legal i {
        color: #22c55e;
    }

    .signature-modal-footer {
        padding: 1.15rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        gap: 0.65rem;
        background: #fafbfc;
    }

    .btn-sig-cancel {
        background: #fff;
        border: 1.5px solid #e2e8f0;
        color: #64748b;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-sig-cancel:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-sig-confirm {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border: none;
        color: #fff;
        padding: 0.6rem 1.5rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
    }

    .btn-sig-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(34, 197, 94, 0.35);
    }
</style>

{{-- ===== Modal: التوقيع الرقمي ===== --}}
<script>
    let canvas, ctx, drawing = false;
    let hasDrawn = false;

    document.addEventListener('DOMContentLoaded', () => {
        canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        ctx = canvas.getContext('2d');

        // Adjust resolution for Retina screens
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = 200;

        ctx.strokeStyle = '#1e3a5f';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';

        const wrapper = canvas.closest('.signature-canvas-wrapper');

        function startDrawing(x, y) {
            drawing = true;
            hasDrawn = true;
            ctx.beginPath();
            ctx.moveTo(x, y);
            if (wrapper) wrapper.classList.add('has-signature');
        }

        function draw(x, y) {
            if (!drawing) return;
            ctx.lineTo(x, y);
            ctx.stroke();
        }

        function stopDrawing() {
            drawing = false;
        }

        // Mouse events
        canvas.addEventListener('mousedown', (e) => {
            startDrawing(getX(e), getY(e));
        });
        canvas.addEventListener('mousemove', (e) => {
            draw(getX(e), getY(e));
        });
        window.addEventListener('mouseup', stopDrawing);

        // Touch events
        canvas.addEventListener('touchstart', (e) => {
            const touch = e.touches[0];
            startDrawing(getX(touch), getY(touch));
            e.preventDefault();
        });
        canvas.addEventListener('touchmove', (e) => {
            const touch = e.touches[0];
            draw(getX(touch), getY(touch));
            e.preventDefault();
        });
        window.addEventListener('touchend', stopDrawing);
    });

    function getX(e) {
        const rect = canvas.getBoundingClientRect();
        return (e.clientX || e.pageX) - rect.left;
    }

    function getY(e) {
        const rect = canvas.getBoundingClientRect();
        return (e.clientY || e.pageY) - rect.top;
    }

    function clearSignature() {
        if (!ctx || !canvas) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasDrawn = false;
        const wrapper = canvas.closest('.signature-canvas-wrapper');
        if (wrapper) wrapper.classList.remove('has-signature');
    }

    function openSignatureModal(memoId) {
        const form = document.getElementById('signatureForm');
        const memoSignRoute = @json(
            $task->project_id
                ? route('projects.tasks.memos.sign', [$task->project_id, $task->id, '__MEMO_ID__'])
                : route('tasks.memos.sign', [$task, '__MEMO_ID__'])
        );
        form.action = memoSignRoute.replace('__MEMO_ID__', memoId);

        const modal = new bootstrap.Modal(document.getElementById('signatureModal'));
        modal.show();

        setTimeout(() => {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = 200;
            ctx.strokeStyle = '#1e3a5f';
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            hasDrawn = false;
            const wrapper = canvas.closest('.signature-canvas-wrapper');
            if (wrapper) wrapper.classList.remove('has-signature');
        }, 300);
    }

    function submitSignature() {
        const hasSavedSignature = {{ auth()->user()->hasSignature() ? 'true' : 'false' }};

        if (!hasDrawn) {
            // Check if canvas is truly empty
            const blank = document.createElement('canvas');
            blank.width = canvas.width;
            blank.height = canvas.height;
            if (canvas.toDataURL() === blank.toDataURL()) {
                if (hasSavedSignature) {
                    // Bypass validation, backend will use saved signature
                    document.getElementById('signatureDataInput').value = '';
                    document.getElementById('signatureForm').submit();
                    return;
                }

                // Show inline alert instead of browser alert
                const instruction = document.querySelector('.signature-instruction');
                instruction.style.background = '#fef2f2';
                instruction.style.borderColor = '#fecaca';
                instruction.style.color = '#dc2626';
                instruction.innerHTML = '<i class="fas fa-exclamation-circle"></i> الرجاء رسم توقيعك أولاً!';
                setTimeout(() => {
                    instruction.style.background = '#f0fdf4';
                    instruction.style.borderColor = '#bbf7d0';
                    instruction.style.color = '#64748b';
                    instruction.innerHTML = '<i class="fas fa-info-circle"></i> الرجاء رسم توقيعك بوضوح باستخدام الماوس أو الشاشة التي تعمل باللمس';
                }, 2500);
                return;
            }
        }

        const dataUrl = canvas.toDataURL();
        document.getElementById('signatureDataInput').value = dataUrl;
        document.getElementById('signatureForm').submit();
    }

    // Logic for create signature canvas
    let createCanvas, createCtx, createDrawing = false;
    let createHasDrawn = false;

    document.addEventListener('DOMContentLoaded', () => {
        const createForm = document.querySelector('#createMemoCollapse form');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                const signToggle = document.getElementById('sign_now_toggle');
                if (signToggle && signToggle.checked) {
                    const hasSavedSignature = {{ auth()->user()->hasSignature() ? 'true' : 'false' }};
                    
                    if (!createHasDrawn) {
                        const blank = document.createElement('canvas');
                        blank.width = createCanvas ? createCanvas.width : 450;
                        blank.height = createCanvas ? createCanvas.height : 200;
                        if (!createCanvas || createCanvas.toDataURL() === blank.toDataURL()) {
                            if (!hasSavedSignature) {
                                e.preventDefault();
                                alert('الرجاء رسم توقيعك أولاً!');
                                return false;
                            }
                        }
                    }
                    
                    if (createHasDrawn && createCanvas) {
                        document.getElementById('createSignatureDataInput').value = createCanvas.toDataURL();
                    }
                }
            });
        }

        const signToggle = document.getElementById('sign_now_toggle');
        const createSigSection = document.getElementById('create_signature_section');
        
        if (signToggle && createSigSection) {
            signToggle.addEventListener('change', function() {
                createSigSection.style.display = this.checked ? 'block' : 'none';
                if (this.checked && !createCanvas) {
                    initCreateCanvas();
                }
            });
        }
    });

    function initCreateCanvas() {
        createCanvas = document.getElementById('createSignatureCanvas');
        if (!createCanvas) return;
        createCtx = createCanvas.getContext('2d');

        createCanvas.width = 450; 
        createCanvas.height = 200;

        createCtx.strokeStyle = '#1e3a5f';
        createCtx.lineWidth = 3;
        createCtx.lineCap = 'round';

        function startCreateDrawing(x, y) {
            createDrawing = true;
            createHasDrawn = true;
            createCtx.beginPath();
            createCtx.moveTo(x, y);
            const placeholder = document.getElementById('createSignaturePlaceholder');
            if (placeholder) placeholder.style.opacity = '0';
        }

        function drawCreate(x, y) {
            if (!createDrawing) return;
            createCtx.lineTo(x, y);
            createCtx.stroke();
        }

        function stopCreateDrawing() {
            createDrawing = false;
        }

        function getCreateX(e) {
            const rect = createCanvas.getBoundingClientRect();
            return (e.clientX || e.touches[0].clientX) - rect.left;
        }

        function getCreateY(e) {
            const rect = createCanvas.getBoundingClientRect();
            return (e.clientY || e.touches[0].clientY) - rect.top;
        }

        createCanvas.addEventListener('mousedown', (e) => startCreateDrawing(getCreateX(e), getCreateY(e)));
        createCanvas.addEventListener('mousemove', (e) => drawCreate(getCreateX(e), getCreateY(e)));
        window.addEventListener('mouseup', stopCreateDrawing);

        createCanvas.addEventListener('touchstart', (e) => {
            startCreateDrawing(getCreateX(e), getCreateY(e));
            e.preventDefault();
        });
        createCanvas.addEventListener('touchmove', (e) => {
            drawCreate(getCreateX(e), getCreateY(e));
            e.preventDefault();
        });
        window.addEventListener('touchend', stopCreateDrawing);
    }

    function clearCreateSignature() {
        if (!createCtx || !createCanvas) return;
        createCtx.clearRect(0, 0, createCanvas.width, createCanvas.height);
        createHasDrawn = false;
        const placeholder = document.getElementById('createSignaturePlaceholder');
        if (placeholder) placeholder.style.opacity = '1';
        document.getElementById('createSignatureDataInput').value = '';
    }

    // Confidential toggle text update
    document.addEventListener('change', function (e) {
        if (e.target.id === 'confidential') {
            const toggle = e.target.closest('.confidential-toggle');
            if (toggle) {
                const text = toggle.querySelector('.confidential-text');
                const badge = toggle.querySelector('.confidential-badge');
                if (e.target.checked) {
                    text.textContent = 'مراسلة سرية';
                    badge.textContent = '🔒 سرية';
                } else {
                    text.textContent = 'مراسلة عادية';
                    badge.textContent = 'غير سرية';
                }
            }
        }
    });
</script>

@push('scripts')
    <script>
        $(document).ready(function () {
            $('.select2-dynamic').each(function () {
                $(this).select2({
                    placeholder: 'اختر الجهة...',
                    allowClear: true,
                    language: "ar",
                    dropdownParent: $(this).parent()
                });
            });

            $('.select2-dynamic-modal').each(function () {
                var modal = $(this).closest('.modal');
                $(this).select2({
                    placeholder: 'اختر الأقسام الفرعية...',
                    language: "ar",
                    dropdownParent: modal.length ? modal : null
                });
            });

            // File size validation
            $(document).on('change', 'input[type="file"]', function () {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const files = this.files;
                for (let i = 0; i < files.length; i++) {
                    if (files[i].size > maxSize) {
                        alert(`الملف ${files[i].name} يتجاوز الحد الأقصى المسموح به (10 ميجابايت)`);
                        $(this).val('');
                        return;
                    }
                }
            });
        });
    </script>
@endpush

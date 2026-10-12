@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold">إضافة مراحل لجهة</h4>
            <small class="text-muted">تحديد مراحل المراجعة والاعتماد والمرفقات المطلوبة والإجراءات المسموحة</small>
        </div>
        <a href="{{ route('admin.entity-stages.index') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5">
            @can('stages.create')
            <form action="{{ route('admin.entity-stages.store', ['tab' => $tab]) }}" method="POST" id="workflowForm">
            @endcan
                @csrf
                <input type="hidden" name="tab" id="form-tab" value="{{ $tab }}">
                <input type="hidden" name="workflow_type" value="{{ $workflowType }}">

                <div class="row mb-5">
                    <div class="col-md-6">
                        @if($tab === 'internal')
                            <label class="form-label fw-bold">الجهة الداخلية <span class="text-danger">*</span></label>
                            <select name="entity_id" id="entity_id" class="form-select select2-search" required>
                                <option value="">-- اختر الجهة الداخلية --</option>
                                @foreach($entities as $entity)
                                    <option value="{{ $entity->id }}" @selected(old('entity_id') == $entity->id)>
                                        {{ $entity->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <label class="form-label fw-bold">الجهة الخارجية <span class="text-danger">*</span></label>
                            <select name="authority_id" id="authority_id" class="form-select select2-search" required>
                                <option value="">-- اختر الجهة الخارجية --</option>
                                @foreach($authorities as $authority)
                                    <option value="{{ $authority->id }}" @selected(old('authority_id') == $authority->id)>
                                        {{ $authority->agency_name ?? $authority->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <div class="form-text">اختر الجهة التي تريد تفعيل مسار الموافقات لها.</div>
                    </div>
                </div>

                @if($tab === 'external')
                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">الوجهة التالية في المسار <span class="text-danger">*</span></label>
                        <select name="route_destination_type" id="route_destination_type" class="form-select" required>
                            <option value="ministry" @selected(old('route_destination_type') == 'ministry')>الوزارة كجهة نهائية (Ministry Root)</option>
                            <option value="authority" @selected(old('route_destination_type') == 'authority')>جهة خارجية أخرى</option>
                        </select>
                        <div class="form-text">إلى أين سيتم توجيه المشروع بعد انتهاء اعتمادات هذه الجهة؟</div>
                    </div>
                    <div class="col-md-6" id="destination_authority_container" style="display: {{ old('route_destination_type') == 'authority' ? 'block' : 'none' }};">
                        <label class="form-label fw-bold">الجهة الخارجية الوجهة <span class="text-danger">*</span></label>
                        <select name="destination_authority_id" class="form-select select2-search">
                            <option value="">-- اختر الجهة الخارجية الوجهة --</option>
                            @foreach($authorities as $authority)
                                <option value="{{ $authority->id }}" @selected(old('destination_authority_id') == $authority->id)>
                                    {{ $authority->agency_name ?? $authority->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif

                <hr class="border-light">

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">مراحل المراجعة والاعتماد</h5>
                            <p class="text-muted small mb-0">أضف المراحل التي سيمر بها المشروع بالتسلسل المطلوب.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-stage-btn">
                            <i class="fas fa-plus"></i> إضافة مرحلة جديدة
                        </button>
                    </div>

                    {{-- تنبيه المرحلة النهائية --}}
                    <div class="alert alert-info border-0 rounded-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>ملاحظة:</strong> يجب تحديد <strong>مرحلة نهائية واحدة فقط</strong> في السلسلة.
                        عند اعتمادها سيدخل المشروع/الطلب إلى مرحلة التنفيذ تلقائياً.
                    </div>

                    <div id="stages-container">
                        <!-- Stages injected by JS -->
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-5">
                    <button type="reset" class="btn btn-light px-4">تفريغ الحقول</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> حفظ المراحل
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Stage Template                                              --}}
{{-- ============================================================ --}}
<template id="stage-template">
    <div class="card border border-2 rounded-4 mb-3 stage-row" style="border-color: #cbd5e1 !important;">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill px-3 py-2 stage-badge">مرحلة 1</span>
                <span class="fw-bold text-dark stage-title">إعداد المرحلة</span>
                <span class="badge bg-danger rounded-pill stage-final-badge d-none">
                    <i class="fas fa-flag-checkered me-1"></i> نهائية
                </span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle remove-stage-btn" title="حذف المرحلة">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- Basic Info --}}
                <div class="col-md-3">
                    <label class="form-label small fw-bold">الاسم الداخلي (Key) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm stage-key-input" name="stages[__INDEX__][stage]" required placeholder="مثال: technical_review">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">اسم المرحلة (عربي) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm stage-name-ar-input" name="stages[__INDEX__][stage_name_ar]" required placeholder="مثال: مراجعة فنية">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">اسم المرحلة (إنجليزي)</label>
                    <input type="text" class="form-control form-control-sm" name="stages[__INDEX__][stage_name_en]" placeholder="مثال: Technical Review">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">المسؤول عن هذه المرحلة</label>
                    <select name="stages[__INDEX__][responsible_user_id]" class="form-select form-select-sm stage-user-select">
                        <option value="">-- اختر المستخدم --</option>
                    </select>
                </div>

                {{-- ✅ خصائص نهاية المسار --}}
                <div class="col-12 mt-3 pt-3 border-top">
                    <label class="form-label small fw-bold text-dark d-block mb-2">
                        <i class="fas fa-flag-checkered text-danger me-1"></i> خصائص نهاية المسار
                    </label>
                    <div class="d-flex gap-4 flex-wrap bg-white p-3 rounded-3 border">
                        <div class="form-check">
                            <input class="form-check-input stage-final-checkbox"
                                   type="checkbox"
                                   name="stages[__INDEX__][is_final]"
                                   value="1"
                                   id="stage_final___INDEX__">
                            <label class="form-check-label small" for="stage_final___INDEX__">
                                <strong class="text-danger">المرحلة النهائية</strong>
                                <span class="text-muted d-block" style="font-size: 0.75rem;">
                                    بعد الاعتماد عليها يُغلق مسار الاعتماد
                                </span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="stages[__INDEX__][triggers_execution]"
                                   value="1"
                                   id="stage_exec___INDEX__">
                            <label class="form-check-label small" for="stage_exec___INDEX__">
                                <strong class="text-success">تُفعِّل التنفيذ</strong>
                                <span class="text-muted d-block" style="font-size: 0.75rem;">
                                    عند الاعتماد يبدأ المشروع/الطلب مرحلة التنفيذ
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- ✅ الإجراءات المسموحة (جميع الإجراءات) --}}
                <div class="col-12 mt-3 pt-3 border-top">
                    <label class="form-label small fw-bold text-dark d-block mb-2">
                        <i class="fas fa-check-square text-success me-1"></i> الإجراءات المسموحة في هذه المرحلة
                    </label>
                    <div class="d-flex gap-3 flex-wrap bg-white p-3 rounded-3 border">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="submit">
                            <label class="form-check-label small fw-semibold text-info">إرسال (Submit)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="approve" checked>
                            <label class="form-check-label small fw-semibold text-success">اعتماد (Approve)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="return" checked>
                            <label class="form-check-label small fw-semibold text-warning">إعادة (Return)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="reject">
                            <label class="form-check-label small fw-semibold text-danger">رفض (Reject)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="comment">
                            <label class="form-check-label small text-secondary">تعليق (Comment)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="upload_attachment">
                            <label class="form-check-label small text-primary">رفع مرفق (Upload)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="need_action">
                            <label class="form-check-label small text-orange">بحاجة لإجراء (Need Action)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="consult">
                            <label class="form-check-label small text-primary">استشارة (Consult)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stages[__INDEX__][available_actions][]" value="forward">
                            <label class="form-check-label small text-primary">تحويل (Forward)</label>
                        </div>
                    </div>
                </div>

                {{-- ✅ المرفقات المطلوبة --}}
                <div class="col-12 mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold text-dark mb-0">
                            <i class="fas fa-paperclip text-primary me-1"></i> المرفقات المطلوبة في هذه المرحلة (إن وجدت):
                        </label>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill add-attachment-btn">
                            <i class="fas fa-plus me-1"></i> إضافة مرفق مطلوب
                        </button>
                    </div>
                    <div class="attachments-list bg-white p-3 rounded-3 border">
                        <div class="text-muted small no-attachments-hint">لا توجد مرفقات إلزامية محددة لهذه المرحلة حالياً.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- ============================================================ --}}
{{-- Attachment Row Template                                     --}}
{{-- ============================================================ --}}
<template id="attachment-template">
    <div class="row g-2 align-items-center mb-2 attachment-row">
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm"
                   name="stages[__INDEX__][required_attachments][__ATT_INDEX__][key]"
                   required placeholder="رمز المرفق (مثال: clearance_receipt)">
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control form-control-sm"
                   name="stages[__INDEX__][required_attachments][__ATT_INDEX__][label]"
                   required placeholder="وصف المرفق (مثال: محضر الاستلام المالي)">
        </div>
        <div class="col-md-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="stages[__INDEX__][required_attachments][__ATT_INDEX__][required]"
                       value="1" checked id="req___INDEX_____ATT_INDEX__">
                <label class="form-check-label small" for="req___INDEX_____ATT_INDEX__">إلزامي</label>
            </div>
        </div>
        <div class="col-md-1 text-center">
            <button type="button" class="btn btn-sm btn-link text-danger remove-attachment-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</template>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2-search').select2({
            dir: "rtl",
            theme: "bootstrap-5",
            width: '100%'
        });

        let stageIndex = 0;
        let eligibleUsers = [];

        function fetchEligibleUsers() {
            let entityId = $('#entity_id').val();
            let authorityId = $('#authority_id').val();
            let tab = $('#form-tab').val();

            if ((tab === 'internal' && !entityId) || (tab === 'external' && !authorityId)) {
                eligibleUsers = [];
                updateUserSelects();
                return;
            }

            let data = { tab: tab };
            if (tab === 'internal') data.entity_id = entityId;
            if (tab === 'external') data.authority_id = authorityId;

            $.ajax({
                url: "{{ route('admin.entity-stages.eligible-users') }}",
                data: data,
                success: function(users) {
                    eligibleUsers = users;
                    updateUserSelects();
                }
            });
        }

        $('#entity_id, #authority_id').change(fetchEligibleUsers);

        function updateUserSelects() {
            $('.stage-user-select').each(function() {
                let $select = $(this);
                let val = $select.val();
                $select.empty().append('<option value="">-- اختر المستخدم --</option>');
                eligibleUsers.forEach(user => {
                    let selected = (user.id == val) ? 'selected' : '';
                    $select.append(`<option value="${user.id}" ${selected}>${user.name}</option>`);
                });
            });
        }

        /**
         * إضافة مرحلة جديدة
         *
         * @param {string}  key
         * @param {string}  nameAr
         * @param {string}  nameEn
         * @param {array}   actions
         * @param {string}  responsibleUserId
         * @param {array}   attachments
         * @param {boolean} isFinal
         * @param {boolean} triggersExecution
         */
        function addStage(key = '', nameAr = '', nameEn = '', actions = ['approve', 'return'], responsibleUserId = '', attachments = [], isFinal = false, triggersExecution = false) {
            const template = $('#stage-template').html();
            const $newStage = $(template.replace(/__INDEX__/g, stageIndex));

            $newStage.find('.stage-badge').text('مرحلة ' + (stageIndex + 1));
            $newStage.find('.stage-title').text(nameAr || ('مرحلة ' + (stageIndex + 1)));
            $newStage.find('.stage-key-input').val(key);
            $newStage.find('.stage-name-ar-input').val(nameAr);
            $newStage.find('input[name="stages['+stageIndex+'][stage_name_en]"]').val(nameEn);

            // تحديث عنوان البطاقة تلقائياً
            $newStage.find('.stage-name-ar-input').on('input', function() {
                $newStage.find('.stage-title').text($(this).val() || ('مرحلة ' + (stageIndex + 1)));
            });

            // الإجراءات المتاحة
            $newStage.find('input[name*="[available_actions]"]').prop('checked', false);
            actions.forEach(action => {
                $newStage.find(`input[name*="[available_actions]"][value="${action}"]`).prop('checked', true);
            });

            // ✅ المرحلة النهائية وتفعيل التنفيذ
            $newStage.find('.stage-final-checkbox').prop('checked', !!isFinal);
            $newStage.find('input[name*="[triggers_execution]"]').prop('checked', !!triggersExecution);

            if (isFinal) {
                $newStage.find('.stage-final-badge').removeClass('d-none');
            }

            // Populate user select
            let $select = $newStage.find('.stage-user-select');
            eligibleUsers.forEach(user => {
                let selected = (user.id == responsibleUserId) ? 'selected' : '';
                $select.append(`<option value="${user.id}" ${selected}>${user.name}</option>`);
            });

            // ✅ إضافة المرفقات المطلوبة
            let currentStageIdx = stageIndex;
            let attList = $newStage.find('.attachments-list');
            if (attachments && attachments.length > 0) {
                attList.find('.no-attachments-hint').hide();
                attachments.forEach((att, attIdx) => {
                    const attTpl = $('#attachment-template').html()
                        .replace(/__INDEX__/g, currentStageIdx)
                        .replace(/__ATT_INDEX__/g, attIdx);
                    const $attRow = $(attTpl);
                    $attRow.find('input[name*="[key]"]').val(att.key);
                    $attRow.find('input[name*="[label]"]').val(att.label);
                    $attRow.find('input[name*="[required]"]').prop('checked', !!att.required);
                    attList.append($attRow);
                });
            }

            // زر إضافة مرفق جديد لهذه المرحلة
            $newStage.find('.add-attachment-btn').click(function() {
                attList.find('.no-attachments-hint').hide();
                let attCount = attList.find('.attachment-row').length;
                const attTpl = $('#attachment-template').html()
                    .replace(/__INDEX__/g, currentStageIdx)
                    .replace(/__ATT_INDEX__/g, attCount);
                attList.append(attTpl);
            });

            $('#stages-container').append($newStage);
            stageIndex++;

            // إذا كانت المرحلة النهائية، ألغِ تحديد الباقي
            if (isFinal) {
                enforceSingleFinalStage($newStage.find('.stage-final-checkbox'));
            }
        }

        $('#add-stage-btn').click(function() {
            addStage();
        });

        $(document).on('click', '.remove-stage-btn', function() {
            $(this).closest('.stage-row').remove();
            // Re-index titles
            let i = 1;
            $('#stages-container .stage-row').each(function() {
                $(this).find('.stage-badge').text('مرحلة ' + i);
                i++;
            });
        });

        $(document).on('click', '.remove-attachment-btn', function() {
            let container = $(this).closest('.attachments-list');
            $(this).closest('.attachment-row').remove();
            if (container.find('.attachment-row').length === 0) {
                container.find('.no-attachments-hint').show();
            }
        });

        // ✅ ضمان وجود مرحلة نهائية واحدة فقط
        function enforceSingleFinalStage($currentCheckbox) {
            if (!$currentCheckbox.is(':checked')) {
                return;
            }

            $('#stages-container .stage-final-checkbox').not($currentCheckbox).each(function() {
                let $other = $(this);
                if ($other.is(':checked')) {
                    $other.prop('checked', false);
                    $other.closest('.stage-row').find('.stage-final-badge').addClass('d-none');
                    $other.closest('.stage-row')
                        .find('input[name*="[triggers_execution]"]')
                        .prop('checked', false);
                }
            });
        }

        // ✅ ربط حدث تغيير checkbox المرحلة النهائية
        $(document).on('change', '.stage-final-checkbox', function() {
            let $checkbox = $(this);
            let $row = $checkbox.closest('.stage-row');

            if ($checkbox.is(':checked')) {
                enforceSingleFinalStage($checkbox);
                $row.find('.stage-final-badge').removeClass('d-none');
                // علّم "تفعيل التنفيذ" تلقائياً
                $row.find('input[name*="[triggers_execution]"]').prop('checked', true);
            } else {
                $row.find('.stage-final-badge').addClass('d-none');
            }
        });

        // Initialize default stages
        if ($('#stages-container').children().length === 0 && !window.hasExistingStages) {
            addStage('technical_review', 'مراجعة فنية', 'Technical Review', ['approve', 'return']);
            addStage('financial_review', 'مراجعة مالية', 'Financial Review', ['approve', 'return']);
            addStage('final_approval', 'اعتماد نهائي', 'Final Approval', ['approve', 'return', 'reject'], '', [], true, true);
        }

        $('#route_destination_type').on('change', function() {
            if ($(this).val() === 'authority') {
                $('#destination_authority_container').show();
                $('#destination_authority_container select').prop('required', true);
            } else {
                $('#destination_authority_container').hide();
                $('#destination_authority_container select').prop('required', false).val('').trigger('change');
            }
        });

        // ✅ تحقق قبل الإرسال
        $('#workflowForm').on('submit', function(e) {
            let finalCount = $('#stages-container .stage-final-checkbox:checked').length;

            if (finalCount === 0) {
                e.preventDefault();
                alert('⚠️ يجب تحديد مرحلة نهائية واحدة على الأقل قبل الحفظ.');
                return false;
            }

            if (finalCount > 1) {
                e.preventDefault();
                alert('⚠️ لا يمكن تحديد أكثر من مرحلة نهائية واحدة.');
                return false;
            }
        });
    });
</script>
@endsection
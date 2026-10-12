@extends('layouts.app')

@section('title', 'تعديل مسار الاعتماد - ' . ($tab === 'internal' ? $entity->name : $authority->name))

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-primary fw-bold">
                <i class="fas fa-edit me-2"></i>
                تعديل مسار الاعتماد: {{ $tab === 'internal' ? $entity->name : $authority->name }}
            </h4>
            <div class="d-flex gap-2 align-items-center mt-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-1 rounded-pill">
                    نوع المسار: {{ $workflowTypes[$workflowType] ?? $workflowType }}
                </span>
                <small class="text-muted">تعديل تسلسل المراحل، الصلاحيات، والمرفقات المطلوبة</small>
            </div>
        </div>
        <a href="{{ route('approvals.configurations.index', ['tab' => $tab, 'workflow_type' => $workflowType]) }}" class="btn btn-outline-secondary rounded-pill shadow-sm">
            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>يرجى تصحيح الأخطاء التالية:</h6>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('approvals.configurations.update', ['stage' => ($tab === 'internal' ? $entity->id : $authority->id), 'tab' => $tab]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" id="form-tab" value="{{ $tab }}">
                <input type="hidden" name="workflow_type" value="{{ $workflowType }}">
                
                @if($tab === 'internal')
                    <input type="hidden" name="entity_id" id="entity_id" value="{{ $entity->id }}">
                @else
                    <input type="hidden" name="authority_id" id="authority_id" value="{{ $authority->id }}">
                @endif
                
                {{-- External Route Destination --}}
                @if($tab === 'external')
                <div class="row g-4 mb-4 bg-light p-3 rounded-3 border">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">الوجهة التالية في المسار <span class="text-danger">*</span></label>
                        <select name="route_destination_type" id="route_destination_type" class="form-select" required>
                            <option value="ministry" @selected(old('route_destination_type', $route?->destination_type) === 'ministry')>الوزارة كجهة نهائية (Ministry Root)</option>
                            <option value="authority" @selected(old('route_destination_type', $route?->destination_type) === 'authority')>جهة خارجية أخرى</option>
                        </select>
                        <div class="form-text small">إلى أين يتم توجيه المعاملة بعد اعتماد هذه الجهة؟</div>
                    </div>
                    <div class="col-md-6" id="destination_authority_container" style="display: {{ old('route_destination_type', $route?->destination_type) === 'authority' ? 'block' : 'none' }};">
                        <label class="form-label fw-bold">الجهة الخارجية الوجهة <span class="text-danger">*</span></label>
                        <select name="destination_authority_id" class="form-select select2-search">
                            <option value="">-- اختر الجهة الخارجية الوجهة --</option>
                            @foreach($authorities as $authItem)
                                <option value="{{ $authItem->id }}" @selected(old('destination_authority_id', $route?->destination_authority_id) == $authItem->id)>
                                    {{ $authItem->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif

                {{-- Stages Repeater Header --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="fas fa-layer-group text-primary me-2"></i>مراحل المسار التتابعية</h5>
                        <p class="text-muted small mb-0">يمكنك تعديل المراحل أو إضافة مراحل جديدة. المراحل ذات المعاملات النشطة محمية من الحذف التلقائي.</p>
                    </div>
                    <button type="button" class="btn btn-outline-primary rounded-pill px-4 btn-sm fw-bold shadow-sm" id="add-stage-btn">
                        <i class="fas fa-plus me-1"></i> إضافة مرحلة جديدة
                    </button>
                </div>
                
                {{-- Container where stages are injected --}}
                <div id="stages-container"></div>

                {{-- Submit Buttons --}}
                <div class="d-flex justify-content-end gap-3 mt-5 pt-3 border-top">
                    <a href="{{ route('approvals.configurations.index', ['tab' => $tab, 'workflow_type' => $workflowType]) }}" class="btn btn-light rounded-pill px-4">
                        إلغاء
                    </a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                        <i class="fas fa-save me-1"></i> حفظ تعديلات المسار
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Stage Template --}}
<template id="stage-template">
    <div class="card border border-2 rounded-4 mb-4 stage-row shadow-sm" style="border-color: #cbd5e1 !important;">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center border-0">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold stage-badge">مرحلة 1</span>
                <span class="fw-bold text-dark stage-title-display">إعداد المرحلة</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle remove-stage-btn" title="حذف هذه المرحلة">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">الرمز البرمجي (Key) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm stage-key-input" name="stages[__INDEX__][stage]" required placeholder="مثال: financial_review">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">اسم المرحلة بالعربية <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm stage-name-ar-input" name="stages[__INDEX__][stage_name_ar]" required placeholder="مثال: التدقيق والمراجعة المالية">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">اسم المرحلة بالإنجليزية</label>
                    <input type="text" class="form-control form-control-sm stage-name-en-input" name="stages[__INDEX__][stage_name_en]" placeholder="مثال: Financial Audit">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">المسؤول المحدد (اختياري)</label>
                    <select name="stages[__INDEX__][responsible_user_id]" class="form-select form-select-sm stage-user-select">
                        <option value="">-- أي موظف مخول بالجهة --</option>
                    </select>
                </div>

                {{-- Permitted Actions Checklist --}}
                <div class="col-12 mt-3 pt-3 border-top">
                    <label class="form-label small fw-bold text-dark d-block mb-2">
                        <i class="fas fa-check-square text-success me-1"></i> الإجراءات المسموحة في هذه المرحلة:
                    </label>
                    <div class="d-flex gap-3 flex-wrap bg-white p-3 rounded-3 border">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="submit" id="act_sub___INDEX__">
                            <label class="form-check-label small fw-semibold text-info" for="act_sub___INDEX__">إرسال للاعتماد (Submit)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="approve" checked id="act_app___INDEX__">
                            <label class="form-check-label small fw-semibold text-success" for="act_app___INDEX__">موافقة / اعتماد (Approve)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="return" checked id="act_ret___INDEX__">
                            <label class="form-check-label small fw-semibold text-warning" for="act_ret___INDEX__">إعادة للاستكمال (Return)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="reject" checked id="act_rej___INDEX__">
                            <label class="form-check-label small fw-semibold text-danger" for="act_rej___INDEX__">رفض المعاملة (Reject)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="comment" checked id="act_com___INDEX__">
                            <label class="form-check-label small text-secondary" for="act_com___INDEX__">إضافة ملاحظات (Comments)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="upload_attachment" checked id="act_upl___INDEX__">
                            <label class="form-check-label small text-primary" for="act_upl___INDEX__">رفع مرفقات (Upload Attachment)</label>
                        </div>
                    </div>
                </div>

                {{-- Required Attachments Repeater for this Stage --}}
                <div class="col-12 mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold text-dark mb-0">
                            <i class="fas fa-paperclip text-primary me-1"></i> المرفقات المطلوبة في هذه المرحلة (إن وجدت):
                        </label>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill add-attachment-btn">
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

{{-- Attachment Row Template --}}
<template id="attachment-template">
    <div class="row g-2 align-items-center mb-2 attachment-row">
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm" name="stages[__INDEX__][required_attachments][__ATT_INDEX__][key]" required placeholder="رمز المرفق (مثال: clearance_receipt)">
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control form-control-sm" name="stages[__INDEX__][required_attachments][__ATT_INDEX__][label]" required placeholder="وصف المرفق (مثال: محضر الاستلام المالي)">
        </div>
        <div class="col-md-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="stages[__INDEX__][required_attachments][__ATT_INDEX__][required]" value="1" checked id="req___INDEX_____ATT_INDEX__">
                <label class="form-check-label small" for="req___INDEX_____ATT_INDEX__">إلزامي</label>
            </div>
        </div>
        <div class="col-md-1 text-center">
            <button type="button" class="btn btn-xs btn-link text-danger remove-attachment-btn"><i class="fas fa-times"></i></button>
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

        function fetchEligibleUsers(callback) {
            let entityId = $('#entity_id').val();
            let authorityId = $('#authority_id').val();
            let tab = $('#form-tab').val();

            let data = { tab: tab };
            if (tab === 'internal') data.entity_id = entityId;
            if (tab === 'external') data.authority_id = authorityId;

            $.ajax({
                url: "{{ route('approvals.configurations.eligible-users') }}",
                data: data,
                success: function(users) {
                    eligibleUsers = users;
                    updateUserSelects();
                    if (typeof callback === 'function') callback();
                }
            });
        }

        function updateUserSelects() {
            $('.stage-user-select').each(function() {
                let $select = $(this);
                let val = $select.data('selected-val') || $select.val();
                $select.empty().append('<option value="">-- أي موظف مخول بالجهة --</option>');
                eligibleUsers.forEach(user => {
                    let selected = (user.id == val) ? 'selected' : '';
                    $select.append(`<option value="${user.id}" ${selected}>${user.name}</option>`);
                });
            });
        }

        function addStage(key = '', nameAr = '', nameEn = '', actions = ['submit', 'approve', 'return', 'reject'], responsibleUserId = '', attachments = []) {
            const template = $('#stage-template').html();
            const $newStage = $(template.replace(/__INDEX__/g, stageIndex));
            
            $newStage.find('.stage-badge').text('مرحلة ' + (stageIndex + 1));
            $newStage.find('.stage-title-display').text(nameAr || ('مرحلة ' + (stageIndex + 1)));
            $newStage.find('.stage-key-input').val(key);
            $newStage.find('.stage-name-ar-input').val(nameAr);
            $newStage.find('.stage-name-en-input').val(nameEn);
            
            $newStage.find('.stage-name-ar-input').on('input', function() {
                $newStage.find('.stage-title-display').text($(this).val() || ('مرحلة ' + (stageIndex + 1)));
            });

            $newStage.find('input[type="checkbox"][name*="available_actions"]').prop('checked', false);
            actions.forEach(action => {
                $newStage.find(`input[value="${action}"]`).prop('checked', true);
            });

            let $select = $newStage.find('.stage-user-select');
            $select.data('selected-val', responsibleUserId);
            eligibleUsers.forEach(user => {
                let selected = (user.id == responsibleUserId) ? 'selected' : '';
                $select.append(`<option value="${user.id}" ${selected}>${user.name}</option>`);
            });

            // Add attachments if provided
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

            // Hook up add attachment button for this stage
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
        }

        $('#add-stage-btn').click(function() {
            addStage('', '', '', ['submit', 'approve', 'return', 'reject']);
        });

        $(document).on('click', '.remove-stage-btn', function() {
            $(this).closest('.stage-row').remove();
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

        // Load existing stages
        fetchEligibleUsers(function() {
            @foreach($stages as $st)
                @php
                    $rawActions = is_array($st->available_actions) ? $st->available_actions : (json_decode($st->available_actions, true) ?: ['approve', 'return']);
                    $rawAttachments = is_array($st->required_attachments) ? $st->required_attachments : (json_decode($st->required_attachments, true) ?: []);
                @endphp
                addStage(
                    '{{ $st->stage }}',
                    '{{ addslashes($st->stage_name_ar) }}',
                    '{{ addslashes($st->stage_name_en ?? '') }}',
                    {!! json_encode($rawActions) !!},
                    '{{ $st->responsible_user_id }}',
                    {!! json_encode($rawAttachments) !!}
                );
            @endforeach

            if ($('#stages-container').children().length === 0) {
                addStage('submitting_entity_review', 'مراجعة وتدقيق جهة التقديم', 'Submitting Entity Review', ['submit', 'approve', 'return']);
                addStage('financial_review', 'المراجعة والتدقيق المالي', 'Financial Audit', ['approve', 'return', 'reject']);
                addStage('executive_approval', 'اعتماد صاحب الصلاحية النهائي', 'Final Executive Approval', ['approve', 'return', 'reject']);
            }
        });

        $('#route_destination_type').on('change', function() {
            if ($(this).val() === 'authority') {
                $('#destination_authority_container').show();
                $('#destination_authority_container select').prop('required', true);
            } else {
                $('#destination_authority_container').hide();
                $('#destination_authority_container select').prop('required', false).val('').trigger('change');
            }
        });
    });
</script>
@endsection

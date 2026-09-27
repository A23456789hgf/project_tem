@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold">إضافة مراحل لجهة</h4>
            <small class="text-muted">تحديد مراحل المراجعة والاعتماد لجهة معينة</small>
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
            <form action="{{ route('admin.entity-stages.store', ['tab' => $tab]) }}" method="POST">
            @endcan
                @csrf
                <input type="hidden" name="tab" value="{{ $tab }}">
                
                <div class="row mb-5">
                    <div class="col-md-6">
                        @if($tab === 'internal')
                            <label class="form-label fw-bold">الجهة الداخلية <span class="text-danger">*</span></label>
                            <select name="entity_id" class="form-select select2-search" required>
                                <option value="">-- اختر الجهة الداخلية --</option>
                                @foreach($entities as $entity)
                                    <option value="{{ $entity->id }}" @selected(old('entity_id') == $entity->id)>
                                        {{ $entity->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <label class="form-label fw-bold">الجهة الخارجية <span class="text-danger">*</span></label>
                            <select name="authority_id" class="form-select select2-search" required>
                                <option value="">-- اختر الجهة الخارجية --</option>
                                @foreach($authorities as $authority)
                                    <option value="{{ $authority->id }}" @selected(old('authority_id') == $authority->id)>
                                        {{ $authority->name }}
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
                                    {{ $authority->name }}
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
                    
                    <div id="stages-container">
                        <!-- Default Stages injected by JS -->
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

<template id="stage-template">
    <div class="card border border-2 rounded-4 mb-3 stage-row">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="fw-bold text-primary mb-0 stage-title">مرحلة جديدة</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-stage-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">الاسم الداخلي (Key)</label>
                    <input type="text" class="form-control form-control-sm stage-key-input" name="stages[__INDEX__][stage]" required placeholder="مثال: technical_review">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">اسم المرحلة (عربي)</label>
                    <input type="text" class="form-control form-control-sm stage-name-ar-input" name="stages[__INDEX__][stage_name_ar]" required placeholder="مثال: مراجعة فنية">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">اسم المرحلة (إنجليزي)</label>
                    <input type="text" class="form-control form-control-sm" name="stages[__INDEX__][stage_name_en]" placeholder="مثال: Technical Review">
                </div>
                <div class="col-12 mt-3">
                    <label class="form-label small fw-bold">الإجراءات المتاحة</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="approve" checked>
                            <label class="form-check-label small">اعتماد (Approve)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="return" checked>
                            <label class="form-check-label small">إعادة (Return)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="reject">
                            <label class="form-check-label small">رفض (Reject)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="technical_review">
                            <label class="form-check-label small">اعتماد فني</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stages[__INDEX__][available_actions][]" value="financial_review">
                            <label class="form-check-label small">اعتماد مالي</label>
                        </div>
                    </div>
                </div>
            </div>
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

        function addStage(key = '', nameAr = '', nameEn = '', actions = ['approve', 'return']) {
            const template = $('#stage-template').html();
            const $newStage = $(template.replace(/__INDEX__/g, stageIndex));
            
            $newStage.find('.stage-title').text('مرحلة ' + (stageIndex + 1));
            $newStage.find('.stage-key-input').val(key);
            $newStage.find('.stage-name-ar-input').val(nameAr);
            $newStage.find('input[name="stages['+stageIndex+'][stage_name_en]"]').val(nameEn);
            
            $newStage.find('input[type="checkbox"]').prop('checked', false);
            actions.forEach(action => {
                $newStage.find(`input[value="${action}"]`).prop('checked', true);
            });

            $('#stages-container').append($newStage);
            stageIndex++;
        }

        $('#add-stage-btn').click(function() {
            addStage();
        });

        $(document).on('click', '.remove-stage-btn', function() {
            $(this).closest('.stage-row').remove();
            // Re-index titles
            let i = 1;
            $('#stages-container .stage-row').each(function() {
                $(this).find('.stage-title').text('مرحلة ' + i++);
            });
        });

        // Initialize default stages
        addStage('technical_review', 'مراجعة فنية', 'Technical Review', ['technical_review', 'return']);
        addStage('financial_review', 'مراجعة مالية', 'Financial Review', ['financial_review', 'return']);
        addStage('stage_approval', 'اعتماد', 'Approval', ['approve', 'return', 'reject']);

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

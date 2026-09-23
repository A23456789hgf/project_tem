@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-{{ $type == 'return' ? 'danger' : ($type == 'reply' ? 'success' : 'primary') }} text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-{{ $type == 'return' ? 'undo' : ($type == 'reply' ? 'reply' : 'envelope') }} me-2"></i>
                        @if($type == 'reply')
                            رد على مراسلة: {{ $parent->correspondence_number }}
                        @elseif($type == 'return')
                            إرجاع للمرسل: {{ $parent->correspondence_number }}
                        @else
                            {{ isset($correspondence) ? 'تعديل مراسلة' : 'مراسلة جديدة' }}
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @can('correspondence.edit')
                    <form action="{{ isset($correspondence) ? route('correspondence.update', $correspondence->id) : route('correspondence.store') }}" 
                    @endcan
                          method="POST" enctype="multipart/form-data" id="correspondence-form">
                        @csrf
                        @if(isset($correspondence))
                            @method('PUT')
                        @endif

                        <input type="hidden" name="parent_id" value="{{ $parent->id ?? '' }}">
                        <input type="hidden" name="correspondence_type" value="{{ $type ?? 'new' }}">

                        {{-- Approval Context Banner (يظهر فقط إذا كانت المراسلة مرتبطة بمشروع اعتماد) --}}
                        @php
                            $linkedProjectId = old('project_id', $correspondence->project_id ?? request('project_id'));
                            $linkedProject = $linkedProjectId ? \App\Models\Project::with(['creatorEntity', 'projectApprovals.entity'])->find($linkedProjectId) : null;
                            $activeApprovalStep = $linkedProject?->projectApprovals?->firstWhere('is_active', true) ?? $linkedProject?->projectApprovals?->first();
                            $activeDecisionType = request('decision_type') ?? ($correspondence->notes ?? null);
                        @endphp

                        @if($linkedProject)
                            <div class="card border-primary mb-4 shadow-sm" style="background-color: #f0f7ff; border-radius: 12px;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-primary-subtle">
                                        <h6 class="fw-bold text-primary mb-0">
                                            <i class="fas fa-file-contract me-2"></i> مراسلة مرتبطة بمشروع اعتماد
                                        </h6>
                                        @if($activeDecisionType)
                                            <span class="badge bg-primary px-3 py-1 rounded-pill small">
                                                قرار الاعتماد: {{ $activeDecisionType }}
                                            </span>
                                        @else
                                            <span class="badge bg-primary px-3 py-1 rounded-pill small">
                                                اعتماد مشروع
                                            </span>
                                        @endif
                                    </div>
                                    <div class="row g-2 small text-dark">
                                        <div class="col-md-6 col-lg-3">
                                            <span class="text-muted d-block" style="font-size: 0.78rem;">اسم المشروع:</span>
                                            <strong>{{ $linkedProject->project_name ?? $linkedProject->name }} ({{ $linkedProject->form_number }})</strong>
                                        </div>
                                        <div class="col-md-6 col-lg-3">
                                            <span class="text-muted d-block" style="font-size: 0.78rem;">الجهة الحالية:</span>
                                            <strong>{{ request('entity_name') ?? $activeApprovalStep?->entity?->name ?? 'الجهة المختصة' }}</strong>
                                        </div>
                                        <div class="col-md-6 col-lg-3">
                                            <span class="text-muted d-block" style="font-size: 0.78rem;">المرحلة الحالية:</span>
                                            <strong>{{ request('phase_name') ?? $activeApprovalStep?->getPhaseArabicName() ?? 'اعتماد المرحلة' }}</strong>
                                        </div>
                                        <div class="col-md-6 col-lg-3">
                                            <span class="text-muted d-block" style="font-size: 0.78rem;">الخطوة:</span>
                                            <strong>{{ request('step_order') ?? ($activeApprovalStep ? "الخطوة {$activeApprovalStep->step_order}" : 'خطوة الاعتماد') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($parent)
                            <div class="alert alert-info border-info d-flex align-items-center mb-4">
                                <i class="fas fa-link me-3 fa-2x"></i>
                                <div>
                                    <strong>سياق المراسلة:</strong> 
                                    هذه العملية مرتبطة بالمراسلة رقم 
                                    <a href="{{ route('correspondence.show', $parent->id) }}" class="alert-link" target="_blank">
                                        {{ $parent->correspondence_number }}
                                    </a>
                                    - {{ $parent->subject }}
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <!-- Sender Entity (Automatic) -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted">
                                    <i class="fas fa-building me-1"></i> الجهة المرسلة (تلقائياً)
                                </label>
                                <div class="form-control bg-light">
                                    <strong>{{ $senderEntity->name ?? Auth::user()->entity?->name ?? 'لم يتم تعيين جهة' }}</strong>
                                </div>
                                <small class="text-info mt-1 d-block"><i class="fas fa-info-circle me-1"></i> يتم تحديد جهتك تلقائياً كجهة مرسلة</small>
                            </div>

                            <!-- Recipient Entity -->
                            <div class="col-md-4 mb-3">
                                <label for="recipient_entity_id" class="form-label">
                                    الجهة المستلمة <span class="text-danger">*</span>
                                </label>
                                <select name="recipient_entity_id" id="recipient_entity_id" 
                                        class="form-select @error('recipient_entity_id') is-invalid @enderror" 
                                        required {{ isset($correspondence) ? 'disabled' : '' }}>
                                    <option value="">-- اختر الجهة المستلمة --</option>
                                    @foreach($entities as $entity)
                                        <option value="{{ $entity->id }}" 
                                                {{ (old('recipient_entity_id', $correspondence->recipient_entity_id ?? request('entity_id')) == $entity->id) ? 'selected' : '' }}>
                                            {{ $entity->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(isset($correspondence))
                                    <input type="hidden" name="recipient_entity_id" value="{{ $correspondence->recipient_entity_id }}">
                                @endif
                                @error('recipient_entity_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                             <div class="col-md-4 mb-3">
                                <label for="subject" class="form-label">
                                    الموضوع <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="subject" id="subject" 
                                       class="form-control @error('subject') is-invalid @enderror" 
                                       value="{{ old('subject', request('subject', isset($correspondence) ? $correspondence->subject : ($parent ? ($type == 'reply' ? 'رد: ' : 'إرجاع: ') . $parent->subject : ''))) }}" 
                                       placeholder="أدخل عنوان المراسلة..."
                                       required>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                         <!-- Project Linkage -->
                         <div class="row mb-3">
                             <div class="col-md-4">
                                 <div class="form-check mt-2">
                                     <input class="form-check-input" type="checkbox" id="within_project" 
                                            {{ (old('project_id', $correspondence->project_id ?? '') || request('project_id')) ? 'checked' : '' }}>
                                     <label class="form-check-label fw-bold" for="within_project">
                                         <i class="fas fa-project-diagram me-1 text-primary"></i> ضمن مشروع؟
                                     </label>
                                 </div>
                             </div>
                             <div class="col-md-8" id="project_selection_container" style="display: {{ (old('project_id', $correspondence->project_id ?? '') || request('project_id')) ? 'block' : 'none' }};">
                                 <label for="project_id" class="form-label text-primary fw-bold">اختر المشروع</label>
                                 <select name="project_id" id="project_id" class="form-select select2-ajax">
                                     @if(old('project_id'))
                                         @php $oldProject = \App\Models\Project::find(old('project_id')); @endphp
                                         @if($oldProject)
                                             <option value="{{ $oldProject->id }}" selected>{{ $oldProject->project_name ?? $oldProject->name }} ({{ $oldProject->form_number }})</option>
                                         @endif
                                     @elseif(isset($correspondence) && $correspondence->project_id)
                                         <option value="{{ $correspondence->project_id }}" selected>{{ $correspondence->project->project_name ?? $correspondence->project->name }} ({{ $correspondence->project->form_number }})</option>
                                     @elseif(request('project_id'))
                                         @php $reqProject = \App\Models\Project::find(request('project_id')); @endphp
                                         @if($reqProject)
                                             <option value="{{ $reqProject->id }}" selected>{{ $reqProject->project_name ?? $reqProject->name }} ({{ $reqProject->form_number }})</option>
                                         @endif
                                     @endif
                                 </select>
                                 <small class="text-muted mt-1 d-block">ابحث عن المشروع بالاسم أو رقم الاستمارة</small>
                             </div>
                         </div>

                        <!-- Message Content -->
                        <div class="mb-3">
                            <label for="message_body" class="form-label">
                                نص المراسلة <span class="text-danger">*</span>
                            </label>
                            <textarea name="message_body" id="message_body" rows="6" 
                                      class="form-control @error('message_body') is-invalid @enderror" 
                                      required>{{ old('message_body', $correspondence->message_body ?? '') }}</textarea>
                            @error('message_body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Attachments -->
                        <div class="mb-3">
                            <label for="attachments" class="form-label">
                                المرفقات
                            </label>
                            <input type="file" name="attachments[]" id="attachments" 
                                   class="form-control @error('attachments') is-invalid @enderror" 
                                   multiple>
                            <small class="form-text text-muted">
                                يمكنك رفع ملفات متعددة (PDF, Word, Excel, الصور) - الحد الأقصى 10 ميجابايت لكل ملف
                            </small>
                            @error('attachments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <!-- عرض المرفقات الحالية مع إمكانية الحذف -->
                            @if(isset($correspondence) && $correspondence->attachment_count > 0)
                                <div class="mt-3">
                                    <label class="form-label">المرفقات الحالية:</label>
                                    <div class="list-group">
                                        @foreach($correspondence->attachments as $index => $attachment)
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-file me-2"></i>
                                                    <span>{{ $attachment['original_name'] }}</span>
                                                    <small class="text-muted ms-2">
                                                        ({{ number_format($attachment['size'] / 1024, 2) }} ك.ب)
                                                    </small>
                                                </div>
                                                <div>
                                                    <a href="{{ route('correspondence.download', ['id' => $correspondence->id, 'type' => 'correspondence', 'index' => $loop->index]) }}" 
                                                       class="btn btn-sm btn-primary me-1" 
                                                       target="_blank">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    @if($correspondence->canBeEdited())
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger remove-attachment" 
                                                                data-index="{{ $loop->index }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <input type="hidden" 
                                                               name="remove_attachments[]" 
                                                               id="remove_attachment_{{ $loop->index }}" 
                                                               value="">
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Priority and Confidential -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">
                                    الأولوية
                                </label>
                                <select name="priority" id="priority" class="form-select">
                                    <option value="normal" {{ old('priority', $correspondence->priority ?? 'normal') == 'normal' ? 'selected' : '' }}>
                                        عادية
                                    </option>
                                    <option value="high" {{ old('priority', $correspondence->priority ?? '') == 'high' ? 'selected' : '' }}>
                                        عالية
                                    </option>
                                    <option value="urgent" {{ old('priority', $correspondence->priority ?? '') == 'urgent' ? 'selected' : '' }}>
                                        عاجلة
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" 
                                           name="confidential" id="confidential" 
                                           value="1" {{ old('confidential', $correspondence->confidential ?? 0) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="confidential">
                                        مراسلة سرية
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                ملاحظات
                            </label>
                            <textarea name="notes" id="notes" rows="3" 
                                      class="form-control">{{ old('notes', $correspondence->notes ?? '') }}</textarea>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('correspondence.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                {{ isset($correspondence) ? 'تحديث المراسلة' : 'إرسال المراسلة' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/ar.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 for entity selector
    $('#recipient_entity_id').select2({
        placeholder: 'اختر الجهة...',
        allowClear: true,
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            }
        }
    });

    // Handle removal of existing attachments
    $('.remove-attachment').on('click', function() {
        const index = $(this).data('index');
        const removeInput = $('#remove_attachment_' + index);
        
        // Toggle removal
        if (removeInput.val() === '') {
            removeInput.val(index);
            $(this).closest('.list-group-item').addClass('bg-danger text-white');
            $(this).html('<i class="fas fa-undo"></i>');
        } else {
            removeInput.val('');
            $(this).closest('.list-group-item').removeClass('bg-danger text-white');
            $(this).html('<i class="fas fa-trash"></i>');
        }
    });

    // Form validation to prevent sending to the same entity
    $('#correspondence-form').on('submit', function(e) {
        const recipientEntityId = $('#recipient_entity_id').val();
        if (!recipientEntityId) {
            e.preventDefault();
            alert('يرجى اختيار الجهة المستلمة');
            return false;
        }
    });

    // File size validation (client-side)
    $('#attachments').on('change', function() {
        const maxSize = 10 * 1024 * 1024; // 10MB in bytes
        const files = this.files;
        
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
                alert(`الملف ${files[i].name} يتجاوز الحد الأقصى المسموح به (10 ميجابايت)`);
                $(this).val('');
                return;
            }
        }
    });

    // Project Linkage Logic
    const withinProjectCb = $('#within_project');
    const projectContainer = $('#project_selection_container');
    const projectSelect = $('#project_id');

    withinProjectCb.on('change', function() {
        if ($(this).is(':checked')) {
            projectContainer.slideDown();
        } else {
            projectContainer.slideUp();
            projectSelect.val(null).trigger('change');
        }
    });

    // Initialize Select2 for Projects (AJAX)
    projectSelect.select2({
        placeholder: 'ابحث عن مشروع...',
        allowClear: true,
        language: "ar",
        dir: "rtl",
        ajax: {
            url: "{{ route('correspondence.search-projects') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            error: function (xhr, status, error) {
                console.error('Project search error:', error);
            },
            cache: true
        },
        minimumInputLength: 0
    });
});
</script>
@endpush
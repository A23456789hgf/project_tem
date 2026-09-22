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
                                       value="{{ old('subject', isset($correspondence) ? $correspondence->subject : ($parent ? ($type == 'reply' ? 'رد: ' : 'إرجاع: ') . $parent->subject : request('subject', ''))) }}" 
                                       placeholder="أدخل عنوان المراسلة..."
                                       required>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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

                        <!-- قسم التوقيع -->
                        @if(!isset($correspondence))
                            <div class="mb-4 p-3 border rounded bg-light">
                                <label class="form-label fw-bold d-flex align-items-center mb-3">
                                    <i class="fas fa-signature text-success ms-0 me-2" style="font-size: 1.2rem;"></i>
                                    توقيع المراسلة واعتمادها
                                </label>
                                
                                <div class="form-check form-switch mb-3 d-flex align-items-center ps-0">
                                    <input class="form-check-input mt-0 ms-0" type="checkbox" id="sign_now_toggle" name="sign_now" value="1" style="width: 2.5em; height: 1.4em; cursor: pointer; margin-right: 0;">
                                    <label class="form-check-label fw-bold mx-2" for="sign_now_toggle" style="cursor: pointer;">توقيع المراسلة الآن</label>
                                </div>

                                <div id="create_signature_section" style="display: none;">
                                    <input type="hidden" name="create_signature" id="createSignatureDataInput">
                                    
                                    @if(auth()->user()->hasSignature())
                                        <div class="alert alert-info d-flex align-items-center mb-3">
                                            <div class="bg-white p-1 rounded border me-3 d-flex align-items-center justify-content-center" style="min-width: 80px; height: 60px;">
                                                <img src="{{ Storage::url(auth()->user()->signature_path) }}" alt="توقيعك" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                            </div>
                                            <div>
                                                <strong>لديك توقيع محفوظ مسبقاً!</strong>
                                                <p class="mb-0 small">سيتم اعتماد هذا التوقيع تلقائياً عند الإرسال. يمكنك رسم توقيع جديد بالأسفل فقط إذا أردت تغييره وحفظه كبديل.</p>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-info-circle"></i> ارسم توقيعك بوضوح لاعتماده (سيتم حفظه بملفك الشخصي).
                                        </p>
                                    @endif

                                    <div class="signature-canvas-wrapper" style="border: 2px dashed #cbd5e1; border-radius: 8px; background: #fff; position: relative; width: fit-content; overflow: hidden;">
                                        <canvas id="createSignatureCanvas" width="450" height="200"></canvas>
                                        <div id="createSignaturePlaceholder" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; color: #94a3b8; text-align: center; transition: opacity 0.2s;">
                                            <i class="fas fa-pen-fancy fs-3 mb-1 d-block"></i>
                                            <span class="fw-bold">ارسم توقيعك هنا</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="clearCreateSignature()">
                                        <i class="fas fa-eraser"></i> مسح التوقيع
                                    </button>
                                </div>
                            </div>
                        @endif

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

    // Signature Logic
    let createCanvas, createCtx, createDrawing = false, createHasDrawn = false;
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
            createDrawing = true; createHasDrawn = true;
            createCtx.beginPath(); createCtx.moveTo(x, y);
            const ph = document.getElementById('createSignaturePlaceholder');
            if (ph) ph.style.opacity = '0';
        }
        function drawCreate(x, y) {
            if (!createDrawing) return;
            createCtx.lineTo(x, y); createCtx.stroke();
        }
        function stopCreateDrawing() { createDrawing = false; }
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
        createCanvas.addEventListener('touchstart', (e) => { startCreateDrawing(getCreateX(e), getCreateY(e)); e.preventDefault(); }, {passive: false});
        createCanvas.addEventListener('touchmove', (e) => { drawCreate(getCreateX(e), getCreateY(e)); e.preventDefault(); }, {passive: false});
        window.addEventListener('touchend', stopCreateDrawing);
    }

    window.clearCreateSignature = function() {
        if (!createCtx || !createCanvas) return;
        createCtx.clearRect(0, 0, createCanvas.width, createCanvas.height);
        createHasDrawn = false;
        const ph = document.getElementById('createSignaturePlaceholder');
        if (ph) ph.style.opacity = '1';
        document.getElementById('createSignatureDataInput').value = '';
    }

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
});
</script>
@endpush
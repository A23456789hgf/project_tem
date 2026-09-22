@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-warning text-dark py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>تعديل المذكرة: {{ $memoir->memoir_number }}</h5>
                    <a href="{{ route('memoirs.index') }}" class="btn btn-dark btn-sm shadow-sm text-white">
                        <i class="fas fa-arrow-right me-1"></i>العودة للقائمة
                    </a>
                </div>
                <div class="card-body p-4">
                    @can('memoirs.edit')
                    <form action="{{ route('memoirs.update', $memoir->id) }}" method="POST">
                    @endcan
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-4 mb-4">
                            <!-- To -->
                            <div class="col-md-12">
                                <label for="to" class="form-label fw-bold small">موجهة إلى <span class="text-danger">*</span></label>
                                <input type="text" name="to" id="to" class="form-control shadow-sm @error('to') is-invalid @enderror" 
                                       placeholder="مثلاً: مدير عام الادارة المالية" value="{{ old('to', $memoir->to) }}" required>
                                @error('to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Subject -->
                            <div class="col-md-12">
                                <label for="subject" class="form-label fw-bold small">الموضوع <span class="text-danger">*</span></label>
                                <input type="text" name="subject" id="subject" class="form-control shadow-sm @error('subject') is-invalid @enderror" 
                                       placeholder="عنوان الموضوع باختصار" value="{{ old('subject', $memoir->subject) }}" required>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Dates -->
                            <div class="col-md-6">
                                <label for="gregorian_date" class="form-label fw-bold small">التاريخ الميلادي <span class="text-danger">*</span></label>
                                <input type="date" name="gregorian_date" id="gregorian_date" class="form-control shadow-sm @error('gregorian_date') is-invalid @enderror" 
                                       value="{{ old('gregorian_date', $memoir->gregorian_date) }}" required>
                                @error('gregorian_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="hijri_date" class="form-label fw-bold small">التاريخ الهجري <span class="text-danger">*</span></label>
                                <input type="text" name="hijri_date" id="hijri_date" class="form-control shadow-sm border-0 bg-light @error('hijri_date') is-invalid @enderror" 
                                       value="{{ old('hijri_date', $memoir->hijri_date) }}" required readonly>
                                <small class="text-muted italic">التاريخ الهجري يتم تحديثه تلقائياً.</small>
                                @error('hijri_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Body -->
                            <div class="col-md-12">
                                <label for="body" class="form-label fw-bold small">نص المذكرة <span class="text-danger">*</span></label>
                                <textarea name="body" id="body" class="form-control shadow-sm @error('body') is-invalid @enderror" 
                                          rows="12" placeholder="اكتب نص المذكرة هنا بالتفصيل..." required>{{ old('body', $memoir->body) }}</textarea>
                                @error('body')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Project Linkage -->
                            <div class="col-md-12 border-top pt-4 mt-2">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <label class="form-label fw-bold mb-3 d-block">هل هذه المذكرة جزء من مشروع؟</label>
                                        @php $hasProject = old('is_project_related', $memoir->project_id ? 'yes' : 'no') == 'yes'; @endphp
                                        <div class="d-flex gap-4 mb-3">
                                            <div class="form-check custom-radio">
                                                <input class="form-check-input" type="radio" name="is_project_related" id="is_project_no" value="no" {{ !$hasProject ? 'checked' : '' }}>
                                                <label class="form-check-label px-2" for="is_project_no">لا</label>
                                            </div>
                                            <div class="form-check custom-radio">
                                                <input class="form-check-input" type="radio" name="is_project_related" id="is_project_yes" value="yes" {{ $hasProject ? 'checked' : '' }}>
                                                <label class="form-check-label px-2" for="is_project_yes">نعم</label>
                                            </div>
                                        </div>

                                        <div id="project_selection_container" style="display: {{ $hasProject ? 'block' : 'none' }};" class="animate__animated animate__fadeIn">
                                            <label for="project_id" class="form-label fw-bold small text-primary">المشروع المرتبط الحالي</label>
                                            <select name="project_id" id="project_id" class="form-select select2-project shadow-sm" data-placeholder="ابحث عن اسم المشروع هنا...">
                                                <option value=""></option>
                                                @foreach($projects as $project)
                                                    <option value="{{ $project->id }}" 
                                                            data-form-number="{{ $project->form_number }}"
                                                            {{ old('project_id', $memoir->project_id) == $project->id ? 'selected' : '' }}>
                                                        {{ $project->project_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            
                                            <div id="project_number_display" class="mt-2" style="display: {{ $hasProject ? 'block' : 'none' }};">
                                                <div class="d-flex align-items-center p-2 bg-white rounded border border-primary-subtle shadow-sm">
                                                    <div class="me-2 text-primary">
                                                        <i class="fas fa-hashtag"></i>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted small d-block">رقم المشروع المرجعي:</span>
                                                        <span id="project_form_number_text" class="fw-bold text-dark">{{ $memoir->project?->form_number ?? '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            @error('project_id')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('memoirs.index') }}" class="btn btn-outline-secondary px-4 me-md-2 shadow-sm">
                                <i class="fas fa-times me-1"></i>إلغاء
                            </a>
                            <button type="submit" class="btn btn-warning px-5 shadow-sm text-dark fw-bold">
                                <i class="fas fa-save me-1"></i>تحديث المذكرة
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light border-0 py-3 text-muted small">
                    <i class="fas fa-history me-1 text-warning"></i> آخر تحديث بواسطة: <strong>{{ $memoir->updater?->name ?? $memoir->creator?->name }}</strong> في {{ $memoir->updated_at }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2
        if ($('.select2-project').length > 0) {
            $('.select2-project').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dir: 'rtl',
                allowClear: true,
                dropdownParent: $('#project_selection_container')
            });
        }

        // Project Selection Toggle Logic
        $('input[name="is_project_related"]').on('change', function() {
            const container = $('#project_selection_container');
            const projectSelect = $('#project_id');
            const numberDisplay = $('#project_number_display');
            
            if (this.value === 'yes') {
                container.show();
                projectSelect.prop('required', true);
            } else {
                container.hide();
                numberDisplay.hide();
                projectSelect.prop('required', false);
                projectSelect.val(null).trigger('change');
            }
        });

        // Update Project Number when selection changes
        $('#project_id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const formNumber = selectedOption.data('form-number');
            const display = $('#project_number_display');
            const textSpan = $('#project_form_number_text');

            if (formNumber) {
                textSpan.text(formNumber);
                display.fadeIn();
            } else {
                display.fadeOut();
            }
        });

        // Date sync logic
        $('#gregorian_date').on('change', function() {
            // Logic to update hijri_date if needed
        });
    });
</script>
@endpush

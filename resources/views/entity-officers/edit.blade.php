@extends('layouts.app')

@section('title', 'تعديل مسؤول جهة')

@section('content')
@include('configuration.shared_styles')

<div class="container">
    <div class="main-card shadow-lg border-0 rounded-4">
        <div class="page-header mb-4 p-4 bg-light rounded-4">
            <h2 class="fw-bold mb-0" style="color: #001f3f;">
                <i class="fas fa-edit text-warning me-2"></i> تعديل بيانات المسؤول
            </h2>
            <div class="title-line mt-2"></div>
        </div>

        @can('entity-officers.edit')
        <form action="{{ route('entity-officers.update', $entityOfficer) }}" method="POST" class="p-4">
        @endcan
            @csrf
            @method('PUT')
            
            <div class="row g-4">
                {{-- نوع الجهة --}}
                <div class="col-md-12">
                    <label class="field-label d-block mb-3 fw-bold text-dark">نوع الجهة</label>
                    <div class="d-flex gap-5">
                        <div class="form-check custom-radio">
                            <input class="form-check-input" type="radio" name="entity_type" id="type_internal" value="internal" {{ old('entity_type', $entityOfficer->entity_type) === 'internal' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold cursor-pointer" for="type_internal">
                                <i class="fas fa-building-user text-primary me-1"></i> جهة داخلية
                            </label>
                        </div>
                        <div class="form-check custom-radio">
                            <input class="form-check-input" type="radio" name="entity_type" id="type_external" value="external" {{ old('entity_type', $entityOfficer->entity_type) === 'external' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold cursor-pointer" for="type_external">
                                <i class="fas fa-building-columns text-warning me-1"></i> جهة خارجية
                            </label>
                        </div>
                    </div>
                </div>

                {{-- الجهة (داخلية) --}}
                <div class="col-md-6 {{ old('entity_type', $entityOfficer->entity_type) === 'external' ? 'd-none' : '' }}" id="internal_entity_wrapper">
                    <label for="internal_entity_id" class="field-label fw-bold">الجهة الداخلية</label>
                    <select name="internal_entity_id" id="internal_entity_id" class="form-select select2-enable @error('internal_entity_id') is-invalid @enderror">
                        <option value="">-- اختر الجهة الداخلية --</option>
                        @foreach($internalEntities as $entity)
                            <option value="{{ $entity->id }}" {{ old('internal_entity_id', $entityOfficer->internal_entity_id) == $entity->id ? 'selected' : '' }}>
                                {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('internal_entity_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- الجهة (خارجية) --}}
                <div class="col-md-6 {{ old('entity_type', $entityOfficer->entity_type) === 'internal' ? 'd-none' : '' }}" id="authority_wrapper">
                    <label for="authority_id" class="field-label fw-bold">الجهة الخارجية</label>
                    <select name="authority_id" id="authority_id" class="form-select select2-enable @error('authority_id') is-invalid @enderror">
                        <option value="">-- اختر الجهة الخارجية --</option>
                        @foreach($authorities as $authority)
                            <option value="{{ $authority->id }}" {{ old('authority_id', $entityOfficer->authority_id) == $authority->id ? 'selected' : '' }}>
                                {{ $authority->agency_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('authority_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- اسم المسؤول --}}
                <div class="col-md-6">
                    <label for="admin_name" class="field-label fw-bold">اسم المسؤول / المدير</label>
                    <input type="text" name="admin_name" id="admin_name" class="form-control custom-field @error('admin_name') is-invalid @enderror" required value="{{ old('admin_name', $entityOfficer->admin_name) }}" placeholder="أدخل اسم المسؤول الكامل">
                    @error('admin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- المسمى الوظيفي --}}
                <div class="col-md-6">
                    <label for="job_title" class="field-label fw-bold">المسمى الوظيفي</label>
                    <input type="text" name="job_title" id="job_title" class="form-control custom-field @error('job_title') is-invalid @enderror" required value="{{ old('job_title', $entityOfficer->job_title) }}" placeholder="أدخل المسمى الوظيفي (مثال: مدير عام)">
                    @error('job_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex gap-3">
                <button type="submit" class="btn btn-warning px-5 rounded-3 fw-bold shadow-sm hover-lift text-dark">
                    <i class="fas fa-save me-1"></i> تحديث البيانات
                </button>
                <a href="{{ route('entity-officers.index') }}" class="btn btn-outline-secondary px-4 rounded-3">
                    <i class="fas fa-times me-1"></i> إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2-enable').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dir: 'rtl',
            dropdownParent: $('body')
        });
    }

    // Toggle Entity Type
    $('input[name="entity_type"]').change(function() {
        const type = $(this).val();
        if (type === 'internal') {
            $('#internal_entity_wrapper').removeClass('d-none');
            $('#authority_wrapper').addClass('d-none');
        } else {
            $('#internal_entity_wrapper').addClass('d-none');
            $('#authority_wrapper').removeClass('d-none');
        }
    });
});
</script>
<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
@endsection
@endsection

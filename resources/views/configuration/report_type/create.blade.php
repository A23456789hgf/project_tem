@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة نوع تقرير جديد</h2>
        <p class="text-muted small">يرجى تعبئة البيانات المطلوبة — اسم نوع التقرير يجب أن يكون فريداً</p>
        <div class="section-divider"></div>
    </div>

    @can('reports.view')
    <form action="{{ route('report-types.store') }}" method="POST">
        @csrf

        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="name" class="field-label">
                    اسم نوع التقرير <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control custom-field @error('name') is-invalid @enderror"
                       name="name" id="name"
                       placeholder="أدخل اسم نوع التقرير..."
                       value="{{ old('name') }}"
                       required>
                @error('name')
                <small class="text-danger mt-1 d-block"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</small>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="is_active" class="field-label">الحالة</label>
                <select name="is_active" id="is_active" class="form-select custom-field">
                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>مفعل</option>
                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>معطل</option>
                </select>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('report-types.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-times me-2"></i> إلغاء العملية
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm">
                        <i class="fas fa-save ms-2"></i> حفظ
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
@endsection

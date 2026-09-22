@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل الدليل الرئيسي: <span style="color: #D4AF37;">{{ $mainGuide->guide_name }}</span></h2>
        <p class="text-muted small">قم بتحديث البيانات ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    @can('configuration.main_guides.edit')
    <form action="{{ route('configuration.main_guides.update', $mainGuide->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-12">
                <label for="guide_name" class="field-label">الدليل الرئيسي</label>
                <input type="text" class="form-control custom-field @error('guide_name') is-invalid @enderror" 
                       name="guide_name" id="guide_name" placeholder="أدخل الاسم..." 
                       value="{{ old('guide_name', $mainGuide->guide_name) }}" required>
                @error('guide_name') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
            </div>

            <div class="col-12 mt-4">
                <div class="switch-box">
                    <div class="form-check form-switch d-flex align-items-center m-0">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;"
                            {{ old('is_active', $mainGuide->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                            الحالة (نشط)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('configuration.main_guides.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-arrow-right me-2"></i> إلغاء والعودة
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm">
                        <i class="fas fa-sync-alt ms-2"></i> تحديث البيانات
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
@endsection
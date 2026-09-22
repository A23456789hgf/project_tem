@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل البرنامج: <span style="color: #D4AF37;">{{ $program->name }}</span></h2>
        <p class="text-muted small">قم بتحديث بيانات البرنامج ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    

    @can('programs.edit')
    <form action="{{ route('programs.update', $program->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="name" class="field-label">اسم البرنامج</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل المسمى الجديد هنا..."
                       value="{{ old('name', $program->name) }}" required>
            </div>

            <div class="col-md-6">
                <div class="switch-box h-100 d-flex align-items-center">
                    <div class="form-check form-switch d-flex align-items-center m-0">
                        <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;" 
                               name="is_active" id="is_active" value="1" 
                               {{ (old('is_active') ?? $program->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                            حالة البرنامج (نشط)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('programs.index') }}" class="btn btn-cancel-custom">
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

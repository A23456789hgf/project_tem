@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل الموجه الرئيسي: <span style="color: #D4AF37;">{{ $router->main_router }}</span></h2>
        <p class="text-muted small">قم بتحديث البيانات ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    

    @can('main-routers.edit')
    <form action="{{ route('main-routers.update', $router->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-12">
                <label for="main_router" class="field-label">اسم الموجه</label>
                <input type="text" class="form-control custom-field @error('main_router') is-invalid @enderror" 
                       name="main_router" id="main_router" placeholder="أدخل الاسم..." 
                       value="{{ old('main_router', $router->main_router) }}" required>
            </div>

            <div class="col-12 mt-4">
                <div class="switch-box">
                    <div class="form-check form-switch d-flex align-items-center m-0">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;"
                            {{ old('is_active', $router->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                            الحالة (نشط)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('main-routers.index') }}" class="btn btn-cancel-custom">
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
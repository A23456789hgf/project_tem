@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة موجه فرعي جديد</h2>
        <p class="text-muted small">يرجى تعبئة البيانات المطلوبة</p>
        <div class="section-divider"></div>
    </div>

    @can('sub-routers.create')
    <form action="{{ route('sub-routers.store') }}" method="POST">
        @csrf
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="main_router_id" class="field-label">الموجه الرئيسي</label>
                <select name="main_router_id" id="main_router_id" class="form-select custom-field" required>
                    <option value="">اختر الموجه الرئيسي</option>
                    @foreach($mainRouters as $router)
                        <option value="{{ $router->id }}">{{ $router->main_router }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="sub_router" class="field-label">الموجه الفرعي</label>
                <input type="text" class="form-control custom-field @error('sub_router') is-invalid @enderror" 
                       name="sub_router" id="sub_router" placeholder="أدخل اسم الموجه الفرعي..." value="{{ old('sub_router') }}" required>
            </div>

            <div class="col-md-12">
                <label for="is_active" class="field-label">الحالة (نشط)</label>
                <select name="is_active" id="is_active" class="form-select custom-field">
                    <option value="1">نعم (نشط)</option>
                    <option value="0">لا (غير نشط)</option>
                </select>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('sub-routers.index') }}" class="btn btn-cancel-custom">
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

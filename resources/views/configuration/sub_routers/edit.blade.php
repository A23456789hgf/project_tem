@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل الموجه الفرعي: <span style="color: #D4AF37;">{{ $subRouter->sub_router }}</span></h2>
        <p class="text-muted small">قم بتحديث البيانات ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    @can('sub-routers.edit')
    <form action="{{ route('sub-routers.update', $subRouter->id) }}" method="POST">
    @endcan
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="main_router_id" class="field-label">الموجه الرئيسي</label>
                <select name="main_router_id" id="main_router_id" class="form-select custom-field" required>
                    @foreach($mainRouters as $router)
                        <option value="{{ $router->id }}" {{ $subRouter->main_router_id == $router->id ? 'selected' : '' }}>
                            {{ $router->main_router }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="sub_router" class="field-label">الموجه الفرعي</label>
                <input type="text" class="form-control custom-field" name="sub_router" id="sub_router" 
                       value="{{ $subRouter->sub_router }}" required>
            </div>

            <div class="col-md-12">
                <label for="is_active" class="field-label">الحالة (نشط)</label>
                <select name="is_active" id="is_active" class="form-select custom-field">
                    <option value="1" {{ $subRouter->is_active ? 'selected' : '' }}>نعم (نشط)</option>
                    <option value="0" {{ !$subRouter->is_active ? 'selected' : '' }}>لا (غير نشط)</option>
                </select>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('sub-routers.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-arrow-right me-2"></i> إلغاء والعودة
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm">
                        <i class="fas fa-sync-alt ms-2"></i> تحديث البيانات
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

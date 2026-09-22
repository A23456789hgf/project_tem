@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل بند مالي: <span style="color: #D4AF37;">{{ $financialItem->name }}</span></h2>
        <p class="text-muted small">قم بتحديث البيانات ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    @can('financial-items.edit')
    <form action="{{ route('financial-items.update', $financialItem->id) }}" method="POST">
    @endcan
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-4">
                <label for="code" class="field-label">رقم البند</label>
                <input type="text" id="code" name="code" class="form-control custom-field @error('code') is-invalid @enderror" 
                       value="{{ old('code', $financialItem->code) }}" required>
                @error('code') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-8">
                <label for="name" class="field-label">اسم البند</label>
                <input type="text" id="name" name="name" class="form-control custom-field @error('name') is-invalid @enderror" 
                       value="{{ old('name', $financialItem->name) }}" required>
                @error('name') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-12">
                <div class="switch-box h-100 d-flex align-items-center">
                    <div class="form-check form-switch d-flex align-items-center m-0">
                        <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;" 
                               name="is_active" id="is_active" value="1" {{ old('is_active', $financialItem->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                            حالة البند المالي (نشط)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('financial-items.index') }}" class="btn btn-cancel-custom">
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

@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل الأولوية: <span style="color: #D4AF37;">{{ $priority->priority }}</span></h2>
        <p class="text-muted small">قم بتحديث البيانات ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    @can('priorities.edit')
    <form action="{{ route('priorities.update', $priority->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="priority" class="field-label">اسم الأولوية</label>
                <input type="text" class="form-control custom-field @error('priority') is-invalid @enderror" 
                       name="priority" id="priority" placeholder="أدخل الاسم..." 
                       value="{{ old('priority', $priority->priority) }}" required>
                @error('priority') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-6">
                <label for="is_enabled" class="field-label">الحالة</label>
                <select name="is_enabled" id="is_enabled" class="form-select custom-field">
                    <option value="1" @if($priority->is_enabled) selected @endif>مفعلة</option>
                    <option value="0" @if(!$priority->is_enabled) selected @endif>معطلة</option>
                </select>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('priorities.index') }}" class="btn btn-cancel-custom">
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

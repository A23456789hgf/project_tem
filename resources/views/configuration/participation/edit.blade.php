@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل الجهة المشاركة: <span style="color: #D4AF37;">{{ $participant->name }}</span></h2>
        <p class="text-muted small">قم بتحديث البيانات ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    

    @can('participation.edit')
    <form action="{{ route('participation.update', $participant->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="name" class="field-label">اسم الجهة</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل الاسم..." 
                       value="{{ old('name', $participant->name) }}" required autofocus>
            </div>

            <div class="col-md-6">
                <label for="is_active" class="field-label">الحالة</label>
                <select name="is_active" id="is_active" class="form-select custom-field">
                    <option value="1" {{ $participant->is_active ? 'selected' : '' }}>مفعلة</option>
                    <option value="0" {{ !$participant->is_active ? 'selected' : '' }}>غير مفعلة</option>
                </select>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('participation.index') }}" class="btn btn-cancel-custom">
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

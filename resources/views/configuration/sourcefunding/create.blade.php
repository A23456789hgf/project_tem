@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة مصدر تمويل جديد</h2>
        <p class="text-muted small">يرجى تعبئة البيانات المطلوبة</p>
        <div class="section-divider"></div>
    </div>

    

    @can('funding-sources.create')
    <form action="{{ route('funding-sources.store') }}" method="POST">
        @csrf
        
        <div class="row g-4 align-items-end">
            <div class="col-md-12">
                <label for="name" class="field-label">اسم المصدر</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل اسم مصدر التمويل..." value="{{ old('name') }}" required>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('funding-sources.index') }}" class="btn btn-cancel-custom">
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

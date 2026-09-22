@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة فئة مستفيدة جديدة</h2>
        <p class="text-muted small">يرجى تعبئة البيانات المطلوبة</p>
        <div class="section-divider"></div>
    </div>

    @can('beneficiary-groups.create')
    <form action="{{ route('beneficiary-groups.store') }}" method="POST">
        @csrf
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="name" class="field-label">اسم الفئة المستفيدة *</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل اسم الفئة المستفيدة..." value="{{ old('name') }}" required>
                @error('name') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('beneficiary-groups.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-arrow-right me-2"></i> رجوع
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
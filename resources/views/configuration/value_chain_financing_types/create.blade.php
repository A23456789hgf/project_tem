@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة نوع تمويل جديد</h2>
        <p class="text-muted small">يرجى إدخال اسم النوع لإتمام عملية الإضافة</p>
        <div class="section-divider"></div>
    </div>

    

    @can('value-chain-financing-types.edit')
    <form action="{{ route('value-chain-financing-types.store') }}" method="POST">
        @csrf
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="name" class="field-label">اسم النوع</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل اسم النوع هنا..."
                       value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <div class="switch-box h-100 d-flex align-items-center">
                    <div class="form-check form-switch d-flex align-items-center m-0">
                        <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;" 
                               name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                            حالة النوع (نشط)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('value-chain-financing-types.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-times me-2"></i> إلغاء العملية
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm">
                        <i class="fas fa-save ms-2"></i> حفظ النوع
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
@endsection

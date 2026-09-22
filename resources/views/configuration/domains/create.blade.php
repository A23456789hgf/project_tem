@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة مجال جديد</h2>
        <p class="text-muted small">يرجى إدخال مسمى المجال لإتمام عملية الإضافة</p>
        <div class="section-divider"></div>
    </div>

    

    @can('domains.create')
    <form method="POST" action="{{ route('domains.store') }}">
        @csrf
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="name" class="field-label">اسم المجال</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل مسمى المجال هنا..."
                       value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <div class="switch-box h-100 d-flex align-items-center">
                    <div class="form-check form-switch d-flex align-items-center m-0">
                        <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;" 
                               name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                            حالة المجال (نشط)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('domains.index') }}" class="btn btn-cancel-custom d-inline-flex align-items-center gap-2">
                        <x-icon name="times" /> إلغاء العملية
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm d-inline-flex align-items-center gap-2">
                        <x-icon name="save" /> حفظ المجال
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
@endsection

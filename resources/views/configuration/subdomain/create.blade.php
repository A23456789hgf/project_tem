@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة مجال فرعي جديد</h2>
        <p class="text-muted small">يرجى تعبئة البيانات المطلوبة</p>
        <div class="section-divider"></div>
    </div>

    @can('subdomains.create')
    <form method="POST" action="{{ route('subdomains.store') }}">
    @endcan
        @csrf
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="domain_id" class="field-label">المجال الرئيسي</label>
                <select name="domain_id" id="domain_id" class="form-select custom-field" required>
                    <option value="">اختر مجال رئيسي</option>
                    @foreach($domains as $domain)
                    <option value="{{ $domain->id }}" {{ old('domain_id') == $domain->id ? 'selected' : '' }}>
                        {{ $domain->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="name" class="field-label">اسم المجال الفرعي</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       id="name" name="name" placeholder="أدخل اسم المجال الفرعي..." value="{{ old('name') }}" required>
                @error('name') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-12 mt-4">
                <div class="form-check form-switch pt-2">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked style="transform: scale(1.2); margin-left: 10px;">
                    <label class="form-check-label fw-bold" for="is_active" style="cursor: pointer;">تفعيل المجال الفرعي</label>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('subdomains.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-times me-2"></i> إلغاء العملية
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm">
                        <i class="fas fa-save ms-2"></i> حفظ
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
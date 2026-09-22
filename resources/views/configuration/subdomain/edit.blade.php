@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل المجال الفرعي: <span style="color: #D4AF37;">{{ $subdomain->name }}</span></h2>
        <p class="text-muted small">قم بتحديث البيانات ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    

    @can('subdomains.edit')
    <form action="{{ route('subdomains.update', $subdomain->id) }}" method="POST">
    @endcan
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label for="domain_id" class="field-label">المجال الرئيسي</label>
                <select name="domain_id" id="domain_id" class="form-select custom-field" required>
                    <option value="">-- اختر المجال الرئيسي --</option>
                    @foreach($domains as $domain)
                        <option value="{{ $domain->id }}" {{ (old('domain_id', $subdomain->domain_id) == $domain->id) ? 'selected' : '' }}>
                            {{ $domain->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="name" class="field-label">اسم المجال الفرعي</label>
                <input type="text" name="name" id="name" class="form-control custom-field" 
                       value="{{ old('name', $subdomain->name) }}" required>
            </div>

            <div class="col-md-12 mt-4">
                <div class="form-check form-switch pt-2">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ (old('is_active') ?? $subdomain->is_active) ? 'checked' : '' }} style="transform: scale(1.2); margin-left: 10px;">
                    <label class="form-check-label fw-bold" for="is_active" style="cursor: pointer;">تفعيل المجال الفرعي</label>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('subdomains.index') }}" class="btn btn-cancel-custom">
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

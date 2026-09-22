@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة تدخل جديد</h2>
        <p class="text-muted small">يرجى تعبئة الحقول الثلاثة المتساوية لإتمام عملية الإضافة</p>
        <div class="section-divider"></div>
    </div>

    @can('interventions.create')
    <form action="{{ route('interventions.store') }}" method="POST">
    @endcan
        @csrf
        
        <div class="row g-4 align-items-end">
            
            <div class="col-md-4">
                <label for="domain_id" class="field-label">المجال الرئيسي</label>
                <select class="form-select custom-field @error('domain_id') is-invalid @enderror" id="domain_id" name="domain_id" required>
                    <option value="">اختر المجال...</option>
                    @foreach($domains as $domain)
                        <option value="{{ $domain->id }}" {{ old('domain_id') == $domain->id ? 'selected' : '' }}>
                            {{ $domain->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label for="subdomain_id" class="field-label">المجال الفرعي</label>
                <select class="form-select custom-field @error('subdomain_id') is-invalid @enderror" id="subdomain_id" name="subdomain_id" required>
                    <option value="">اختر المجال الفرعي...</option>
                    @foreach($subdomains as $subdomain)
                        <option value="{{ $subdomain->id }}" {{ old('subdomain_id') == $subdomain->id ? 'selected' : '' }}>
                            {{ $subdomain->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label for="name" class="field-label">اسم التدخل</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل المسمى هنا..."
                       value="{{ old('name') }}" required>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    
                    <div class="switch-box">
                        <div class="form-check form-switch d-flex align-items-center m-0">
                            <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;" 
                                   name="is_active" id="is_active" value="1" checked>
                            <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                                حالة التدخل (نشط)
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <a href="{{ route('interventions.index') }}" class="btn btn-cancel-custom">
                            <i class="fas fa-times me-2"></i> إلغاء العملية
                        </a>
                        <button type="submit" class="btn btn-navy-gold shadow-sm">
                            <i class="fas fa-save ms-2"></i> حفظ التدخل
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

@include('configuration.interventions.partials.script')

@endsection
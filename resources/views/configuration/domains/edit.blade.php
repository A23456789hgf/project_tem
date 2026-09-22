@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل المجال: <span style="color: #D4AF37;">{{ $domain->name }}</span></h2>
        <p class="text-muted small">قم بتحديث بيانات المجال ثم اضغط على حفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    

    @can('domains.edit')
    <form action="{{ route('domains.update', $domain->id) }}" method="POST">
    @endcan
        @csrf
        @method('PUT')
        
        <div class="row g-4 align-items-end">
            <div class="col-md-4">
                <label for="name" class="field-label">اسم المجال</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل المسمى الجديد هنا..."
                       value="{{ old('name', $domain->name) }}" required>
            </div>

            <div class="col-md-4">
                <label for="program_id" class="field-label">البرنامج</label>
                <select name="program_id" id="program_id" class="form-select custom-field" required>
                    <option value="">اختر برنامجاً</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" {{ (old('program_id') ?? $domain->program_id) == $program->id ? 'selected' : '' }}>
                            {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <div class="switch-box h-100 d-flex align-items-center">
                    <div class="form-check form-switch d-flex align-items-center m-0">
                        <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;" 
                               name="is_active" id="is_active" value="1" 
                               {{ (old('is_active') ?? $domain->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                            حالة المجال (نشط)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('domains.index') }}" class="btn btn-cancel-custom d-inline-flex align-items-center gap-2">
                        <x-icon name="arrow-right" /> إلغاء والعودة
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm d-inline-flex align-items-center gap-2">
                        <x-icon name="sync-alt" /> تحديث البيانات
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

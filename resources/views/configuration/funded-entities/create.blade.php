@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة جهة ممولة جديدة</h2>
        <p class="text-muted small">يرجى اختيار مصدر التمويل وإدخال اسم الجهة</p>
        <div class="section-divider"></div>
    </div>

    

    @can('funded-entities.create')
    <form action="{{ route('funded-entities.store') }}" method="POST">
        @csrf
        
        <div class="row g-4 justify-content-center">
            <div class="col-md-6">
                <label for="funding_source_id" class="field-label">مصدر التمويل</label>
                <select class="form-select custom-field" id="funding_source_id" name="funding_source_id" required autofocus>
                    <option value="">اختر مصدر التمويل...</option>
                    @foreach($fundingSources as $source)
                        <option value="{{ $source->id }}" {{ old('funding_source_id') == $source->id ? 'selected' : '' }}>
                            {{ $source->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="name" class="field-label">اسم الجهة الممولة</label>
                <input type="text" class="form-control custom-field" id="name" name="name" 
                       placeholder="أدخل اسم الجهة..." value="{{ old('name') }}" required>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('funded-entities.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-times me-2"></i> إلغاء
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm px-5">
                        <i class="fas fa-save ms-2"></i> حفظ الجهة
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
@endsection

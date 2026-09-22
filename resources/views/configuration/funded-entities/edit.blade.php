@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">تعديل الجهة الممولة: <span style="color: #D4AF37;">{{ $fundedEntity->name }}</span></h2>
        <p class="text-muted small">قم بتحديث البيانات المطلوبة</p>
        <div class="section-divider"></div>
    </div>

    

    @can('funded-entities.edit')
    <form action="{{ route('funded-entities.update', $fundedEntity->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-4 justify-content-center">
            <div class="col-md-6">
                <label for="funding_source_id" class="field-label">مصدر التمويل</label>
                <select class="form-select custom-field" id="funding_source_id" name="funding_source_id" required>
                    @foreach($fundingSources as $source)
                        <option value="{{ $source->id }}" {{ $fundedEntity->funding_source_id == $source->id ? 'selected' : '' }}>
                            {{ $source->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="name" class="field-label">اسم الجهة الممولة</label>
                <input type="text" class="form-control custom-field" id="name" name="name" 
                       value="{{ old('name', $fundedEntity->name) }}" required>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('funded-entities.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-arrow-right me-2"></i> إلغاء والعودة
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm px-5">
                        <i class="fas fa-sync-alt ms-2"></i> تحديث البيانات
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
@endsection

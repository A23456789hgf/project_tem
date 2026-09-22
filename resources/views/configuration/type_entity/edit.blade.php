@extends('layouts.app')

@section('title', 'تعديل نوع جهة')

@section('content')
@include('configuration.shared_styles')

<div class="container">
    <div class="main-card">

        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0" style="color: #001f3f;">
                    <i class="fas fa-edit text-warning me-2"></i> تعديل نوع جهة
                </h2>
                <div class="title-line"></div>
            </div>

            <a href="{{ route('type-entity.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> رجوع
            </a>
        </div>

        @can('type-entity.edit')
        <form method="POST" action="{{ route('type-entity.update', $type->id) }}" class="mt-4">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="field-label">نوع الجهة</label>
                <input type="text"
                       name="name"
                       value="{{ $type->name }}"
                       class="form-control custom-field"
                       required>
            </div>

            <div class="mb-3">
                <label class="field-label">الحالة</label>

                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           name="is_active"
                           id="is_active"
                           {{ $type->is_active ? 'checked' : '' }}>

                    <label class="form-check-label" for="is_active">
                        مفعل
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning px-4 text-white">
                    <i class="fas fa-save"></i> تحديث
                </button>

                <a href="{{ route('type-entity.index') }}" class="btn btn-light border px-4">
                    إلغاء
                </a>
            </div>

        </form>
        @endcan

    </div>
</div>
@endsection
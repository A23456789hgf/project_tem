@extends('layouts.app')

@section('content')
    <div class="container py-5">

        {{-- Header --}}
        <div class="mb-5 border-bottom pb-4">
            <h1 class="display-6 fw-bold text-dark mb-2">تعديل سلسلة القيمة</h1>
            <p class="text-muted font-light">تعديل بيانات سلسلة القيمة الحالية في الهيكل الهرمي للنظام.</p>

            <a href="{{ route('value-chains.index') }}" class="btn btn-link p-0 text-decoration-none text-primary mt-2">
                <i class="fas fa-arrow-right ms-1"></i> العودة لقائمة سلاسل القيمة
            </a>
        </div>

        @can('value-chains.edit')
        <form action="{{ route('value-chains.update', $valueChain->id) }}" method="POST" class="row g-4">
        @endcan
            @csrf
            @method('PUT')

            {{-- الاسم --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">اسم السلسلة <span class="text-danger">*</span></label>
                <input type="text" name="name"
                    class="form-control form-control-lg border-0 bg-light rounded-3 @error('name') is-invalid @enderror"
                    value="{{ old('name', $valueChain->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- السلسلة الأم --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">السلسلة الأم (اختياري)</label>
                <select name="parent_id"
                    class="form-select form-select-lg border-0 bg-light rounded-3 @error('parent_id') is-invalid @enderror">
                    <option value="">بدون سلسلة أم (سلسلة رئيسية)</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $valueChain->parent_id) == $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- الأزرار --}}
            <div class="col-12 d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5 rounded-3 shadow-sm">
                    حفظ التعديلات
                </button>

                <a href="{{ route('value-chains.index') }}" class="btn btn-outline-secondary btn-lg px-4 rounded-3">
                    إلغاء
                </a>
            </div>

        </form>
    </div>
@endsection
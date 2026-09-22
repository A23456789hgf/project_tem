@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة شكل فرعي للتمويل</h2>
        <p class="text-muted small">يرجى تعبئة البيانات المطلوبة</p>
        <div class="section-divider"></div>
    </div>

    

    @can('subfinancing-forms.create')
    <form action="{{ route('subfinancing-forms.store') }}" method="POST">
        @csrf
        
        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label class="field-label">شكل التمويل الرئيسي</label>
                <select name="financing_form_id" class="form-select custom-field" required autofocus>
                    <option value="">اختر الشكل الرئيسي...</option>
                    @foreach($financingForms as $form)
                    <option value="{{ $form->id }}" {{ old('financing_form_id')==$form->id?'selected':'' }}>{{ $form->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="field-label">الاسم</label>
                <input type="text" name="name" class="form-control custom-field" placeholder="أدخل اسم الشكل الفرعي..." value="{{ old('name') }}" required>
            </div>

            <div class="col-md-12">
                <label class="field-label">الحالة</label>
                <select name="is_active" class="form-select custom-field" required>
                    <option value="1" {{ old('is_active')==='1'?'selected':'' }}>نشط (مفعل)</option>
                    <option value="0" {{ old('is_active')==='0'?'selected':'' }}>غير نشط (معطل)</option>
                </select>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('subfinancing-forms.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-times me-2"></i> إلغاء العملية
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm">
                        <i class="fas fa-save ms-2"></i> حفظ
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
@endsection

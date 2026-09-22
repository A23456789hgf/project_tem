@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">إضافة جمعية جديدة</h2>
        <p class="text-muted small">يرجى تعبئة البيانات المطلوبة لإضافة جمعية جديدة</p>
        <div class="section-divider"></div>
    </div>

    

    @can('associations.create')
    <form action="{{ route('associations.store') }}" method="POST">
    @endcan
        @csrf
        
        <div class="row g-4 align-items-end">
            <div class="col-md-4">
                <label for="name" class="field-label">اسم الجمعية</label>
                <input type="text" class="form-control custom-field @error('name') is-invalid @enderror" 
                       name="name" id="name" placeholder="أدخل اسم الجمعية..." value="{{ old('name') }}" required>
            </div>

            <div class="col-md-4">
                <label for="governorate_id" class="field-label">المحافظة</label>
                <select name="governorate_id" id="governorate_id" class="form-select custom-field @error('governorate_id') is-invalid @enderror" required>
                    <option value="">اختر المحافظة...</option>
                    @foreach ($governorates as $gov)
                        <option value="{{ $gov->id }}" {{ old('governorate_id') == $gov->id ? 'selected' : '' }}>
                            {{ $gov->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label for="district_id" class="field-label">المديرية</label>
                <select name="district_id" id="district_id" class="form-select custom-field @error('district_id') is-invalid @enderror" required>
                    <option value="">اختر المديرية...</option>
                </select>
            </div>

            <div class="col-12 mt-5 pt-4 border-top">
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('associations.index') }}" class="btn btn-cancel-custom">
                        <i class="fas fa-times me-2"></i> إلغاء العملية
                    </a>
                    <button type="submit" class="btn btn-navy-gold shadow-sm">
                        <i class="fas fa-save ms-2"></i> حفظ الجمعية
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#governorate_id').change(function() {
        var governorateId = $(this).val();
        if (governorateId) {
            $.ajax({
                url: '/associations/get-districts/' + governorateId,
                type: "GET",
                success: function(data) {
                    $('#district_id').empty();
                    $('#district_id').append('<option value="">اختر المديرية...</option>');
                    $.each(data, function(id, name) {
                        $('#district_id').append('<option value="' + id + '">' + name + '</option>');
                    });
                }
            });
        } else {
            $('#district_id').empty();
            $('#district_id').append('<option value="">اختر المديرية...</option>');
        }
    });
</script>
@endsection

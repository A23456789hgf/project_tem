@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container py-4">
    <x-ui.card title="إضافة قرية جديدة" subtitle="يرجى اختيار الموقع الإداري بدقة ثم إدخال اسم القرية" icon="fas fa-plus-circle">
        @can('villages.create')
        <form action="{{ route('villages.store') }}" method="POST">
        @endcan
            @csrf
            
            <div class="row g-4">
                <div class="col-md-4">
                    <x-ui.select name="governorate_id" label="المحافظة" placeholder="اختر المحافظة..." required="true" id="governorate_id">
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}" {{ old('governorate_id') == $gov->id ? 'selected' : '' }}>{{ $gov->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div class="col-md-4">
                    <x-ui.select name="directorate_id" label="المديرية" placeholder="اختر المديرية..." required="true" id="directorate_id">
                        @foreach($directorates as $dir)
                            <option value="{{ $dir->id }}" {{ old('directorate_id') == $dir->id ? 'selected' : '' }}>{{ $dir->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div class="col-md-4">
                    <x-ui.select name="sub_area_id" label="العزلة / المنطقة" placeholder="اختر العزلة/المنطقة..." required="true" id="sub_area_id">
                        @foreach($subAreas as $area)
                            <option value="{{ $area->id }}" {{ old('sub_area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="col-md-8">
                    <x-ui.input name="name" label="اسم القرية" placeholder="أدخل اسم القرية..." required="true" />
                </div>

                <div class="col-md-4">
                    <x-ui.select name="is_active" label="الحالة">
                        <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>نشط (مفعل)</option>
                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>غير نشط (معطل)</option>
                    </x-ui.select>
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <x-ui.button href="{{ route('villages.index') }}" variant="secondary" icon="fas fa-times">إلغاء</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="fas fa-save">حفظ القرية</x-ui.button>
                    </div>
                </div>
            </div>
        </form>
    </x-ui.card>
</div>

@section('scripts')
<script>
    $(document).ready(function() {
        $('#governorate_id').change(function() {
            var govId = $(this).val();
            $('#directorate_id').html('<option value="">جاري التحميل...</option>');
            $('#sub_area_id').html('<option value="">اختر المديرية أولاً</option>');
            if (govId) {
                $.get('/api/locations/directorates/' + govId, function(data) {
                    $('#directorate_id').html('<option value="">اختر المديرية...</option>');
                    $.each(data, function(k, v) {
                        $('#directorate_id').append('<option value="'+ v.id +'">'+ v.name +'</option>');
                    });
                });
            }
        });

        $('#directorate_id').change(function() {
            var dirId = $(this).val();
            $('#sub_area_id').html('<option value="">جاري التحميل...</option>');
            if (dirId) {
                var govId = $('#governorate_id').val() || '0';
                $.get('/api/locations/sub-areas/' + govId + '/' + dirId, function(data) {
                    $('#sub_area_id').html('<option value="">اختر العزلة/المنطقة...</option>');
                    $.each(data, function(k, v) {
                        $('#sub_area_id').append('<option value="'+ v.id +'">'+ v.name +'</option>');
                    });
                });
            }
        });
    });
</script>
@endsection
@endsection
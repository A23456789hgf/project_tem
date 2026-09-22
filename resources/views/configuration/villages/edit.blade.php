@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container py-4">
    <x-ui.card title="تعديل القرية" subtitle="قم بتحديث بيانات القرية وموقعها الإداري" icon="fas fa-edit">
        @can('villages.edit')
        <form action="{{ route('villages.update', $village->id) }}" method="POST">
        @endcan
            @csrf @method('PUT')
            
            <div class="row g-4">
                <div class="col-md-4">
                    <x-ui.select name="governorate_id" label="المحافظة" required="true" id="governorate_id">
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}" {{ $village->governorate_id == $gov->id ? 'selected' : '' }}>{{ $gov->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div class="col-md-4">
                    <x-ui.select name="directorate_id" label="المديرية" required="true" id="directorate_id">
                        @foreach($directorates as $dir)
                            <option value="{{ $dir->id }}" {{ $village->directorate_id == $dir->id ? 'selected' : '' }}>{{ $dir->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div class="col-md-4">
                    <x-ui.select name="sub_area_id" label="العزلة / المنطقة" required="true" id="sub_area_id">
                        @foreach($subAreas as $area)
                            <option value="{{ $area->id }}" {{ $village->sub_area_id == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="col-md-8">
                    <x-ui.input name="name" label="اسم القرية" :value="$village->name" required="true" />
                </div>

                <div class="col-md-4">
                    <x-ui.select name="is_active" label="الحالة">
                        <option value="1" {{ $village->is_active ? 'selected' : '' }}>نشط (مفعل)</option>
                        <option value="0" {{ !$village->is_active ? 'selected' : '' }}>غير نشط (معطل)</option>
                    </x-ui.select>
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <x-ui.button href="{{ route('villages.index') }}" variant="secondary" icon="fas fa-arrow-right">إلغاء والعودة</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="fas fa-sync">تحديث البيانات</x-ui.button>
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
            if (govId) {
                $.get('/api/locations/directorates/' + govId, function(data) {
                    $('#directorate_id').html('<option value="">اختر المديرية...</option>');
                    $.each(data, function(k, v) {
                        $('#directorate_id').append('<option value="'+ v.id +'">'+ v.name +'</option>');
                    });
                    if (govId == '{{ $village->governorate_id }}') {
                        $('#directorate_id').val('{{ $village->directorate_id }}').trigger('change');
                    }
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
                    if (dirId == '{{ $village->directorate_id }}') {
                        $('#sub_area_id').val('{{ $village->sub_area_id }}');
                    }
                });
            }
        });
    });
</script>
@endsection
@endsection
@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container py-4">
    <x-ui.card title="إضافة عزلة / منطقة جديدة" subtitle="يرجى اختيار المحافظة والمديرية ثم إدخال اسم العزلة" icon="fas fa-plus-circle">
        @can('sub-areas.create')
        <form action="{{ route('sub-areas.store') }}" method="POST" id="subarea-form">
            @csrf
            
            <div class="row g-4">
                <div class="col-md-6">
                    <x-ui.select name="governorate_id" label="المحافظة" placeholder="اختر المحافظة..." required="true" id="governorate_id">
                        @foreach($governorates as $governorate)
                            <option value="{{ $governorate->id }}" {{ old('governorate_id') == $governorate->id ? 'selected' : '' }}>{{ $governorate->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div class="col-md-6">
                    <x-ui.select name="directorate_id" label="المديرية" placeholder="اختر المديرية..." required="true" id="directorate_id">
                        @foreach($directorates as $directorate)
                            <option value="{{ $directorate->id }}" {{ old('directorate_id') == $directorate->id ? 'selected' : '' }}>{{ $directorate->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div class="col-md-12">
                    <x-ui.input name="name" label="اسم العزلة / المنطقة" placeholder="أدخل اسم العزلة..." required="true" />
                </div>
                
                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <x-ui.button href="{{ route('sub-areas.index') }}" variant="secondary" icon="fas fa-times">إلغاء</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="fas fa-save">حفظ البيانات</x-ui.button>
                    </div>
                </div>
            </div>
        </form>
        @endcan
    </x-ui.card>
</div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const governorateSelect = document.getElementById('governorate_id');
    const directorateSelect = document.getElementById('directorate_id');
    
    governorateSelect.addEventListener('change', function() {
        const governorateId = this.value;
        directorateSelect.innerHTML = '<option value="">جاري التحميل...</option>';
        
        if (governorateId) {
            fetch(`/api/locations/directorates/${governorateId}`)
                .then(response => response.json())
                .then(data => {
                    directorateSelect.innerHTML = '<option value="">اختر المديرية...</option>';
                    data.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        directorateSelect.appendChild(opt);
                    });
                });
        } else {
            directorateSelect.innerHTML = '<option value="">اختر المديرية...</option>';
        }
    });
});
</script>
@endsection
@endsection
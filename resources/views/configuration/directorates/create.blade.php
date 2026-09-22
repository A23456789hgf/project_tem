@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container py-4">
    <x-ui.card title="إضافة مديرية جديدة" subtitle="يرجى اختيار المحافظة وإدخال اسم المديرية" icon="fas fa-plus-circle">
        @can('directorates.create')
        <form action="{{ route('directorates.store') }}" method="POST">
            @csrf
            
            <div class="row g-4">
                <div class="col-md-6">
                    <x-ui.select name="governorate_id" label="المحافظة" placeholder="-- اختر المحافظة --" required="true">
                        @foreach ($governorates as $governorate)
                            <option value="{{ $governorate->id }}" {{ old('governorate_id') == $governorate->id ? 'selected' : '' }}>{{ $governorate->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="col-md-6">
                    <x-ui.input name="name" label="اسم المديرية" placeholder="أدخل اسم المديرية..." required="true" />
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <x-ui.button href="{{ route('directorates.index') }}" variant="secondary" icon="fas fa-times">إلغاء</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="fas fa-save">حفظ المديرية</x-ui.button>
                    </div>
                </div>
            </div>
        </form>
        @endcan
    </x-ui.card>
</div>
@endsection

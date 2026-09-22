@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container py-4">
    <x-ui.card title="تعديل المديرية" subtitle="قم بتحديث بيانات المديرية المحددة" icon="fas fa-edit">
        @can('directorates.edit')
        <form action="{{ route('directorates.update', $directorate) }}" method="POST">
            @csrf @method('PUT')
            
            <div class="row g-4">
                <div class="col-md-6">
                    <x-ui.select name="governorate_id" label="المحافظة" required="true">
                        @foreach ($governorates as $governorate)
                            <option value="{{ $governorate->id }}" {{ $governorate->id == $directorate->governorate_id ? 'selected' : '' }}>{{ $governorate->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="col-md-6">
                    <x-ui.input name="name" label="اسم المديرية" :value="$directorate->name" required="true" />
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <x-ui.button href="{{ route('directorates.index') }}" variant="secondary" icon="fas fa-times">إلغاء</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="fas fa-sync">تحديث البيانات</x-ui.button>
                    </div>
                </div>
            </div>
        </form>
        @endcan
    </x-ui.card>
</div>
@endsection

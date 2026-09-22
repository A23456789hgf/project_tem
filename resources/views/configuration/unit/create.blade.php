@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container py-4">
    <x-ui.card title="إضافة وحدة جديدة" subtitle="يرجى تعبئة البيانات المطلوبة" icon="fas fa-plus-circle">
        @can('units.create')
        <form action="{{ route('units.store') }}" method="POST">
            @csrf
            
            <div class="row g-4">
                <div class="col-md-12">
                    <x-ui.input name="unit_name" label="اسم الوحدة" placeholder="أدخل اسم الوحدة..." required="true" />
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <x-ui.button href="{{ route('units.index') }}" variant="secondary" icon="fas fa-arrow-right">رجوع إلى القائمة</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="fas fa-save">حفظ الوحدة</x-ui.button>
                    </div>
                </div>
            </div>
        </form>
        @endcan
    </x-ui.card>
</div>
@endsection
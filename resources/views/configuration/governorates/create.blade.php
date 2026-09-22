@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container py-4">
    <x-ui.card title="إضافة محافظة جديدة" subtitle="يرجى تعبئة البيانات المطلوبة لإضافة المحافظة" icon="fas fa-plus-circle">
        @can('governorates.create')
        <form action="{{ route('governorates.store') }}" method="POST">
            @csrf
            
            <div class="row g-4">
                <div class="col-md-6">
                    <x-ui.input name="name" label="اسم المحافظة" placeholder="أدخل اسم المحافظة..." required="true" />
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <x-ui.button href="{{ route('governorates.index') }}" variant="secondary" icon="fas fa-times">إلغاء العملية</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="fas fa-save">حفظ</x-ui.button>
                    </div>
                </div>
            </div>
        </form>
        @endcan
    </x-ui.card>
</div>
@endsection

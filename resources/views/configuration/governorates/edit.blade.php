@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container py-4">
    <x-ui.card title="تعديل المحافظة" subtitle="تحديث بيانات المحافظة المحددة" icon="fas fa-edit">
        @can('governorates.edit')
        <form action="{{ route('governorates.update', $governorate) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row g-4">
                <div class="col-md-6">
                    <x-ui.input name="name" label="اسم المحافظة" :value="$governorate->name" required="true" />
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <x-ui.button href="{{ route('governorates.index') }}" variant="secondary" icon="fas fa-arrow-right">إلغاء والعودة</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="fas fa-sync">تحديث البيانات</x-ui.button>
                    </div>
                </div>
            </div>
        </form>
        @endcan
    </x-ui.card>
</div>
@endsection

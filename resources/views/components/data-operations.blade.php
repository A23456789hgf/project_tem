@props([
    'hasImport' => false,
    'hasExport' => false,
    'hasTemplate' => false,
    'hasExportPdf' => false,
    'importRoute' => '',
    'exportRoute' => '',
    'templateRoute' => '',
    'createRoute' => '',
    'entityName' => 'البيانات',
    'entityNameSingular' => 'بند'
])

@php
    $module = $createRoute ? explode('.', $createRoute)[0] : null;
    $canCreate = !$createRoute || (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can($createRoute) || ($module && auth()->user()->can("{$module}.create"))));
    $canImport = !$importRoute || (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can($importRoute) || ($module && auth()->user()->can("{$module}.import"))));
    $canExport = !$exportRoute || (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can($exportRoute) || ($module && auth()->user()->can("{$module}.export"))));
@endphp

{{-- Unified Data Operations Component --}}
<div class="data-operations-wrapper d-flex align-items-center flex-wrap gap-2 animate-fade-in">

    {{-- زر الإضافة --}}
    @if($createRoute && $canCreate)
    <a href="{{ route($createRoute) }}" class="btn btn-primary d-flex align-items-center shadow-sm hover-lift">
        <i class="fas fa-plus-circle me-2"></i>إضافة {{ $entityNameSingular }}
    </a>
    @endif

    {{-- تحميل القالب --}}
    @if($hasImport && $hasTemplate && $canImport)
        <a href="{{ route($templateRoute) }}" class="btn btn-outline-secondary d-flex align-items-center shadow-sm hover-lift">
            <i class="fas fa-file-download me-2"></i>تحميل القالب
        </a>
    @endif

    {{-- رفع ملف Excel --}}
    @if($hasImport && $canImport)
        <a href="{{ route($importRoute) }}" class="btn btn-outline-warning d-flex align-items-center shadow-sm hover-lift text-dark">
            <i class="fas fa-file-upload me-2"></i>رفع Excel
        </a>
    @endif

    {{-- تصدير Excel --}}
    @if($hasExport && $canExport)
        <a href="{{ route($exportRoute) }}" class="btn btn-outline-success d-flex align-items-center export-link shadow-sm hover-lift"
           data-entity="{{ $entityName }}">
            <i class="fas fa-file-excel me-2"></i>تصدير
        </a>
    @endif

</div>

<style>
    .hover-lift { transition: transform 0.2s; }
    .hover-lift:hover { transform: translateY(-2px); }
</style>
 
@extends('layouts.app')

@section('styles')
<link href="{{ asset('css/excel-drag-drop.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-eye text-primary me-2"></i>
                    مراجعة بيانات المناطق الفرعية
                </h2>
                <a href="{{ route('sub-areas.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    العودة
                </a>
            </div>

            <!-- Import Configuration Card -->
            <div class="card import-preview-card mb-4">
                <div class="card-header import-preview-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        إعدادات الاستيراد
                    </h5>
                </div>
                <div class="card-body">
                    @can('sub-areas.import')
                    <form action="{{ route('sub-areas.process-import') }}" method="POST" id="importForm" class="import-form">
                    @endcan
                        @csrf
                        <input type="hidden" name="file_path" value="{{ $filePath }}">
                        
                        <!-- Operation Selection -->
                        <div class="operation-selection">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-tasks me-2"></i>
                                نوع العملية:
                            </h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="operation" value="insert" id="insert" checked>
                                        <label class="form-check-label" for="insert">
                                            <i class="fas fa-plus-circle text-success me-1"></i>
                                            إضافة فقط
                                            <small class="d-block text-muted">إضافة سجلات جديدة فقط</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="operation" value="update" id="update">
                                        <label class="form-check-label" for="update">
                                            <i class="fas fa-edit text-warning me-1"></i>
                                            تحديث فقط
                                            <small class="d-block text-muted">تحديث السجلات الموجودة</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="operation" value="both" id="both">
                                        <label class="form-check-label" for="both">
                                            <i class="fas fa-sync-alt text-info me-1"></i>
                                            إضافة أو تحديث
                                            <small class="d-block text-muted">إضافة الجديد وتحديث الموجود</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Field Mapping -->
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-link me-2"></i>
                            ربط الحقول:
                        </h6>
                        <div class="table-responsive mb-4">
                            <table class="table mapping-table">
                                <thead>
                                    <tr>
                                        <th width="40%">
                                            <i class="fas fa-database me-1"></i>
                                            حقل النظام
                                        </th>
                                        <th width="40%">
                                            <i class="fas fa-file-excel me-1"></i>
                                            عمود Excel
                                        </th>
                                        <th width="20%">
                                            <i class="fas fa-info-circle me-1"></i>
                                            حالة الحقل
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mappingFields as $field)
                                    <tr>
                                        <td>
                                            <strong>
                                                @switch($field)
                                                    @case('name')
                                                        اسم المنطقة الفرعية
                                                        @break
                                                    @case('governorate_name')
                                                        اسم المحافظة
                                                        @break
                                                    @case('directorate_name')
                                                        اسم المديرية
                                                        @break
                                                    @case('is_active')
                                                        الحالة
                                                        @break
                                                    @default
                                                        {{ $field }}
                                                @endswitch
                                            </strong>
                                            @if(in_array($field, ['name', 'governorate_name', 'directorate_name']))
                                                <span class="badge bg-danger ms-2">مطلوب</span>
                                            @endif
                                        </td>
                                        <td>
                                            <select name="mapping[{{ $field }}]" class="form-select" {{ in_array($field, ['name', 'governorate_name', 'directorate_name']) ? 'required' : '' }}>
                                                <option value="">-- اختر العمود --</option>
                                                @foreach($headers as $header)
                                                    <option value="{{ $header }}" 
                                                        @if(
                                                            ($field === 'name' && (str_contains(strtolower($header), 'name') || str_contains(strtolower($header), 'اسم') || str_contains(strtolower($header), 'منطقة'))) ||
                                                            ($field === 'governorate_name' && (str_contains(strtolower($header), 'governorate') || str_contains(strtolower($header), 'محافظة'))) ||
                                                            ($field === 'directorate_name' && (str_contains(strtolower($header), 'directorate') || str_contains(strtolower($header), 'مديرية'))) ||
                                                            ($field === 'is_active' && (str_contains(strtolower($header), 'active') || str_contains(strtolower($header), 'حالة') || str_contains(strtolower($header), 'نشط')))
                                                        ) selected @endif>
                                                        {{ $header }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            @if(in_array($field, ['name', 'governorate_name', 'directorate_name']))
                                                <span class="badge bg-danger">مطلوب</span>
                                            @else
                                                <span class="badge bg-secondary">اختياري</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-play me-2"></i>
                                بدء الاستيراد
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Preview Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        معاينة البيانات (أول {{ count($rows) }} صفوف)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="preview-table-container">
                        <table class="table preview-table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th width="50px">#</th>
                                    @foreach($headers as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    @foreach($headers as $header)
                                        <td title="{{ $row[$header] ?? '' }}">
                                            {{ $row[$header] ?? '' }}
                                        </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    document.getElementById('importForm').addEventListener('submit', function(e) {
        const nameMapping = document.querySelector('select[name="mapping[name]"]').value;
        const governorateMapping = document.querySelector('select[name="mapping[governorate_name]"]').value;
        const directorateMapping = document.querySelector('select[name="mapping[directorate_name]"]').value;
        
        if (!nameMapping) {
            e.preventDefault();
            alert('يجب ربط حقل "اسم المنطقة الفرعية" بعمود من ملف Excel');
            return false;
        }
        
        if (!governorateMapping) {
            e.preventDefault();
            alert('يجب ربط حقل "اسم المحافظة" بعمود من ملف Excel');
            return false;
        }
        
        if (!directorateMapping) {
            e.preventDefault();
            alert('يجب ربط حقل "اسم المديرية" بعمود من ملف Excel');
            return false;
        }
    });
});
</script>
@endsection
@extends('layouts.app')

@section('title', 'معاينة استيراد الجهات الداخلية')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-eye"></i> معاينة البيانات المراد استيرادها
            </h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">بيانات الملف</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        @foreach($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">إعدادات الاستيراد</h5>
        </div>
        <div class="card-body">
            @can('internal-entities.view')
            <form id="processImportForm" action="{{ route('internal-entities.process-import') }}" method="POST">
            @endcan
                @csrf
                <input type="hidden" name="file_path" value="{{ $filePath }}">
                <input type="hidden" name="authority_id" value="{{ $authorityId }}">

                <div class="mb-4">
                    <label class="form-label">
                        <span class="text-danger">*</span> نوع العملية
                    </label>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="radio" id="operation_insert" name="operation" value="insert" class="form-check-input" checked>
                                <label class="form-check-label" for="operation_insert">
                                    <strong>إدراج فقط</strong>
                                    <br>
                                    <small class="text-muted">إضافة السجلات الجديدة فقط وتجاهل الموجودة</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="radio" id="operation_update" name="operation" value="update" class="form-check-input">
                                <label class="form-check-label" for="operation_update">
                                    <strong>تحديث فقط</strong>
                                    <br>
                                    <small class="text-muted">تحديث السجلات الموجودة فقط وتجاهل الجديدة</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="radio" id="operation_both" name="operation" value="both" class="form-check-input">
                                <label class="form-check-label" for="operation_both">
                                    <strong>إدراج وتحديث</strong>
                                    <br>
                                    <small class="text-muted">إضافة الجديد وتحديث الموجود</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        <span class="text-danger">*</span> تعيين الأعمدة
                    </label>
                    <small class="text-muted d-block mb-3">
                        اختر موقع كل حقل من البيانات في ملف الاستيراد:
                    </small>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="mapping_name" class="form-label">
                                <span class="text-danger">*</span> اسم الجهة
                            </label>
                            <select name="mapping[name]" id="mapping_name" class="form-select" required>
                                <option value="">-- اختر --</option>
                                @foreach($headers as $header)
                                    <option value="{{ $header }}" {{ $header === 'name' || $header === 'اسم الجهة' ? 'selected' : '' }}>
                                        {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="mapping_parent_name" class="form-label">الجهة الأم</label>
                            <select name="mapping[parent_name]" id="mapping_parent_name" class="form-select">
                                <option value="">-- لا يوجد --</option>
                                @foreach($headers as $header)
                                    <option value="{{ $header }}" {{ $header === 'parent_name' || $header === 'الجهة الأم' ? 'selected' : '' }}>
                                        {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="mapping_authority_name" class="form-label">الجهة المشرفة</label>
                            <select name="mapping[authority_name]" id="mapping_authority_name" class="form-select">
                                <option value="">-- لا يوجد --</option>
                                @foreach($headers as $header)
                                    <option value="{{ $header }}" {{ $header === 'authority_name' || $header === 'الجهة المشرفة' || str_contains($header, 'مشرفة') ? 'selected' : '' }}>
                                        {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="mapping_is_active" class="form-label">الحالة</label>
                            <select name="mapping[is_active]" id="mapping_is_active" class="form-select">
                                <option value="">-- لا يوجد --</option>
                                @foreach($headers as $header)
                                    <option value="{{ $header }}" {{ $header === 'is_active' || $header === 'الحالة' ? 'selected' : '' }}>
                                        {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> استيراد البيانات
                    </button>
                    <a href="{{ route('internal-entities.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

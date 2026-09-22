@extends('layouts.app')

@section('title', 'استيراد المجالات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">استيراد المجالات من ملف</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info d-flex align-items-center gap-2">
                                <x-icon name="info-circle" />
                                <div class="w-100">
                                    <h6 class="mb-2">تعليمات الاستيراد</h6>
                                    <ul class="mb-0">
                                        <li>يمكن استيراد البيانات من ملفات Excel (.xlsx, .xls) أو CSV</li>
                                        <li>يجب أن يحتوي الملف على العمود التالي على الأقل: name</li>
                                        <li>يمكنك <a href="{{ route('domains.download-template') }}" class="alert-link">تحميل نموذج</a> للاستيراد</li>
                                        <li>سيتم تجاهل الصفوف الفارغة أو التي تحتوي على أخطاء في البيانات</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    @can('domains.preview-import')
                    <form action="{{ route('domains.preview-import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="file">اختر ملف البيانات</label>
                                    <input type="file" name="file" id="file" class="form-control-file" accept=".csv,.xlsx,.xls" required>
                                    <small class="form-text text-muted">الامتدادات المسموحة: CSV, XLSX, XLS</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>تحميل نموذج</label>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('domains.download-template', ['format' => 'csv']) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2">
                                            <x-icon name="download" /> نموذج CSV
                                        </a>
                                        <a href="{{ route('domains.download-template', ['format' => 'xlsx']) }}" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2">
                                            <x-icon name="download" /> نموذج Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-start d-flex gap-2 justify-content-start">
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                    <x-icon name="eye" /> معاينة قبل الاستيراد
                                </button>
                                <a href="{{ route('domains.index') }}" class="btn btn-secondary d-inline-flex align-items-center gap-2">
                                    <x-icon name="arrow-left" /> رجوع
                                </a>
                            </div>
                        </div>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

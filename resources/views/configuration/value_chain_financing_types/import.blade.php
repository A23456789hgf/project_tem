@extends('layouts.app')

@section('title', 'استيراد أنواع تمويل سلاسل القيمة')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">استيراد أنواع التمويل من ملف</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> تعليمات الاستيراد</h6>
                                <ul class="mb-0">
                                    <li>يمكن استيراد البيانات من ملفات Excel (.xlsx, .xls) أو CSV</li>
                                    <li>يجب أن يحتوي الملف على العمود التالي على الأقل: name</li>
                                    <li>يمكنك <a href="{{ route('value-chain-financing-types.import.template') }}" class="alert-link">تحميل نموذج</a> للاستيراد</li>
                                    <li>سيتم تجاهل الصفوف الفارغة أو التي تحتوي على أخطاء في البيانات</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    @can('value-chain-financing-types.import.preview')
                    <form action="{{ route('value-chain-financing-types.import.preview') }}" method="POST" enctype="multipart/form-data">
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
                                    <div>
                                        <a href="{{ route('value-chain-financing-types.import.template', ['format' => 'csv']) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-download"></i> نموذج CSV
                                        </a>
                                        <a href="{{ route('value-chain-financing-types.import.template', ['format' => 'xlsx']) }}" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-download"></i> نموذج Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-start">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> معاينة قبل الاستيراد
                                </button>
                                <a href="{{ route('value-chain-financing-types.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> رجوع
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

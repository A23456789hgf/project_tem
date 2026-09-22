@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>استيراد الجهات الممولة</h1>
        </div>
        <div class="col-md-6 text-start">
            <a href="{{ route('fundedentities.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> رجوع
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>رفع ملف الاستيراد</h5>
                </div>
                <div class="card-body">
                    @can('fundedentities.preview.import')
                    <form action="{{ route('fundedentities.preview.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="form-group">
                            <label for="import_file">ملف Excel <span class="text-danger">*</span></label>
                            <input type="file" name="import_file" id="import_file" class="form-control-file @error('import_file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                            @error('import_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                الامتدادات المسموحة: xlsx, xls, csv. الحد الأقصى للحجم: 2MB
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-eye"></i> معاينة البيانات
                            </button>
                            <a href="{{ route('fundedentities.download.template') }}" class="btn btn-success">
                                <i class="fas fa-download"></i> تحميل القالب
                            </a>
                        </div>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>تعليمات الاستيراد</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>قم بتحميل قالب الاستيراد أولاً</li>
                        <li>املأ البيانات في القالب مع الحفاظ على التنسيق</li>
                        <li>تأكد من أن أسماء مصادر التمويل مطابقة تماماً للنظام</li>
                        <li>احفظ الملف بصيغة Excel أو CSV</li>
                        <li>قم برفع الملف باستخدام النموذج المجاور</li>
                        <li>ستظهر لك معاينة للبيانات قبل التأكيد النهائي</li>
                    </ol>
                    
                    <div class="alert alert-warning">
                        <strong>ملاحظة:</strong> سيتم تخطي السجلات المكررة تلقائياً.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
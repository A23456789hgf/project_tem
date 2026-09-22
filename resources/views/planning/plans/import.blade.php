@extends('layouts.app')

@section('styles')
<link href="{{ asset('css/excel-drag-drop.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="config-page">
    <div class="config-header mb-4">
        <div class="config-header-content d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-file-import"></i> استيراد الخطط من Excel</h1>
            <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    {{-- Alerts --}}

    @if(count($errors) > 0)
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Upload Card --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="fas fa-file-excel text-success me-2"></i>رفع ملف Excel</h5>
                </div>
                <div class="card-body">
                    @can('plans.import')
                    <form action="{{ route('plans.process-import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @endcan
                        @csrf

                        {{-- File drop zone --}}
                        <div class="excel-drop-zone mb-4" id="dropZone">
                            <div class="drop-zone-content text-center py-5">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-1 fw-bold">اسحب ملف Excel هنا أو انقر للاختيار</p>
                                <p class="small text-muted mb-3">الصيغ المدعومة: .xlsx, .xls, .csv — الحد الأقصى: 10 ميجابايت</p>
                                <input type="file" name="file" id="planFile" accept=".xlsx,.xls,.csv"
                                       class="d-none" required>
                                <label for="planFile" class="btn btn-outline-primary px-4">
                                    <i class="fas fa-folder-open me-1"></i> اختيار الملف
                                </label>
                                <div id="fileName" class="mt-3 text-success fw-bold d-none"></div>
                            </div>
                        </div>

                        {{-- Import Mode --}}
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="mb-3 fw-bold">خيارات الاستيراد</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="import_mode"
                                                   id="mode_add" value="add" checked>
                                            <label class="form-check-label" for="mode_add">
                                                <strong>إضافة فقط:</strong> تجاهل الخطط الموجودة مسبقاً
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="import_mode"
                                                   id="mode_update" value="update">
                                            <label class="form-check-label" for="mode_update">
                                                <strong>إضافة وتحديث:</strong> تحديث الخطط الموجودة وإضافة الجديدة
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            @can('plans.create')
                            <button type="submit" class="btn btn-success px-5" id="submitBtn" disabled>
                                <i class="fas fa-upload me-1"></i> استيراد البيانات
                            </button>
                            @endcan
                            <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Instructions Sidebar --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0"><i class="fas fa-download text-primary me-2"></i>تحميل القالب</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">حمّل القالب أولاً لمعرفة التنسيق المطلوب، أو استخدم ملف مُصدَّر من صفحة الخطط مباشرةً.</p>
                    <a href="{{ route('plans.download-template') }}" class="btn btn-outline-success w-100">
                        <i class="fas fa-file-excel me-1"></i> تحميل نموذج الخطة المتعدد الأوراق
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0"><i class="fas fa-info-circle text-info me-2"></i>تعليمات الاستيراد</h6>
                </div>
                <div class="card-body">
                    <ol class="small text-muted ps-3 mb-0">
                        <li class="mb-2">حمّل القالب وافتحه في Excel</li>
                        <li class="mb-2">أدخل بيانات الخطط مع الالتزام بأسماء الأعمدة</li>
                        <li class="mb-2">كل سطر يمثّل <strong>إجراءً واحداً</strong> ضمن نشاط ضمن مشروع ضمن خطة</li>
                        <li class="mb-2">إذا كان المشروع لا يحتوي أنشطة، اترك أعمدة النشاط والإجراء فارغة</li>
                        <li class="mb-2">تأكد من وجود الأولوية والجهات في النظام قبل الاستيراد</li>
                        <li>مجموع أوزان الأنشطة لكل مشروع يجب أن يساوي 100%</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('planFile');
    const fileNameDiv = document.getElementById('fileName');
    const submitBtn = document.getElementById('submitBtn');
    const dropZone = document.getElementById('dropZone');

    function handleFile(file) {
        if (!file) return;
        fileNameDiv.textContent = '✓ ' + file.name;
        fileNameDiv.classList.remove('d-none');
        submitBtn.disabled = false;
    }

    fileInput.addEventListener('change', function () {
        handleFile(this.files[0]);
    });

    // Drag & drop support
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('border-primary');
    });
    dropZone.addEventListener('dragleave', function () {
        this.classList.remove('border-primary');
    });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('border-primary');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            handleFile(file);
        }
    });
});
</script>
@endsection

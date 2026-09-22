@extends('layouts.app')

@section('styles')
<style>
    /* =========================================
       إعدادات الصفحة العامة
       ========================================= */
    html, body {
        background-color: #f0f4f8;
        font-family: 'Cairo', system-ui, -apple-system, sans-serif;
    }

    /* =========================================
       رأس الصفحة
       ========================================= */
    .page-header {
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #3b82f6 100%);
        padding: 1.5rem 2rem;
        border-radius: 0 0 20px 20px;
        margin-bottom: 1.75rem;
        box-shadow: 0 6px 30px rgba(37, 99, 235, 0.25);
    }
    .page-header h2 {
        color: #fff;
        font-size: 1.4rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -0.3px;
    }
    .page-header p {
        color: rgba(255,255,255,0.75);
        margin: 0.3rem 0 0;
        font-size: 0.85rem;
    }
    .page-header .btn-back {
        background: rgba(255,255,255,0.15);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        padding: 0.45rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        backdrop-filter: blur(4px);
        transition: background 0.2s;
    }
    .page-header .btn-back:hover {
        background: rgba(255,255,255,0.25);
        color: #fff;
    }

    /* =========================================
       البطاقات
       ========================================= */
    .import-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(15,23,42,0.06);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .import-card .card-head {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.85rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .import-card .card-head h5 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
    }
    .import-card .card-head i {
        color: #2563eb;
        font-size: 1rem;
    }
    .import-card .card-body-pad {
        padding: 1.25rem 1.5rem;
    }

    /* =========================================
       حقول النموذج
       ========================================= */
    .form-label {
        font-size: 0.82rem;
        font-weight: 700;
        color: #374151;
        margin-bottom: 0.3rem;
        display: block;
    }
    .form-label .req {
        color: #ef4444;
        margin-right: 2px;
    }
    .form-label .badge-ro {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
        font-size: 0.65rem;
        padding: 1px 6px;
        border-radius: 20px;
        font-weight: 600;
        vertical-align: middle;
    }
    .form-control, .form-select {
        font-size: 0.85rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.45rem 0.75rem;
        background-color: #f8fafc;
        color: #1e293b;
        transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
    }
    .form-control:focus, .form-select:focus {
        background-color: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        outline: none;
    }
    .form-control[readonly], .form-control.readonly-field {
        background-color: #fef9ec;
        border: 1px dashed #f59e0b;
        color: #78350f;
        cursor: not-allowed;
    }
    .form-control[readonly]:focus, .form-control.readonly-field:focus {
        box-shadow: none;
        border-color: #f59e0b;
    }
    textarea.form-control {
        resize: vertical;
        min-height: 70px;
    }

    /* =========================================
       قسم الحقول – الشبكة
       ========================================= */
    .fields-section {
        margin-bottom: 1.5rem;
    }
    .fields-section-title {
        font-size: 0.78rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 0.75rem;
        padding-bottom: 0.4rem;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .fields-section-title i { color: #3b82f6; font-size: 0.8rem; }

    /* =========================================
       أداة رفع الملف
       ========================================= */
    .file-upload-zone {
        border: 2px dashed #93c5fd;
        border-radius: 12px;
        background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease;
        position: relative;
    }
    .file-upload-zone:hover,
    .file-upload-zone.drag-over {
        border-color: #2563eb;
        background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
    }
    .file-upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .file-upload-zone .upload-icon {
        font-size: 2rem;
        color: #3b82f6;
        margin-bottom: 0.5rem;
    }
    .file-upload-zone .upload-text {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1d4ed8;
        margin-bottom: 0.25rem;
    }
    .file-upload-zone .upload-hint {
        font-size: 0.75rem;
        color: #64748b;
    }
    .file-name-display {
        margin-top: 0.6rem;
        font-size: 0.82rem;
        color: #16a34a;
        font-weight: 600;
        display: none;
    }

    /* =========================================
       معاينة القائمة / الجدول
       ========================================= */
    .import-table-wrapper {
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(15,23,42,0.05);
    }
    .import-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.82rem;
        min-width: 1800px;
    }
    .import-table thead th {
        background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%);
        color: #fff;
        font-weight: 700;
        padding: 0.7rem 0.75rem;
        white-space: nowrap;
        border-bottom: none;
        font-size: 0.78rem;
        letter-spacing: 0.02em;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .import-table thead th:first-child { border-radius: 10px 0 0 0; }
    .import-table thead th:last-child  { border-radius: 0 10px 0 0; }

    .import-table tbody tr { transition: background-color 0.15s; }
    .import-table tbody tr:nth-child(even) td { background: #f8fafc; }
    .import-table tbody tr:hover td { background: #eff6ff; }

    .import-table tbody td {
        padding: 0.5rem 0.6rem;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        background: #fff;
    }

    .import-table .form-control,
    .import-table .form-select {
        min-width: 100px;
        height: 32px;
        padding: 0.25rem 0.5rem;
        font-size: 0.78rem;
        border-radius: 6px;
        background-color: #fff;
    }
    .import-table .form-control[readonly] {
        background-color: #fef9ec;
        border: 1px dashed #f59e0b;
        color: #78350f;
        cursor: not-allowed;
    }
    .import-table .form-control[readonly]:focus {
        box-shadow: none;
        border-color: #f59e0b;
    }
    .import-table .col-name   { min-width: 180px; }
    .import-table .col-field  { min-width: 140px; }
    .import-table .col-gov    { min-width: 130px; }
    .import-table .col-dir    { min-width: 130px; }
    .import-table .col-num    { min-width: 110px; }
    .import-table .col-text   { min-width: 160px; }
    .import-table .col-pct    { min-width: 110px; }
    .import-table .col-notes  { min-width: 200px; }
    .import-table .col-year   { min-width: 110px; }
    .import-table .col-prog   { min-width: 120px; }
    .import-table .col-status { min-width: 130px; }
    .import-table .col-file   { min-width: 170px; }
    .import-table .col-action { min-width: 60px;  text-align: center; }

    /* أزرار الصفوف */
    .btn-row-action {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s;
        font-size: 0.75rem;
    }
    .btn-row-action:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
    .btn-row-delete { background: linear-gradient(135deg, #fda4af, #f87171); color: #fff; }
    .btn-row-add    { background: linear-gradient(135deg, #6ee7b7, #34d399); color: #fff; }

    /* =========================================
       شريط الإجراءات السفلي
       ========================================= */
    .action-bar {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.04);
        position: sticky;
        bottom: 0;
        z-index: 20;
        margin-top: 1rem;
    }
    .btn-import {
        background: linear-gradient(135deg, #16a34a, #22c55e);
        color: #fff;
        border: none;
        padding: 0.6rem 2rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        box-shadow: 0 4px 14px rgba(34,197,94,0.3);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-import:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(34,197,94,0.4);
        color: #fff;
    }
    .btn-add-row {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        padding: 0.55rem 1.25rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        box-shadow: 0 3px 10px rgba(37,99,235,0.25);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-add-row:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.35);
        color: #fff;
    }

    /* =========================================
       إشعارات / رسائل
       ========================================= */
    .alert-info-soft {
        background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd;
        border-radius: 10px;
        padding: 0.85rem 1.1rem;
        font-size: 0.82rem;
        color: #1e40af;
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
    }
    .alert-info-soft i { color: #3b82f6; font-size: 1rem; margin-top: 1px; }

    /* =========================================
       الإحصائيات الفورية
       ========================================= */
    .stats-chip {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.4rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: #374151;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .stats-chip i { color: #3b82f6; }

    /* =========================================
       تجاوب
       ========================================= */
    @media (max-width: 768px) {
        .page-header { border-radius: 0 0 12px 12px; padding: 1rem 1rem 1rem 1rem; }
        .card-body-pad { padding: 1rem; }
        .action-bar { flex-direction: column; align-items: stretch; text-align: center; }
    }
</style>
@endsection

@section('content')
<div dir="rtl">

    {{-- ───────── رأس الصفحة ───────── --}}
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-file-import me-2"></i>قائمة استيراد المشاريع</h2>
                <p>أدخل بيانات المشاريع مباشرةً في الجدول أو أضف صفوفاً جديدة، ثم اضغط "استيراد"</p>
            </div>
            <a href="{{ route('projects.index') }}" class="btn btn-back">
                <i class="fas fa-arrow-right me-1"></i>رجوع
            </a>
        </div>
    </div>

    <div class="container-fluid px-3 px-md-4">

        @if(session('import_report'))
            <div class="import-card mb-4" id="resultsSection">
                <div class="card-head bg-white">
                    <i class="fas fa-check-circle text-success"></i>
                    <h5 class="text-success">نتائج الاستيراد</h5>
                </div>
                <div class="card-body-pad">
                    @php $report = session('import_report'); @endphp
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-info">
                                <div class="small text-muted">إجمالي الصفوف</div>
                                <div class="h4 fw-bold mb-0">{{ $report['total_rows'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-success">
                                <div class="small text-muted">تم الإضافة</div>
                                <div class="h4 fw-bold mb-0 text-success">{{ $report['successful_inserts'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-warning">
                                <div class="small text-muted">تم التحديث</div>
                                <div class="h4 fw-bold mb-0 text-warning">{{ $report['successful_updates'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-danger">
                                <div class="small text-muted">فشل</div>
                                <div class="h4 fw-bold mb-0 text-danger">{{ $report['failed_rows'] }}</div>
                            </div>
                        </div>
                    </div>

                    @if(session('import_failures'))
                        <div class="mt-4">
                            <h6 class="fw-bold text-danger mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>الأخطاء
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-danger">
                                        <tr>
                                            <th width="200">اسم المشروع (الصف)</th>
                                            <th>الخطأ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('import_failures') as $failure)
                                            <tr>
                                                <td class="fw-bold">{{ $failure['row'] ?? '—' }}</td>
                                                <td>
                                                    @if(is_array($failure['errors'] ?? null))
                                                        {{ implode(' | ', $failure['errors']) }}
                                                    @else
                                                        {{ $failure['reason'] ?? '—' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            @can('projects.create')
                            <form method="POST" action="{{ route('projects.failure-report') }}" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-download me-2"></i>تحميل تقرير المشاريع غير المستوردة
                                </button>
                            </form>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ───────── بطاقة التعليمات ───────── --}}
        <div class="import-card">
            <div class="card-head">
                <i class="fas fa-info-circle"></i>
                <h5>تعليمات الاستيراد</h5>
            </div>
            <div class="card-body-pad">
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="alert-info-soft">
                            <i class="fas fa-lightbulb"></i>
                            <div>
                                <strong>حقول للقراءة فقط:</strong> يتم احتساب <em>نسبة الإنجاز الإجمالية</em> و<em>إجمالي المساهمة</em> تلقائياً من البيانات المدخلة — لا تعدّل هذه الحقول يدوياً.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert-info-soft">
                            <i class="fas fa-upload"></i>
                            <div>
                                <strong>رفع المستندات:</strong> يمكنك إرفاق ملف واحد لكل مشروع (PDF, Word, Excel, صور). الحد الأقصى للحجم <strong>10 ميجابايت</strong> لكل ملف.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ───────── بطاقة الجدول الرئيسي ───────── --}}
        <div class="import-card">
            <div class="card-head">
                <i class="fas fa-table"></i>
                <h5>بيانات المشاريع</h5>
                <span class="ms-auto stats-chip" id="rowCountChip">
                    <i class="fas fa-list-ol"></i>
                    عدد الصفوف: <span id="rowCount">1</span>
                </span>
            </div>
            <div class="card-body-pad">

                <form id="importListForm" method="POST"
                      action="{{ route('projects.process-import') }}"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="import-table-wrapper">
                        <table class="import-table" id="importTable">
                            <thead>
                                <tr>
                                    <th class="col-action">#</th>
                                    {{-- المعلومات الأساسية --}}
                                    <th class="col-name">
                                        <i class="fas fa-tag me-1"></i>اسم المشروع <span style="color:#fca5a5">*</span>
                                    </th>
                                    <th class="col-text">
                                        <i class="fas fa-tags me-1"></i>نوع المشروع
                                    </th>
                                    <th class="col-text">
                                        <i class="fas fa-building me-1"></i>اسم الجهة المنشئة
                                    </th>
                                    <th class="col-field">
                                        <i class="fas fa-layer-group me-1"></i>المجال <span style="color:#fca5a5">*</span>
                                    </th>
                                    <th class="col-gov">
                                        <i class="fas fa-map-marker-alt me-1"></i>المحافظة <span style="color:#fca5a5">*</span>
                                    </th>
                                    <th class="col-dir">
                                        <i class="fas fa-map-pin me-1"></i>المديرية
                                    </th>
                                    {{-- المالية --}}
                                    <th class="col-num">
                                        <i class="fas fa-dollar-sign me-1"></i>التكلفة الإجمالية
                                    </th>
                                    <th class="col-num">
                                        <i class="fas fa-money-bill-wave me-1"></i>المبلغ المصروف
                                    </th>
                                    <th class="col-num">
                                        <i class="fas fa-balance-scale me-1"></i>المبلغ المتبقي
                                    </th>
                                    <th class="col-year">
                                        <i class="fas fa-calendar-alt me-1"></i>السنة الهجرية
                                    </th>
                                    <th class="col-text">
                                        <i class="fas fa-users me-1"></i>المستفيد
                                    </th>
                                    {{-- المؤشرات --}}
                                    <th class="col-text">
                                        <i class="fas fa-chart-line me-1"></i>المؤشر
                                    </th>
                                    <th class="col-num">
                                        <i class="fas fa-bullseye me-1"></i>القيمة المستهدفة
                                    </th>
                                    <th class="col-num">
                                        <i class="fas fa-check-circle me-1"></i>المنجز
                                    </th>
                                    <th class="col-pct">
                                        <i class="fas fa-percentage me-1"></i>نسبة الإنجاز
                                    </th>
                                    {{-- المساهمات --}}
                                    <th class="col-num">
                                        <i class="fas fa-coins me-1"></i>مساهمة الوحدة
                                    </th>
                                    <th class="col-num">
                                        <i class="fas fa-money-bill-wave me-1"></i>إنفاق الوحدة
                                    </th>
                                    <th class="col-num">
                                        <i class="fas fa-hand-holding-heart me-1"></i>مساهمة المجتمع
                                    </th>
                                    <th class="col-num">
                                        <i class="fas fa-building me-1"></i>تكلفة جهة أخرى
                                    </th>
                                    <th class="col-text">
                                        <i class="fas fa-building me-1"></i>اسم الجهة الأخرى
                                    </th>
                                    <th class="col-pct" title="إجمالي المساهمة – محسوب تلقائياً">
                                        <i class="fas fa-calculator me-1"></i>إجمالي المساهمة
                                        <span style="font-size:0.65rem; opacity:0.75;">(قراءة)</span>
                                    </th>
                                    {{-- السنوات والجهات --}}
                                    <th class="col-year">
                                        <i class="fas fa-calendar-check me-1"></i>سنة الاعتماد
                                    </th>
                                    <th class="col-year">
                                        <i class="fas fa-calendar-alt me-1"></i>سنة التنفيذ
                                    </th>
                                    <th class="col-text">
                                        <i class="fas fa-hand-holding-usd me-1"></i>جهة التمويل
                                    </th>
                                    <th class="col-text">
                                        <i class="fas fa-tools me-1"></i>جهة التنفيذ
                                    </th>
                                    <th class="col-text">
                                        <i class="fas fa-user-tie me-1"></i>جهة الإشراف
                                    </th>
                                    <th class="col-text" title="للمشاريع القديمة فقط">
                                        <i class="fas fa-hand-holding-heart me-1"></i>الجهة المستفيدة
                                        <span style="font-size:0.65rem; opacity:0.75;">(قديم)</span>
                                    </th>
                                    {{-- التقدم --}}
                                    <th class="col-prog">
                                        <i class="fas fa-coins me-1"></i>التقدم المالي %
                                    </th>
                                    <th class="col-prog">
                                        <i class="fas fa-hard-hat me-1"></i>التقدم الفني %
                                    </th>
                                    <th class="col-pct" title="التقدم الإجمالي – محسوب تلقائياً">
                                        <i class="fas fa-chart-pie me-1"></i>التقدم الإجمالي
                                        <span style="font-size:0.65rem; opacity:0.75;">(قراءة)</span>
                                    </th>
                                    {{-- الحالة والملاحظات --}}
                                    <th class="col-status">
                                        <i class="fas fa-flag me-1"></i>الحالة <span style="color:#fca5a5">*</span>
                                    </th>
                                    <th class="col-notes">
                                        <i class="fas fa-sticky-note me-1"></i>ملاحظات
                                    </th>
                                    {{-- الملفات --}}
                                    <th class="col-file">
                                        <i class="fas fa-paperclip me-1"></i>رفع مستند
                                    </th>
                                    <th class="col-action">حذف</th>
                                </tr>
                            </thead>
                            <tbody id="importTableBody">
                                {{-- يتم إدراج الصفوف بواسطة JavaScript --}}
                            </tbody>
                        </table>
                    </div>

                </form>

            </div>
        </div>

        {{-- ───────── شريط الإجراءات ───────── --}}
        <div class="action-bar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn-add-row" id="addRowBtn" onclick="addRow()">
                    <i class="fas fa-plus me-1"></i>إضافة صف جديد
                </button>
                <span class="stats-chip">
                    <i class="fas fa-list-ol"></i>
                    إجمالي الصفوف: <span id="rowCountFooter">1</span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fas fa-times me-1"></i>إلغاء
                </a>
                <button type="button" class="btn-import" id="submitImportBtn" onclick="submitImport()">
                    <i class="fas fa-file-import me-2"></i>استيراد المشاريع
                </button>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
// ===================================================
//  البيانات الأساسية من الخادم
// ===================================================
const GOVERNORATES = @json(\App\Models\Governorate::orderBy('name')->get(['id','name']));

const STATUSES = [
    { value: 'draft',             label: 'مسودة' },
    { value: 'pending',           label: 'قيد المراجعة' },
    { value: 'under_review',      label: 'تحت الدراسة' },
    { value: 'approved',          label: 'معتمد' },
    { value: 'in_progress',       label: 'قيد التنفيذ' },
    { value: 'completed',         label: 'مكتمل' },
    { value: 'suspended',         label: 'موقوف' },
    { value: 'cancelled',         label: 'ملغي' },
];

let rowCounter = 0;

// ===================================================
//  بناء عنصر <select> للمحافظات
// ===================================================
function buildGovOptions(selectedId = '') {
    let html = `<option value="">— اختر —</option>`;
    GOVERNORATES.forEach(g => {
        const sel = (String(g.id) === String(selectedId)) ? 'selected' : '';
        html += `<option value="${g.id}" ${sel}>${g.name}</option>`;
    });
    return html;
}

// ===================================================
//  بناء عنصر <select> للحالة
// ===================================================
function buildStatusOptions(selectedVal = '') {
    let html = `<option value="">— اختر —</option>`;
    STATUSES.forEach(s => {
        const sel = (s.value === selectedVal) ? 'selected' : '';
        html += `<option value="${s.value}" ${sel}>${s.label}</option>`;
    });
    return html;
}

// ===================================================
//  إنشاء صف جديد
// ===================================================
function buildRow(idx) {
    const n = `rows[${idx}]`;
    return `
    <tr data-row="${idx}" id="row-${idx}">
        <!-- رقم الصف -->
        <td class="col-action text-center text-muted fw-bold" style="font-size:0.78rem;">${idx + 1}</td>

        <!-- #f_name: اسم المشروع -->
        <td class="col-name">
            <input type="text" id="f_name_${idx}" name="${n}[project_name]"
                   class="form-control" placeholder="اسم المشروع">
        </td>

        <!-- #f_type: نوع المشروع -->
        <td class="col-text">
            <input type="text" id="f_type_${idx}" name="${n}[project_type]"
                   class="form-control" placeholder="نوع المشروع">
        </td>

        <!-- #f_creator: اسم الجهة المنشئة -->
        <td class="col-text">
            <input type="text" id="f_creator_${idx}" name="${n}[created_by_entity]"
                   class="form-control" placeholder="اسم الجهة المنشئة">
        </td>

        <!-- #f_field: المجال -->
        <td class="col-field">
            <input type="text" id="f_field_${idx}" name="${n}[field]"
                   class="form-control" placeholder="المجال">
        </td>

        <!-- #f_gov: المحافظة (Dropdown) -->
        <td class="col-gov">
            <select id="f_gov_${idx}" name="${n}[governorate_id]" class="form-select gov-select" data-row="${idx}">
                ${buildGovOptions()}
            </select>
        </td>

        <!-- #f_district: المديرية -->
        <td class="col-dir">
            <input type="text" id="f_district_${idx}" name="${n}[directorate]"
                   class="form-control" placeholder="المديرية">
        </td>

        <!-- #f_cost: التكلفة الإجمالية -->
        <td class="col-num">
            <input type="number" id="f_cost_${idx}" name="${n}[total_cost]"
                   class="form-control contribution-input cost-calc-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01">
        </td>

        <!-- #f_spent: المبلغ المصروف -->
        <td class="col-num">
            <input type="number" id="f_spent_${idx}" name="${n}[spent_amount]"
                   class="form-control spent-calc-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01">
        </td>

        <!-- #f_remaining: المبلغ المتبقي -->
        <td class="col-num">
            <input type="number" id="f_remaining_${idx}" name="${n}[remaining_amount]"
                   class="form-control remaining-calc-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01" readonly tabindex="-1" style="background-color: #e9ecef;">
        </td>

        <!-- #f_hijri: السنة الهجرية -->
        <td class="col-year">
            <input type="number" id="f_hijri_${idx}" name="${n}[hijri_year]"
                   class="form-control" placeholder="1445">
        </td>

        <!-- #f_beneficiary: المستفيد -->
        <td class="col-text">
            <input type="text" id="f_beneficiary_${idx}" name="${n}[beneficiary]"
                   class="form-control" placeholder="المستفيد">
        </td>

        <!-- #f_indicator: المؤشر -->
        <td class="col-text">
            <input type="text" id="f_indicator_${idx}" name="${n}[indicator]"
                   class="form-control" placeholder="المؤشر">
        </td>

        <!-- #f_target: القيمة المستهدفة -->
        <td class="col-num">
            <input type="number" id="f_target_${idx}" name="${n}[target_value]"
                   class="form-control progress-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01">
        </td>

        <!-- #f_achieved: المنجز -->
        <td class="col-num">
            <input type="number" id="f_achieved_${idx}" name="${n}[achieved]"
                   class="form-control progress-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01">
        </td>

        <!-- #f_achievedPercent: نسبة الإنجاز (محسوبة) -->
        <td class="col-pct">
            <input type="text" id="f_achievedPercent_${idx}" name="${n}[achieved_percent]"
                   class="form-control" readonly placeholder="—" tabindex="-1">
        </td>

        <!-- #f_unitContribution: مساهمة الوحدة -->
        <td class="col-num">
            <input type="number" id="f_unitContribution_${idx}" name="${n}[unit_contribution]"
                   class="form-control contribution-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01">
        </td>

        <!-- #f_unitSpent: إنفاق الوحدة -->
        <td class="col-num">
            <input type="number" id="f_unitSpent_${idx}" name="${n}[unit_spent]"
                   class="form-control contribution-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01">
        </td>

        <!-- #f_communityContribution: مساهمة المجتمع -->
        <td class="col-num">
            <input type="number" id="f_communityContribution_${idx}" name="${n}[community_contribution]"
                   class="form-control contribution-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01">
        </td>

        <!-- #f_otherCost: تكلفة جهة أخرى -->
        <td class="col-num">
            <input type="number" id="f_otherCost_${idx}" name="${n}[other_cost]"
                   class="form-control contribution-input" data-row="${idx}"
                   placeholder="0" min="0" step="0.01">
        </td>

        <!-- #f_otherName: اسم الجهة الأخرى -->
        <td class="col-text">
            <input type="text" id="f_otherName_${idx}" name="${n}[other_name]"
                   class="form-control" placeholder="اسم الجهة">
        </td>

        <!-- #f_totalPercent: إجمالي المساهمة (قراءة فقط – محسوب) -->
        <td class="col-pct">
            <input type="text" id="f_totalPercent_${idx}" name="${n}[total_contribution]"
                   class="form-control" readonly placeholder="—" tabindex="-1">
        </td>

        <!-- #f_approvalYear: سنة الاعتماد -->
        <td class="col-year">
            <input type="number" id="f_approvalYear_${idx}" name="${n}[approval_year]"
                   class="form-control" placeholder="${new Date().getFullYear()}"
                   min="2000" max="2100">
        </td>

        <!-- #f_executionYear: سنة التنفيذ -->
        <td class="col-year">
            <input type="number" id="f_executionYear_${idx}" name="${n}[execution_year]"
                   class="form-control" placeholder="${new Date().getFullYear()}"
                   min="2000" max="2100">
        </td>

        <!-- #f_funder: جهة التمويل -->
        <td class="col-text">
            <input type="text" id="f_funder_${idx}" name="${n}[funder]"
                   class="form-control" placeholder="جهة التمويل">
        </td>

        <!-- #f_executor: جهة التنفيذ -->
        <td class="col-text">
            <input type="text" id="f_executor_${idx}" name="${n}[executor]"
                   class="form-control" placeholder="جهة التنفيذ">
        </td>

        <!-- #f_supervisor: جهة الإشراف -->
        <td class="col-text">
            <input type="text" id="f_supervisor_${idx}" name="${n}[supervisor]"
                   class="form-control" placeholder="جهة الإشراف">
        </td>

        <!-- #f_beneficiaryEntity: الجهة المستفيدة (للمشاريع القديمة فقط) -->
        <td class="col-text">
            <input type="text" id="f_beneficiaryEntity_${idx}" name="${n}[beneficiary_entity]"
                   class="form-control" placeholder="الجهة المستفيدة" title="للمشاريع القديمة فقط">
        </td>

        <!-- #f_financialProgress: التقدم المالي -->
        <td class="col-prog">
            <input type="number" id="f_financialProgress_${idx}" name="${n}[financial_progress]"
                   class="form-control overall-input" data-row="${idx}"
                   placeholder="0" min="0" max="100" step="0.1">
        </td>

        <!-- #f_technicalProgress: التقدم الفني -->
        <td class="col-prog">
            <input type="number" id="f_technicalProgress_${idx}" name="${n}[technical_progress]"
                   class="form-control overall-input" data-row="${idx}"
                   placeholder="0" min="0" max="100" step="0.1">
        </td>

        <!-- #f_overallProgress: التقدم الإجمالي (قراءة فقط – محسوب) -->
        <td class="col-pct">
            <input type="text" id="f_overallProgress_${idx}" name="${n}[overall_progress]"
                   class="form-control" readonly placeholder="—" tabindex="-1">
        </td>

        <!-- #f_status: الحالة (Dropdown) -->
        <td class="col-status">
            <select id="f_status_${idx}" name="${n}[status]" class="form-select">
                ${buildStatusOptions()}
            </select>
        </td>

        <!-- #f_notes: الملاحظات -->
        <td class="col-notes">
            <input type="text" id="f_notes_${idx}" name="${n}[notes]"
                   class="form-control" placeholder="ملاحظات اختيارية">
        </td>

        <!-- #f_fileInput: رفع مستند -->
        <td class="col-file">
            <div style="position:relative;">
                <label for="f_fileInput_${idx}" class="file-upload-mini" id="fileLabel_${idx}"
                       style="
                            display:flex; align-items:center; gap:0.4rem;
                            background:#f0f9ff; border:1.5px dashed #93c5fd;
                            border-radius:7px; padding:0.3rem 0.6rem;
                            cursor:pointer; font-size:0.75rem; color:#1d4ed8; font-weight:600;
                            transition:background 0.2s, border-color 0.2s;
                       "
                       onmouseover="this.style.background='#dbeafe'"
                       onmouseout="this.style.background='#f0f9ff'">
                    <i class="fas fa-paperclip"></i>
                    <span id="fileNameText_${idx}">اختر ملفاً</span>
                </label>
                <input type="file" id="f_fileInput_${idx}" name="${n}[document]"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                       style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                       onchange="updateFileName(${idx}, this)">
            </div>
        </td>

        <!-- زر الحذف -->
        <td class="col-action text-center">
            <button type="button" class="btn-row-action btn-row-delete"
                    onclick="deleteRow(${idx})" title="حذف الصف">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>`;
}

// ===================================================
//  إضافة صف
// ===================================================
function addRow() {
    const tbody = document.getElementById('importTableBody');
    tbody.insertAdjacentHTML('beforeend', buildRow(rowCounter));
    rowCounter++;
    updateRowNumbers();
    updateRowCount();
}

// ===================================================
//  حذف صف
// ===================================================
function deleteRow(idx) {
    const row = document.getElementById(`row-${idx}`);
    if (!row) return;

    const tbody = document.getElementById('importTableBody');
    if (tbody.querySelectorAll('tr').length <= 1) {
        showToast('يجب أن تبقى على الأقل صف واحد.', 'warning');
        return;
    }

    row.style.transition = 'opacity 0.2s, transform 0.2s';
    row.style.opacity = '0';
    row.style.transform = 'translateX(20px)';
    setTimeout(() => {
        row.remove();
        updateRowNumbers();
        updateRowCount();
    }, 200);
}

// ===================================================
//  تحديث أرقام الصفوف
// ===================================================
function updateRowNumbers() {
    const rows = document.querySelectorAll('#importTableBody tr');
    rows.forEach((tr, i) => {
        const numCell = tr.querySelector('td:first-child');
        if (numCell) numCell.textContent = i + 1;
    });
}

// ===================================================
//  تحديث عداد الصفوف
// ===================================================
function updateRowCount() {
    const count = document.querySelectorAll('#importTableBody tr').length;
    document.getElementById('rowCount').textContent = count;
    document.getElementById('rowCountFooter').textContent = count;
}

// ===================================================
//  تحديث اسم الملف المختار
// ===================================================
function updateFileName(idx, input) {
    const nameEl = document.getElementById(`fileNameText_${idx}`);
    if (!nameEl) return;
    if (input.files && input.files.length > 0) {
        let name = input.files[0].name;
        if (name.length > 20) name = name.substring(0, 18) + '…';
        nameEl.textContent = name;
    } else {
        nameEl.textContent = 'اختر ملفاً';
    }
}

// ===================================================
//  احتساب الحقول التلقائية
// ===================================================
document.addEventListener('input', function(e) {
    // المبلغ المتبقي = التكلفة الإجمالية - المبلغ المصروف
    if (e.target.classList.contains('cost-calc-input') || e.target.classList.contains('spent-calc-input')) {
        const idx   = e.target.dataset.row;
        const total = parseFloat(document.getElementById(`f_cost_${idx}`)?.value)  || 0;
        const spent = parseFloat(document.getElementById(`f_spent_${idx}`)?.value) || 0;
        const remEl = document.getElementById(`f_remaining_${idx}`);
        if (remEl) {
            remEl.value = (total || spent) ? (total - spent).toFixed(2) : '';
        }
    }

    // نسبة الإنجاز = (المنجز / المستهدف) × 100
    if (e.target.classList.contains('progress-input')) {
        const idx = e.target.dataset.row;
        const target   = parseFloat(document.getElementById(`f_target_${idx}`)?.value)   || 0;
        const achieved = parseFloat(document.getElementById(`f_achieved_${idx}`)?.value)  || 0;
        const pctEl    = document.getElementById(`f_achievedPercent_${idx}`);
        if (pctEl) {
            pctEl.value = (target > 0) ? (achieved / target * 100).toFixed(1) + ' %' : '—';
        }
    }

    // إجمالي المساهمة = مساهمة الوحدة + مساهمة المجتمع + تكلفة الجهة الأخرى
    if (e.target.classList.contains('contribution-input')) {
        const idx  = e.target.dataset.row;
        const unit = parseFloat(document.getElementById(`f_unitContribution_${idx}`)?.value)    || 0;
        const comm = parseFloat(document.getElementById(`f_communityContribution_${idx}`)?.value) || 0;
        const othr = parseFloat(document.getElementById(`f_otherCost_${idx}`)?.value)           || 0;
        const totEl = document.getElementById(`f_totalPercent_${idx}`);
        if (totEl) {
            const sum = unit + comm + othr;
            totEl.value = sum > 0 ? sum.toLocaleString('ar-YE') : '—';
        }
    }

    // التقدم الإجمالي = متوسط (التقدم المالي + التقدم الفني)
    if (e.target.classList.contains('overall-input')) {
        const idx       = e.target.dataset.row;
        const financial = parseFloat(document.getElementById(`f_financialProgress_${idx}`)?.value)  || 0;
        const technical = parseFloat(document.getElementById(`f_technicalProgress_${idx}`)?.value)  || 0;
        const overallEl = document.getElementById(`f_overallProgress_${idx}`);
        if (overallEl) {
            overallEl.value = ((financial + technical) / 2).toFixed(1) + ' %';
        }
    }
});

// ===================================================
//  إرسال النموذج
// ===================================================
function submitImport() {
    const form = document.getElementById('importListForm');
    const rows = document.querySelectorAll('#importTableBody tr');

    // التحقق الأساسي
    let valid = true;
    rows.forEach((tr) => {
        const nameInput = tr.querySelector('input[name$="[project_name]"]');
        const govSelect = tr.querySelector('select[name$="[governorate_id]"]');
        const statusSel = tr.querySelector('select[name$="[status]"]');

        [nameInput, govSelect, statusSel].forEach(el => {
            if (el && !el.value.trim()) {
                el.style.borderColor = '#ef4444';
                el.style.boxShadow   = '0 0 0 3px rgba(239,68,68,0.15)';
                valid = false;
            } else if (el) {
                el.style.borderColor = '';
                el.style.boxShadow   = '';
            }
        });
    });

    if (!valid) {
        showToast('يرجى ملء الحقول المطلوبة (اسم المشروع، المحافظة، الحالة) في جميع الصفوف.', 'error');
        return;
    }

    const btn = document.getElementById('submitImportBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جارٍ الاستيراد...';

    form.submit();
}

// ===================================================
//  إشعار مؤقت (Toast)
// ===================================================
function showToast(message, type = 'info') {
    const colors = {
        info:    { bg: '#1d4ed8', icon: 'fa-info-circle' },
        success: { bg: '#16a34a', icon: 'fa-check-circle' },
        warning: { bg: '#d97706', icon: 'fa-exclamation-triangle' },
        error:   { bg: '#dc2626', icon: 'fa-times-circle' },
    };
    const c = colors[type] || colors.info;

    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed; bottom:90px; left:50%; transform:translateX(-50%);
        background:${c.bg}; color:#fff; padding:0.75rem 1.5rem;
        border-radius:10px; font-size:0.85rem; font-weight:600;
        z-index:9999; box-shadow:0 6px 20px rgba(0,0,0,0.2);
        display:flex; align-items:center; gap:0.5rem;
        animation:fadeInUp 0.3s ease;
        max-width:90vw; text-align:center;
    `;
    toast.innerHTML = `<i class="fas ${c.icon}"></i> ${message}`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ===================================================
//  تهيئة الصفحة
// ===================================================
document.addEventListener('DOMContentLoaded', function () {
    // إضافة الصف الأول تلقائياً
    addRow();

    // CSS Animation للـ Toast
    const style = document.createElement('style');
    style.textContent = `@keyframes fadeInUp { from { opacity:0; transform:translateX(-50%) translateY(20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }`;
    document.head.appendChild(style);
});
</script>
@endpush

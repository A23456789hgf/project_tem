 @extends('layouts.app')

@section('title', 'تسجيل إنجاز - ' . $project->project_name)

@section('styles')
<style>
    .bg-gradient-premium {
        background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8f 100%);
    }
    .text-gold {
        color: #c9a961 !important;
    }
    .border-gold {
        border-color: #c9a961 !important;
    }
    .card {
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(226, 232, 240, 0.8);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-header {
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .form-section-title {
        position: relative;
        padding-right: 15px;
        font-weight: 700;
        color: #1e3a5f;
    }
    .form-section-title::before {
        content: '';
        position: absolute;
        right: 0;
        top: 4px;
        bottom: 4px;
        width: 4px;
        background-color: #c9a961;
        border-radius: 2px;
    }
    .display-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 15px;
        min-height: 43px;
        color: #334155;
    }
    .upload-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        background-color: #f8fafc;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .upload-zone:hover {
        border-color: #1e3a5f;
        background-color: #f1f5f9;
    }
    .upload-icon {
        font-size: 2.5rem;
        color: #94a3b8;
        margin-bottom: 10px;
    }
    .file-item {
        background-color: #f1f5f9;
        border-radius: 8px;
        padding: 8px 12px;
        margin-top: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .table th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        border-bottom: 2px solid #e2e8f0;
    }
    .table td {
        vertical-align: middle;
    }
    .warning-message {
        font-size: 0.8rem;
        color: #dc3545;
        margin-top: 4px;
    }
    .summary-table th {
        background-color: #e9ecef;
        font-weight: 700;
    }
    .summary-table .text-danger {
        font-weight: bold;
    }
    .summary-table .text-success {
        font-weight: bold;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-muted">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted">المشاريع</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->id) }}" class="text-muted">تفاصيل المشروع</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold">تسجيل إنجاز</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="fas fa-trophy text-gold me-2"></i> تسجيل إنجاز المشروع
            </h1>
            <p class="text-muted small mb-0">قم بتوثيق الإنجازات وتحديث بيانات الصرف ونسب التقدم للشركاء والممولين.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-secondary rounded-pill">
                <i class="fas fa-arrow-right me-1"></i> العودة للمشروع
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle fs-4 me-3 text-danger"></i>
                <div>
                    <strong>فشل الحفظ!</strong> يرجى تصحيح الأخطاء التالية:<br>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @can('projects.create')
    <form action="{{ route('projects.achievements.store', $project->id) }}" method="POST" enctype="multipart/form-data" id="achievementForm" class="needs-validation" novalidate>
    @endcan
        @csrf

        <div class="row">
            <div class="col-lg-12">
                
                <!-- Section 1: Basic Data -->
                <div class="card mb-4 overflow-hidden">
                    <div class="card-header bg-gradient-premium text-white py-3">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-info-circle text-gold me-2"></i> القسم الأول: البيانات الأساسية للمشروع</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold text-secondary">رقم المشروع</label>
                                <div class="display-box fw-bold">{{ $project->form_number ?? 'غير محدد' }}</div>
                            </div>
                            <div class="col-md-9 mb-3">
                                <label class="form-label fw-bold text-secondary">اسم المشروع</label>
                                <div class="display-box fw-bold">{{ $project->project_name }}</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold text-secondary">المحافظة</label>
                                <div class="display-box">{{ $governorates }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold text-secondary">المديرية</label>
                                <div class="display-box">{{ $directorates }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold text-secondary">الجهة المنفذة</label>
                                <div class="display-box">{{ $implementingEntitiesList }}</div>
                            </div>
                        </div>

                        <hr class="my-4 text-muted">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="report_type_id" class="form-label fw-bold">نوع التقرير <span class="text-danger">*</span></label>
                                <select name="report_type_id" id="report_type_id" class="form-select select-search" required>
                                    <option value="">-- اختر نوع التقرير --</option>
                                    @foreach($reportTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('report_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">يرجى اختيار نوع التقرير.</div>
                            </div>

                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date_gregorian" class="form-label fw-bold">تاريخ البدء (ميلادي) <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date_gregorian" id="start_date_gregorian" class="form-control" 
                                               value="{{ old('start_date_gregorian') }}" required data-hijri-target="#start_date_hijri">
                                        <div class="invalid-feedback">يرجى تحديد تاريخ البدء الميلادي.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date_hijri" class="form-label fw-bold text-muted">تاريخ البدء الموافق (هجري)</label>
                                        <input type="text" name="start_date_hijri" id="start_date_hijri" class="form-control bg-light" 
                                               value="{{ old('start_date_hijri') }}" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date_gregorian" class="form-label fw-bold">تاريخ الانتهاء (ميلادي) <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date_gregorian" id="end_date_gregorian" class="form-control" 
                                               value="{{ old('end_date_gregorian') }}" required data-hijri-target="#end_date_hijri">
                                        <div class="invalid-feedback">يرجى تحديد تاريخ الانتهاء الميلادي.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date_hijri" class="form-label fw-bold text-muted">تاريخ الانتهاء الموافق (هجري)</label>
                                        <input type="text" name="end_date_hijri" id="end_date_hijri" class="form-control bg-light" 
                                               value="{{ old('end_date_hijri') }}" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="duration" class="form-label fw-bold text-secondary">مدة التقرير المحتسبة</label>
                                        <input type="text" name="duration" id="duration" class="form-control bg-light" 
                                               value="{{ old('duration') }}" readonly placeholder="سيتم احتساب المدة تلقائياً بناءً على التواريخ المدخلة">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Achievement Data -->
                <div class="card mb-4 overflow-hidden">
                    <div class="card-header bg-gradient-premium text-white py-3">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-chart-line text-gold me-2"></i> القسم الثاني: بيانات الإنجاز ومؤشرات الأداء</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary">الإنجاز السابق (تراكمي) %</label>
                                <div class="input-group">
                                    <input type="number" id="previous_achievement_display" class="form-control bg-light fw-bold text-primary" 
                                           value="{{ $previousAchievementValue }}" readonly>
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                                <small class="text-muted">تم جلبه تلقائياً من آخر تقرير إنجاز مسجل للمشروع.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_achievement" class="form-label fw-bold">نسبة الإنجاز الإجمالية الجديدة المستهدفة (تراكمي) % <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" name="new_achievement" id="new_achievement" 
                                           class="form-control fw-bold" value="{{ old('new_achievement') }}" required>
                                    <span class="input-group-text">%</span>
                                    <div class="invalid-feedback">يرجى إدخال نسبة مئوية صحيحة بين 0 و 100.</div>
                                </div>
                                <small class="text-muted">أدخل النسبة المئوية التراكمية الإجمالية الجديدة بعد الإنجاز الحالي.</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="achieved_outputs" class="form-label fw-bold">المخرجات المحققة (نص طويل) <span class="text-danger">*</span></label>
                                <textarea name="achieved_outputs" id="achieved_outputs" rows="4" class="form-control" 
                                          placeholder="اكتب المخرجات والنتائج التي تم تحقيقها خلال هذه الفترة بالتفصيل..." required>{{ old('achieved_outputs') }}</textarea>
                                <div class="invalid-feedback">يرجى كتابة المخرجات المحققة.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="achieved_indicators" class="form-label fw-bold">المؤشرات المحققة (نص طويل) <span class="text-danger">*</span></label>
                                <textarea name="achieved_indicators" id="achieved_indicators" rows="4" class="form-control" 
                                          placeholder="اكتب مؤشرات القياس والتحقق التي تم إنجازها خلال هذه الفترة..." required>{{ old('achieved_indicators') }}</textarea>
                                <div class="invalid-feedback">يرجى كتابة المؤشرات المحققة.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="number_of_beneficiaries" class="form-label fw-bold">عدد المستفيدين <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-users"></i></span>
                                    <input type="number" min="0" name="number_of_beneficiaries" id="number_of_beneficiaries" 
                                           class="form-control" value="{{ old('number_of_beneficiaries', 0) }}" required>
                                    <div class="invalid-feedback">يرجى تحديد عدد المستفيدين بشكل صحيح.</div>
                                </div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="notes_on_beneficiaries" class="form-label fw-bold">ملاحظات وتفاصيل عن المستفيدين <span class="text-danger">*</span></label>
                                <textarea name="notes_on_beneficiaries" id="notes_on_beneficiaries" rows="2" class="form-control" 
                                          placeholder="تفاصيل الفئات المستفيدة (ذكور/إناث، مناطق محددة، إلخ)..." required>{{ old('notes_on_beneficiaries') }}</textarea>
                                <div class="invalid-feedback">يرجى كتابة ملاحظات المستفيدين.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Funding and Disbursement Data -->
                <div class="card mb-4 overflow-hidden">
                    <div class="card-header bg-gradient-premium text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-hand-holding-usd text-gold me-2"></i> القسم الثالث: بيانات التمويل والصرف المالي</h5>
                        <button type="button" class="btn btn-sm btn-premium rounded-pill px-3" id="addFundingRow">
                            <i class="fas fa-plus me-1"></i> إضافة جهة تمويل
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- ===== START: Funding Summary Table ===== -->
                        @if(isset($fundingSummary) && count($fundingSummary) > 0)
                        <div class="mb-4">
                            <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-chart-pie text-gold me-2"></i> ملخص التمويلات والصرف التراكمي للمشروع</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped summary-table">
                                    <thead>
                                        <tr>
                                            <th>الجهة الممولة</th>
                                            <th style="width: 15%;">إجمالي التمويل</th>
                                            <th style="width: 15%;">إجمالي الصرف التراكمي</th>
                                            <th style="width: 15%;">المتبقي</th>
                                            <th style="width: 25%;">نسبة الصرف</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($fundingSummary as $authorityId => $item)
                                            @php
                                                $percentage = ($item['total_funding'] > 0) ? ($item['total_disbursed'] / $item['total_funding']) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $item['authority_name'] }}</td>
                                                <td class="font-monospace">{{ number_format($item['total_funding'], 2) }}</td>
                                                <td class="font-monospace">{{ number_format($item['total_disbursed'], 2) }}</td>
                                                <td class="font-monospace {{ $item['remaining'] < 0 ? 'text-danger' : ($item['remaining'] == 0 ? 'text-success' : '') }}">
                                                    {{ number_format($item['remaining'], 2) }}
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="font-monospace me-2">{{ number_format($percentage, 1) }}%</span>
                                                        <div class="progress flex-grow-1" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success') }}"
                                                                 role="progressbar" style="width: {{ min($percentage, 100) }}%;"
                                                                 aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <hr class="my-3">
                        </div>
                        @endif
                        <!-- ===== END: Funding Summary Table ===== -->

                        <!-- ===== START: Funding Entry Table ===== -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle" id="fundingTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 250px;">الجهة الممولة <span class="text-danger">*</span></th>
                                        <th style="width: 150px;">إجمالي التمويل <span class="text-danger">*</span></th>
                                        <th style="width: 150px;">الصرف السابق <span class="text-danger">*</span></th>
                                        <th style="width: 160px;">المتبقي السابق من الصرف</th>
                                        <th style="width: 150px;">الصرف الجديد <span class="text-danger">*</span></th>
                                        <th style="width: 140px;">نسبة الصرف الكلية</th>
                                        <th style="width: 50px;">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="fundingTableBody">
                                    <!-- Rows will be dynamically loaded via JS -->
                                </tbody>
                            </table>
                        </div>
                        <div id="fundingTableEmptyMsg" class="text-center py-4 text-muted">
                            <i class="fas fa-receipt fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0">لا توجد جهات تمويل مضافة حالياً. اضغط على "إضافة جهة تمويل" للبدء.</p>
                        </div>
                        <!-- ===== END: Funding Entry Table ===== -->
                    </div>
                </div>

                <!-- Section 4: Notes and Attachments -->
                <div class="card mb-4 overflow-hidden">
                    <div class="card-header bg-gradient-premium text-white py-3">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-paperclip text-gold me-2"></i> القسم الرابع: الملاحظات والمرفقات الداعمة</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">المرفقات والوثائق المؤيدة للإنجاز</label>
                                <div class="upload-zone" onclick="document.getElementById('attachments').click()">
                                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                    <h5>اسحب الملفات هنا أو اضغط للتصفح</h5>
                                    <p class="text-muted small">يمكنك اختيار ملفات متعددة (PDF, DOC, JPG, PNG, XLS) بحد أقصى 20 ميجابايت للملف.</p>
                                    <input type="file" name="attachments[]" id="attachments" class="d-none" multiple>
                                </div>
                                <div id="fileList" class="mt-3"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="comments" class="form-label fw-bold">ملاحظات وتوصيات عامة (نص طويل)</label>
                                <textarea name="comments" id="comments" rows="6" class="form-control" 
                                          placeholder="أدخل أي ملاحظات إضافية، تحديات واجهت التنفيذ، أو توصيات متعلقة بالإنجاز المذكور..." >{{ old('comments') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Form Buttons -->
                <div class="card mb-4 bg-light border-0">
                    <div class="card-body d-flex justify-content-end gap-3 py-3">
                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-secondary px-4 rounded-pill">
                            إلغاء
                        </a>
                        <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">
                            <i class="fas fa-save me-1"></i> حفظ وإنجاز
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>

    <!-- Achievements History Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-history text-gold me-2"></i> السجل التاريخي للإنجازات والتقارير المرفوعة</h5>
                </div>
                <div class="card-body">
                    @if($achievementsHistory->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>نوع التقرير</th>
                                        <th>الفترة (من - إلى)</th>
                                        <th>المدة</th>
                                        <th>نسبة الإنجاز السابقة</th>
                                        <th>نسبة الإنجاز المحققة</th>
                                        <th>عدد المستفيدين</th>
                                        <th>المرفقات</th>
                                        <th>تاريخ الإضافة</th>
                                        <th>بواسطة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($achievementsHistory as $hist)
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $hist->reportType->name }}</td>
                                            <td>
                                                <div class="small">
                                                    <strong>ميلادي:</strong> {{ $hist->start_date_gregorian->format('Y-m-d') }} إلى {{ $hist->end_date_gregorian->format('Y-m-d') }}<br>
                                                    <strong>هجري:</strong> {{ $hist->start_date_hijri }} إلى {{ $hist->end_date_hijri }}
                                                </div>
                                            </td>
                                            <td>{{ $hist->duration }}</td>
                                            <td class="text-center font-monospace">{{ $hist->previous_achievement }}%</td>
                                            <td class="text-center fw-bold font-monospace text-success">{{ $hist->new_achievement }}%</td>
                                            <td class="font-monospace">{{ number_format($hist->number_of_beneficiaries) }}</td>
                                            <td>
                                                @if($hist->documents->count() > 0)
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-info dropdown-toggle py-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-paperclip"></i> ({{ $hist->documents->count() }})
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            @foreach($hist->documents as $doc)
                                                                <li>
                                                                    <a class="dropdown-item py-1" href="{{ asset('storage/' . $doc->file_path) }}" target="_blank">
                                                                        <i class="fas {{ $doc->file_icon }} text-secondary me-2"></i>{{ $doc->file_name }}
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">لا توجد</span>
                                                @endif
                                            </td>
                                            <td>{{ $hist->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="small">{{ $hist->creator->name ?? 'غير معروف' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0">لا توجد تقارير إنجاز سابقة مسجلة لهذا المشروع بعد.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ── Step 1: Manage Files List
        const fileInput = document.getElementById('attachments');
        const fileList = document.getElementById('fileList');
        
        fileInput.addEventListener('change', function () {
            fileList.innerHTML = '';
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                const sizeKB = (file.size / 1024).toFixed(1);
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <span class="small"><i class="fas fa-file text-secondary me-2"></i>${file.name} (${sizeKB} KB)</span>
                    <i class="fas fa-check-circle text-success"></i>
                `;
                fileList.appendChild(fileItem);
            }
        });

        // ── Step 2: Date Hijri Synchronization and Duration Calculate
        const startGreg = document.getElementById('start_date_gregorian');
        const endGreg = document.getElementById('end_date_gregorian');
        const durationField = document.getElementById('duration');

        function calculateDuration() {
            if (startGreg.value && endGreg.value) {
                const start = new Date(startGreg.value + 'T00:00:00');
                const end = new Date(endGreg.value + 'T00:00:00');
                const diffTime = end.getTime() - start.getTime();
                const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays >= 0) {
                    const months = (diffDays / 30.436875).toFixed(1);
                    durationField.value = `${diffDays} يوم (ما يعادل ${months} شهر)`;
                } else {
                    durationField.value = 'خطأ: تاريخ النهاية يسبق تاريخ البداية!';
                }
            } else {
                durationField.value = '';
            }
        }

        startGreg.addEventListener('input', calculateDuration);
        startGreg.addEventListener('change', calculateDuration);
        endGreg.addEventListener('input', calculateDuration);
        endGreg.addEventListener('change', calculateDuration);

        // ── Step 3: Funding Master-Detail Dynamic Behavior with authority_id
        const fundingTableBody = document.getElementById('fundingTableBody');
        const addFundingRowBtn = document.getElementById('addFundingRow');
        const emptyMsg = document.getElementById('fundingTableEmptyMsg');
        let rowIndex = 0;

        // بيانات الجهات (كل الجهات) من المتحكم
        const allAuthoritiesData = @json($allAuthorities->map(function($auth) {
            return ['id' => $auth->id, 'name' => $auth->agency_name];
        }));

        // بيانات التمويل الإجمالي للمشروع (من financings)
        const projectFundingData = @json($projectFundingData ?? []);

        // بيانات الصرف السابق من آخر إنجاز
        const lastFundingData = @json($lastFundingData ?? []);

        function checkEmptyTable() {
            if (fundingTableBody.children.length === 0) {
                emptyMsg.classList.remove('d-none');
            } else {
                emptyMsg.classList.add('d-none');
            }
        }

        function createFundingRow(selectedAuthorityId = null) {
            const tr = document.createElement('tr');
            tr.className = 'funding-row';
            tr.dataset.index = rowIndex;

            // Build select options: all authorities + "new" option
            let optionsHtml = '<option value="">-- اختر جهة تمويل --</option>';
            allAuthoritiesData.forEach(auth => {
                const selected = (selectedAuthorityId && auth.id == selectedAuthorityId) ? 'selected' : '';
                optionsHtml += `<option value="${auth.id}" ${selected}>${auth.name}</option>`;
            });
            optionsHtml += `<option value="new">+ إضافة جهة جديدة</option>`;

            // Initial values if selectedAuthorityId is provided
            let initTotal = 0;
            let initPrev = 0;
            let initPrevRem = 0;

            if (selectedAuthorityId) {
                if (lastFundingData[selectedAuthorityId]) {
                    initTotal = parseFloat(lastFundingData[selectedAuthorityId].total_funding) || 0;
                    initPrev = parseFloat(lastFundingData[selectedAuthorityId].previous_disbursement) || 0;
                    initPrevRem = parseFloat(lastFundingData[selectedAuthorityId].previous_remaining_disbursement) || Math.max(0, initTotal - initPrev);
                } else if (projectFundingData[selectedAuthorityId]) {
                    initTotal = parseFloat(projectFundingData[selectedAuthorityId].total_funding) || 0;
                    initPrev = 0;
                    initPrevRem = initTotal;
                }
            }

            tr.innerHTML = `
                <td>
                    <select name="fundings[${rowIndex}][authority_id]" class="form-select funding-authority-select select-search-dynamic" required>
                        ${optionsHtml}
                    </select>
                    <div class="new-authority-container mt-1" style="display:none;">
                        <input type="text" name="fundings[${rowIndex}][new_authority_name]" class="form-control new-authority-input" placeholder="أدخل اسم الجهة الجديدة">
                    </div>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="fundings[${rowIndex}][total_funding]" class="form-control total-funding-input" required value="${initTotal}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="fundings[${rowIndex}][previous_disbursement]" class="form-control prev-disbursement-input" required value="${initPrev}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="fundings[${rowIndex}][previous_remaining_disbursement]" class="form-control prev-remaining-input bg-light" readonly value="${initPrevRem.toFixed(2)}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="fundings[${rowIndex}][new_disbursement]" class="form-control new-disbursement-input" required value="" placeholder="0.00">
                    <div class="warning-message new-disbursement-warning" style="display:none;"><i class="fas fa-exclamation-triangle me-1"></i> لا يمكن أن يتجاوز الصرف الجديد المتبقي السابق.</div>
                </td>
                <td>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="100" name="fundings[${rowIndex}][disbursement_percentage]" class="form-control percentage-input bg-light fw-bold" readonly value="${initTotal > 0 ? ((initPrev / initTotal) * 100).toFixed(2) : '0.00'}">
                        <span class="input-group-text">%</span>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;

            fundingTableBody.appendChild(tr);
            rowIndex++;
            checkEmptyTable();

            // Bind triggers to newly created inputs
            bindRowEvents(tr);
        }

        function bindRowEvents(row) {
            const authSelect = row.querySelector('.funding-authority-select');
            const newContainer = row.querySelector('.new-authority-container');
            const newInput = row.querySelector('.new-authority-input');
            const totalInput = row.querySelector('.total-funding-input');
            const prevInput = row.querySelector('.prev-disbursement-input');
            const prevRemInput = row.querySelector('.prev-remaining-input');
            const newDisbInput = row.querySelector('.new-disbursement-input');
            const percentInput = row.querySelector('.percentage-input');
            const warningMsg = row.querySelector('.new-disbursement-warning');
            const removeBtn = row.querySelector('.remove-row-btn');

            // 1. حساب المتبقي السابق وتحديث الحقل
            function updatePreviousRemaining() {
                const total = parseFloat(totalInput.value) || 0;
                const prev = parseFloat(prevInput.value) || 0;
                const remaining = Math.max(0, total - prev);
                prevRemInput.value = remaining.toFixed(2);
                return remaining;
            }

            // 2. التحقق من صحة الصرف الجديد وحساب النسبة دون تعديل مدخلات المستخدم أثناء الكتابة
            function validateAndCalculate() {
                const total = parseFloat(totalInput.value) || 0;
                const prev = parseFloat(prevInput.value) || 0;
                const prevRemaining = updatePreviousRemaining();

                const rawVal = newDisbInput.value.trim();

                // إذا كان الحقل فارغاً (أثناء المسح أو قبل البدء بالكتابة)
                if (rawVal === '') {
                    warningMsg.style.display = 'none';
                    newDisbInput.classList.remove('is-invalid');
                    newDisbInput.setCustomValidity('');

                    const pct = (total > 0) ? (prev / total) * 100 : 0;
                    percentInput.value = Math.min(100, pct).toFixed(2);
                    return true;
                }

                const newly = parseFloat(rawVal);

                if (isNaN(newly) || newly < 0) {
                    warningMsg.style.display = 'none';
                    newDisbInput.classList.remove('is-invalid');
                    newDisbInput.setCustomValidity('');
                    return false;
                }

                // التحقق: إذا تجاوز الصرف الجديد المبلغ المتبقي السابق
                if (newly - prevRemaining > 0.0001) {
                    warningMsg.style.display = 'block';
                    newDisbInput.classList.add('is-invalid');
                    newDisbInput.setCustomValidity('لا يمكن أن يتجاوز الصرف الجديد المتبقي السابق.');
                } else {
                    warningMsg.style.display = 'none';
                    newDisbInput.classList.remove('is-invalid');
                    newDisbInput.setCustomValidity('');
                }

                // حساب النسبة التراكمية الإجمالية
                const cumulative = prev + newly;
                let pct = 0;
                if (total > 0) {
                    pct = (cumulative / total) * 100;
                }
                percentInput.value = Math.min(100, pct).toFixed(2);

                if (cumulative - total > 0.0001) {
                    percentInput.classList.add('text-danger', 'is-invalid');
                } else {
                    percentInput.classList.remove('text-danger', 'is-invalid');
                }

                return (newly - prevRemaining <= 0.0001);
            }

            // عند تغيير الجهة الممولة
            authSelect.addEventListener('change', function () {
                if (this.value === 'new') {
                    newContainer.style.display = 'block';
                    newInput.required = true;
                    totalInput.value = 0;
                    prevInput.value = 0;
                    prevRemInput.value = (0).toFixed(2);
                    newDisbInput.value = '';
                    percentInput.value = (0).toFixed(2);
                    warningMsg.style.display = 'none';
                    newDisbInput.classList.remove('is-invalid');
                    newDisbInput.setCustomValidity('');
                    return;
                } else {
                    newContainer.style.display = 'none';
                    newInput.required = false;
                    newInput.value = '';
                }

                const authId = this.value;
                if (authId) {
                    let total = 0;
                    let prev = 0;

                    if (lastFundingData[authId]) {
                        total = parseFloat(lastFundingData[authId].total_funding) || 0;
                        prev = parseFloat(lastFundingData[authId].previous_disbursement) || 0;
                    } else if (projectFundingData[authId]) {
                        total = parseFloat(projectFundingData[authId].total_funding) || 0;
                        prev = 0;
                    }

                    totalInput.value = total;
                    prevInput.value = prev;
                    updatePreviousRemaining();
                    newDisbInput.value = '';
                } else {
                    totalInput.value = 0;
                    prevInput.value = 0;
                    updatePreviousRemaining();
                    newDisbInput.value = '';
                }
                validateAndCalculate();
            });

            // مراقبة أحداث الإدخال للحساب اللحظي والتحقق السلس
            totalInput.addEventListener('input', validateAndCalculate);
            prevInput.addEventListener('input', validateAndCalculate);
            newDisbInput.addEventListener('input', validateAndCalculate);
            newDisbInput.addEventListener('blur', validateAndCalculate);

            // زر حذف الصف
            removeBtn.addEventListener('click', function () {
                row.remove();
                checkEmptyTable();
            });
        }

        // Add funding row event
        addFundingRowBtn.addEventListener('click', function() {
            createFundingRow();
        });

        // Prepopulate rows for all distinct funding authorities from history or project configuration
        const allFundingAuthKeys = Array.from(new Set([
            ...Object.keys(lastFundingData),
            ...Object.keys(projectFundingData)
        ]));

        if (allFundingAuthKeys.length > 0) {
            allFundingAuthKeys.forEach(authId => {
                createFundingRow(authId);
            });
        } else {
            createFundingRow();
        }

        // ── Step 4: Bootstrap 5 Native Validation & Submit Guard
        const form = document.getElementById('achievementForm');
        form.addEventListener('submit', function (event) {
            let isValid = true;
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                isValid = false;
            }

            // Custom checks: Dates duration check
            const startVal = new Date(startGreg.value);
            const endVal = new Date(endGreg.value);
            if (startVal > endVal) {
                event.preventDefault();
                event.stopPropagation();
                endGreg.classList.add('is-invalid');
                isValid = false;
            } else {
                endGreg.classList.remove('is-invalid');
            }

            // Check if new disbursement exceeds remaining for each row
            const fundingRows = fundingTableBody.querySelectorAll('.funding-row');
            fundingRows.forEach(row => {
                const newDisbInput = row.querySelector('.new-disbursement-input');
                const rawVal = newDisbInput.value.trim();
                const newDisb = parseFloat(rawVal) || 0;
                const total = parseFloat(row.querySelector('.total-funding-input').value) || 0;
                const prev = parseFloat(row.querySelector('.prev-disbursement-input').value) || 0;
                const remaining = Math.max(0, total - prev);
                const warningMsg = row.querySelector('.new-disbursement-warning');

                if (newDisb - remaining > 0.0001) {
                    newDisbInput.classList.add('is-invalid');
                    warningMsg.style.display = 'block';
                    isValid = false;
                } else {
                    warningMsg.style.display = 'none';
                    newDisbInput.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
</script>
@endsection
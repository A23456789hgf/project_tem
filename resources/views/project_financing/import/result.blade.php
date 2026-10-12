@extends('layouts.app')

@section('title', 'نتيجة استيراد التمويلات ومطابقة المشاريع')

@section('content')
<div class="container-fluid py-3">
    <!-- Header Title -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-file-import text-primary me-2"></i> تقرير استيراد التمويلات ومطابقة المشاريع</h3>
            <p class="text-muted mb-0">نتائج معالجة ملف الإكسل وتفاصيل المطابقة الذكية للمشاريع وسجلات التمويل.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.financings.import.form') }}" class="btn btn-outline-primary rounded-pill px-3">
                <i class="fas fa-upload me-1"></i> استيراد ملف آخر
            </a>
            <a href="{{ route('projects.index') }}" class="btn btn-primary rounded-pill px-3">
                <i class="fas fa-list me-1"></i> قائمة المشاريع
            </a>
        </div>
    </div>

    <!-- 1. General Processing Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">إجمالي الصفوف المعالجة</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0">{{ $results['total_rows'] ?? $results['processed'] }}</h3>
                            <small class="text-muted">موزعة على {{ $results['stats']['total_projects'] ?? count($results['project_details'] ?? []) }} مشروع</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                            <i class="fas fa-table fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">المشاريع المحدثة بنجاح</span>
                            <h3 class="fw-bold text-success mt-1 mb-0">{{ $results['updated'] }}</h3>
                            <small class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i> تم استيراد {{ $results['records_imported'] }} تمويل</small>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                            <i class="fas fa-check-double fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-danger">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">الصفوف غير المستوردة / الفاشلة</span>
                            <h3 class="fw-bold text-danger mt-1 mb-0">{{ $results['failed'] }}</h3>
                            <small class="text-muted">تحتاج مراجعة أو لم يتم التعرف عليها</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                            <i class="fas fa-exclamation-circle fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">إجمالي مبالغ التمويل المستوردة</span>
                            <h4 class="fw-bold text-info mt-1 mb-0">{{ number_format($results['total_amount'], 2) }}</h4>
                            <small class="text-muted">ريال يمني</small>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle">
                            <i class="fas fa-coins fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Project Matching Breakdown Statistics -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fas fa-brain text-primary me-2"></i> إحصائيات مطابقة المشاريع الذكية (Matching Breakdown)
            </h5>
            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                إجمالي المشاريع في الملف: {{ $results['stats']['total_projects'] ?? 0 }}
            </span>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 text-center">
                <!-- Exact Match -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 h-100">
                        <div class="d-flex align-items-center justify-content-center gap-1 text-success mb-1">
                            <i class="fas fa-bullseye"></i>
                            <span class="small fw-bold">مطابقة تامة</span>
                        </div>
                        <h3 class="fw-bold text-success mb-0">{{ $results['stats']['matched_exact'] ?? 0 }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Exact Match</small>
                    </div>
                </div>

                <!-- Normalized Match -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 h-100">
                        <div class="d-flex align-items-center justify-content-center gap-1 text-primary mb-1">
                            <i class="fas fa-spell-check"></i>
                            <span class="small fw-bold">مطابقة بعد التوحيد</span>
                        </div>
                        <h3 class="fw-bold text-primary mb-0">{{ $results['stats']['matched_normalized'] ?? 0 }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Normalized Match</small>
                    </div>
                </div>

                <!-- Fuzzy Match -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-3 rounded-3 bg-indigo bg-opacity-10 border border-info border-opacity-25 h-100" style="background-color: #f0f4ff;">
                        <div class="d-flex align-items-center justify-content-center gap-1 text-primary mb-1" style="color: #4f46e5 !important;">
                            <i class="fas fa-project-diagram"></i>
                            <span class="small fw-bold">مطابقة ذكية تقريبية</span>
                        </div>
                        <h3 class="fw-bold mb-0" style="color: #4f46e5;">{{ $results['stats']['matched_fuzzy'] ?? 0 }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Fuzzy Match</small>
                    </div>
                </div>

                <!-- Needs Review -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-3 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 h-100">
                        <div class="d-flex align-items-center justify-content-center gap-1 text-warning mb-1">
                            <i class="fas fa-user-clock"></i>
                            <span class="small fw-bold">يحتاج مراجعة</span>
                        </div>
                        <h3 class="fw-bold text-warning mb-0">{{ $results['stats']['needs_review'] ?? 0 }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Needs Review</small>
                    </div>
                </div>

                <!-- Ambiguous -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 h-100">
                        <div class="d-flex align-items-center justify-content-center gap-1 text-danger mb-1">
                            <i class="fas fa-question-circle"></i>
                            <span class="small fw-bold">مطابقة غامضة</span>
                        </div>
                        <h3 class="fw-bold text-danger mb-0">{{ $results['stats']['ambiguous'] ?? 0 }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Ambiguous Match</small>
                    </div>
                </div>

                <!-- Not Found -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-3 rounded-3 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 h-100">
                        <div class="d-flex align-items-center justify-content-center gap-1 text-secondary mb-1">
                            <i class="fas fa-times-circle"></i>
                            <span class="small fw-bold">لم يتم العثور عليه</span>
                        </div>
                        <h3 class="fw-bold text-secondary mb-0">{{ $results['stats']['not_found'] ?? 0 }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Project Not Found</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Errors / Diagnostics Alert (if any) -->
    @if(!empty($results['errors']) && count($results['errors']) > 0)
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-danger">
            <div class="card-header bg-danger bg-opacity-10 py-3 border-0 d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0 text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> ملاحظات وأخطاء الاستيراد ({{ count($results['errors']) }})
                </h5>
            </div>
            <div class="card-body p-3">
                <div class="alert alert-light border mb-0 text-dark" style="max-height: 250px; overflow-y: auto;">
                    <ul class="mb-0 ps-3">
                        @foreach($results['errors'] as $error)
                            <li class="py-1">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- 4. Project Matching Details Audit Table -->
    @if(!empty($results['project_details']) && count($results['project_details']) > 0)
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-list-check text-primary me-2"></i> تفاصيل مطابقة المشاريع وسجل التدقيق (Audit Log)
                </h5>
                <div class="text-muted small">
                    عرض كافة المشاريع وحالات مطابقتها بدقة
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">اسم المشروع في الملف (Excel)</th>
                                <th style="width: 25%;">المشروع المطابق في النظام (Database)</th>
                                <th style="width: 15%; text-align: center;">نوع المطابقة (Match Type)</th>
                                <th style="width: 10%; text-align: center;">نسبة الدقة</th>
                                <th style="width: 25%;">التشخيص والتفاصيل (Diagnostics)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['project_details'] as $item)
                                <tr>
                                    <!-- Excel Project Name -->
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item['excel_project_name'] }}</div>
                                        <div class="small text-muted">
                                            <i class="far fa-file-alt me-1"></i> عدد الصفوف: {{ $item['rows_count'] ?? 1 }}
                                        </div>
                                    </td>

                                    <!-- Matched Database Project -->
                                    <td>
                                        @if(!empty($item['matched_project_name']))
                                            <div class="fw-bold text-primary">{{ $item['matched_project_name'] }}</div>
                                            <div class="small text-muted">
                                                <i class="fas fa-hashtag me-1"></i> المعرّف: #{{ $item['matched_project_id'] }}
                                            </div>
                                        @else
                                            <span class="text-muted fst-italic">-- لم يتم ربط مشروع --</span>
                                        @endif
                                    </td>

                                    <!-- Match Status Badge -->
                                    <td class="text-center">
                                        @if($item['status'] === 'matched_exact')
                                            <span class="badge bg-success px-3 py-2 rounded-pill">
                                                <i class="fas fa-bullseye me-1"></i> مطابقة تامة
                                            </span>
                                        @elseif($item['status'] === 'matched_normalized')
                                            <span class="badge bg-primary px-3 py-2 rounded-pill">
                                                <i class="fas fa-spell-check me-1"></i> بعد توحيد الاسم
                                            </span>
                                        @elseif($item['status'] === 'matched_fuzzy')
                                            <span class="badge px-3 py-2 rounded-pill text-white" style="background-color: #4f46e5;">
                                                <i class="fas fa-project-diagram me-1"></i> مطابقة ذكية تقريبية
                                            </span>
                                        @elseif($item['status'] === 'needs_review')
                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                                <i class="fas fa-user-clock me-1"></i> يحتاج مراجعة
                                            </span>
                                        @elseif($item['status'] === 'ambiguous')
                                            <span class="badge bg-danger px-3 py-2 rounded-pill">
                                                <i class="fas fa-question-circle me-1"></i> مطابقة غامضة
                                            </span>
                                        @else
                                            <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                                <i class="fas fa-times-circle me-1"></i> لم يتم العثور عليه
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Similarity Score -->
                                    <td class="text-center">
                                        @if($item['status'] === 'matched_fuzzy' || ($item['status'] === 'needs_review' && !empty($item['score'])))
                                            <span class="fw-bold text-dark">{{ number_format(($item['score'] ?? 0) * 100, 1) }}%</span>
                                        @elseif($item['status'] === 'matched_exact' || $item['status'] === 'matched_normalized')
                                            <span class="text-success fw-bold">100%</span>
                                        @else
                                            <span class="text-muted">--</span>
                                        @endif
                                    </td>

                                    <!-- Diagnostics / Reason / Candidates -->
                                    <td>
                                        <div class="small text-muted mb-1">{{ $item['reason'] ?? '' }}</div>
                                        
                                        @if(!empty($item['candidates']) && count($item['candidates']) > 0)
                                            <div class="p-2 bg-light rounded border mt-1" style="font-size: 0.8rem;">
                                                <div class="fw-semibold text-secondary mb-1"><i class="fas fa-list-ol me-1"></i> أقرب المشاريع المرشحة:</div>
                                                <ul class="mb-0 ps-3">
                                                    @foreach($item['candidates'] as $cand)
                                                        <li>
                                                            <strong>{{ $cand['project_name'] ?? '' }}</strong>
                                                            @if(isset($cand['score']))
                                                                <span class="badge bg-light text-dark border ms-1">{{ number_format($cand['score'] * 100, 1) }}%</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        @if(!empty($item['import_status']) && $item['import_status'] === 'imported')
                                            <div class="text-success small fw-semibold mt-1">
                                                <i class="fas fa-check-circle me-1"></i> تم استيراد {{ $item['financings_count'] ?? 0 }} تمويل بقيمة {{ number_format($item['total_amount'] ?? 0, 2) }} ريال
                                            </div>
                                        @elseif(!empty($item['import_status']) && $item['import_status'] === 'skipped')
                                            <div class="text-danger small fw-semibold mt-1">
                                                <i class="fas fa-ban me-1"></i> تم تخطي استيراد تمويلات هذا المشروع
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="d-flex justify-content-center gap-3 mt-4 mb-3">
        <a href="{{ route('projects.financings.import.form') }}" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
            <i class="fas fa-file-upload me-2"></i> استيراد ملف تمويل جديد
        </a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
            <i class="fas fa-arrow-right me-2"></i> الذهاب إلى قائمة المشاريع
        </a>
    </div>
</div>
@endsection

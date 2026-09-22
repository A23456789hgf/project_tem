@extends('layouts.app')

@section('styles')
<style>
    /* =========================================
       1. إعدادات الصفحة الأساسية (Global Settings)
       ========================================= */
    html, body {
        height: 100%;
        margin: 0;
        background-color: #f4f6f9;
        font-family: 'Cairo', system-ui, -apple-system, sans-serif;
    }

    /* Header Banner */
    .complete-data-header {
        background: linear-gradient(135deg, #1e3a5f 0%, #2c5f8a 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(30, 58, 95, 0.15);
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .complete-data-header::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    /* =========================================
       2. منطقة المحتوى (Scrollable Content Area)
       ========================================= */
    .content-scrollable-area {
        padding: 1.5rem;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        margin-bottom: 2rem;
    }

    /* =========================================
       3. تنسيق النماذج والأقسام (Forms & Sections)
       ========================================= */
    .form-section {
        background: #fff;
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: #1e293b;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }
    .section-title i {
        color: #3b82f6;
        margin-inline-end: 8px;
    }

    .form-control, .form-select {
        border: 1px solid #e2e8f0;
        padding: 0.6rem 0.8rem;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.2s;
        background-color: #f8fafc;
    }

    .form-control:focus, .form-select:focus {
        background-color: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .form-grid .form-group {
        margin-bottom: 0;
    }

    textarea.auto-expand {
        resize: none;
        overflow: hidden;
        min-height: 38px;
        line-height: 1.5;
        transition: height 0.2s ease;
    }

    /* =========================================
       4. الجداول المخصصة (Custom Tables)
       ========================================= */
    .project-table-container {
        margin-bottom: 1.5rem;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .project-table-header {
        padding: 15px 20px;
        border-radius: 10px 10px 0 0;
        background: #f8fafc;
        color: #1e40af;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .project-table-header i {
        font-size: 1.2rem;
        color: #1d4ed8;
    }

    .project-table-wrapper {
        border: 1px solid #e2e8f0;
        border-radius: 0 0 10px 10px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 15px;
        padding: 15px;
        background: #f8fbff;
    }

    .project-table-wrapper .table-responsive,
    .project-table-wrapper .project-table-inner,
    .table-responsive {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        box-shadow: none;
    }

    .project-table-wrapper table,
    table.project-table,
    .table-responsive table {
        margin-bottom: 0;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .project-table-wrapper th,
    table.project-table th,
    .table-responsive th {
        background: #f8fafc;
        color: #1e40af;
        font-weight: 700;
        padding: 12px 15px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .project-table-wrapper td,
    table.project-table td,
    .table-responsive td {
        padding: 10px 15px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        color: #1e293b;
        background: #ffffff;
        transition: background-color 0.2s ease;
        font-size: 0.85rem;
    }

    .project-table-wrapper tbody tr:nth-child(even) td,
    table.project-table tbody tr:nth-child(even) td,
    .table-responsive tbody tr:nth-child(even) td {
        background: #f8fafc;
    }
    .project-table-wrapper tbody tr:hover td,
    table.project-table tbody tr:hover td,
    .table-responsive tbody tr:hover td {
        background-color: rgba(191, 219, 254, 0.15);
    }

    .project-table-wrapper .form-control,
    .project-table-wrapper .form-select,
    table.project-table .form-control,
    table.project-table .form-select,
    .table-responsive .form-control,
    .table-responsive .form-select {
        border-radius: 6px;
        border: 1px solid rgba(148, 163, 184, 0.3);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: rgba(255, 255, 255, 0.95);
        font-size: 0.8rem;
        padding: 5px 10px;
        height: calc(1.5em + 0.5rem + 2px);
    }

    .project-table-wrapper .form-control:focus,
    .project-table-wrapper .form-select:focus,
    table.project-table .form-control:focus,
    table.project-table .form-select:focus,
    .table-responsive .form-control:focus,
    .table-responsive .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.15rem rgba(59, 130, 246, 0.1);
        background-color: #ffffff;
    }

    /* Buttons */
    .project-btn,
    .project-table-wrapper .project-btn {
        border-radius: 8px;
        border: none;
        font-weight: 600;
        padding: 6px 12px;
        font-size: 0.8rem;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .project-btn:hover,
    .project-table-wrapper .project-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(59, 130, 246, 0.25);
    }

    .project-btn-primary {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        color: #ffffff;
    }
    .project-btn-danger {
        background: linear-gradient(135deg, #fda4af 0%, #f87171 100%);
        color: #ffffff;
    }
    .project-btn-success {
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        color: #ffffff;
    }
    .project-btn-secondary {
        background: #64748b;
        color: #ffffff;
    }
    
    .project-btn i {
        margin-inline-end: 4px;
        font-size: 0.8rem;
    }

    .project-empty-row td {
        background: #ffffff !important;
        color: #64748b;
        font-size: 0.85rem;
    }
    .project-empty-row i {
        color: rgba(100, 116, 139, 0.6);
    }
    .project-action-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
        justify-content: center;
    }

    .project-section-heading {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1rem;
    }
    .project-section-heading i {
        color: #2563eb;
        font-size: 1.1rem;
    }

    .project-info-text {
        background: linear-gradient(135deg, rgba(191, 219, 254, 0.15) 0%, rgba(226, 232, 240, 0.15) 100%);
        color: #1e293b;
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        margin-bottom: 1rem;
        font-size: 0.85rem;
    }

    /* Select2 Customization */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #e2e8f0 !important;
        min-height: 38px !important;
        font-size: 0.85rem !important;
        border-radius: 8px !important;
        background-color: #f8fafc !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-right: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }

    /* =========================================
       5. التذييل الثابت (Action Footer)
       ========================================= */
    .action-footer {
        background: #fff;
        padding: 1.25rem 1.5rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        z-index: 100;
        box-shadow: 0 -10px 25px rgba(0,0,0,0.05);
        position: sticky;
        bottom: 0;
        border-radius: 12px;
    }

    .btn-navigation {
        min-width: 130px;
        padding: 10px 20px;
        font-weight: 600;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .content-scrollable-area { padding: 1rem; }
        .action-footer { padding: 10px; flex-direction: column-reverse; gap: 15px; }
        .action-footer .d-flex { width: 100%; justify-content: space-between; }
        .btn-navigation { min-width: 100px; padding: 8px 15px; font-size: 0.85rem; }
        .form-grid { grid-template-columns: 1fr; gap: 0.75rem; }
        .project-table-wrapper { padding: 10px; }
        .project-table-wrapper th, .project-table-wrapper td { padding: 8px 10px; font-size: 0.8rem; }
        .section-title { font-size: 1rem; margin-bottom: 12px; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-3">
    {{-- Header Banner --}}
    <div class="complete-data-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-list-check"></i>
                استكمال بيانات المشروع
            </h2>
            <p class="mb-0 text-white-50">
                مشروع: <span class="text-white fw-bold">{{ $project->project_name }}</span>
                @if($project->project_type === 'old')
                    <span class="badge bg-warning text-dark ms-2"><i class="fas fa-history me-1"></i>مشروع قديم</span>
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-light fw-bold px-4 rounded-3 shadow-sm">
                <i class="fas fa-arrow-right me-2"></i> عودة لبيانات المشروع
            </a>
        </div>
    </div>

    {{-- إشعار اكتمال البيانات (للمشاريع القديمة المكتملة بالفعل) --}}
    @if($project->project_type === 'old' && $project->is_data_completed)
    <div class="alert border-0 rounded-4 p-4 mb-4" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-right: 5px solid #16a34a !important;">
        <div class="d-flex align-items-start gap-3">
            <div class="flex-shrink-0">
                <i class="fas fa-lock fa-2x" style="color: #16a34a;"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1" style="color: #15803d;"><i class="fas fa-check-circle me-2"></i>تم استكمال بيانات المشروع</h5>
                <p class="mb-1 text-secondary">تم استكمال البيانات بتاريخ
                    <strong>{{ $project->data_completed_at ? $project->data_completed_at->format('Y-m-d H:i') : 'غير محدد' }}</strong>.
                </p>
                <p class="mb-0 text-danger fw-semibold">
                    <i class="fas fa-ban me-1"></i>لا يمكن تعديل هذه البيانات مجدداً. يمكنك الآن إضافة الإنجازات.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Form (only for projects where data is NOT yet completed) --}}
    @if(!($project->project_type === 'old' && $project->is_data_completed))
    <div class="content-scrollable-area" id="mainScrollContainer">
        
        <!-- Flash Messages Container -->
        <div id="flash-messages-container"></div>

        {{-- تحذير مهم للمشاريع القديمة --}}
        @if($project->project_type === 'old')
        <div class="alert border-0 rounded-3 p-3 mb-4 d-flex align-items-start gap-3" style="background: #fffbeb; border-right: 4px solid #f59e0b !important;">
            <i class="fas fa-exclamation-triangle fa-lg mt-1" style="color: #d97706;"></i>
            <div>
                <strong style="color: #92400e;">تنبيه: استكمال البيانات مرة واحدة فقط!</strong>
                <p class="mb-0 small text-secondary mt-1">هذا النموذج متاح مرة واحدة فقط للمشاريع القديمة. بعد الحفظ، لن تتمكن من تعديل هذه البيانات مجدداً وسيصبح زر "إضافة إنجاز" متاحاً.</p>
            </div>
        </div>
        @endif

        @can('projects.edit')
        <form action="{{ route('projects.update', $project->id) }}" method="POST" id="completeDataForm">
        @endcan
            @csrf
            @method('PUT')

            {{-- Hidden Required Fields to ensure safe update --}}
            <input type="hidden" name="project_name" value="{{ $project->project_name }}">
            <input type="hidden" name="status" value="{{ $project->status === 'final' ? 'final' : 'draft' }}">
            <input type="hidden" name="program_id" value="{{ $project->program_id }}">
            <input type="hidden" name="project_type" value="{{ $project->project_type }}">
            <input type="hidden" name="redirect_to" value="{{ route('projects.show', $project->id) }}">

            {{-- 1. عرض البيانات الأساسية والتفاصيل المضافة مسبقاً (للاطلاع والمراجعة) --}}
            <div class="project-table-container mb-4">
                <div class="project-table-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        <span>البيانات الأساسية والتفاصيل المضافة مسبقاً (للاطلاع والمراجعة)</span>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#previousDataCollapse" aria-expanded="true">
                        <i class="fas fa-eye me-1"></i> إظهار / إخفاء البيانات السابقة
                    </button>
                </div>
                <div class="collapse show" id="previousDataCollapse">
                    <div class="p-4 bg-light bg-opacity-50">
                        {{-- Financial Summary Cards --}}
                        @php
                            $totalCost = $project->cost->total_cost ?? 0;
                            $spentAmount = $project->cost->spent_amount ?? 0;
                            $remainingAmount = $project->cost->remaining_amount ?? 0;
                        @endphp
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded-3 shadow-sm border border-start border-4 border-primary">
                                    <div class="text-muted small mb-1"><i class="fas fa-coins text-primary me-1"></i> إجمالي التكلفة المعتمدة</div>
                                    <div class="fs-5 fw-bold text-dark">{{ number_format($totalCost, 2) }} <small class="fs-6 fw-normal text-muted">ريال</small></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded-3 shadow-sm border border-start border-4 border-warning">
                                    <div class="text-muted small mb-1"><i class="fas fa-hand-holding-usd text-warning me-1"></i> المبلغ المصروف الفعلي</div>
                                    <div class="fs-5 fw-bold text-dark">{{ number_format($spentAmount, 2) }} <small class="fs-6 fw-normal text-muted">ريال</small></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded-3 shadow-sm border border-start border-4 border-success">
                                    <div class="text-muted small mb-1"><i class="fas fa-wallet text-success me-1"></i> المبلغ المتبقي</div>
                                    <div class="fs-5 fw-bold text-dark">{{ number_format($remainingAmount, 2) }} <small class="fs-6 fw-normal text-muted">ريال</small></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            {{-- البيانات الأساسية والتصنيف --}}
                            <div class="col-lg-6">
                                <div class="card h-100 border-0 shadow-sm rounded-3">
                                    <div class="card-header bg-white py-3 border-bottom">
                                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-list-alt text-primary me-2"></i> البيانات الأساسية والتصنيف</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-3 small">
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">اسم المشروع:</span>
                                                <strong class="text-dark">{{ $project->project_name }}</strong>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">البرنامج التابع له:</span>
                                                <strong class="text-primary">{{ $project->program->name ?? 'غير محدد' }}</strong>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">أولوية المشروع:</span>
                                                @if($project->priority)
                                                    <span class="badge bg-info text-dark">{{ $project->priority->name }}</span>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">ضمن خطة معتمدة؟</span>
                                                @if(($project->detail->is_part_of_plan ?? null) === 1 || ($project->detail->is_part_of_plan ?? null) === true)
                                                    <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> نعم</span>
                                                @else
                                                    <span class="text-muted">مستقل / غير محدد</span>
                                                @endif
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">تاريخ البداية (ميلادي):</span>
                                                <strong class="text-dark">{{ $project->start_date_gregorian ? \Carbon\Carbon::parse($project->start_date_gregorian)->format('Y-m-d') : 'غير محدد' }}</strong>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">تاريخ الانتهاء (ميلادي):</span>
                                                <strong class="text-dark">{{ $project->end_date_gregorian ? \Carbon\Carbon::parse($project->end_date_gregorian)->format('Y-m-d') : 'غير محدد' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- المستفيدون والتفاصيل --}}
                            <div class="col-lg-6">
                                <div class="card h-100 border-0 shadow-sm rounded-3">
                                    <div class="card-header bg-white py-3 border-bottom">
                                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-users text-success me-2"></i> المستفيدون والتفاصيل</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-3 small">
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">إجمالي المستفيدين:</span>
                                                <strong class="text-success fs-6">{{ $project->number_of_beneficiaries ? number_format($project->number_of_beneficiaries) : '0' }} <small>مستفيد</small></strong>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">الفئة المستهدفة الرئيسية:</span>
                                                <strong class="text-dark">{{ $project->targetCategory->name ?? 'غير محدد' }}</strong>
                                            </div>
                                            @if($project->detail && $project->detail->project_summary)
                                            <div class="col-12">
                                                <span class="text-muted d-block">ملخص المشروع:</span>
                                                <div class="p-2 bg-light rounded text-secondary mt-1" style="max-height: 80px; overflow-y: auto;">
                                                    {{ $project->detail->project_summary }}
                                                </div>
                                            </div>
                                            @endif
                                            @if($project->main_directives || $project->subdirectives)
                                            <div class="col-12">
                                                <span class="text-muted d-block">التوجيهات والملاحظات:</span>
                                                <div class="p-2 bg-light rounded text-secondary mt-1">
                                                    {{ $project->main_directives }} {{ $project->subdirectives ? ' - ' . $project->subdirectives : '' }}
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. المجال الرئيسي والمجال الفرعي والتدخل --}}
            <div class="form-section mb-4">
                <h4 class="section-title">
                    <i class="fas fa-layer-group"></i> المجال والتدخل في المشروع
                </h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">المجال الرئيسي <span class="text-danger">*</span></label>
                        <select name="domain_id" id="domain_id" class="form-select select-search" 
                                data-ajax-url="{{ route('lookup.search', ['include_pending' => 1]) }}" data-ajax-type="domain">
                            <option value="">اختر المجال</option>
                            @php 
                                $selectedDomainId = old('domain_id', $project->domain_id ?? '');
                                $initialDomains = $domains->take(10);
                                if ($selectedDomainId && !$initialDomains->contains('id', $selectedDomainId)) {
                                    $selectedDomain = $domains->firstWhere('id', $selectedDomainId);
                                    if ($selectedDomain) $initialDomains->push($selectedDomain);
                                }
                            @endphp
                            @foreach($initialDomains as $domain)
                                <option value="{{ $domain->id }}" {{ $selectedDomainId == $domain->id ? 'selected' : '' }}>
                                    {{ $domain->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">المجال الفرعي <span class="text-danger">*</span></label>
                        <select name="subdomain_id" id="subdomain_id" class="form-select select-search" 
                                data-ajax-url="{{ route('lookup.search', ['include_pending' => 1]) }}" data-ajax-type="subdomain" 
                                data-ajax-params="domain_id=#domain_id">
                            <option value="">اختر المجال الفرعي</option>
                            @php 
                                $selectedSubdomainId = old('subdomain_id', $project->subdomain_id ?? '');
                                $initialSubdomains = $subdomains->take(10);
                                if ($selectedSubdomainId && !$initialSubdomains->contains('id', $selectedSubdomainId)) {
                                    $selectedSubdomain = $subdomains->firstWhere('id', $selectedSubdomainId);
                                    if ($selectedSubdomain) $initialSubdomains->push($selectedSubdomain);
                                }
                            @endphp
                            @foreach($initialSubdomains as $subdomain)
                                <option value="{{ $subdomain->id }}" {{ $selectedSubdomainId == $subdomain->id ? 'selected' : '' }}>
                                    {{ $subdomain->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">نوع التدخل <span class="text-danger">*</span></label>
                        <select name="intervention_id" id="intervention_id" class="form-select select-search" 
                                data-ajax-url="{{ route('lookup.search', ['include_pending' => 1]) }}" data-ajax-type="intervention" 
                                data-ajax-params="subdomain_id=#subdomain_id">
                            <option value="">اختر نوع التدخل</option>
                            @php 
                                $selectedInterventionId = old('intervention_id', $project->intervention_id ?? '');
                                $initialInterventions = $interventions->take(10);
                                if ($selectedInterventionId && !$initialInterventions->contains('id', $selectedInterventionId)) {
                                    $selectedIntervention = $interventions->firstWhere('id', $selectedInterventionId);
                                    if ($selectedIntervention) $initialInterventions->push($selectedIntervention);
                                }
                            @endphp
                            @foreach($initialInterventions as $intervention)
                                <option value="{{ $intervention->id }}" {{ $selectedInterventionId == $intervention->id ? 'selected' : '' }}>
                                    {{ $intervention->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- 3. مواقع المشروع --}}
            <div class="form-section mb-4">
                @include('projects.partials.tables.project_location')
            </div>

            {{-- 4. الجهات المعنية بالمشروع --}}
            <div class="form-section mb-4">
                <h4 class="section-title"><i class="fas fa-building me-2"></i>الجهات</h4>
                @include('projects.partials.tables.supervising_authorities')
                @include('projects.partials.tables.implementing-entities')
                @include('projects.partials.tables.participating-entities')
                @include('projects.partials.tables.beneficiary-entities')
                @include('projects.partials.tables.project_entities')
            </div>

            {{-- Action Footer --}}
            <footer class="action-footer rounded-3 shadow-sm border mt-4">
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1 text-primary"></i> يتم حفظ كافة التعديلات في المجالات والمواقع والجهات مباشرة في المشروع.
                    @if($project->project_type === 'old')
                    <span class="text-danger fw-semibold d-block mt-1">
                        <i class="fas fa-lock me-1"></i> تحذير: بعد الحفظ لن تتمكن من تعديل هذه البيانات مجدداً.
                    </span>
                    @endif
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-secondary btn-navigation">
                        إلغاء والعودة
                    </a>
                    @if($project->project_type === 'old')
                    <button type="button" class="btn btn-success btn-navigation px-4 fw-bold" id="finalSaveBtn"
                        onclick="confirmCompleteData(event)">
                        <i class="fas fa-save me-2"></i> حفظ واستكمال البيانات نهائياً
                    </button>
                    @else
                    <button type="submit" class="btn btn-primary btn-navigation px-4 fw-bold">
                        <i class="fas fa-save me-2"></i> حفظ كبيانات المشروع
                    </button>
                    @endif
                </div>
            </footer>
        </form>
    </div>
    @else
    {{-- رسالة للمستخدم عند محاولة الوصول للنموذج بعد الإقفال --}}
    <div class="text-center py-5 bg-white rounded-3 shadow-sm p-5">
        <i class="fas fa-lock fa-4x text-secondary mb-3 d-block"></i>
        <h4 class="fw-bold text-dark mb-2">البيانات مقفلة ونهائية</h4>
        <p class="text-muted mb-4">تم استكمال بيانات هذا المشروع القديم ولا يمكن تعديلها مجدداً.<br>يمكنك الآن إضافة الإنجازات من صفحة المشروع.</p>
        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-primary fw-bold px-5 rounded-3">
            <i class="fas fa-arrow-right me-2"></i> العودة لصفحة المشروع
        </a>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // SweetAlert2 Confirmation matching navbar style
    function confirmCompleteData(event) {
        if (event) event.preventDefault();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'تأكيد استكمال بيانات المشروع',
                text: 'تنبيه: سيتم قفل هذه البيانات نهائياً بعد الحفظ ولن تتمكن من تعديلها مجدداً. هل أنت متأكد من الاستكمال النهائي؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save me-1"></i> نعم، حفظ واستكمال نهائي',
                cancelButtonText: '<i class="fas fa-times me-1"></i> إلغاء',
                customClass: {
                    confirmButton: 'btn btn-success px-4 fw-bold',
                    cancelButton: 'btn btn-light text-dark px-4 border'
                },
                buttonsStyling: true,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('completeDataForm').submit();
                }
            });
        } else {
            if (confirm('تنبيه: سيتم قفل هذه البيانات نهائياً بعد الحفظ ولن تتمكن من تعديلها مجدداً.\n\nهل أنت متأكد من الاستكمال النهائي؟')) {
                document.getElementById('completeDataForm').submit();
            }
        }
    }
</script>

<script>
    // Auto-expand textarea script
    document.addEventListener('input', function (event) {
        if (event.target.tagName.toLowerCase() !== 'textarea' || !event.target.classList.contains('auto-expand')) return;
        autoExpand(event.target);
    });

    function autoExpand(field) {
        field.style.height = 'inherit';
        var computed = window.getComputedStyle(field);
        var height = parseInt(computed.getPropertyValue('border-top-width'), 10)
                     + parseInt(computed.getPropertyValue('padding-top'), 10)
                     + field.scrollHeight
                     + parseInt(computed.getPropertyValue('padding-bottom'), 10)
                     + parseInt(computed.getPropertyValue('border-bottom-width'), 10);
        field.style.height = height + 'px';
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('textarea.auto-expand').forEach(function(textarea) {
            autoExpand(textarea);
        });
    });
</script>

<script>
$(document).ready(function() {
    let cascadeInitializing = true;
    setTimeout(function () {
        cascadeInitializing = false;
    }, 800);

    // Domain → Subdomain reset (user interaction only)
    $('#domain_id').on('change', function () {
        if (cascadeInitializing) return;
        $('#subdomain_id').val(null).trigger('change');
    });

    // Subdomain → Intervention reset (user interaction only)
    $('#subdomain_id').on('change', function () {
        if (cascadeInitializing) return;
        $('#intervention_id').val(null).trigger('change');
    });
});
</script>
@endsection

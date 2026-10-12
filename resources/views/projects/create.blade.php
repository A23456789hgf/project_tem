@extends('layouts.app')

@section('styles')
<style>
    /* =========================================
       1. إعدادات الصفحة الأساسية (Global Settings)
       ========================================= */
    html, body {
        height: 100%;
        margin: 0;
        overflow-y: auto;
        background-color: #f4f6f9;
        font-family: 'Cairo', system-ui, -apple-system, sans-serif;
    }

    .app-layout-wrapper {
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 160px);
        width: 100%;
    }

    /* =========================================
       2. منطقة المحتوى القابلة للتمرير (Scrollable Content)
       ========================================= */
    .content-scrollable-area {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        scroll-behavior: smooth;
        background-color: #fff;
        border-radius: 12px 12px 0 0;
        margin: 0 1rem;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.03);
    }

    /* تحسين شكل شريط التمرير */
    .content-scrollable-area::-webkit-scrollbar { width: 6px; }
    .content-scrollable-area::-webkit-scrollbar-track { background: transparent; }
    .content-scrollable-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .content-scrollable-area::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* =========================================
       3. شريط الخطوات (Step Indicator)
       ========================================= */
    .steps-container {
        padding: 1rem 1rem 0 1rem;
        background-color: #f4f6f9;
        z-index: 10;
        flex-shrink: 0;
    }

    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 15px;
        padding-bottom: 15px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .step-indicator::-webkit-scrollbar { display: none; }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px 15px;
        min-width: 100px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        user-select: none;
    }

    .step-item.active {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        transform: translateY(-2px);
    }

    .step-item.completed {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #bbf7d0;
    }
    
    .step-item.completed .step-icon { color: #16a34a; }
    .step-icon { font-size: 1.2rem; margin-bottom: 5px; }
    .step-text { font-size: 0.8rem; font-weight: 700; white-space: nowrap; }

    /* =========================================
       4. تنسيق النماذج والأقسام (Forms & Sections)
       ========================================= */
    .step {
        display: none;
        animation: slideIn 0.3s ease-out;
        margin-bottom: 1.5rem;
    }
    .step.active { display: block; }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-section { background: #fff; margin-bottom: 2rem; }

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
    .section-title i { color: #3b82f6; margin-inline-end: 8px; }

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

    .form-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px; }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .form-grid .form-group { margin-bottom: 0; }

    textarea.auto-expand {
        resize: none;
        overflow: hidden;
        min-height: 38px;
        line-height: 1.5;
        transition: height 0.2s ease;
    }

    /* =========================================
       5. الجداول المخصصة (Custom Tables)
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
    .project-table-header i { font-size: 1.2rem; color: #1d4ed8; }

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
    .project-table-wrapper .project-table-inner {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        box-shadow: none;
    }

    .project-table-wrapper table {
        margin-bottom: 0;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .project-table-wrapper th {
        background: #f8fafc;
        color: #1e40af;
        font-weight: 700;
        padding: 12px 15px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .project-table-wrapper td {
        padding: 10px 15px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        color: #1e293b;
        background: #ffffff;
        transition: background-color 0.2s ease;
        font-size: 0.85rem;
    }

    .project-table-wrapper tbody tr:nth-child(even) td { background: #f8fafc; }
    .project-table-wrapper tbody tr:hover td { background-color: rgba(191, 219, 254, 0.15); }

    .project-table-wrapper .form-control,
    .project-table-wrapper .form-select {
        border-radius: 6px;
        border: 1px solid rgba(148, 163, 184, 0.3);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: rgba(255, 255, 255, 0.95);
        font-size: 0.8rem;
        padding: 5px 10px;
        height: calc(1.5em + 0.5rem + 2px);
    }

    .project-table-wrapper .form-control:focus,
    .project-table-wrapper .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.15rem rgba(59, 130, 246, 0.1);
        background-color: #ffffff;
    }

    .project-table-wrapper .project-btn {
        border-radius: 8px;
        border: none;
        font-weight: 600;
        padding: 6px 12px;
        font-size: 0.8rem;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .project-table-wrapper .project-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(59, 130, 246, 0.25);
    }

    .project-table-wrapper .project-btn-primary { background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); color: #ffffff; }
    .project-table-wrapper .project-btn-danger { background: linear-gradient(135deg, #fda4af 0%, #f87171 100%); color: #ffffff; }
    
    .project-table-wrapper .project-btn-primary i,
    .project-table-wrapper .project-btn-danger i { margin-inline-end: 4px; font-size: 0.8rem; }

    .project-table-wrapper .badge {
        border-radius: 999px;
        font-size: 0.75rem;
        padding: 5px 10px;
        background: rgba(59, 130, 246, 0.1);
        color: #1d4ed8;
    }

    .project-table-wrapper .project-empty-row td { background: #ffffff; color: #64748b; font-size: 0.85rem; }
    .project-table-wrapper .project-empty-row i { color: rgba(100, 116, 139, 0.6); }
    .project-table-wrapper .project-action-buttons { display: flex; align-items: center; gap: 6px; justify-content: center; }

    .project-section-heading {
        font-weight: 700; color: #0f172a; margin-bottom: 1rem;
        display: flex; align-items: center; gap: 10px; font-size: 1.1rem;
    }
    .project-section-heading i { color: #2563eb; font-size: 1.1rem; }

    .project-info-text {
        background: linear-gradient(135deg, rgba(191, 219, 254, 0.15) 0%, rgba(226, 232, 240, 0.15) 100%);
        color: #1e293b; padding: 12px 15px; border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.2); margin-bottom: 1rem; font-size: 0.85rem;
    }

    /* Compact table styles */
    .compact-table { font-size: 0.875rem; }
    .compact-table th { padding: 0.5rem !important; font-size: 0.8rem; }
    .compact-table td { padding: 0.5rem !important; }
    .compact-table .form-control, .compact-table .form-select {
        height: calc(1.5em + 0.5rem + 2px); padding: 0.25rem 0.5rem; font-size: 0.8125rem;
    }

    /* Column Width Helpers */
    .col-min-150 { min-width: 150px; } .col-min-200 { min-width: 200px; }
    .col-min-250 { min-width: 250px; } .col-min-300 { min-width: 300px; } .col-min-400 { min-width: 400px; }
    
    .w-5  { width: 5%; }  .w-10 { width: 10%; } .w-15 { width: 15%; }
    .w-20 { width: 20%; } .w-25 { width: 25%; } .w-30 { width: 30%; } .w-35 { width: 35%; }

    /* =========================================
       6. التذييل وشريط التقدم (Footer & Progress)
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
    }

    .progress-wrapper {
        flex: 1;
        margin: 0 2rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .btn-navigation {
        min-width: 130px;
        padding: 10px 20px;
        font-weight: 600;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .save-indicator {
        font-size: 0.78rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        white-space: nowrap;
        transition: color 0.3s ease;
        padding: 0 0.5rem;
    }
    .save-indicator i { font-size: 0.85rem; }

    /* =========================================
       7. التجاوب مع الشاشات المختلفة (Responsive)
       ========================================= */
    @media (max-width: 768px) {
        .content-scrollable-area { padding: 1rem; margin: 0; border-radius: 0; }
        .step-indicator { justify-content: flex-start; padding-bottom: 10px; }
        .step-item { min-width: 85px; padding: 8px; }
        .step-text { font-size: 0.7rem; }
        .step-icon { font-size: 1rem; }
        .action-footer { padding: 10px; flex-direction: column-reverse; gap: 15px; }
        .action-footer .d-flex { width: 100%; justify-content: space-between; }
        .progress-wrapper { margin: 0; width: 100%; margin-bottom: 5px; }
        .btn-navigation { min-width: 100px; padding: 8px 15px; font-size: 0.85rem; }
        .form-grid { grid-template-columns: 1fr; gap: 0.75rem; }
        .project-table-wrapper { padding: 10px; }
        .project-table-wrapper th, .project-table-wrapper td { padding: 8px 10px; font-size: 0.8rem; }
        .section-title { font-size: 1rem; margin-bottom: 12px; }
    }

    @media (max-width: 576px) {
        .step-item { min-width: 80px; padding: 8px 10px; }
        .step-icon { font-size: 1rem; margin-inline-end: 4px; }
        .step-text { font-size: 0.75rem; }
        .btn-navigation { min-width: 90px; padding: 6px 12px; font-size: 0.8rem; }
        .action-footer .d-flex { flex-direction: column; gap: 10px; }
        .action-footer .d-flex > div { width: 100%; display: flex; justify-content: center; }
    }
</style>
@endsection

@section('content')
<div class="app-layout-wrapper">
    
    <!-- =========================================
         شريط الخطوات (Steps Indicator)
         ========================================= -->
    <div class="steps-container">
        @php $initialStep = isset($lastSavedStep) ? (int)$lastSavedStep : (isset($project) && $project->last_saved_step ? (int)$project->last_saved_step : 1); @endphp
        <nav class="step-indicator">
            @foreach([
                ['icon' => 'fa-info-circle',       'text' => 'بيانات المشروع'],
                ['icon' => 'fa-clipboard-list',    'text' => 'تفاصيل المشروع'],
                ['icon' => 'fa-shield-alt',        'text' => 'المخاطر والجهات'],
                ['icon' => 'fa-play',              'text' => 'الانشطة التمهيدية'],
                ['icon' => 'fa-tasks',             'text' => 'الانشطة التنفيذية'],
                ['icon' => 'fa-coins',             'text' => 'التمويلات والتكلفة'],
                ['icon' => 'fa-check-double',      'text' => 'المراجعة']
            ] as $index => $step)
                <div class="step-item {{ $initialStep == ($index + 1) ? 'active' : ($initialStep > ($index + 1) ? 'completed' : '') }}" 
                     onclick="FormManager.goToStep({{ $index + 1 }})"
                     data-step="{{ $index + 1 }}">
                    <i class="fas {{ $step['icon'] }} step-icon"></i>
                    <span class="step-text">{{ $step['text'] }}</span>
                </div>
            @endforeach
        </nav>
    </div>

    <!-- =========================================
         منطقة المحتوى الرئيسية (Main Content Area)
         ========================================= -->
    <div class="content-scrollable-area" id="mainScrollContainer">
        
        <!-- حاوية رسائل التنبيهات (Flash Sessions) -->
        <div id="flash-messages-container"></div>

        <!-- Project Draft Status Alert -->
        {{-- ========================================================
             لوحة حالة المسودة الديناميكية (تُحدَّث تلقائياً عند تحميل الصفحة)
             ======================================================== --}}
        <div id="draftStatusPanel" class="mb-4">

            {{-- شريط التقدم --}}
            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:.82rem;">
                <span class="fw-bold text-secondary"><i class="fas fa-tasks me-1"></i> اكتمال المسودة</span>
                <span id="draftPctLabel" class="fw-bold text-secondary">جارٍ التحقق...</span>
            </div>
            <div class="progress mb-3" style="height:8px;border-radius:8px;">
                <div id="draftProgressBar"
                     class="progress-bar"
                     role="progressbar"
                     style="width:0%;border-radius:8px;transition:width .6s ease;"
                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>

            {{-- بطاقة الحالة الرئيسية --}}
            <div id="completedDraftAlert"
                 class="alert {{ (isset($project) && ($project->status === 'completed_draft' || $project->isDraftComplete())) ? 'alert-success' : 'alert-warning' }} border-0 shadow-sm"
                 style="border-right: 4px solid {{ (isset($project) && ($project->status === 'completed_draft' || $project->isDraftComplete())) ? '#10b981' : '#f59e0b' }} !important;">

                <div class="d-flex align-items-start">
                    <i id="draftAlertIcon"
                       class="fas {{ (isset($project) && ($project->status === 'completed_draft' || $project->isDraftComplete())) ? 'fa-check-double text-success' : 'fa-exclamation-circle text-warning' }} me-3 fa-lg mt-1">
                    </i>
                    <div class="flex-grow-1">
                        <p class="mb-1 fw-bold" id="completedDraftAlertText">
                            @if(isset($project) && ($project->status === 'completed_draft' || $project->isDraftComplete()))
                                المسودة مكتملة – تم تعبئة جميع البيانات المطلوبة بنجاح.
                            @elseif(isset($project))
                                المسودة غير مكتملة – يرجى استكمال الأقسام التالية:
                            @else
                                سيتم تحديث حالة المسودة تلقائياً أثناء حفظ كل خطوة.
                            @endif
                        </p>
                        {{-- قائمة الأقسام الناقصة --}}
                        <ul id="missingSectionsList" class="mb-0 ps-3"
                            style="font-size:.85rem;{{ (isset($project) && ($project->status === 'completed_draft' || $project->isDraftComplete())) ? 'display:none;' : '' }}">
                            @if(isset($project))
                                @foreach($project->getMissingRequiredFields() as $field)
                                    <li>{{ $field }}</li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                    {{-- مؤشر دوّار يظهر أثناء التحقق --}}
                    <span id="draftValidatingSpinner" class="spinner-border spinner-border-sm text-secondary ms-2" role="status" style="display:none;"></span>
                </div>
            </div>
        </div>
        
        <!-- لوحة التشخيص (للمطورين فقط) -->
        <div id="diagnosticPanel" class="alert alert-dark d-none mb-3" style="font-family: monospace; font-size: 0.85rem;">
            <h6><i class="fas fa-microscope me-1"></i>Project Diagnostics (#{{ $project->id ?? 'New' }})</h6>
            <div id="diagnosticContent"></div>
            <hr class="my-2 border-secondary">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info" onclick="runDiagnostics()">
                    <i class="fas fa-sync-alt me-1"></i>Run Diagnostic
                </button>
                <button type="button" class="btn btn-sm btn-info" id="manualFixBtn">
                    <i class="fas fa-magic me-1"></i>Force Initialize Step 3
                </button>
            </div>
        </div> <!-- تم إغلاق الـ div الناقص هنا -->

        @if(isset($draftCollaborators) && $draftCollaborators->count() > 0)
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#collaboratorsModal">
                <i class="fas fa-users me-1"></i> سجل التعديلات والمتعاونين ({{ $draftCollaborators->count() }})
            </button>
        </div>
        @endif

        <!-- النموذج الرئيسي (Main Form) -->
        @can('projects.create')
        <form id="projectForm" action="{{ route('projects.store') }}" method="POST" novalidate>
        @endcan
            @csrf
            <input type="hidden" name="status" value="draft">
            <input type="hidden" id="project_id" name="project_id" value="{{ $project->id ?? '' }}">
            <input type="hidden" id="isDraft" value="{{ (isset($isDraft) && $isDraft) ? 'true' : 'false' }}">
            <input type="hidden" id="lastSavedStep" value="{{ $initialStep }}">

            <section class="step {{ $initialStep == 1 ? 'active' : '' }}" data-step="1">
                @include('projects.partials._basic_info')
            </section>

            <section class="step {{ $initialStep == 2 ? 'active' : '' }}" data-step="2">
                @include('projects.partials._project_details')
                <div class="form-section mt-4">
                    <h4 class="section-title"><i class="fas fa-bullseye me-2"></i>أهداف المشروع</h4>
                    @include('projects.partials._project_objective')
                </div>
            </section>

            <section class="step {{ $initialStep == 3 ? 'active' : '' }}" data-step="3">
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-exclamation-triangle me-2"></i>إدارة المخاطر</h4>
                    @include('projects.partials._risks')
                </div>
                <div class="form-section mt-4">
                    <h4 class="section-title"><i class="fas fa-building me-2"></i>الجهات</h4>
                    @include('projects.partials.tables.supervising_authorities')
                    @include('projects.partials.tables.implementing-entities')
                    @include('projects.partials.tables.participating-entities')
                    <div id="beneficiary-entities-section" style="display: none;">
                        @include('projects.partials.tables.beneficiary-entities')
                    </div>
                    @include('projects.partials.tables.entity-selector-script')
                    @include('projects.partials.tables.project_entities')
                </div>
            </section>

            <section class="step {{ $initialStep == 4 ? 'active' : '' }}" data-step="4">
                <h4 class="section-title"><i class="fas fa-play-circle me-2"></i>الأنشطة التمهيدية</h4>
                @include('projects.partials.tables.preliminary.activities', [
                    'financialItems' => \App\Models\FinancialItem::where('is_active', true)->orderBy('name')->get(),
                    'units' => \App\Models\Unit::orderBy('unit_name')->get()
                ])
                @include('projects.partials.tables.preliminary.preliminary-financial-summary')
            </section>

            <section class="step {{ $initialStep == 5 ? 'active' : '' }}" data-step="5">
                <h4 class="section-title"><i class="fas fa-cogs me-2"></i>الأنشطة التنفيذية</h4>
                @include('projects.partials.tables.executive.activities', [
                    'financialItems' => \App\Models\FinancialItem::where('is_active', true)->orderBy('name')->get(),
                    'units' => \App\Models\Unit::orderBy('unit_name')->get()
                ])
                @include('projects.partials.tables.executive.executive-financial-summary')
            </section>

            <section class="step {{ $initialStep == 6 ? 'active' : '' }}" data-step="6">
                <div id="step6-financing-placeholder">
                    @include('projects.partials._financing')
                </div>
                @include('projects.partials._project_cost')
            </section>

            <section class="step {{ $initialStep == 7 ? 'active' : '' }}" data-step="7">
                @include('projects.partials._review')
            </section>
        </form>
    </div>

    <!-- =========================================
         التذييل الثابت (Sticky Footer Action Bar)
         ========================================= -->
    <footer class="action-footer">
        <div id="saveIndicator" class="save-indicator text-muted px-3" style="font-size: 0.8rem; min-width: 150px;">
            <i class="fas fa-check-circle me-1"></i>جاهز
        </div>
        
        <div class="d-flex">
            <button type="button" id="prevBtn" class="btn btn-secondary btn-navigation px-4" disabled>
                 السابق <i class="fas fa-chevron-right ms-1"></i>
            </button>
        </div>

        <div class="progress-wrapper">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted fw-bold">التقدم</small>
                <small class="text-primary fw-bold"><span id="currentStepDisplay">1</span> / 7</small>
            </div>
            <div class="progress" style="height: 6px; border-radius: 10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: 14.29%; border-radius: 10px;"></div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" id="saveBtn" class="btn {{ ($initialStep ?? 1) == 7 ? 'btn-success px-4' : 'btn-light border' }} btn-navigation">
                <i class="fas fa-save {{ ($initialStep ?? 1) == 7 ? 'me-1' : 'text-muted me-1' }}"></i> {{ ($initialStep ?? 1) == 7 ? 'حفظ' : 'حفظ كمسودة' }}
            </button>
            <button type="button" id="nextBtn" class="btn btn-primary btn-navigation px-4">
                التالي <i class="fas fa-chevron-left ms-1"></i>
            </button>
        </div>
    </footer>

    <!-- =========================================
         نافذة المتعاونين (Collaborators Modal)
         ========================================= -->
    @if(isset($draftCollaborators) && isset($draftActivities))
    <div class="modal fade" id="collaboratorsModal" tabindex="-1" aria-labelledby="collaboratorsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="collaboratorsModalLabel"><i class="fas fa-users text-primary me-2"></i> سجل التعديلات والمتعاونين</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-4 border-end bg-light p-3">
                            <h6 class="fw-bold mb-3">المتعاونون ({{ $draftCollaborators->count() }})</h6>
                            <ul class="list-group list-group-flush border-0">
                                @foreach($draftCollaborators as $collaborator)
                                <li class="list-group-item bg-transparent px-0 border-bottom py-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user-circle text-secondary fs-4 me-2 align-middle"></i>
                                        <span class="small fw-semibold">{{ $collaborator->user->name ?? 'غير معروف' }}</span>
                                    </div>
                                    @if($collaborator->role === 'owner')
                                        <span class="badge bg-primary rounded-pill" style="font-size:0.6rem;">المنشئ</span>
                                    @endif
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-8 p-3" style="max-height: 400px; overflow-y: auto;">
                            <h6 class="fw-bold mb-3">سجل النشاط</h6>
                            @if($draftActivities->count() > 0)
                                <div class="timeline position-relative" style="border-right: 2px solid #e9ecef; margin-right: 10px; padding-right: 20px;">
                                    @foreach($draftActivities as $activity)
                                    <div class="position-relative mb-3">
                                        <div class="position-absolute" style="right: -27px; top: 0; background: #fff; padding: 2px;">
                                            <i class="fas fa-circle text-{{ $activity->action === 'created' ? 'success' : 'primary' }}" style="font-size: 10px;"></i>
                                        </div>
                                        <div class="small fw-semibold text-dark">
                                            {{ $activity->user->name ?? 'غير معروف' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            @if($activity->action === 'created')
                                                أنشأ المسودة
                                            @else
                                                عدل المرحلة ({{ $activity->step }})
                                            @endif
                                            - <span dir="ltr">{{ $activity->created_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-history fs-3 mb-2 opacity-50"></i>
                                    <p class="small mb-0">لا يوجد سجل نشاط متاح</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@section('scripts')

<script>
    // =========================================
    // Auto-expand textarea script
    // =========================================
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
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // =========================================
    // Override showToast to act like a Flash Session at the top
    // =========================================
    if (window.AppUtils && window.AppUtils.Utils) {
        window.AppUtils.Utils.showToast = function(message, type = 'info', options = {}) {
            const container = document.getElementById('flash-messages-container');
            if (!container) return;
            
            // Remove existing custom flash messages
            const existing = document.getElementById('custom-flash-message');
            if (existing) existing.remove();
            
            const alertClass = type === 'error' ? 'alert-danger' : (type === 'warning' ? 'alert-warning' : (type === 'success' ? 'alert-success' : 'alert-info'));
            const icon = type === 'error' ? 'fa-exclamation-triangle' : (type === 'warning' ? 'fa-exclamation-circle' : (type === 'success' ? 'fa-check-circle' : 'fa-info-circle'));
            const borderColor = type === 'error' ? '#dc3545' : (type === 'success' ? '#198754' : (type === 'warning' ? '#ffc107' : '#0dcaf0'));
            
            const alertHtml = `
                <div id="custom-flash-message" class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-4" role="alert" style="border:0; border-right:4px solid ${borderColor};">
                    <div class="d-flex align-items-center">
                        <i class="fas ${icon} fs-4 me-3 ms-2"></i>
                        <div>${message.replace(/\n/g, '<br>')}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            container.innerHTML = alertHtml;
            
            // Auto-scroll to top so the user sees it
            const scrollContainer = document.getElementById('mainScrollContainer');
            if (scrollContainer) scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
            else window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Auto-hide after 6 seconds
            setTimeout(() => {
                const el = document.getElementById('custom-flash-message');
                if (el) {
                    el.classList.remove('show');
                    setTimeout(() => { if (el && el.parentNode) el.remove(); }, 200);
                }
            }, 6000);
        };
    }
    
    // Global permissions and status context
    window.reviewerType = "{{ $reviewerType ?? 'general' }}";
    window.projectStatus = "{{ $projectStatus ?? 'draft' }}";
    window.isFinancialReview = window.projectStatus === 'financial_review';
    
    const FormManager = {
        currentStep: {{ $initialStep ?? 1 }},
        totalSteps: 7,
        projectId: null,
        debugMode: true,
        isDirty: false,
        saveInProgress: false,
        autoSaveInterval: null,
        lastSavedAt: null,
        
        init: function() {
            this.debug('Initializing Form Manager...');
            
            const projectIdField = document.getElementById('project_id');
            if (projectIdField && projectIdField.value) {
                this.projectId = projectIdField.value;
                this.debug(`Initial Project ID: ${this.projectId}`);
            }
            
            const lastSavedStep = document.getElementById('lastSavedStep');
            if (lastSavedStep && lastSavedStep.value && parseInt(lastSavedStep.value) > 0) {
                this.currentStep = parseInt(lastSavedStep.value);
                this.debug(`Restoring to Step: ${this.currentStep}`);
            }
            
            this.bindEvents();
            this.updateUI();
            this.updateProgressBar();
            this.initAutoSave();
            this.setupFormObservers();
            this.debug('✅ Form Manager Initialized');
            this.setupDynamicRowObserver();
            
            setTimeout(() => {
                this.animateStepTransition();
                if (typeof this.initializeTables === 'function') {
                    this.initializeTables();
                }
            }, 100);
        },

        setupFormObservers: function() {
            const form = document.getElementById('projectForm');
            if (form) {
                form.addEventListener('input', () => { this.isDirty = true; });
                $(form).on('change', 'select, input', () => { this.isDirty = true; });
            }
        },

        initAutoSave: function() {
            if (this.autoSaveInterval) clearInterval(this.autoSaveInterval);
            this.autoSaveInterval = setInterval(() => {
                if (this.isDirty && !this.saveInProgress && this.currentStep < this.totalSteps) {
                    this.debug('Auto-saving draft...');
                    this.saveStep(null, false, true);
                }
            }, 30000);
            this.debug('Auto-save timer started (30s)');
        },
        
        loadDraftSettings: function() {
            const isDraftField = document.getElementById('isDraft');
            const lastSavedStepField = document.getElementById('lastSavedStep');
            const projectIdField = document.getElementById('project_id');
            
            if (isDraftField && isDraftField.value === 'true') {
                this.isDraft = true;
                this.debug('✅ Draft resume detected');
                if (lastSavedStepField && lastSavedStepField.value) {
                    this.currentStep = parseInt(lastSavedStepField.value);
                }
                if (projectIdField && projectIdField.value) {
                    this.projectId = projectIdField.value;
                }
            }
        },

        setupDynamicRowObserver: function() {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.addedNodes.length && this.currentStep === 5) {
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType === 1 && (node.classList.contains('executive-activity-row') || node.classList.contains('executive-activity-action-row') || node.querySelector('.objective-result-select'))) {
                                this.populateExecutiveActivityDropdowns();
                            }
                        });
                    }
                });
            });

            const container = document.getElementById('executive-activities-container');
            if (container) observer.observe(container, { childList: true, subtree: true });
        },
        
        bindEvents: function() {
            this.debug('Binding events...');
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const saveBtn = document.getElementById('saveBtn');
            
            if (nextBtn) nextBtn.addEventListener('click', (e) => { e.preventDefault(); this.nextStep(); });
            if (prevBtn) prevBtn.addEventListener('click', (e) => { e.preventDefault(); this.prevStep(); });
            if (saveBtn) saveBtn.addEventListener('click', (e) => { e.preventDefault(); this.saveDraft(); });
            
            document.querySelectorAll('.step-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.goToStep(parseInt(e.currentTarget.dataset.step));
                });
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    if (e.key === 'ArrowRight') { e.preventDefault(); this.nextStep(); } 
                    else if (e.key === 'ArrowLeft') { e.preventDefault(); this.prevStep(); }
                }
            });
            
            window.ProjectForm = this;
            this.debug('✅ Events bound successfully');
        },
        
        // ============================================================
        // زر "التالي" – نحفظ كمسودة دائماً (isDraft=true)
        // ============================================================
        nextStep: function() {
            const isValid = this.validateCurrentStep(true);
            if (!isValid) {
                return;
            }

            const statusInput = document.querySelector('input[name="status"]');
            if (statusInput) statusInput.value = 'draft';

            const lastStepField = document.getElementById('lastSavedStep');
            if (lastStepField) {
                const currentVal = parseInt(lastStepField.value || '1', 10);
                const nextStepNum = Math.min(this.totalSteps, this.currentStep + 1);
                lastStepField.value = Math.max(currentVal, nextStepNum);
            }

            this.saveStep((isSuccess) => {
                if (isSuccess) {
                    if (this.currentStep < this.totalSteps) {
                        this.currentStep++;
                        this.updateUI();
                        this.updateProgressBar();
                        this.scrollToTop();
                        this.animateStepTransition();
                        if (typeof flasher !== 'undefined' && flasher.success) {
                            flasher.success('تم الانتقال للمرحلة التالية بنجاح');
                        } else if (typeof AppUtils !== 'undefined' && AppUtils.Utils && AppUtils.Utils.showToast) {
                            AppUtils.Utils.showToast('تم الانتقال للمرحلة التالية بنجاح', 'success');
                        }
                    }
                } else {
                    if (typeof flasher !== 'undefined' && flasher.error) {
                        flasher.error('لا يمكن الانتقال للمرحلة التالية لوجود أخطاء في البيانات.');
                    } else if (typeof AppUtils !== 'undefined' && AppUtils.Utils && AppUtils.Utils.showToast) {
                        AppUtils.Utils.showToast('لا يمكن الانتقال للمرحلة التالية لوجود أخطاء في البيانات.', 'error');
                    }
                }
            }, true, false, 'next', true); // isDraft = true
        },
        
        prevStep: function() {
            if (this.currentStep > 1) {
                const statusInput = document.querySelector('input[name="status"]');
                if (statusInput) statusInput.value = 'draft';

                const prev = this.currentStep - 1;
                this.saveStep(() => {}, false, true, 'back', true); // isDraft = true

                this.currentStep = prev;
                this.updateUI();
                this.updateProgressBar();
                this.scrollToTop();
                this.animateStepTransition();
            }
        },
        
        goToStep: function(step) {
            if (step >= 1 && step <= this.totalSteps) {
                if (step === this.currentStep) return;
                const direction = step > this.currentStep ? 'next' : 'back';

                if (direction === 'next') {
                    const isValid = this.validateCurrentStep(true);
                    if (!isValid) return;
                }

                const statusInput = document.querySelector('input[name="status"]');
                if (statusInput) statusInput.value = 'draft';

                if (direction === 'back') {
                    this.saveStep(() => {
                        this.currentStep = step;
                        this.updateUI();
                        this.updateProgressBar();
                        this.scrollToTop();
                        this.animateStepTransition();
                    }, false, true, 'back', true);
                } else {
                    this.saveStep((isSuccess) => {
                        if (isSuccess) {
                            this.currentStep = step;
                            this.updateUI();
                            this.updateProgressBar();
                            this.scrollToTop();
                            this.animateStepTransition();
                            if (typeof flasher !== 'undefined' && flasher.success) {
                                flasher.success('تم الانتقال للمرحلة التالية بنجاح');
                            } else if (typeof AppUtils !== 'undefined' && AppUtils.Utils && AppUtils.Utils.showToast) {
                                AppUtils.Utils.showToast('تم الانتقال للمرحلة التالية بنجاح', 'success');
                            }
                        } else {
                            if (typeof flasher !== 'undefined' && flasher.error) {
                                flasher.error('لا يمكن الانتقال للأمام لوجود أخطاء في البيانات الحالية.');
                            } else if (typeof AppUtils !== 'undefined' && AppUtils.Utils && AppUtils.Utils.showToast) {
                                AppUtils.Utils.showToast('لا يمكن الانتقال للأمام لوجود أخطاء في البيانات الحالية.', 'error');
                            }
                        }
                    }, false, false, direction, true);
                }
            }
        },

        // ============================================================
        // حفظ المسودة – يحدد isDraft تلقائياً بناءً على الخطوة الحالية
        // ============================================================
        saveDraft: function() {
            if (this.currentStep === 1 && !this.projectId) {
                const projectName = document.querySelector('[name="project_name"]');
                if (projectName && !projectName.value.trim()) {
                    projectName.value = 'مسودة مشروع - ' + new Date().toLocaleTimeString('ar-SA');
                }
            }

            // تحديد ما إذا كنا في الخطوة 7 (حفظ نهائي) أم لا
            const isFinalSave = (this.currentStep === this.totalSteps);

            const statusInput = document.querySelector('input[name="status"]');
            if (statusInput) statusInput.value = 'draft';

            const lastStepField = document.getElementById('lastSavedStep');
            if (lastStepField) {
                const currentVal = parseInt(lastStepField.value || '1', 10);
                lastStepField.value = Math.max(currentVal, this.currentStep);
            }

            // استدعاء saveStep مع isDraft = false في الخطوة 7، وإلا true
            this.saveStep((isSuccess, data) => {
                if (isSuccess) {
                    let msg = '';
                    if (isFinalSave) {
                        const isComplete = data && data.is_draft_complete === true;
                        msg = isComplete
                            ? '✅ تم حفظ المشروع كمسودة مكتملة بنجاح'
                            : '⚠️ تم حفظ المشروع كمسودة (البيانات غير مكتملة)';
                    } else {
                        msg = `✅ تم حفظ المسودة بنجاح (الخطوة ${this.currentStep})`;
                    }

                    if (typeof flasher !== 'undefined' && flasher.success) {
                        flasher.success(msg);
                    } else if (typeof AppUtils !== 'undefined' && AppUtils.Utils && AppUtils.Utils.showToast) {
                        AppUtils.Utils.showToast(msg, 'success');
                    }

                    setTimeout(() => {
                        window.location.href = this.projectId ? `/projects/${this.projectId}` : '/projects';
                    }, 1500);
                } else {
                    const errorMsg = (data && data.message) ? data.message : 'حدث خطأ أثناء حفظ البيانات.';
                    if (typeof flasher !== 'undefined' && flasher.error) {
                        flasher.error(errorMsg);
                    } else if (typeof AppUtils !== 'undefined' && AppUtils.Utils && AppUtils.Utils.showToast) {
                        AppUtils.Utils.showToast(errorMsg, 'error');
                    }
                }
            }, false, false, isFinalSave ? 'final' : 'draft', isFinalSave ? false : true);
        },
        
        // ============================================================
        // saveStep المعدل – يقبل isDraft (خامس معامل)
        // ============================================================
        saveStep: function(callback, showErrors = true, isBackground = false, direction = null, isDraft = true) {
            if (this.saveInProgress) return;
            const stepNumber = this.currentStep;
            const form = document.getElementById('projectForm');
            if (!form) { if (callback) callback(); return; }
            
            this.saveInProgress = true;
            const fullData = new FormData(form);
            let formData = new FormData();
            
            // إذا كنا في الخطوة السابعة، نرسل كل البيانات (كما هي)
            if (stepNumber === this.totalSteps) {
                formData = fullData;
            } else {
                const currentStepSection = form.querySelector(`.step[data-step="${stepNumber}"]`);
                const stepInputNames = new Set();
                if (currentStepSection) {
                    currentStepSection.querySelectorAll('[name]').forEach(el => stepInputNames.add(el.name));
                }
                
                const globalFields = ['_token', 'status', 'project_id', 'last_saved_step'];
                for (let [key, value] of fullData.entries()) {
                    let isStepField = false;
                    stepInputNames.forEach(name => {
                        if (key === name || key.startsWith(name + '[') || key.startsWith(name + '.')) isStepField = true;
                    });
                    if (isStepField || globalFields.includes(key)) formData.append(key, value);
                }
            }

            if (this.projectId) formData.set('project_id', this.projectId);
            if (direction) formData.append('navigation_direction', direction);
            
            // ========== إضافة is_draft ==========
            formData.append('is_draft', isDraft ? '1' : '0');
            
            formData = this.normalizeFormData(formData);
            const token = document.querySelector('input[name="_token"]').value;
            const url = `/projects/step/${stepNumber}`;
            
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const originalNextText = nextBtn ? nextBtn.innerHTML : '';
            
            if (!isBackground) {
                if (nextBtn) {
                    nextBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري الحفظ...';
                    nextBtn.disabled = true;
                }
                if (prevBtn) prevBtn.disabled = true;
            } else {
                this.updateSaveIndicator('saving');
            }
            
            const requestHeaders = { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' };
            if (isBackground) requestHeaders['X-Background-Request'] = 'true';
            
            fetch(url, { method: 'POST', body: formData, headers: requestHeaders })
                .then(response => response.json().then(data => ({ ok: response.ok, status: response.status, data })))
                .then(({ ok, status, data }) => {
                    this.isDirty = false;
                    this.lastSavedAt = new Date();
                    if (data.project_id) {
                        this.projectId = data.project_id;
                        const field = document.getElementById('project_id');
                        if (field) field.value = data.project_id;
                    }
                    if (data.last_saved_step) {
                        const lssField = document.getElementById('lastSavedStep');
                        if (lssField) lssField.value = data.last_saved_step;
                    } else if (stepNumber === 7) {
                        const lssField = document.getElementById('lastSavedStep');
                        if (lssField) lssField.value = 7;
                    }

                    if (ok || status === 422) {
                        const isComplete = data && (data.is_draft_complete === true || data.status === 'completed_draft');
                        this.updateDraftStatusUI(isComplete);
                        this.updateSaveIndicator('saved', isComplete);
                        if (status === 422) {
                            if (data.is_draft_incomplete) {
                                this.showIncompleteDraftError(data);
                                if (callback) callback(false, data);
                            } else if (showErrors) {
                                this.handleServerErrors(data.errors);
                                if (callback) callback(false, data);
                            } else {
                                if (callback) callback(false, data);
                            }
                        } else {
                            if (callback) callback(ok, data);
                        }
                    } else {
                        throw new Error(data.message || 'خطأ في الاتصال بالخادم');
                    }
                })
                .catch(error => {
                    this.debug('Save failed', error);
                    if (!isBackground) AppUtils.Utils.showToast('فشل في حفظ البيانات: ' + (error.message || 'خطأ غير معروف'), 'error');
                    this.updateSaveIndicator('error');
                    if (callback) callback(false, null);
                })
                .finally(() => {
                    this.saveInProgress = false;
                    if (!isBackground) {
                        if (nextBtn) { nextBtn.innerHTML = originalNextText; nextBtn.disabled = false; }
                        if (prevBtn) prevBtn.disabled = this.currentStep === 1;
                        this.updateUI();
                        const overlay = document.getElementById('step7-loading-overlay');
                        if (overlay) overlay.remove();
                    }
                });
        },

        // ============================================================
        // تحديث واجهة المستخدم – تغيير زر الحفظ في الخطوة 7
        // ============================================================
        updateUI: function() {
            document.querySelectorAll('.step').forEach((step, index) => {
                step.classList.toggle('active', index + 1 === this.currentStep);
            });
            
            document.querySelectorAll('.step-item').forEach((item, index) => {
                const stepNum = index + 1;
                item.classList.remove('active', 'completed');
                if (stepNum === this.currentStep) item.classList.add('active');
                else if (stepNum < this.currentStep) item.classList.add('completed');
            });
            
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const saveBtn = document.getElementById('saveBtn');
            const currentStepDisplay = document.getElementById('currentStepDisplay');
            
            if (prevBtn) prevBtn.disabled = this.currentStep === 1;
            
            if (this.currentStep === this.totalSteps) {
                if (nextBtn) nextBtn.style.display = 'none';
                if (saveBtn) {
                    saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';
                    saveBtn.className = 'btn btn-success btn-navigation px-4';
                    // إزالة المستمعات القديمة وإضافة مستمع جديد (أو استخدام onclick)
                    saveBtn.onclick = null;
                    saveBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        FormManager.saveDraft();
                    });
                }
            } else {
                if (nextBtn) {
                    nextBtn.style.display = 'inline-block';
                    nextBtn.innerHTML = 'التالي <i class="fas fa-chevron-left ms-1"></i>';
                    nextBtn.className = 'btn btn-primary btn-navigation px-4';
                }
                if (saveBtn) {
                    saveBtn.innerHTML = '<i class="fas fa-save text-muted me-1"></i> حفظ كمسودة';
                    saveBtn.className = 'btn btn-light border btn-navigation';
                    saveBtn.onclick = null;
                    saveBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        FormManager.saveDraft();
                    });
                }
            }
            
            if (currentStepDisplay) currentStepDisplay.textContent = this.currentStep;
            
            if (this.currentStep === 5) {
                this.refreshRisks();
                this.refreshOutputs();
            }
        },

        refreshRisks: function() {
            if (!this.projectId) return;
            fetch(`/projects/${this.projectId}/risks-list`)
                .then(response => response.json())
                .then(data => {
                    if (data.risks) {
                        document.querySelectorAll('.project-risk-select').forEach(select => {
                            const currentValue = select.value;
                            select.innerHTML = '<option value="">-- اختر المخاطرة --</option>';
                            data.risks.forEach(risk => {
                                const option = document.createElement('option');
                                option.value = risk.id;
                                option.textContent = `${risk.risk} (${risk.risk_rate})`;
                                if (risk.id == currentValue) option.selected = true;
                                select.appendChild(option);
                            });
                        });
                    }
                }).catch(err => console.error('Error refreshing risks:', err));
        },

        refreshOutputs: function() {
            if (!this.projectId) return Promise.resolve();
            return fetch(`/projects/${this.projectId}/outputs-list`)
                .then(response => response.json())
                .then(data => {
                    if (data.outputs) {
                        document.querySelectorAll('.project-output-select').forEach(select => {
                            const currentValue = select.value;
                            select.innerHTML = '<option value="">-- اختر المخرج --</option>';
                            data.outputs.forEach(output => {
                                const option = document.createElement('option');
                                option.value = output.id;
                                option.textContent = output.output;
                                if (output.id == currentValue) option.selected = true;
                                select.appendChild(option);
                            });
                        });
                    }
                }).catch(err => { console.error('Error refreshing outputs:', err); return Promise.resolve(); });
        },
        
        updateProgressBar: function() {
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                const progress = (this.currentStep / this.totalSteps) * 100;
                progressBar.style.width = `${progress}%`;
                progressBar.setAttribute('aria-valuenow', progress);
            }
        },
        
        animateStepTransition: function() {
            const currentStep = document.querySelector(`.step[data-step="${this.currentStep}"]`);
            if (currentStep) {
                currentStep.style.animation = 'none';
                currentStep.offsetHeight;
                currentStep.style.animation = 'slideIn 0.3s ease-out';
            }

            if (this.currentStep === 5) {
                this.refreshRisks();
                this.refreshOutputs().then(() => this.populateExecutiveActivityDropdowns());
            }

            // عند الانتقال للخطوة 4 (تمهيدية) أو 5 (تنفيذية): تحقق من الجهة في ERPNext وجلب البنود المالية
            if (this.currentStep === 4 || this.currentStep === 5) {
                this.loadErpNextFinancialItems(this.currentStep);
            }

            if (this.currentStep === 7 && typeof populateReviewData === 'function') {
                populateReviewData();
            }
        },

        /**
         * التحقق من الجهة في ERPNext وجلب البنود المالية (Expense Claim Types).
         * يعمل قبل حفظ المشروع في قاعدة البيانات.
         */
        loadErpNextFinancialItems: function(stepNumber) {
            this.debug(`Loading ERPNext financial items for step ${stepNumber}...`);

            const apiUrl = '/api/frappe-project/verify-entity-financial-items';

            // أظهر مؤشر تحميل في كل قوائم البنود المالية للخطوة الحالية
            const stepSection = document.querySelector(`.step[data-step="${stepNumber}"]`);
            if (!stepSection) return;

            const financialSelects = stepSection.querySelectorAll('.financial-item-select');

            financialSelects.forEach(sel => {
                // أضف placeholder تحميل إن لم يكن محددًا مسبقًا
                if (!sel.disabled && sel.options.length <= 1) {
                    const loadingOpt = document.createElement('option');
                    loadingOpt.value = '';
                    loadingOpt.textContent = '⏳ جاري جلب البنود من ERPNext...';
                    loadingOpt.disabled = true;
                    sel.appendChild(loadingOpt);
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            })
            .then(response => response.json())
            .then(data => {
                this.debug('ERPNext financial items response:', data);

                if (data.entity_created) {
                    AppUtils?.Utils?.showToast(
                        `✅ تم إنشاء الجهة "${data.entity_name}" في ERPNext وجلب البنود المالية بنجاح`,
                        'success'
                    );
                } else if (data.entity_found) {
                    this.debug(`Entity "${data.entity_name}" verified in ERPNext`);
                }

                const items = data.items || [];

                // خزّن البنود عالمياً لإعادة الاستخدام عند إضافة صفوف جديدة
                window.erpNextFinancialItems = items;
                window.erpNextFinancialItemsStep = stepNumber;

                if (items.length === 0) {
                    // لا توجد بنود — أزل placeholder التحميل
                    const stepSect = document.querySelector(`.step[data-step="${stepNumber}"]`);
                    if (stepSect) {
                        stepSect.querySelectorAll('.financial-item-select').forEach(sel => {
                            // أزل أي option تحميل
                            Array.from(sel.options).forEach(opt => {
                                if (opt.textContent.includes('⏳')) opt.remove();
                            });
                        });
                    }
                    if (!data.success) {
                        this.debug('Entity not found/created in ERPNext:', data.message);
                    }
                    return;
                }

                // عبّئ كل قوائم البنود المالية في الخطوة الحالية
                this.populateFinancialItemSelects(stepNumber, items);
            })
            .catch(err => {
                this.debug('Error fetching ERPNext financial items:', err);
                // أزل placeholder التحميل عند الخطأ
                const stepSect = document.querySelector(`.step[data-step="${stepNumber}"]`);
                if (stepSect) {
                    stepSect.querySelectorAll('.financial-item-select').forEach(sel => {
                        Array.from(sel.options).forEach(opt => {
                            if (opt.textContent.includes('⏳')) opt.remove();
                        });
                    });
                }
            });
        },

        /**
         * تعبئة قوائم اختيار البنود المالية ببيانات ERPNext.
         * يعمل على الصفوف الموجودة وكذلك أي صف جديد يُضاف لاحقاً.
         */
        populateFinancialItemSelects: function(stepNumber, items) {
            if (!items || items.length === 0) return;

            const stepSection = document.querySelector(`.step[data-step="${stepNumber}"]`);
            if (!stepSection) return;

            const selects = stepSection.querySelectorAll('.financial-item-select');
            this.debug(`Populating ${selects.length} financial item selects with ${items.length} ERPNext items`);

            selects.forEach(sel => {
                // تجاهل القوائم المُعطلة (read-only)
                if (sel.disabled) return;

                const currentValue = sel.value; // احتفظ بالقيمة الحالية إن وُجدت

                // أزل placeholder التحميل والخيارات القديمة من ERPNext (لتجنب التكرار)
                Array.from(sel.options).forEach(opt => {
                    if (opt.textContent.includes('⏳') || opt.dataset.fromErpnext === 'true') {
                        opt.remove();
                    }
                });

                // أضف البنود الجديدة من ERPNext
                items.forEach(item => {
                    // تجنب التكرار إذا كان البند موجوداً بالفعل
                    const alreadyExists = Array.from(sel.options).some(opt => opt.value === String(item.id));
                    if (alreadyExists) return;

                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.text || item.name || item.id;
                    option.dataset.fromErpnext = 'true';
                    sel.appendChild(option);
                });

                // أعد تحديد القيمة السابقة إن وُجدت
                if (currentValue) {
                    sel.value = currentValue;
                }

                // حدّث Select2 إن كان مُفعّلاً
                if (typeof $ !== 'undefined' && $(sel).data('select2')) {
                    $(sel).trigger('change.select2');
                }
            });
        },

        populateExecutiveActivityDropdowns: function() {
            this.debug('Populating Executive Activity Dropdowns...');
            const results = [];
            document.querySelectorAll('input[name^="objective_results"][name$="[result_name]"]').forEach(input => {
                if (input.value.trim() !== '') results.push(input.value.trim());
            });

            document.querySelectorAll('.objective-result-select').forEach(select => {
                const currentValue = select.getAttribute('data-selected-value') || select.value;
                while (select.options.length > 1) select.remove(1);
                results.forEach(result => {
                    const option = document.createElement('option');
                    option.value = result;
                    option.textContent = result;
                    if (currentValue === result) option.selected = true;
                    select.appendChild(option);
                });
            });

            const outputs = [];
            document.querySelectorAll('input[name^="result_outputs"][name$="[output]"]').forEach(input => {
                if (input.value.trim() !== '') outputs.push(input.value.trim());
            });

            document.querySelectorAll('.project-output-select').forEach(select => {
                const currentValue = select.getAttribute('data-selected-value') || select.value;
                const existingOptions = Array.from(select.options).map(opt => opt.textContent.trim());
                outputs.forEach(output => {
                    if (!existingOptions.includes(output)) {
                        const option = document.createElement('option');
                        option.value = output;
                        option.textContent = output;
                        select.appendChild(option);
                    }
                });
                if (!select.value && currentValue) {
                    for (let opt of select.options) {
                        if (opt.textContent.trim() === currentValue || opt.value === currentValue) {
                            opt.selected = true;
                            break;
                        }
                    }
                }
            });
        },
            
        isOldProject: function() {
            const typeInput = document.querySelector('input[name="project_type"]');
            return typeInput && typeInput.value === 'old';
        },
            
        validateCurrentStep: function(isStrict = false) {
            this.clearFlashError();
            if (this.isOldProject()) {
                if (this.currentStep === 1 && isStrict) {
                    const projectName = document.querySelector('[name="project_name"]');
                    if (!projectName || !projectName.value.trim()) {
                        this.showFlashError(['يرجى إدخال اسم المشروع للمتابعة']);
                        if (projectName) projectName.focus();
                        return false;
                    }
                }
                return true;
            }

            const currentStepElement = document.querySelector(`.step[data-step="${this.currentStep}"]`);
            if (!currentStepElement) return true;

            let validationError = false;
            let currentErrors = [];

            if (isStrict) {
                const validationResult = this.highlightInvalidFields(currentStepElement);
                validationError = validationResult.hasError;
                if (validationError) {
                    currentErrors = currentErrors.concat(validationResult.errorMessages);
                }
            }

            if (this.currentStep === 1 && isStrict) {
                const projectName = document.querySelector('[name="project_name"]');
                if (!projectName || !projectName.value.trim()) {
                    currentErrors.push('يرجى إدخال اسم المشروع للمتابعة');
                    validationError = true;
                }
            }
            
            let isValid = !validationError;
            let customErrors = [];
            
            if (this.currentStep === 1) isValid = this.validateStep1(isStrict, customErrors) && isValid;
            else if (this.currentStep === 2) isValid = this.validateStep2(isStrict, customErrors) && isValid;
            else if (this.currentStep === 3) isValid = this.validateStep3(isStrict, customErrors) && isValid;
            else if (this.currentStep === 4) isValid = this.validatePreliminaryActivitiesWeights(isStrict, customErrors) && isValid;
            else if (this.currentStep === 5 && typeof this.validateExecutiveActivitiesWeights === 'function') isValid = this.validateExecutiveActivitiesWeights(isStrict, customErrors) && isValid;
            else if (this.currentStep === 6 && typeof this.validateFinancingWeights === 'function') isValid = this.validateFinancingWeights(isStrict, customErrors) && isValid;

            if (isStrict && !isValid) {
                this.showFlashError(currentErrors.concat(customErrors));
                return false;
            }

            return isValid;
        },

        validateStep1: function(isStrict = false, customErrors = []) {
            if (!isStrict) return true;
            if (this.isOldProject()) return true;

            let isValid = true;

            // 1. Beneficiaries Check
            const beneficiarySelect = document.querySelector('select[name="beneficiary_groups[]"]');
            const selectedBeneficiaries = beneficiarySelect ? Array.from(beneficiarySelect.selectedOptions).filter(opt => opt.value !== "") : [];
            const numBeneficiariesInput = document.querySelector('input[name="number_of_beneficiaries"]');
            const numBeneficiaries = numBeneficiariesInput ? parseInt(numBeneficiariesInput.value) || 0 : 0;

            if (selectedBeneficiaries.length === 0 && numBeneficiaries <= 0) {
                customErrors.push('مجموعة المستفيدين: يجب إضافة مستفيد واحد على الأقل قبل الانتقال إلى الخطوة التالية.');
                isValid = false;
                if (beneficiarySelect) {
                    const s2Container = beneficiarySelect.nextElementSibling;
                    if (s2Container && s2Container.classList.contains('select2-container')) {
                        const sel = s2Container.querySelector('.select2-selection');
                        if (sel) { sel.style.borderColor = '#dc3545'; sel.style.borderWidth = '2px'; }
                    } else {
                        beneficiarySelect.style.borderColor = '#dc3545';
                        beneficiarySelect.style.borderWidth = '2px';
                    }
                }
            } else {
                if (beneficiarySelect) {
                    const s2Container = beneficiarySelect.nextElementSibling;
                    if (s2Container && s2Container.classList.contains('select2-container')) {
                        const sel = s2Container.querySelector('.select2-selection');
                        if (sel) { sel.style.borderColor = ''; sel.style.borderWidth = ''; }
                    } else {
                        beneficiarySelect.style.borderColor = '';
                        beneficiarySelect.style.borderWidth = '';
                    }
                }
            }

            // 2. Project Locations Check
            const locationsTable = document.querySelector('#projectLocationsTable tbody');
            const locationRows = locationsTable ? locationsTable.querySelectorAll('tr:not(.project-empty-row)') : [];
            if (locationRows.length === 0) {
                customErrors.push('مواقع المشروع: يجب إضافة موقع واحد على الأقل للمشروع قبل السماح بالانتقال.');
                isValid = false;
                if (locationsTable) {
                    const tableEl = locationsTable.closest('.project-table-container') || locationsTable.closest('table');
                    if (tableEl) { tableEl.style.border = '2px solid #dc3545'; tableEl.style.borderRadius = '4px'; }
                }
            } else {
                if (locationsTable) {
                    const tableEl = locationsTable.closest('.project-table-container') || locationsTable.closest('table');
                    if (tableEl) { tableEl.style.border = ''; tableEl.style.borderRadius = ''; }
                }
            }

            return isValid;
        },

        validateStep2: function(isStrict = false, customErrors = []) {
            if (!isStrict) return true;
            if (this.isOldProject()) return true;

            let isValid = true;

            // 1. Special Objectives Check (الأهداف الخاصة)
            const objRows = document.querySelectorAll('#specificObjectivesTable tbody tr.objective-row');
            if (objRows.length === 0) {
                customErrors.push('الأهداف الخاصة: يجب إدخال هدف خاص واحد على الأقل.');
                isValid = false;
            }

            let totalWeight = 0;
            let hasEmptyObjName = false;

            objRows.forEach((row) => {
                const nameInput = row.querySelector('input[name*="[objective]"]');
                if (!nameInput || !nameInput.value.trim()) {
                    hasEmptyObjName = true;
                    if (nameInput) {
                        nameInput.style.borderColor = '#dc3545';
                        nameInput.style.borderWidth = '2px';
                        nameInput.classList.add('is-invalid');
                    }
                } else {
                    if (nameInput) {
                        nameInput.style.borderColor = '';
                        nameInput.style.borderWidth = '';
                        nameInput.classList.remove('is-invalid');
                    }
                }

                const weightInput = row.querySelector('.objective-weight, input[name*="[objective_weight]"]');
                const w = weightInput ? (parseFloat(weightInput.value) || 0) : 0;
                totalWeight += w;
            });

            if (hasEmptyObjName) {
                customErrors.push('الأهداف الخاصة: حقل اسم الهدف الخاص مطلوب لجميع الأهداف المضافة.');
                isValid = false;
            }

            if (objRows.length > 0 && Math.abs(totalWeight - 100) > 0.01) {
                customErrors.push('الأهداف الخاصة: يجب أن يكون مجموع أوزان جميع الأهداف الخاصة مساوياً لـ 100% (المجموع الحالي: ' + totalWeight.toFixed(2) + '%).');
                isValid = false;
                const totalWeightDisplay = document.getElementById('totalWeightDisplay');
                if (totalWeightDisplay) {
                    totalWeightDisplay.classList.remove('bg-secondary', 'bg-success');
                    totalWeightDisplay.classList.add('bg-danger');
                }
            } else if (objRows.length > 0) {
                const totalWeightDisplay = document.getElementById('totalWeightDisplay');
                if (totalWeightDisplay) {
                    totalWeightDisplay.classList.remove('bg-danger', 'bg-secondary');
                    totalWeightDisplay.classList.add('bg-success');
                }
            }

            // 2. Results Check (النتائج المرتبطة بالأهداف)
            let missingResultForObj = false;
            let hasEmptyResultName = false;

            objRows.forEach((objRow, objIdx) => {
                const objIndex = objRow.getAttribute('data-objective-index') || objIdx;
                const resultRows = document.querySelectorAll(`tr.result-row[data-objective-index="${objIndex}"]`);
                if (resultRows.length === 0) {
                    missingResultForObj = true;
                }

                resultRows.forEach((resRow) => {
                    const resultNameInput = resRow.querySelector('input[name*="[result_name]"]');
                    if (!resultNameInput || !resultNameInput.value.trim()) {
                        hasEmptyResultName = true;
                        if (resultNameInput) {
                            resultNameInput.style.borderColor = '#dc3545';
                            resultNameInput.style.borderWidth = '2px';
                            resultNameInput.classList.add('is-invalid');
                        }
                    } else {
                        if (resultNameInput) {
                            resultNameInput.style.borderColor = '';
                            resultNameInput.style.borderWidth = '';
                            resultNameInput.classList.remove('is-invalid');
                        }
                    }
                });
            });

            if (objRows.length > 0 && missingResultForObj) {
                customErrors.push('النتائج المرتبطة بالأهداف: يجب إضافة نتيجة واحدة على الأقل لكل هدف خاص.');
                isValid = false;
            }

            if (hasEmptyResultName) {
                customErrors.push('النتائج المرتبطة بالأهداف: حقل "اسم النتيجة" مطلوب لجميع النتائج المضافة.');
                isValid = false;
            }

            // 3. Outputs Check (المخرجات المرتبطة بالنتائج)
            let missingOutputForRes = false;
            let hasEmptyOutputName = false;

            const allResultRows = document.querySelectorAll('tr.result-row');
            allResultRows.forEach((resRow) => {
                const outputsList = resRow.querySelectorAll('.project-output-item');
                if (outputsList.length === 0) {
                    missingOutputForRes = true;
                }

                outputsList.forEach((outItem) => {
                    const outputNameInput = outItem.querySelector('input[name*="[output]"]');
                    if (!outputNameInput || !outputNameInput.value.trim()) {
                        hasEmptyOutputName = true;
                        if (outputNameInput) {
                            outputNameInput.style.borderColor = '#dc3545';
                            outputNameInput.style.borderWidth = '2px';
                            outputNameInput.classList.add('is-invalid');
                        }
                    } else {
                        if (outputNameInput) {
                            outputNameInput.style.borderColor = '';
                            outputNameInput.style.borderWidth = '';
                            outputNameInput.classList.remove('is-invalid');
                        }
                    }
                });
            });

            if (allResultRows.length > 0 && missingOutputForRes) {
                customErrors.push('المخرجات المرتبطة بالنتائج: يجب إضافة مخرج واحد على الأقل لكل نتيجة.');
                isValid = false;
            }

            if (hasEmptyOutputName) {
                customErrors.push('المخرجات المرتبطة بالنتائج: حقل "اسم المخرج" مطلوب لجميع المخرجات المضافة.');
                isValid = false;
            }

            if (!isValid) {
                if (typeof flasher !== 'undefined' && flasher.error) {
                    flasher.error('يرجى استكمال وإعادة تصحيح بيانات الأهداف والنتائج والمخرجات.');
                }
            }

            return isValid;
        },
        
        validateStep3: function(isStrict = false, customErrors = []) {
            if (!isStrict) return true;
            if (this.isOldProject()) return true;

            let isValid = true;

            // 1. Risks Check (جدول المخاطر)
            const risksTableBody = document.querySelector('#risksTable tbody');
            const riskRows = risksTableBody ? risksTableBody.querySelectorAll('tr.risk-row, tr:not(.project-empty-row)') : [];
            let validRiskCount = 0;
            let hasEmptyRiskDesc = false;

            riskRows.forEach(row => {
                const riskInput = row.querySelector('input[name*="[risk]"]');
                if (riskInput) {
                    if (!riskInput.value.trim()) {
                        hasEmptyRiskDesc = true;
                        riskInput.style.borderColor = '#dc3545';
                        riskInput.style.borderWidth = '2px';
                        riskInput.classList.add('is-invalid');
                    } else {
                        riskInput.style.borderColor = '';
                        riskInput.style.borderWidth = '';
                        riskInput.classList.remove('is-invalid');
                        validRiskCount++;
                    }
                }
            });

            if (riskRows.length === 0 || validRiskCount === 0) {
                customErrors.push('إدارة المخاطر: يجب إضافة مخاطرة واحدة على الأقل واستكمال وصف الخطر.');
                isValid = false;
                if (risksTableBody) {
                    const tableEl = risksTableBody.closest('.project-table-container') || risksTableBody.closest('table');
                    if (tableEl) tableEl.style.border = '2px solid #dc3545';
                }
            } else if (hasEmptyRiskDesc) {
                customErrors.push('إدارة المخاطر: حقل وصف الخطر إجباري ولا يمكن تركه فارغاً.');
                isValid = false;
            } else {
                if (risksTableBody) {
                    const tableEl = risksTableBody.closest('.project-table-container') || risksTableBody.closest('table');
                    if (tableEl) tableEl.style.border = '';
                }
            }

            // Helper function to validate entity table
            const validateEntityTable = (tableId, tableName) => {
                const tbody = document.querySelector(`#${tableId} tbody`);
                const rows = tbody ? tbody.querySelectorAll('tr:not(.project-empty-row)') : [];
                if (rows.length === 0) {
                    customErrors.push(`${tableName}: يجب إضافة جهة واحدة على الأقل.`);
                    if (tbody) {
                        const tableEl = tbody.closest('.project-table-container') || tbody.closest('table');
                        if (tableEl) tableEl.style.border = '2px solid #dc3545';
                    }
                    return false;
                }

                let hasEmptySelect = false;
                rows.forEach(row => {
                    const sel = row.querySelector('select.authority-select');
                    if (!sel || !sel.value || sel.value === "" || sel.value === "-- اختر --") {
                        hasEmptySelect = true;
                        if (sel) {
                            const s2 = sel.nextElementSibling;
                            if (s2 && s2.classList.contains('select2-container')) {
                                const s2Select = s2.querySelector('.select2-selection');
                                if (s2Select) { s2Select.style.borderColor = '#dc3545'; s2Select.style.borderWidth = '2px'; }
                            } else {
                                sel.style.borderColor = '#dc3545';
                                sel.style.borderWidth = '2px';
                            }
                        }
                    } else {
                        if (sel) {
                            const s2 = sel.nextElementSibling;
                            if (s2 && s2.classList.contains('select2-container')) {
                                const s2Select = s2.querySelector('.select2-selection');
                                if (s2Select) { s2Select.style.borderColor = ''; s2Select.style.borderWidth = ''; }
                            } else {
                                sel.style.borderColor = '';
                                sel.style.borderWidth = '';
                            }
                        }
                    }
                });

                if (hasEmptySelect) {
                    customErrors.push(`${tableName}: يجب اختيار اسم الجهة لجميع العناصر المضافة.`);
                    return false;
                } else {
                    if (tbody) {
                        const tableEl = tbody.closest('.project-table-container') || tbody.closest('table');
                        if (tableEl) tableEl.style.border = '';
                    }
                    return true;
                }
            };

            // 2. Supervising Authorities Check (الجهات الإشرافية)
            if (!validateEntityTable('supervisingAuthoritiesTable', 'الجهات الإشرافية')) {
                isValid = false;
            }

            // 3. Implementing Entities Check (الجهات المنفذة)
            if (!validateEntityTable('implementingEntitiesTable', 'الجهات المنفذة')) {
                isValid = false;
            }

            // 4. Participating Entities Check (الجهات المشاركة)
            if (!validateEntityTable('participatingEntitiesTable', 'الجهات المشاركة')) {
                isValid = false;
            }

            // 5. Beneficiary Entities Check (الجهات المستفيدة)
            const benSec = document.getElementById('beneficiary-entities-section');
            if (benSec && benSec.style.display !== 'none') {
                if (!validateEntityTable('beneficiaryEntitiesTable', 'الجهات المستفيدة')) {
                    isValid = false;
                }
            }

            return isValid;
        },

        validateFinancingWeights: function(isStrict = false, errors = null) {
            if (!isStrict) return true;
            if (this.isOldProject()) return true;

            const financingCards = document.querySelectorAll('.financing-card');
            let isValid = true;

            if (financingCards.length === 0) {
                const msg = 'مصادر التمويل: يجب إضافة مصدر تمويل واحد على الأقل للمشروع للمتابعة.';
                if (errors) errors.push(msg); else if (typeof flasher !== 'undefined' && flasher.error) flasher.error(msg);
                return false;
            }

            let totalAmount = 0;
            let totalPercentage = 0;
            let hasEmptyField = false;

            financingCards.forEach((card) => {
                const sourceSelect = card.querySelector('select[name*="[funding_source_id]"], select.funding-source-select');
                if (!sourceSelect || !sourceSelect.value || sourceSelect.value === "") {
                    hasEmptyField = true;
                    if (sourceSelect) {
                        const s2 = sourceSelect.nextElementSibling;
                        if (s2 && s2.classList.contains('select2-container')) {
                            const sel = s2.querySelector('.select2-selection');
                            if (sel) { sel.style.borderColor = '#dc3545'; sel.style.borderWidth = '2px'; }
                        } else {
                            sourceSelect.style.borderColor = '#dc3545';
                            sourceSelect.style.borderWidth = '2px';
                        }
                    }
                } else {
                    if (sourceSelect) {
                        const s2 = sourceSelect.nextElementSibling;
                        if (s2 && s2.classList.contains('select2-container')) {
                            const sel = s2.querySelector('.select2-selection');
                            if (sel) { sel.style.borderColor = ''; sel.style.borderWidth = ''; }
                        } else {
                            sourceSelect.style.borderColor = '';
                            sourceSelect.style.borderWidth = '';
                        }
                    }
                }

                const amountInput = card.querySelector('input[name*="[financing_amount]"]');
                const amt = amountInput ? (parseFloat(amountInput.value) || 0) : 0;
                totalAmount += amt;

                if (!amountInput || !amountInput.value || amt <= 0) {
                    hasEmptyField = true;
                    if (amountInput) {
                        amountInput.style.borderColor = '#dc3545';
                        amountInput.style.borderWidth = '2px';
                        amountInput.classList.add('is-invalid');
                    }
                } else {
                    if (amountInput) {
                        amountInput.style.borderColor = '';
                        amountInput.style.borderWidth = '';
                        amountInput.classList.remove('is-invalid');
                    }
                }

                const percentageInput = card.querySelector('input[name*="[financing_percentage]"]');
                const perc = percentageInput ? (parseFloat(percentageInput.value) || 0) : 0;
                totalPercentage += perc;
            });

            if (hasEmptyField) {
                const msg = 'مصادر التمويل: يجب استكمال الحقول الإلزامية لجميع بطاقات التمويل المضافة (مصدر التمويل ومبلغ التمويل).';
                if (errors) errors.push(msg); else if (typeof flasher !== 'undefined' && flasher.error) flasher.error(msg);
                isValid = false;
            }

            if (Math.abs(totalPercentage - 100) > 0.01) {
                let msg = '';
                if (financingCards.length === 1) {
                    msg = `مصادر التمويل: يجب أن تكون نسبة التمويل 100% (النسبة الحالية: ${totalPercentage.toFixed(2)}%).`;
                } else {
                    msg = `مصادر التمويل: يجب أن يكون مجموع نسب جميع مصادر التمويل مساوياً لـ 100% (المجموع الحالي: ${totalPercentage.toFixed(2)}%).`;
                }
                if (errors) errors.push(msg); else if (typeof flasher !== 'undefined' && flasher.error) flasher.error(msg);
                isValid = false;
            }

            return isValid;
        },
        
        validatePreliminaryActivitiesWeights: function(isStrict = false, customErrors = []) {
            if (!isStrict) return true;
            if (this.isOldProject()) return true;

            let isValid = true;
            const container = document.getElementById('preliminary-activities-container');
            const rows = container ? container.querySelectorAll('.activity-row') : [];

            if (rows.length < 1) {
                customErrors.push('الأنشطة التمهيدية: يجب إضافة نشاط تمهيدي واحد على الأقل للمتابعة.');
                return false;
            }

            let totalWeight = 0;
            rows.forEach(row => {
                const weightInp = row.querySelector('.activity-weight-input, [name$="[weight]"], [name*="[weight]"]');
                const w = weightInp ? (parseFloat(weightInp.value) || 0) : 0;
                totalWeight += w;
            });

            const displayedTotalWeight = parseFloat(document.getElementById('weight-total')?.textContent || totalWeight) || totalWeight;
            const checkWeight = Math.abs(displayedTotalWeight - 100) <= 0.01 ? displayedTotalWeight : totalWeight;

            if (Math.abs(checkWeight - 100) > 0.01) {
                if (rows.length === 1) {
                    customErrors.push(`الأنشطة التمهيدية: يجب أن يكون وزن النشاط التمهيدي مساوياً لـ 100% (المجموع الحالي: ${checkWeight.toFixed(2)}%).`);
                } else {
                    customErrors.push(`الأنشطة التمهيدية: يجب أن يكون مجموع أوزان جميع الأنشطة التمهيدية مساوياً لـ 100% (المجموع الحالي: ${checkWeight.toFixed(2)}%).`);
                }
                isValid = false;
            }

            for (const row of rows) {
                const idx = row.dataset.activityIndex;
                const num = row.querySelector('.activity-number')?.textContent || (parseInt(idx || 0) + 1);
                
                const nameInp = row.querySelector(`[name$="[name]"]`);
                if (!nameInp || !nameInp.value.trim()) {
                    customErrors.push(`الأنشطة التمهيدية: يرجى إدخال اسم النشاط رقم ${num}.`);
                    if (nameInp) {
                        nameInp.style.borderColor = '#dc3545';
                        nameInp.style.borderWidth = '2px';
                        nameInp.classList.add('is-invalid');
                    }
                    isValid = false;
                } else {
                    if (nameInp) {
                        nameInp.style.borderColor = '';
                        nameInp.style.borderWidth = '';
                        nameInp.classList.remove('is-invalid');
                    }
                }

                const detailsRow = row.nextElementSibling;
                if (!detailsRow || !detailsRow.classList.contains('activity-details-row')) {
                    customErrors.push(`النشاط رقم ${num}: يجب إضافة إجرائين (2) على الأقل.`);
                    isValid = false;
                    continue;
                }

                const procRows = detailsRow.querySelectorAll('.procedure-row');
                if (procRows.length < 2) {
                    customErrors.push(`النشاط رقم ${num}: يجب إضافة إجرائين (2) على الأقل التابعة للنشاط.`);
                    isValid = false;
                }

                let procWeightSum = 0;
                procRows.forEach(procRow => {
                    const procWeightInp = procRow.querySelector('.procedure-weight-input, [name$="[weight]"], [name*="[procedure_weight]"]');
                    const pw = procWeightInp ? (parseFloat(procWeightInp.value) || 0) : 0;
                    procWeightSum += pw;
                });

                const displayedProcWeight = parseFloat(detailsRow.querySelector('.activity-procedures-weight-total')?.textContent || procWeightSum) || procWeightSum;
                const checkProcWeight = Math.abs(displayedProcWeight - 100) <= 0.01 ? displayedProcWeight : procWeightSum;

                if (procRows.length > 0 && Math.abs(checkProcWeight - 100) > 0.01) {
                    customErrors.push(`النشاط رقم ${num}: مجموع أوزان الإجراءات التابعة يجب أن يكون 100% (المجموع الحالي: ${checkProcWeight.toFixed(2)}%).`);
                    isValid = false;
                }

                procRows.forEach((procRow, pIdx) => {
                    const procNameInp = procRow.querySelector(`[name$="[procedure_name]"], [name$="[name]"]`);
                    if (!procNameInp || !procNameInp.value.trim()) {
                        const pNum = procRow.querySelector('.procedure-number')?.textContent || (pIdx + 1);
                        customErrors.push(`النشاط رقم ${num}، الإجراء رقم ${pNum}: يرجى إدخال اسم الإجراء.`);
                        if (procNameInp) {
                            procNameInp.style.borderColor = '#dc3545';
                            procNameInp.style.borderWidth = '2px';
                            procNameInp.classList.add('is-invalid');
                        }
                        isValid = false;
                    } else {
                        if (procNameInp) {
                            procNameInp.style.borderColor = '';
                            procNameInp.style.borderWidth = '';
                            procNameInp.classList.remove('is-invalid');
                        }
                    }
                });
            }

            return isValid;
        },
        
        validateExecutiveActivitiesWeights: function(isStrict = false, customErrors = []) {
            if (!isStrict) return true;
            if (this.isOldProject()) return true;

            let isValid = true;
            const container = document.getElementById('executive-activities-container');
            const activityRows = container ? container.querySelectorAll('.executive-activity-row') : [];

            // 1. At least 2 Executive Activities
            if (activityRows.length < 2) {
                customErrors.push('الأنشطة التنفيذية: يجب إضافة نشاطين تنفيذيين (2) على الأقل للمتابعة.');
                isValid = false;
            }

            // 2. Sum of weights of ALL Executive Activities = 100%
            let totalWeight = 0;
            activityRows.forEach(row => {
                const weightInp = row.querySelector('.executive-activity-weight, [name$="[weight]"]');
                const w = weightInp ? (parseFloat(weightInp.value) || 0) : 0;
                totalWeight += w;
            });

            const displayedTotalWeight = parseFloat(document.getElementById('executive-weight-total')?.textContent || totalWeight) || totalWeight;
            const checkWeight = Math.abs(displayedTotalWeight - 100) <= 0.01 ? displayedTotalWeight : totalWeight;

            if (activityRows.length > 0 && Math.abs(checkWeight - 100) > 0.01) {
                customErrors.push(`الأنشطة التنفيذية: يجب أن يكون مجموع أوزان جميع الأنشطة التنفيذية مساوياً لـ 100% (المجموع الحالي: ${checkWeight.toFixed(2)}%).`);
                isValid = false;
            }

            // 3. Validate each activity
            activityRows.forEach((activityRow, aIdx) => {
                const activityIndex = activityRow.dataset.activityIndex || aIdx;
                const actNum = activityRow.querySelector('.activity-number')?.textContent || (parseInt(activityIndex) + 1);

                // Activity Name
                const nameInput = activityRow.querySelector('[name$="[name]"]');
                if (!nameInput || !nameInput.value.trim()) {
                    customErrors.push(`الأنشطة التنفيذية: يرجى إدخال اسم النشاط التنفيذي رقم ${actNum}.`);
                    if (nameInput) {
                        nameInput.style.borderColor = '#dc3545';
                        nameInput.style.borderWidth = '2px';
                        nameInput.classList.add('is-invalid');
                    }
                    isValid = false;
                } else {
                    if (nameInput) {
                        nameInput.style.borderColor = '';
                        nameInput.style.borderWidth = '';
                        nameInput.classList.remove('is-invalid');
                    }
                }

                const detailsRow = activityRow.nextElementSibling;
                const actionsCont = detailsRow ? detailsRow.querySelector('.executive-actions-tbody') : null;
                const actionRows = actionsCont ? actionsCont.querySelectorAll('.executive-activity-action-row') : document.querySelectorAll(`.executive-activity-action-row[data-activity-index="${activityIndex}"]`);

                // At least 2 procedures (إجرائين على الأقل)
                if (actionRows.length < 2) {
                    customErrors.push(`النشاط التنفيذي رقم ${actNum}: يجب إضافة إجرائين (2) على الأقل التابعة للنشاط.`);
                    isValid = false;
                }

                // Procedures Weight sum = 100%
                let actionWeightSum = 0;
                actionRows.forEach(actionRow => {
                    const weightInp = actionRow.querySelector('.action-weight-input, [name$="[weight]"]');
                    const w = weightInp ? (parseFloat(weightInp.value) || 0) : 0;
                    actionWeightSum += w;
                });

                const displayedActionWeight = parseFloat(detailsRow?.querySelector('.executive-action-total-weight')?.textContent || actionWeightSum) || actionWeightSum;
                const checkActionWeight = Math.abs(displayedActionWeight - 100) <= 0.01 ? displayedActionWeight : actionWeightSum;

                if (actionRows.length > 0 && Math.abs(checkActionWeight - 100) > 0.01) {
                    customErrors.push(`النشاط التنفيذي رقم ${actNum}: مجموع أوزان الإجراءات التابعة يجب أن يكون 100% (المجموع الحالي: ${checkActionWeight.toFixed(2)}%).`);
                    isValid = false;
                }

                // Check procedures description & financial cost
                actionRows.forEach((actionRow, actIdx) => {
                    const actionIndex = actionRow.dataset.actionIndex || actIdx;
                    const actionNum = actionRow.querySelector('.action-number')?.textContent || (parseInt(actionIndex) + 1);

                    const actionTextarea = actionRow.querySelector('textarea[name*="[action]"], input[name*="[action]"]');
                    if (!actionTextarea || !actionTextarea.value.trim()) {
                        customErrors.push(`النشاط التنفيذي رقم ${actNum}، الإجراء رقم ${actionNum}: يرجى إدخال وصف الإجراء.`);
                        if (actionTextarea) {
                            actionTextarea.style.borderColor = '#dc3545';
                            actionTextarea.style.borderWidth = '2px';
                            actionTextarea.classList.add('is-invalid');
                        }
                        isValid = false;
                    } else {
                        if (actionTextarea) {
                            actionTextarea.style.borderColor = '';
                            actionTextarea.style.borderWidth = '';
                            actionTextarea.classList.remove('is-invalid');
                        }
                    }

                    // Check Financial Cost per Procedure (تكليف مالي واحد على الأقل لكل إجراء)
                    const actionDetailsId = `action-details-${activityIndex}-${actionIndex}`;
                    const actionDetailsRow = document.getElementById(actionDetailsId) || actionRow.nextElementSibling;
                    let costCount = 0;

                    if (actionDetailsRow) {
                        const costsContainer = actionDetailsRow.querySelector('.action-costs-container');
                        if (costsContainer) {
                            const costItems = costsContainer.querySelectorAll('.executive-cost-row, [name*="[costs]"]');
                            costCount = costItems.length;
                        }
                    }

                    const costBadge = actionRow.querySelector('.costs-count-badge');
                    if (costBadge) {
                        const badgeCount = parseInt(costBadge.textContent || '0') || 0;
                        if (badgeCount > costCount) costCount = badgeCount;
                    }

                    if (costCount < 1) {
                        customErrors.push(`النشاط التنفيذي رقم ${actNum}، الإجراء رقم ${actionNum}: يجب إضافة تكليف مالي واحد على الأقل.`);
                        isValid = false;
                        if (costBadge) {
                            costBadge.classList.remove('bg-warning');
                            costBadge.classList.add('bg-danger');
                        }
                    } else {
                        if (costBadge) {
                            costBadge.classList.remove('bg-danger');
                            costBadge.classList.add('bg-warning');
                        }
                    }
                });
            });

            return isValid;
        },
        
        highlightInvalidFields: function(stepElement) {
            const allInputs = stepElement.querySelectorAll('input[required]:not([type="hidden"]):not([type="search"]):not([readonly]), select[required]:not([readonly]), textarea[required]:not([readonly])');
            const inputs = Array.from(allInputs).filter(field => {
                const style = window.getComputedStyle(field);
                if (style.display === 'none' || style.visibility === 'hidden') return false;
                let parent = field.parentElement;
                while (parent && parent !== stepElement) {
                    if (window.getComputedStyle(parent).display === 'none') return false;
                    parent = parent.parentElement;
                }
                return true;
            });
            
            let firstInvalid = null, hasError = false;
            const validatedGroups = new Set();
            const errorMessagesSet = new Set();

            inputs.forEach(field => {
                let isInvalid = false;
                if (field.type === 'radio' || field.type === 'checkbox') {
                    const name = field.name;
                    if (!name || validatedGroups.has(name)) return;
                    validatedGroups.add(name);
                    if (!stepElement.querySelector(`input[name="${name}"]:checked`)) {
                        isInvalid = true;
                        stepElement.querySelectorAll(`input[name="${name}"]`).forEach(el => {
                            el.classList.add('is-invalid');
                            el.style.outline = '2px solid #dc3545';
                            el.style.outlineOffset = '2px';
                        });
                    }
                } else {
                    isInvalid = !field.value || (field.type === 'number' && field.value === '') || (field.tagName === 'SELECT' && field.value === "");
                }

                if (isInvalid) {
                    hasError = true;
                    let fieldName = '';
                    const label = field.closest('div')?.querySelector('label');
                    if (label) fieldName = label.innerText.replace(/\*/g, '').trim();
                    if (!fieldName) fieldName = field.placeholder || field.name || 'حقل مطلوب';
                    errorMessagesSet.add(`الحقل "${fieldName}" مطلوب.`);
                    if (field.type !== 'radio' && field.type !== 'checkbox') {
                        field.style.borderColor = '#dc3545';
                        field.style.borderWidth = '2px';
                        field.classList.add('is-invalid');
                    }
                    
                    const isS2 = field.classList.contains('select2-hidden-accessible');
                    if (isS2) {
                        const s2Container = field.nextElementSibling;
                        if (s2Container && s2Container.classList.contains('select2-container')) {
                            const sel = s2Container.querySelector('.select2-selection');
                            if (sel) { sel.style.borderColor = '#dc3545'; sel.style.borderWidth = '2px'; }
                        }
                    }
                    
                    if (!firstInvalid) firstInvalid = field;

                    const removeError = () => {
                        if (field.type === 'radio' || field.type === 'checkbox') {
                            stepElement.querySelectorAll(`input[name="${field.name}"]`).forEach(el => {
                                el.classList.remove('is-invalid');
                                el.style.outline = '';
                                el.style.outlineOffset = '';
                            });
                        } else {
                            field.style.borderColor = '';
                            field.style.borderWidth = '';
                            field.classList.remove('is-invalid');
                        }
                        if (isS2) {
                            const s2Container = field.nextElementSibling;
                            if (s2Container && s2Container.classList.contains('select2-container')) {
                                const sel = s2Container.querySelector('.select2-selection');
                                if (sel) { sel.style.borderColor = ''; sel.style.borderWidth = ''; }
                            }
                        }
                        field.removeEventListener('input', removeError);
                        field.removeEventListener('change', removeError);
                    };
                    
                    field.addEventListener('input', removeError);
                    field.addEventListener('change', removeError);
                }
            });
            
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => {
                    if (firstInvalid.classList.contains('select2-hidden-accessible') && window.$ && typeof $.fn !== 'undefined') {
                        $(firstInvalid).select2('open');
                    } else {
                        firstInvalid.focus();
                    }
                }, 500);
            }
            return { hasError: hasError, errorMessages: Array.from(errorMessagesSet), firstInvalid: firstInvalid };
        },
        
        isStepCompleted: function(step) { return step < this.currentStep; },
        
        normalizeFormData: function(formData) { return formData; },

        updateDraftStatusUI: function(isComplete, missingSections, completionPct) {
            window.isDraftCompleteState = !!isComplete;
            window.isCompletedDraft = isComplete ? 'true' : 'false';

            // ── Progress bar ─────────────────────────────────────────
            const pct = (completionPct !== undefined && completionPct !== null)
                ? completionPct
                : (isComplete ? 100 : null);

            const progressBar = document.getElementById('draftProgressBar');
            const pctLabel    = document.getElementById('draftPctLabel');
            if (progressBar && pct !== null) {
                progressBar.style.width = pct + '%';
                progressBar.setAttribute('aria-valuenow', pct);
                progressBar.className = 'progress-bar ' +
                    (pct === 100 ? 'bg-success' : pct >= 60 ? 'bg-warning' : 'bg-danger');
                if (pctLabel) pctLabel.textContent = pct + '%';
            }

            // ── Alert card ───────────────────────────────────────────
            const alertContainer = document.getElementById('completedDraftAlert');
            const alertText      = document.getElementById('completedDraftAlertText');
            const alertIcon      = document.getElementById('draftAlertIcon');
            const missingList    = document.getElementById('missingSectionsList');

            if (alertContainer) {
                if (isComplete) {
                    alertContainer.className = 'alert alert-success border-0 shadow-sm';
                    alertContainer.style.borderRight = '4px solid #10b981';
                    if (alertText) alertText.textContent = 'المسودة مكتملة – تم تعبئة جميع البيانات المطلوبة بنجاح.';
                    if (alertIcon) alertIcon.className = 'fas fa-check-double text-success me-3 fa-lg mt-1';
                    if (missingList) missingList.style.display = 'none';
                } else {
                    alertContainer.className = 'alert alert-warning border-0 shadow-sm';
                    alertContainer.style.borderRight = '4px solid #f59e0b';
                    if (alertText) alertText.textContent = 'المسودة غير مكتملة – يرجى استكمال الأقسام التالية:';
                    if (alertIcon) alertIcon.className = 'fas fa-exclamation-circle text-warning me-3 fa-lg mt-1';

                    if (missingList && Array.isArray(missingSections) && missingSections.length > 0) {
                        missingList.innerHTML = missingSections.map(s => `<li>${s}</li>`).join('');
                        missingList.style.display = 'block';
                    } else if (missingList) {
                        missingList.style.display = 'none';
                    }
                }
            }

            // ── Save button label on step 7 ──────────────────────────
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn && this.currentStep === 7) {
                if (isComplete) {
                    saveBtn.innerHTML = '<i class="fas fa-check-double me-1"></i> حفظ كمسودة مكتملة';
                    saveBtn.className = 'btn btn-success px-4 btn-navigation';
                } else {
                    saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ كمسودة';
                    saveBtn.className = 'btn btn-light border btn-navigation';
                }
            }
        },

        updateSaveIndicator: function(status, isDraftComplete = null) {
            const indicator = document.getElementById('saveIndicator');
            if (!indicator) return;
            const isComplete = (isDraftComplete !== null) ? isDraftComplete : (window.isDraftCompleteState || window.projectStatus === 'completed_draft');
            const timeStr = this.lastSavedAt ? this.lastSavedAt.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) : '';
            switch(status) {
                case 'saving':
                    indicator.innerHTML = '<i class="fas fa-sync fa-spin me-1"></i>جاري الحفظ تلقائياً...';
                    indicator.className = 'save-indicator text-info';
                    break;
                case 'saved':
                    if (isComplete) {
                        indicator.innerHTML = `<i class="fas fa-check-double me-1 text-success"></i>تم حفظ المسودة المكتملة ${timeStr}`;
                        indicator.className = 'save-indicator text-success fw-bold';
                    } else {
                        indicator.innerHTML = `<i class="fas fa-check-circle me-1"></i>تم حفظ المسودة ${timeStr}`;
                        indicator.className = 'save-indicator text-success';
                    }
                    break;
                case 'error':
                    indicator.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>فشل الحفظ التلقائي';
                    indicator.className = 'save-indicator text-danger';
                    break;
            }
        },

        showIncompleteDraftError: function(data) {
            const fields = (data && Array.isArray(data.missing_fields)) ? data.missing_fields : [];
            const list = fields.length ? fields.join('، ') : 'حقول غير محددة';
            AppUtils.Utils.showToast('المشروع غير مكتمل. الحقول الناقصة: ' + list, 'error');
        },

        handleServerErrors: function(errors) {
            if (!errors) return;
            document.querySelectorAll('.is-invalid').forEach(el => { el.classList.remove('is-invalid'); el.style.borderColor = ''; });
            let firstErrorField = null;
            let errorMessages = [];
            Object.keys(errors).forEach(key => {
                const field = document.querySelector(`[name="${key}"], [name^="${key}["]`);
                if (field) {
                    field.classList.add('is-invalid');
                    field.style.borderColor = '#dc3545';
                    if (!firstErrorField) firstErrorField = field;
                }
                if (Array.isArray(errors[key])) {
                    errorMessages = errorMessages.concat(errors[key]);
                } else {
                    errorMessages.push(errors[key]);
                }
            });
            this.showFlashError(errorMessages);
            if (firstErrorField) firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },

        showFlashError: function(messages) {
            this.clearFlashError();
            if (!messages || messages.length === 0) return;

            if (typeof flasher !== 'undefined' && flasher.error) {
                messages.forEach(msg => {
                    flasher.error(msg);
                });
            } else if (typeof AppUtils !== 'undefined' && AppUtils.Utils && AppUtils.Utils.showToast) {
                messages.forEach(msg => {
                    AppUtils.Utils.showToast(msg, 'error');
                });
            }
        },

        clearFlashError: function() {
            const existing = document.getElementById('step-error-container');
            if (existing) existing.remove();
        },

        // ============================================================
        // submitForm (غير مستخدم حالياً، نحتفظ به للتوافق)
        // ============================================================
        submitForm: function() {
            if (this.validateCurrentStep(true)) {
                if (this.isOldProject()) {
                    const totalCostInput = document.querySelector('[name="project_cost[total_cost]"]') || document.getElementById('step1_total_cost') || document.getElementById('total_project_cost');
                    if (!totalCostInput || !totalCostInput.value || parseFloat(totalCostInput.value) < 0) {
                        AppUtils.Utils.showToast('يرجى إدخال إجمالي تكلفة المشروع أولاً للاعتماد النهائي', 'warning');
                        if (totalCostInput) totalCostInput.focus();
                        return;
                    }
                }
                const form = document.getElementById('projectForm');
                if (form) {
                    const statusInput = form.querySelector('input[name="status"]');
                    if (statusInput) statusInput.value = 'final';
                    AppUtils.Utils.showToast('جاري إرسال المشروع وتهيئة مراحل الاعتماد... قد يستغرق هذا بضع ثوانٍ', 'info');
                    this.showLoadingState();
                    this.showStep7LoadingOverlay();
                    this.saveStep((isSuccess) => {
                        if (isSuccess) {
                            const projectId = document.getElementById('project_id').value;
                            window.location.href = projectId ? `/projects/${projectId}` : '/projects';
                        } else {
                            const overlay = document.getElementById('step7-loading-overlay');
                            if (overlay) overlay.remove();
                            AppUtils.Utils.showToast('لا يمكن إرسال المشروع لوجود أخطاء في البيانات، يرجى مراجعة الحقول المطلوبة.', 'error');
                        }
                    }, true, false, 'next', false); // isDraft = false
                }
            }
        },

        showStep7LoadingOverlay: function() {
            const existing = document.getElementById('step7-loading-overlay');
            if (existing) return;
            const overlay = document.createElement('div');
            overlay.id = 'step7-loading-overlay';
            overlay.style.cssText = 'position:fixed;inset:0;background:rgba(255,255,255,0.88);z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1rem;backdrop-filter:blur(4px);';
            overlay.innerHTML = `
                <div style="text-align:center;">
                    <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status"></div>
                    <div style="margin-top:1rem;font-size:1.1rem;font-weight:700;color:#1e40af;">جاري حفظ المشروع وإعداد مراحل الاعتماد...</div>
                    <div style="font-size:0.85rem;color:#64748b;margin-top:0.5rem;">يرجى الانتظار، لا تغلق الصفحة</div>
                </div>
            `;
            document.body.appendChild(overlay);
        },

        saveOldProject: function(asFinal = false) {
            const projectName = document.querySelector('[name="project_name"]');
            if (!projectName || !projectName.value.trim()) {
                AppUtils.Utils.showToast('يرجى إدخال اسم المشروع أولاً', 'warning');
                if (projectName) projectName.focus();
                return;
            }
            if (asFinal) {
                const totalCostInput = document.querySelector('[name="project_cost[total_cost]"]') || document.getElementById('step1_total_cost') || document.getElementById('total_project_cost');
                if (!totalCostInput || !totalCostInput.value || parseFloat(totalCostInput.value) < 0) {
                    AppUtils.Utils.showToast('يرجى إدخال إجمالي تكلفة المشروع أولاً للاعتماد النهائي', 'warning');
                    if (totalCostInput) totalCostInput.focus();
                    return;
                }
            }
            const statusInput = document.querySelector('input[name="status"]');
            if (statusInput) statusInput.value = asFinal ? 'final' : 'draft';

            AppUtils.Utils.showToast(asFinal ? 'جاري حفظ المشروع...' : 'جاري حفظ المسودة...', 'info');
            this.showLoadingState();
            this.saveStep((isSuccess) => {
                if (isSuccess) {
                    const id = document.getElementById('project_id') ? document.getElementById('project_id').value : null;
                    setTimeout(() => window.location.href = id ? `/projects/${id}` : '/projects', 500);
                } else {
                    AppUtils.Utils.showToast('حدث خطأ أثناء حفظ المشروع', 'error');
                }
            }, true, false, asFinal ? 'final' : 'draft', true); // دائماً مسودة
        },
        
        showLoadingState: function() {
            document.querySelectorAll('#nextBtn, #prevBtn, #saveBtn').forEach(btn => {
                btn.disabled = true;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري المعالجة...';
                const timeoutMs = this.currentStep === 7 ? 60000 : 10000;
                setTimeout(() => { btn.disabled = false; btn.innerHTML = originalHTML; }, timeoutMs);
            });
        },
        
        scrollToTop: function() {
            const scrollContainer = document.getElementById('mainScrollContainer') || document.querySelector('.content-scrollable-area');
            if (scrollContainer) scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
        },
        
        scrollToElement: function(element) {
            const scrollContainer = document.getElementById('mainScrollContainer') || document.querySelector('.content-scrollable-area');
            if (scrollContainer && element) {
                const containerTop = scrollContainer.getBoundingClientRect().top;
                const elementTop = element.getBoundingClientRect().top;
                scrollContainer.scrollTo({ top: scrollContainer.scrollTop + (elementTop - containerTop) - 100, behavior: 'smooth' });
            }
        },
        
        initializeTables: function() {
            this.debug('Initializing dynamic tables for full width...');
            this.initializePreliminaryActivities();
            this.initializeRisksTable();
            this.initializeFinancingTable();
            this.initializeExecutiveActivities();
            this.debug('✅ Full width tables initialized');
        },
        
        // Stub methods (Handled by partial scripts)
        initializePreliminaryActivities: function() {},
        initializeRisksTable: function() {},
        initializeFinancingTable: function() {},
        initializeExecutiveActivities: function() {},
        addPreliminaryActivity: function() {},
        getPreliminaryActivityTemplate: function(activityIndex) {},
        addPreliminaryAction: function(activityIndex) {},
        getPreliminaryActionTemplate: function(activityIndex, actionIndex) {},
        addPreliminaryCost: function(activityIndex, actionIndex = null) {},
        getPreliminaryCostTemplate: function(activityIndex, actionIndex, costIndex) {},
        addRisk: function() {},
        getRiskTemplate: function(riskIndex) {},
        addFinancing: function() {},
        getFinancingTemplate: function(financingIndex) {},
        addExecutiveActivity: function() {},
        getExecutiveActivityTemplate: function(activityIndex) {},
        addExecutiveAction: function(activityIndex) {},
        getExecutiveActionTemplate: function(activityIndex, actionIndex) {},
        addExecutiveCost: function(activityIndex, actionIndex = null) {},
        getExecutiveCostTemplate: function(activityIndex, actionIndex, costIndex) {},
        checkEmptyActivities: function() {},
        bindActivityEvents: function(activityIndex) {},
        bindActionEvents: function(activityIndex, actionIndex) {},
        bindCostEvents: function(activityIndex, actionIndex, costIndex) {},
        calculateTotalCost: function(activityIndex, actionIndex) {},
        calculateActivityTotalCost: function(activityIndex) {},
        calculateActivityWeight: function(activityIndex) {},
        calculateActionWeight: function(activityIndex, actionIndex) {},
        updateTotalProjectCost: function() {},
        updateTotalProjectWeight: function() {},
        
        debug: function(message, data = null) {
            if (this.debugMode) {
                if (data !== null) console.log(`[FormManager] ${message}`, data);
                else console.log(`[FormManager] ${message}`);
            }
        }
    };
    
    FormManager.init();
    window.FormManager = FormManager;
    window.addEventListener('resize', () => FormManager.updateUI());
    
    // Debugging Helpers
    window.checkEntityData = function() {
        console.log('🔍 ENTITY DATA CHECK');
        const sup = document.querySelectorAll('#supervisingAuthoritiesTable tbody tr[data-entity-index]').length;
        const imp = document.querySelectorAll('#implementingEntitiesTable tbody tr[data-entity-index]').length;
        const part = document.querySelectorAll('#participatingEntitiesTable tbody tr[data-entity-index]').length;
        console.log(`Sup: ${sup}, Imp: ${imp}, Part: ${part}`);
    };

    window.runDiagnostics = function() {
        const panel = document.getElementById('diagnosticPanel');
        const content = document.getElementById('diagnosticContent');
        if (!panel || !content) return;
        panel.classList.remove('d-none');
        
        let report = `• Current Step: ${FormManager.currentStep}\n• Project ID: ${FormManager.projectId || 'None'}\n`;
        const entities = {
            supervising: document.querySelectorAll('#supervisingAuthoritiesTable tbody tr[data-entity-index]').length,
            implementing: document.querySelectorAll('#implementingEntitiesTable tbody tr[data-entity-index]').length,
            participating: document.querySelectorAll('#participatingEntitiesTable tbody tr[data-entity-index]').length
        };
        report += `• DOM Entities: ${entities.supervising} Sup, ${entities.implementing} Imp, ${entities.participating} Part\n`;
        
        if (typeof Select2 === 'undefined' && typeof $.fn.select2 === 'undefined') report += '⚠️ Select2 NOT LOADED\n';
        if (typeof AppUtils === 'undefined') report += '⚠️ AppUtils NOT LOADED\n';
        
        content.innerText = report;
    };

    document.addEventListener('stepChanged', function(e) {
        if (e.detail.step === 3) setTimeout(window.runDiagnostics, 500);
    });

    const manualFixBtn = document.getElementById('manualFixBtn');
    if (manualFixBtn) {
        manualFixBtn.addEventListener('click', function() {
            AppUtils.Utils.showToast('إعادة تهيئة الخطوة 3...', 'info');
            if (window.SpecificObjectivesManager) window.SpecificObjectivesManager.init();
        });
    }

    document.addEventListener('keydown', function(event) {
        if (event.ctrlKey && event.altKey && event.key === 'd') {
            const panel = document.getElementById('diagnosticPanel');
            if (panel) panel.classList.toggle('d-none');
        }
    });

    // ============================================================
    // Auto-validate draft on load (resume flow only)
    // Fires only when a saved project ID exists in the form
    // ============================================================
    (function autoValidateDraftOnLoad() {
        const projectIdField = document.getElementById('project_id');
        const projectId = projectIdField ? projectIdField.value : null;
        if (!projectId) return; // brand-new project: nothing to validate yet

        const currentStatus = (document.querySelector('input[name="status"]') || {}).value || 'draft';
        if (!['draft', 'completed_draft'].includes(currentStatus)) return;

        const spinner = document.getElementById('draftValidatingSpinner');
        if (spinner) spinner.style.display = 'inline-block';

        fetch(`/projects/${projectId}/validate-draft`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(r => r.json())
        .then(data => {
            if (spinner) spinner.style.display = 'none';
            if (!data.success || data.skipped) return;

            window.projectStatus = data.status;
            window.isCompletedDraft = data.is_complete ? 'true' : 'false';
            window.isDraftCompleteState = !!data.is_complete;

            const statusInput = document.querySelector('input[name="status"]');
            if (statusInput && statusInput.value !== 'final') {
                statusInput.value = data.status;
            }

            if (typeof FormManager !== 'undefined' && FormManager.updateDraftStatusUI) {
                FormManager.updateDraftStatusUI(
                    data.is_complete,
                    data.missing_sections || [],
                    data.completion_pct
                );
                FormManager.updateSaveIndicator('saved', data.is_complete);
            }
        })
        .catch(() => {
            if (spinner) spinner.style.display = 'none';
        });
    })();
});
</script>

<script>
    // Bind Hijri Dates
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('start_date_gregorian')) bindGregorianToHijri("start_date_gregorian", "start_date_hijri");
        if (document.getElementById('end_date_gregorian')) bindGregorianToHijri("end_date_gregorian", "end_date_hijri");
        if (document.getElementById('actual_start_g')) bindGregorianToHijri("actual_start_g", "actual_start_h");
        if (document.getElementById('actual_end_g')) bindGregorianToHijri("actual_end_g", "actual_end_h");
    });
</script>

@include('projects.partials.weight-calculator')

@endsection

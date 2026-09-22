 @extends('layouts.app')

@section('styles')
<style>
    /* 1. إعدادات الصفحة الأساسية لتملأ الشاشة */
    html, body {
        height: 100%;
        margin: 0;
        overflow-y: auto; /* السماح بالتمرير عند الحاجة لضمان رؤية الأزرار */
        background-color: #f4f6f9;
        font-family: 'Cairo', system-ui, -apple-system, sans-serif;
    }

    /* حاوية الصفحة الرئيسية بتقنية Flexbox */
    .app-layout-wrapper {
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 160px); /* ضبط الارتفاع ليتناسب مع الشاشة */
        width: 100%;
    }

    /* 2. منطقة المحتوى القابلة للتمرير */
    .content-scrollable-area {
        flex: 1; /* يأخذ المساحة المتبقية تلقائياً */
        overflow-y: auto;
        padding: 1.5rem;
        scroll-behavior: smooth;
        background-color: #fff;
        border-radius: 12px 12px 0 0;
        margin: 0 1rem;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.03);
    }

    /* تحسين شكل شريط التمرير */
    .content-scrollable-area::-webkit-scrollbar {
        width: 6px;
    }
    .content-scrollable-area::-webkit-scrollbar-track {
        background: transparent;
    }
    .content-scrollable-area::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .content-scrollable-area::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* 3. شريط الخطوات (Step Indicator) */
    .steps-container {
        padding: 1rem 1rem 0 1rem;
        background-color: #f4f6f9;
        z-index: 10;
        flex-shrink: 0; /* منع التقلص */
    }

    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 15px;
        padding-bottom: 15px;
        overflow-x: auto;
        scrollbar-width: none; /* إخفاء السكرول بار */
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
    
    .step-item.completed .step-icon {
        color: #16a34a;
    }
    
    .step-icon {
        font-size: 1.2rem;
        margin-bottom: 5px;
    }
    
    .step-text {
        font-size: 0.8rem;
        font-weight: 700;
        white-space: nowrap;
    }

    /* 4. تنسيق النماذج (Forms) */
    .step {
        display: none;
        animation: slideIn 0.3s ease-out;
        margin-bottom: 1.5rem;
    }
    
    .step.active {
        display: block;
    }

    .form-section {
        background: #fff;
        margin-bottom: 2rem;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
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

    /* 5. الجداول (Tables) */
    .project-table-wrapper {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 15px;
    }
    
    .project-table-header {
        background: #f1f5f9;
        padding: 12px 15px;
        font-weight: 700;
        color: #334155;
        border-bottom: 1px solid #e2e8f0;
    }

    /* 6. التذييل (Footer Action Bar) */
    .action-footer {
        background: #fff;
        padding: 1.25rem 1.5rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        z-index: 100; /* رفع مستوى الطبقة لضمان الظهور */
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

    /* Grid layout for forms */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .form-grid .form-group {
        margin-bottom: 0;
    }

    /* Compact table styles */
    .compact-table {
        font-size: 0.875rem;
    }

    .compact-table th {
        padding: 0.5rem !important;
        font-size: 0.8rem;
    }

    .compact-table td {
        padding: 0.5rem !important;
    }

    .compact-table .form-control,
    .compact-table .form-select {
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 0.5rem;
        font-size: 0.8125rem;
    }

    /* Unified project table styling */
    .project-table-container {
        margin-bottom: 1.5rem;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05); /* slightly increased shadow */
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    /* Auto-expanding textarea styling */
    textarea.auto-expand {
        resize: none;
        overflow: hidden;
        min-height: 38px; /* Match standard input height */
        line-height: 1.5;
        transition: height 0.2s ease;
    }

    /* Column Width Helpers */
    .col-min-150 { min-width: 150px; }
    .col-min-200 { min-width: 200px; }
    .col-min-250 { min-width: 250px; }
    .col-min-300 { min-width: 300px; }
    .col-min-400 { min-width: 400px; }
    
    .w-5  { width: 5%; }
    .w-10 { width: 10%; }
    .w-15 { width: 15%; }
    .w-20 { width: 20%; }
    .w-25 { width: 25%; }
    .w-30 { width: 30%; }
    .w-35 { width: 35%; }
    

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
        padding: 15px;
        border-radius: 0 0 10px 10px;
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

    .project-table-wrapper tbody tr:nth-child(even) td {
        background: #f8fafc;
    }

    .project-table-wrapper tbody tr:hover td {
        background-color: rgba(191, 219, 254, 0.15);
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

    .project-table-wrapper .badge {
        border-radius: 999px;
        font-size: 0.75rem;
        padding: 5px 10px;
        background: rgba(59, 130, 246, 0.1);
        color: #1d4ed8;
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

    .project-table-wrapper .project-empty-row td {
        background: #ffffff;
        color: #64748b;
        font-size: 0.85rem;
    }

    .project-table-wrapper .project-empty-row i {
        color: rgba(100, 116, 139, 0.6);
    }

    .project-table-wrapper .project-action-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
        justify-content: center;
    }

    .project-table-wrapper .project-btn-primary {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        color: #ffffff;
    }

    .project-table-wrapper .project-btn-danger {
        background: linear-gradient(135deg, #fda4af 0%, #f87171 100%);
        color: #ffffff;
    }

    .project-table-wrapper .project-btn-primary i,
    .project-table-wrapper .project-btn-danger i {
        margin-inline-end: 4px;
        font-size: 0.8rem;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .content-scrollable-area {
            padding: 1rem;
            margin: 0; /* إزالة الهوامش الجانبية للجوال */
            border-radius: 0;
        }
        
        .step-indicator {
            justify-content: flex-start; /* السماح بالسكرول الأفقي */
            padding-bottom: 10px;
        }
        
        .step-item {
            min-width: 85px;
            padding: 8px;
        }
        
        .step-text { font-size: 0.7rem; }
        .step-icon { font-size: 1rem; }
        
        .action-footer {
            padding: 10px;
            flex-direction: column-reverse;
            gap: 15px;
        }
        
        .action-footer .d-flex {
            width: 100%;
            justify-content: space-between;
        }

        .progress-wrapper {
            margin: 0;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .btn-navigation {
            min-width: 100px;
            padding: 8px 15px;
            font-size: 0.85rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        
        .project-table-wrapper {
            padding: 10px;
        }
        
        .project-table-wrapper th,
        .project-table-wrapper td {
            padding: 8px 10px;
            font-size: 0.8rem;
        }
        
        .section-title {
            font-size: 1rem;
            margin-bottom: 12px;
        }
    }

    @media (max-width: 576px) {
        .step-item {
            min-width: 80px;
            padding: 8px 10px;
        }
        
        .step-icon {
            font-size: 1rem;
            margin-inline-end: 4px;
        }
        
        .step-text {
            font-size: 0.75rem;
        }
        
        .btn-navigation {
            min-width: 90px;
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .action-footer .d-flex {
            flex-direction: column;
            gap: 10px;
        }
        
        .action-footer .d-flex > div {
            width: 100%;
            display: flex;
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="app-layout-wrapper">
    
    <div class="steps-container">
        @php $initialStep = isset($lastSavedStep) ? (int)$lastSavedStep : 1; @endphp
        <nav class="step-indicator">
            @foreach([
                ['icon' => 'fa-info-circle', 'text' => 'بيانات المشروع '],
                ['icon' => 'fa-clipboard-list', 'text' => 'تفاصيل المشروع '],
                ['icon' => 'fa-shield-alt', 'text' => 'المخاطر والجهات '],
                ['icon' => 'fa-play', 'text' => 'الانشطة التمهيدية '],
                ['icon' => 'fa-tasks', 'text' => 'الانشطة التنفيذية '],
                ['icon' => 'fa-coins', 'text' => 'التمويلات والتكلفة  '],
                ['icon' => 'fa-check-double', 'text' => 'المراجعة']
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

    <div class="content-scrollable-area" id="mainScrollContainer">
        @can('project-requests.create')
        <form id="projectForm" action="{{ route('project-requests.store') }}" method="POST" novalidate>
        @endcan
            @csrf
            <input type="hidden" name="status" value="draft">
            <input type="hidden" id="request_id" name="request_id" value="{{ $projectRequest->id ?? '' }}">
            <input type="hidden" id="isDraft" value="{{ (isset($isDraft) && $isDraft) ? 'true' : 'false' }}">
            <input type="hidden" id="lastSavedStep" value="{{ $lastSavedStep ?? 1 }}">

            <section class="step {{ $initialStep == 1 ? 'active' : '' }}" data-step="1">
                @include('projects.partials._basic_info', ['project' => $projectRequest])
            </section>

            <section class="step {{ $initialStep == 2 ? 'active' : '' }}" data-step="2">
                @include('projects.partials._project_details', ['project' => $projectRequest])
                <div class="form-section mt-4">
                    <h4 class="section-title"><i class="fas fa-bullseye me-2"></i>أهداف المشروع</h4>
                    @include('projects.partials._project_objective', ['project' => $projectRequest])
                </div>
            </section>

            <section class="step {{ $initialStep == 3 ? 'active' : '' }}" data-step="3">
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-exclamation-triangle me-2"></i>إدارة المخاطر</h4>
                    @include('projects.partials._risks', ['project' => $projectRequest])
                </div>
                <div class="form-section mt-4">
                    <h4 class="section-title"><i class="fas fa-building me-2"></i>الجهات</h4>
                    @include('projects.partials.tables.supervising_authorities', ['project' => $projectRequest])
                    @include('projects.partials.tables.implementing-entities', ['project' => $projectRequest])
                    @include('projects.partials.tables.participating-entities', ['project' => $projectRequest])
                </div>
            </section>

            <section class="step {{ $initialStep == 4 ? 'active' : '' }}" data-step="4">
                <h4 class="section-title"><i class="fas fa-play-circle me-2"></i>الأنشطة التمهيدية</h4>
                @include('projects.partials.tables.preliminary.activities', [
                    'project' => $projectRequest,
                    'financialItems' => \App\Models\FinancialItem::where('is_active', true)->orderBy('name')->get(),
                    'units' => \App\Models\Unit::orderBy('unit_name')->get()
                ])
                @include('projects.partials.tables.preliminary.preliminary-financial-summary', ['project' => $projectRequest])
            </section>

            <section class="step {{ $initialStep == 5 ? 'active' : '' }}" data-step="5">
                <h4 class="section-title"><i class="fas fa-cogs me-2"></i>الأنشطة التنفيذية</h4>
                @include('projects.partials.tables.executive.activities', [
                    'project' => $projectRequest,
                    'financialItems' => \App\Models\FinancialItem::where('is_active', true)->orderBy('name')->get(),
                    'units' => \App\Models\Unit::orderBy('unit_name')->get()
                ])
                @include('projects.partials.tables.executive.executive-financial-summary', ['project' => $projectRequest])
            </section>

            <section class="step {{ $initialStep == 6 ? 'active' : '' }}" data-step="6">
                @include('projects.partials._financing', ['project' => $projectRequest])
                @include('projects.partials._project_cost', ['project' => $projectRequest])
            </section>

            <section class="step {{ $initialStep == 7 ? 'active' : '' }}" data-step="7">
                @include('projects.partials._review', ['project' => $projectRequest])
            </section>
        </form>
    </div>

    <footer class="action-footer">
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
            <button type="button" id="saveBtn" class="btn btn-light border btn-navigation">
                <i class="fas fa-save text-muted me-1"></i> مسودة
            </button>
            <button type="button" id="nextBtn" class="btn btn-primary btn-navigation px-4">
                التالي <i class="fas fa-chevron-left me-1"></i>
            </button>
        </div>
    </footer>




</div>
@endsection
@section('scripts')
<script src="{{ asset('js/hijri-converter.js') }}"></script>
<script>
    // Auto-expand textarea script
    document.addEventListener('input', function (event) {
        if (event.target.tagName.toLowerCase() !== 'textarea' || !event.target.classList.contains('auto-expand')) return;
        autoExpand(event.target);
    });

    function autoExpand(field) {
        // Reset field height
        field.style.height = 'inherit';

        // Calculate the height
        var computed = window.getComputedStyle(field);
        var height = parseInt(computed.getPropertyValue('border-top-width'), 10)
                     + parseInt(computed.getPropertyValue('padding-top'), 10)
                     + field.scrollHeight
                     + parseInt(computed.getPropertyValue('padding-bottom'), 10)
                     + parseInt(computed.getPropertyValue('border-bottom-width'), 10);

        field.style.height = height + 'px';
    }
    
    // Expand on load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('textarea.auto-expand').forEach(function(textarea) {
            autoExpand(textarea);
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Global permissions and status context
    window.reviewerType = "{{ $reviewerType ?? 'general' }}";
    window.projectStatus = "{{ $projectStatus ?? 'draft' }}";
    window.isFinancialReview = window.projectStatus === 'financial_review';
    
    const FormManager = {
        currentStep: 1,
        totalSteps: 7,
        isDraft: false,
        
        projectId: '',
        
        debug: function(message, data = null) {
            console.log(`🔧 FormManager: ${message}`, data || '');
        },

        init: function() {
            this.debug('Initializing FormManager...');
            
            // Load draft resume settings if available
            this.loadDraftSettings();
            
            this.bindEvents();
            this.updateUI();
            this.initializeTables();
            this.updateProgressBar();
            this.debug('✅ Form Manager Initialized');
            this.setupDynamicRowObserver();
        },
        
        loadDraftSettings: function() {
            // Check if this is a draft resume
            const isDraftField = document.getElementById('isDraft');
            const lastSavedStepField = document.getElementById('lastSavedStep');
            const projectIdField = document.getElementById('project_id');
            
            if (isDraftField && isDraftField.value === 'true') {
                this.isDraft = true;
                this.debug('✅ Draft resume detected');
                
                // Set currentStep to lastSavedStep if resuming draft
                if (lastSavedStepField && lastSavedStepField.value) {
                    this.currentStep = parseInt(lastSavedStepField.value);
                    this.debug(`Current step set to last saved step: ${this.currentStep}`);
                }
                
                const requestIdField = document.getElementById('request_id');
                if (requestIdField && requestIdField.value) {
                    this.projectId = requestIdField.value;
                    this.debug(`Request ID set: ${this.projectId}`);
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
            if (container) {
                observer.observe(container, { childList: true, subtree: true });
            }
        },
        
        bindEvents: function() {
            this.debug('Binding events...');
            
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const saveBtn = document.getElementById('saveBtn');
            
            this.debug('Navigation buttons found:', {
                nextBtn: !!nextBtn,
                prevBtn: !!prevBtn,
                saveBtn: !!saveBtn
            });
            
            if (nextBtn) {
                nextBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.debug('Next button clicked');
                    this.nextStep();
                });
            }
            
            if (prevBtn) {
                prevBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.debug('Previous button clicked');
                    this.prevStep();
                });
            }
            
            if (saveBtn) {
                saveBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.debug('Save draft button clicked');
                    this.saveDraft();
                });
            }
            
            document.querySelectorAll('.step-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const step = parseInt(e.currentTarget.dataset.step);
                    this.debug(`Step indicator clicked: ${step}`);
                    this.goToStep(step);
                });
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        this.nextStep();
                    } else if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        this.prevStep();
                    }
                }
            });
            
            this.debug('✅ Events bound successfully');
            
            // Expose as window.ProjectForm for compatibility with partials
            window.ProjectForm = this;
        },
        
        nextStep: function() {
            // When moving next, we still want to save as draft
            // The validation only happens if we are moving forward
            if (this.validateCurrentStep(true)) { // Enforce strict validation when moving forward
                const statusInput = document.querySelector('input[name="status"]');
                if (statusInput) statusInput.value = 'draft';

                if (this.currentStep < this.totalSteps) {
                    this.saveStep(() => {
                        this.currentStep++;
                        this.updateUI();
                        this.updateProgressBar();
                        this.scrollToTop();
                        this.animateStepTransition();
                    }, false, false); // Silent save for navigation, sendAll = false
                } else {
                    this.submitForm();
                }
            }
        },
        
        prevStep: function() {
            if (this.currentStep > 1) {
                // Ensure we save current work as draft before going back
                this.saveStep(() => {
                    this.currentStep--;
                    this.updateUI();
                    this.updateProgressBar();
                    this.scrollToTop();
                    this.animateStepTransition();
                }, false, false); // Silent save for navigation, sendAll = false
            }
        },
        
        goToStep: function(step) {
            if (step >= 1 && step <= this.totalSteps) {
                // If clicking same step, do nothing
                if (step === this.currentStep) return;

                // Validation only if moving forward
                if (step > this.currentStep) {
                    if (!this.validateCurrentStep(true)) { // Enforce strict validation when moving forward
                        return;
                    }
                }

                const statusInput = document.querySelector('input[name="status"]');
                if (statusInput) statusInput.value = 'draft';

                this.saveStep(() => {
                    this.currentStep = step;
                    this.updateUI();
                    this.updateProgressBar();
                    this.scrollToTop();
                    this.animateStepTransition();
                }, false, false); // Silent save for navigation, sendAll = false
            }
        },
        
        updateUI: function() {
            document.querySelectorAll('.step').forEach((step, index) => {
                if (index + 1 === this.currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
            
            document.querySelectorAll('.step-item').forEach((item, index) => {
                const stepNum = index + 1;
                item.classList.remove('active', 'completed');
                
                if (stepNum === this.currentStep) {
                    item.classList.add('active');
                } else if (stepNum < this.currentStep) {
                    item.classList.add('completed');
                }
            });
            
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const currentStepDisplay = document.getElementById('currentStepDisplay');
            
            if (prevBtn) {
                prevBtn.disabled = this.currentStep === 1;
            }
            
            if (nextBtn) {
                if (this.currentStep === this.totalSteps) {
                    nextBtn.innerHTML = '<i class="fas fa-save me-2"></i>حفظ المشروع';
                    nextBtn.className = 'btn btn-success btn-navigation';
                } else {
                    nextBtn.innerHTML = 'التالي <i class="fas fa-chevron-left ms-2"></i>';
                    nextBtn.className = 'btn btn-primary btn-navigation';
                }
            }
            
            if (currentStepDisplay) {
                currentStepDisplay.textContent = this.currentStep;
            }
            
            console.log(`📍 Current Step: ${this.currentStep}`);
            
            // Refresh dropdowns if moving to Step 5 (Executive Activities)
            if (this.currentStep === 5) {
                this.refreshRisks();
                this.refreshOutputs();
            }
        },

        refreshRisks: function() {
            if (!this.projectId) return;
            fetch(`/project-requests/${this.projectId}/risks-list`)
                .then(response => response.json())
                .then(data => {
                    if (data.risks) {
                        const riskSelects = document.querySelectorAll('.project-risk-select');
                        riskSelects.forEach(select => {
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
                })
                .catch(err => console.error('Error refreshing risks:', err));
        },

        refreshOutputs: function() {
            if (!this.projectId) return Promise.resolve();
            return fetch(`/project-requests/${this.projectId}/outputs-list`)
                .then(response => response.json())
                .then(data => {
                    if (data.outputs) {
                        const outputSelects = document.querySelectorAll('.project-output-select');
                        outputSelects.forEach(select => {
                            const currentValue = select.value;
                            // Clear and keep the first option
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
                })
                .catch(err => {
                    console.error('Error refreshing outputs:', err);
                    return Promise.resolve();
                });
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
                currentStep.style.animation = 'fadeIn 0.3s ease-in-out';
            }

            if (this.currentStep === 5) {
                this.refreshRisks();
                this.refreshOutputs().then(() => {
                    this.populateExecutiveActivityDropdowns();
                });
            }

            if (this.currentStep === 7) {
                if (typeof populateReviewData === 'function') {
                    populateReviewData();
                }
            }
        },

        populateExecutiveActivityDropdowns: function() {
            this.debug('Populating Executive Activity Dropdowns...');
            
            // 1. Populate Objective Results
            const results = [];
            document.querySelectorAll('input[name^="objective_results"][name$="[result_name]"]').forEach(input => {
                if (input.value.trim() !== '') {
                    results.push(input.value.trim());
                }
            });

            this.debug('Found Objective Results:', results);

            const resultDropdowns = document.querySelectorAll('.objective-result-select');
            resultDropdowns.forEach(select => {
                const currentValue = select.getAttribute('data-selected-value') || select.value;
                
                // Clear existing options except the first one
                while (select.options.length > 1) {
                    select.remove(1);
                }

                results.forEach(result => {
                    const option = document.createElement('option');
                                    option.value = result;
                                    option.textContent = result;
                                    if (currentValue === result) {
                                        option.selected = true;
                                    }
                                    select.appendChild(option);
                                });
                            });

            // 2. Populate Outputs
            const outputs = [];
            // Scan for outputs: input name format is result_outputs[index][output]
            document.querySelectorAll('input[name^="result_outputs"][name$="[output]"]').forEach(input => {
                if (input.value.trim() !== '') {
                    outputs.push(input.value.trim());
                }
            });

            this.debug('Found Outputs:', outputs);

            const outputDropdowns = document.querySelectorAll('.project-output-select');
            outputDropdowns.forEach(select => {
                const currentValue = select.getAttribute('data-selected-value') || select.value;
                
                // Get existing options to avoid duplicates
                const existingOptions = Array.from(select.options).map(opt => opt.textContent.trim());

                outputs.forEach(output => {
                    // Only add if not already present as an option (checked by text)
                    if (!existingOptions.includes(output)) {
                        const option = document.createElement('option');
                        option.value = output; // Use text as value for new outputs (backend resolves it)
                        option.textContent = output;
                        select.appendChild(option);
                    }
                });

                // Restore selection if match found by name
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
            
            validateCurrentStep: function(isStrict = false) {
                // Find all required fields in the current step
                const currentStepElement = document.querySelector(`.step[data-step="${this.currentStep}"]`);
                if (!currentStepElement) return true;

                // Highlight invalid fields first
                let validationError = false;
                if (isStrict) {
                    validationError = this.highlightInvalidFields(currentStepElement);
                }

                if (validationError) {
                    AppUtils.Utils.showToast('يرجى استكمال كافة الحقول المطلوبة في هذه الخطوة', 'error');
                    return false;
                }

                // Step 1: Basic project name check
                if (this.currentStep === 1) {
                    if (isStrict) {
                        const projectName = document.querySelector('[name="project_name"]');
                        if (!projectName || !projectName.value.trim()) {
                            AppUtils.Utils.showToast('يرجى إدخال اسم المشروع للمتابعة', 'warning');
                            if (projectName) projectName.focus();
                            return false;
                        }
                    }
                    return true;
                }
                
                // Validate Step 2 (Details and Objectives)
                if (this.currentStep === 2) {
                    return this.validateStep2(isStrict);
                }
                
                // Validate Step 3 (Risks, Entities)
                if (this.currentStep === 3) {
                    return this.validateStep3(isStrict);
                }
                
                // Validate Step 4 (Preliminary Activities)
                if (this.currentStep === 4) {
                    return this.validatePreliminaryActivitiesWeights(isStrict);
                }
                
                // Validate Step 5 (Executive Activities)
                if (this.currentStep === 5) {
                    return this.validateExecutiveActivitiesWeights(isStrict);
                }
                
                // Step 6 (Financing)
                if (this.currentStep === 6 && isStrict) {
                    return this.validateFinancingWeights(true);
                }
                
                return true;
            },
            
            validateStep2: function(isStrict = false) {
                if (!isStrict) return true; // Allow navigation without locations in loose mode

                // Check if at least one location has been added
                const locationsTable = document.querySelector('#projectLocationsTable tbody');
                if (!locationsTable) {
                    AppUtils.Utils.showToast('خطأ: لم يتم العثور على جدول المواقع', 'error');
                    return false;
                }
                
                const locationRows = locationsTable.querySelectorAll('tr:not(.project-empty-row)');
                if (locationRows.length === 0) {
                    AppUtils.Utils.showToast('يرجى إضافة موقع واحد على الأقل للمشروع', 'error');
                    return false;
                }
                
                return true;
            },
            
            validateStep3: function(isStrict = false) {
                if (!isStrict) return true; // Allow navigation in loose mode

                let errors = [];
                
                // 1. Check Main Objectives
                const mainObjectivesContainer = document.getElementById('main-objectives-container');
                if (mainObjectivesContainer) {
                    const mainObjectiveRows = mainObjectivesContainer.querySelectorAll('.objective-row');
                    if (mainObjectiveRows.length === 0) {
                        errors.push('يجب إضافة هدف رئيسي واحد على الأقل');
                    }
                }
                
                // 2. Check Special Objectives
                const specialObjectivesContainer = document.getElementById('special-objectives-container');
                if (specialObjectivesContainer) {
                    const specialObjectiveRows = specialObjectivesContainer.querySelectorAll('.special-objective-row');
                    if (specialObjectiveRows.length === 0) {
                        errors.push('يجب إضافة هدف خاص واحد على الأقل');
                    } else {
                        // Check that each special objective has at least one result
                        let hasObjectiveWithoutResults = false;
                        specialObjectiveRows.forEach((row, index) => {
                            const resultsContainer = row.querySelector('.results-container');
                            if (resultsContainer) {
                                const resultRows = resultsContainer.querySelectorAll('.result-row');
                                if (resultRows.length === 0) {
                                    hasObjectiveWithoutResults = true;
                                }
                            }
                        });
                        if (hasObjectiveWithoutResults) {
                            errors.push('يجب أن يحتوي كل هدف خاص على نتيجة واحدة على الأقل');
                        }
                    }
                }
                
                // 3. Check Risks
                const risksTable = document.querySelector('#risks-table tbody');
                if (risksTable) {
                    const riskRows = risksTable.querySelectorAll('tr:not(.empty-row)');
                    if (riskRows.length === 0) {
                        errors.push('يجب إضافة مخاطرة واحدة على الأقل');
                    }
                }
                
                // 4. Check Supervising Authorities
                const supervisingTable = document.querySelector('#supervisingEntitiesTable tbody');
                if (supervisingTable) {
                    const supervisingRows = supervisingTable.querySelectorAll('tr:not(.project-empty-row)');
                    if (supervisingRows.length === 0) {
                        errors.push('يجب إضافة جهة مشرفة واحدة على الأقل');
                    }
                }
                
                // 5. Check Implementing Entities
                const implementingTable = document.querySelector('#implementingEntitiesTable tbody');
                if (implementingTable) {
                    const implementingRows = implementingTable.querySelectorAll('tr:not(.project-empty-row)');
                    if (implementingRows.length === 0) {
                        errors.push('يجب إضافة جهة منفذة واحدة على الأقل');
                    }
                }
                
                // Display errors if any
                if (errors.length > 0) {
                    const errorMessage = errors.join('\n• ');
                    AppUtils.Utils.showToast('يرجى استكمال المتطلبات التالية:\n• ' + errorMessage, 'error');
                    return false;
                }
                
                return true;
            },

            validateFinancingWeights: function(isStrict = false, errors = null) {
                const financingCards = document.querySelectorAll('.financing-card');
                
                if (isStrict && financingCards.length === 0) {
                    const msg = 'يجب إضافة مصدر تمويل واحد على الأقل';
                    if (errors) errors.push(msg);
                    else AppUtils.Utils.showToast(msg, 'error');
                    return false;
                }

                if (financingCards.length > 0) {
                    let totalAmount = 0;
                    let totalPercentage = 0;
                    const totalCost = parseFloat(document.getElementById('total_project_cost')?.value) || 0;

                    financingCards.forEach(card => {
                        const amountInput = card.querySelector('input[name*="[financing_amount]"]');
                        const percentageInput = card.querySelector('input[name*="[financing_percentage]"]');
                        
                        if (amountInput) {
                            totalAmount += parseFloat(amountInput.value) || 0;
                        }
                        if (percentageInput) {
                            totalPercentage += parseFloat(percentageInput.value) || 0;
                        }
                    });
                    
                    if (isStrict) {
                        // Check percentage (should be 100%)
                        if (Math.abs(totalPercentage - 100) > 0.01) {
                            const msg = `يجب أن يكون مجموع نسب التمويل 100% (الحالي: ${totalPercentage.toFixed(2)}%)`;
                            if (errors) errors.push(msg);
                            else AppUtils.Utils.showToast(msg, 'error');
                            return false;
                        }

                        // Check amount (must match total cost)
                        if (totalCost > 0 && Math.abs(totalAmount - totalCost) > 0.1) {
                            const msg = `إجمالي مبالغ التمويل (${totalAmount.toFixed(2)}) يجب أن يتطابق مع إجمالي تكلفة المشروع (${totalCost.toFixed(2)})`;
                            if (errors) errors.push(msg);
                            else AppUtils.Utils.showToast(msg, 'error');
                            return false;
                        }
                    }
                }
                return true;
            },
            
            validatePreliminaryActivitiesWeights: function(isStrict = false) {
                if (!isStrict) return true;

                const activitiesContainer = document.getElementById('preliminary-activities-container');
                if (!activitiesContainer) return true;

                const activityRows = activitiesContainer.querySelectorAll('.activity-row');
                
                if (activityRows.length < 1) {
                    AppUtils.Utils.showToast('يجب إضافة نشاط تمهيدي واحد على الأقل للمتابعة', 'warning');
                    return false;
                }
                
                const totalWeightElement = document.getElementById('weight-total');
                const totalWeight = parseFloat(totalWeightElement ? totalWeightElement.textContent : 0) || 0;
                
                if (Math.abs(totalWeight - 100) > 0.01) {
                    AppUtils.Utils.showToast(`يجب أن يكون مجموع أوزان الأنشطة التمهيدية 100% (الحالي: ${totalWeight}%)`, 'error');
                    return false;
                }
                
                // Validate each activity
                for (const activityRow of activityRows) {
                    const activityIndex = activityRow.dataset.activityIndex;
                    const activityNum = activityRow.querySelector('.activity-number')?.textContent || (parseInt(activityIndex) + 1);
                    
                    const nameInput = activityRow.querySelector(`[name$="[name]"]`);
                    if (!nameInput || !nameInput.value.trim()) {
                        AppUtils.Utils.showToast(`يرجى إدخال اسم النشاط رقم ${activityNum}`, 'error');
                        if (nameInput) nameInput.focus();
                        return false;
                    }

                    const detailsRow = activityRow.nextElementSibling;
                    if (!detailsRow || !detailsRow.classList.contains('activity-details-row')) continue;
                    
                    const procedureRows = detailsRow.querySelectorAll('.procedure-row');
                    if (procedureRows.length < 2) {
                        AppUtils.Utils.showToast(`النشاط رقم ${activityNum}: يجب أن يحتوي على إجراءين على الأقل`, 'error');
                        return false;
                    }
                    
                    const proceduresWeightElement = detailsRow.querySelector('.activity-procedures-weight-total');
                    const proceduresWeight = parseFloat(proceduresWeightElement ? proceduresWeightElement.textContent : 0) || 0;
                    
                    if (Math.abs(proceduresWeight - 100) > 0.01) {
                        AppUtils.Utils.showToast(`النشاط رقم ${activityNum}: مجموع أوزان الإجراءات يجب أن يكون 100% (الحالي: ${proceduresWeight}%)`, 'error');
                        return false;
                    }

                    // Validate procedure names
                    for (const procedureRow of procedureRows) {
                        const procedureNameInput = procedureRow.querySelector(`[name$="[procedure_name]"]`);
                        
                        if (!procedureNameInput || !procedureNameInput.value.trim()) {
                            const procedureNum = procedureRow.querySelector('.procedure-number')?.textContent || "جديد";
                            AppUtils.Utils.showToast(`النشاط رقم ${activityNum}، الإجراء رقم ${procedureNum}: يرجى إدخال اسم الإجراء`, 'error');
                            if (procedureNameInput) procedureNameInput.focus();
                            return false;
                        }
                    }
                }
                
                return true;
            },
            
            validateExecutiveActivitiesWeights: function(isStrict = false) {
                if (!isStrict) return true;

                const activitiesContainer = document.getElementById('executive-activities-container');
                const activityRows = activitiesContainer ? activitiesContainer.querySelectorAll('.executive-activity-row') : [];
                
                if (activityRows.length < 2) {
                    AppUtils.Utils.showToast('يجب إضافة نشاطين تنفيذيين على الأقل للمتابعة', 'warning');
                    return false;
                }
                
                const totalWeightElement = document.getElementById('executive-weight-total');
                const totalWeight = parseFloat(totalWeightElement ? totalWeightElement.textContent : 0) || 0;
                
                if (Math.abs(totalWeight - 100) > 0.01) {
                    AppUtils.Utils.showToast(`يجب أن يكون مجموع أوزان الأنشطة التنفيذية 100% (الحالي: ${totalWeight}%)`, 'error');
                    return false;
                }
                
                // Validate each activity
                for (const activityRow of activityRows) {
                    const activityIndex = activityRow.dataset.activityIndex || activityRow.id.replace('executive-activity-', '');
                    
                    const nameInput = activityRow.querySelector(`[name$="[name]"]`);
                    if (!nameInput || !nameInput.value.trim()) {
                        AppUtils.Utils.showToast(`يرجى إدخال اسم النشاط التنفيذي رقم ${parseInt(activityIndex) + 1}`, 'error');
                        if (nameInput) nameInput.focus();
                        return false;
                    }

                    const detailsRow = activityRow.nextElementSibling;
                    const actionsContainer = detailsRow ? detailsRow.querySelector('.executive-actions-tbody') : null;
                    const actionRows = actionsContainer ? actionsContainer.querySelectorAll('.executive-activity-action-row') : [];
                    
                    if (actionRows.length < 2) {
                        AppUtils.Utils.showToast(`النشاط ${parseInt(activityIndex) + 1}: يجب أن يحتوي على إجراءين (Actions) على الأقل`, 'error');
                        return false;
                    }
                    
                    const actionsWeightElement = detailsRow ? detailsRow.querySelector('.executive-action-total-weight') : null;
                    const actionsWeight = parseFloat(actionsWeightElement ? actionsWeightElement.textContent : 0) || 0;
                    
                    if (Math.abs(actionsWeight - 100) > 0.01) {
                        AppUtils.Utils.showToast(`النشاط ${parseInt(activityIndex) + 1}: مجموع أوزان الإجراءات يجب أن يكون 100%`, 'error');
                        return false;
                    }
                }
                
                return true;
            },
            
            highlightInvalidFields: function(stepElement) {
                // Find ALL required fields first, then filter for visible ones only
                // This prevents hidden procedures (in collapsed rows) or templates from blocking validation
                const allInputs = stepElement.querySelectorAll('input[required], select[required], textarea[required], input[data-required], select[data-required], .required-field');
                const inputs = Array.from(allInputs).filter(field => {
                    // Basic visibility check: must have offsetParent or be a hidden input (though we shouldn't validate those as required)
                    // Check if the element OR any of its parents are hidden via display: none
                    const style = window.getComputedStyle(field);
                    if (style.display === 'none' || style.visibility === 'hidden') return false;
                    
                    // Recursive check for ancestors being hidden
                    let parent = field.parentElement;
                    while (parent && parent !== stepElement) {
                        const parentStyle = window.getComputedStyle(parent);
                        if (parentStyle.display === 'none') return false;
                        parent = parent.parentElement;
                    }
                    return true;
                });
                
                let firstInvalid = null;
                let hasError = false;
                const validatedGroups = new Set();

                inputs.forEach(field => {
                    let isInvalid = false;

                    // Handle Radio buttons and Checkboxes
                    if (field.type === 'radio' || field.type === 'checkbox') {
                        const name = field.name;
                        if (!name || validatedGroups.has(name)) return;
                        validatedGroups.add(name);
                        
                        const checked = stepElement.querySelector(`input[name="${name}"]:checked`);
                        if (!checked) {
                            isInvalid = true;
                            // Highlight the group
                            const groupElements = stepElement.querySelectorAll(`input[name="${name}"]`);
                            groupElements.forEach(el => {
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
                        
                        if (field.type !== 'radio' && field.type !== 'checkbox') {
                            field.style.borderColor = '#dc3545';
                            field.style.borderWidth = '2px';
                            field.classList.add('is-invalid');
                        }
                        
                        // Handle Select2 if applicable
                        const $field = $(field);
                        if ($field.hasClass('select2-hidden-accessible')) {
                            const $selection = $field.next('.select2-container').find('.select2-selection');
                            $selection.css('border-color', '#dc3545');
                            $selection.css('border-width', '2px');
                        }
                        
                        if (!firstInvalid) firstInvalid = field;

                        // Create listener to remove highlight once user starts typing or changes value
                        const removeError = () => {
                            if (field.type === 'radio' || field.type === 'checkbox') {
                                const groupElements = stepElement.querySelectorAll(`input[name="${field.name}"]`);
                                groupElements.forEach(el => {
                                    el.classList.remove('is-invalid');
                                    el.style.outline = '';
                                    el.style.outlineOffset = '';
                                });
                            } else {
                                field.style.borderColor = '';
                                field.style.borderWidth = '';
                                field.classList.remove('is-invalid');
                            }
                            
                            const $field = $(field);
                            if ($field.hasClass('select2-hidden-accessible')) {
                                const $selection = $field.next('.select2-container').find('.select2-selection');
                                $selection.css('border-color', '');
                                $selection.css('border-width', '');
                            }
                            
                            field.removeEventListener('input', removeError);
                            field.removeEventListener('change', removeError);
                            if ($field.hasClass('select2-hidden-accessible')) {
                                $field.off('select2:select', removeError);
                            }
                        };
                        
                        field.addEventListener('input', removeError);
                        field.addEventListener('change', removeError);
                        if ($(field).hasClass('select2-hidden-accessible')) {
                            $(field).on('select2:select', removeError);
                        }
                    }
                });
                
                // Scroll to first invalid field
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Give it a moment to scroll then focus
                    setTimeout(() => {
                        if ($(firstInvalid).hasClass('select2-hidden-accessible')) {
                            $(firstInvalid).select2('open');
                        } else {
                            firstInvalid.focus();
                        }
                    }, 500);
                }
                
                return hasError;
            },
            
            isStepCompleted: function(step) {
                return step < this.currentStep;
            },
            
            /**
             * Auto-save draft using AJAX without form submission
             * Saves the project as a single draft record in the database
             */
            /**
             * Save current step data
             */
            normalizeFormData: function(formData) {
                const locationFields = ['governorate_id', 'directorate_id', 'sub_area_id', 'village_id'];
                const entriesToDelete = [];
                
                for (let [key, value] of formData.entries()) {
                    locationFields.forEach(field => {
                        if (key.includes(`][${field}]`)) {
                            if (value === '0') {
                                entriesToDelete.push(key);
                            }
                        }
                    });
                }
                
                entriesToDelete.forEach(key => {
                    formData.delete(key);
                });
                
                return formData;
            },
            
            saveStep: function(callback, showSuccessToast = true, sendAll = false) {
                const stepNumber = this.currentStep;
                const form = document.getElementById('projectForm');
                if (!form) {
                    if (callback) callback();
                    return;
                }
                
                let formData;
                if (sendAll) {
                    this.debug('Final submission: Sending all project data');
                    formData = new FormData(form);
                } else {
                    const fullData = new FormData(form);
                    formData = new FormData();
                    
                    // Get all input names that belong to the current step section
                    const currentStepSection = form.querySelector(`.step[data-step="${stepNumber}"]`);
                    const stepInputNames = new Set();
                    if (currentStepSection) {
                        currentStepSection.querySelectorAll('[name]').forEach(el => {
                            stepInputNames.add(el.name);
                        });
                    }
                    
                    // Essential global fields that must always be sent
                    const globalFields = ['_token', 'status', 'request_id', 'last_saved_step'];
                    
                    // Filter fullData to only include step-specific and global fields
                    for (let [key, value] of fullData.entries()) {
                        let isStepField = false;
                        stepInputNames.forEach(name => {
                            if (key === name || key.startsWith(name + '[') || key.startsWith(name + '.')) {
                                isStepField = true;
                            }
                        });
                        
                        if (isStepField || globalFields.includes(key)) {
                            formData.append(key, value);
                        }
                    }
                }
                
                formData = this.normalizeFormData(formData);
                const token = document.querySelector('input[name="_token"]').value;
                const url = `/project-requests/step/${stepNumber}`;
                const activeStepBtn = document.getElementById('nextBtn');
                const prevBtn = document.getElementById('prevBtn');
                const originalNextText = activeStepBtn ? activeStepBtn.innerHTML : '';
                const originalPrevText = prevBtn ? prevBtn.innerHTML : '';
                
                if (showSuccessToast) {
                    AppUtils.Utils.showToast(`جاري حفظ الخطوة ${stepNumber}...`, 'info');
                }
                
                if(activeStepBtn) {
                    activeStepBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحفظ...';
                    activeStepBtn.disabled = true;
                }
                if(prevBtn) {
                    prevBtn.disabled = true;
                }
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json().then(data => ({ ok: response.ok, status: response.status, data })))
                .then(({ ok, status, data }) => {
                    if (!ok) {
                        if (status === 422) {
                            throw { status: 422, errors: data.errors || {}, message: data.message || 'خطأ في التحقق من البيانات' };
                        }
                        throw new Error(data.message || `خطأ في الخادم: ${status}`);
                    }
                    
                    if (data.success) {
                        const requestId = data.request_id || data.project_id;
                        if (requestId) {
                            this.projectId = requestId;
                            const requestIdField = document.getElementById('request_id');
                            if (requestIdField) {
                                requestIdField.value = requestId;
                            }
                        }

                        
                        if (showSuccessToast) {
                            AppUtils.Utils.showToast('تم حفظ الخطوة بنجاح', 'success');
                        }
                        
                        if (callback) callback(data);
                    } else {
                        throw new Error(data.message || 'فشل حفظ الخطوة');
                    }
                })
                .catch(error => {
                    console.error('❌ Step save error:', error);
                    
                    // Check if this is a "Project not found" error
                    const errorMessage = error.message || '';
                    if (errorMessage.includes('Request not found') || errorMessage.includes('may have been deleted')) {
                        // Clear the invalid request_id
                        const requestIdField = document.getElementById('request_id');
                        if (requestIdField) {
                            requestIdField.value = '';
                        }
                        
                        // Show error and redirect to step 1
                        AppUtils.Utils.showToast('المشروع غير موجود. سيتم إعادة توجيهك إلى الخطوة الأولى لإنشاء مشروع جديد.', 'error');
                        
                        // Reset to step 1 after a short delay
                        setTimeout(() => {
                            this.currentStep = 1;
                            this.updateUI();
                            this.updateProgressBar();
                        }, 2000);
                        
                        return; // Exit early
                    }
                    
                    if (error.status === 422 && error.errors) {
                        let errorMsg = 'يرجى تصحيح الأخطاء التالية:\n';
                        Object.values(error.errors).forEach(errArray => {
                            if (Array.isArray(errArray)) {
                                errorMsg += `• ${errArray[0]}\n`;
                            } else {
                                errorMsg += `• ${errArray}\n`;
                            }
                        });
                        AppUtils.Utils.showToast(errorMsg, 'error');
                    } else {
                        AppUtils.Utils.showToast('خطأ: ' + (error.message || 'حدث خطأ غير متوقع'), 'error');
                    }
                })
                .finally(() => {
                    if(activeStepBtn) {
                        activeStepBtn.innerHTML = originalNextText;
                        activeStepBtn.disabled = false;
                    }
                    if(prevBtn) {
                        prevBtn.disabled = (this.currentStep === 1);
                    }
                });
            },
            
            saveDraft: function() {
                const form = document.getElementById('projectForm');
                if (form) {
                    const statusInput = form.querySelector('input[name="status"]');
                    if (statusInput) {
                        statusInput.value = 'draft';
                    }
                    
                    AppUtils.Utils.showToast('جاري حفظ المسودة...', 'info');
                    this.showLoadingState();
                    this.saveStep(() => {
                        // Redirect to project requests index after successful draft save
                        AppUtils.Utils.showToast('تم حفظ المسودة بنجاح. جاري الانتقال...', 'success');
                        setTimeout(() => {
                            window.location.href = '/project-requests';
                        }, 1500);
                    }, true, false); // showSuccessToast = true, sendAll = false
                }
            },
            
            submitForm: function() {
                if (this.validateCurrentStep(true)) { // Strict validation for final submission
                    const form = document.getElementById('projectForm');
                    if (form) {
                        const statusInput = form.querySelector('input[name="status"]');
                        if (statusInput) {
                            statusInput.value = 'final';
                        }
                        
                        AppUtils.Utils.showToast('جاري إرسال طلب المشروع...', 'info');
                        this.showLoadingState();
                        this.saveStep((data) => {
                            if (data && data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                const requestId = document.getElementById('request_id').value;
                                if (requestId) {
                                    window.location.href = `/project-requests/${requestId}`;
                                } else {
                                    window.location.href = '/project-requests';
                                }
                            }
                        }, true, true); // showSuccessToast = true, sendAll = true (IMPORTANT)
                    }
                }
            },
            
            showLoadingState: function() {
                const buttons = document.querySelectorAll('#nextBtn, #prevBtn, #saveBtn');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري المعالجة...';
                    
                    // Restore after 5 seconds (in case of error)
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }, 5000);
                });
            },
            
            scrollToTop: function() {
                const scrollContainer = document.querySelector('.scrollable-container');
                if (scrollContainer) {
                    scrollContainer.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            },
            
            scrollToElement: function(element) {
                const scrollContainer = document.querySelector('.scrollable-container');
                if (scrollContainer && element) {
                    const containerTop = scrollContainer.getBoundingClientRect().top;
                    const elementTop = element.getBoundingClientRect().top;
                    const relativeTop = elementTop - containerTop;
                    
                    scrollContainer.scrollTo({
                        top: scrollContainer.scrollTop + relativeTop - 100,
                        behavior: 'smooth'
                    });
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
            
            initializePreliminaryActivities: function() {
                // Try both button IDs for compatibility
                // Entities and other sections are handled by their own partial scripts
            },
            
            initializeRisksTable: function() {
                // Risks are handled by their own partial script
            },
            
            initializeFinancingTable: function() {
                // Financing is handled by its own partial script
            },
            
            initializeExecutiveActivities: function() {
                // Executive activities are handled by their own partial script
            },
            
            addPreliminaryActivity: function() {
                // Handled by partial script
            },
            
            getPreliminaryActivityTemplate: function(activityIndex) {
                // Handled by partial script
            },
            
            addPreliminaryAction: function(activityIndex) {
                // Handled by partial script
            },
            
            getPreliminaryActionTemplate: function(activityIndex, actionIndex) {
                // Handled by partial script
            },
            
            addPreliminaryCost: function(activityIndex, actionIndex = null) {
                // Handled by partial script
            },
            
            getPreliminaryCostTemplate: function(activityIndex, actionIndex, costIndex) {
                // Handled by partial script
            },
            
            addRisk: function() {
                // Handled by partial script
            },
            
            getRiskTemplate: function(riskIndex) {
                // Handled by partial script
            },
            
            addFinancing: function() {
                // Handled by partial script
            },
            
            getFinancingTemplate: function(financingIndex) {
                // Handled by partial script
            },
            
            addExecutiveActivity: function() {
                // Handled by partial script
            },
            
            getExecutiveActivityTemplate: function(activityIndex) {
                // Handled by partial script
            },
            
            addExecutiveAction: function(activityIndex) {
                // Handled by partial script
            },
            
            getExecutiveActionTemplate: function(activityIndex, actionIndex) {
                // Handled by partial script
            },
            
            addExecutiveCost: function(activityIndex, actionIndex = null) {
                // Handled by partial script
            },
            
            getExecutiveCostTemplate: function(activityIndex, actionIndex, costIndex) {
                // Handled by partial script
            },
            
            checkEmptyActivities: function() {
                // Handled by partial script
            },
            
            bindActivityEvents: function(activityIndex) {
                // Handled by partial script
            },
            
            bindActionEvents: function(activityIndex, actionIndex) {
                // Handled by partial script
            },
            
            bindCostEvents: function(activityIndex, actionIndex, costIndex) {
                // Handled by partial script
            },
            
            calculateTotalCost: function(activityIndex, actionIndex) {
                // Handled by partial script
            },
            
            calculateActivityTotalCost: function(activityIndex) {
                // Handled by partial script
            },
            
            calculateActivityWeight: function(activityIndex) {
                // Handled by partial script
            },
            
            calculateActionWeight: function(activityIndex, actionIndex) {
                // Handled by partial script
            },
            
            updateTotalProjectCost: function() {
                // Handled by partial script
            },
            
            updateTotalProjectWeight: function() {
                // Handled by partial script
            },
            
            debug: function(message) {
                if (this.debugMode) {
                    console.log(`[FormManager Debug] ${message}`);
                }
            }
    };
    
    // Initialize the form manager
    FormManager.init();
    
    // Make it globally accessible for debugging
    window.FormManager = FormManager;
    
    // Add resize handler for responsive adjustments
    window.addEventListener('resize', () => {
        FormManager.updateUI();
    });
    
    // 🔍 DEBUGGING HELPER: Check entity data before submission
    window.checkEntityData = function() {
        console.log('═══════════════════════════════════════════════════════');
        console.log('🔍 ENTITY DATA CHECK');
        console.log('═══════════════════════════════════════════════════════');
        
        const supervisingRows = document.querySelectorAll('#supervisingEntitiesTable tbody tr:not(.project-empty-row)');
        const implementingRows = document.querySelectorAll('#implementingEntitiesTable tbody tr:not(.project-empty-row)');
        const participatingRows = document.querySelectorAll('#participatingEntitiesTable tbody tr:not(.project-empty-row)');
        
        console.log('🏛️ Supervising Entities: ' + supervisingRows.length + ' rows');
        console.log('🏢 Implementing Entities: ' + implementingRows.length + ' rows');
        console.log('🤝 Participating Entities: ' + participatingRows.length + ' rows');
        
        if (supervisingRows.length === 0 && implementingRows.length === 0 && participatingRows.length === 0) {
            console.error('❌ NO ENTITIES ADDED!');
            console.warn('⚠️ You need to click the "Add Entity" buttons in Step 3');
            console.warn('⚠️ Without entities, the entity tables will be empty in the database');
        } else {
            console.log('✅ Entities have been added');
            
            const form = document.getElementById('projectForm');
            const formData = new FormData(form);
            let entityFieldCount = 0;
            for (let [key, value] of formData.entries()) {
                if (key.includes('_entities')) {
                    entityFieldCount++;
                }
            }
            console.log('📊 Total entity form fields: ' + entityFieldCount);
            
            if (entityFieldCount === 0) {
                console.error('❌ Entity rows exist but no form data found!');
                console.warn('⚠️ This might be a JavaScript issue. Check if entity managers are initialized.');
            }
        }
        
        console.log('═══════════════════════════════════════════════════════');
        console.log('💡 TIP: Run this command before submitting to verify entities are added');
    };
    
    console.log('💡 Debugging Helper Available: Run checkEntityData() in console to verify entities');
});
</script>



@include('projects.partials.weight-calculator')

@endsection
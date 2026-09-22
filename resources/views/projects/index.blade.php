@extends('layouts.app')

@section('styles')
<style>
    /* ─── إعادة ضبط العرض الكامل ─── */
    .projects-index {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }
    .projects-index .container,
    .projects-index .container-fluid {
        max-width: 100% !important;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* ─── تصغير الخطوط العامة ─── */
    .projects-index,
    .projects-index .form-control,
    .projects-index .form-select,
    .projects-index .btn,
    .projects-index .dropdown-item,
    .projects-index .badge,
    .projects-index .small {
        font-size: 0.72rem !important;
    }
    .projects-index h6 { font-size: 0.8rem !important; }
    .projects-index .form-label {
        font-size: 0.65rem !important;
        font-weight: 600;
    }

    /* ─── الفلاتر ─── */
    .filter-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.6rem;
        margin-bottom: 0.5rem;
    }
    .filter-card .form-control,
    .filter-card .form-select {
        font-size: 0.7rem !important;
        padding: 0.3rem 0.5rem;
        height: 30px;
        border-radius: 6px;
    }
    .filter-card .row.g-3 { --bs-gutter-y: 0.4rem; }

    /* ─── الجدول بعرض الشاشة ─── */
    .projects-table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    .projects-table {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .projects-table thead th {
        background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
        color: #334155;
        font-weight: 700;
        font-size: 0.68rem;
        padding: 0.4rem 0.3rem;
        border-bottom: 2px solid #cbd5e1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: center;
        vertical-align: middle;
    }
    .projects-table tbody td {
        padding: 0.35rem 0.3rem;
        font-size: 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .projects-table tbody tr:hover {
        background: #f8fafc;
    }

    /* ─── منع التفاف النص والتمرير الأفقي ─── */
    .no-wrap {
        white-space: nowrap !important;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .text-truncate-custom {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ─── البادجات الصغيرة ─── */
    .badge-artistic {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50rem;
        font-size: 0.65rem !important;
        font-weight: 600;
        border: 1px solid transparent;
        white-space: nowrap;
        line-height: 1.2;
    }
    .badge-artistic.badge-new {
        background: #e0f2fe;
        color: #0369a1;
        border-color: #7dd3fc;
    }
    .badge-artistic.badge-old {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }
    .badge-artistic.badge-stage {
        background: #ede9fe;
        color: #5b21b6;
        border-color: #c4b5fd;
    }
    .badge-artistic.badge-returned {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }
    .badge-artistic.badge-draft-complete {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }
    .badge-artistic.badge-draft-incomplete {
        background: #f3f4f6;
        color: #374151;
        border-color: #d1d5db;
    }
    .badge-artistic.badge-execution {
        background: #fef3c7;
        color: #92400e;
        border-color: #fcd34d;
    }
    .badge-artistic.badge-waiting-entity {
        background: #ffedd5;
        color: #9a3412;
        border-color: #fdba74;
    }
    .badge-artistic.badge-waiting-complete {
        background: #dbeafe;
        color: #1e40af;
        border-color: #93c5fd;
    }
    .badge-artistic.badge-ready {
        background: #fef9c3;
        color: #854d0e;
        border-color: #facc15;
    }

    /* ─── أزرار الإجراءات الصغيرة ─── */
    .btn-action-artistic {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.65rem;
        padding: 0;
        vertical-align: middle;
    }
    .btn-action-artistic:hover {
        transform: translateY(-1px);
        filter: brightness(1.1);
    }
    .btn-action-artistic.btn-view { background: #3b82f6; color: white; }
    .btn-action-artistic.btn-edit { background: #f59e0b; color: white; }
    .btn-action-artistic.btn-delete { background: #ef4444; color: white; }
    .btn-action-artistic.btn-enable { background: #10b981; color: white; }
    .btn-action-artistic.btn-disable { background: #6b7280; color: white; }
    .btn-action-artistic.btn-log { background: #8b5cf6; color: white; }
    .btn-action-artistic.btn-more {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
    }

    /* ─── progress bar ─── */
    .progress-artistic {
        height: 4px;
        border-radius: 50rem;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: 0.25rem;
    }
    .progress-artistic .progress-bar {
        background: #10b981;
        border-radius: 50rem;
    }

    /* ─── أيقونات المراجعة ─── */
    .review-icon-badge {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
    }
    .review-icon-badge.review-done { background: #10b981; color: white; }
    .review-icon-badge.review-pending-financial { background: #0ea5e9; color: white; }
    .review-icon-badge.review-pending-technical { background: #8b5cf6; color: white; }

    /* ─── رقم المشروع ─── */
    .project-number {
        font-family: monospace;
        font-weight: 700;
        font-size: 0.68rem;
        color: #1e293b;
        background: #f1f5f9;
        padding: 0.15rem 0.4rem;
        border-radius: 5px;
        border: 1px solid #cbd5e1;
        white-space: nowrap;
    }

    /* ─── Dropdown ─── */
    .dropdown-menu-artistic {
        border: none;
        border-radius: 10px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        padding: 0.3rem;
        font-size: 0.7rem !important;
        min-width: 180px;
    }
    .dropdown-menu-artistic .dropdown-item {
        border-radius: 6px;
        padding: 0.4rem 0.6rem;
        font-size: 0.7rem !important;
    }

    /* ─── Checkbox ─── */
    .custom-checkbox {
        width: 14px;
        height: 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 4px;
        cursor: pointer;
    }

    /* ─── Header Buttons ─── */
    .header-btn {
        border-radius: 8px;
        padding: 0.35rem 0.75rem;
        font-weight: 600;
        font-size: 0.72rem !important;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: none;
        white-space: nowrap;
    }
    .header-btn.btn-primary-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .header-btn.btn-outline-gradient {
        background: white;
        border: 1px solid #e2e8f0;
        color: #475569;
    }

    /* ─── Empty State ─── */
    .empty-state { padding: 2rem 1rem; text-align: center; }
    .empty-state .empty-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    /* ─── Modal ─── */
    .modal-artistic .modal-content {
        border: none;
        border-radius: 12px;
        font-size: 0.75rem !important;
    }
    .modal-artistic .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 0.6rem 1rem;
    }
    .modal-artistic .modal-body { padding: 0.75rem; }
    .modal-artistic .modal-footer { padding: 0.5rem 0.75rem; background: #f8fafc; }
    .modal-artistic .form-control,
    .modal-artistic .form-select { font-size: 0.72rem !important; }

    /* ─── أحجام الأعمدة المحددة ─── */
    .projects-table th:nth-child(1), .projects-table td:nth-child(1) { width: 35px; }
    .projects-table th:nth-child(2), .projects-table td:nth-child(2) { width: 35px; }
    .projects-table th:nth-child(3), .projects-table td:nth-child(3) { width: 85px; }
    .projects-table th:nth-child(4), .projects-table td:nth-child(4) { width: auto; }
    .projects-table th:nth-child(5), .projects-table td:nth-child(5) { width: 55px; }
    .projects-table th:nth-child(6), .projects-table td:nth-child(6) { width: 90px; }
    .projects-table th:nth-child(7), .projects-table td:nth-child(7) { width: 85px; }
    .projects-table th:nth-child(8), .projects-table td:nth-child(8) { width: 85px; }
    .projects-table th:nth-child(9), .projects-table td:nth-child(9) { width: 100px; }
    .projects-table th:nth-child(10), .projects-table td:nth-child(10) { width: 110px; }
    .projects-table th:nth-child(11), .projects-table td:nth-child(11) { width: 75px; }
    .projects-table th:nth-child(12), .projects-table td:nth-child(12) { width: 80px; }
    .projects-table th:nth-child(13), .projects-table td:nth-child(13) { width: 155px; }

    /* ─── Responsive hiding ─── */
    @media (max-width: 1200px) {
        .hide-xl { display: none !important; }
        .projects-table th:nth-child(12), .projects-table td:nth-child(12) { display: none; }
    }
    @media (max-width: 992px) {
        .hide-lg { display: none !important; }
        .projects-table th:nth-child(11), .projects-table td:nth-child(11) { display: none; }
    }
    @media (max-width: 768px) {
        .projects-table th:nth-child(6), .projects-table td:nth-child(6),
        .projects-table th:nth-child(7), .projects-table td:nth-child(7),
        .projects-table th:nth-child(8), .projects-table td:nth-child(8) { display: none; }
    }

    /* ─── منع التمرير الأفقي على مستوى الصفحة ─── */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
</style>
@endsection

@section('content')
<div class="projects-index">
    <x-index-page title="إدارة المشاريع" icon="project-diagram">

        <x-slot name="headerActions">
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="header-btn btn-primary-gradient auth-perm-projects-create">
                    <x-icon name="plus" size="12" /> إنشاء
                </a>
            @endcan

            @canany(['projects.import', 'projects.export'])
                <div class="dropdown">
                    <button class="header-btn btn-outline-gradient dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <x-icon name="cogs" size="12" /> عمليات
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-artistic">
                        @can('projects.import')
                            <li><a class="dropdown-item auth-perm-projects-import" href="{{ route('projects.import') }}"><x-icon name="file-import" class="text-primary" size="12" /> استيراد</a></li>
                            <li><hr class="dropdown-divider"></li>
                        @endcan
                        @can('projects.export')
                            <li><button class="dropdown-item auth-perm-projects-export w-100 text-start border-0 bg-transparent" onclick="handleExport('excel')"><x-icon name="file-excel" class="text-success" size="12" /> Excel</button></li>
                            <li><button class="dropdown-item auth-perm-projects-export w-100 text-start border-0 bg-transparent" onclick="handleExport('comprehensive')"><x-icon name="download" class="text-success" size="12" /> شامل</button></li>
                            <li><button class="dropdown-item auth-perm-projects-export w-100 text-start border-0 bg-transparent" onclick="handleExport('pdf')"><x-icon name="file-pdf" class="text-danger" size="12" /> PDF</button></li>
                            <li><button class="dropdown-item auth-perm-projects-export w-100 text-start border-0 bg-transparent" onclick="handleExport('pivot')"><x-icon name="table" class="text-info" size="12" /> Pivot</button></li>
                        @endcan
                    </ul>
                </div>
            @endcanany
        </x-slot>

        @php
            $page_statuses = $projects->pluck('status')->unique()->filter();
            $all_internal_entities = App\Models\InternalEntity::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        @endphp

        <x-slot name="filters">
            <form id="auto-filter-form" action="{{ route('projects.index') }}" method="GET">
                <div class="filter-card">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-2">
                            <label for="search" class="form-label">بحث</label>
                            <input type="text" class="form-control auto-filter" id="search" name="search" value="{{ request('search') }}" placeholder="اسم المشروع...">
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="organization" class="form-label">الجهة</label>
                            <select class="form-select auto-filter" id="organization" name="organization" onchange="this.form.submit()">
                                <option value="">الكل</option>
                                @if(isset($entities) && $entities->isNotEmpty())
                                    @foreach($entities as $entity)
                                        <option value="{{ $entity->id }}" {{ (int) request('organization', request('entity')) === (int) $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-6 col-md-1">
                            <label for="hijri_year" class="form-label">العام</label>
                            <select class="form-select auto-filter" id="hijri_year" name="hijri_year" onchange="this.form.submit()">
                                <option value="">الكل</option>
                                @if(isset($hijriYears))
                                    @foreach($hijriYears as $year)
                                        <option value="{{ $year }}" {{ request('hijri_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="governorate" class="form-label">المحافظة</label>
                            <select class="form-select auto-filter" id="governorate" name="governorate" onchange="this.form.submit()">
                                <option value="">الكل</option>
                                @foreach($governorates as $gov)
                                    <option value="{{ $gov->id }}" {{ request('governorate') == $gov->id ? 'selected' : '' }}>{{ $gov->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="domain" class="form-label">المجال</label>
                            <select class="form-select auto-filter" id="domain" name="domain" onchange="this.form.submit()">
                                <option value="">الكل</option>
                                @foreach($domains as $domain)
                                    <option value="{{ $domain->id }}" {{ request('domain') == $domain->id ? 'selected' : '' }}>{{ $domain->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-1">
                            <label for="project_type" class="form-label">النوع</label>
                            <select class="form-select auto-filter" id="project_type" name="project_type" onchange="this.form.submit()">
                                <option value="">الكل</option>
                                <option value="new" {{ request('project_type') === 'new' ? 'selected' : '' }}>جديد</option>
                                <option value="old" {{ request('project_type') === 'old' ? 'selected' : '' }}>قديم</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-1">
                            <label for="status" class="form-label">الحالة</label>
                            <select class="form-select auto-filter" id="status" name="status" onchange="this.form.submit()">
                                <option value="">الكل</option>
                                @foreach($page_statuses as $status_key)
                                    @if(isset($statuses[$status_key]))
                                        <option value="{{ $status_key }}" {{ request('status') === (string) $status_key ? 'selected' : '' }}>{{ $statuses[$status_key] }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">الفرز</label>
                            <div class="d-flex gap-1">
                                <select name="sort_by" class="form-select auto-filter" onchange="this.form.submit()">
                                    <option value="form_number" {{ request('sort_by') == 'form_number' || !request('sort_by') ? 'selected' : '' }}>رقم</option>
                                    <option value="project_name" {{ request('sort_by') == 'project_name' ? 'selected' : '' }}>اسم</option>
                                    <option value="project_type" {{ request('sort_by') == 'project_type' ? 'selected' : '' }}>نوع</option>
                                    <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>حالة</option>
                                    <option value="created_by_entity" {{ request('sort_by') == 'created_by_entity' ? 'selected' : '' }}>جهة</option>
                                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>تاريخ</option>
                                    <option value="hijri_year" {{ request('sort_by') == 'hijri_year' ? 'selected' : '' }}>هجري</option>
                                </select>
                                <select name="sort_order" class="form-select auto-filter" style="width: 45px; flex-shrink: 0; padding: 0.2rem;" onchange="this.form.submit()">
                                    <option value="desc" {{ request('sort_order') == 'desc' || !request('sort_order') ? 'selected' : '' }}>▼</option>
                                    <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>▲</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-auto d-flex align-items-end">
                            <a href="{{ route('projects.index') }}" class="header-btn btn-outline-gradient" title="إعادة تعيين">
                                <x-icon name="refresh-ccw" size="12" />
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="table">
            <div class="projects-table-wrapper">
                <table class="table projects-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center no-wrap"><input type="checkbox" id="select-all-projects" class="custom-checkbox"></th>
                            <th class="text-center no-wrap">#</th>
                            <th class="text-center no-wrap">رقم المشروع</th>
                            <th>اسم المشروع</th>
                            <th class="text-center no-wrap">النوع</th>
                            <th>البرنامج</th>
                            <th>المجال</th>
                            <th>المجال الفرعي</th>
                            <th class="text-center no-wrap">المرحلة</th>
                            <th class="text-center no-wrap">الحالة</th>
                            <th class="text-center no-wrap hide-lg">المنشئ</th>
                            <th class="hide-xl">الجهة</th>
                            <th class="text-center no-wrap">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                            @php
                                $isOldProject = $project->project_type === 'old';
                                $isDraft = in_array($project->status, ['draft', 'completed_draft', 'rolled_back_for_review']) && !$isOldProject;
                                $isReturned = $project->status === 'rolled_back_for_review' && !$isOldProject;
                                $isFinal = $project->status === 'final';
                                $isInProgress = in_array($project->status, ['in_progress', 'implementation', 'in_execution']);
                                $isPendingApproval = $project->status === 'pending_approval' || $project->approval_status === 'pending';
                                $isDraftComplete = $project->status === 'completed_draft' || $project->is_data_completed;
                                $isDraftIncomplete = !$isDraftComplete;
                                $isFirstStage = ($project->current_stage_order ?? 1) == 1;
                                // شرط أن المشروع في حالة مسودة (أي من الأنواع الثلاثة)
                                $isDraftStatus = in_array($project->status, ['draft', 'completed_draft', 'rolled_back_for_review']) && !$isOldProject;

                                $currentStage = null;
                                if ($isPendingApproval) {
                                    $currentStage = $project->current_stage_name;
                                } elseif ($isInProgress) {
                                    $currentStage = 'التنفيذ';
                                }

                                $approvalBadge = null;
                                if ($isPendingApproval) {
                                    $currentApproval = $project->projectApprovals
                                        ->where('drop', $project->current_stage)
                                        ->where('is_completed', false)
                                        ->sortByDesc('id')
                                        ->first();
                                    if ($currentApproval) {
                                        $statusMaps = [
                                            'pending' => ['class' => 'bg-warning-subtle text-warning', 'label' => 'بانتظار'],
                                            'approved' => ['class' => 'bg-success-subtle text-success', 'label' => 'موافق'],
                                            'rejected' => ['class' => 'bg-danger-subtle text-danger', 'label' => 'مرفوض'],
                                            'need_action' => ['class' => 'bg-danger-subtle text-danger', 'label' => 'إجراء'],
                                            'financial_technical_review' => ['class' => 'bg-info-subtle text-info', 'label' => 'مراجعة'],
                                            'referral' => ['class' => 'bg-secondary-subtle text-secondary', 'label' => 'إحالة'],
                                            'resubmitted' => ['class' => 'bg-primary-subtle text-primary', 'label' => 'إعادة'],
                                        ];
                                        $approvalBadge = $statusMaps[$currentApproval->status] ?? ['class' => 'bg-warning-subtle text-warning', 'label' => 'بانتظار'];
                                    } else {
                                        $approvalBadge = ['class' => 'bg-warning-subtle text-warning', 'label' => 'بانتظار'];
                                    }
                                } elseif ($project->approval_status === 'action_requested') {
                                    $approvalBadge = ['class' => 'bg-danger-subtle text-danger', 'label' => 'إجراء'];
                                } elseif ($project->approval_status === 'approved') {
                                    $approvalBadge = ['class' => 'bg-success-subtle text-success', 'label' => 'معتمد'];
                                } elseif ($project->approval_status === 'rejected') {
                                    $approvalBadge = ['class' => 'bg-secondary-subtle text-secondary', 'label' => 'مرفوض'];
                                }

                                $reviewIcons = [];
                                if (in_array($project->status, ['internally_approved', 'final', 'in_progress', 'completed'])) {
                                    if ($project->isReviewed()) {
                                        $reviewIcons[] = 'reviewed_completed';
                                    } else {
                                        $reviewIcons[] = $project->financial_status === 'completed' ? 'financial_done' : 'financial';
                                        $reviewIcons[] = $project->technical_status === 'completed' ? 'technical_done' : 'technical';
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" name="selected_projects[]" value="{{ $project->id }}" class="custom-checkbox project-checkbox"></td>
                                <td class="text-center"><span class="fw-bold text-primary" style="font-size:0.68rem;">{{ $loop->iteration }}</span></td>
                                <td class="text-center"><span class="project-number">{{ $project->form_number ?? 'ـ' }}</span></td>
                                <td>
                                    <div class="text-truncate-custom" title="{{ $project->project_name }}">
                                        {{ $project->project_name ?: ($isDraft ? 'مسودة' : 'غير معنون') }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($project->project_type === 'old')
                                        <span class="badge-artistic badge-old">قديم</span>
                                    @else
                                        <span class="badge-artistic badge-new">جديد</span>
                                    @endif
                                </td>
                                <td><div class="text-truncate-custom text-muted" title="{{ optional($project->program)->name }}">{{ optional($project->program)->name ?? 'ـ' }}</div></td>
                                <td><div class="text-truncate-custom text-muted" title="{{ optional($project->domain)->name }}">{{ optional($project->domain)->name ?? 'ـ' }}</div></td>
                                <td><div class="text-truncate-custom text-muted" title="{{ optional($project->subdomain)->name }}">{{ optional($project->subdomain)->name ?? 'ـ' }}</div></td>
                                <td class="text-center">
                                    @if($isOldProject)
                                        <span class="badge-artistic badge-old"><x-icon name="history" size="8" /> سابقة</span>
                                    @else
                                        <span class="badge-artistic badge-stage" title="{{ $currentStage ?? $project->current_stage_name }}">
                                            <x-icon name="layer-group" size="8" /> {{ \Illuminate\Support\Str::limit($currentStage ?? $project->current_stage_name, 12) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isOldProject)
                                        @if(!$project->entity_modified)
                                            <span class="badge-artistic badge-waiting-entity" title="بانتظار تعديل الجهة"><x-icon name="exclamation-circle" size="8" /> تعديل جهة</span>
                                        @elseif(!$project->is_data_completed)
                                            <span class="badge-artistic badge-waiting-complete" title="بانتظار الاستكمال"><x-icon name="list" size="8" /> استكمال</span>
                                        @else
                                            <span class="badge-artistic badge-ready" title="جاهز للإنجاز"><x-icon name="check-circle" size="8" /> جاهز</span>
                                        @endif
                                    @else
                                        @if($isReturned)
                                            <span class="badge-artistic badge-returned" title="أُعيد للمراجعة"><x-icon name="undo" size="8" /> إعادة</span>
                                        @elseif($isDraftIncomplete)
                                            <span class="badge-artistic badge-draft-incomplete" title="خطوة {{ $project->last_saved_step ?? 1 }}"><x-icon name="file-alt" size="8" /> مسودة {{ $project->last_saved_step ?? 1 }}</span>
                                        @elseif(in_array($project->status, ['draft', 'completed_draft']))
                                            <span class="badge-artistic badge-draft-complete" title="بانتظار الإرسال"><x-icon name="check-double" size="8" /> مكتملة</span>
                                        @elseif($project->status === 'in_execution')
                                            <span class="badge-artistic badge-execution"><x-icon name="play" size="8" /> تنفيذ</span>
                                            <div class="progress-artistic mx-auto" style="width: 55px;" title="{{ $project->execution_progress }}%"><div class="progress-bar" style="width: {{ $project->execution_progress }}%;"></div></div>
                                        @endif

                                        @if($approvalBadge && $project->status !== 'in_execution' && !in_array($project->status, ['draft', 'completed_draft', 'rolled_back_for_review']))
                                            <span class="badge {{ $approvalBadge['class'] }} badge-artistic mt-1">{{ $approvalBadge['label'] }}</span>
                                        @endif

                                        @if(count($reviewIcons) > 0)
                                            <div class="d-flex align-items-center justify-content-center gap-1 mt-1">
                                                @foreach($reviewIcons as $reviewIcon)
                                                    @if($reviewIcon === 'reviewed_completed')
                                                        <span class="review-icon-badge review-done" title="مكتملة"><x-icon name="check-double" size="8" /></span>
                                                    @elseif($reviewIcon === 'financial_done')
                                                        <span class="review-icon-badge review-done" title="مالية"><x-icon name="check" size="8" /></span>
                                                    @elseif($reviewIcon === 'financial')
                                                        <span class="review-icon-badge review-pending-financial" title="مالية"><x-icon name="dollar-sign" size="8" /></span>
                                                    @elseif($reviewIcon === 'technical_done')
                                                        <span class="review-icon-badge review-done" title="فنية"><x-icon name="check" size="8" /></span>
                                                    @elseif($reviewIcon === 'technical')
                                                        <span class="review-icon-badge review-pending-technical" title="فنية"><x-icon name="tools" size="8" /></span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-center hide-lg"><span class="fw-semibold text-dark" style="font-size:0.68rem;">{{ optional($project->createdBy)->name ?? 'ـ' }}</span></td>
                                <td class="hide-xl"><div class="text-truncate-custom text-muted" title="{{ $project->creator_entity_name }}">{{ $project->creator_entity_name }}</div></td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">
                                        @can('view', $project)
                                            <a href="{{ route('projects.show', $project->id) }}" class="btn-action-artistic btn-view" title="عرض"><x-icon name="eye" size="10" /></a>
                                        @endcan

                                        @if($isOldProject)
                                            @if(!$project->entity_modified)
                                                @can('editEntity', $project)
                                                    <button type="button" class="btn-action-artistic btn-edit btn-trigger-edit-entity" style="background:#e67e22;" title="تعديل جهة"
                                                            data-bs-toggle="modal" data-bs-target="#editEntityModal"
                                                            data-project-name="{{ $project->project_name }}"
                                                            data-form-number="{{ $project->form_number }}"
                                                            data-program-domain="{{ optional($project->program)->name }} / {{ optional($project->domain)->name }}"
                                                            data-current-entity="{{ $project->created_by_entity }}"
                                                            data-creator-entity-id="{{ $project->creator_entity_id }}"
                                                            data-action="{{ route('projects.update-entity', $project) }}"><x-icon name="building" size="10" /></button>
                                                @endcan
                                            @elseif(!$project->is_data_completed)
                                                @can('completeData', $project)
                                                    <a href="{{ route('projects.complete-data', $project->id) }}" class="btn-action-artistic btn-view" style="background:#2c5f8a;" title="استكمال"><i class="fas fa-list-check" style="font-size:9px;"></i></a>
                                                @endcan
                                            @else
                                                @can('update', $project)
                                                    <a href="{{ route('projects.edit', $project->id) }}" class="btn-action-artistic btn-edit" title="تعديل"><x-icon name="edit-2" size="10" /></a>
                                                @endcan
                                                @can('achievements', $project)
                                                    <a href="{{ route('projects.achievements.create', $project->id) }}" class="btn-action-artistic btn-enable" style="background:#c9a961;" title="إنجاز"><i class="fas fa-trophy" style="font-size:9px;"></i></a>
                                                @endcan
                                            @endif
                                        @else
                                            {{-- مشاريع جديدة --}}
                                            @if($isDraftStatus)
                                                {{-- زر إغلاق المسودة (finalize) يظهر إذا كانت مكتملة --}}
                                                @if($isDraftComplete)
                                                    @can('finalize', $project)
                                                        <form action="{{ route('projects.finalize', $project->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn-action-artistic btn-enable" title="إغلاق" onclick="return confirmAction(this, 'هل أنت متأكد؟')"><x-icon name="lock" size="10" /></button>
                                                        </form>
                                                    @endcan
                                                @endif

                                                {{-- زر استئناف (resume) يظهر فقط إذا كانت المسودة غير مكتملة --}}
                                                @if($isDraftIncomplete)
                                                    @can('resume', $project)
                                                        <a href="{{ route('projects.draft.resume', $project->id) }}" class="btn-action-artistic btn-view" style="background:#3a8fc7;" title="استئناف"><x-icon name="play" size="10" /></a>
                                                    @endcan
                                                @endif

                                                {{-- زر تعديل (edit) يظهر فقط إذا كانت المسودة مكتملة --}}
                                                @if($isDraftComplete)
                                                    @can('update', $project)
                                                        <a href="{{ route('projects.edit', $project->id) }}" class="btn-action-artistic btn-edit" title="تعديل"><x-icon name="edit-2" size="10" /></a>
                                                    @endcan
                                                @endif

                                                {{-- زر حذف (delete) يظهر دائماً للمسودات --}}
                                                @can('delete', $project)
                                                    <form action="{{ route('projects.destroy', $project->id) }}" method="POST" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn-action-artistic btn-delete" title="حذف" onclick="return confirmAction(this, 'تأكيد الحذف؟')"><x-icon name="trash-2" size="10" /></button>
                                                    </form>
                                                @endcan
                                            @else
                                                {{-- ليست في حالة مسودة --}}
                                                @if($isFirstStage)
                                                    {{-- زر إعادة إلى مسودة (revert) يظهر فقط في المرحلة الأولى --}}
                                                    @can('revert', $project)
                                                        <form action="{{ route('projects.revert-draft', $project->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn-action-artistic btn-disable" title="مسودة" onclick="return confirmAction(this, 'تحويل لمسودة؟')"><x-icon name="undo" size="10" /></button>
                                                        </form>
                                                    @endcan
                                                @endif

                                                {{-- باقي الأيقونات الأخرى (تنفيذ، جدول، إرسال، مراجعة، إلخ) --}}
                                                @if($isInProgress)
                                                    @can('execute', $project)
                                                        <a href="{{ route('projects.execution', $project->id) }}" class="btn-action-artistic btn-enable" title="تنفيذ"><x-icon name="play" size="10" /></a>
                                                    @endcan
                                                    @can('viewSchedule', $project)
                                                        <a href="{{ route('projects.schedule', $project->id) }}" class="btn-action-artistic btn-log" title="جدول"><x-icon name="calendar-alt" size="10" /></a>
                                                    @endcan
                                                @endif

                                                @if($isFinal && $isPendingApproval)
                                                    @can('submit', $project)
                                                         <button type="button" class="btn-action-artistic btn-view btn-trigger-submit-approval" style="background:#1e3a5f;" title="إرسال"
                                                                 data-bs-toggle="modal" data-bs-target="#submitApprovalModal"
                                                                 data-project-name="{{ $project->project_name }}"
                                                                 data-form-number="{{ $project->form_number }}"
                                                                 data-action="{{ route('projects.approval.submit', $project) }}"><x-icon name="paper-plane" size="10" /></button>
                                                    @endcan
                                                @endif

                                                @if(in_array($project->status, ['financial_review', 'financial_technical_review']) || $project->approval_status === 'financial_technical_review' || $project->projectApprovals->where('status', 'financial_technical_review')->where('is_completed', false)->isNotEmpty())
                                                    @can('reviewTechnical', $project)
                                                        <a href="{{ route('projects.review.technical', $project->id) }}" class="btn-action-artistic btn-log" style="background:#6f42c1;" title="فنية"><x-icon name="tools" size="10" /></a>
                                                    @endcan
                                                    @can('reviewFinancial', $project)
                                                        <a href="{{ route('projects.review.financial', $project->id) }}" class="btn-action-artistic btn-view" style="background:#17a2b8;" title="مالية"><x-icon name="dollar-sign" size="10" /></a>
                                                    @endcan
                                                @endif
                                            @endif
                                        @endif

                                        {{-- القائمة المنسدلة "المزيد" --}}
                                        <div class="dropdown d-inline-block">
                                            <button class="btn-action-artistic btn-more" type="button" id="moreActionsDropdown{{ $project->id }}" data-bs-toggle="dropdown" aria-expanded="false" title="المزيد"><x-icon name="ellipsis-v" size="10" /></button>
                                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-artistic" aria-labelledby="moreActionsDropdown{{ $project->id }}">
                                                @can('print', $project)
                                                    <a class="dropdown-item" href="{{ route('projects.print', $project->id) }}" target="_blank"><x-icon name="print" class="text-success" size="12" /> طباعة</a>
                                                    <a class="dropdown-item" href="{{ route('projects.card.print-reviews', $project->id) }}" target="_blank"><x-icon name="print" class="text-info" size="12" /> ملاحظات</a>
                                                @endcan
                                                @can('view', $project)
                                                    <a class="dropdown-item" href="{{ route('projects.card.show', $project->id) }}" target="_blank"><x-icon name="id-card" class="text-primary" size="12" /> بطاقة</a>
                                                    <a class="dropdown-item" href="{{ route('projects.card.export-pdf', $project->id) }}" target="_blank"><x-icon name="file-pdf" class="text-danger" size="12" /> PDF بطاقة</a>
                                                @endcan
                                                @can('exportWord', $project)
                                                    <a class="dropdown-item" href="{{ route('projects.export-word', $project->id) }}"><x-icon name="file-alt" class="text-primary" size="12" /> Word</a>
                                                @endcan
                                                @can('exportPdf', $project)
                                                    <a class="dropdown-item" href="{{ route('projects.export-pdf', $project->id) }}"><x-icon name="file-pdf" class="text-danger" size="12" /> PDF</a>
                                                @endcan
                                                @can('duplicate', $project)
                                                    <form action="{{ route('projects.duplicate', $project->id) }}" method="POST" class="d-block">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent"><x-icon name="copy" class="text-info" size="12" /> تكرار</button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="empty-state">
                                    <div class="empty-icon"><x-icon name="project-diagram" size="30" class="text-muted" /></div>
                                    <h6>لا توجد مشاريع</h6>
                                    <small class="d-block mb-2">القائمة فارغة أو لا تطابق البحث</small>
                                    @can('create', App\Models\Project::class)
                                        <a href="{{ route('projects.create') }}" class="header-btn btn-primary-gradient"><x-icon name="plus" size="12" /> إنشاء</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>

        <x-slot name="pagination">
            <div class="d-flex justify-content-center mt-2">
                {{ $projects->withQueryString()->links() }}
            </div>
        </x-slot>
        
        <x-slot name="total">
            <span class="badge bg-dark rounded-pill px-2 py-1" style="font-size:0.7rem;">{{ $projects->total() }}</span>
        </x-slot>
    </x-index-page>

    {{-- نافذة إرسال للموافقة الموحدة والخفيفة --}}
    <div class="modal fade modal-artistic" id="submitApprovalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header"><h6 class="modal-title text-white mb-0" style="font-size:0.8rem;">إرسال للموافقة</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <form id="submitApprovalSharedForm" action="" method="POST">
                    @csrf
                    <div class="modal-body py-2">
                        <p class="small mb-1"><strong>المشروع:</strong> <span id="submitApprovalProjectName"></span></p>
                        <p class="small mb-2"><strong>الرقم:</strong> <span id="submitApprovalFormNumber"></span></p>
                        <div class="alert alert-info py-1 small mb-0 rounded-2"><x-icon name="info-circle" size="10" class="me-1" /> لن تتمكن من التعديل بعد الإرسال.</div>
                    </div>
                    <div class="modal-footer py-1">
                        <button type="button" class="header-btn btn-outline-gradient py-1" data-bs-dismiss="modal" style="font-size:0.7rem;">إلغاء</button>
                        <button type="submit" class="header-btn btn-primary-gradient py-1" style="font-size:0.7rem;">إرسال</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- نافذة تعديل الجهة الموحدة والخفيفة --}}
    <div class="modal fade" id="editEntityModal" tabindex="-1" aria-labelledby="editEntityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark py-2">
                    <h6 class="modal-title mb-0" id="editEntityModalLabel">تعديل الجهة المقدمة للمشروع</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editEntitySharedForm" action="" method="POST" onsubmit="return confirm('تنبيه: تغيير الجهة المقدمة هو إجراء نهائي لمرة واحدة فقط ولا يمكن التراجع عنه أو تعديله لاحقًا. هل أنت متأكد من الاستمرار؟');">
                    @csrf
                    @method('PUT')
                    <div class="modal-body py-3">
                        <div class="alert alert-danger small py-2">
                            <strong>تنبيه هام!</strong>
                            تغيير الجهة المقدمة هو إجراء نهائي لمرة واحدة فقط ولا يمكن التراجع عنه أو تعديله لاحقًا.
                        </div>
                        <table class="table table-sm table-bordered small">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 35%;">اسم المشروع</th>
                                    <td id="editEntityProjectName"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">رقم النموذج</th>
                                    <td id="editEntityFormNumber"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">التمويل (برنامج/مجال)</th>
                                    <td id="editEntityProgramDomain"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">الجهة المقدمة الحالية</th>
                                    <td class="text-danger fw-bold" id="editEntityCurrentEntity"></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="mb-3 mt-3">
                            <label for="shared_creator_entity_id" class="form-label small fw-bold">اختر الجهة المقدمة الجديدة</label>
                            <select class="form-select form-select-sm" id="shared_creator_entity_id" name="creator_entity_id" required>
                                <option value="">-- اختر الجهة --</option>
                                @if(isset($entities) && (is_countable($entities) ? count($entities) > 0 : !empty($entities)))
                                    @foreach($entities as $entity)
                                        @php
                                            $entityId = data_get($entity, 'id');
                                            $entityName = data_get($entity, 'displayName') ?? data_get($entity, 'name');
                                        @endphp
                                        <option value="{{ $entityId }}">{{ $entityName }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer py-2 bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning btn-sm fw-bold">حفظ التعديل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/projects-bulk-actions.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('auto-filter-form');
            if (filterForm) {
                filterForm.querySelectorAll('select.auto-filter').forEach(function (select) {
                    select.addEventListener('change', function () { filterForm.submit(); });
                });
                const searchInput = filterForm.querySelector('input#search.auto-filter[type="text"]');
                if (searchInput) {
                    searchInput.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') { e.preventDefault(); filterForm.submit(); }
                    });
                }
                filterForm.querySelectorAll('input.auto-filter:not([type="text"])').forEach(function (input) {
                    input.addEventListener('change', function () { filterForm.submit(); });
                });
            }
            const selectAll = document.getElementById('select-all-projects');
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    document.querySelectorAll('.project-checkbox').forEach(function (cb) { cb.checked = selectAll.checked; });
                });
            }
            document.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('project-checkbox')) {
                    const all = document.querySelectorAll('.project-checkbox');
                    const checked = document.querySelectorAll('.project-checkbox:checked');
                    if (selectAll) selectAll.checked = (all.length > 0 && all.length === checked.length);
                }
            });

            // إعداد نافذة إرسال للموافقة الموحدة
            document.querySelectorAll('.btn-trigger-submit-approval').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const form = document.getElementById('submitApprovalSharedForm');
                    if (form) form.action = this.dataset.action || '';
                    const nameEl = document.getElementById('submitApprovalProjectName');
                    if (nameEl) nameEl.textContent = this.dataset.projectName || '';
                    const numEl = document.getElementById('submitApprovalFormNumber');
                    if (numEl) numEl.textContent = this.dataset.formNumber || '';
                });
            });

            // إعداد نافذة تعديل الجهة الموحدة
            document.querySelectorAll('.btn-trigger-edit-entity').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const form = document.getElementById('editEntitySharedForm');
                    if (form) form.action = this.dataset.action || '';
                    const nameEl = document.getElementById('editEntityProjectName');
                    if (nameEl) nameEl.textContent = this.dataset.projectName || '';
                    const numEl = document.getElementById('editEntityFormNumber');
                    if (numEl) numEl.textContent = this.dataset.formNumber || '';
                    const progEl = document.getElementById('editEntityProgramDomain');
                    if (progEl) progEl.textContent = this.dataset.programDomain || '';
                    const entEl = document.getElementById('editEntityCurrentEntity');
                    if (entEl) entEl.textContent = this.dataset.currentEntity || '';
                    const select = document.getElementById('shared_creator_entity_id');
                    if (select) select.value = this.dataset.creatorEntityId || '';
                });
            });
        });

        window.handleExport = function (type) {
            const selectedIds = [];
            document.querySelectorAll('.project-checkbox:checked').forEach(function (cb) { selectedIds.push(cb.value); });
            const exportType = selectedIds.length > 0 ? 'selected' : 'all';
            let url = '', params = new URLSearchParams();
            switch (type) {
                case 'excel': url = '{{ route("projects.export-excel") }}'; break;
                case 'comprehensive': url = '{{ route("projects.export-excel-comprehensive-all") }}'; break;
                case 'pdf': url = '{{ route("projects.export-pdf-all") }}'; break;
                case 'pivot': url = '{{ route("projects.export-pivot-all") }}'; break;
                default: return;
            }
            params.append('export_type', exportType);
            if (exportType === 'selected') {
                params.append('selected_projects', JSON.stringify(selectedIds));
            } else {
                const urlParams = new URLSearchParams(window.location.search);
                ['program', 'domain', 'subdomain', 'status', 'search', 'priority'].forEach(function (param) {
                    if (urlParams.has(param)) params.append(param, urlParams.get(param));
                });
            }
            window.location.href = url + '?' + params.toString();
        };
    </script>
@endsection
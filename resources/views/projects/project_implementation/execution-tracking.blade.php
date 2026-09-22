@extends('layouts.app')

@section('styles')
<style>
    :root {
        --primary-color: #4f46e5;
        --primary-light: #eef2ff;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --bg-body: #f1f5f9;
        --card-bg: #ffffff;
        --success-soft: #dcfce7;
        --success-text: #166534;
        --danger-soft: #fee2e2;
        --danger-text: #991b1b;
        --warning-soft: #fef3c7;
        --warning-text: #92400e;
        --radius-lg: 16px;
        --radius-md: 12px;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Cairo', sans-serif;
        color: var(--text-dark);
    }

    .dashboard-header {
        background: var(--card-bg);
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        border-radius: var(--radius-md);
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        font-size: 0.95rem;
        color: var(--text-muted);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--card-bg);
        padding: 1.25rem;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        display: flex;
        justify-content: space-between;
        align-items: start;
        border-right: 4px solid var(--primary-color);
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--text-muted);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-top: 0.5rem;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .stat-icon.blue {
        background: var(--primary-color);
    }

    .stat-icon.green {
        background: #22c55e;
    }

    .stat-icon.red {
        background: #ef4444;
    }

    /* Execution Logs Styles */
    .execution-logs-container {
        background: var(--card-bg);
        padding: 1.5rem;
        border-radius: var(--radius-md);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
    }

    .logs-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .logs-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .logs-sort-controls {
        display: flex;
        align-items: center;
    }

    .logs-sort-controls .form-select {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        background-color: var(--card-bg);
        color: var(--text-dark);
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 150px;
    }

    .logs-sort-controls .form-select:hover {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .logs-sort-controls .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        outline: none;
    }

    .logs-sort-controls .form-label {
        color: var(--text-dark);
        font-weight: 600;
        font-size: 0.85rem;
    }

    .execution-log-item {
        padding: 1rem;
        border-left: 4px solid #cbd5e1;
        background: #f8fafc;
        border-radius: 6px;
        margin-bottom: 0.75rem;
        transition: all 0.2s ease;
    }

    .execution-log-item:hover {
        background: #f1f5f9;
        border-left-color: var(--primary-color);
    }

    .execution-log-item.pending {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }

    .execution-log-item.approved {
        border-left-color: #10b981;
        background: #f0fdf4;
    }

    .execution-log-item.rejected {
        border-left-color: #ef4444;
        background: #fef2f2;
    }

    .log-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 0.5rem;
    }

    .log-project-name {
        font-weight: 700;
        color: var(--text-dark);
        font-size: 0.95rem;
    }

    .log-timestamp {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .log-details {
        font-size: 0.85rem;
        color: var(--text-dark);
        margin-top: 0.5rem;
    }

    .log-status-badge {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .log-status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .log-status-approved {
        background: #dcfce7;
        color: #166534;
    }

    .log-status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Filter Section */
    .filter-section {
        background: var(--card-bg);
        padding: 1.5rem;
        border-radius: var(--radius-md);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: flex-end;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-label {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-control, .form-select {
        border: 1px solid #cbd5e1;
        border-radius: var(--radius-md);
        padding: 0.6rem 0.75rem;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    /* Content Card */
    .content-card {
        background: var(--card-bg);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .content-card-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }

    .content-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    /* Table Styles */
    .modern-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .modern-table thead {
        background: #4472c4;
        color: white;
    }

    .modern-table thead th {
        padding: 1rem 0.75rem;
        text-align: right;
        font-weight: 600;
        font-size: 0.85rem;
        border: none;
    }

    .modern-table tbody tr {
        border-bottom: 1px solid #e2e8f0;
        transition: background 0.15s ease;
    }

    .modern-table tbody tr:hover {
        background: #f8fafc;
    }

    .modern-table tbody td {
        padding: 1rem 0.75rem;
        font-size: 0.85rem;
        vertical-align: middle;
    }

    /* Badge Styles */
    .badge-soft {
        padding: 0.4em 0.8em;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-block;
    }

    .badge-soft-success {
        background: var(--success-soft);
        color: var(--success-text);
    }

    .badge-soft-danger {
        background: var(--danger-soft);
        color: var(--danger-text);
    }

    .badge-soft-warning {
        background: var(--warning-soft);
        color: var(--warning-text);
    }

    .badge-soft-info {
        background: #e0f2fe;
        color: #0369a1;
    }

    /* Button Styles */
    .btn-modern {
        padding: 0.5rem 1rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }

    .btn-modern-primary {
        background: var(--primary-color);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
    }

    .btn-modern-primary:hover {
        background: #4338ca;
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.3);
    }

    .btn-modern-success {
        background: #22c55e;
        color: white;
    }

    .btn-modern-success:hover {
        background: #16a34a;
    }

    .btn-modern-danger {
        background: #ef4444;
        color: white;
    }

    .btn-modern-danger:hover {
        background: #dc2626;
    }

    .btn-sm {
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
    }

    /* Action Buttons Group */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .action-buttons a,
    .action-buttons button {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: var(--text-muted);
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-dark);
    }

    /* Execution Logs Empty State */
    .logs-empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--text-muted);
    }

    .logs-scroll-container {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 0.5rem;
    }

    .logs-scroll-container::-webkit-scrollbar {
        width: 6px;
    }

    .logs-scroll-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .logs-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .logs-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-row {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .modern-table {
            font-size: 0.75rem;
        }

        .modern-table th,
        .modern-table td {
            padding: 0.5rem 0.4rem;
        }

        .action-buttons {
            flex-wrap: wrap;
        }

        .log-header {
            flex-direction: column;
            align-items: start;
        }

        .logs-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .logs-sort-controls {
            width: 100%;
        }

        .logs-sort-controls .form-select {
            width: 100%;
        }

        .logs-sort-controls .d-flex {
            flex-direction: column;
            gap: 0.5rem;
        }

        .logs-sort-controls .form-label {
            width: 100%;
        }
    }
</style>
@endsection
@section('content')
<div class="container-fluid px-3 px-md-4">
    {{-- Header Section --}}
    <div class="dashboard-header mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1 class="page-title">
                    <x-icon name="tasks" class="me-2" />متابعة سجلات التنفيذ
                </h1>
                <p class="page-subtitle">استعراض وموافقة على جميع سجلات التنفيذ للمشاريع المعتمدة</p>
            </div>
        </div>
    </div>

    {{-- Statistics Section --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-label">قيد المراجعة</div>
                <div class="stat-value">{{ $stats['pending'] }}</div>
            </div>
            <div class="stat-icon blue">
                <x-icon name="hourglass-half" />
            </div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-label">موافق عليها</div>
                <div class="stat-value">{{ $stats['approved'] }}</div>
            </div>
            <div class="stat-icon green">
                <x-icon name="check-circle" />
            </div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-label">مرفوضة</div>
                <div class="stat-value">{{ $stats['rejected'] }}</div>
            </div>
            <div class="stat-icon red">
                <x-icon name="times-circle" />
            </div>
        </div>
    </div>

    {{-- Execution Logs Section --}}
    <div class="execution-logs-container">
        <div class="logs-header">
            <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-3">
                <h5 class="logs-title">
                    <x-icon name="history" class="me-2" />سجل التنفيذ الكامل لجميع المشاريع
                </h5>
                <div class="logs-sort-controls">
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <label for="logsSort" class="form-label mb-0" style="font-size: 0.85rem;">ترتيب:</label>
                        <select id="logsSort" class="form-select" style="width: auto; font-size: 0.85rem;" onchange="sortExecutionLogs(this.value)">
                            <option value="date-desc">الأحدث أولاً</option>
                            <option value="date-asc">الأقدم أولاً</option>
                            <option value="amount-desc">أعلى مبلغ</option>
                            <option value="amount-asc">أقل مبلغ</option>
                            <option value="project-asc">اسم المشروع (أ-ي)</option>
                            <option value="status-pending">قيد المراجعة أولاً</option>
                            <option value="status-approved">الموافق عليها أولاً</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="logs-scroll-container">
            @php
                $sortBy = request('logs_sort', 'date-desc');
                
                $allExecutions = collect()
                    ->concat($preliminaryExecutions->map(fn($e) => [...$e->toArray(), 'type' => 'preliminary']))
                    ->concat($executiveExecutions->map(fn($e) => [...$e->toArray(), 'type' => 'executive']));
                
                if ($sortBy === 'date-asc') {
                    $allExecutions = $allExecutions->sortBy('updated_at');
                } elseif ($sortBy === 'amount-desc') {
                    $allExecutions = $allExecutions->sortByDesc(fn($item) => $item['actual_amount'] ?? 0);
                } elseif ($sortBy === 'amount-asc') {
                    $allExecutions = $allExecutions->sortBy(fn($item) => $item['actual_amount'] ?? 0);
                } elseif ($sortBy === 'project-asc') {
                    $allExecutions = $allExecutions->sortBy(fn($item) => $item['project']['project_name'] ?? '');
                } elseif ($sortBy === 'status-pending') {
                    $allExecutions = $allExecutions->sort(function($a, $b) {
                        $statusOrder = ['pending' => 0, 'approved' => 1, 'rejected' => 2];
                        return ($statusOrder[$a['approval_status']] ?? 3) <=> ($statusOrder[$b['approval_status']] ?? 3);
                    });
                } elseif ($sortBy === 'status-approved') {
                    $allExecutions = $allExecutions->sort(function($a, $b) {
                        $statusOrder = ['approved' => 0, 'pending' => 1, 'rejected' => 2];
                        return ($statusOrder[$a['approval_status']] ?? 3) <=> ($statusOrder[$b['approval_status']] ?? 3);
                    });
                } else {
                    $allExecutions = $allExecutions->sortByDesc('updated_at');
                }
            @endphp

            @if($allExecutions->count() > 0)
                @foreach($allExecutions as $log)
                    <div class="execution-log-item {{ $log['approval_status'] }}">
                        <div class="log-header">
                            <div>
                                <div class="log-project-name">
                                    {{ $log['project']['project_name'] ?? 'مشروع غير متوفر' }}
                                    <span class="text-muted ms-2" style="font-size: 0.8rem;">
                                        ({{ $log['project']['form_number'] ?? '' }})
                                    </span>
                                </div>
                                <div class="log-details">
                                    <strong>النوع:</strong>
                                    {{ $log['type'] === 'preliminary' ? 'نشاط تحضيري' : 'نشاط تنفيذي' }}
                                    @if($log['type'] === 'preliminary')
                                        <strong class="ms-3">الإجراء:</strong> 
                                        {{ isset($log['procedure']) && $log['procedure'] ? $log['procedure']['procedure_name'] ?? 'غير متوفر' : 'غير متوفر' }}
                                    @else
                                        <strong class="ms-3">الإجراء:</strong> 
                                        {{ isset($log['action']) && $log['action'] ? $log['action']['action'] ?? 'غير متوفر' : 'غير متوفر' }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="log-status-badge log-status-{{ $log['approval_status'] }}">
                                    @if($log['approval_status'] === 'pending')
                                        قيد المراجعة
                                    @elseif($log['approval_status'] === 'approved')
                                        موافق عليه
                                    @else
                                        مرفوض
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="log-details mt-2" style="font-size: 0.8rem;">
                            <div class="mb-2">
                                <strong>المبلغ:</strong> {{ number_format($log['actual_amount'] ?? 0, 2) }} ﷼
                                @if(isset($log['completion_percentage']))
                                    <strong class="ms-3">النسبة المئوية:</strong> {{ number_format($log['completion_percentage'], 1) }}%
                                @endif
                            </div>

                            @if($log['approval_status'] === 'approved' && isset($log['approved_at']))
                                <div class="mb-2 text-success">
                                    <x-icon name="check-circle" class="me-1" />
                                    <strong>وافق عليه:</strong> {{ $log['approvedBy']['name'] ?? 'غير متوفر' }}
                                    في {{ \Carbon\Carbon::parse($log['approved_at'])->format('Y-m-d H:i') }}
                                </div>
                            @elseif($log['approval_status'] === 'rejected' && isset($log['rejected_at']))
                                <div class="mb-2 text-danger">
                                    <x-icon name="times-circle" class="me-1" />
                                    <strong>رفضه:</strong> {{ $log['rejectedBy']['name'] ?? 'غير متوفر' }}
                                    في {{ \Carbon\Carbon::parse($log['rejected_at'])->format('Y-m-d H:i') }}
                                </div>
                                @if(isset($log['rejection_reason']))
                                    <div class="alert alert-danger py-1 px-2" style="font-size: 0.8rem;">
                                        <strong>سبب الرفض:</strong> {{ $log['rejection_reason'] }}
                                    </div>
                                @endif
                            @endif

                            <div class="text-muted mt-2">
                                <x-icon name="clock" class="me-1" />
                                <span class="log-timestamp">
                                    آخر تحديث: {{ isset($log['updated_at']) ? \Carbon\Carbon::parse($log['updated_at'])->diffForHumans() : 'غير متوفر' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="logs-empty-state">
                    <x-icon name="inbox" style="font-size: 2.5rem; width: 2.5rem; height: 2.5rem; opacity: 0.5;" />
                    <p class="mt-2">لا توجد سجلات تنفيذ حالياً</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="filter-section">
        <form method="GET" action="{{ route('execution.tracking') }}" id="filterForm">
            <div class="filter-row">
                <div class="form-group">
                    <label for="status" class="form-label">حالة الموافقة</label>
                    <select name="status" id="status" class="form-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>موافق عليه</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>الكل</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type" class="form-label">نوع السجل</label>
                    <select name="type" id="type" class="form-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>الكل</option>
                        <option value="preliminary" {{ $type === 'preliminary' ? 'selected' : '' }}>الأنشطة التحضيرية</option>
                        <option value="executive" {{ $type === 'executive' ? 'selected' : '' }}>الأنشطة التنفيذية</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_id" class="form-label">المشروع</label>
                    <select name="project_id" id="project_id" class="form-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="">اختر مشروعاً</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ $projectId == $project->id ? 'selected' : '' }}>
                                {{ $project->project_name }} ({{ $project->form_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="search" class="form-label">بحث</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="ابحث باسم المشروع..." value="{{ $searchTerm }}">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-modern btn-modern-primary w-100">
                        <x-icon name="search" class="me-1" />بحث
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Preliminary Executions --}}
    @if($type === 'preliminary' || $type === 'all')
    <div class="content-card mb-4">
        <div class="content-card-header">
            <h5 class="content-card-title">
                <x-icon name="cogs" class="me-2" />سجلات الأنشطة التحضيرية
                <span class="badge bg-info ms-2">{{ $preliminaryExecutions->count() }}</span>
            </h5>
        </div>
        <div class="table-responsive">
            @if($preliminaryExecutions->count() > 0)
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="15%">اسم المشروع</th>
                            <th width="15%">الإجراء</th>
                            <th width="10%">الحالة</th>
                            <th width="8%">المبلغ الفعلي</th>
                            <th width="8%">تاريخ البداية</th>
                            <th width="8%">تاريخ النهاية</th>
                            <th width="12%">المنشئ</th>
                            <th width="10%">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preliminaryExecutions as $execution)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ optional($execution->project)->project_name ?? 'غير متوفر' }}</div>
                                    <small class="text-muted">{{ optional($execution->project)->form_number ?? '' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ optional($execution->procedure)->procedure_name ?? 'غير متوفر' }}</div>
                                </td>
                                <td>
                                    @if($execution->approval_status === 'pending')
                                        <span class="badge-soft badge-soft-warning">قيد المراجعة</span>
                                    @elseif($execution->approval_status === 'approved')
                                        <span class="badge-soft badge-soft-success">✓ موافق عليه</span>
                                    @else
                                        <span class="badge-soft badge-soft-danger">✗ مرفوض</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($execution->actual_amount, 2) }} ﷼</td>
                                <td>{{ optional($execution->actual_start_date_gregorian)?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ optional($execution->actual_finish_date_gregorian)?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ optional($execution->createdBy)->name ?? 'غير متوفر' }}</td>
                                <td>
                                    <div class="action-buttons">
                                        @can('execution.view')
                                        <a href="{{ route('projects.execution', $execution->project) }}" 
                                           class="btn btn-sm btn-modern btn-modern-primary"
                                           title="عرض التفاصيل">
                                            <x-icon name="eye" />
                                        </a>
                                        @endcan
                                        @if($execution->approval_status === 'pending' && auth()->user()->hasPermission('execution.approve'))
                                            <button type="button" class="btn btn-sm btn-modern btn-modern-success approve-execution-btn"
                                                    data-execution-id="{{ $execution->id }}"
                                                    data-execution-type="preliminary"
                                                    data-project-id="{{ $execution->project->id }}"
                                                    title="الموافقة">
                                                <x-icon name="check" />
                                            </button>
                                        @endif
                                        @if($execution->approval_status === 'pending' && auth()->user()->hasPermission('execution.reject'))
                                            <button type="button" class="btn btn-sm btn-modern btn-modern-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal{{ $execution->id }}"
                                                    title="الرفض">
                                                <x-icon name="times" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Rejection Modal --}}
                            @if($execution->approval_status === 'pending')
                                <div class="modal fade" id="rejectModal{{ $execution->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">رفض سجل التنفيذ</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            @can('execution.reject')
                                            <form action="{{ route('projects.execution.preliminary.reject', [$execution->project, $execution]) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label"><strong>سبب الرفض</strong></label>
                                                        <textarea class="form-control" name="rejection_reason" rows="4" 
                                                                  placeholder="أدخل السبب التفصيلي..." minlength="10"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" class="btn btn-danger">رفض</button>
                                                </div>
                                            </form>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <x-icon name="inbox" size="48" />
                    </div>
                    <div class="empty-state-title">لا توجد سجلات</div>
                    <p>لا توجد سجلات تنفيذ تحضيرية تطابق معايير البحث</p>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Executive Executions --}}
    @if($type === 'executive' || $type === 'all')
    <div class="content-card">
        <div class="content-card-header">
            <h5 class="content-card-title">
                <x-icon name="tasks" class="me-2" />سجلات الأنشطة التنفيذية
                <span class="badge bg-info ms-2">{{ $executiveExecutions->count() }}</span>
            </h5>
        </div>
        <div class="table-responsive">
            @if($executiveExecutions->count() > 0)
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="15%">اسم المشروع</th>
                            <th width="15%">الإجراء</th>
                            <th width="10%">الحالة</th>
                            <th width="8%">المبلغ الفعلي</th>
                            <th width="8%">تاريخ البداية</th>
                            <th width="8%">تاريخ النهاية</th>
                            <th width="12%">النسبة المئوية</th>
                            <th width="10%">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($executiveExecutions as $execution)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ optional($execution->project)->project_name ?? 'غير متوفر' }}</div>
                                    <small class="text-muted">{{ optional($execution->project)->form_number ?? '' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ optional($execution->action)->action ?? 'غير متوفر' }}</div>
                                </td>
                                <td>
                                    @if($execution->approval_status === 'pending')
                                        <span class="badge-soft badge-soft-warning">قيد المراجعة</span>
                                    @elseif($execution->approval_status === 'approved')
                                        <span class="badge-soft badge-soft-success">✓ موافق عليه</span>
                                    @else
                                        <span class="badge-soft badge-soft-danger">✗ مرفوض</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($execution->actual_amount, 2) }} ﷼</td>
                                <td>{{ optional($execution->actual_start_date_gregorian)?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ optional($execution->actual_finish_date_gregorian)?->format('Y-m-d') ?? '-' }}</td>
                                <td class="text-center">{{ number_format($execution->completion_percentage, 1) }}%</td>
                                <td>
                                    <div class="action-buttons">
                                        @can('execution.view')
                                        <a href="{{ route('projects.execution', $execution->project) }}" 
                                           class="btn btn-sm btn-modern btn-modern-primary"
                                           title="عرض التفاصيل">
                                            <x-icon name="eye" />
                                        </a>
                                        @endcan
                                        @if($execution->approval_status === 'pending' && auth()->user()->hasPermission('execution.approve'))
                                            <button type="button" class="btn btn-sm btn-modern btn-modern-success approve-execution-btn"
                                                    data-execution-id="{{ $execution->id }}"
                                                    data-execution-type="executive"
                                                    data-project-id="{{ $execution->project->id }}"
                                                    title="الموافقة">
                                                <x-icon name="check" />
                                            </button>
                                        @endif
                                        @if($execution->approval_status === 'pending' && auth()->user()->hasPermission('execution.reject'))
                                            <button type="button" class="btn btn-sm btn-modern btn-modern-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal{{ $execution->id }}"
                                                    title="الرفض">
                                                <x-icon name="times" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Rejection Modal --}}
                            @if($execution->approval_status === 'pending')
                                <div class="modal fade" id="rejectModal{{ $execution->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">رفض سجل التنفيذ</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            @can('execution.reject')
                                            <form action="{{ route('projects.execution.executive.reject', [$execution->project, $execution]) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label"><strong>سبب الرفض</strong></label>
                                                        <textarea class="form-control" name="rejection_reason" rows="4" 
                                                                  placeholder="أدخل السبب التفصيلي..." minlength="10"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" class="btn btn-danger">رفض</button>
                                                </div>
                                            </form>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <x-icon name="inbox" size="48" />
                    </div>
                    <div class="empty-state-title">لا توجد سجلات</div>
                    <p>لا توجد سجلات تنفيذ تنفيذية تطابق معايير البحث</p>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>

<script>
    function sortExecutionLogs(sortBy) {
        const params = new URLSearchParams(window.location.search);
        params.set('logs_sort', sortBy);
        
        const url = new URL(window.location);
        url.search = params.toString();
        
        window.location.href = url.toString();
    }

    document.querySelectorAll('.approve-execution-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const executionId = this.getAttribute('data-execution-id');
            const executionType = this.getAttribute('data-execution-type');
            const projectId = this.getAttribute('data-project-id');

            const routeName = executionType === 'preliminary' 
                ? 'projects.execution.preliminary.approve'
                : 'projects.execution.executive.approve';

            fetch(`/projects/${projectId}/execution/${executionType}/${executionId}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const logsSort = new URLSearchParams(window.location.search).get('logs_sort');
        if (logsSort) {
            document.getElementById('logsSort').value = logsSort;
        }
    });
</script>
@endsection
@extends('layouts.app')

@section('styles')
    <style>
        /* ===============================
               المتغيرات الأساسية
               =============================== */
        :root {
            --surface: #ffffff;
            --surface-alt: #fafbfc;
            --surface-hover: #f5f7fa;
            --border: #eef0f4;
            --border-strong: #dfe3ea;
            --text-primary: #1a1d23;
            --text-secondary: #5f6672;
            --text-muted: #9099a8;
            --accent: #4f6ef7;
            --accent-soft: #eef1fe;
            --accent-hover: #3d5ce5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        body {
            background: #f4f6fa;
        }

        /* ===============================
               التخطيط الرئيسي
               =============================== */
        .task-page {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.5rem;
            align-items: start;
        }

        @media (max-width: 1199.98px) {
            .task-page {
                grid-template-columns: 1fr;
            }
        }

        /* ===============================
               شريط التنقل العلوي
               =============================== */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .breadcrumb-modern {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.82rem;
        }

        .breadcrumb-modern li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
        }

        .breadcrumb-modern li a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .breadcrumb-modern li a:hover {
            color: var(--accent);
        }

        .breadcrumb-modern li.active {
            color: var(--text-primary);
            font-weight: 600;
        }

        .breadcrumb-modern .separator {
            color: var(--border-strong);
            font-size: 0.7rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-secondary);
            background: var(--surface);
            border: 1px solid var(--border);
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-back:hover {
            color: var(--text-primary);
            border-color: var(--border-strong);
            background: var(--surface-hover);
        }

        /* ===============================
               عنوان المهمة
               =============================== */
        .task-title-block {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.75rem 2rem;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .task-title-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .task-title-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: var(--accent-soft);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .task-title-text {
            flex: 1;
            min-width: 0;
        }

        .task-title-text h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.35rem 0;
            line-height: 1.35;
        }

        .task-id-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: var(--surface-alt);
            color: var(--text-muted);
            padding: 0.2rem 0.55rem;
            border-radius: 5px;
            font-size: 0.72rem;
            font-weight: 600;
            font-family: 'SF Mono', Monaco, monospace;
            border: 1px solid var(--border);
        }

        .task-meta-row {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .meta-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 600;
            border: 1px solid;
        }

        .meta-tag i {
            font-size: 0.68rem;
        }

        .meta-tag.status-todo {
            background: #f5f7fa;
            color: #5f6672;
            border-color: #eef0f4;
        }

        .meta-tag.status-in_progress {
            background: #eef1fe;
            color: #4f6ef7;
            border-color: #d6ddfc;
        }

        .meta-tag.status-completed {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }

        .meta-tag.status-cancelled {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .meta-tag.priority-low {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }

        .meta-tag.priority-medium {
            background: #fffbeb;
            color: #d97706;
            border-color: #fde68a;
        }

        .meta-tag.priority-high {
            background: #fff7ed;
            color: #ea580c;
            border-color: #fed7aa;
        }

        .meta-tag.priority-urgent {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .meta-tag.date {
            background: #f0fdfa;
            color: #0d9488;
            border-color: #99f6e4;
        }

        .meta-tag.user {
            background: #faf5ff;
            color: #7c3aed;
            border-color: #e9d5ff;
        }

        /* ===============================
               التبويبات - Underline Style
               =============================== */
        .tabs-bar {
            display: flex;
            gap: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: 0 0.5rem;
            overflow-x: auto;
            scrollbar-width: none;
            border-bottom: none;
        }

        .tabs-bar::-webkit-scrollbar {
            display: none;
        }

        .tab-link {
            color: var(--text-secondary);
            font-weight: 500;
            padding: 1rem 1.25rem;
            border: none;
            background: transparent;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            white-space: nowrap;
            cursor: pointer;
            margin-bottom: -1px;
        }

        .tab-link:hover {
            color: var(--accent);
        }

        .tab-link.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
            font-weight: 600;
        }

        .tab-link i {
            font-size: 0.85rem;
        }

        /* ===============================
               لوحة محتوى التبويبات
               =============================== */
        .tab-content-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            min-height: 500px;
            padding: 1.5rem;
        }

        /* ===============================
               الشريط الجانبي
               =============================== */
        .side-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .panel-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .panel-card-header {
            padding: 0.9rem 1.15rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.55rem;
            background: var(--surface-alt);
        }

        .panel-card-header .panel-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        .panel-icon.blue {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .panel-icon.purple {
            background: #faf5ff;
            color: #a855f7;
        }

        .panel-icon.orange {
            background: #fff7ed;
            color: #f97316;
        }

        .panel-card-header h6 {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            flex: 1;
        }

        .panel-card-body {
            padding: 1.15rem;
        }

        /* ===============================
               بطاقة الإجراء التنفيذي
               =============================== */
        .exec-action-name {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.85rem;
            line-height: 1.45;
        }

        .progress-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0.5rem;
        }

        .progress-row .label {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .progress-row .value {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
        }

        .progress-track {
            height: 6px;
            background: var(--surface-alt);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .progress-track .fill {
            height: 100%;
            background: var(--accent);
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        .progress-footnote {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* ===============================
               حقول الإعدادات
               =============================== */
        .field-group {
            margin-bottom: 0.9rem;
        }

        .field-group:last-child {
            margin-bottom: 0;
        }

        .field-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .field-group .form-select,
        .field-group .form-control {
            padding: 0.45rem 0.7rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-primary);
            transition: all 0.2s;
            width: 100%;
        }

        .field-group .form-select:hover,
        .field-group .form-control:hover {
            border-color: var(--border-strong);
        }

        .field-group .form-select:focus,
        .field-group .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(79, 110, 247, 0.08);
            outline: none;
        }

        .field-group .form-select:disabled,
        .field-group .form-control:disabled {
            background: var(--surface-alt);
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* ===============================
               معلومات الإنشاء
               =============================== */
        .info-strip {
            border-top: 1px solid var(--border);
            padding: 0.9rem 1.15rem;
            background: var(--surface-alt);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.4rem 0;
            font-size: 0.78rem;
        }

        .info-row:not(:last-child) {
            border-bottom: 1px dashed var(--border);
        }

        .info-row .info-label {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .info-row .info-label i {
            font-size: 0.7rem;
        }

        .info-row .info-value {
            color: var(--text-primary);
            font-weight: 600;
        }

        .info-row .info-value.success {
            color: var(--success);
        }

        /* ===============================
               أزرار الإجراءات
               =============================== */
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.6rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 600;
            border: 1px solid;
            transition: all 0.2s;
            cursor: pointer;
            background: var(--surface);
            width: 100%;
        }

        .quick-action-btn.edit {
            color: #d97706;
            border-color: #fde68a;
        }

        .quick-action-btn.edit:hover {
            background: #fffbeb;
            border-color: #fcd34d;
        }

        .quick-action-btn.delete {
            color: #dc2626;
            border-color: #fecaca;
        }

        .quick-action-btn.delete:hover {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        /* ===============================
               Modal
               =============================== */
        .modal-content {
            border: none;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-head {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-head .modal-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: #fffbeb;
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .modal-head h5 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .modal-head small {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .modal-body-custom {
            padding: 1.5rem;
        }

        .modal-body-custom .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
        }

        .modal-body-custom .form-control {
            padding: 0.6rem 0.85rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            font-size: 0.88rem;
            transition: all 0.2s;
        }

        .modal-body-custom .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(79, 110, 247, 0.08);
            outline: none;
        }

        .modal-foot {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            background: var(--surface-alt);
        }

        .btn-modal-cancel {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.5rem 1.1rem;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-modal-cancel:hover {
            background: var(--surface-hover);
            border-color: var(--border-strong);
        }

        .btn-modal-save {
            background: var(--accent);
            border: none;
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-modal-save:hover {
            background: var(--accent-hover);
        }

        /* ===============================
               Avatar
               =============================== */
        .user-avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        /* ===============================
               Animations
               =============================== */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4" dir="rtl" style="max-width: var(--content-max-width);">

        {{-- ===== شريط التنقل العلوي ===== --}}
        <div class="top-bar fade-in">
            <ul class="breadcrumb-modern">
                <li>
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                @if(isset($project) && $project && $project->id)
                    <li class="separator"><i class="fas fa-chevron-left"></i></li>
                    <li><a href="{{ route('projects.index') }}">المشاريع</a></li>
                    <li class="separator"><i class="fas fa-chevron-left"></i></li>
                    <li><a href="{{ route('projects.show', $project->id) }}">{{ Str::limit($project->project_name, 20) }}</a></li>
                    <li class="separator"><i class="fas fa-chevron-left"></i></li>
                    <li><a href="{{ route('projects.tasks.index', $project->id) }}">المهام</a></li>
                @else
                    <li class="separator"><i class="fas fa-chevron-left"></i></li>
                    <li><a href="{{ route('tasks.index') }}">إدارة المهام</a></li>
                @endif
                <li class="separator"><i class="fas fa-chevron-left"></i></li>
                <li class="active">التفاصيل</li>
            </ul>
            @if(isset($project) && $project && $project->id)
                <a href="{{ route('projects.tasks.print', [$project->id, $task->id]) }}" target="_blank"
                    class="btn btn-secondary no-print">طباعة المهمة</a>
                <a href="{{ route('projects.tasks.index', $project->id) }}" class="btn-back">
                    <i class="fas fa-arrow-right"></i>
                    العودة
                </a>
            @else
                <a href="{{ route('tasks.print', $task->id) }}" target="_blank"
                    class="btn btn-secondary no-print">طباعة المهمة</a>
                <a href="{{ route('tasks.index') }}" class="btn-back">
                    <i class="fas fa-arrow-right"></i>
                    العودة
                </a>
            @endif
        </div>

        {{-- ===== عنوان المهمة ===== --}}
        <div class="task-title-block fade-in">
            <div class="task-title-row">
                <div class="task-title-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="task-title-text">
                    <h1>{{ $task->title }}</h1>
                    <span class="task-id-badge">
                        <i class="fas fa-hashtag"></i>
                        {{ $task->id }}
                    </span>
                </div>
            </div>

            <div class="task-meta-row">
                @php
                    $statusLabels = [
                        'todo' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغاة',
                    ];
                    $priorityLabels = [
                        'low' => 'منخفضة',
                        'medium' => 'متوسطة',
                        'high' => 'عالية',
                        'urgent' => 'عاجلة',
                    ];
                @endphp
                <span class="meta-tag status-{{ $task->status }}">
                    <i class="fas fa-circle-dot"></i>
                    {{ $statusLabels[$task->status] ?? $task->status }}
                </span>
                <span class="meta-tag priority-{{ $task->priority }}">
                    <i class="fas fa-flag"></i>
                    أولوية {{ $priorityLabels[$task->priority] ?? $task->priority }}
                </span>
                @if($task->due_date)
                    <span class="meta-tag date">
                        <i class="fas fa-calendar-alt"></i>
                        {{ \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') }}
                    </span>
                @endif
                @if($task->assignedTo)
                    <span class="meta-tag user">
                        <i class="fas fa-user"></i>
                        {{ $task->assignedTo->name }}
                    </span>
                @endif
            </div>
        </div>

        {{-- ===== المحتوى الرئيسي ===== --}}
        <div class="task-page">

            {{-- ===== الجانب الرئيسي: التبويبات ===== --}}
            <div class="fade-in">
                {{-- التبويبات - Underline Style --}}
                <div class="tabs-bar" role="tablist">
                    @can('task.chat.view', $task)
                        <button class="tab-link active" data-tab-target="tab-chat">
                            <i class="fas fa-comments"></i>
                            <span>النقاش</span>
                        </button>
                    @endcan
                    <button class="tab-link" data-tab-target="tab-memos">
                        <i class="fas fa-file-contract"></i>
                        <span>القرارات والمذكرات</span>
                    </button>
                    <button class="tab-link" data-tab-target="tab-attachments">
                        <i class="fas fa-paperclip"></i>
                        <span>المرفقات</span>
                    </button>
                    <button class="tab-link" data-tab-target="tab-notes">
                        <i class="fas fa-sticky-note"></i>
                        <span>ملاحظات التنفيذ</span>
                    </button>
                    @can('task.timeline.view', $task)
                        <button class="tab-link" data-tab-target="tab-timeline" id="tab-timeline-trigger">
                            <i class="fas fa-history"></i>
                            <span>سجل العمليات</span>
                        </button>
                    @endcan
                </div>

                {{-- محتوى التبويبات --}}
                <div class="tab-content-panel">
                    @can('task.chat.view', $task)
                        <div class="tab-pane-content" id="tab-chat">
                            @include('projects.tasks.partials.chat')
                        </div>
                    @endcan

                    <div class="tab-pane-content d-none" id="tab-memos">
                        @include('projects.tasks.partials.memos')
                    </div>

                    <div class="tab-pane-content d-none" id="tab-attachments">
                        @include('projects.tasks.partials.attachments')
                    </div>

                    <div class="tab-pane-content d-none" id="tab-notes">
                        @include('projects.tasks.partials.notes')
                    </div>

                    @can('task.timeline.view', $task)
                        <div class="tab-pane-content d-none" id="tab-timeline">
                            @include('projects.tasks.partials.timeline')
                        </div>
                    @endcan
                </div>
            </div>

            {{-- ===== الشريط الجانبي ===== --}}
            <div class="side-panel fade-in">

                {{-- بطاقة الإجراء التنفيذي --}}
                @if($task->executiveAction)
                    <div class="panel-card">
                        <div class="panel-card-header">
                            <div class="panel-icon blue">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <h6>الإجراء التنفيذي</h6>
                        </div>
                        <div class="panel-card-body">
                            <div class="exec-action-name">
                                {{ $task->executiveAction->action }}
                            </div>
                            <div class="progress-row">
                                <span class="label">نسبة الإنجاز</span>
                                <span class="value">{{ $task->official_execution_progress }}%</span>
                            </div>
                            <div class="progress-track">
                                <div class="fill" style="width: {{ $task->official_execution_progress }}%;"></div>
                            </div>
                            <div class="progress-footnote">
                                <i class="fas fa-info-circle"></i>
                                مقروءة من سجلات التنفيذ الرسمية
                            </div>
                        </div>
                    </div>
                @endif

                {{-- بطاقة إعدادات المهمة --}}
                <div class="panel-card">
                    <div class="panel-card-header">
                        <div class="panel-icon purple">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <h6>إعدادات المهمة</h6>
                    </div>

                    @can('task.edit')
                    <form action="{{ route('projects.tasks.update', [$project->id, $task->id]) }}" method="POST">
                    @endcan
                        @csrf
                        @method('PUT')
                        <div class="panel-card-body">

                            <div class="field-group">
                                <label class="field-label">الحالة</label>
                                <select name="status" class="form-select" onchange="this.form.submit()" @cannot('task.edit', $task) disabled @endcannot>
                                    <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>قيد الانتظار
                                    </option>
                                    <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>قيد
                                        التنفيذ</option>
                                    <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>مكتملة
                                    </option>
                                    <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>ملغاة
                                    </option>
                                </select>
                            </div>

                            <div class="field-group">
                                <label class="field-label">الأولوية</label>
                                <select name="priority" class="form-select" onchange="this.form.submit()" @cannot('task.edit', $task) disabled @endcannot>
                                    <option value="low" {{ $task->priority === 'low' ? 'selected' : '' }}>منخفضة</option>
                                    <option value="medium" {{ $task->priority === 'medium' ? 'selected' : '' }}>متوسطة
                                    </option>
                                    <option value="high" {{ $task->priority === 'high' ? 'selected' : '' }}>عالية</option>
                                    <option value="urgent" {{ $task->priority === 'urgent' ? 'selected' : '' }}>عاجلة</option>
                                </select>
                            </div>

                            <div class="field-group">
                                <label class="field-label">المسند إليه</label>
                                <select name="assigned_to[]" multiple class="form-select select2"
                                    onchange="this.form.submit()" @cannot('task.edit', $task) disabled @endcannot>
                                    <option value="">— غير مسندة —</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $task->assignees->contains('id', $user->id) ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field-group">
                                <label class="field-label">الجهة المرتبطة</label>
                                <select name="project_entities_id" class="form-select" onchange="this.form.submit()"
                                    @cannot('task.edit', $task) disabled @endcannot>
                                    <option value="">— لا يوجد جهة —</option>
                                    @foreach($projectEntities as $entity)
                                        <option value="{{ $entity->id }}" {{ $task->project_entities_id == $entity->id ? 'selected' : '' }}>
                                            {{ $entity->entity_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field-group">
                                <label class="field-label">الإجراء التنفيذي</label>
                                <select name="executive_activity_action_id" class="form-select"
                                    onchange="this.form.submit()" @cannot('task.edit', $task) disabled @endcannot>
                                    <option value="">— لا يوجد إجراء —</option>
                                    @foreach($executiveActions as $action)
                                        <option value="{{ $action->id }}" {{ $task->executive_activity_action_id == $action->id ? 'selected' : '' }}>
                                            {{ $action->action }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field-group">
                                <label class="field-label">تاريخ الاستحقاق</label>
                                <input type="date" name="due_date" class="form-control" value="{{ $task->due_date }}"
                                    onchange="this.form.submit()" @cannot('task.edit', $task) disabled @endcannot>
                            </div>

                            <input type="hidden" name="title" value="{{ $task->title }}">
                            <input type="hidden" name="description" value="{{ $task->description }}">
                        </div>

                        <div class="info-strip">
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-user-plus"></i> أنشأها</span>
                                <span class="info-value">{{ $task->createdBy->name ?? 'غير معروف' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-calendar-plus"></i> تاريخ الإنشاء</span>
                                <span class="info-value">{{ $task->created_at->format('Y-m-d') }}</span>
                            </div>
                            @if($task->completed_at)
                                <div class="info-row">
                                    <span class="info-label"><i class="fas fa-calendar-check"></i> تاريخ الانتهاء</span>
                                    <span
                                        class="info-value success">{{ \Carbon\Carbon::parse($task->completed_at)->format('Y-m-d') }}</span>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- بطاقة الإجراءات السريعة --}}
                @canany(['task.edit', 'task.delete'], $task)
                    <div class="panel-card">
                        <div class="panel-card-header">
                            <div class="panel-icon orange">
                                <i class="fas fa-zap"></i>
                            </div>
                            <h6>إجراءات سريعة</h6>
                        </div>
                        <div class="panel-card-body">
                            <div class="quick-actions">
                                @can('task.edit', $task)
                                    <button class="quick-action-btn edit" data-bs-toggle="modal"
                                        data-bs-target="#editFullTaskModal">
                                        <i class="fas fa-edit"></i>
                                        تعديل العنوان والوصف
                                    </button>
                                @endcan
                                @can('task.delete', $task)
                                    <form action="{{ route('projects.tasks.destroy', [$project->id, $task->id]) }}" method="POST"
                                        onsubmit="return confirmAction(this, 'هل أنت متأكد من حذف هذه المهمة نهائياً؟ لا يمكن التراجع عن هذا الإجراء!')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="quick-action-btn delete">
                                            <i class="fas fa-trash-alt"></i>
                                            حذف المهمة بالكامل
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endcanany
            </div>
        </div>
    </div>

    {{-- ===== Modal: تعديل المهمة ===== --}}
    @can('task.edit', $task)
        <div class="modal fade" id="editFullTaskModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form action="{{ route('projects.tasks.update', [$project->id, $task->id]) }}" method="POST"
                    class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-head">
                        <div class="modal-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <h5>تعديل المهمة</h5>
                            <small>قم بتعديل عنوان ووصف المهمة</small>
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body-custom">
                        <div class="mb-3">
                            <label class="form-label">عنوان المهمة </label>
                            <input type="text" name="title" class="form-control" value="{{ $task->title }}"
                                placeholder="أدخل عنوان المهمة...">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">الوصف والتفاصيل</label>
                            <textarea name="description" class="form-control" rows="5"
                                placeholder="اكتب وصفاً تفصيلياً للمهمة...">{{ $task->description }}</textarea>
                        </div>

                        <input type="hidden" name="status" value="{{ $task->status }}">
                        <input type="hidden" name="priority" value="{{ $task->priority }}">
                        @foreach($task->assignees as $assignee)
                            <input type="hidden" name="assigned_to[]" value="{{ $assignee->id }}">
                        @endforeach
                        <input type="hidden" name="project_entities_id" value="{{ $task->project_entities_id }}">
                        <input type="hidden" name="executive_activity_action_id"
                            value="{{ $task->executive_activity_action_id }}">
                        <input type="hidden" name="due_date" value="{{ $task->due_date }}">
                    </div>

                    <div class="modal-foot">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn-modal-save">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    <script>
        // Tab switching logic
        document.querySelectorAll('.tab-link').forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-tab-target');

                document.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
                button.classList.add('active');

                document.querySelectorAll('.tab-pane-content').forEach(pane => pane.classList.add('d-none'));
                const targetPane = document.getElementById(targetId);
                if (targetPane) {
                    targetPane.classList.remove('d-none');
                }

                localStorage.setItem('task_active_tab_{{ $task->id }}', targetId);

                if (targetId === 'tab-timeline' && typeof loadTaskTimeline === 'function') {
                    loadTaskTimeline();
                }
            });
        });

        // Auto-activate saved tab
        document.addEventListener('DOMContentLoaded', () => {
            const activeTab = localStorage.getItem('task_active_tab_{{ $task->id }}') || 'tab-chat';
            let trigger = document.querySelector(`.tab-link[data-tab-target="${activeTab}"]`);
            if (!trigger) {
                trigger = document.querySelector('.tab-link');
            }
            if (trigger) {
                trigger.click();
            }
        });
    </script>
@endsection
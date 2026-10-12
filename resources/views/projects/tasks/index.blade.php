@extends('layouts.app')

@section('styles')
<style>
    /* ==================================
       Premium Kanban Board Styles
       ================================== */
    .task-board {
        display: flex;
        gap: 1.5rem;
        overflow-x: auto;
        padding: 0.5rem 0.5rem 2rem 0.5rem;
        min-height: 600px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    .task-board::-webkit-scrollbar { height: 8px; }
    .task-board::-webkit-scrollbar-track { background: transparent; }
    .task-board::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }

    .task-column {
        flex: 1;
        min-width: 320px;
        max-width: 360px;
        background: #f1f5f9;
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02);
    }
    .column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px dashed #e2e8f0;
    }
    .column-title {
        font-weight: 800;
        font-size: 1.05rem;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .column-count {
        background: white;
        color: #475569;
        font-size: 0.8rem;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }

    /* ==================================
       Premium Task Card
       ================================== */
    .task-card {
        background: white;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        padding: 1.25rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        position: relative;
        overflow: hidden;
    }
    .task-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: transparent;
        transition: background 0.3s ease;
    }
    .task-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        border-color: #cbd5e1;
    }
    
    .status-todo::before { background: linear-gradient(90deg, #64748b, #94a3b8); }
    .status-in_progress::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .status-completed::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .status-cancelled::before { background: linear-gradient(90deg, #ef4444, #f87171); }

    /* ==================================
       Badges & Typography
       ================================== */
    .priority-badge {
        font-size: 0.75rem;
        font-weight: 800;
        padding: 0.35rem 0.65rem;
        border-radius: 8px;
        width: fit-content;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .priority-low { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .priority-medium { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .priority-high { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
    .priority-urgent { background: #fdf2f8; color: #e11d48; border: 1px solid #fbcfe8; }

    .user-avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark, #1e40af));
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 700;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-transform: uppercase;
    }

    .card-meta-item {
        font-size: 0.85rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 500;
    }

    /* ==================================
       Elegant List View Table
       ================================== */
    .elegant-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .elegant-table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .elegant-table tbody tr {
        transition: all 0.2s ease;
        background: white;
    }
    .elegant-table tbody tr:hover {
        background-color: #f8fafc;
        transform: scale(1.002);
        box-shadow: inset 2px 0 0 0 var(--primary);
    }
    .elegant-table td {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .view-switcher .btn {
        border-radius: 10px;
        font-weight: 600;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }
    .view-switcher .btn.active {
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Animations */
    .animate-fade {
        animation: fadeIn 0.4s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Print button on card */
    .card-print-btn {
        opacity: 0;
        transition: opacity 0.2s;
    }
    .task-card:hover .card-print-btn {
        opacity: 1;
    }

    /* ==================================
       Bulk Delete Styles
       ================================== */
    .bulk-delete-bar {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 12px;
        padding: 0.75rem 1.25rem;
        display: none;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    .bulk-delete-bar.show {
        display: flex;
    }
    .bulk-delete-bar .selected-count {
        font-weight: 600;
        color: #dc2626;
    }
    .btn-danger-soft {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        transition: all 0.2s ease;
    }
    .btn-danger-soft:hover {
        background: #dc2626;
        color: white;
        border-color: #dc2626;
    }
    .task-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #dc2626;
    }

    /* ==================================
       Action Buttons Styles
       ================================== */
    .action-buttons {
        display: flex;
        gap: 0.35rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
        transition: all 0.2s ease;
        font-size: 0.8rem;
        text-decoration: none;
        cursor: pointer;
    }
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    .action-btn-edit:hover {
        border-color: #f59e0b;
        color: #f59e0b;
        background: #fffbeb;
    }
    .action-btn-print:hover {
        border-color: #3b82f6;
        color: #3b82f6;
        background: #eff6ff;
    }
    .action-btn-delete:hover {
        border-color: #ef4444;
        color: #ef4444;
        background: #fef2f2;
    }
    .action-btn-view:hover {
        border-color: #10b981;
        color: #10b981;
        background: #f0fdf4;
    }
    .action-btn-stop:hover {
        border-color: #e11d48;
        color: #e11d48;
        background: #fff1f2;
    }
    .action-btn-stop.is-stopped {
        color: #dc2626;
        background: #fee2e2;
        border-color: #fca5a5;
    }
</style>
@endsection

@section('content')
    <x-index-page title="مساحة عمل المهام - {{ $project->project_name }}" icon="tasks" :paginator="$tasks">
        <x-slot name="headerActions">
            @can('task.create', $project)
            <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary shadow-sm fw-bold px-3 py-2">
                <i class="fas fa-plus me-2"></i>إضافة مهمة جديدة
            </a>
            @endcan
            <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-secondary px-3 py-2">
                <i class="fas fa-arrow-left me-2"></i>العودة للمشروع
            </a>
        </x-slot>

        <x-slot name="breadcrumb">
            <nav aria-label="breadcrumb" class="mb-0">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-muted">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted">المشاريع</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->id) }}" class="text-muted">{{ $project->project_name }}</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold">إدارة المهام</li>
                </ol>
            </nav>
        </x-slot>

        <x-slot name="filters">
            <form method="GET" action="{{ route('projects.tasks.index', $project->id) }}" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="بحث عن مهمة..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        <option value="todo" {{ request('status') === 'todo' ? 'selected' : '' }}>قيد الانتظار</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتملة</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الأولويات</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>منخفضة</option>
                        <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>متوسطة</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>عالية</option>
                        <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>عاجلة</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="assigned_to[]" multiple class="form-select select2" onchange="this.form.submit()">
                        <option value="">كل المنفذين</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ in_array($user->id, (array)request('assigned_to', [])) ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="project_entities_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الجهات المرتبطة</option>
                        @foreach($projectEntities as $entity)
                            <option value="{{ $entity->id }}" {{ request('project_entities_id') == $entity->id ? 'selected' : '' }}>
                                {{ $entity->entity_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 text-end d-flex gap-2 justify-content-end">
                    <div class="btn-group view-switcher" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="btn-kanban" onclick="switchView('kanban')">
                            <i class="fas fa-columns me-1"></i>لوحة المهام
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btn-list" onclick="switchView('list')">
                            <i class="fas fa-list me-1"></i>قائمة
                        </button>
                    </div>
                    @if(request()->anyFilled(['search', 'status', 'priority', 'assigned_to']))
                        <a href="{{ route('projects.tasks.index', $project->id) }}" class="btn btn-light" title="إعادة تعيين">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    @endif
                    <a href="{{ route('projects.tasks.print_index', $project->id) }}?{{ http_build_query(request()->query()) }}" target="_blank" class="btn btn-light" title="طباعة القائمة">
                        <i class="fas fa-print text-secondary"></i>
                    </a>
                </div>
            </form>
        </x-slot>

    <!-- Bulk Delete Bar -->
    <div id="bulkDeleteBar" class="bulk-delete-bar">
        <div>
            <i class="fas fa-trash text-danger me-2"></i>
            <span class="selected-count" id="selectedCount">0</span> مهمة محددة
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                <i class="fas fa-times me-1"></i>إلغاء التحديد
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="confirmBulkDelete()">
                <i class="fas fa-trash me-1"></i>حذف المحدد
            </button>
        </div>
    </div>

    <!-- Kanban View Container -->
    <div id="view-kanban" class="animate-fade">
        <div class="task-board">
            <!-- 1. TODO -->
            <div class="task-column">
                <div class="column-header">
                    <span class="column-title"><i class="far fa-circle text-secondary fs-5"></i>قيد الانتظار</span>
                    <span class="column-count">{{ $tasks->where('status', 'todo')->count() }}</span>
                </div>
                @forelse($tasks->where('status', 'todo') as $task)
                    <div class="task-card position-relative status-todo" style="cursor: default;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center gap-2">
                                @can('task.delete', $task)
                                <input type="checkbox" class="task-checkbox task-checkbox-kanban" 
                                       value="{{ $task->id }}" 
                                       onchange="updateBulkDeleteBar()">
                                @endcan
                            </div>
                            <h6 class="fw-bold mb-0 flex-grow-1" onclick="window.location='{{ route('projects.tasks.show', [$project->id, $task->id]) }}'" style="cursor: pointer;">
                                {{ $task->title }}
                            </h6>
                            <div class="action-buttons">
                                <a href="{{ route('projects.tasks.show', [$project->id, $task->id]) }}" class="action-btn action-btn-view" onclick="event.stopPropagation()" title="عرض تفاصيل المهمة" aria-label="عرض تفاصيل المهمة">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($task->isStandaloneGeneralTask() && auth()->user()->hasPermission('task_reports.create') && $task->assignees->contains('id', auth()->id()) && $task->achievementReports->firstWhere('user_id', auth()->id())?->status !== 'submitted')
                                <a href="{{ route('tasks.achievement-reports.create', $task) }}" class="action-btn action-btn-view" onclick="event.stopPropagation()" title="إضافة تقرير إنجاز" aria-label="إضافة تقرير إنجاز">
                                    <i class="fas fa-file-circle-plus"></i>
                                </a>
                                @endif
                                @can('task.edit', $task)
                                <button type="button" class="action-btn action-btn-stop" onclick="event.stopPropagation(); openStopTaskModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->status }}')" title="إيقاف المهمة وإشعار المكلفين">
                                    <i class="fas fa-pause"></i>
                                </button>
                                @endcan
                                @can('task.view', $task)
                                <a href="{{ route('projects.tasks.print', [$project->id, $task->id]) }}" target="_blank" class="action-btn action-btn-print" onclick="event.stopPropagation()" title="طباعة المهمة">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endcan
                                @can('task.delete', $task)
                                <button type="button" class="action-btn action-btn-delete" onclick="event.stopPropagation(); confirmDeleteTask({{ $task->id }})" title="حذف المهمة">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </div>
                        <div class="text-muted small" onclick="window.location='{{ route('projects.tasks.show', [$project->id, $task->id]) }}'" style="cursor: pointer;">
                            {{ Str::limit($task->description ?? 'لا يوجد وصف', 60) }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="priority-badge priority-{{ $task->priority }}">{{ $task->priority }}</span>
                            @if($task->assignees->isNotEmpty())
                                <div class="d-flex gap-1">
                                    @foreach($task->assignees->take(2) as $assignee)
                                        <span class="user-avatar-circle" style="width:28px;height:28px;font-size:0.7rem;">{{ mb_substr($assignee->name, 0, 1) }}</span>
                                    @endforeach
                                    @if($task->assignees->count() > 2)
                                        <span class="small text-muted">+{{ $task->assignees->count() - 2 }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if($task->due_date)
                            <div class="card-meta-item"><i class="far fa-calendar-alt"></i> {{ $task->due_date->format('Y-m-d') }}</div>
                        @endif
                        <!-- Delete Form (Hidden) -->
                        @can('task.delete', $task)
                        <form id="delete-task-{{ $task->id }}" action="{{ route('projects.tasks.destroy', [$project->id, $task->id]) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                @empty
                    <div class="text-center py-5 text-muted small border border-dashed rounded-3">لا توجد مهام</div>
                @endforelse
            </div>

            <!-- 2. IN PROGRESS -->
            <div class="task-column">
                <div class="column-header">
                    <span class="column-title"><i class="fas fa-spinner text-primary fa-spin fs-5"></i>قيد التنفيذ</span>
                    <span class="column-count">{{ $tasks->where('status', 'in_progress')->count() }}</span>
                </div>
                @forelse($tasks->where('status', 'in_progress') as $task)
                    <div class="task-card position-relative status-in_progress" style="cursor: default;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center gap-2">
                                @can('task.delete', $task)
                                <input type="checkbox" class="task-checkbox task-checkbox-kanban" 
                                       value="{{ $task->id }}" 
                                       onchange="updateBulkDeleteBar()">
                                @endcan
                            </div>
                            <h6 class="fw-bold mb-0 flex-grow-1" onclick="window.location='{{ route('projects.tasks.show', [$project->id, $task->id]) }}'" style="cursor: pointer;">
                                {{ $task->title }}
                            </h6>
                            <div class="action-buttons">
                                @can('task.edit', $task)
                                <button type="button" class="action-btn action-btn-stop" onclick="event.stopPropagation(); openStopTaskModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->status }}')" title="إيقاف المهمة وإشعار المكلفين">
                                    <i class="fas fa-pause"></i>
                                </button>
                                @endcan
                                @can('task.view', $task)
                                <a href="{{ route('projects.tasks.print', [$project->id, $task->id]) }}" target="_blank" class="action-btn action-btn-print" onclick="event.stopPropagation()" title="طباعة المهمة">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endcan
                                @can('task.delete', $task)
                                <button type="button" class="action-btn action-btn-delete" onclick="event.stopPropagation(); confirmDeleteTask({{ $task->id }})" title="حذف المهمة">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </div>
                        <div class="text-muted small" onclick="window.location='{{ route('projects.tasks.show', [$project->id, $task->id]) }}'" style="cursor: pointer;">
                            {{ Str::limit($task->description ?? 'لا يوجد وصف', 60) }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="priority-badge priority-{{ $task->priority }}">{{ $task->priority }}</span>
                            @if($task->assignees->isNotEmpty())
                                <div class="d-flex gap-1">
                                    @foreach($task->assignees->take(2) as $assignee)
                                        <span class="user-avatar-circle" style="width:28px;height:28px;font-size:0.7rem;">{{ mb_substr($assignee->name, 0, 1) }}</span>
                                    @endforeach
                                    @if($task->assignees->count() > 2)
                                        <span class="small text-muted">+{{ $task->assignees->count() - 2 }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if($task->due_date)
                            <div class="card-meta-item"><i class="far fa-calendar-alt"></i> {{ $task->due_date->format('Y-m-d') }}</div>
                        @endif
                        @can('task.delete', $task)
                        <form id="delete-task-{{ $task->id }}" action="{{ route('projects.tasks.destroy', [$project->id, $task->id]) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                @empty
                    <div class="text-center py-5 text-muted small border border-dashed rounded-3">لا توجد مهام</div>
                @endforelse
            </div>

            <!-- 3. COMPLETED -->
            <div class="task-column">
                <div class="column-header">
                    <span class="column-title"><i class="far fa-check-circle text-success fs-5"></i>مكتملة</span>
                    <span class="column-count">{{ $tasks->where('status', 'completed')->count() }}</span>
                </div>
                @forelse($tasks->where('status', 'completed') as $task)
                    <div class="task-card position-relative status-completed" style="cursor: default;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center gap-2">
                                @can('task.delete', $task)
                                <input type="checkbox" class="task-checkbox task-checkbox-kanban" 
                                       value="{{ $task->id }}" 
                                       onchange="updateBulkDeleteBar()">
                                @endcan
                            </div>
                            <h6 class="fw-bold mb-0 flex-grow-1" onclick="window.location='{{ route('projects.tasks.show', [$project->id, $task->id]) }}'" style="cursor: pointer;">
                                {{ $task->title }}
                            </h6>
                            <div class="action-buttons">
                                @can('task.edit', $task)
                                <button type="button" class="action-btn action-btn-stop" onclick="event.stopPropagation(); openStopTaskModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->status }}')" title="إيقاف المهمة وإشعار المكلفين">
                                    <i class="fas fa-pause"></i>
                                </button>
                                @endcan
                                @can('task.view', $task)
                                <a href="{{ route('projects.tasks.print', [$project->id, $task->id]) }}" target="_blank" class="action-btn action-btn-print" onclick="event.stopPropagation()" title="طباعة المهمة">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endcan
                                @can('task.delete', $task)
                                <button type="button" class="action-btn action-btn-delete" onclick="event.stopPropagation(); confirmDeleteTask({{ $task->id }})" title="حذف المهمة">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </div>
                        <div class="text-muted small" onclick="window.location='{{ route('projects.tasks.show', [$project->id, $task->id]) }}'" style="cursor: pointer;">
                            {{ Str::limit($task->description ?? 'لا يوجد وصف', 60) }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="priority-badge priority-{{ $task->priority }}">{{ $task->priority }}</span>
                            @if($task->assignees->isNotEmpty())
                                <div class="d-flex gap-1">
                                    @foreach($task->assignees->take(2) as $assignee)
                                        <span class="user-avatar-circle" style="width:28px;height:28px;font-size:0.7rem;">{{ mb_substr($assignee->name, 0, 1) }}</span>
                                    @endforeach
                                    @if($task->assignees->count() > 2)
                                        <span class="small text-muted">+{{ $task->assignees->count() - 2 }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if($task->due_date)
                            <div class="card-meta-item"><i class="far fa-calendar-alt"></i> {{ $task->due_date->format('Y-m-d') }}</div>
                        @endif
                        @can('task.delete', $task)
                        <form id="delete-task-{{ $task->id }}" action="{{ route('projects.tasks.destroy', [$project->id, $task->id]) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                @empty
                    <div class="text-center py-5 text-muted small border border-dashed rounded-3">لا توجد مهام</div>
                @endforelse
            </div>

            <!-- 4. CANCELLED -->
            <div class="task-column">
                <div class="column-header">
                    <span class="column-title"><i class="far fa-times-circle text-danger fs-5"></i>ملغاة</span>
                    <span class="column-count">{{ $tasks->where('status', 'cancelled')->count() }}</span>
                </div>
                @forelse($tasks->where('status', 'cancelled') as $task)
                    <div class="task-card position-relative status-cancelled" style="cursor: default;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center gap-2">
                                @can('task.delete', $task)
                                <input type="checkbox" class="task-checkbox task-checkbox-kanban" 
                                       value="{{ $task->id }}" 
                                       onchange="updateBulkDeleteBar()">
                                @endcan
                            </div>
                            <h6 class="fw-bold mb-0 flex-grow-1" onclick="window.location='{{ route('projects.tasks.show', [$project->id, $task->id]) }}'" style="cursor: pointer;">
                                {{ $task->title }}
                            </h6>
                            <div class="action-buttons">
                                @can('task.edit', $task)
                                <button type="button" class="action-btn action-btn-stop is-stopped" onclick="event.stopPropagation(); openStopTaskModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->status }}')" title="استئناف المهمة وإشعار المكلفين">
                                    <i class="fas fa-play"></i>
                                </button>
                                @endcan
                                @can('task.view', $task)
                                <a href="{{ route('projects.tasks.print', [$project->id, $task->id]) }}" target="_blank" class="action-btn action-btn-print" onclick="event.stopPropagation()" title="طباعة المهمة">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endcan
                                @can('task.delete', $task)
                                <button type="button" class="action-btn action-btn-delete" onclick="event.stopPropagation(); confirmDeleteTask({{ $task->id }})" title="حذف المهمة">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </div>
                        <div class="text-muted small" onclick="window.location='{{ route('projects.tasks.show', [$project->id, $task->id]) }}'" style="cursor: pointer;">
                            {{ Str::limit($task->description ?? 'لا يوجد وصف', 60) }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="priority-badge priority-{{ $task->priority }}">{{ $task->priority }}</span>
                            @if($task->assignees->isNotEmpty())
                                <div class="d-flex gap-1">
                                    @foreach($task->assignees->take(2) as $assignee)
                                        <span class="user-avatar-circle" style="width:28px;height:28px;font-size:0.7rem;">{{ mb_substr($assignee->name, 0, 1) }}</span>
                                    @endforeach
                                    @if($task->assignees->count() > 2)
                                        <span class="small text-muted">+{{ $task->assignees->count() - 2 }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if($task->due_date)
                            <div class="card-meta-item"><i class="far fa-calendar-alt"></i> {{ $task->due_date->format('Y-m-d') }}</div>
                        @endif
                        @can('task.delete', $task)
                        <form id="delete-task-{{ $task->id }}" action="{{ route('projects.tasks.destroy', [$project->id, $task->id]) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                @empty
                    <div class="text-center py-5 text-muted small border border-dashed rounded-3">لا توجد مهام</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- List View Container -->
    <div id="view-list" class="d-none animate-fade">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table elegant-table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="task-checkbox" id="selectAllTasks" onchange="toggleAllTasks(this)">
                            </th>
                            <th class="px-4 py-3">المهمة</th>
                            <th>الحالة</th>
                            <th>الأولوية</th>
                            <th>الجهة المرتبطة</th>
                            <th>النشاط المرتبط</th>
                            <th>الإجراء/الفعالية</th>
                            <th>المسند إليه</th>
                            <th>تاريخ الاستحقاق</th>
                            <th class="text-center">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $task)
                            <tr>
                                <td>
                                    @can('task.delete', $task)
                                    <input type="checkbox" class="task-checkbox task-checkbox-list" 
                                           value="{{ $task->id }}" 
                                           onchange="updateBulkDeleteBar()">
                                    @endcan
                                </td>
                                <td class="px-4 py-3">
                                    <div class="fw-bold text-dark">{{ $task->title }}</div>
                                    <div class="text-muted small text-truncate" style="max-width: 300px;">{{ $task->description ?? 'بدون وصف' }}</div>
                                 </td>
                                <td>
                                    @if($task->status === 'todo')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-1.5 rounded-pill"><i class="far fa-circle me-1"></i>قيد الانتظار</span>
                                    @elseif($task->status === 'in_progress')
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill"><i class="fas fa-spinner fa-spin me-1"></i>قيد التنفيذ</span>
                                    @elseif($task->status === 'completed')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill"><i class="far fa-check-circle me-1"></i>مكتملة</span>
                                    @elseif($task->status === 'cancelled')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-1.5 rounded-pill"><i class="far fa-times-circle me-1"></i>ملغاة</span>
                                    @endif
                                 </td>
                                <td>
                                    @if($task->priority === 'low')
                                        <span class="priority-badge priority-low">منخفضة</span>
                                    @elseif($task->priority === 'medium')
                                        <span class="priority-badge priority-medium">متوسطة</span>
                                    @elseif($task->priority === 'high')
                                        <span class="priority-badge priority-high">عالية</span>
                                    @elseif($task->priority === 'urgent')
                                        <span class="priority-badge priority-urgent">عاجلة</span>
                                    @endif
                                 </td>
                                <td>
                                    @if($task->projectEntity)
                                        <span class="text-muted small fw-semibold"><i class="fas fa-university me-1"></i>{{ $task->projectEntity->entity_name }}</span>
                                    @else
                                        <span class="text-muted small text-decoration-underline">-</span>
                                    @endif
                                 </td>
                                <td>
                                    @if($task->linked_activity_name)
                                        <span class="text-muted small fw-semibold">
                                            @if($task->activity_type === 'preliminary')
                                                <i class="fas fa-clipboard-list text-primary me-1"></i>
                                                <span class="badge bg-light text-primary border px-2 py-1">{{ $task->linked_activity_name }}</span>
                                            @else
                                                <i class="fas fa-tasks text-success me-1"></i>
                                                <span class="badge bg-light text-success border px-2 py-1">{{ $task->linked_activity_name }}</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                 </td>
                                <td>
                                    @if($task->linked_procedure_name)
                                        <span class="text-muted small fw-semibold" title="{{ $task->linked_procedure_name }}">
                                            <i class="fas fa-link text-info me-1"></i>
                                            {{ Str::limit($task->linked_procedure_name, 35) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                 </td>
                                <td>
                                    @if($task->assignees->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($task->assignees as $assignee)
                                                <span class="badge bg-secondary" title="{{ $assignee->name }}">{{ mb_substr($assignee->name, 0, 15) }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">غير مسندة</span>
                                    @endif
                                 </td>
                                <td>
                                    @if($task->due_date)
                                        <span class="small text-muted"><i class="far fa-calendar-alt me-1"></i>{{ $task->due_date->format('Y-m-d') }}</span>
                                    @else
                                        <span class="small text-muted">-</span>
                                    @endif
                                 </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <a href="{{ route('projects.tasks.show', [$project->id, $task->id]) }}" class="action-btn action-btn-view" title="عرض تفاصيل المهمة" aria-label="عرض تفاصيل المهمة">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($task->isStandaloneGeneralTask() && auth()->user()->hasPermission('task_reports.create') && $task->assignees->contains('id', auth()->id()) && $task->achievementReports->firstWhere('user_id', auth()->id())?->status !== 'submitted')
                                        <a href="{{ route('tasks.achievement-reports.create', $task) }}" class="action-btn action-btn-view" title="إضافة تقرير إنجاز" aria-label="إضافة تقرير إنجاز">
                                            <i class="fas fa-file-circle-plus"></i>
                                        </a>
                                        @endif
                                        @can('task.edit', $task)
                                        <button type="button" class="action-btn action-btn-stop {{ $task->status === 'cancelled' ? 'is-stopped' : '' }}" title="{{ $task->status === 'cancelled' ? 'استئناف المهمة وإشعار المكلفين' : 'إيقاف المهمة وإشعار المكلفين' }}" onclick="openStopTaskModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->status }}')">
                                            <i class="fas {{ $task->status === 'cancelled' ? 'fa-play' : 'fa-pause' }}"></i>
                                        </button>
                                        <button type="button" class="action-btn action-btn-edit" title="تعديل" onclick="editTaskModal({{ json_encode($task) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @endcan
                                        @can('task.view', $task)
                                        <a href="{{ route('projects.tasks.print', [$project->id, $task->id]) }}" target="_blank" class="action-btn action-btn-print" title="طباعة المهمة">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        @endcan
                                        @can('task.delete', $task)
                                        <button type="button" class="action-btn action-btn-delete" title="حذف" onclick="confirmDeleteTask({{ $task->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                             </tr>
                        @empty
                             <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-tasks fa-3x mb-3 text-slate-300"></i>
                                    <h5>لا توجد أي مهام مسجلة</h5>
                                </td>
                             </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </x-index-page>

<!-- Bulk Delete Form -->
@can('projects.tasks.bulk-destroy')
<form id="bulkDeleteForm" action="{{ route('projects.tasks.bulk-destroy', $project->id) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="ids" id="bulkDeleteIds">
</form>
@endcan

@can('task.create', $project)
<!-- Modal: Create Task -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('projects.tasks.store', $project->id) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header bg-primary text-white rounded-top-4 border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus me-2"></i>إضافة مهمة جديدة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-slate-700">عنوان المهمة </label>
                    <input type="text" name="title" class="form-control" placeholder="أدخل عنواناً واضحاً وموجزاً...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-slate-700">الوصف والتفاصيل</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="أدخل تفاصيل ومخرجات المهمة المطلوبة..."></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-slate-700">الحالة </label>
                        <select name="status" class="form-select">
                            <option value="todo">قيد الانتظار</option>
                            <option value="in_progress">قيد التنفيذ</option>
                            <option value="completed">مكتملة</option>
                            <option value="cancelled">ملغاة</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-slate-700">الأولوية </label>
                        <select name="priority" class="form-select">
                            <option value="low">منخفضة</option>
                            <option value="medium" selected>متوسطة</option>
                            <option value="high">عالية</option>
                            <option value="urgent">عاجلة</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-slate-700">الجهة المرتبطة بالطلب</label>
                    <select name="project_entities_id" class="form-select">
                        <option value="">اختر جهة (اختياري)</option>
                        @foreach($projectEntities as $entity)
                            <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               id="create_is_within_activities"
                               name="is_within_activities"
                               value="1">
                        <label class="form-check-label fw-bold small text-slate-700" for="create_is_within_activities">
                            هل هي ضمن الأنشطة والإجراءات؟
                        </label>
                    </div>
                </div>

                {{-- ===== قسم الأنشطة والإجراءات (مخفي افتراضياً) ===== --}}
                <div id="create_activities_section" class="d-none mb-3">
                    <div class="row g-2">
                        {{-- النشاط --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-700">النشاط</label>
                            <select id="create_activity_select" name="activity_id" class="form-select">
                                <option value="">-- اختر النشاط --</option>
                            </select>
                            <input type="hidden" id="create_activity_type" name="activity_type" value="">
                        </div>

                        {{-- الإجراء --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-700">الإجراء/الفعالية</label>
                            <select id="create_procedure_select" name="procedure_id" class="form-select">
                                <option value="">-- اختر الإجراء --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="create_executive_action_wrapper">
                    <label class="form-label fw-bold small text-slate-700">الإجراء التنفيذي المرتبط (سريع)</label>
                    <select name="executive_activity_action_id" id="create_executive_activity_action_id" class="form-select">
                        <option value="">بدون ربط بإجراء تنفيذي</option>
                        @foreach($executiveActions as $action)
                            <option value="{{ $action->id }}">{{ $action->action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @can('task.assign')
                        <label class="form-label fw-bold small text-slate-700">إسناد إلى مستخدم</label>
                        <select name="assigned_to[]" multiple class="form-select select2">
                            <option value="">اختر مستخدماً (اختياري)</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @endcan
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-slate-700">تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 gap-2">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ المهمة</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('projects.edit', $project)
<!-- Modal: Edit Task -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editTaskForm" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            @method('PUT')
            <div class="modal-header bg-warning text-dark rounded-top-4 border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>تعديل المهمة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-slate-700">عنوان المهمة </label>
                    <input type="text" name="title" id="edit-title" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-slate-700">الوصف والتفاصيل</label>
                    <textarea name="description" id="edit-description" class="form-control" rows="3"></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-slate-700">الحالة </label>
                        <select name="status" id="edit-status" class="form-select">
                            <option value="todo">قيد الانتظار</option>
                            <option value="in_progress">قيد التنفيذ</option>
                            <option value="completed">مكتملة</option>
                            <option value="cancelled">ملغاة</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-slate-700">الأولوية </label>
                        <select name="priority" id="edit-priority" class="form-select">
                            <option value="low">منخفضة</option>
                            <option value="medium">متوسطة</option>
                            <option value="high">عالية</option>
                            <option value="urgent">عاجلة</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-slate-700">الجهة المرتبطة بالطلب</label>
                    <select name="project_entities_id" id="edit-project_entities_id" class="form-select">
                        <option value="">اختر جهة (اختياري)</option>
                        @foreach($projectEntities as $entity)
                            <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               id="edit_is_within_activities"
                               name="is_within_activities"
                               value="1">
                        <label class="form-check-label fw-bold small text-slate-700" for="edit_is_within_activities">
                            هل هي ضمن الأنشطة والإجراءات؟
                        </label>
                    </div>
                </div>

                {{-- ===== قسم الأنشطة والإجراءات (مخفي افتراضياً) ===== --}}
                <div id="edit_activities_section" class="d-none mb-3">
                    <div class="row g-2">
                        {{-- النشاط --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-700">النشاط</label>
                            <select id="edit_activity_select" name="activity_id" class="form-select">
                                <option value="">-- اختر النشاط --</option>
                            </select>
                            <input type="hidden" id="edit_activity_type" name="activity_type" value="">
                        </div>

                        {{-- الإجراء --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-slate-700">الإجراء/الفعالية</label>
                            <select id="edit_procedure_select" name="procedure_id" class="form-select">
                                <option value="">-- اختر الإجراء --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="edit_executive_action_wrapper">
                    <label class="form-label fw-bold small text-slate-700">الإجراء التنفيذي المرتبط (سريع)</label>
                    <select name="executive_activity_action_id" id="edit-executive_activity_action_id" class="form-select">
                        <option value="">بدون ربط بإجراء تنفيذي</option>
                        @foreach($executiveActions as $action)
                            <option value="{{ $action->id }}">{{ $action->action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-slate-700">إسناد إلى مستخدم</label>
                        @can('task.assign')
                        <select name="assigned_to[]" multiple id="edit-assigned_to" class="form-select">
                            <option value="">اختر مستخدماً (اختياري)</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @endcan
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-slate-700">تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" id="edit-due_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 gap-2">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-warning px-4 fw-bold">حفظ التغييرات</button>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- Stop / Resume Task Modal -->
<div class="modal fade" id="stopTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" id="stopModalIconBg" style="width:48px;height:48px;background:#fee2e2;color:#dc2626;">
                        <i class="fas fa-pause fs-5" id="stopModalIcon"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="stopModalTitle">إيقاف المهمة</h5>
                        <small class="text-muted" id="stopModalSubtitle">تغيير حالة المهمة وإشعار المكلفين</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="stopTaskForm" method="POST" onsubmit="submitStopTask(event)">
                @csrf
                <div class="modal-body py-4">
                    <div class="alert alert-light border rounded-3 d-flex align-items-center gap-2 mb-3">
                        <i class="fas fa-tasks text-primary"></i>
                        <span class="fw-bold" id="stopTaskTitleDisplay"></span>
                    </div>

                    <div class="mb-3" id="stopReasonWrapper">
                        <label class="form-label fw-bold small">سبب الإيقاف / ملاحظات (اختياري)</label>
                        <textarea name="reason" id="stopTaskReason" class="form-control" rows="3" placeholder="اكتب سبب إيقاف المهمة ليتم إرساله للمكلفين..."></textarea>
                    </div>

                    <div class="bg-light p-3 rounded-3 border">
                        <div class="d-flex align-items-center gap-2 text-primary small fw-bold mb-2">
                            <i class="fas fa-bell"></i>
                            <span>سيتم إرسال إشعار فوري داخل النظام لجميع المستخدمين المكلفين</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-success small fw-bold">
                            <i class="fas fa-sms"></i>
                            <span>سيتم إرسال رسالة نصية قصيرة (SMS) إلى أرقام هواتف المكلفين</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold" id="stopModalSubmitBtn">
                        <i class="fas fa-check me-1"></i>تأكيد الإجراء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const activitiesUrl = "{{ route('tasks.activities') }}";
    const proceduresUrl = "{{ route('tasks.procedures') }}";
    const projectId = "{{ $project->id }}";

    function switchView(view) {
        if(view === 'kanban') {
            document.getElementById('view-kanban').classList.remove('d-none');
            document.getElementById('view-list').classList.add('d-none');
            document.getElementById('btn-kanban').classList.add('active');
            document.getElementById('btn-list').classList.remove('active');
            localStorage.setItem('task_view_mode', 'kanban');
        } else {
            document.getElementById('view-kanban').classList.add('d-none');
            document.getElementById('view-list').classList.remove('d-none');
            document.getElementById('btn-kanban').classList.remove('active');
            document.getElementById('btn-list').classList.add('active');
            localStorage.setItem('task_view_mode', 'list');
        }
    }

    // =============================================
    // Bulk Delete Functions
    // =============================================
    
    function updateBulkDeleteBar() {
        const checkboxes = document.querySelectorAll('.task-checkbox:checked');
        const count = checkboxes.length;
        const bar = document.getElementById('bulkDeleteBar');
        const countSpan = document.getElementById('selectedCount');
        
        if (count > 0) {
            bar.classList.add('show');
            countSpan.textContent = count;
        } else {
            bar.classList.remove('show');
        }
    }

    function toggleAllTasks(master) {
        document.querySelectorAll('.task-checkbox').forEach(cb => {
            cb.checked = master.checked;
        });
        updateBulkDeleteBar();
    }

    function clearSelection() {
        document.querySelectorAll('.task-checkbox').forEach(cb => {
            cb.checked = false;
        });
        updateBulkDeleteBar();
    }

    function confirmBulkDelete() {
        const checkboxes = document.querySelectorAll('.task-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);
        
        if (ids.length === 0) {
            alert('الرجاء تحديد مهمة واحدة على الأقل للحذف.');
            return;
        }
        
        if (confirm('هل أنت متأكد من حذف ' + ids.length + ' مهمة؟')) {
            document.getElementById('bulkDeleteIds').value = ids.join(',');
            document.getElementById('bulkDeleteForm').submit();
        }
    }

    // =============================================
    // Single Delete Function
    // =============================================
    
    function confirmDeleteTask(taskId) {
        if (confirm('هل أنت متأكد من حذف هذه المهمة؟')) {
            document.getElementById('delete-task-' + taskId).submit();
        }
    }

    // Create Modal Dynamic Handling
    const createToggle = document.getElementById('create_is_within_activities');
    const createSection = document.getElementById('create_activities_section');
    const createExecWrapper = document.getElementById('create_executive_action_wrapper');
    const createActivitySelect = document.getElementById('create_activity_select');
    const createActivityType = document.getElementById('create_activity_type');
    const createProcedureSelect = document.getElementById('create_procedure_select');

    if (createToggle) {
        createToggle.addEventListener('change', function() {
            if (this.checked) {
                createSection.classList.remove('d-none');
                createExecWrapper.classList.add('d-none');
                loadActivities(projectId, createActivitySelect, createActivityType, createProcedureSelect);
            } else {
                createSection.classList.add('d-none');
                createExecWrapper.classList.remove('d-none');
                createActivitySelect.innerHTML = '<option value="">-- اختر النشاط --</option>';
                createProcedureSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';
                createActivityType.value = '';
            }
        });
    }

    if (createActivitySelect) {
        createActivitySelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const type = selected?.dataset?.type || '';
            createActivityType.value = type;
            loadProcedures(this.value, type, createProcedureSelect);
        });
    }

    // Edit Modal Dynamic Handling
    const editToggle = document.getElementById('edit_is_within_activities');
    const editSection = document.getElementById('edit_activities_section');
    const editExecWrapper = document.getElementById('edit_executive_action_wrapper');
    const editActivitySelect = document.getElementById('edit_activity_select');
    const editActivityType = document.getElementById('edit_activity_type');
    const editProcedureSelect = document.getElementById('edit_procedure_select');

    if (editToggle) {
        editToggle.addEventListener('change', function() {
            if (this.checked) {
                editSection.classList.remove('d-none');
                editExecWrapper.classList.add('d-none');
                loadActivities(projectId, editActivitySelect, editActivityType, editProcedureSelect);
            } else {
                editSection.classList.add('d-none');
                editExecWrapper.classList.remove('d-none');
                editActivitySelect.innerHTML = '<option value="">-- اختر النشاط --</option>';
                editProcedureSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';
                editActivityType.value = '';
            }
        });
    }

    if (editActivitySelect) {
        editActivitySelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const type = selected?.dataset?.type || '';
            editActivityType.value = type;
            loadProcedures(this.value, type, editProcedureSelect);
        });
    }

    function loadActivities(projId, actSelect, actTypeInput, procSelect, selectedActId = null, selectedProcId = null) {
        if (!actSelect) return;
        actSelect.innerHTML = '<option value="">-- اختر النشاط --</option>';
        if (procSelect) procSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';

        if (!projId) return;

        fetch(`${activitiesUrl}?project_id=${projId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.label;
                opt.dataset.type = item.type;
                if (selectedActId && selectedActId == item.id && item.type === actTypeInput.value) {
                    opt.selected = true;
                }
                actSelect.appendChild(opt);
            });

            if (selectedActId && procSelect) {
                loadProcedures(selectedActId, actTypeInput.value, procSelect, selectedProcId);
            }
        })
        .catch(() => {});
    }

    function loadProcedures(activityId, activityType, procSelect, selectedProcId = null) {
        if (!procSelect) return;
        procSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';

        if (!activityId || !activityType) return;

        fetch(`${proceduresUrl}?activity_id=${activityId}&activity_type=${activityType}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                if (selectedProcId && selectedProcId == item.id) {
                    opt.selected = true;
                }
                procSelect.appendChild(opt);
            });
        })
        .catch(() => {});
    }

    function editTaskModal(task) {
        document.getElementById('edit-title').value = task.title;
        document.getElementById('edit-description').value = task.description || '';
        document.getElementById('edit-status').value = task.status;
        document.getElementById('edit-priority').value = task.priority;
        document.getElementById('edit-project_entities_id').value = task.project_entities_id || '';
        document.getElementById('edit-executive_activity_action_id').value = task.executive_activity_action_id || '';
        
        const assignedSelect = document.getElementById('edit-assigned_to');
        if (assignedSelect && task.assignees) {
            const assignedIds = task.assignees.map(a => a.id);
            Array.from(assignedSelect.options).forEach(opt => {
                opt.selected = assignedIds.includes(parseInt(opt.value));
            });
            $(assignedSelect).trigger('change');
        }
        
        document.getElementById('edit-due_date').value = task.due_date || '';

        const editToggle = document.getElementById('edit_is_within_activities');
        const editSection = document.getElementById('edit_activities_section');
        const editExecWrapper = document.getElementById('edit_executive_action_wrapper');
        const editActivityType = document.getElementById('edit_activity_type');

        if (task.is_within_activities) {
            editToggle.checked = true;
            editSection.classList.remove('d-none');
            editExecWrapper.classList.add('d-none');
            editActivityType.value = task.activity_type || '';
            loadActivities(projectId, editActivitySelect, editActivityType, editProcedureSelect, task.activity_id, task.procedure_id);
        } else {
            editToggle.checked = false;
            editSection.classList.add('d-none');
            editExecWrapper.classList.remove('d-none');
            editActivitySelect.innerHTML = '<option value="">-- اختر النشاط --</option>';
            editProcedureSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';
            editActivityType.value = '';
        }

        const form = document.getElementById('editTaskForm');
        form.action = `/projects/{{ $project->id }}/tasks/${task.id}`;

        const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
        modal.show();
    }

    // Stop / Resume Task Modal Handling
    let currentStopTaskId = null;
    let currentStopTaskStatus = null;

    function openStopTaskModal(taskId, taskTitle, taskStatus) {
        currentStopTaskId = taskId;
        currentStopTaskStatus = taskStatus;
        
        const isCancelled = (taskStatus === 'cancelled');
        const modalTitle = document.getElementById('stopModalTitle');
        const modalSubtitle = document.getElementById('stopModalSubtitle');
        const modalIcon = document.getElementById('stopModalIcon');
        const modalIconBg = document.getElementById('stopModalIconBg');
        const submitBtn = document.getElementById('stopModalSubmitBtn');
        const reasonWrapper = document.getElementById('stopReasonWrapper');
        
        document.getElementById('stopTaskTitleDisplay').textContent = taskTitle;
        document.getElementById('stopTaskReason').value = '';
        
        if (isCancelled) {
            modalTitle.textContent = 'استئناف المهمة';
            modalSubtitle.textContent = 'إعادة تفعيل المهمة وإشعار المكلفين';
            modalIcon.className = 'fas fa-play fs-5';
            modalIconBg.style.background = '#dcfce7';
            modalIconBg.style.color = '#16a34a';
            submitBtn.className = 'btn btn-success rounded-pill px-4 fw-bold';
            submitBtn.innerHTML = '<i class="fas fa-play me-1"></i>تأكيد الاستئناف';
            reasonWrapper.querySelector('label').textContent = 'ملاحظات الاستئناف (اختياري)';
        } else {
            modalTitle.textContent = 'إيقاف / تعليق المهمة';
            modalSubtitle.textContent = 'إيقاف المهمة مؤقتاً وإشعار المكلفين عبر النظام و SMS';
            modalIcon.className = 'fas fa-pause fs-5';
            modalIconBg.style.background = '#fee2e2';
            modalIconBg.style.color = '#dc2626';
            submitBtn.className = 'btn btn-danger rounded-pill px-4 fw-bold';
            submitBtn.innerHTML = '<i class="fas fa-pause me-1"></i>تأكيد إيقاف المهمة';
            reasonWrapper.querySelector('label').textContent = 'سبب الإيقاف / ملاحظات (اختياري)';
        }
        
        const modal = new bootstrap.Modal(document.getElementById('stopTaskModal'));
        modal.show();
    }

    function submitStopTask(e) {
        e.preventDefault();
        if (!currentStopTaskId) return;
        
        const reason = document.getElementById('stopTaskReason').value;
        const submitBtn = document.getElementById('stopModalSubmitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري التنفيذ...';
        
        fetch(`/projects/{{ $project->id }}/tasks/${currentStopTaskId}/stop`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('stopTaskModal')).hide();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    alert(data.message);
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('خطأ', data.message || 'حدث خطأ أثناء تنفيذ العملية', 'error');
                } else {
                    alert(data.message || 'حدث خطأ');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(err => {
            if (typeof Swal !== 'undefined') {
                Swal.fire('خطأ', 'حدث خطأ في الاتصال بالخادم', 'error');
            } else {
                alert('حدث خطأ في الاتصال بالخادم');
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }

    // Preserve view mode on load
    document.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('task_view_mode') || 'kanban';
        switchView(savedView);
    });
</script>
@endsection

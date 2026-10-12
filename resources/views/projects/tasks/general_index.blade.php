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
</style>
@endsection

@section('content')
    <x-index-page title="مساحة عمل المهام - المهام العامة (مستقلة)" icon="tasks" :paginator="$tasks">
        <x-slot name="headerActions">
            @can('create', \App\Models\Task::class)
            <a href="{{ route('tasks.create') }}" class="btn btn-primary shadow-sm fw-bold px-3 py-2">
                <i class="fas fa-plus me-2"></i>إضافة مهمة جديدة
            </a>
            @endcan
            <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary px-3 py-2">
                <i class="fas fa-arrow-left me-2"></i>العودة للمشروع
            </a>
        </x-slot>

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-muted">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted">المشاريع</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}" class="text-muted">المهام العامة (مستقلة)</a></li>
                <li class="breadcrumb-item active text-primary fw-bold">إدارة المهام</li>
            </ol>
        </nav>

    <!-- Alert Notifications -->
    

        <x-slot name="filters">
            <form method="GET" action="{{ route('tasks.general') }}" class="row g-3 align-items-center">
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
                        <a href="{{ route('tasks.general') }}" class="btn btn-light" title="إعادة تعيين">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    @endif
                    <a href="{{ route('tasks.print_index', request()->query()) }}" target="_blank" class="btn btn-light" title="طباعة القائمة">
                        <i class="fas fa-print text-secondary"></i>
                    </a>
                </div>
            </form>
        </x-slot>

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
                    @include('projects.tasks.partials.task_card', ['task' => $task])
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
                    @include('projects.tasks.partials.task_card', ['task' => $task])
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
                    @include('projects.tasks.partials.task_card', ['task' => $task])
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
                    @include('projects.tasks.partials.task_card', ['task' => $task])
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
                            <tr class="floating-row">
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
                                        <span class="small text-muted"><i class="far fa-calendar-alt me-1"></i>{{ $task->due_date }}</span>
                                    @else
                                        <span class="small text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ $task->project_id ? route('projects.tasks.show', [$task->project_id, $task->id]) : route('tasks.show', $task->id) }}" class="btn btn-sm btn-light border" title="عرض تفاصيل المهمة" aria-label="عرض تفاصيل المهمة">
                                            <i class="fas fa-eye text-success"></i>
                                        </a>
                                        @can('update', $task)
                                        <button type="button" class="btn btn-sm btn-light border" title="التعديل" onclick="editTaskModal({{ json_encode($task) }})">
                                            <i class="fas fa-edit text-warning"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border" title="تكليف مستخدمين" onclick="editTaskModal({{ json_encode($task) }})">
                                            <i class="fas fa-user-plus text-primary"></i>
                                        </button>
                                        @endcan
                                        @can('tasks.print')
                                        <a href="{{ route('tasks.print', $task->id) }}" target="_blank" class="btn btn-sm btn-light border" title="طباعة">
                                            <i class="fas fa-print text-secondary"></i>
                                        </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
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

@can('create', \App\Models\Task::class)
<!-- Modal: Create Task -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('tasks.store') }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
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

@auth
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

@endsection

@section('scripts')
<script>
    const activitiesUrl = "{{ route('tasks.activities') }}";
    const proceduresUrl = "{{ route('tasks.procedures') }}";
    const projectId = null;

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

    // Create Modal Dynamic Handling
    const createToggle = document.getElementById('create_is_within_activities');
    const createSection = document.getElementById('create_activities_section');
    const createExecWrapper = document.getElementById('create_executive_action_wrapper');
    const createActivitySelect = document.getElementById('create_activity_select');
    const createActivityType = document.getElementById('create_activity_type');
    const createProcedureSelect = document.getElementById('create_procedure_select');

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

    createActivitySelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const type = selected?.dataset?.type || '';
        createActivityType.value = type;
        loadProcedures(this.value, type, createProcedureSelect);
    });

    // Edit Modal Dynamic Handling
    const editToggle = document.getElementById('edit_is_within_activities');
    const editSection = document.getElementById('edit_activities_section');
    const editExecWrapper = document.getElementById('edit_executive_action_wrapper');
    const editActivitySelect = document.getElementById('edit_activity_select');
    const editActivityType = document.getElementById('edit_activity_type');
    const editProcedureSelect = document.getElementById('edit_procedure_select');

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

    editActivitySelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const type = selected?.dataset?.type || '';
        editActivityType.value = type;
        loadProcedures(this.value, type, editProcedureSelect);
    });

    function loadActivities(projId, actSelect, actTypeInput, procSelect, selectedActId = null, selectedProcId = null) {
        actSelect.innerHTML = '<option value="">-- اختر النشاط --</option>';
        procSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';

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

            if (selectedActId) {
                loadProcedures(selectedActId, actTypeInput.value, procSelect, selectedProcId);
            }
        })
        .catch(() => {});
    }

    function loadProcedures(activityId, activityType, procSelect, selectedProcId = null) {
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
        $('#edit-assigned_to').val = task.assignees ? task.assignees.map(a => a.id) : [];
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
        form.action = `/tasks/${task.id}`;

        const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
        modal.show();
    }

    // Preserve view mode on load
    document.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('task_view_mode') || 'kanban';
        switchView(savedView);
    });
</script>
@endsection

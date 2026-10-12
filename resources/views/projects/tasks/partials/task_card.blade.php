{{-- resources/views/projects/tasks/partials/task_card.blade.php --}}

<div class="task-card status-{{ $task->status }}"
    onclick="window.location='{{ $task->project_id ? route('projects.tasks.show', [$task->project_id, $task->id]) : route('tasks.show', $task->id) }}'"
    style="cursor: pointer;">

    {{-- Card Header: Title + Print --}}
    <div class="d-flex justify-content-between align-items-start gap-2">
        <h6 class="card-title flex-grow-1">{{ $task->title }}</h6>
        @can('task.view', $task)
            <a href="{{ $task->project_id ? route('projects.tasks.show', [$task->project_id, $task->id]) : route('tasks.show', $task->id) }}"
                class="action-btn action-btn-view" onclick="event.stopPropagation()" title="عرض تفاصيل المهمة" aria-label="عرض تفاصيل المهمة">
                <i class="fas fa-eye"></i>
            </a>
        @endcan
        @if($task->isStandaloneGeneralTask() && auth()->user()->hasPermission('task_reports.create') && $task->assignees->contains('id', auth()->id()) && $task->achievementReports->firstWhere('user_id', auth()->id())?->status !== 'submitted')
            <a href="{{ route('tasks.achievement-reports.create', $task) }}" class="action-btn action-btn-view"
                onclick="event.stopPropagation()" title="إضافة تقرير إنجاز" aria-label="إضافة تقرير إنجاز">
                <i class="fas fa-file-circle-plus"></i>
            </a>
        @endif
        @can('task.view', $task)
            <a href="{{ $task->project_id ? route('projects.tasks.print', [$task->project_id, $task->id]) : route('tasks.print', $task->id) }}"
                target="_blank" class="card-print-btn" onclick="event.stopPropagation()" title="طباعة المهمة">
                <i class="fas fa-print"></i>
            </a>
        @endcan
    </div>

    {{-- Description --}}
    <p class="card-desc">
        {{ Str::limit($task->description ?? 'لا يوجد وصف', 70) }}
    </p>

    {{-- Priority + Scope --}}
    <div class="d-flex justify-content-between align-items-center">
        <span class="priority-badge priority-{{ $task->priority }}">
            @if($task->priority === 'low') 🟢
            @elseif($task->priority === 'medium') 🔵
            @elseif($task->priority === 'high') 🟠
            @elseif($task->priority === 'urgent') 🔴
            @endif
            {{ $task->priority === 'low' ? 'منخفضة' : ($task->priority === 'medium' ? 'متوسطة' : ($task->priority === 'high' ? 'عالية' : 'عاجلة')) }}
        </span>

        @if($task->project_id && $task->value_chain_id)
            <span class="scope-badge scope-both">
                <i class="fas fa-link"></i>مشروع وسلسلة
            </span>
        @elseif($task->project_id)
            <span class="scope-badge scope-project">
                <i class="fas fa-project-diagram"></i>مشروع
            </span>
        @elseif($task->value_chain_id)
            <span class="scope-badge scope-value-chain">
                <i class="fas fa-link"></i>سلسلة قيمة
            </span>
        @else
            <span class="scope-badge scope-general">
                <i class="fas fa-globe"></i>عامة
            </span>
        @endif
    </div>

    {{-- Footer: Assignees + Due Date --}}
    <div class="card-footer-meta">
        @if($task->assignees->isNotEmpty())
            <div class="avatar-stack">
                @foreach($task->assignees->take(3) as $assignee)
                    <span class="user-avatar-circle" title="{{ $assignee->name }}"
                        style="width:26px;height:26px;font-size:0.65rem;">
                        {{ mb_substr($assignee->name, 0, 1) }}
                    </span>
                @endforeach
                @if($task->assignees->count() > 3)
                    <span class="avatar-more" style="width:26px;height:26px;font-size:0.6rem;">
                        +{{ $task->assignees->count() - 3 }}
                    </span>
                @endif
            </div>
        @else
            <span></span>
        @endif

        @if($task->due_date)
            @php
                $daysLeft = now()->diffInDays($task->due_date, false);
            @endphp
            <span class="card-meta-item {{ $daysLeft < 0 ? 'due-date-overdue' : ($daysLeft <= 3 ? 'due-date-soon' : '') }}">
                <i class="far fa-calendar-alt"></i>
                {{ $task->due_date->format('M d') }}
            </span>
        @endif
    </div>
</div>

@php
    $assignees = $task->assignees;
    $submittedCount = $assignees->filter(fn ($assignee) => $task->achievementReports->firstWhere('user_id', $assignee->id)?->status === 'submitted')->count();
    $missingAssignees = $assignees->filter(fn ($assignee) => $task->achievementReports->firstWhere('user_id', $assignee->id)?->status !== 'submitted');
    $isOverdue = $task->due_date && now()->startOfDay()->greaterThan($task->due_date) && $missingAssignees->isNotEmpty();
    $canManage = auth()->user()->hasPermission('task_reports.view_all') || auth()->user()->hasPermission('task_reports.review');
    $ownReport = $task->achievementReports->firstWhere('user_id', auth()->id());
@endphp
<section dir="rtl" class="p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="h5 mb-1">تقارير الإنجاز</h3>
            <p class="text-muted mb-0">تقارير مستقلة لكل مكلف بالمهمة.</p>
        </div>
        @if($task->assignees->contains('id', auth()->id()) && auth()->user()->hasPermission('task_reports.create') && $ownReport?->status !== 'submitted')
            <a class="btn btn-primary" href="{{ $ownReport ? route('tasks.achievement-reports.edit', [$task, $ownReport]) : route('tasks.achievement-reports.create', $task) }}">
                {{ $ownReport ? 'متابعة المسودة' : 'إنشاء تقريري' }}
            </a>
        @endif
    </div>
    @if($canManage)<a class="btn btn-sm btn-outline-primary mb-3" href="{{ route('tasks.achievement-reports.index-all') }}">إدارة جميع التقارير ضمن النطاق</a>@endif
    <div class="row g-3 mb-4">
        <div class="col"><div class="border rounded p-3"><small>المكلفون</small><strong class="d-block">{{ $assignees->count() }}</strong></div></div>
        <div class="col"><div class="border rounded p-3"><small>التقارير المطلوبة</small><strong class="d-block">{{ $assignees->count() }}</strong></div></div>
        <div class="col"><div class="border rounded p-3"><small>المقدمة نهائياً</small><strong class="d-block">{{ $submittedCount }}</strong></div></div>
        <div class="col"><div class="border rounded p-3"><small>المفقودة</small><strong class="d-block">{{ $missingAssignees->count() }}</strong></div></div>
        <div class="col"><div class="border rounded p-3"><small>المتأخرة</small><strong class="d-block">{{ $isOverdue ? $missingAssignees->count() : 0 }}</strong></div></div>
    </div>
    @if($isOverdue)<div class="alert alert-danger">متأخرة — تقارير الإنجاز مفقودة: {{ $missingAssignees->pluck('name')->join('، ') }}</div>
    @elseif($missingAssignees->isNotEmpty())<div class="alert alert-warning">تقارير الإنجاز معلقة: {{ $missingAssignees->pluck('name')->join('، ') }}</div>@endif
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>#</th><th>المكلف</th><th>نسبة الإنجاز</th><th>الحالة</th><th>تاريخ التقديم</th><th>الإجراءات</th></tr></thead>
            <tbody>
            @foreach($assignees as $number => $assignee)
                @php($report = $task->achievementReports->firstWhere('user_id', $assignee->id))
                <tr><td>{{ $number + 1 }}</td><td>{{ $assignee->name }}</td>
                    <td>{{ $report?->overall_completion_percentage !== null ? $report->overall_completion_percentage.'%' : '—' }}</td>
                    <td>{{ $report?->status === 'submitted' ? 'مقدم' : ($report ? 'مسودة' : 'معلق') }}</td>
                    <td>{{ $report?->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>
                        @if($report && $report->status === 'submitted' && ($canManage || $report->user_id === auth()->id()))
                            <a href="{{ route('tasks.achievement-reports.show', [$task, $report]) }}">عرض التقرير</a>
                        @elseif($report && $report->status === 'draft' && $report->user_id === auth()->id())
                            <a href="{{ route('tasks.achievement-reports.edit', [$task, $report]) }}">متابعة المسودة</a>
                        @elseif(!$report && $assignee->id === auth()->id() && auth()->user()->hasPermission('task_reports.create'))
                            <a href="{{ route('tasks.achievement-reports.create', $task) }}">إنشاء التقرير</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

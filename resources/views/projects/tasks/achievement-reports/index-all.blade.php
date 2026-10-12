@extends('layouts.app')
@section('content')
<main class="container-fluid py-4" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h3">تقارير إنجاز المهام</h1><p class="text-muted mb-0">تقارير المهام العامة المستقلة ضمن نطاقك المصرح.</p></div><a class="btn btn-outline-secondary" href="{{ route('tasks.index') }}">إدارة المهام</a></div>
    <form method="GET" class="card card-body mb-4"><div class="row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label">المهمة أو المستخدم</label><input name="search" class="form-control" value="{{ request('search') }}"></div>
        <div class="col-md-2"><label class="form-label">حالة التقرير</label><select name="status" class="form-select"><option value="">الكل</option><option value="draft" @selected(request('status') === 'draft')>مسودة</option><option value="submitted" @selected(request('status') === 'submitted')>مقدم</option></select></div>
        <div class="col-md-2"><label class="form-label">من تاريخ التقديم</label><input type="date" name="submitted_from" class="form-control" value="{{ request('submitted_from') }}"></div>
        <div class="col-md-2"><label class="form-label">إلى تاريخ التقديم</label><input type="date" name="submitted_to" class="form-control" value="{{ request('submitted_to') }}"></div>
        <div class="col-md-1"><label class="form-label">متأخر</label><select name="overdue" class="form-select"><option value="0">الكل</option><option value="1" @selected(request('overdue'))>نعم</option></select></div>
        <div class="col-md-1"><button class="btn btn-primary w-100">بحث</button></div>
    </div></form>
    <div class="table-responsive card"><table class="table table-hover align-middle mb-0"><thead><tr><th>المهمة</th><th>المبلغ</th><th>المكلفون</th><th>البدء</th><th>الموعد</th><th>المدة</th><th>الإنجاز</th><th>الحالة</th><th>تاريخ التقديم</th><th>إجراء</th></tr></thead><tbody>
        @forelse($reports as $report)
            <tr><td>{{ $report->task_title_snapshot }}</td><td>{{ $report->user->name }}</td><td>{{ $report->task->assignees->pluck('name')->join('، ') }}</td><td>{{ $report->task_start_date_snapshot?->format('Y-m-d') ?? '—' }}</td><td>{{ $report->task_due_date_snapshot?->format('Y-m-d') ?? '—' }}</td><td>{{ $report->planned_duration_days_snapshot ? $report->planned_duration_days_snapshot.' يوم' : 'غير محدد' }}</td><td>{{ $report->overall_completion_percentage ?? '—' }}%</td><td>{{ $report->status === 'submitted' ? 'مقدم' : 'مسودة' }}</td><td>{{ $report->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td><td><a href="{{ route('tasks.achievement-reports.show', [$report->task, $report]) }}">عرض</a> @if(auth()->user()->hasPermission('task_reports.export')) · <a href="{{ route('tasks.achievement-reports.pdf', [$report->task, $report]) }}">PDF</a>@endif</td></tr>
        @empty<tr><td colspan="10" class="text-center text-muted py-4">لا توجد تقارير مطابقة.</td></tr>@endforelse
    </tbody></table></div>
    <div class="mt-3">{{ $reports->links() }}</div>
</main>
@endsection

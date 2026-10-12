@extends('layouts.app')
@section('content')
<main class="container py-4" dir="rtl">
    <a href="{{ route('tasks.show', $task) }}" class="btn btn-link px-0">العودة إلى المهمة</a>
    <h1 class="h3 mb-4">تقرير إنجاز: {{ $task->title }}</h1>
    <div class="card mb-4"><div class="card-header fw-bold">بيانات المهمة الأصلية (للقراءة فقط)</div><div class="card-body row g-3">
        <div class="col-md-6"><strong>العنوان</strong><div>{{ $task->title }}</div></div>
        <div class="col-md-6"><strong>المبلغ</strong><div>{{ auth()->user()->name }}</div></div>
        <div class="col-12"><strong>الوصف</strong><div class="text-break">{{ $task->description ?: 'غير محدد' }}</div></div>
        <div class="col-md-6"><strong>المكلفون</strong><div>{{ $task->assignees->pluck('name')->join('، ') ?: 'غير محدد' }}</div></div>
        <div class="col-md-3"><strong>تاريخ البدء</strong><div>{{ $task->start_date?->format('Y-m-d') ?? 'غير محدد' }}</div></div>
        <div class="col-md-3"><strong>الموعد النهائي</strong><div>{{ $task->due_date?->format('Y-m-d') ?? 'غير محدد' }}</div></div>
        <div class="col-md-3"><strong>المدة المخططة</strong><div>{{ $report->planned_duration_days_snapshot ? $report->planned_duration_days_snapshot.' يوم' : 'غير محدد' }}</div></div>
        <div class="col-md-3"><strong>حالة التنفيذ</strong><div>{{ $task->status }}</div></div>
        <div class="col-md-3"><strong>رقم المهمة</strong><div>{{ $task->id }}</div></div>
    </div></div>
    <form method="POST" action="{{ route('tasks.achievement-reports.update', [$task, $report]) }}">
        @csrf @method('PUT')
        <div class="card mb-4"><div class="card-header fw-bold">ملخص الإنجازات</div><div class="card-body">
            <div class="mb-3"><label class="form-label">نسبة الإنجاز الإجمالية</label><input class="form-control" type="number" min="0" max="100" step="0.01" name="overall_completion_percentage" value="{{ old('overall_completion_percentage', $report->overall_completion_percentage) }}" required></div>
            <div id="achievement-rows">
                @forelse(old('items', $report->items->toArray() ?: [['achievement_description' => '', 'completion_percentage' => '', 'notes' => '']]) as $i => $item)
                    <div class="border rounded p-3 mb-3 achievement-row"><div class="d-flex justify-content-between"><strong>{{ $task->title }}</strong><button type="button" class="btn btn-sm btn-outline-danger remove-row">حذف</button></div><div class="text-muted small mb-2">{{ $task->description }} · المدة: {{ $report->planned_duration_days_snapshot ? $report->planned_duration_days_snapshot.' يوم' : 'غير محدد' }}</div>
                        <label class="form-label">الإنجاز الفعلي</label><textarea class="form-control mb-2" name="items[{{ $i }}][achievement_description]" required>{{ $item['achievement_description'] ?? '' }}</textarea>
                        <label class="form-label">نسبة الإنجاز</label><input class="form-control mb-2" type="number" min="0" max="100" step="0.01" name="items[{{ $i }}][completion_percentage]" value="{{ $item['completion_percentage'] ?? '' }}" required>
                        <label class="form-label">ملاحظات</label><textarea class="form-control" name="items[{{ $i }}][notes]">{{ $item['notes'] ?? '' }}</textarea></div>
                @empty @endforelse
            </div><button type="button" id="add-achievement" class="btn btn-outline-primary">إضافة إنجاز</button>
        </div></div>
        <div class="card mb-4"><div class="card-header fw-bold">أدوار العاملين في تنفيذ الإنجازات</div><div class="card-body"><div id="worker-rows">
            @foreach(old('workers', $report->workers->toArray()) as $i => $worker)
                <div class="row g-2 mb-2 worker-row"><div class="col-md-3"><select name="workers[{{ $i }}][worker_user_id]" class="form-select" required>@foreach($task->assignees as $assignee)<option value="{{ $assignee->id }}" @selected(($worker['worker_user_id'] ?? null) == $assignee->id)>{{ $assignee->name }}</option>@endforeach</select></div><div class="col-md-3"><input class="form-control" name="workers[{{ $i }}][worker_role]" placeholder="الدور" value="{{ $worker['worker_role'] ?? '' }}"></div><div class="col-md-3"><input class="form-control" name="workers[{{ $i }}][achievement_description]" placeholder="المساهمة" value="{{ $worker['achievement_description'] ?? '' }}"></div><div class="col-md-2"><input class="form-control" type="number" min="0" max="100" name="workers[{{ $i }}][completion_percentage]" placeholder="النسبة" value="{{ $worker['completion_percentage'] ?? '' }}"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div><div class="col-12"><input class="form-control" name="workers[{{ $i }}][notes]" placeholder="ملاحظات" value="{{ $worker['notes'] ?? '' }}"></div></div>
            @endforeach
        </div><button type="button" id="add-worker" class="btn btn-outline-primary">إضافة عامل</button></div></div>
        <div class="card mb-4"><div class="card-header fw-bold">أبرز النتائج المحققة</div><div class="card-body"><textarea class="form-control" rows="5" name="key_results">{{ old('key_results', $report->key_results) }}</textarea></div></div>
        <div class="card mb-4"><div class="card-header fw-bold">المرفقات</div><div class="card-body">
            <input type="file" class="form-control" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <button class="btn btn-outline-primary mt-2" type="submit" formaction="{{ route('tasks.achievement-reports.attachments.store', [$task, $report]) }}" formmethod="POST" formnovalidate>رفع المرفق</button>
            @foreach($report->attachments as $attachment)<div class="mt-2"><a href="{{ route('tasks.achievement-reports.attachments.download', [$task, $report, $attachment]) }}">{{ $attachment->file_name }}</a></div>@endforeach
        </div></div>
        <button class="btn btn-outline-secondary" name="action" value="draft">حفظ كمسودة</button>
        @if(auth()->user()->hasPermission('task_reports.submit'))<button class="btn btn-primary" name="action" value="submit">تقديم التقرير نهائياً</button>@endif
    </form>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let itemIndex = {{ count(old('items', $report->items->toArray() ?: [[]])) }};
    let workerIndex = {{ count(old('workers', $report->workers->toArray())) }};
    document.getElementById('add-achievement').addEventListener('click', () => {
        const index = itemIndex++;
        document.getElementById('achievement-rows').insertAdjacentHTML('beforeend', `<div class="border rounded p-3 mb-3 achievement-row"><strong>{{ addslashes($task->title) }}</strong><button type="button" class="btn btn-sm btn-outline-danger remove-row float-end">حذف</button><div class="text-muted small mb-2">{{ addslashes($task->description ?? '') }}</div><textarea class="form-control mb-2" name="items[${index}][achievement_description]" placeholder="الإنجاز الفعلي" required></textarea><input class="form-control mb-2" type="number" min="0" max="100" step="0.01" name="items[${index}][completion_percentage]" placeholder="النسبة" required><textarea class="form-control" name="items[${index}][notes]" placeholder="ملاحظات"></textarea></div>`);
    });
    document.getElementById('add-worker').addEventListener('click', () => {
        const index = workerIndex++;
        const options = @json($task->assignees->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])->values());
        const select = options.map(user => `<option value="${user.id}">${user.name}</option>`).join('');
        document.getElementById('worker-rows').insertAdjacentHTML('beforeend', `<div class="row g-2 mb-2 worker-row"><div class="col-md-3"><select name="workers[${index}][worker_user_id]" class="form-select" required>${select}</select></div><div class="col-md-3"><input class="form-control" name="workers[${index}][worker_role]" placeholder="الدور"></div><div class="col-md-3"><input class="form-control" name="workers[${index}][achievement_description]" placeholder="المساهمة"></div><div class="col-md-2"><input class="form-control" type="number" min="0" max="100" name="workers[${index}][completion_percentage]" placeholder="النسبة"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div><div class="col-12"><input class="form-control" name="workers[${index}][notes]" placeholder="ملاحظات"></div></div>`);
    });
    document.addEventListener('click', event => { if (event.target.closest('.remove-row')) event.target.closest('.achievement-row, .worker-row')?.remove(); });
});
</script>
@endsection

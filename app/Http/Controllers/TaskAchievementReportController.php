<?php

namespace App\Http\Controllers;

use App\Models\Authority;
use App\Models\Task;
use App\Models\TaskAchievementReport;
use App\Models\TaskAchievementReportAttachment;
use App\Models\User;
use App\Notifications\TaskNotification;
use App\Services\OrganizationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaskAchievementReportController extends Controller
{
    public function create(Task $task): View|RedirectResponse
    {
        $this->assertEligible($task);
        abort_unless(auth()->user()->hasPermission('task_reports.create') && $task->assignees()->whereKey(auth()->id())->exists(), 403);
        $this->ensureCurrentRequirements($task);
        $report = TaskAchievementReport::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => auth()->id()],
            $this->snapshot($task)
        );
        abort_if($report->status === 'submitted', 409);

        return redirect()->route('tasks.achievement-reports.edit', [$task, $report]);
    }

    public function edit(Task $task, TaskAchievementReport $report): View
    {
        $this->assertEligible($task);
        $this->assertReportAccess($task, $report, 'task_reports.edit');
        abort_if($report->status === 'submitted', 409);
        $task->load('assignees');
        $report->load('items', 'workers', 'attachments');

        return view('projects.tasks.achievement-reports.form', compact('task', 'report'));
    }

    public function update(Request $request, Task $task, TaskAchievementReport $report): RedirectResponse
    {
        $this->assertEligible($task);
        $this->assertReportAccess($task, $report, 'task_reports.edit');
        abort_if($report->status === 'submitted', 409);
        $validated = $request->validate([
            'overall_completion_percentage' => ['required', 'numeric', 'between:0,100'],
            'key_results' => ['nullable', 'string', 'max:20000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.achievement_description' => ['required', 'string', 'max:5000'],
            'items.*.completion_percentage' => ['required', 'numeric', 'between:0,100'],
            'items.*.notes' => ['nullable', 'string', 'max:5000'],
            'workers' => ['nullable', 'array', 'max:50'],
            'workers.*.worker_user_id' => ['required', 'integer'],
            'workers.*.worker_role' => ['nullable', 'string', 'max:255'],
            'workers.*.achievement_description' => ['nullable', 'string', 'max:5000'],
            'workers.*.completion_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'workers.*.notes' => ['nullable', 'string', 'max:5000'],
            'action' => ['required', 'in:draft,submit'],
        ]);
        $assignedIds = $task->assignees()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        foreach ($validated['workers'] ?? [] as $worker) {
            abort_unless(in_array((int) $worker['worker_user_id'], $assignedIds, true), 422, 'Worker must be assigned to this task.');
        }
        if ($validated['action'] === 'submit') {
            abort_unless(auth()->user()->hasPermission('task_reports.submit'), 403);
        }

        $wasSubmitted = $report->status === 'submitted';
        DB::transaction(function () use ($validated, $report): void {
            $report->update([
                'overall_completion_percentage' => $validated['overall_completion_percentage'],
                'key_results' => $validated['key_results'] ?? null,
                'status' => $validated['action'] === 'submit' ? 'submitted' : 'draft',
                'submitted_at' => $validated['action'] === 'submit' ? now() : null,
            ]);
            $report->items()->delete();
            foreach ($validated['items'] as $position => $item) {
                $report->items()->create($item + ['sort_order' => $position]);
            }
            $report->workers()->delete();
            foreach ($validated['workers'] ?? [] as $position => $worker) {
                $report->workers()->create($worker + ['sort_order' => $position]);
            }
        });
        if ($validated['action'] === 'submit' && ! $wasSubmitted) {
            $this->notifyTaskCreator($task, $report, 'achievement_report_submitted', "تم تقديم تقرير الإنجاز للمهمة: {$task->title}");
            $missingCount = $task->assignees()->whereDoesntHave('taskAchievementReports', fn ($reports) => $reports->where('task_achievement_reports.task_id', $task->id)->where('status', 'submitted'))->count();
            if ($missingCount === 0) {
                $this->notifyTaskCreator($task, $report, 'achievement_reports_complete', "اكتملت جميع تقارير الإنجاز للمهمة: {$task->title}");
            }
        }

        return redirect()->route('tasks.achievement-reports.show', [$task, $report]);
    }

    public function show(Task $task, TaskAchievementReport $report): View
    {
        $this->assertEligible($task);
        $this->assertReportAccess($task, $report, 'task_reports.view');
        $task->load('assignees');
        $report->load(['user', 'items', 'workers.worker']);

        return view('projects.tasks.achievement-reports.show', compact('task', 'report'));
    }

    public function index(Task $task): View
    {
        $this->assertEligible($task);
        abort_unless(auth()->user()->hasPermission('task_reports.view'), 403);
        $task->load('assignees');
        $canViewAll = auth()->user()->hasPermission('task_reports.view_all') || auth()->user()->hasPermission('task_reports.review');
        abort_unless($task->assignees->contains('id', auth()->id()) || ($canViewAll && $this->taskIsInScope($task, auth()->user())), 403);
        $reports = $task->achievementReports()->with('user')->get()
            ->filter(fn (TaskAchievementReport $report): bool => (int) $report->user_id === (int) auth()->id() || ($canViewAll && $this->reportIsInScope($report)))
            ->keyBy('user_id');

        return view('projects.tasks.achievement-reports.index', compact('task', 'reports'));
    }

    public function reportIndex(Request $request): View
    {
        $viewer = auth()->user();
        abort_unless($viewer->hasPermission('task_reports.view'), 403);
        $canViewAll = $viewer->hasPermission('task_reports.view_all') || $viewer->hasPermission('task_reports.review');
        $query = TaskAchievementReport::query()->with(['task.assignees', 'user', 'items'])
            ->whereHas('task', fn ($tasks) => $tasks->whereNull('project_id')->whereNull('value_chain_id')
                ->whereNull('assigned_entity_id')->whereNull('assigned_authority_id')->whereNull('assigned_user_id')
                ->where('is_within_activities', false)->whereNull('activity_type')->whereNull('activity_id')
                ->whereNull('procedure_id')->whereNull('executive_activity_action_id'));
        $userQuery = User::query()->withoutGlobalScopes();
        app(OrganizationResolver::class)->applyVisibleUsers($userQuery, $viewer);
        $visibleUserIds = $userQuery->pluck('users.id');
        if (! $canViewAll) {
            $query->where('user_id', $viewer->id);
        } else {
            $query->whereIn('user_id', $visibleUserIds);
        }
        $query->whereHas('task', fn ($tasks) => $this->applyTaskScope($tasks, $viewer));
        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($reports) use ($search): void {
                $reports->whereHas('task', fn ($tasks) => $tasks->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->boolean('overdue')) {
            $query->where('status', '!=', 'submitted')
                ->whereHas('task', fn ($tasks) => $tasks->whereDate('due_date', '<', now()->toDateString()));
        }
        if ($request->filled('submitted_from')) {
            $query->whereDate('submitted_at', '>=', $request->input('submitted_from'));
        }
        if ($request->filled('submitted_to')) {
            $query->whereDate('submitted_at', '<=', $request->input('submitted_to'));
        }

        $reports = $query->latest()->paginate(20)->withQueryString();

        return view('projects.tasks.achievement-reports.index-all', compact('reports'));
    }

    public function print(Task $task, TaskAchievementReport $report): View
    {
        $this->assertEligible($task);
        abort_unless(auth()->user()->hasPermission('task_reports.export'), 403);
        $this->assertReportAccess($task, $report, 'task_reports.view');
        $task->load('assignees');
        $report->load(['user', 'items', 'workers.worker']);

        return view('projects.tasks.achievement-reports.print', compact('task', 'report'));
    }

    public function pdf(Task $task, TaskAchievementReport $report)
    {
        abort(501, 'PDF export requires an approved Arabic-capable PDF package.');
    }

    public function uploadAttachment(Request $request, Task $task, TaskAchievementReport $report): RedirectResponse
    {
        $this->assertEligible($task);
        $this->assertReportAccess($task, $report, 'task_reports.edit');
        abort_if($report->status === 'submitted', 409);
        $validated = $request->validate([
            'attachment' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);
        $file = $validated['attachment'];
        $path = $file->store("task-achievement-reports/{$task->id}/{$report->id}", 'local');
        $report->attachments()->create([
            'uploaded_by' => auth()->id(),
            'file_name' => basename($file->getClientOriginalName()),
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'تم إرفاق الملف بالتقرير.');
    }

    public function downloadAttachment(Task $task, TaskAchievementReport $report, TaskAchievementReportAttachment $attachment)
    {
        $this->assertEligible($task);
        abort_unless((int) $attachment->report_id === (int) $report->id, 404);
        $this->assertReportAccess($task, $report, 'task_reports.view');
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->file_name);
    }

    private function assertReportAccess(Task $task, TaskAchievementReport $report, string $permission): void
    {
        abort_unless((int) $report->task_id === (int) $task->id, 404);
        $isOwner = (int) $report->user_id === (int) auth()->id();
        $canReview = auth()->user()->hasPermission('task_reports.review') || auth()->user()->hasPermission('task_reports.view_all');
        abort_unless(auth()->user()->hasPermission($permission) && ($isOwner || $canReview), 403);
    }

    private function assertEligible(Task $task): void
    {
        abort_unless($task->isStandaloneGeneralTask(), 404);
    }

    private function reportIsInScope(TaskAchievementReport $report): bool
    {
        $viewer = auth()->user();
        $report->loadMissing(['user.entity.authority', 'user.authority', 'task']);
        if (! $report->user || ! $report->task) {
            return false;
        }
        if ($viewer->isAdmin()) {
            return true;
        }

        $userQuery = User::query()->withoutGlobalScopes()->whereKey($report->user_id);
        app(OrganizationResolver::class)->applyVisibleUsers($userQuery, $viewer);
        if (! $userQuery->exists()) {
            return false;
        }

        return $this->taskIsInScope($report->task, $viewer);
    }

    private function taskIsInScope(Task $task, User $viewer): bool
    {
        if ($viewer->isAdmin()) {
            return true;
        }
        if ((int) $task->created_by === (int) $viewer->id || $task->assignees()->where('users.id', $viewer->id)->exists()) {
            return true;
        }
        $entityIds = $viewer->getFilteredEntityIds();
        if ($task->assigned_entity_id && in_array((int) $task->assigned_entity_id, $entityIds, true)) {
            return true;
        }
        if ($task->assigned_authority_id) {
            $authorityQuery = Authority::query()->withoutGlobalScopes()->whereKey($task->assigned_authority_id);
            app(OrganizationResolver::class)->applyVisibleAuthorities($authorityQuery, $viewer);

            return $authorityQuery->exists();
        }

        return false;
    }

    private function applyTaskScope($query, User $viewer): void
    {
        if ($viewer->isAdmin()) {
            return;
        }
        $entityIds = $viewer->getFilteredEntityIds();
        $authorityQuery = Authority::query()->withoutGlobalScopes();
        app(OrganizationResolver::class)->applyVisibleAuthorities($authorityQuery, $viewer);
        $authorityIds = $authorityQuery->pluck('authorities.id');
        $query->where(function ($tasks) use ($viewer, $entityIds, $authorityIds): void {
            $tasks->where('created_by', $viewer->id)
                ->orWhereHas('assignees', fn ($assignees) => $assignees->where('users.id', $viewer->id));
            if ($entityIds !== []) {
                $tasks->orWhereIn('assigned_entity_id', $entityIds);
            }
            if ($authorityIds->isNotEmpty()) {
                $tasks->orWhereIn('assigned_authority_id', $authorityIds);
            }
        });
    }

    private function notifyTaskCreator(Task $task, TaskAchievementReport $report, string $action, string $message): void
    {
        $creator = $task->creator;
        if (! $creator || (int) $creator->id === (int) $report->user_id) {
            return;
        }
        $creator->notify(new TaskNotification(
            $task,
            $action,
            'task_achievement_report',
            $message,
            route('tasks.achievement-reports.index', $task),
            'fas fa-clipboard-check',
            $report->user
        ));
    }

    /** @return array<string, mixed> */
    private function snapshot(Task $task): array
    {
        $duration = null;
        if ($task->start_date && $task->due_date && Carbon::parse($task->due_date)->greaterThanOrEqualTo($task->start_date)) {
            $duration = Carbon::parse($task->start_date)->diffInDays(Carbon::parse($task->due_date)) + 1;
        }

        return [
            'status' => 'draft',
            'task_title_snapshot' => $task->title,
            'task_description_snapshot' => $task->description,
            'task_start_date_snapshot' => $task->start_date,
            'task_due_date_snapshot' => $task->due_date,
            'task_status_snapshot' => $task->status,
            'planned_duration_days_snapshot' => $duration,
        ];
    }

    private function ensureCurrentRequirements(Task $task): void
    {
        foreach ($task->assignees()->get() as $assignee) {
            TaskAchievementReport::firstOrCreate(
                ['task_id' => $task->id, 'user_id' => $assignee->id],
                $this->snapshot($task) + ['status' => 'draft']
            );
        }
    }
}

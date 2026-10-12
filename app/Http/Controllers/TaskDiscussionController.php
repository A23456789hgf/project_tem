<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDiscussion;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskDiscussionController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Store a newly created discussion reply.
     */
    public function store(Request $request, Project $project, Task $task)
    {
        $this->authorize('reply', [TaskDiscussion::class, $task]);

        $validated = $request->validate([
            'message' => 'required|string',
            'parent_id' => 'nullable|exists:task_discussions,id',
        ]);

        $validated['task_id'] = $task->id;
        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated, $task, &$discussion) {
            $discussion = TaskDiscussion::create($validated);

            $this->taskService->logActivity($task, 'comment_posted', $discussion, [
                'message_preview' => mb_substr($discussion->message, 0, 100),
            ]);

            // Parse mentions and notify users
            $actionUrl = route('projects.tasks.show', [$task->project_id, $task->id]).'#discussion-'.$discussion->id;
            $this->taskService->parseAndNotifyMentions($task, $discussion->message, 'discussion_reply', $actionUrl);
        });

        session()->flash('success', 'تم إضافة التعليق بنجاح.');

        return redirect()->back();
    }

    public function storeGeneral(Request $request, Task $task)
    {
        abort_unless($task->project_id === null, 404);
        $this->authorize('reply', [TaskDiscussion::class, $task]);

        $validated = $request->validate([
            'message' => 'required|string',
            'parent_id' => 'nullable|exists:task_discussions,id',
        ]);
        $validated['task_id'] = $task->id;
        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated, $task, &$discussion): void {
            $discussion = TaskDiscussion::create($validated);
            $this->taskService->logActivity($task, 'comment_posted', $discussion, [
                'message_preview' => mb_substr($discussion->message, 0, 100),
            ]);
            $actionUrl = route('tasks.show', $task)."#discussion-{$discussion->id}";
            $this->taskService->parseAndNotifyMentions($task, $discussion->message, 'discussion_reply', $actionUrl);
        });

        return redirect()->back()->with('success', 'تم إضافة التعليق بنجاح.');
    }
}

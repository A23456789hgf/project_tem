<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskAchievementReport;
use App\Notifications\TaskNotification;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily reminders for tasks with approaching deadlines';

    /**
     * Execute the console command.
     */
    public function handle(TaskService $taskService)
    {
        $this->info('Starting task reminders check...');

        // Fetch tasks that are not completed/cancelled and have a due date
        $tasks = Task::whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->get();

        $notificationsSent = 0;

        foreach ($tasks as $task) {
            $dueDate = Carbon::parse($task->due_date)->startOfDay();
            $today = Carbon::now()->startOfDay();

            if ($dueDate->lt($today)) {
                // Task is overdue
                $daysOverdue = $today->diffInDays($dueDate);
                $message = "تذكير: المهمة '{$task->title}' متأخرة عن موعدها بـ {$daysOverdue} يوم/أيام.";
                $taskService->notifyAssignees($task, 'need_action', $message, 'fas fa-exclamation-triangle');
                $notificationsSent++;

                if ($task->isStandaloneGeneralTask()) {
                    foreach ($task->assignees as $assignee) {
                        $report = TaskAchievementReport::firstOrCreate(
                            ['task_id' => $task->id, 'user_id' => $assignee->id],
                            [
                                'status' => 'draft',
                                'task_title_snapshot' => $task->title,
                                'task_description_snapshot' => $task->description,
                                'task_start_date_snapshot' => $task->start_date,
                                'task_due_date_snapshot' => $task->due_date,
                                'task_status_snapshot' => $task->status,
                            ]
                        );
                        if ($report->status === 'submitted') {
                            continue;
                        }
                        $alreadyNotifiedToday = $assignee->notifications()
                            ->whereDate('created_at', $today->toDateString())
                            ->where('data->action_type', 'achievement_report_overdue')
                            ->where('data->task_id', $task->id)
                            ->exists();
                        if (! $alreadyNotifiedToday) {
                            $assignee->notify(new TaskNotification(
                                $task,
                                'achievement_report_overdue',
                                'task_achievement_report',
                                "تقرير الإنجاز للمهمة {$task->title} متأخر ولم يتم تقديمه.",
                                route('tasks.achievement-reports.edit', [$task, $report]),
                                'fas fa-exclamation-triangle'
                            ));
                        }
                    }
                }
            } elseif ($dueDate->diffInDays($today) <= 3) {
                // Task is due within 3 days
                $daysRemaining = $dueDate->diffInDays($today);

                if ($daysRemaining == 0) {
                    $message = "تذكير: المهمة '{$task->title}' موعد تسليمها اليوم!";
                } else {
                    $message = "تذكير: يتبقى {$daysRemaining} يوم/أيام على موعد تسليم المهمة '{$task->title}'.";
                }

                $taskService->notifyAssignees($task, 'requires_action', $message, 'fas fa-clock');
                $notificationsSent++;
            }
        }

        $this->info("Task reminders check completed. {$notificationsSent} task reminders processed.");

        return Command::SUCCESS;
    }
}

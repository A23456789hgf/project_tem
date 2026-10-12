<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskDiscussionPolicy
{
    /**
     * Determine whether the user can view discussions for the task.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->hasPermission('task.chat.view', $task);
    }

    /**
     * Determine whether the user can reply to discussions for the task.
     */
    public function reply(User $user, Task $task): bool
    {
        return $user->hasPermission('task.chat.reply', $task);
    }
}

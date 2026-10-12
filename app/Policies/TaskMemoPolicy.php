<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskMemo;
use App\Models\User;

class TaskMemoPolicy
{
    /**
     * Determine whether the user can create a memo for the task.
     */
    public function create(User $user, Task $task): bool
    {
        return $user->hasPermission('task.memo.create', $task);
    }

    /**
     * Determine whether the user can sign the memo.
     */
    public function sign(User $user, TaskMemo $memo): bool
    {
        return $user->hasPermission('task.memo.sign', $memo->task);
    }

    /**
     * Determine whether the user can delete the memo.
     */
    public function delete(User $user, TaskMemo $memo): bool
    {
        return $user->hasPermission('task.memo.delete', $memo->task);
    }
}

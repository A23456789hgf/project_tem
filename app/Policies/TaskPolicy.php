<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks in a project.
     */
    public function viewAny(User $user, ?Project $project = null): bool
    {
        return $user->hasPermission('task.view', $project);
    }

    /**
     * Determine whether the user can view the specific task.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->hasPermission('task.view', $task);
    }

    /**
     * Determine whether the user can create a task in the project.
     */
    public function create(User $user, ?Project $project = null): bool
    {
        return $user->hasPermission('task.create', $project);
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission('task.edit', $task);
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission('task.delete', $task);
    }
}

<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id !== $task->tenant_id) {
            return false;
        }

        // If they have edit_all_tasks, they can edit any task in the tenant
        if ($user->hasPermission('edit_all_tasks')) {
            return true;
        }

        // Otherwise, they can edit if they have view_tasks and are assigned or entered it
        if ($user->hasPermission('view_tasks')) {
            return $user->id === (int) $task->entry_by
                || $user->id === (int) $task->script_by
                || $user->id === (int) $task->voice_by
                || $user->id === (int) $task->video_edit_by;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id !== $task->tenant_id) {
            return false;
        }

        return $user->hasPermission('delete_tasks');
    }
}

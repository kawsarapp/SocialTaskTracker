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
        // Super admin can edit anything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Must belong to the same tenant
        if ($user->tenant_id !== $task->tenant_id) {
            return false;
        }

        // Admin and Team Leader can edit any task in their tenant
        if ($user->role === 'admin' || $user->role === 'team_leader') {
            return true;
        }

        // Staff can only edit if they entered it, wrote the script, gave the voice, or edited the video
        if ($user->role === 'staff') {
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
        // Same rules apply for deletion
        return $this->update($user, $task);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'name', 'email', 'password', 'role', 'designation_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    // Role helper methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeamLeader(): bool
    {
        return $this->role === 'team_leader';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if user is authorized to perform specific actions.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin() || $this->role === 'admin') {
            return true;
        }

        if ($this->designation) {
            $permissions = $this->designation->permissions ?? [];
            return in_array($permission, $permissions);
        }

        if ($this->role === 'team_leader') {
            return in_array($permission, ['view_tasks', 'create_tasks', 'edit_all_tasks', 'view_analytics']);
        }

        if ($this->role === 'staff') {
            return in_array($permission, ['view_tasks', 'create_tasks']);
        }

        return false;
    }

    // Relationships
    public function loggedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'entry_by');
    }

    public function scriptedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'script_by');
    }

    public function voicedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'voice_by');
    }

    public function editedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'video_edit_by');
    }
}

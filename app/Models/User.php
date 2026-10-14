<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Resolve the effective Tenant for this user (supports Super Admin workspace switching / fallback).
     */
    public function effectiveTenant(): ?Tenant
    {
        if (!empty($this->attributes['tenant_id'])) {
            return $this->getRelationValue('tenant');
        }

        if ($this->isSuperAdmin()) {
            $sessionTenantId = session('active_tenant_id');
            if ($sessionTenantId) {
                $found = Tenant::find($sessionTenantId);
                if ($found) {
                    return $found;
                }
            }
            return Tenant::first();
        }

        return null;
    }

    /**
     * Resolve the effective tenant_id for this user.
     */
    public function effectiveTenantId(): ?int
    {
        if (!empty($this->attributes['tenant_id'])) {
            return (int) $this->attributes['tenant_id'];
        }

        return $this->effectiveTenant()?->id;
    }

    /**
     * Accessor so Auth::user()->tenant automatically resolves for Super Admins too.
     */
    public function getTenantAttribute(): ?Tenant
    {
        return $this->effectiveTenant();
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
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }

        if ($this->designation) {
            $permissions = $this->designation->permissions ?? [];
            if (!empty($permissions)) {
                return in_array($permission, $permissions);
            }
        }

        if ($this->isTeamLeader()) {
            return in_array($permission, [
                'view_tasks',
                'create_tasks',
                'edit_all_tasks',
                'delete_tasks',
                'quick_update_links',
                'bulk_actions',
                'export_tasks',
                'view_calendar',
                'view_analytics',
                'manage_settings',
                'view_activity_logs',
                'publish_social_posts',
            ]);
        }

        if ($this->isStaff()) {
            return in_array($permission, [
                'view_tasks',
                'create_tasks',
                'quick_update_links',
                'view_calendar',
            ]);
        }

        return false;
    }

    // Relationships
    public function loggedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'entry_by');
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments', 'user_id', 'task_id')
            ->withPivot('designation_id')
            ->withTimestamps();
    }

    public function scriptedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments', 'user_id', 'task_id')
            ->wherePivotIn('designation_id', Designation::withoutGlobalScopes()->where('name', 'Script Writer')->select('id'));
    }

    public function voicedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments', 'user_id', 'task_id')
            ->wherePivotIn('designation_id', Designation::withoutGlobalScopes()->where('name', 'Voice Artist')->select('id'));
    }

    public function editedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments', 'user_id', 'task_id')
            ->wherePivotIn('designation_id', Designation::withoutGlobalScopes()->where('name', 'Video Editor')->select('id'));
    }
}

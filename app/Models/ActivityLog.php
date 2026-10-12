<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'task_id',
        'task_title',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated task if applicable.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Record a comprehensive SaaS activity audit log entry.
     */
    public static function record(string $action, ?string $description = null, ?Task $task = null, ?array $changes = null): ?self
    {
        $user = Auth::user();
        $tenantId = $task?->tenant_id ?? $user?->effectiveTenantId();

        if (!$tenantId) {
            return null;
        }

        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'action' => $action,
            'task_id' => $task?->id,
            'task_title' => $description ?? ($task?->title ?? 'System Action'),
            'changes' => $changes,
        ]);
    }
}

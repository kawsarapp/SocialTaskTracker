<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'task_type_id',
        'facebook_url',
        'facebook_page_id',
        'youtube_url',
        'youtube_channel_id',
        'on_air_time',
        'on_air_date',
        'area_id',
        'team_id',
        'tags',
        'remarks',
        'entry_by',
        'shift_id',
        'thumbnail_path',
        'script_path',
        'views',
        'likes',
        'reach',
        'api_sync_error',
        'custom_fields',
    ];

    protected $casts = [
        'on_air_date' => 'date',
        'views' => 'integer',
        'likes' => 'integer',
        'reach' => 'integer',
        'custom_fields' => 'array',
    ];

    protected static function booted()
    {
        // 1. Task Created Hook
        static::created(function ($task) {
            $userId = \Illuminate\Support\Facades\Auth::id();
            $tenantId = $task->tenant_id ?? (\Illuminate\Support\Facades\Auth::user()?->tenant_id ?? null);
            if ($tenantId) {
                \App\Models\ActivityLog::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'action' => 'created',
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'changes' => null,
                ]);
            }
        });

        // 2. Task Updating Hook (to catch changed attributes before save)
        static::updating(function ($task) {
            $userId = \Illuminate\Support\Facades\Auth::id();
            $tenantId = $task->tenant_id ?? (\Illuminate\Support\Facades\Auth::user()?->tenant_id ?? null);

            if ($tenantId) {
                $changes = [];
                foreach ($task->getDirty() as $key => $newValue) {
                    if (in_array($key, ['created_at', 'updated_at'])) {
                        continue;
                    }
                    $changes[$key] = [
                        'old' => $task->getOriginal($key),
                        'new' => $newValue,
                    ];
                }

                if (!empty($changes)) {
                    \App\Models\ActivityLog::create([
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'action' => 'updated',
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'changes' => $changes,
                    ]);
                }
            }
        });

        // 3. Task Deleted Hook
        static::deleted(function ($task) {
            $userId = \Illuminate\Support\Facades\Auth::id();
            $tenantId = $task->tenant_id ?? (\Illuminate\Support\Facades\Auth::user()?->tenant_id ?? null);

            if ($tenantId) {
                \App\Models\ActivityLog::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'action' => 'deleted',
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'changes' => null,
                ]);
            }
        });
    }

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class);
    }

    public function youtubeChannel(): BelongsTo
    {
        return $this->belongsTo(YoutubeChannel::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function scriptWriter()
    {
        return $this->hasOneThrough(
            User::class,
            TaskAssignment::class,
            'task_id',
            'id',
            'id',
            'user_id'
        )->whereIn(
            'task_assignments.designation_id',
            Designation::withoutGlobalScopes()->where('name', 'Script Writer')->select('id')
        );
    }

    public function voiceArtist()
    {
        return $this->hasOneThrough(
            User::class,
            TaskAssignment::class,
            'task_id',
            'id',
            'id',
            'user_id'
        )->whereIn(
            'task_assignments.designation_id',
            Designation::withoutGlobalScopes()->where('name', 'Voice Artist')->select('id')
        );
    }

    public function videoEditor()
    {
        return $this->hasOneThrough(
            User::class,
            TaskAssignment::class,
            'task_id',
            'id',
            'id',
            'user_id'
        )->whereIn(
            'task_assignments.designation_id',
            Designation::withoutGlobalScopes()->where('name', 'Video Editor')->select('id')
        );
    }

    public function entryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entry_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class);
    }
}

<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'task_id',
        'title',
        'content',
        'post_type',
        'media_path',
        'thumbnail_path',
        'platforms',
        'facebook_page_id',
        'youtube_channel_id',
        'status',
        'platform_results',
        'scheduled_at',
        'published_at',
    ];

    protected $casts = [
        'platforms' => 'array',
        'platform_results' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class);
    }

    public function youtubeChannel(): BelongsTo
    {
        return $this->belongsTo(YoutubeChannel::class);
    }
}

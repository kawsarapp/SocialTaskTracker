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
        'script_by',
        'voice_by',
        'video_edit_by',
        'tags',
        'remarks',
        'entry_by',
    ];

    protected $casts = [
        'on_air_date' => 'date',
    ];

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

    public function scriptWriter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'script_by');
    }

    public function voiceArtist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voice_by');
    }

    public function videoEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'video_edit_by');
    }

    public function entryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entry_by');
    }
}

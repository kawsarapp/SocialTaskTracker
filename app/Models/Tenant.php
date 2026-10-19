<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'slug', 
        'is_active', 
        'slack_webhook_url', 
        'telegram_bot_token', 
        'telegram_chat_id',
        'youtube_api_key',
        'facebook_page_access_token',
        'api_sync_interval',
        'last_api_sync_at',
        'task_form_config',
        'google_sheet_url',
        'google_sheet_webhook_token',
        'google_sheet_auto_sync',
        'google_sheet_last_synced_at',
        'google_sheet_column_mapping',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_sync_interval' => 'integer',
        'last_api_sync_at' => 'datetime',
        'task_form_config' => 'array',
        'google_sheet_auto_sync' => 'boolean',
        'google_sheet_last_synced_at' => 'datetime',
        'google_sheet_column_mapping' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($tenant) {
            if (empty($tenant->google_sheet_webhook_token)) {
                $tenant->google_sheet_webhook_token = \Illuminate\Support\Str::random(32);
            }
        });
    }

    public function getOrGenerateWebhookToken(): string
    {
        if (empty($this->google_sheet_webhook_token)) {
            $this->google_sheet_webhook_token = \Illuminate\Support\Str::random(32);
            $this->save();
        }

        return $this->google_sheet_webhook_token;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function taskTypes(): HasMany
    {
        return $this->hasMany(TaskType::class);
    }

    public function facebookPages(): HasMany
    {
        return $this->hasMany(FacebookPage::class);
    }

    public function youtubeChannels(): HasMany
    {
        return $this->hasMany(YoutubeChannel::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function getTaskFormConfigAttribute($value)
    {
        $default = [
            'task_type_id' => ['visible' => true, 'required' => true, 'sort_order' => 1, 'is_custom' => false, 'label' => 'Task Type / Category', 'type' => 'radio'],
            'facebook_url' => ['visible' => true, 'required' => false, 'sort_order' => 2, 'is_custom' => false, 'label' => 'Facebook Post/Video Link', 'type' => 'url'],
            'facebook_page_id' => ['visible' => true, 'required' => false, 'sort_order' => 3, 'is_custom' => false, 'label' => 'Facebook Page Selector', 'type' => 'select'],
            'youtube_url' => ['visible' => true, 'required' => false, 'sort_order' => 4, 'is_custom' => false, 'label' => 'YouTube Video Link', 'type' => 'url'],
            'youtube_channel_id' => ['visible' => true, 'required' => false, 'sort_order' => 5, 'is_custom' => false, 'label' => 'YouTube Channel Selector', 'type' => 'select'],
            'on_air_time' => ['visible' => true, 'required' => true, 'sort_order' => 6, 'is_custom' => false, 'label' => 'On-Air Broadcast Time', 'type' => 'text'],
            'on_air_date' => ['visible' => true, 'required' => true, 'sort_order' => 7, 'is_custom' => false, 'label' => 'On-Air Date Selector', 'type' => 'date'],
            'area_id' => ['visible' => true, 'required' => false, 'sort_order' => 8, 'is_custom' => false, 'label' => 'News Area / Sources', 'type' => 'select'],
            'team_id' => ['visible' => true, 'required' => false, 'sort_order' => 9, 'is_custom' => false, 'label' => 'Assigned Production Team', 'type' => 'select'],
            'shift_id' => ['visible' => true, 'required' => false, 'sort_order' => 10, 'is_custom' => false, 'label' => 'Work Shift Scheduler', 'type' => 'select'],
            'tags' => ['visible' => true, 'required' => false, 'sort_order' => 11, 'is_custom' => false, 'label' => 'Context tags', 'type' => 'text'],
            'remarks' => ['visible' => true, 'required' => false, 'sort_order' => 12, 'is_custom' => false, 'label' => 'Remarks / Internal Notes', 'type' => 'textarea'],
            'thumbnail' => ['visible' => true, 'required' => false, 'sort_order' => 13, 'is_custom' => false, 'label' => 'Visual Thumbnail Upload', 'type' => 'file'],
            'script_file' => ['visible' => true, 'required' => false, 'sort_order' => 14, 'is_custom' => false, 'label' => 'Script Word/PDF Document Attachment', 'type' => 'file']
        ];

        $decoded = $value ? (is_array($value) ? $value : json_decode($value, true)) : [];
        if (!is_array($decoded)) {
            $decoded = [];
        }

        $merged = [];
        foreach ($default as $key => $defVal) {
            $defVal['key'] = $key;
            if (isset($decoded[$key])) {
                $merged[$key] = array_merge($defVal, $decoded[$key]);
            } else {
                $merged[$key] = $defVal;
            }
        }

        foreach ($decoded as $key => $val) {
            if (!isset($default[$key])) {
                $val['key'] = $key;
                $merged[$key] = $val;
            }
        }

        uasort($merged, function ($a, $b) {
            return ($a['sort_order'] ?? 99) <=> ($b['sort_order'] ?? 99);
        });

        return $merged;
    }
}

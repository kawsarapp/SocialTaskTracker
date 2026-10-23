<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoUpload extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'task_id',
        'original_filename',
        'r2_key',
        'r2_url',
        'thumbnail_r2_key',
        'thumbnail_r2_url',
        'file_size',
        'duration',
        'mime_type',
        'status',
        'ai_title',
        'ai_description',
        'ai_tags',
        'ai_hashtags',
        'ai_chapters',
        'youtube_video_id',
        'youtube_url',
        'youtube_status',
        'facebook_post_id',
        'facebook_url',
        'platform_results',
    ];

    protected $casts = [
        'ai_tags'          => 'array',
        'ai_hashtags'      => 'array',
        'platform_results' => 'array',
        'file_size'        => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    // ─── Helpers / Accessors ──────────────────────────────────────────────────

    /**
     * Returns a human-readable file size string.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Returns a Tailwind badge color class based on current status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'uploading'  => 'bg-blue-100 text-blue-700 border border-blue-200',
            'ready'      => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            'processing' => 'bg-amber-100 text-amber-700 border border-amber-200',
            'published'  => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
            'failed'     => 'bg-rose-100 text-rose-700 border border-rose-200',
            default      => 'bg-gray-100 text-gray-600 border border-gray-200',
        };
    }
}

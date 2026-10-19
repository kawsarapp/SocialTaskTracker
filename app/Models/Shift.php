<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'start_time',
        'end_time',
    ];

    /**
     * Get the tasks associated with this shift.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

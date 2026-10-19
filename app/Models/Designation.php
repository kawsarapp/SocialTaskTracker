<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Designation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'color', 'permissions', 'is_task_column'];

    protected $casts = [
        'permissions' => 'array',
        'is_task_column' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

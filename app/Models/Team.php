<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['tenant_id', 'name'];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

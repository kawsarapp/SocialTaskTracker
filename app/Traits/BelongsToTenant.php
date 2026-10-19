<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && !$model->tenant_id) {
                // Assign tenant_id from logged-in user (or active tenant for Super Admin) if not already set
                $user = Auth::user();
                $effectiveTenantId = $user->effectiveTenantId();
                if ($effectiveTenantId) {
                    $model->tenant_id = $effectiveTenantId;
                }
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                // Super admins can view all tenant data; others are scoped to their own tenant
                if ($user->role !== 'super_admin') {
                    $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

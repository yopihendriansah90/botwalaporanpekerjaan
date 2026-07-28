<?php

namespace App\Models\Concerns;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $context = app(TenantContext::class);

            if (! app()->runningInConsole() && ! auth()->check()) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', 0);
                return;
            }

            if (! $context->shouldScope()) {
                return;
            }

            $tenantId = $context->id();

            $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId ?? 0);
        });

        static::creating(function ($model): void {
            if (blank($model->tenant_id)) {
                $tenantId = app(TenantContext::class)->creationId();

                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}

<?php

namespace App\Services;

use App\Models\Tenant;
use Closure;

class TenantContext
{
    private ?int $forcedTenantId = null;

    public function id(): ?int
    {
        if ($this->forcedTenantId !== null) {
            return $this->forcedTenantId;
        }

        if (app()->runningInConsole()) {
            return null;
        }

        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $selected = session('tenant_id');

        $selectedIsAllowed = $selected && (
            $user->isSuperAdmin()
                ? Tenant::query()->whereKey($selected)->where('is_active', true)->exists()
                : $user->tenants()->whereKey($selected)->where('tenants.is_active', true)->exists()
        );

        if ($selectedIsAllowed) {
            return (int) $selected;
        }

        if ($user->isSuperAdmin()) {
            return Tenant::query()->where('slug', 'tenant-utama')->value('id');
        }

        $tenantId = $user->tenants()->where('tenants.is_active', true)->value('tenants.id');

        if ($tenantId) {
            session(['tenant_id' => $tenantId]);
            return (int) $tenantId;
        }

        return null;
    }

    public function shouldScope(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        $user = auth()->user();
        return $user !== null && ! $user->isSuperAdmin();
    }

    public function creationId(): ?int
    {
        return $this->id() ?? Tenant::query()->where('slug', 'tenant-utama')->value('id');
    }

    public function current(): ?Tenant
    {
        $id = $this->id();
        return $id ? Tenant::query()->find($id) : null;
    }

    public function set(?int $tenantId): void
    {
        $user = app()->runningInConsole() ? null : auth()->user();

        if ($tenantId !== null) {
            $tenant = Tenant::query()->find($tenantId);

            if (! $tenant || ! $tenant->is_active) {
                abort(403, 'Workspace tidak aktif atau tidak ditemukan.');
            }

            if ($user && ! $user->isSuperAdmin() && ! $user->tenants()->whereKey($tenantId)->exists()) {
                abort(403, 'Anda tidak memiliki akses ke workspace tersebut.');
            }
        }

        $this->forcedTenantId = $tenantId;

        if (! app()->runningInConsole()) {
            session(['tenant_id' => $tenantId]);
        }
    }

    public function run(int $tenantId, Closure $callback): mixed
    {
        $previous = $this->forcedTenantId;
        $this->forcedTenantId = $tenantId;

        try {
            return $callback();
        } finally {
            $this->forcedTenantId = $previous;
        }
    }
}

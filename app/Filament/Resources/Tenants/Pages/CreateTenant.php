<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Services\TenantContext;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterCreate(): void
    {
        $this->record->users()->syncWithoutDetaching([
            auth()->id() => ['role' => 'owner'],
        ]);

        app(TenantContext::class)->set($this->record->id);
    }
}

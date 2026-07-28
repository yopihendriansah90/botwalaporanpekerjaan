<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\TenantContext;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Tambah Pengguna';

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId) {
            $this->record->tenants()->syncWithoutDetaching([$tenantId => ['role' => 'member']]);
        }
    }
}

<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Tenant;
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
        $tenant = Tenant::create([
            'name' => 'Workspace '.$this->record->name,
            'slug' => 'user-'.$this->record->id,
            'is_active' => true,
        ]);

        $this->record->tenants()->syncWithoutDetaching([$tenant->id => ['role' => 'owner']]);
    }
}

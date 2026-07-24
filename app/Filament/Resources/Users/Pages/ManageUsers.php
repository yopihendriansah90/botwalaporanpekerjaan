<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tambah Pengguna')
                ->icon('heroicon-o-user-plus')
                ->url(fn (): string => UserResource::getUrl('create')),
        ];
    }
}

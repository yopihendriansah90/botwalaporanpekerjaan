<?php

namespace App\Filament\Resources\MessageSchedules\Pages;

use App\Filament\Resources\MessageSchedules\MessageScheduleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageMessageSchedules extends ManageRecords
{
    protected static string $resource = MessageScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Membuat Jadwal')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => MessageScheduleResource::getUrl('create')),
        ];
    }
}

<?php

namespace App\Filament\Resources\WorkReports\Pages;

use App\Filament\Resources\WorkReports\WorkReportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageWorkReports extends ManageRecords
{
    protected static string $resource = WorkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Membuat Laporan')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => WorkReportResource::getUrl('create')),
        ];
    }
}

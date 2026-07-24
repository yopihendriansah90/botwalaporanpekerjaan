<?php

namespace App\Filament\Resources\WhatsAppMessageLogs\Pages;

use App\Filament\Resources\WhatsAppMessageLogs\WhatsAppMessageLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageWhatsAppMessageLogs extends ManageRecords
{
    protected static string $resource = WhatsAppMessageLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

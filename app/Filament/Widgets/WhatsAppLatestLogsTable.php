<?php

namespace App\Filament\Widgets;

use App\Models\WhatsAppMessageLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class WhatsAppLatestLogsTable extends TableWidget
{
    protected static ?string $heading = 'Log WhatsApp terbaru';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(WhatsAppMessageLog::query()->with(['report', 'group']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('report.officer_name')
                    ->label('Nama')
                    ->placeholder('-'),
                TextColumn::make('group.name')
                    ->label('Grup tujuan')
                    ->placeholder(fn (WhatsAppMessageLog $record): string => $record->recipient_jid)
                    ->limit(35),
                TextColumn::make('message')
                    ->label('Pesan')
                    ->limit(60)
                    ->tooltip(fn (WhatsAppMessageLog $record): string => $record->message),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}

<?php

namespace App\Filament\Resources\WhatsAppMessageLogs;

use App\Filament\Resources\WhatsAppMessageLogs\Pages\ManageWhatsAppMessageLogs;
use App\Models\WhatsAppMessageLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsAppMessageLogResource extends Resource
{
    protected static ?string $model = WhatsAppMessageLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Log WhatsApp';

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?string $modelLabel = 'Log WhatsApp';

    protected static ?string $pluralModelLabel = 'Log WhatsApp';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('report.officer_name')
                    ->label('Nama')
                    ->placeholder('-'),
                TextColumn::make('group.name')
                    ->label('Grup')
                    ->placeholder(fn (WhatsAppMessageLog $record): string => $record->recipient_jid),
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
                    })
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Terkirim pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                    ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWhatsAppMessageLogs::route('/'),
        ];
    }
}

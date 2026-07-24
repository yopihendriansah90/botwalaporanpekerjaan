<?php

namespace App\Filament\Resources\MessageSchedules;

use App\Filament\Resources\MessageSchedules\Pages\CreateMessageSchedule;
use App\Filament\Resources\MessageSchedules\Pages\EditMessageSchedule;
use App\Filament\Resources\MessageSchedules\Pages\ManageMessageSchedules;
use App\Models\MessageSchedule;
use App\Models\WhatsAppGroup;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessageScheduleResource extends Resource
{
    protected static ?string $model = MessageSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Jadwal Pengiriman';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $modelLabel = 'Jadwal pengiriman';

    protected static ?string $pluralModelLabel = 'Jadwal pengiriman';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi jadwal')
                    ->description('Tentukan nama jadwal, grup penerima, dan status penggunaannya.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama jadwal')
                            ->placeholder('Contoh: Jadwal laporan mingguan')
                            ->required()
                            ->maxLength(255),
                        Select::make('whatsapp_group_ids')
                            ->label('Grup tujuan')
                            ->options(fn (): array => WhatsAppGroup::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        Select::make('is_active')
                            ->label('Status')
                            ->options([1 => 'Aktif', 0 => 'Nonaktif'])
                            ->default(1)
                            ->required(),
                        Placeholder::make('timezone_display')
                            ->label('Zona waktu')
                            ->content('WIB (Asia/Jakarta)'),
                    ])
                    ->columns(2),
                Section::make('Jadwal mingguan')
                    ->description('Tambahkan hari dan jam pengiriman. Satu hari hanya boleh memiliki satu jam.')
                    ->schema([
                        Repeater::make('slots')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Hari')->markAsRequired(),
                                TableColumn::make('Jam pengiriman')->markAsRequired(),
                            ])
                            ->schema([
                                Select::make('weekday')
                                    ->hiddenLabel()
                                    ->options([
                                        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
                                        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
                                    ])
                                    ->required()
                                    ->distinct()
                                    ->validationMessages([
                                        'distinct' => 'Hari tersebut sudah digunakan dalam jadwal ini.',
                                    ]),
                                TimePicker::make('send_time')
                                    ->hiddenLabel()
                                    ->native(false)
                                    ->format('H:i')
                                    ->displayFormat('H:i')
                                    ->timezone('Asia/Jakarta')
                                    ->seconds(false)
                                    ->required(),
                            ])
                            ->addActionLabel('Tambah hari')
                            ->reorderable()
                            ->defaultItems(1)
                            ->required()
                            ->minItems(1)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
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
                TextColumn::make('name')->label('Nama jadwal')->searchable()->sortable(),
                TextColumn::make('slots.send_time')->label('Jam aktif')->listWithLineBreaks()->limitList(3),
                TextColumn::make('whatsappGroups.name')->label('Grup tujuan')->listWithLineBreaks()->limitList(2),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')->label('Status'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMessageSchedules::route('/'),
            'create' => CreateMessageSchedule::route('/create'),
            'edit' => EditMessageSchedule::route('/{record}/edit'),
        ];
    }
}

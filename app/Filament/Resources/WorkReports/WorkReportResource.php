<?php

namespace App\Filament\Resources\WorkReports;

use App\Filament\Resources\WorkReports\Pages\ManageWorkReports;
use App\Models\WorkReport;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkReportResource extends Resource
{
    protected static ?string $model = WorkReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Laporan Pekerjaan';

    protected static ?string $modelLabel = 'Laporan pekerjaan';

    protected static ?string $pluralModelLabel = 'Laporan pekerjaan';

    protected static ?string $recordTitleAttribute = 'officer_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(fn (): ?int => auth()->id()),
                DatePicker::make('work_date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                TextInput::make('officer_name')
                    ->label('Petugas')
                    ->default(fn (): ?string => auth()->user()?->name)
                    ->required()
                    ->maxLength(255),
                Repeater::make('tasks')
                    ->label('Daftar pekerjaan')
                    ->schema([
                        TextInput::make('description')
                            ->label('Pekerjaan')
                            ->placeholder('Contoh: Membuat fitur login')
                            ->required()
                            ->maxLength(500),
                        TextInput::make('media_url')
                            ->label('Link media (opsional)')
                            ->url()
                            ->maxLength(2048),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('Tambah pekerjaan')
                    ->reorderable()
                    ->required()
                    ->minItems(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('officer_name')
            ->columns([
                TextColumn::make('work_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('officer_name')
                    ->label('Petugas')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('tasks')
                    ->label('Jumlah pekerjaan')
                    ->formatStateUsing(fn (?array $state): string => count($state ?? []) . ' pekerjaan')
                    ->badge(),
                TextColumn::make('user.name')
                    ->label('Dibuat oleh')
                    ->toggleable(),
            ])
            ->filters([])
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
            'index' => ManageWorkReports::route('/'),
        ];
    }
}

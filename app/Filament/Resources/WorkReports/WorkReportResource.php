<?php

namespace App\Filament\Resources\WorkReports;

use App\Filament\Resources\WorkReports\Pages\ManageWorkReports;
use App\Filament\Resources\WorkReports\Pages\CreateWorkReport;
use App\Filament\Resources\WorkReports\Pages\EditWorkReport;
use App\Models\WorkReport;
use App\Models\MessageSchedule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class WorkReportResource extends Resource
{
    protected static ?string $model = WorkReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Laporan Pekerjaan';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $modelLabel = 'Laporan pekerjaan';

    protected static ?string $pluralModelLabel = 'Laporan pekerjaan';

    protected static ?string $recordTitleAttribute = 'officer_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi laporan')
                    ->description('Lengkapi tanggal dan identitas karyawan yang membuat laporan.')
                    ->schema([
                        DatePicker::make('work_date')
                            ->label('Tanggal')
                            ->displayFormat('d F Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->required()
                            ->validationMessages([
                                'required' => 'Tanggal laporan wajib dipilih.',
                            ])
                            ->default(now()),
                        TextInput::make('officer_name')
                            ->label('Nama karyawan')
                            ->default(fn (): ?string => auth()->user()?->name)
                            ->required()
                            ->validationMessages([
                                'required' => 'Nama karyawan wajib diisi.',
                            ])
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Pengaturan pengiriman')
                    ->description('Pilih jadwal untuk menentukan waktu dan grup WhatsApp tujuan laporan.')
                    ->schema([
                        Select::make('message_schedule_id')
                            ->label('Jadwal pengiriman')
                            ->helperText('Jadwal menentukan hari, jam, dan grup tujuan pengiriman.')
                            ->options(fn (): array => MessageSchedule::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih jadwal pengiriman')
                            ->live()
                            ->nullable()
                            ->columnSpanFull(),
                        Placeholder::make('schedule_groups')
                            ->label('Grup tujuan')
                            ->content(function (Get $get): HtmlString {
                                $scheduleId = $get('message_schedule_id');

                                if (blank($scheduleId)) {
                                    return new HtmlString('<span class="text-sm text-gray-500">Pilih jadwal untuk melihat grup tujuan.</span>');
                                }

                                $groups = MessageSchedule::query()
                                    ->with(['whatsappGroups' => fn ($query) => $query
                                        ->where('whatsapp_groups.is_active', true)
                                        ->orderBy('whatsapp_groups.name')])
                                    ->find($scheduleId)?->whatsappGroups ?? collect();

                                if ($groups->isEmpty()) {
                                    return new HtmlString('<span class="text-sm text-danger-600">Jadwal ini belum memiliki grup WhatsApp aktif.</span>');
                                }

                                $badges = $groups->map(fn ($group): string => '<span class="inline-flex items-center rounded-md bg-primary-50 px-2.5 py-1 text-sm font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400">' . e($group->name) . '</span>')->implode(' ');

                                return new HtmlString('<div class="flex flex-wrap gap-2">' . $badges . '</div>');
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Daftar pekerjaan')
                    ->description('Tambahkan pekerjaan yang sudah diselesaikan. Nomor urut akan diisi otomatis.')
                    ->schema([
                        Repeater::make('tasks')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('No.')
                                    ->width('8%'),
                                TableColumn::make('Pekerjaan / tugas')
                                    ->markAsRequired(),
                                TableColumn::make('Link media (opsional)')
                                    ->width('32%'),
                            ])
                            ->schema([
                                TextInput::make('number')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->integer()
                                    ->default(1)
                                    ->readOnly(),
                                TextInput::make('description')
                                    ->hiddenLabel()
                                    ->placeholder('Contoh: Membuat fitur login')
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Nama pekerjaan wajib diisi.',
                                    ])
                                    ->maxLength(500),
                                TextInput::make('media_url')
                                    ->hiddenLabel()
                                    ->placeholder('https://...')
                                    ->url()
                                    ->validationMessages([
                                        'url' => 'Link media harus berupa URL yang valid, misalnya https://contoh.com/file.',
                                    ])
                                    ->maxLength(2048),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Tambah baris pekerjaan')
                            ->reorderable()
                            ->afterStateUpdated(function (Set $set, ?array $state): void {
                                foreach (array_values($state ?? []) as $index => $task) {
                                    $number = $index + 1;
                                    $key = array_keys($state ?? [])[$index] ?? $index;

                                    if ((int) ($task['number'] ?? 0) !== $number) {
                                        $set("tasks.{$key}.number", $number);
                                    }
                                }
                            })
                            ->required()
                            ->minItems(1)
                            ->validationMessages([
                                'required' => 'Tambahkan minimal satu pekerjaan.',
                                'minItems' => 'Tambahkan minimal satu pekerjaan.',
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('officer_name')
            ->columns([
                TextColumn::make('row_number')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),
                TextColumn::make('work_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('officer_name')
                    ->label('Nama')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('tasks')
                    ->label('Pekerjaan')
                    ->state(fn (WorkReport $record): int => count($record->tasks ?? []))
                    ->formatStateUsing(fn (int $state): string => "{$state} pekerjaan")
                    ->badge()
                    ->color('info'),
                TextColumn::make('whatsappGroups.name')
                    ->label('Tujuan grup')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList(2)
                    ->placeholder('Belum dipilih'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draf',
                        'scheduled' => 'Dijadwalkan',
                        'pending' => 'Menunggu kirim',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'info',
                        'pending' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Dikirim pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Dibuat oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draf',
                        'scheduled' => 'Dijadwalkan',
                        'pending' => 'Menunggu kirim',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
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
            'create' => CreateWorkReport::route('/create'),
            'edit' => EditWorkReport::route('/{record}/edit'),
        ];
    }
}

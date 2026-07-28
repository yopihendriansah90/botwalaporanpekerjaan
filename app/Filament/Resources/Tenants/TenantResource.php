<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ManageTenants;
use App\Models\Tenant;
use App\Services\TenantContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    protected static ?string $navigationLabel = 'Workspace';
    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static ?string $modelLabel = 'workspace';
    protected static ?string $pluralModelLabel = 'workspace';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! auth()->user()?->isSuperAdmin(),
                fn ($query) => $query->whereHas('users', fn ($userQuery) => $userQuery->whereKey(auth()->id())),
            );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi workspace')
                ->description('Pisahkan laporan, jadwal, pengguna, dan perangkat WhatsApp berdasarkan workspace.')
                ->schema([
                    TextInput::make('name')->label('Nama workspace')->required()->maxLength(255),
                    TextInput::make('slug')->label('Slug')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(100),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('row_number')->label('No.')->rowIndex()->alignCenter(),
            TextColumn::make('name')->label('Workspace')->searchable()->sortable(),
            TextColumn::make('users_count')->label('Pengguna')->counts('users')->numeric(),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->recordActions([
            Action::make('use')
                ->label('Gunakan')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (Tenant $record): bool => app(TenantContext::class)->id() !== $record->id)
                ->action(function (Tenant $record): void {
                    app(TenantContext::class)->set($record->id);
                }),
            EditAction::make(),
        ])->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}

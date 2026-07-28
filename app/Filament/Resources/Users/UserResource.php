<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Services\TenantContext;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Manajemen Pengguna';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'pengguna';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = app(TenantContext::class)->id();

        return parent::getEloquentQuery()
            ->when($tenantId && ! auth()->user()?->isSuperAdmin(), fn ($query) => $query->whereHas(
                'tenants',
                fn ($tenantQuery) => $tenantQuery->whereKey($tenantId),
            ));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi pengguna')
                    ->description('Kelola nama dan alamat email pengguna.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Keamanan akun')
                    ->description('Kosongkan password jika tidak ingin mengubahnya.')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(6)
                            ->same('password_confirmation')
                            ->validationMessages([
                                'required' => 'Password wajib diisi untuk pengguna baru.',
                                'min' => 'Password minimal terdiri dari 6 karakter.',
                                'same' => 'Konfirmasi password tidak sama.',
                            ]),
                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi password')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2),
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
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_type')
                    ->label('Tipe akun')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'superadmin' ? 'Superadmin' : 'User')
                    ->color(fn (?string $state): string => $state === 'superadmin' ? 'warning' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Terdaftar pada')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

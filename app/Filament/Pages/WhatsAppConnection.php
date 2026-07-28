<?php

namespace App\Filament\Pages;

use App\Models\WhatsAppConnection as WhatsAppConnectionModel;
use App\Models\WhatsAppGroup;
use App\Services\WhatsAppGatewayService;
use App\Filament\Widgets\WhatsAppConnectionStats;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;
use Throwable;

class WhatsAppConnection extends Page
{
    protected string $view = 'filament.pages.whats-app-connection';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $navigationLabel = 'Koneksi WhatsApp';

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?string $title = 'Koneksi WhatsApp';

    protected static ?string $slug = 'whatsapp-connection';

    public array $status = [];

    public array $groups = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_and_sync')
                ->label('Sinkronkan')
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (): bool => ($this->status['state'] ?? null) === 'connected')
                ->action(fn (): mixed => $this->refreshAndSync()),
            Action::make('disconnect')
                ->label('Putuskan Perangkat')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Putuskan perangkat WhatsApp?')
                ->modalDescription('Sesi WhatsApp akan dihapus dan Anda perlu memindai QR Code lagi untuk menghubungkan perangkat.')
                ->modalSubmitActionLabel('Ya, putuskan')
                ->visible(fn (): bool => in_array($this->status['state'] ?? null, ['connected', 'qr_required'], true))
                ->action(fn (): mixed => $this->disconnect()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WhatsAppConnectionStats::class,
        ];
    }

    public function mount(): void
    {
        $this->ensureConnection();
    }

    public function ensureConnection(): void
    {
        $this->refreshStatus();

        if (! in_array($this->status['state'] ?? null, ['disconnected', 'error'], true)) {
            return;
        }

        $this->startConnection();
    }

    protected function startConnection(bool $notify = false): void
    {
        try {
            $result = app(WhatsAppGatewayService::class)->connect();
            $this->status = array_merge($this->status, $result);
            $this->refreshStatus();

            if ($notify) {
                Notification::make()
                    ->title('Koneksi WhatsApp dimulai')
                    ->body('Pindai QR Code yang tampil di halaman ini.')
                    ->success()
                    ->send();
            }
        } catch (Throwable $exception) {
            $this->status = [
                'state' => 'service_unavailable',
                'last_error' => $exception->getMessage(),
            ];

            if ($notify) {
                Notification::make()
                    ->title('Gagal memulai koneksi WhatsApp')
                    ->body($exception->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    public function disconnect(): void
    {
        try {
            app(WhatsAppGatewayService::class)->disconnect();
            $this->groups = [];
            $this->startConnection();

            Notification::make()
                ->title('Sesi dihapus, QR Code sedang disiapkan')
                ->body('Pindai QR Code yang tampil untuk menghubungkan kembali.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Gagal memutuskan perangkat')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function refreshStatus(bool $autoSync = true): void
    {
        try {
            $wasConnected = ($this->status['state'] ?? null) === 'connected';
            $this->status = app(WhatsAppGatewayService::class)->status();

            $tenantId = $this->activeTenantId();
            $connection = WhatsAppConnectionModel::firstOrCreate([
                'tenant_id' => $tenantId,
                'name' => 'Koneksi utama',
            ]);

            $connection->update([
                'phone' => $this->status['phone'] ?? null,
                'status' => $this->status['state'] ?? 'disconnected',
                'last_connected_at' => ($this->status['state'] ?? null) === 'connected' ? now() : $connection->last_connected_at,
            ]);

            $this->dispatch('whatsapp-data-updated');

            if ($autoSync && ! $wasConnected && ($this->status['state'] ?? null) === 'connected') {
                $this->syncGroups(notify: false);
            }
        } catch (Throwable $exception) {
            $this->status = [
                'state' => 'service_unavailable',
                'last_error' => $exception->getMessage(),
            ];
        }
    }

    public function refreshAndSync(): void
    {
        $this->refreshStatus(autoSync: false);

        if (($this->status['state'] ?? null) !== 'connected') {
            Notification::make()
                ->title('WhatsApp belum terhubung')
                ->body('Hubungkan WhatsApp terlebih dahulu sebelum menyinkronkan grup.')
                ->warning()
                ->send();

            return;
        }

        $this->syncGroups();
    }

    public function syncGroups(bool $notify = true): void
    {
        try {
            $groups = app(WhatsAppGatewayService::class)->groups();
            $tenantId = $this->activeTenantId();
            $connection = WhatsAppConnectionModel::firstOrCreate([
                'tenant_id' => $tenantId,
                'name' => 'Koneksi utama',
            ]);

            $groupJids = collect($groups)->pluck('jid')->filter()->values()->all();

            foreach ($groups as $group) {
                if (blank($group['jid'] ?? null) || blank($group['name'] ?? null)) {
                    continue;
                }

                WhatsAppGroup::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'whatsapp_connection_id' => $connection->id,
                        'jid' => $group['jid'],
                    ],
                    [
                        'name' => $group['name'],
                        'participants_count' => $group['participants_count'] ?? null,
                        'is_active' => true,
                    ],
                );
            }

            $connection->groups()
                ->where('tenant_id', $tenantId)
                ->when(
                    filled($groupJids),
                    fn ($query) => $query->whereNotIn('jid', $groupJids),
                    fn ($query) => $query,
                )
                ->update(['is_active' => false]);

            $this->groups = $connection->groups()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray();
            $this->dispatch('whatsapp-data-updated');

            if ($notify) {
                Notification::make()
                    ->title('Daftar grup berhasil diperbarui')
                    ->success()
                    ->send();
            }
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Gagal mengambil daftar grup')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function activeTenantId(): int
    {
        $tenantId = (int) app(\App\Services\TenantContext::class)->id();

        if ($tenantId < 1) {
            throw new \RuntimeException('Workspace WhatsApp belum ditentukan.');
        }

        return $tenantId;
    }
}

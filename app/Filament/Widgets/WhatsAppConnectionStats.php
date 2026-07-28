<?php

namespace App\Filament\Widgets;

use App\Models\WhatsAppConnection;
use App\Models\WhatsAppGroup;
use App\Services\TenantContext;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class WhatsAppConnectionStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan koneksi';

    #[On('whatsapp-data-updated')]
    public function refreshStats(): void
    {
        $this->cachedStats = null;
    }

    protected function getStats(): array
    {
        $tenantId = app(TenantContext::class)->id();
        $connection = WhatsAppConnection::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->latest('id')
            ->first();
        $status = $connection?->status ?? 'disconnected';

        $statusLabel = match ($status) {
            'connected' => 'Terhubung',
            'qr_required' => 'Menunggu QR Code',
            'service_unavailable' => 'Service tidak tersedia',
            default => 'Terputus',
        };

        $statusColor = match ($status) {
            'connected' => 'success',
            'qr_required' => 'warning',
            'service_unavailable' => 'danger',
            default => 'gray',
        };

        $phone = str_replace('@s.whatsapp.net', '', (string) ($connection?->phone ?? ''));
        $lastConnected = $connection?->last_connected_at?->diffForHumans() ?? 'Belum pernah';

        return [
            Stat::make('Status koneksi', $statusLabel)
                ->description($phone !== '' ? $phone : 'Nomor belum tersedia')
                ->descriptionIcon(Heroicon::OutlinedDevicePhoneMobile)
                ->icon(Heroicon::OutlinedSignal)
                ->color($statusColor),
            Stat::make('Terakhir terhubung', $lastConnected)
                ->description('Waktu koneksi terakhir')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray'),
        ];
    }
}

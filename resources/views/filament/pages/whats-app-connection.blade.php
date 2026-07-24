<x-filament-panels::page>
    <style>
        .wa-qr-layout { display: flex; justify-content: center; padding: 2rem 0; }
        .wa-qr-phone { width: 100%; max-width: 360px; padding: 9px; overflow: hidden; border: 1px solid #3f3f46; border-radius: 42px; background: #09090b; box-shadow: 0 24px 60px rgba(0, 0, 0, .45); }
        .wa-qr-screen { position: relative; min-height: 590px; overflow: hidden; border-radius: 31px; background: #18181b; color: #fff; }
        .wa-qr-notch { position: absolute; z-index: 2; top: 0; left: 50%; width: 128px; height: 24px; transform: translateX(-50%); border-radius: 0 0 16px 16px; background: #09090b; }
        .wa-qr-topbar { display: flex; align-items: center; justify-content: space-between; padding: 36px 22px 14px; border-bottom: 1px solid rgba(255,255,255,.1); color: #a1a1aa; font-size: 12px; }
        .wa-qr-state { display: inline-flex; align-items: center; gap: 7px; }
        .wa-qr-state-dot { width: 8px; height: 8px; border-radius: 50%; background: #fbbf24; }
        .wa-qr-content { display: flex; min-height: 510px; flex-direction: column; align-items: center; justify-content: center; padding: 32px 20px; text-align: center; }
        .wa-qr-icon { display: flex; width: 56px; height: 56px; align-items: center; justify-content: center; margin-bottom: 20px; border-radius: 16px; background: rgba(251,191,36,.15); color: #fbbf24; }
        .wa-qr-icon svg { width: 32px; height: 32px; }
        .wa-qr-title { margin: 0; color: #fff; font-size: 20px; font-weight: 600; }
        .wa-qr-lead { max-width: 260px; margin: 8px 0 0; color: #a1a1aa; font-size: 14px; line-height: 1.5rem; }
        .wa-qr-image-wrap { margin-top: 24px; padding: 12px; border-radius: 16px; background: #fff; box-shadow: 0 12px 24px rgba(0,0,0,.35); }
        .wa-qr-image, .wa-qr-placeholder { width: 240px; height: 240px; }
        .wa-qr-image { display: block; }
        .wa-qr-placeholder { display: flex; align-items: center; justify-content: center; color: #71717a; font-size: 14px; text-align: center; }
        .wa-qr-help { max-width: 270px; margin: 22px 0 0; color: #71717a; font-size: 12px; line-height: 1.25rem; }
        .wa-qr-connected-id { display: flex; flex-direction: column; gap: 6px; margin-top: 28px; padding: 14px 24px; border: 1px solid rgba(34,197,94,.25); border-radius: 14px; background: rgba(34,197,94,.08); color: #a1a1aa; font-size: 12px; }
        .wa-qr-connected-id strong { color: #86efac; font-size: 17px; letter-spacing: .04em; }
        @media (max-width: 480px) {
            .wa-qr-layout { padding: 1rem 0; }
            .wa-qr-phone { max-width: 330px; }
            .wa-qr-image, .wa-qr-placeholder { width: 220px; height: 220px; }
        }
    </style>
    <div wire:poll.3s="ensureConnection">
    @php
        $isConnected = ($status['state'] ?? null) === 'connected';
        $phone = preg_replace('/:\\d+$/', '', str_replace('@s.whatsapp.net', '', (string) ($status['phone'] ?? '')));
    @endphp

    @if (in_array($status['state'] ?? null, ['qr_required', 'connecting', 'connected'], true))
        <x-filament::section
            icon="{{ $isConnected ? 'heroicon-o-check-circle' : 'heroicon-o-qr-code' }}"
            icon-color="{{ $isConnected ? 'success' : 'warning' }}"
            heading="{{ $isConnected ? 'WhatsApp Terhubung' : 'Hubungkan WhatsApp' }}"
        >
            <div class="wa-qr-layout">
                <div class="wa-qr-phone">
                    <div class="wa-qr-screen">
                        <div class="wa-qr-notch"></div>

                        <div class="wa-qr-topbar">
                            <span>Wabot</span>
                            <span class="wa-qr-state">
                                <span class="wa-qr-state-dot" style="background: {{ $isConnected ? '#22c55e' : '#fbbf24' }}"></span>
                                {{ $isConnected ? 'Terhubung' : 'Menunggu koneksi' }}
                            </span>
                        </div>

                        <div class="wa-qr-content">
                            <div class="wa-qr-icon">
                                <x-filament::icon icon="{{ $isConnected ? 'heroicon-o-check-circle' : 'heroicon-o-qr-code' }}" />
                            </div>
                            @if ($isConnected)
                                <h3 class="wa-qr-title">Terhubung</h3>
                                <p class="wa-qr-lead">Perangkat WhatsApp siap digunakan untuk mengirim laporan.</p>
                                <div class="wa-qr-connected-id">
                                    <span>ID WhatsApp</span>
                                    <strong>{{ $phone !== '' ? $phone : 'Tidak tersedia' }}</strong>
                                </div>
                            @else
                                <h3 class="wa-qr-title">Hubungkan WhatsApp</h3>
                                <p class="wa-qr-lead">
                                    Pindai QR Code ini menggunakan WhatsApp di ponsel Anda.
                                </p>

                                @if (filled($status['qr'] ?? null))
                                    <div class="wa-qr-image-wrap">
                                        <img src="{{ $status['qr'] }}" alt="QR Code WhatsApp" class="wa-qr-image">
                                    </div>
                                @else
                                    <div class="wa-qr-image-wrap">
                                        <div class="wa-qr-placeholder">Menyiapkan QR Code...</div>
                                    </div>
                                @endif

                                <p class="wa-qr-help">
                                    Buka WhatsApp → Perangkat tertaut → Tautkan perangkat, lalu arahkan kamera ke QR Code.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @elseif (($status['state'] ?? null) === 'service_unavailable')
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger" compact>
            <p class="text-sm text-danger-700 dark:text-danger-300">
                Service WhatsApp belum dapat dihubungi. Pastikan Node.js service sedang berjalan.
            </p>
        </x-filament::section>
    @endif
    </div>
</x-filament-panels::page>

<?php

namespace App\Jobs;

use App\Models\WhatsAppMessageLog;
use App\Services\WhatsAppGatewayService;
use App\Services\TenantContext;
use App\Services\WhatsAppTenantIntegrity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public WhatsAppMessageLog $messageLog)
    {
    }

    public function handle(WhatsAppGatewayService $gateway): void
    {
        $this->messageLog->refresh();

        // Job dapat sudah berada di Redis ketika laporan dijadwalkan ulang
        // atau dibatalkan. Jangan kirim log yang sudah tidak pending.
        if ($this->messageLog->status !== 'pending') {
            return;
        }

        $delivery = $this->messageLog->delivery;

        if ($delivery && ! in_array($delivery->status, ['pending', 'queued'], true)) {
            return;
        }

        app(WhatsAppTenantIntegrity::class)->assertLog($this->messageLog);

        $result = app(TenantContext::class)->run((int) $this->messageLog->tenant_id, function () use ($gateway): array {
            return $gateway->send(
                $this->messageLog->recipient_jid,
                $this->messageLog->message,
                (int) $this->messageLog->tenant_id,
            );
        });

        $this->messageLog->update([
            'status' => 'sent',
            'provider_message_id' => $result['message_id'] ?? null,
            'sent_at' => now(),
            'error_message' => null,
        ]);

        $this->messageLog->delivery?->update([
            'status' => 'sent',
            'error_message' => null,
        ]);

        $report = $this->messageLog->report;

        if ($report && ! $report->messageLogs()->whereIn('status', ['pending'])->exists()) {
            $report->update([
                'status' => $report->messageLogs()->where('status', 'failed')->exists() ? 'failed' : 'sent',
                'sent_at' => now(),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->messageLog->refresh();

        if ($this->messageLog->status !== 'pending') {
            return;
        }

        $this->messageLog->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        $this->messageLog->delivery?->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        $this->messageLog->report?->update([
            'status' => 'failed',
            'send_error' => $exception->getMessage(),
        ]);
    }
}

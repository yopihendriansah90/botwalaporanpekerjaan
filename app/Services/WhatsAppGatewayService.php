<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WhatsAppGatewayService
{
    private function client(?int $tenantId = null): PendingRequest
    {
        $tenantId ??= app(TenantContext::class)->id();

        return Http::baseUrl((string) config('services.whatsapp_gateway.url'))
            ->withHeaders([
                'x-api-key' => (string) config('services.whatsapp_gateway.token'),
                'x-tenant-id' => (string) $tenantId,
            ])
            ->acceptJson()
            ->timeout(15);
    }

    public function status(?int $tenantId = null): array
    {
        return $this->client($tenantId)->get('/status')->throw()->json();
    }

    public function connect(?int $tenantId = null): array
    {
        return $this->client($tenantId)->post('/connect')->throw()->json();
    }

    public function disconnect(?int $tenantId = null): array
    {
        return $this->client($tenantId)->post('/logout')->throw()->json();
    }

    public function groups(?int $tenantId = null): array
    {
        return $this->client($tenantId)->get('/groups')->throw()->json();
    }

    public function send(string $recipient, string $message, ?int $tenantId = null): array
    {
        return $this->client($tenantId)->post('/send', [
            'to' => $recipient,
            'text' => $message,
        ])->throw()->json();
    }
}

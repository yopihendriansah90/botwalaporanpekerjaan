<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LogicException;

class WhatsAppGatewayService
{
    private function client(?int $tenantId = null): PendingRequest
    {
        $tenantId ??= app(TenantContext::class)->id();

        if (! $tenantId) {
            throw new LogicException('Tenant WhatsApp belum ditentukan.');
        }

        $tenantSignature = hash_hmac(
            'sha256',
            (string) $tenantId,
            (string) config('services.whatsapp_gateway.signing_key'),
        );

        return Http::baseUrl((string) config('services.whatsapp_gateway.url'))
            ->withHeaders([
                'x-api-key' => (string) config('services.whatsapp_gateway.token'),
                'x-tenant-id' => (string) $tenantId,
                'x-tenant-signature' => $tenantSignature,
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
        $payload = $this->client($tenantId)->get('/groups')->throw()->json();
        $groups = $payload['groups'] ?? $payload;

        if (! is_array($groups)) {
            return [];
        }

        return collect(array_values($groups))
            ->map(function (mixed $group): ?array {
                if (! is_array($group)) {
                    return null;
                }

                $jid = $group['jid'] ?? $group['id'] ?? null;
                $name = $group['name'] ?? $group['subject'] ?? $group['group_name'] ?? null;

                if (blank($jid) || blank($name)) {
                    return null;
                }

                return [
                    'jid' => (string) $jid,
                    'name' => (string) $name,
                    'participants_count' => $group['participants_count']
                        ?? (is_array($group['participants'] ?? null) ? count($group['participants']) : null),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function send(string $recipient, string $message, ?int $tenantId = null): array
    {
        return $this->client($tenantId)->post('/send', [
            'to' => $recipient,
            'text' => $message,
        ])->throw()->json();
    }
}

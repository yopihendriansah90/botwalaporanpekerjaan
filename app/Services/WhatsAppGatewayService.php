<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WhatsAppGatewayService
{
    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.whatsapp_gateway.url'))
            ->withHeaders([
                'x-api-key' => (string) config('services.whatsapp_gateway.token'),
            ])
            ->acceptJson()
            ->timeout(15);
    }

    public function status(): array
    {
        return $this->client()->get('/status')->throw()->json();
    }

    public function connect(): array
    {
        return $this->client()->post('/connect')->throw()->json();
    }

    public function disconnect(): array
    {
        return $this->client()->post('/logout')->throw()->json();
    }

    public function groups(): array
    {
        return $this->client()->get('/groups')->throw()->json();
    }

    public function send(string $recipient, string $message): array
    {
        return $this->client()->post('/send', [
            'to' => $recipient,
            'text' => $message,
        ])->throw()->json();
    }
}

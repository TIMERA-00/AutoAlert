<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp delivery.
 *
 * Uses the official WhatsApp Business Cloud API when configured.
 * Falls back to a log-only provider in development so the whole notification
 * pipeline stays testable. Personal-account automation is never attempted.
 */
class WhatsAppService
{
    private ?string $phoneNumberId;

    private ?string $accessToken;

    private string $graphVersion;

    public function __construct(
        ?string $phoneNumberId = null,
        ?string $accessToken = null,
        ?string $graphVersion = null,
    ) {
        $this->phoneNumberId = $phoneNumberId ?? config('services.whatsapp.phone_number_id');
        $this->accessToken = $accessToken ?? config('services.whatsapp.access_token');
        $this->graphVersion = $graphVersion ?? 'v21.0';
    }

    public function isConfigured(): bool
    {
        return filled($this->phoneNumberId) && filled($this->accessToken);
    }

    /**
     * @return array{accepted: bool, external_id: ?string, error: ?string}
     */
    public function send(string $phone, string $body): array
    {
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';

        if (! $this->isConfigured()) {
            Log::info('WhatsApp (mode simulation)', ['to' => $phone, 'body' => $body]);

            return ['accepted' => true, 'external_id' => 'simulated-'.substr(sha1($body.$phone), 0, 12), 'error' => null];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/{$this->graphVersion}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $body],
                ]);

            if ($response->successful()) {
                $id = $response->json('messages.0.id');

                return ['accepted' => true, 'external_id' => $id, 'error' => null];
            }

            $error = $response->json('error.message') ?? "HTTP {$response->status()}";
            Log::warning('WhatsApp delivery failed', ['to' => $phone, 'error' => $error]);

            return ['accepted' => false, 'external_id' => null, 'error' => $error];
        } catch (\Throwable $e) {
            Log::error('WhatsApp request error', ['error' => $e->getMessage()]);

            return ['accepted' => false, 'external_id' => null, 'error' => $e->getMessage()];
        }
    }
}

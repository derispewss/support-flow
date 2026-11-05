<?php

namespace App\Modules\Support\Jobs;

use App\Modules\Support\Models\Webhook;
use App\Modules\Support\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhookDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public WebhookDelivery $delivery) {}

    public function handle(): void
    {
        $this->delivery->loadMissing('webhook');
        $webhook = $this->delivery->webhook;
        if (! $webhook instanceof Webhook) {
            throw new \RuntimeException('Webhook delivery has no webhook relation.');
        }
        $body = $this->delivery->payload;
        $signature = hash_hmac('sha256', json_encode($body, JSON_THROW_ON_ERROR), $webhook->secret);

        /** @var Response $response */
        $response = Http::timeout(10)
            ->withHeaders(['X-SupportFlow-Event' => $this->delivery->event, 'X-SupportFlow-Signature' => $signature])
            ->post($webhook->url, $body);

        $this->delivery->update([
            'attempts' => $this->delivery->attempts + 1,
            'response_status' => $response->status(),
            'response_body' => str($response->body())->limit(1000)->toString(),
            'delivered_at' => $response->successful() ? now() : null,
            'next_retry_at' => $response->successful() ? null : now()->addMinutes(5),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Webhook returned HTTP '.$response->status());
        }
    }
}

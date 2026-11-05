<?php

namespace App\Modules\Support\Services;

use App\Modules\Support\Jobs\SendWebhookDelivery;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\Webhook;
use App\Modules\Support\Models\WebhookDelivery;

class WebhookService
{
    public function dispatch(Ticket $ticket, string $event): void
    {
        $payload = [
            'id' => $ticket->getKey(),
            'event' => $event,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'organization_id' => $ticket->organization_id,
        ];

        Webhook::query()
            ->where('organization_id', $ticket->organization_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Webhook $webhook): bool => in_array('*', $webhook->events, true) || in_array($event, $webhook->events, true))
            ->each(function (Webhook $webhook) use ($event, $payload): void {
                $delivery = $webhook->deliveries()->create([
                    'event' => $event,
                    'payload' => $payload,
                ]);
                if (! $delivery instanceof WebhookDelivery) {
                    throw new \RuntimeException('Webhook delivery could not be created.');
                }
                SendWebhookDelivery::dispatch($delivery)->afterCommit();
            });
    }

    public function retry(WebhookDelivery $delivery): void
    {
        SendWebhookDelivery::dispatch($delivery);
    }
}
